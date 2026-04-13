// ─────────────────────────────────────────────
//  NexaPOS — Printer Manager
//  Supports: Browser dialog | Web Serial | Web Bluetooth
// ─────────────────────────────────────────────

const POSPrinter = {

  // ── State ──────────────────────────────────
  state:  'disconnected', // disconnected | connecting | connected | error
  type:   'browser',      // browser | serial | bluetooth
  name:   null,
  port:   null,           // Web Serial port
  btChar: null,           // Bluetooth GATT characteristic

  // ── Init on page load ──────────────────────
  init() {
    const saved = JSON.parse(localStorage.getItem('nexapos_printer') || '{}');
    this.type = saved.type || 'browser';
    this.name = saved.name || null;
    this.state = 'disconnected';
    this.updateUI();

    // Check API support
    if (this.type === 'serial' && !navigator.serial) {
      POS.toast('Web Serial not supported in this browser (use Chrome/Edge)', 'warning', 5000);
      this.type = 'browser';
      this.save();
    }
    if (this.type === 'bluetooth' && !navigator.bluetooth) {
      POS.toast('Web Bluetooth not supported in this browser (use Chrome/Edge)', 'warning', 5000);
      this.type = 'browser';
      this.save();
    }
  },

  // ── Save state ─────────────────────────────
  save() {
    localStorage.setItem('nexapos_printer', JSON.stringify({
      type: this.type,
      name: this.name,
    }));
  },

  // ── Connect USB/Serial ──────────────────────
  async connectSerial() {
    if (!navigator.serial) {
      POS.toast('Web Serial not supported. Use Chrome or Edge.', 'error'); return;
    }
    this.state = 'connecting';
    this.updateUI();
    try {
      this.port = await navigator.serial.requestPort();
      await this.port.open({ baudRate: 9600 });
      this.type  = 'serial';
      this.name  = 'USB / Serial Printer';
      this.state = 'connected';
      this.save();
      POS.toast('USB/Serial printer connected!', 'success');
    } catch (e) {
      this.state = 'error';
      this.name  = null;
      if (e.name !== 'NotFoundError') {
        POS.toast('Failed to connect: ' + (e.message || e), 'error');
      }
    }
    this.updateUI();
  },

  // ── Connect Bluetooth ──────────────────────
  async connectBluetooth() {
    if (!navigator.bluetooth) {
      POS.toast('Web Bluetooth not supported. Use Chrome or Edge.', 'error'); return;
    }
    this.state = 'connecting';
    this.updateUI();
    try {
      // Common ESC/POS BT printer service UUIDs
      const serviceUUIDs = [
        '000018f0-0000-1000-8000-00805f9b34fb', // common BT printer
        '00001101-0000-1000-8000-00805f9b34fb', // SPP Serial Port Profile
        '0000ff00-0000-1000-8000-00805f9b34fb', // some BT printers
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // Xprinter, etc.
      ];
      const device = await navigator.bluetooth.requestDevice({
        filters: [{ services: serviceUUIDs }],
        optionalServices: serviceUUIDs,
      }).catch(() =>
        // Fallback: accept any device (user picks manually)
        navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: serviceUUIDs })
      );
      const server  = await device.gatt.connect();
      // Try each service UUID to find writable characteristic
      let foundChar = null;
      for (const uuid of serviceUUIDs) {
        try {
          const svc  = await server.getPrimaryService(uuid);
          const chars = await svc.getCharacteristics();
          for (const c of chars) {
            if (c.properties.write || c.properties.writeWithoutResponse) {
              foundChar = c; break;
            }
          }
          if (foundChar) break;
        } catch { /* service not available */ }
      }
      if (!foundChar) throw new Error('No writable characteristic found on this printer');
      this.btChar = foundChar;
      this.type   = 'bluetooth';
      this.name   = device.name || 'Bluetooth Printer';
      this.state  = 'connected';
      this.save();
      POS.toast('Bluetooth printer "' + this.name + '" connected!', 'success');
      // Handle disconnection
      device.addEventListener('gattserverdisconnected', () => {
        this.btChar = null;
        this.state  = 'disconnected';
        this.updateUI();
        POS.toast('Bluetooth printer disconnected', 'warning');
      });
    } catch (e) {
      this.state = 'error';
      if (e.name !== 'NotFoundError') {
        POS.toast('Bluetooth error: ' + (e.message || e), 'error');
      } else {
        this.state = 'disconnected';
      }
    }
    this.updateUI();
  },

  // ── Disconnect ─────────────────────────────
  async disconnect() {
    if (this.port) {
      try { await this.port.close(); } catch {}
      this.port = null;
    }
    if (this.btChar) {
      try { this.btChar.service.device.gatt.disconnect(); } catch {}
      this.btChar = null;
    }
    this.state = 'disconnected';
    this.type  = 'browser';
    this.name  = null;
    this.save();
    this.updateUI();
    POS.toast('Printer disconnected', 'info');
  },

  // ── Send raw bytes ─────────────────────────
  async sendBytes(bytes) {
    const data = new Uint8Array(bytes);
    if (this.type === 'serial' && this.port) {
      const writer = this.port.writable.getWriter();
      await writer.write(data);
      writer.releaseLock();
      return true;
    }
    if (this.type === 'bluetooth' && this.btChar) {
      const chunkSize = 512;
      for (let i = 0; i < data.length; i += chunkSize) {
        await this.btChar.writeValueWithoutResponse(data.slice(i, i + chunkSize));
      }
      return true;
    }
    return false;
  },

  // ── ESC/POS: Print receipt from HTML receipt ─
  async printESCPOS(order, items, payments, outlet) {
    const lines = [];
    const W = 32; // characters per line (58mm paper)

    const center = s => {
      s = String(s);
      const pad = Math.max(0, Math.floor((W - s.length) / 2));
      return ' '.repeat(pad) + s;
    };
    const leftRight = (l, r) => {
      l = String(l); r = String(r);
      const space = Math.max(1, W - l.length - r.length);
      return l + ' '.repeat(space) + r;
    };
    const line = '-'.repeat(W);

    // Header
    lines.push(center(outlet?.name || 'NexaPOS'));
    if (outlet?.address) lines.push(center(outlet.address));
    if (outlet?.phone)   lines.push(center(outlet.phone));
    lines.push(line);
    lines.push(leftRight('Invoice:', order.invoice_no));
    lines.push(leftRight('Date:', new Date(order.created_at).toLocaleString()));
    lines.push(leftRight('Cashier:', order.cashier_name || ''));
    if (order.customer_name) lines.push(leftRight('Customer:', order.customer_name));
    lines.push(line);

    // Items
    for (const i of items) {
      lines.push(String(i.name).substring(0, W));
      lines.push(leftRight(`  x${i.quantity} @ ৳${parseFloat(i.unit_price).toFixed(2)}`, '৳' + parseFloat(i.subtotal).toFixed(2)));
    }
    lines.push(line);

    // Totals
    lines.push(leftRight('Subtotal:', '৳' + parseFloat(order.subtotal).toFixed(2)));
    if (parseFloat(order.discount_amount) > 0)
      lines.push(leftRight('Discount:', '-৳' + parseFloat(order.discount_amount).toFixed(2)));
    if (parseFloat(order.tax_amount) > 0)
      lines.push(leftRight('Tax:', '৳' + parseFloat(order.tax_amount).toFixed(2)));
    lines.push(leftRight('TOTAL:', '৳' + parseFloat(order.total).toFixed(2)));
    lines.push(line);

    // Payments
    for (const p of payments) {
      lines.push(leftRight(p.method_name + ':', '৳' + parseFloat(p.amount).toFixed(2)));
    }
    lines.push(leftRight('Paid:', '৳' + parseFloat(order.paid).toFixed(2)));
    lines.push(leftRight('Change:', '৳' + parseFloat(order.change_due).toFixed(2)));
    lines.push(line);
    lines.push(center('Thank you!'));
    lines.push('');
    lines.push('');
    lines.push('');

    // Build ESC/POS byte array
    const ESC = 0x1B, GS = 0x1D;
    const bytes = [ESC, 0x40]; // Initialize
    const enc = new TextEncoder();
    for (const l of lines) {
      bytes.push(...enc.encode(l + '\n'));
    }
    // Feed + cut
    bytes.push(GS, 0x56, 0x41, 0x05); // partial cut

    return this.sendBytes(bytes);
  },

  // ── Main print entry point ─────────────────
  async print(orderId) {
    // Browser print always works regardless of printer settings
    if (this.type === 'browser' || this.state !== 'connected') {
      this._browserPrint();
      return;
    }

    // For hardware printer: fetch receipt data and send ESC/POS
    this.updateUI('printing');
    try {
      const res = await POS.get(`${POS.API}?module=pos&action=get_receipt&id=${orderId}`);
      if (!res?.success) throw new Error('Could not load receipt data');
      const { order, items, payments, outlet } = res.data;
      const ok = await this.printESCPOS(order, items, payments, outlet);
      if (ok) {
        POS.toast('Receipt printed ✓', 'success', 2500);
        this.updateUI('connected');
      } else {
        throw new Error('Could not send to printer');
      }
    } catch (e) {
      POS.toast('Print failed: ' + (e.message || 'Unknown error'), 'error');
      this.state = 'error';
      this.updateUI();
      // Fallback to browser
      this._browserPrint();
    }
  },

  // ── Browser print fallback ─────────────────
  _browserPrint() {
    const content = document.getElementById('printRcp')?.outerHTML;
    if (!content) { POS.toast('No receipt to print', 'warning'); return; }
    this.updateUI('printing');
    const html = `<!DOCTYPE html><html><head><title>Receipt</title>
      <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Courier New',monospace;font-size:12px;padding:10px;width:76mm}
        .rcp-row{display:flex;justify-content:space-between;margin:2px 0}
        .rcp-div{border-top:1px dashed #ccc;margin:5px 0}
        .rcp-tot{font-weight:700;font-size:13px}
        .rcp-hd{text-align:center;margin-bottom:6px}
        .rcp-hd img{max-width:70px;max-height:45px;margin-bottom:4px}
        @media print{body{width:auto}}
      </style></head>
      <body>${content}<script>window.onload=function(){window.print();setTimeout(()=>window.close(),500)}<\/script></body></html>`;
    const blob = new Blob([html], { type: 'text/html' });
    const url  = URL.createObjectURL(blob);
    const w    = window.open(url, '_blank', 'width=400,height=650');
    if (!w) {
      URL.revokeObjectURL(url);
      // Fallback: iframe print
      this._iframePrint(html);
      return;
    }
    setTimeout(() => { URL.revokeObjectURL(url); this.updateUI(); }, 5000);
  },

  // ── iframe print (popup-blocked fallback) ──
  _iframePrint(html) {
    let iframe = document.getElementById('_printFrame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = '_printFrame';
      iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:400px;height:650px;border:none';
      document.body.appendChild(iframe);
    }
    const blob = new Blob([html], { type: 'text/html' });
    const url  = URL.createObjectURL(blob);
    iframe.src = url;
    iframe.onload = () => {
      try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        POS.toast('Printing via page...', 'info', 2000);
      } catch(e) {
        POS.toast('Could not print — allow popups for best results', 'warning');
      }
      setTimeout(() => URL.revokeObjectURL(url), 3000);
      this.updateUI();
    };
  },

  // ── Update topbar status dot ───────────────
  updateUI(overrideState) {
    const s   = overrideState || this.state;
    const dot = document.getElementById('printerDot');
    const txt = document.getElementById('printerStatusTxt');
    if (!dot) return;

    const config = {
      connected:    { bg: '#10b981', label: this.name ? this.name.replace(' Printer','') : 'Connected' },
      connecting:   { bg: '#f59e0b', label: 'Connecting…' },
      printing:     { bg: '#3b82f6', label: 'Printing…' },
      error:        { bg: '#dc2626', label: 'Print Error' },
      disconnected: { bg: '#6b7280', label: window.NEXAPOS?.printerEnabled ? 'No Printer' : 'Printer Off' },
    };
    const c = config[s] || config.disconnected;
    dot.style.background = c.bg;
    if (txt) txt.textContent = c.label;
  },

  // ── Open printer modal ─────────────────────
  openModal() {
    const el = document.getElementById('printerModal');
    if (!el) return;
    // Update modal state display
    const statusEl = document.getElementById('pmStatus');
    if (statusEl) {
      const map = {
        connected:    `<span style="color:#10b981;font-weight:600">● Connected — ${this.name||'Printer'}</span>`,
        connecting:   `<span style="color:#f59e0b;font-weight:600">● Connecting…</span>`,
        error:        `<span style="color:#dc2626;font-weight:600">● Connection Error</span>`,
        disconnected: `<span style="color:#6b7280;font-weight:600">○ Not Connected</span>`,
      };
      statusEl.innerHTML = map[this.state] || map.disconnected;
    }
    const typeEl = document.getElementById('pmType');
    if (typeEl) typeEl.value = this.type;
    el.classList.add('open');
  },

  closeModal() {
    document.getElementById('printerModal')?.classList.remove('open');
  },

  // ── Connect from modal ─────────────────────
  async connectFromModal() {
    const type = document.getElementById('pmType')?.value || 'browser';
    this.type = type;
    if (type === 'browser') {
      this.state = 'disconnected';
      this.name  = 'Browser Print';
      this.save();
      this.updateUI();
      POS.toast('Will use browser print dialog', 'info');
      this.closeModal();
      return;
    }
    if (type === 'serial')    await this.connectSerial();
    if (type === 'bluetooth') await this.connectBluetooth();
    this.closeModal();
  },

  // ── Test print ─────────────────────────────
  async testPrint() {
    if (this.type === 'browser' || this.state !== 'connected') {
      const shopName = window.NEXAPOS?.appName || 'NexaPOS';
      const html = `<!DOCTYPE html><html><head><title>Test</title>
        <style>body{font-family:'Courier New',monospace;font-size:12px;padding:10px;width:76mm}
        hr{border-top:1px dashed #ccc}</style></head>
        <body>
          <div style="text-align:center"><strong>*** TEST PRINT ***</strong></div>
          <hr><div>${shopName}</div>
          <div>${new Date().toLocaleString()}</div>
          <hr><div style="text-align:center">Printer is working!</div>
          <script>window.onload=function(){window.print();setTimeout(()=>window.close(),500)}<\/script>
        </body></html>`;
      const blob = new Blob([html], { type: 'text/html' });
      const url  = URL.createObjectURL(blob);
      const w    = window.open(url, '_blank', 'width=400,height=350');
      if (!w) { this._iframePrint(html); return; }
      setTimeout(() => URL.revokeObjectURL(url), 5000);
      POS.toast('Test print sent!', 'success');
      return;
    }
    // Hardware test
    const ESC = 0x1B, GS = 0x1D;
    const enc = new TextEncoder();
    const text = '*** TEST PRINT ***\n' + new Date().toLocaleString() + '\nNexaPOS - Printer OK\n\n\n';
    const bytes = [ESC, 0x40, ...enc.encode(text), GS, 0x56, 0x41, 0x05];
    try {
      const ok = await this.sendBytes(bytes);
      if (ok) POS.toast('Test print sent!', 'success');
      else    throw new Error('No printer connected');
    } catch (e) {
      POS.toast('Test failed: ' + (e.message || e), 'error');
    }
  },
};

// ─────────────────────────────────────────────
//  NexaPOS — Payment Logic
// ─────────────────────────────────────────────

const POSPayment = {

  // ── Load payment methods ───────────────────
  async loadMethods() {
    const res = await POS.get(`${POS.API}?module=pos&action=get_payment_methods`);
    POS.payMethods = res.data || [
      { id:1, name:'Cash',          type:'cash',          cash_drawer:1, color:'#10b981' },
      { id:2, name:'bKash',         type:'mobile_banking',cash_drawer:0, color:'#e91e63' },
      { id:3, name:'Nagad',         type:'mobile_banking',cash_drawer:0, color:'#f97316' },
      { id:4, name:'Rocket',        type:'mobile_banking',cash_drawer:0, color:'#7c3aed' },
      { id:5, name:'Card',          type:'card',          cash_drawer:0, color:'#2563eb' },
      { id:6, name:'Bank Transfer', type:'bank_transfer', cash_drawer:0, color:'#64748b' },
    ];
  },

  // ── Open payment modal ─────────────────────
  open() {
    if (!POS.cart.length) return;
    POS.selPayments = [];

    const grid = document.getElementById('payGrid');
    if (grid) {
      grid.innerHTML = POS.payMethods.map(m => `
        <div class="pay-m" onclick="POSPayment.selectMethod(${m.id})"
             data-id="${m.id}" style="--mc:${m.color || 'var(--accent)'}">
          ${this.methodIcon(m.type)}
          <span>${m.name}</span>
        </div>`).join('');
    }

    const total = POS.getTotal();
    const recvEl = document.getElementById('receivedAmt');
    if (recvEl) recvEl.value = total.toFixed(2);

    const totalDisp = document.getElementById('payTotalDisp');
    if (totalDisp) totalDisp.textContent = POS.fmt(total);

    this.updateChange();
    POS.openModal('payModal');
  },

  // ── Select payment method ──────────────────
  selectMethod(id) {
    const method = POS.payMethods.find(m => m.id == id);
    if (!method) return;

    document.querySelectorAll('.pay-m').forEach(el => el.classList.remove('sel'));
    document.querySelector(`.pay-m[data-id="${id}"]`)?.classList.add('sel');

    const total = POS.getTotal();
    const recv  = document.getElementById('receivedAmt');
    if (recv) recv.value = total.toFixed(2);

    POS.selPayments = [{
      method_id:      id,
      amount:         total,
      reference:      '',
      account_number: '',
    }];

    this.updateChange();

    // Show/hide reference + mobile fields
    const refArea    = document.getElementById('payRefArea');
    const mobileArea = document.getElementById('payMobileArea');
    const refLbl     = document.getElementById('payRefLabel');
    if (refArea)    refArea.style.display    = method.type !== 'cash' ? 'block' : 'none';
    if (mobileArea) mobileArea.style.display = method.type === 'mobile_banking' ? 'block' : 'none';
    if (refLbl) {
      if (method.type === 'mobile_banking') refLbl.textContent = 'Transaction ID (TrxID)';
      else if (method.type === 'card')      refLbl.textContent = 'Card Transaction ID';
      else if (method.type === 'bank_transfer') refLbl.textContent = 'Transfer Reference';
      else refLbl.textContent = 'Reference';
    }

    // ── Merchant info box ──────────────────────
    const infoBox    = document.getElementById('payMerchantInfo');
    const header     = document.getElementById('payMethodHeader');
    const iconEl     = document.getElementById('payMethodIcon');
    const lblEl      = document.getElementById('payMerchantLabel');
    const numEl      = document.getElementById('payMerchantNumber');
    const qrWrap     = document.getElementById('payQRWrap');
    const qrImg      = document.getElementById('payQRImg');
    const instBox    = document.getElementById('payInstructionBox');
    if (!infoBox) return;

    if (method.type === 'cash') {
      infoBox.style.display = 'none';
      return;
    }

    // ── Build instruction text per method ─────
    const instructions = this._buildInstructions(method);

    // ── Icons per type ─────────────────────────
    const typeIcons = {
      mobile_banking: '📱',
      card:           '💳',
      bank_transfer:  '🏦',
      credit:         '💰',
    };

    const color = method.color || '#64748b';

    // Header background = method brand color
    if (header) {
      header.style.background = color;
    }

    // Icon
    if (iconEl) iconEl.textContent = typeIcons[method.type] || '💰';

    // Label
    if (lblEl) {
      if (method.type === 'mobile_banking') {
        lblEl.textContent = `${method.name} Payment করুন`;
      } else if (method.type === 'card') {
        lblEl.textContent = 'Card Payment';
      } else if (method.type === 'bank_transfer') {
        lblEl.textContent = 'Bank Transfer';
      } else {
        lblEl.textContent = method.name;
      }
    }

    // Merchant number
    if (numEl) {
      if (method.account_number) {
        numEl.textContent = method.account_number;
        numEl.style.display = 'block';
      } else if (method.type === 'card') {
        numEl.textContent = 'Use card terminal';
        numEl.style.display = 'block';
      } else {
        numEl.textContent = 'Number not configured — Go to Settings → Payment';
        numEl.style.fontSize = '12px';
        numEl.style.display = 'block';
      }
    }

    // QR code image
    if (qrWrap && qrImg) {
      const qrPath = method.qr_image || null;
      const qrOn   = method.qr_enabled && qrPath;
      if (qrOn) {
        // Stored path is relative to web root (e.g. nexapos/public/uploads/qr/...)
        qrImg.src  = '/' + qrPath;
        qrImg.onerror = () => { qrWrap.style.display = 'none'; };
        qrWrap.style.display = 'block';
      } else {
        qrWrap.style.display = 'none';
      }
    }

    // Instruction strip below header
    if (instBox) {
      if (instructions) {
        instBox.innerHTML    = instructions;
        instBox.style.display = 'block';
      } else {
        instBox.style.display = 'none';
      }
    }

    infoBox.style.border  = 'none';
    infoBox.style.display = 'block';

    // ── MFS API: show "Pay via API" button if enabled ──
    this._renderMfsApiButton(method);
  },

  // ── MFS API button ─────────────────────────
  _mfsPollTimer: null,
  _mfsPaymentID:  null,
  _mfsRefId:      null,
  _mfsMethod:     null,

  _renderMfsApiButton(method) {
    const slug      = method.slug || method.name.toLowerCase();
    const bkashApi  = window.NEXAPOS?.bkashApiEnabled && slug === 'bkash';
    const nagadApi  = window.NEXAPOS?.nagadApiEnabled && slug === 'nagad';
    const apiEl     = document.getElementById('mfsApiBtn');
    if (!apiEl) return;
    if (bkashApi || nagadApi) {
      apiEl.style.display = 'block';
      apiEl.innerHTML = `
        <button onclick="POSPayment.initMfsPayment()" style="
          width:100%;padding:11px;border:none;border-radius:8px;
          background:${method.color || '#e91e63'};color:#fff;
          font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;
          display:flex;align-items:center;justify-content:center;gap:8px;margin-top:10px">
          📱 ${method.name} দিয়ে Pay করুন (Auto-Confirm)
        </button>
        <div id="mfsApiStatus" style="display:none;margin-top:10px"></div>`;
    } else {
      apiEl.style.display = 'none';
      apiEl.innerHTML = '';
    }
  },

  async initMfsPayment() {
    const total  = POS.getTotal();
    const inv    = 'POS-' + Date.now();
    const method = POS.selPayments[0];
    const m      = POS.payMethods.find(x => x.id == method?.method_id);
    const slug   = m ? (m.slug || m.name.toLowerCase()) : '';
    const fd     = new FormData();
    fd.append('amount',  total.toFixed(2));
    fd.append('invoice', inv);

    const statusEl = document.getElementById('mfsApiStatus');
    if (statusEl) { statusEl.style.display='block'; statusEl.innerHTML='<span style="color:#6b7280">⏳ Payment request পাঠানো হচ্ছে…</span>'; }

    try {
      let res;
      if (slug === 'bkash') {
        res = await POS.postFD(`${POS.API}?module=mfs&action=bkash_create`, fd);
      } else {
        res = await POS.postFD(`${POS.API}?module=mfs&action=nagad_create`, fd);
      }

      if (!res.success) {
        if (statusEl) statusEl.innerHTML = `<span style="color:#dc2626">❌ ${res.message}</span>`;
        return;
      }

      const d = res.data;
      this._mfsPaymentID = d.paymentID || d.refId || null;
      this._mfsRefId     = d.refId     || null;
      this._mfsMethod    = slug;

      if (statusEl) {
        let inner = '';
        if (d.bkashURL || d.redirectUrl) {
          const url = d.bkashURL || d.redirectUrl;
          inner += `<div style="text-align:center;padding:8px 0">
            <a href="${url}" target="_blank" style="display:inline-block;background:#e91e63;color:#fff;padding:10px 18px;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none">
              📱 ${slug === 'bkash' ? 'bKash' : 'Nagad'} App-এ Pay করুন
            </a>
            <div style="font-size:11px;color:#6b7280;margin-top:6px">অথবা নিচের QR স্ক্যান করুন</div>
          </div>`;
        }
        inner += `<div style="text-align:center;padding:6px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb">
          <div style="font-size:12px;color:#374151;margin-bottom:6px">⏳ Payment-এর জন্য অপেক্ষা করছি…</div>
          <div class="mfs-dots"><span>•</span><span>•</span><span>•</span></div>
          <div style="font-size:11px;color:#9ca3af;margin-top:4px">Auto-confirm হবে — refresh করতে হবে না</div>
        </div>`;
        statusEl.innerHTML = inner;
      }

      // Start polling
      clearInterval(this._mfsPollTimer);
      let attempts = 0;
      this._mfsPollTimer = setInterval(async () => {
        attempts++;
        if (attempts > 60) { clearInterval(this._mfsPollTimer); return; } // 3 min timeout

        try {
          let pollRes;
          if (slug === 'bkash') {
            pollRes = await POS.get(`${POS.API}?module=mfs&action=bkash_status&paymentID=${encodeURIComponent(this._mfsPaymentID)}`);
          } else {
            pollRes = await POS.get(`${POS.API}?module=mfs&action=nagad_verify&refId=${encodeURIComponent(this._mfsRefId)}`);
          }

          if (pollRes.success && pollRes.data?.completed) {
            clearInterval(this._mfsPollTimer);
            const trxID = pollRes.data.trxID || '';
            // Auto-fill TrxID and mark verified
            const refInp = document.getElementById('payRefInp');
            if (refInp) refInp.value = trxID;
            if (POS.selPayments[0]) POS.selPayments[0].reference = trxID;
            if (statusEl) statusEl.innerHTML = `
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;text-align:center">
                <div style="font-size:16px;margin-bottom:4px">✅</div>
                <div style="font-weight:700;color:#16a34a;font-size:13px">Payment Confirmed!</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px">TrxID: ${trxID}</div>
              </div>`;
            POS.toast(`✅ Payment confirmed — TrxID: ${trxID}`, 'success');
            // Auto-proceed if amount matches
            setTimeout(() => this.process(), 800);
          }
        } catch(_) {}
      }, 3000);
    } catch(e) {
      if (statusEl) statusEl.innerHTML = `<span style="color:#dc2626">❌ ${e.message}</span>`;
    }
  },

  // ── Build step-by-step instructions ────────
  _buildInstructions(method) {
    const num = method.account_number || '';

    // Method-specific flows (Bangladesh mobile banking)
    const flows = {
      bkash: [
        'bKash App খুলুন',
        '<strong>Payment</strong> সিলেক্ট করুন',
        `Merchant Number: <strong>${num || '...'}</strong>`,
        `Amount: <strong>৳[total]</strong> দিন`,
        'PIN দিয়ে Confirm করুন',
        'TrxID উপরে লিখুন',
      ],
      nagad: [
        'Nagad App খুলুন',
        '<strong>Payment</strong> সিলেক্ট করুন',
        `Merchant Number: <strong>${num || '...'}</strong>`,
        `Amount: <strong>৳[total]</strong> দিন`,
        'PIN দিয়ে Confirm করুন',
        'TrxID উপরে লিখুন',
      ],
      rocket: [
        'Rocket App খুলুন',
        '<strong>Payment</strong> সিলেক্ট করুন',
        `Merchant Number: <strong>${num || '...'}</strong>`,
        `Amount: <strong>৳[total]</strong> দিন`,
        'PIN দিয়ে Confirm করুন',
        'TrxID উপরে লিখুন',
      ],
    };

    const slug = (method.slug || method.name.toLowerCase());

    // Use slug-based flow if available
    if (flows[slug]) {
      const total = POS.getTotal ? POS.getTotal().toFixed(2) : '';
      const steps = flows[slug].map(s => s.replace('[total]', total));
      return steps.map((s, i) => `<span style="display:inline-block;margin-right:8px;opacity:.7">${i+1}.</span>${s}`).join('<br>');
    }

    // Generic instructions from DB
    if (method.instructions) {
      return method.instructions.replace(/\n/g, '<br>');
    }

    // Card terminal hint
    if (method.type === 'card') {
      return 'Swipe or tap card on terminal → Enter transaction ID above';
    }

    // Bank transfer hint
    if (method.type === 'bank_transfer' && num) {
      return `Transfer to account <strong>${num}</strong> → Enter reference number above`;
    }

    return '';
  },

  // ── Numpad ─────────────────────────────────
  numpad(key) {
    const el = document.getElementById('receivedAmt');
    if (!el) return;
    let val = el.value.replace(/[^\d.]/g, '');

    if (key === 'del') {
      val = val.slice(0, -1) || '0';
    } else if (key === 'exact') {
      val = POS.getTotal().toFixed(2);
    } else if (key === '.') {
      if (!val.includes('.')) val += '.';
    } else {
      val = (val === '0' || val === '0.00') ? key : val + key;
    }

    const num = parseFloat(val) || 0;
    el.value  = val.endsWith('.') ? val : num.toFixed(2);
    this.updateChange();
  },

  // ── Update change display ──────────────────
  updateChange() {
    const recv  = parseFloat(document.getElementById('receivedAmt')?.value) || 0;
    const total = POS.getTotal();
    const diff  = recv - total;

    const amtEl  = document.getElementById('changeAmt');
    const lblEl  = document.getElementById('changeLbl');
    if (!amtEl) return;

    if (diff >= 0) {
      amtEl.textContent = POS.fmt(diff);
      amtEl.className   = 'chg-amt';
      if (lblEl) lblEl.textContent = 'Change Due';
    } else {
      amtEl.textContent = '-' + POS.fmt(Math.abs(diff));
      amtEl.className   = 'chg-amt due';
      if (lblEl) lblEl.textContent  = 'Amount Due';
    }
  },

  // ── Process payment ────────────────────────
  _processing: false,

  async process() {
    if (this._processing) return;
    this._processing = true;
    const total = POS.getTotal();
    const paid  = parseFloat(document.getElementById('receivedAmt')?.value) || 0;

    if (paid < total * 0.01) {
      POS.toast('Enter payment amount', 'warning');
      this._processing = false;
      return;
    }
    if (!POS.selPayments.length) {
      POS.toast('Select a payment method', 'warning');
      this._processing = false;
      return;
    }

    // Update payment amount with what was entered
    POS.selPayments[0].amount         = paid;
    POS.selPayments[0].reference      = document.getElementById('payRefInp')?.value   || '';
    POS.selPayments[0].account_number = document.getElementById('payMobileInp')?.value || '';

    const btn = document.getElementById('procBtn');
    if (btn) {
      btn.disabled   = true;
      btn.innerHTML  = '<div class="spin"></div> Processing...';
    }

    const checkoutPayload = {
      items:          POS.cart,
      payments:       POS.selPayments,
      customer_id:    POS.customer?.id   || null,
      discount_type:  POS.discount.type,
      discount_value: POS.discount.value,
      redeem_points:  POS.redeemPoints   || 0,
      note:           document.getElementById('orderNote')?.value || '',
    };

    // Always try the server first — pre-flight offline checks are unreliable.
    // If the request fails (network error / timeout) we fall back to offline below.
    let res;
    try {
      res = await POS.post(`${POS.API}?module=pos&action=checkout`, checkoutPayload);
    } catch (_) {
      res = { success: false, message: 'Network error' };
    }

    if (btn) {
      btn.disabled  = false;
      btn.innerHTML = 'Complete Sale';
    }

    // ── Network failed mid-request — save offline ──
    if (!res.success && (res.message === 'Network error' || res.message === 'Offline')) {
      if (typeof POSOffline !== 'undefined') {
        const saved = await POSOffline.saveOrder(checkoutPayload);
        POS.closeModal('payModal');
        POSCart.clear();
        POS.toast(`Connection lost — sale saved offline (${saved.local_invoice})`, 'warning');
        this._processing = false;
        POSModals.loadHeldCount();
        return;
      }
    }

    if (!res.success) {
      POS.toast(res.message || 'Payment failed', 'error');
      this._processing = false;
      return;
    }

    const orderId = res.data ? parseInt(res.data.order_id) : null;
    POS.lastOrderId = orderId;

    // Cache updated products/methods for next offline session
    if (typeof POSOffline !== 'undefined') {
      POSOffline._cacheProducts();
      POSOffline._cachePaymentMethods();
    }

    // Cash drawer — open if enabled + auto + server says open
    if (res.data?.open_drawer && window.NEXAPOS?.drawerEnabled) {
      if (window.NEXAPOS.drawerAuto) {
        this.triggerDrawer();
      }
    }

    // Stop any MFS polling
    clearInterval(this._mfsPollTimer);
    this._mfsPaymentID = null;
    this._mfsRefId     = null;

    POS.redeemPoints = 0;
    POS.closeModal('payModal');
    POSCart.clear();

    // Show loyalty points toast if earned
    if (res.data?.points_earned > 0) {
      POS.toast(
        `★ Earned ${res.data.points_earned} pts — Balance: ${res.data.points_balance} pts`,
        'success', 5000
      );
    }

    if (orderId) {
      setTimeout(() => {
        POSModals.showReceipt(orderId);
        // Auto-print if enabled
        if (window.NEXAPOS?.printerEnabled && window.NEXAPOS?.autoPrint) {
          setTimeout(() => {
            if (typeof POSPrinter !== 'undefined') {
              POSPrinter.print(orderId);
            } else {
              POSModals.printReceipt();
            }
          }, 600);
        }
      }, 300);
    }
    POS.toast('Sale complete — ' + (res.data ? res.data.invoice_no : ''), 'success');
    this._processing = false;
    POSModals.loadHeldCount();
  },

  // ── Cash drawer ────────────────────────────
  // manualTrigger=true means user clicked the button — skip the enabled check
  triggerDrawer(manualTrigger = false) {
    if (!manualTrigger && (!window.NEXAPOS || !window.NEXAPOS.drawerEnabled)) return;
    const el = document.createElement('div');
    el.className = 'drawer-pop';
    el.innerHTML = `
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/>
      </svg>
      Cash Drawer Opening...`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2700);

    // Web Serial API for hardware drawer
    if (navigator.serial) {
      this.openDrawerSerial().catch(() => {});
    }
  },

  async openDrawerSerial() {
    // Reuse the already-connected printer port if available
    const existingPort = (typeof POSPrinter !== 'undefined' && POSPrinter.state === 'connected' && POSPrinter.type === 'serial')
      ? POSPrinter.port : null;

    const drawerCmd = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]); // ESC/POS open drawer

    if (existingPort) {
      const writer = existingPort.writable.getWriter();
      await writer.write(drawerCmd);
      writer.releaseLock();
      return;
    }
    // No connected port — open a temporary one (will prompt user once)
    const port = await navigator.serial.requestPort();
    await port.open({ baudRate: 9600 });
    const writer = port.writable.getWriter();
    await writer.write(drawerCmd);
    writer.releaseLock();
    await port.close();
  },

  // ── Method icons ───────────────────────────
  methodIcon(type) {
    const icons = {
      cash: `<svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
      </svg>`,
      mobile_banking: `<svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
      </svg>`,
      card: `<svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
      </svg>`,
      bank_transfer: `<svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zM11.5 1L2 6v2h19V6l-9.5-5z"/>
      </svg>`,
      credit: `<svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
      </svg>`,
    };
    return icons[type] || icons.card;
  },
};

// Patch: make received amount directly editable
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('receivedAmt');
  if (!el) return;
  el.readOnly = false;
  el.addEventListener('input', () => POSPayment.updateChange());
  el.addEventListener('focus', () => el.select());
});

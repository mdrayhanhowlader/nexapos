// ─────────────────────────────────────────────
//  NexaPOS — Core State & Shared Utilities
// ─────────────────────────────────────────────

const POS = {
  API: '../routes/api.php',

  // ── State ──────────────────────────────────
  cart:         [],
  customer:     null,
  discount:     { type: 'fixed', value: 0, amount: 0 },
  redeemPoints: 0,
  payMethods:   [],
  selPayments:  [],
  allProducts:  [],
  lastOrderId:  null,

  // Scanner mode: 'scanner' | 'manual' | 'both'
  // Loaded from settings on init — localStorage is the runtime override
  inputMode: localStorage.getItem('pos_input_mode') || 'both',

  // Load scanner mode from server settings (called on init)
  async loadScannerMode() {
    try {
      const r = await fetch(this.API + '?module=settings&action=get_all');
      const d = await r.json();
      if (!d.success) return;
      const autoScan    = d.data['barcode_auto_scan']    !== '0';
      const manualEntry = d.data['barcode_manual_entry'] !== '0';
      // Only override if settings have been explicitly saved (not just defaults)
      if ('barcode_auto_scan' in d.data || 'barcode_manual_entry' in d.data) {
        const derived = autoScan && manualEntry ? 'both'
                      : autoScan               ? 'scanner'
                      : manualEntry            ? 'manual'
                      : 'both'; // fallback
        // localStorage runtime override takes precedence
        if (!localStorage.getItem('pos_input_mode')) {
          this.inputMode = derived;
        }
      }
    } catch {}
  },

  // ── API Helpers ────────────────────────────
  async get(url) {
    try {
      const r = await fetch(url);
      if (r.status === 401) { window.location = 'login.php'; return; }
      return await r.json();
    } catch (e) {
      console.error('[POS] GET error:', url, e);
      return { success: false, message: 'Network error' };
    }
  },

  async post(url, data) {
    try {
      const r = await fetch(url, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(data),
      });
      if (r.status === 401) { window.location = 'login.php'; return; }
      return await r.json();
    } catch (e) {
      console.error('[POS] POST error:', url, e);
      return { success: false, message: 'Network error' };
    }
  },

  async postFD(url, fd) {
    try {
      const r = await fetch(url, { method: 'POST', body: fd });
      if (r.status === 401) { window.location = 'login.php'; return; }
      return await r.json();
    } catch (e) {
      console.error('[POS] POST FD error:', url, e);
      return { success: false, message: 'Network error' };
    }
  },

  // ── Toast ──────────────────────────────────
  toast(msg, type = 'info', dur = 3500) {
    const icons = {
      success: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
      error:   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
      info:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
      warning: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    };
    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'toastWrap';
      wrap.className = 'toast-c';
      document.body.appendChild(wrap);
    }
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = (icons[type] || '') + `<span>${msg}</span>`;
    wrap.appendChild(el);
    setTimeout(() => {
      el.style.cssText = 'opacity:0;transform:translateX(14px);transition:all .3s';
      setTimeout(() => el.remove(), 320);
    }, dur);
  },

  // ── Modal ──────────────────────────────────
  openModal(id)  { document.getElementById(id)?.classList.add('open'); },
  closeModal(id) { document.getElementById(id)?.classList.remove('open'); },

  // ── Helpers ────────────────────────────────
  fmt(n) { return '৳' + parseFloat(n || 0).toFixed(2); },

  calcItemSub(item) {
    return Math.max(0, (item.unit_price - item.discount_amount) * item.quantity);
  },

  getSubtotal() {
    return this.cart.reduce((s, i) => s + i.subtotal, 0);
  },

  getTax() {
    // Returns total tax amount for DISPLAY (receipt line item).
    // Inclusive items: extract embedded tax = sub * rate / (100 + rate)
    // Exclusive items: add-on tax = sub * rate / 100
    return this.cart.reduce((s, i) => {
      const rate = i.tax_rate || 0;
      if (!rate) return s;
      return (i.tax_inclusive ?? 1)
        ? s + i.subtotal * rate / (100 + rate)   // extract from inclusive price
        : s + i.subtotal * rate / 100;            // add-on tax
    }, 0);
  },

  getTotal() {
    const sub = this.getSubtotal();
    // Only EXCLUSIVE-tax items add extra to the total.
    // Inclusive items already have tax baked into their selling price.
    const extraTax = this.cart.reduce((s, i) => {
      const rate = i.tax_rate || 0;
      if (!rate || (i.tax_inclusive ?? 1)) return s;
      return s + i.subtotal * rate / 100;
    }, 0);
    const ptsDisc = (this.redeemPoints || 0) * (window.NEXAPOS?.pointsValue || 0);
    return Math.max(0, sub + extraTax - this.discount.amount - ptsDisc);
  },

  getCartQty() {
    return this.cart.reduce((s, i) => s + i.quantity, 0);
  },

  // ── Input Mode ─────────────────────────────
  setInputMode(mode) {
    this.inputMode = mode;
    localStorage.setItem('pos_input_mode', mode);
    this.applyInputMode();
  },

  applyInputMode() {
    const scannerMode = document.getElementById('scannerMode');
    const manualMode  = document.getElementById('manualMode');
    const manualPanel = document.getElementById('manualPanel');
    const searchInput = document.getElementById('posSearch');
    const scanStatus  = document.getElementById('scanStatus');
    const searchBadge = document.querySelector('.search-badge');

    if (!scannerMode) return;

    // Reset
    scannerMode.classList.remove('active');
    manualMode.classList.remove('active');

    if (this.inputMode === 'scanner') {
      scannerMode.classList.add('active');
      manualPanel?.classList.remove('show');
      if (searchInput) {
        searchInput.placeholder = 'Scan barcode... (auto-detect)';
        searchInput.readOnly = false;
      }
      if (scanStatus) { scanStatus.textContent = ''; scanStatus.innerHTML = '<span class="dot"></span> Scanner Active'; scanStatus.className = 'on'; }
      if (searchBadge) searchBadge.style.display = '';
    } else if (this.inputMode === 'manual') {
      manualMode.classList.add('active');
      manualPanel?.classList.add('show');
      if (searchInput) {
        searchInput.placeholder = 'Search products by name or SKU...';
        searchInput.readOnly = false;
      }
      if (scanStatus) { scanStatus.textContent = ''; scanStatus.innerHTML = '<span class="dot"></span> Manual Mode'; scanStatus.className = ''; }
      if (searchBadge) searchBadge.style.display = 'none';
    } else {
      // both
      scannerMode.classList.add('active');
      manualMode.classList.add('active');
      manualPanel?.classList.add('show');
      if (searchInput) {
        searchInput.placeholder = 'Search or scan barcode... (F2)';
        searchInput.readOnly = false;
      }
      if (scanStatus) { scanStatus.textContent = ''; scanStatus.innerHTML = '<span class="dot"></span> Scan + Manual'; scanStatus.className = 'on'; }
      if (searchBadge) searchBadge.style.display = '';
    }
  },
};

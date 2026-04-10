// ─────────────────────────────────────────────
//  NexaPOS — Scanner & Manual Input Logic
// ─────────────────────────────────────────────

const POSScanner = {

  scanBuffer:  '',
  scanTimer:   null,
  isScanEvent: false,

  // ── Initialize ─────────────────────────────
  init() {
    this.bindSearchInput();
    this.bindHardwareScanner();
    this.bindManualAdd();
    this.bindKeyboardShortcuts();

    // Apply saved mode
    POS.applyInputMode();
  },

  // ── Main search input ──────────────────────
  bindSearchInput() {
    const inp = document.getElementById('posSearch');
    if (!inp) return;

    inp.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const val = inp.value.trim();
      if (!val) return;

      const mode = POS.inputMode;

      // Barcode pattern or scanner mode — try barcode lookup first
      if (mode === 'scanner' || mode === 'both') {
        if (this.looksLikeBarcode(val)) {
          POSCart.addByBarcode(val);
          inp.value = '';
          return;
        }
      }

      // Manual / search mode — search products
      if (mode === 'manual' || mode === 'both') {
        POSProducts.load(POSProducts.activeCatId, val);
      }
    });

    inp.addEventListener('input', e => {
      const val = e.target.value;

      // Auto-detect rapid barcode scanner input (scanner types fast)
      clearTimeout(this.scanTimer);
      if (this.looksLikeBarcode(val) && val.length >= 8) {
        this.scanTimer = setTimeout(() => {
          if (POS.inputMode !== 'manual') {
            POSCart.addByBarcode(val);
            inp.value = '';
          }
        }, 120);
      } else {
        // Normal search
        if (POS.inputMode !== 'scanner') {
          this.scanTimer = setTimeout(() => {
            POSProducts.load(POSProducts.activeCatId, val);
          }, 350);
        }
      }
    });
  },

  // ── Hardware barcode scanner detection ─────
  // Hardware scanners type very fast (< 50ms per char)
  bindHardwareScanner() {
    let lastKey = 0;
    let buffer  = '';

    document.addEventListener('keypress', e => {
      const now     = Date.now();
      const timeDiff = now - lastKey;
      lastKey = now;

      const active = document.activeElement;
      const isInput = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');

      // If focus is on our search input, let bindSearchInput handle it
      if (active?.id === 'posSearch') return;

      // If focus is on other input, ignore
      if (isInput) return;

      if (e.key === 'Enter') {
        if (buffer.length >= 4) {
          if (POS.inputMode !== 'manual') {
            POSCart.addByBarcode(buffer);
          }
        }
        buffer = '';
        return;
      }

      // Fast typing = scanner (< 50ms between keystrokes)
      if (timeDiff < 50) {
        buffer += e.key;
      } else {
        buffer = e.key;
      }
    });
  },

  // ── Manual add panel ───────────────────────
  bindManualAdd() {
    const addBtn = document.getElementById('manualAddBtn');
    if (!addBtn) return;

    addBtn.addEventListener('click', () => {
      const inp = document.getElementById('manualProductSearch');
      const qty = document.getElementById('manualQty');
      if (!inp) return;

      const val = inp.value.trim();
      if (!val) { POS.toast('Enter product name, SKU or barcode', 'warning'); return; }

      const qtyVal = parseFloat(qty?.value || 1) || 1;

      this.manualSearch(val, qtyVal);
    });

    // Enter key on manual input
    const manualInp = document.getElementById('manualProductSearch');
    manualInp?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('manualAddBtn')?.click();
      }
    });
  },

  // ── Manual product search & add ────────────
  async manualSearch(query, qty = 1) {
    // Try exact barcode first
    if (this.looksLikeBarcode(query)) {
      const ok = await POSCart.addByBarcode(query);
      if (ok) {
        document.getElementById('manualProductSearch').value = '';
        return;
      }
    }

    // Search by name/SKU
    const res = await POS.get(
      `${POS.API}?module=pos&action=lookup_product&q=${encodeURIComponent(query)}`
    );
    const products = res.data || [];

    if (!products.length) {
      POS.toast('No product found for: ' + query, 'error');
      return;
    }

    if (products.length === 1) {
      // Exact match — add directly
      const p = products[0];
      for (let i = 0; i < qty; i++) POSCart.add(p);
      POS.toast(`${p.name} ×${qty} added`, 'success');
      document.getElementById('manualProductSearch').value = '';
    } else {
      // Multiple results — show in grid
      POSProducts.render(products);
      POS.toast(`${products.length} products found — tap to add`, 'info');
      document.getElementById('manualProductSearch').value = '';
    }
  },

  // ── Keyboard shortcuts ─────────────────────
  bindKeyboardShortcuts() {
    document.addEventListener('keydown', e => {
      // F2 — focus search
      if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('posSearch')?.focus();
      }
      // Ctrl/Cmd + Enter — open payment
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        if (POS.cart.length > 0) POSPayment.open();
      }
      // Escape — close modals
      if (e.key === 'Escape') {
        document.querySelectorAll('.m-bd.open').forEach(m => m.classList.remove('open'));
      }
      // Ctrl/Cmd + H — hold order
      if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
        e.preventDefault();
        POSModals.holdOrder();
      }
      // Ctrl/Cmd + D — discount
      if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        POSModals.openDiscount();
      }
    });
  },

  // ── Utility ────────────────────────────────
  looksLikeBarcode(val) {
    // Numeric string 4+ chars, or starts with typical barcode prefixes
    return /^\d{4,}$/.test(val) || /^[A-Z0-9\-]{6,}$/.test(val);
  },
};

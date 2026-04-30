// ─────────────────────────────────────────────
//  NexaPOS — Product Add-ons
// ─────────────────────────────────────────────

const POSAddons = {

  _product:   null,  // the main product being added
  _addons:    [],    // available add-ons for this product
  _selected:  {},    // { addon_id: true/false }
  _onConfirm: null,  // callback(mainProduct, selectedAddons[])

  // ── Check & prompt add-ons when adding to cart ──
  async checkAndAdd(product) {
    // Quickly check if this product has any add-ons
    let addons = [];
    try {
      const res = await POS.get(`${POS.API}?module=products&action=get_addons&product_id=${product.id}`);
      addons = res.data || [];
    } catch {}

    if (!addons.length) {
      // No add-ons — add directly
      POSCart.add(product);
      return;
    }

    // Has add-ons — show selection modal
    this._product  = product;
    this._addons   = addons;
    this._selected = {};

    // Pre-select required add-ons
    addons.forEach(a => {
      if (a.is_required) this._selected[a.addon_id] = true;
    });

    this._render();
    POS.openModal('addonsModal');
  },

  // ── Render add-on list ─────────────────────
  _render() {
    const wrap = document.getElementById('addonsListWrap');
    const title = document.getElementById('addonsModalTitle');
    const sub   = document.getElementById('addonsModalSub');
    if (!wrap) return;

    if (title) title.textContent = `Add-ons: ${this._product.name}`;
    if (sub) {
      const reqCount = this._addons.filter(a => a.is_required).length;
      sub.textContent = reqCount
        ? `${reqCount} required • Select optional add-ons below`
        : 'All add-ons are optional';
    }

    wrap.innerHTML = this._addons.map(a => {
      const checked  = this._selected[a.addon_id] ? 'checked' : '';
      const reqLabel = a.is_required
        ? '<span style="font-size:10px;background:#ef4444;color:#fff;border-radius:4px;padding:1px 5px;margin-left:5px">Required</span>'
        : '';
      const img = a.image
        ? `<img src="/nexapos/public/uploads/products/${a.image}" style="width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0">`
        : `<div style="width:36px;height:36px;border-radius:6px;background:var(--bg2);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">➕</div>`;
      return `
        <label style="display:flex;align-items:center;gap:10px;padding:10px 4px;
               border-bottom:1px solid var(--border);cursor:pointer;user-select:none"
               onclick="POSAddons.toggle(${a.addon_id}, ${a.is_required})">
          ${img}
          <div style="flex:1">
            <div style="font-size:14px;font-weight:600">${a.name}${reqLabel}</div>
            <div style="font-size:12px;color:var(--text2)">${a.unit || 'pcs'} · ৳${parseFloat(a.price).toFixed(2)}</div>
          </div>
          <div style="flex-shrink:0">
            <div id="addonChk_${a.addon_id}" style="width:20px;height:20px;border-radius:6px;border:2px solid var(--border);
                 display:flex;align-items:center;justify-content:center;font-size:13px;
                 ${checked ? 'background:var(--accent);border-color:var(--accent);color:#fff' : ''}">
              ${checked ? '✓' : ''}
            </div>
          </div>
        </label>`;
    }).join('');

    this._updateTotal();
  },

  // ── Toggle selection ───────────────────────
  toggle(addonId, isRequired) {
    if (isRequired) return; // can't deselect required
    this._selected[addonId] = !this._selected[addonId];
    const el = document.getElementById(`addonChk_${addonId}`);
    if (el) {
      const on = this._selected[addonId];
      el.style.background    = on ? 'var(--accent)' : '';
      el.style.borderColor   = on ? 'var(--accent)' : 'var(--border)';
      el.style.color         = on ? '#fff' : '';
      el.textContent         = on ? '✓' : '';
    }
    this._updateTotal();
  },

  // ── Update add-ons total display ───────────
  _updateTotal() {
    const total = this._addons
      .filter(a => this._selected[a.addon_id])
      .reduce((s, a) => s + parseFloat(a.price), 0);
    const el = document.getElementById('addonsTotalDisp');
    if (el) el.textContent = POS.fmt(total);
  },

  // ── Confirm: add main product + selected add-ons to cart ──
  confirm() {
    // Add main product
    POSCart.add(this._product);

    // Add selected add-ons as separate cart lines
    const selected = this._addons.filter(a => this._selected[a.addon_id]);
    selected.forEach(a => {
      POSCart.add({
        id:             a.addon_id,
        name:           `↳ ${a.name}`,   // indented label
        sku:            a.sku  || '',
        barcode:        '',
        selling_price:  a.price,
        price:          a.price,
        unit:           a.unit || 'pcs',
        image:          a.image || null,
        tax_rate:       0,
        tax_inclusive:  0,
        track_stock:    0,
        discount_allowed: 1,
        type:           a.type || 'service',
        category:       'Add-on',
        stock:          9999,
        _is_addon:      true,
        _parent_id:     this._product.id,
      });
    });

    if (selected.length) {
      POS.toast(`${this._product.name} + ${selected.length} add-on(s) added`, 'success');
    }

    POS.closeModal('addonsModal');
    this._product = null;
  },

  // ── Skip: add main product only ───────────
  skip() {
    if (this._product) {
      POSCart.add(this._product);
    }
    POS.closeModal('addonsModal');
    this._product = null;
  },
};

// ── Patch POSCart to intercept user-initiated product adds ──
(function patchCart() {
  // Patch addById — called when clicking a product card
  POSCart.addById = async function(id) {
    const cached = POS.allProducts.find(p => p.id == id);
    let product = cached;
    if (!product) {
      const res = await POS.get(`${POS.API}?module=products&action=get&id=${id}`);
      if (!res.success) { POS.toast('Product not found', 'error'); return; }
      product = res.data;
    }
    await POSAddons.checkAndAdd(product);
  };

  // Patch addByBarcode — called by scanner
  POSCart.addByBarcode = async function(barcode) {
    const res = await POS.get(
      `${POS.API}?module=pos&action=scan_barcode&barcode=${encodeURIComponent(barcode)}`
    );
    if (!res.success) {
      POS.toast('Not found: ' + barcode, 'error');
      return false;
    }
    const product = res.data.data;
    await POSAddons.checkAndAdd(product);
    return true;
  };
})();

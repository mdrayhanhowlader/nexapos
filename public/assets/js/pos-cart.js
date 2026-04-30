// ─────────────────────────────────────────────
//  NexaPOS — Cart Logic
// ─────────────────────────────────────────────

const POSCart = {

  // ── Add by ID ──────────────────────────────
  async addById(id) {
    const cached = POS.allProducts.find(p => p.id == id);
    if (cached) {
      this.add(cached);
      return;
    }
    const res = await POS.get(`${POS.API}?module=products&action=get&id=${id}`);
    if (res.success) this.add(res.data);
    else POS.toast('Product not found', 'error');
  },

  // ── Add by barcode ─────────────────────────
  async addByBarcode(barcode) {
    const res = await POS.get(
      `${POS.API}?module=pos&action=scan_barcode&barcode=${encodeURIComponent(barcode)}`
    );
    if (!res.success) {
      POS.toast('Not found: ' + barcode, 'error');
      return false;
    }
    this.add(res.data.data);
    POS.toast(res.data.data.name + ' added', 'success');
    return true;
  },

  // ── Core add ───────────────────────────────
  add(product) {
    const pid = product.id || product.product_id;
    const existing = POS.cart.find(i => i.product_id == pid && !i.variant_id);

    if (existing) {
      existing.quantity = parseFloat(existing.quantity) + 1;
      existing.subtotal = POS.calcItemSub(existing);
    } else {
      POS.cart.push({
        product_id:      pid,
        variant_id:      null,
        name:            product.name || product.product_name,
        sku:             product.sku     || '',
        barcode:         product.barcode || '',
        unit:            product.unit    || 'pcs',
        unit_price:      parseFloat(product.selling_price || product.price),
        quantity:        1,
        // Per-product rate; fall back to the global default from Settings
        tax_rate:        parseFloat(product.tax_rate) || (window.NEXAPOS?.taxRate || 0),
        tax_inclusive:   product.tax_inclusive ?? 1,
        discount_amount: 0,
        track_stock:     product.track_stock ?? 1,
        subtotal:        parseFloat(product.selling_price || product.price),
      });
    }
    this.render();
  },

  // ── Update quantity ────────────────────────
  updateQty(idx, delta) {
    const item = POS.cart[idx];
    if (!item) return;
    item.quantity = Math.max(0, parseFloat(item.quantity) + delta);
    if (item.quantity === 0) {
      POS.cart.splice(idx, 1);
    } else {
      item.subtotal = POS.calcItemSub(item);
    }
    this.render();
  },

  // ── Set exact quantity ─────────────────────
  setQty(idx, val) {
    const item = POS.cart[idx];
    if (!item) return;
    item.quantity = Math.max(0, parseFloat(val) || 0);
    if (item.quantity === 0) {
      POS.cart.splice(idx, 1);
    } else {
      item.subtotal = POS.calcItemSub(item);
    }
    this.render();
  },

  // ── Remove item ────────────────────────────
  remove(idx) {
    POS.cart.splice(idx, 1);
    this.render();
  },

  // ── Clear cart ─────────────────────────────
  clear() {
    POS.cart        = [];
    POS.customer    = null;
    POS.discount    = { type: 'fixed', value: 0, amount: 0 };
    const custBtn   = document.getElementById('custBtn');
    const custName  = document.getElementById('custName');
    if (custBtn)  custBtn.classList.remove('has');
    if (custName) custName.textContent = 'Walk-in Customer';
    this.render();
  },

  // ── Item-level discount ────────────────────
  itemDiscount(idx) {
    const item = POS.cart[idx];
    if (!item) return;
    const val = prompt(
      `Discount for "${item.name}"\nEnter amount (e.g. 50) or percent (e.g. 10%):`,
      item.discount_amount || 0
    );
    if (val === null) return;
    let disc = 0;
    if (String(val).includes('%')) {
      disc = item.unit_price * (parseFloat(val) / 100);
    } else {
      disc = parseFloat(val) || 0;
    }
    item.discount_amount = Math.min(Math.max(0, disc), item.unit_price);
    item.subtotal = POS.calcItemSub(item);
    this.render();
    POS.toast('Item discount applied', 'success');
  },

  // ── Render cart list ───────────────────────
  render() {
    const el = document.getElementById('cartList');
    if (!el) return;

    if (!POS.cart.length) {
      el.innerHTML = `
        <div class="cart-empty">
          <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0023.44 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
          <p>Cart is empty<br><small>Scan a barcode or tap a product</small></p>
        </div>`;
      this.updateTotals();
      return;
    }

    el.innerHTML = POS.cart.map((item, idx) => `
      <div class="ci">
        <div>
          <div class="ci-nm">${item.name}</div>
          <div class="ci-px">${POS.fmt(item.unit_price)} × ${item.quantity} ${item.unit}</div>
          ${item.discount_amount > 0
            ? `<div class="ci-disc">- ${POS.fmt(item.discount_amount)} discount</div>`
            : ''}
          <span class="ci-idiscbtn" onclick="POSCart.itemDiscount(${idx})">item discount</span>
        </div>
        <div class="ci-r">
          <div class="ci-ctrls">
            <button class="q-btn" onclick="POSCart.updateQty(${idx},-1)">−</button>
            <input
              class="q-inp"
              type="number"
              value="${item.quantity}"
              min="0"
              onchange="POSCart.setQty(${idx}, this.value)"
            >
            <button class="q-btn" onclick="POSCart.updateQty(${idx},1)">+</button>
            <button class="q-btn del" onclick="POSCart.remove(${idx})">×</button>
          </div>
          <div class="ci-sub">${POS.fmt(item.subtotal)}</div>
        </div>
      </div>`).join('');

    this.updateTotals();
  },

  // ── Update totals display ──────────────────
  updateTotals() {
    const sub   = POS.getSubtotal();
    const tax   = POS.getTax();
    const total = POS.getTotal();
    const qty   = POS.getCartQty();

    const set = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    set('subtotalDisp', POS.fmt(sub));
    set('totalDisp',    POS.fmt(total));
    set('chargeLbl',    POS.fmt(total));
    set('cartCount',    qty);

    // Discount row
    const discRow  = document.getElementById('discRow');
    const discDisp = document.getElementById('discDisp');
    if (discRow) {
      discRow.style.display = POS.discount.amount > 0 ? 'flex' : 'none';
      if (discDisp) discDisp.textContent = '-' + POS.fmt(POS.discount.amount);
    }

    // Points redemption row
    const ptsRow  = document.getElementById('ptsDiscRow');
    const ptsDisp = document.getElementById('ptsDiscDisp');
    if (ptsRow) {
      const ptsAmt = (POS.redeemPoints || 0) * (window.NEXAPOS?.pointsValue || 0);
      ptsRow.style.display = POS.redeemPoints > 0 ? 'flex' : 'none';
      if (ptsDisp) ptsDisp.textContent = '-' + POS.fmt(ptsAmt) + ` (${POS.redeemPoints} pts)`;
    }

    // VAT row
    const taxRow  = document.getElementById('taxRow');
    const taxDisp = document.getElementById('taxDisp');
    const taxLbl  = document.getElementById('taxRowLabel');
    if (taxRow) {
      taxRow.style.display = tax > 0 ? 'flex' : 'none';
      if (taxDisp) taxDisp.textContent = POS.fmt(tax);
      if (taxLbl) {
        const lbl  = window.NEXAPOS?.vatLabel || 'VAT';
        const rate = POS.cart.length ? (POS.cart[0].tax_rate || window.NEXAPOS?.taxRate || 0) : (window.NEXAPOS?.taxRate || 0);
        const incl = POS.cart.length ? (POS.cart[0].tax_inclusive ?? (window.NEXAPOS?.vatInclDefault ? 1 : 0)) : 0;
        taxLbl.textContent = `${lbl} (${rate}%${incl ? ' incl.' : ''})`;
      }
    }

    // Checkout button
    const btn = document.getElementById('checkoutBtn');
    if (btn) btn.disabled = POS.cart.length === 0;

    // Update payment modal total if open
    const payModal = document.getElementById('payModal');
    if (payModal?.classList.contains('open')) {
      set('payTotalDisp', POS.fmt(total));
      POSPayment.updateChange();
    }
  },
};

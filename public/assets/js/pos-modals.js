// ─────────────────────────────────────────────
//  NexaPOS — Modals Logic
// ─────────────────────────────────────────────

const POSModals = {

  discTabActive: 'amount',
  cashType:      'cash_in',

  // ── Customer ───────────────────────────────
  openCustomer() {
    POS.openModal('custModal');
    setTimeout(() => document.getElementById('custSearch')?.focus(), 100);
  },

  async searchCustomer(q) {
    const el = document.getElementById('custResults');
    if (!el) return;
    if (!q || q.length < 2) { el.innerHTML = ''; return; }

    const res  = await POS.get(`${POS.API}?module=customers&action=search&q=${encodeURIComponent(q)}`);
    const list = res.data || [];

    if (!list.length) {
      el.innerHTML = `<div style="padding:12px;color:var(--text3);font-size:13px">No customers found</div>`;
      return;
    }

    el.innerHTML = list.map(c => `
      <div
        onclick="POSModals.selectCustomer(${JSON.stringify(JSON.stringify(c))})"
        style="padding:10px;border:1px solid var(--border);border-radius:var(--r);
               cursor:pointer;margin-bottom:6px;transition:border-color .15s"
        onmouseenter="this.style.borderColor='var(--accent)'"
        onmouseleave="this.style.borderColor='var(--border)'"
      >
        <div style="font-weight:600;font-size:13px;color:var(--text1)">${c.name}</div>
        <div style="font-size:11px;color:var(--text2)">
          ${c.phone || '—'} &nbsp;·&nbsp; Points: ${c.loyalty_points}
          ${c.outstanding_balance > 0
            ? `<span style="color:var(--red)"> · Due: ${POS.fmt(c.outstanding_balance)}</span>`
            : ''}
        </div>
      </div>`).join('');
  },

  selectCustomer(jsonStr) {
    const c = JSON.parse(jsonStr);
    POS.customer = c;

    const btn  = document.getElementById('custBtn');
    const name = document.getElementById('custName');
    if (btn)  btn.classList.add('has');
    if (name) name.textContent = c.name + (c.phone ? ' · ' + c.phone : '');

    POS.closeModal('custModal');
    POS.toast('Customer: ' + c.name, 'info');

    // Apply customer discount if set
    if (parseFloat(c.discount_rate) > 0) {
      const sub = POS.getSubtotal();
      POS.discount = {
        type:   'percent',
        value:  parseFloat(c.discount_rate),
        amount: sub * (parseFloat(c.discount_rate) / 100),
      };
      POSCart.updateTotals();
      POS.toast(`${c.discount_rate}% customer discount applied`, 'info');
    }
  },

  clearCustomer() {
    POS.customer = null;
    POS.discount = { type: 'fixed', value: 0, amount: 0 };
    const btn  = document.getElementById('custBtn');
    const name = document.getElementById('custName');
    if (btn)  btn.classList.remove('has');
    if (name) name.textContent = 'Walk-in Customer';
    POSCart.updateTotals();
  },

  async quickAddCustomer() {
    const name  = document.getElementById('newCustName')?.value.trim();
    const phone = document.getElementById('newCustPhone')?.value.trim();
    if (!name) { POS.toast('Name is required', 'warning'); return; }

    const fd = new FormData();
    fd.append('name', name);
    fd.append('phone', phone || '');

    const res = await POS.postFD(`${POS.API}?module=pos&action=quick_add_customer`, fd);
    if (!res.success) { POS.toast(res.message, 'error'); return; }

    this.selectCustomer(JSON.stringify(res.data));
    POS.toast('Customer added', 'success');

    if (document.getElementById('newCustName'))  document.getElementById('newCustName').value  = '';
    if (document.getElementById('newCustPhone')) document.getElementById('newCustPhone').value = '';
  },

  // ── Discount ───────────────────────────────
  openDiscount() { POS.openModal('discModal'); },

  switchDiscTab(type, el) {
    this.discTabActive = type;
    document.querySelectorAll('.disc-t').forEach(t => t.classList.remove('on'));
    el.classList.add('on');
    document.getElementById('discAmtPanel').style.display  = type === 'amount'  ? 'block' : 'none';
    document.getElementById('discPctPanel').style.display  = type === 'percent' ? 'block' : 'none';
    document.getElementById('discCodePanel').style.display = type === 'code'    ? 'block' : 'none';
  },

  applyDiscount() {
    const sub = POS.getSubtotal();
    if (this.discTabActive === 'amount') {
      const v = parseFloat(document.getElementById('discAmtInp')?.value) || 0;
      POS.discount = { type: 'fixed', value: v, amount: Math.min(v, sub) };
    } else if (this.discTabActive === 'percent') {
      const p = parseFloat(document.getElementById('discPctInp')?.value) || 0;
      POS.discount = { type: 'percent', value: p, amount: sub * (p / 100) };
    }
    POS.closeModal('discModal');
    POSCart.updateTotals();
    POS.toast('Discount applied', 'success');
  },

  async applyPromoCode() {
    const code = document.getElementById('discCodeInp')?.value.trim();
    if (!code) return;
    const fd = new FormData();
    fd.append('code',  code);
    fd.append('total', POS.getTotal());
    const res = await POS.postFD(`${POS.API}?module=pos&action=apply_discount_code`, fd);
    if (!res.success) { POS.toast(res.message, 'error'); return; }
    POS.discount = { type: res.data.type, value: res.data.amount, amount: res.data.amount };
    POS.closeModal('discModal');
    POSCart.updateTotals();
    POS.toast('Code applied — -' + POS.fmt(res.data.amount), 'success');
  },

  // ── Hold orders ────────────────────────────
  async holdOrder() {
    if (!POS.cart.length) return;
    const label = prompt('Label for this held order:', 'Table ' + (Math.floor(Math.random() * 20) + 1));
    if (label === null) return;

    const res = await POS.post(`${POS.API}?module=pos&action=hold_order`, {
      label,
      items:           POS.cart,
      customer_id:     POS.customer?.id || null,
      subtotal:        POS.getSubtotal(),
      discount_amount: POS.discount.amount,
      total:           POS.getTotal(),
    });

    if (!res.success) { POS.toast(res.message, 'error'); return; }
    POSCart.clear();
    POS.toast('Order held: ' + label, 'info');
    this.loadHeldCount();
  },

  async openHeldOrders() {
    const res    = await POS.get(`${POS.API}?module=pos&action=get_held_orders`);
    const orders = res.data || [];
    const el     = document.getElementById('heldList');
    if (!el) return;

    el.innerHTML = !orders.length
      ? `<div style="text-align:center;padding:30px;color:var(--text3);font-size:13px">No held orders</div>`
      : orders.map(o => `
          <div class="held-c" onclick="POSModals.resumeHeld(${o.id})">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div class="hl">${o.label}</div>
              <button
                onclick="event.stopPropagation();POSModals.deleteHeld(${o.id},this)"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:12px;font-weight:600"
              >Delete</button>
            </div>
            <div class="hi">
              ${o.items.length} items &nbsp;·&nbsp;
              ${o.customer_name || 'Walk-in'} &nbsp;·&nbsp;
              ${new Date(o.created_at).toLocaleTimeString()}
            </div>
            <div class="ht">${POS.fmt(o.total)}</div>
          </div>`).join('');

    POS.openModal('heldModal');
  },

  async resumeHeld(id) {
    const res    = await POS.get(`${POS.API}?module=pos&action=get_held_orders`);
    const order  = (res.data || []).find(o => o.id == id);
    if (!order) return;

    POS.cart = order.items;
    POSCart.render();

    const fd = new FormData();
    fd.append('id', id);
    await POS.postFD(`${POS.API}?module=pos&action=delete_held_order`, fd);

    POS.closeModal('heldModal');
    this.loadHeldCount();
    POS.toast('Order resumed', 'info');
  },

  async deleteHeld(id, btn) {
    const fd = new FormData();
    fd.append('id', id);
    await POS.postFD(`${POS.API}?module=pos&action=delete_held_order`, fd);
    btn.closest('.held-c')?.remove();
    this.loadHeldCount();
    POS.toast('Held order deleted', 'info');
  },

  async loadHeldCount() {
    const res = await POS.get(`${POS.API}?module=pos&action=dashboard_stats`);
    const el  = document.getElementById('heldCount');
    if (el) el.textContent = res.data?.held_orders || 0;
  },

  // ── Receipt ────────────────────────────────
  async showReceipt(orderId) {
    if (!orderId) { POS.toast('Receipt not available', 'warning'); return; }
    const res = await POS.get(`${POS.API}?module=pos&action=get_receipt&id=${orderId}`);
    if (!res || !res.success || !res.data) {
      POS.toast('Could not load receipt', 'warning');
      return;
    }
    const { order, items, payments, outlet } = res.data;
    const el = document.getElementById('rcpContent');
    if (el) el.innerHTML = this.buildReceipt(order, items, payments, outlet);
    POS.openModal('rcpModal');
  },

  buildReceipt(o, items, payments, outlet) {
    const rows = items.map(i => `
      <div class="rcp-row">
        <span>${i.name} ×${i.quantity}</span>
        <span>${POS.fmt(i.subtotal)}</span>
      </div>`).join('');

    const payRows = payments.map(p => `
      <div class="rcp-row">
        <span>${p.method_name}</span>
        <span>${POS.fmt(p.amount)}</span>
      </div>`).join('');

    return `
      <div class="rcp-paper" id="printRcp">
        <div class="rcp-hd">
          <strong style="font-size:14px">${outlet?.name || 'NexaPOS'}</strong><br>
          <small>${outlet?.address || ''}</small>
          ${outlet?.phone ? `<br><small>${outlet.phone}</small>` : ''}
        </div>
        <hr class="rcp-div">
        <div class="rcp-row"><span>Invoice</span><span>${o.invoice_no}</span></div>
        <div class="rcp-row"><span>Date</span><span>${new Date(o.created_at).toLocaleString()}</span></div>
        <div class="rcp-row"><span>Cashier</span><span>${o.cashier_name}</span></div>
        ${o.customer_name ? `<div class="rcp-row"><span>Customer</span><span>${o.customer_name}</span></div>` : ''}
        <hr class="rcp-div">
        ${rows}
        <hr class="rcp-div">
        <div class="rcp-row"><span>Subtotal</span><span>${POS.fmt(o.subtotal)}</span></div>
        ${parseFloat(o.discount_amount) > 0
          ? `<div class="rcp-row"><span>Discount</span><span>-${POS.fmt(o.discount_amount)}</span></div>`
          : ''}
        ${parseFloat(o.tax_amount) > 0
          ? `<div class="rcp-row"><span>Tax</span><span>${POS.fmt(o.tax_amount)}</span></div>`
          : ''}
        <div class="rcp-row rcp-tot"><span>TOTAL</span><span>${POS.fmt(o.total)}</span></div>
        <hr class="rcp-div">
        ${payRows}
        <div class="rcp-row"><span>Paid</span><span>${POS.fmt(o.paid)}</span></div>
        <div class="rcp-row"><span>Change</span><span>${POS.fmt(o.change_due)}</span></div>
        ${parseFloat(o.due) > 0
          ? `<div class="rcp-row" style="color:var(--red)"><span>Due</span><span>${POS.fmt(o.due)}</span></div>`
          : ''}
        <hr class="rcp-div">
        <div style="text-align:center;margin-top:8px">
          <small>Thank you for your purchase!</small>
        </div>
      </div>`;
  },

  printReceipt() {
    const content = document.getElementById('printRcp')?.outerHTML;
    if (!content) return;
    const w = window.open('', '_blank', 'width=380,height=620');
    w.document.write(`<!DOCTYPE html><html><head><title>Receipt</title>
      <style>
        body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:10px}
        .rcp-row{display:flex;justify-content:space-between}
        .rcp-div{border-top:1px dashed #ccc;margin:6px 0}
        .rcp-tot{font-weight:700;font-size:13px}
      </style>
      </head><body onload="window.print();window.close()">${content}</body></html>`);
    w.document.close();
  },

  newSale() {
    POSCart.clear();
    POS.closeModal('rcpModal');
    POS.lastOrderId = null;
    setTimeout(() => document.getElementById('posSearch')?.focus(), 100);
  },

  // ── Shift ──────────────────────────────────
  async openShift() {
    const res = await POS.get(`${POS.API}?module=pos&action=get_shift_summary`);
    const el  = document.getElementById('shiftContent');
    if (!el) return;

    if (res.success) {
      const s = res.data;
      el.innerHTML = `
        <div style="background:var(--bg);border-radius:var(--r);padding:14px;margin-bottom:14px;font-size:13px">
          <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--text2)">
            <span>Opening Balance</span><strong>${POS.fmt(s.opening_balance)}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--text2)">
            <span>Total Sales</span><strong style="color:var(--green)">${POS.fmt(s.total_sales)}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--text2)">
            <span>Orders</span><strong>${s.orders_count}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--text2)">
            <span>Opened At</span><strong>${new Date(s.opened_at).toLocaleTimeString()}</strong>
          </div>
        </div>
        <div class="fg"><label>Closing Balance (৳)</label>
          <input type="number" class="fc" id="closeBalInp" value="0" min="0">
        </div>
        <div class="fg"><label>Note (optional)</label>
          <input type="text" class="fc" id="closeNoteInp" placeholder="Any note...">
        </div>
        <button
          onclick="POSModals.closeShift()"
          style="width:100%;height:42px;background:var(--red-bg);border:1.5px solid #fecaca;color:var(--red);border-radius:var(--r);font-family:var(--font);font-size:13px;font-weight:600;cursor:pointer"
        >Close Shift</button>`;
    } else {
      el.innerHTML = `
        <p style="color:var(--text2);font-size:13px;margin-bottom:14px">
          No active shift. Open one to start recording sales.
        </p>
        <div class="fg"><label>Opening Cash Balance (৳)</label>
          <input type="number" class="fc" id="openBalInp" value="0" min="0">
        </div>
        <button class="btn-chk" style="font-size:13px;padding:10px" onclick="POSModals.openNewShift()">
          Open Shift
        </button>`;
    }
    POS.openModal('shiftModal');
  },

  async openNewShift() {
    const fd = new FormData();
    fd.append('opening_balance', document.getElementById('openBalInp')?.value || 0);
    const res = await POS.postFD(`${POS.API}?module=pos&action=open_shift`, fd);
    POS.toast(res.success ? 'Shift opened' : res.message, res.success ? 'success' : 'error');
    if (res.success) POS.closeModal('shiftModal');
  },

  async closeShift() {
    if (!confirm('Are you sure you want to close this shift?')) return;
    const fd = new FormData();
    fd.append('closing_balance', document.getElementById('closeBalInp')?.value || 0);
    fd.append('note',            document.getElementById('closeNoteInp')?.value || '');
    const res = await POS.postFD(`${POS.API}?module=pos&action=close_shift`, fd);
    if (res.success) {
      const d = res.data;
      POS.toast(
        `Shift closed — Expected: ${POS.fmt(d.expected)} | Actual: ${POS.fmt(d.closing)} | Diff: ${POS.fmt(d.difference)}`,
        'info', 6000
      );
      POS.closeModal('shiftModal');
    } else {
      POS.toast(res.message, 'error');
    }
  },

  // ── Cash in/out ────────────────────────────
  openCash() { POS.openModal('cashModal'); },

  setCashType(type, el) {
    this.cashType = type;
    document.querySelectorAll('#cashModal .disc-t').forEach(t => t.classList.remove('on'));
    el.classList.add('on');
  },

  async recordCash() {
    const amount = parseFloat(document.getElementById('cashAmtInp')?.value) || 0;
    if (amount <= 0) { POS.toast('Enter a valid amount', 'warning'); return; }
    const fd = new FormData();
    fd.append('type',   this.cashType);
    fd.append('amount', amount);
    fd.append('reason', document.getElementById('cashReasonInp')?.value || '');
    const res = await POS.postFD(`${POS.API}?module=pos&action=cash_in_out`, fd);
    POS.toast(res.success ? res.message : res.message, res.success ? 'success' : 'error');
    if (res.success) {
      POS.closeModal('cashModal');
      if (document.getElementById('cashAmtInp'))    document.getElementById('cashAmtInp').value    = '';
      if (document.getElementById('cashReasonInp')) document.getElementById('cashReasonInp').value = '';
    }
  },
};

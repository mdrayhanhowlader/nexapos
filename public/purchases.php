<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
$user = Auth::user();
$appName = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Purchases — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/pages.css">
<style>
.po-items{width:100%;border-collapse:collapse;margin-top:8px}
.po-items th{text-align:left;font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;padding:8px 10px;border-bottom:1px solid var(--border);background:var(--bg)}
.po-items td{padding:8px 10px;border-bottom:1px solid #f3f4f6;font-size:13px;vertical-align:middle}
.po-items tr:last-child td{border-bottom:none}
.po-items input{width:100%;height:34px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;outline:none}
.po-items input:focus{border-color:var(--accent)}
.rm-row{width:28px;height:28px;background:var(--red-bg);border:1px solid #fecaca;border-radius:6px;color:var(--red);cursor:pointer;display:grid;place-items:center}
.rm-row svg{width:14px;height:14px;fill:currentColor}
.po-total{display:flex;justify-content:flex-end;margin-top:12px}
.po-total table{border-collapse:collapse;min-width:280px}
.po-total td{padding:6px 12px;font-size:13px}
.po-total td:first-child{color:var(--text2)}
.po-total td:last-child{text-align:right;font-weight:600}
.po-total .grand td{font-size:15px;font-weight:700;color:var(--text1);border-top:2px solid var(--border);padding-top:10px}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/resources/views/partials/sidebar.php'; ?>
<div class="main">
<?php include dirname(__DIR__) . '/resources/views/partials/topbar.php'; ?>
<div class="content">

  <div class="page-header">
    <div>
      <h2 class="page-title">Purchases</h2>
      <p class="page-sub">Manage stock purchases from suppliers</p>
    </div>
    <button class="btn btn-primary" onclick="openAdd()">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      New Purchase
    </button>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Orders</span><div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg></div></div>
      <div class="kpi-value" id="kTotal">—</div>
      <div class="kpi-sub">All purchases</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Pending</span><div class="kpi-icon" style="background:#fffbeb;color:#f59e0b"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div></div>
      <div class="kpi-value" id="kPending">—</div>
      <div class="kpi-sub">Awaiting receipt</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Received</span><div class="kpi-icon" style="background:#ecfdf5;color:#059669"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div></div>
      <div class="kpi-value" id="kReceived">—</div>
      <div class="kpi-sub">Stock received</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Due</span><div class="kpi-icon" style="background:#fef2f2;color:#ef4444"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div></div>
      <div class="kpi-value" id="kDue">—</div>
      <div class="kpi-sub">Outstanding balance</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search reference, supplier…" oninput="debounceLoad()">
    </div>
    <select class="filter-select" id="statusFilter" onchange="currentPage=1;loadList()">
      <option value="">All Status</option>
      <option value="pending">Pending</option>
      <option value="received">Received</option>
      <option value="partial">Partial</option>
      <option value="cancelled">Cancelled</option>
    </select>
    <input type="date" class="filter-select" id="fromDate" onchange="currentPage=1;loadList()">
    <input type="date" class="filter-select" id="toDate" onchange="currentPage=1;loadList()">
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Reference</th>
          <th>Supplier</th>
          <th>Date</th>
          <th>Items</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Due</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="poBody"><tr><td colspan="9" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- New Purchase Modal -->
<div class="modal-backdrop" id="poModal">
  <div class="modal" style="max-width:760px">
    <div class="modal-head">
      <h3 id="modalTitle">New Purchase Order</h3>
      <button class="modal-close" onclick="closeModal('poModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Supplier</label>
          <select class="form-control" id="poSupplier"><option value="">— Select Supplier —</option></select>
        </div>
        <div class="form-group">
          <label>Expected Date</label>
          <input class="form-control" id="poExpected" type="date">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" id="poStatus">
            <option value="pending">Pending</option>
            <option value="received">Received</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount Paid (৳)</label>
          <input class="form-control" id="poPaid" type="number" min="0" step="0.01" placeholder="0.00" oninput="calcTotal()">
        </div>
      </div>

      <!-- Items -->
      <div style="margin-top:8px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <label style="font-size:13px;font-weight:600">Items *</label>
          <div style="display:flex;gap:8px;align-items:center">
            <select class="filter-select" id="itemProdSel" style="min-width:200px"><option value="">— Add Product —</option></select>
            <button class="btn btn-ghost" style="padding:6px 12px;font-size:12px" onclick="addItem()">+ Add</button>
          </div>
        </div>
        <table class="po-items">
          <thead><tr><th>Product</th><th>Qty</th><th>Unit Cost (৳)</th><th>Tax %</th><th>Subtotal</th><th></th></tr></thead>
          <tbody id="itemsBody"></tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="po-total">
        <table>
          <tr><td>Subtotal</td><td id="tSubtotal">৳0.00</td></tr>
          <tr><td>Tax</td><td id="tTax">৳0.00</td></tr>
          <tr><td>Discount</td><td><input style="width:90px;height:28px;padding:0 8px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;text-align:right" id="poDiscount" type="number" min="0" step="0.01" value="0" oninput="calcTotal()"></td></tr>
          <tr><td>Shipping</td><td><input style="width:90px;height:28px;padding:0 8px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;text-align:right" id="poShipping" type="number" min="0" step="0.01" value="0" oninput="calcTotal()"></td></tr>
          <tr class="grand"><td>Grand Total</td><td id="tTotal">৳0.00</td></tr>
        </table>
      </div>

      <div class="form-group" style="margin-top:12px">
        <label>Notes</label>
        <textarea class="form-control" id="poNote" placeholder="Internal notes…" rows="2"></textarea>
      </div>

      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('poModal')">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="savePO()">Create Purchase</button>
      </div>
    </div>
  </div>
</div>

<!-- View/Receive Modal -->
<div class="modal-backdrop" id="viewModal">
  <div class="modal" style="max-width:640px">
    <div class="modal-head">
      <h3 id="viewTitle">Purchase Details</h3>
      <button class="modal-close" onclick="closeModal('viewModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body" id="viewBody">Loading…</div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let currentPage = 1, totalPages = 1, saving = false;
let items = [], products = [], suppliers = [];

const dbLoad = debounce(() => { currentPage=1; loadList(); }, 350);
function debounceLoad() { dbLoad(); }

const statusColors = { pending:'badge-amber', received:'badge-green', partial:'badge-blue', cancelled:'badge-red' };
const fmt = v => '৳' + parseFloat(v||0).toFixed(2);

async function init() {
  // Load suppliers
  const sr = await api(`${API}?module=products&action=suppliers`);
  suppliers = sr.data || [];
  const sel = document.getElementById('poSupplier');
  sel.innerHTML = '<option value="">— Select Supplier —</option>' + suppliers.map(s=>`<option value="${s.id}">${s.name}</option>`).join('');

  // Load products for item selector
  const pr = await api(`${API}?module=products&action=list&per_page=500&status=active`);
  products = pr.data?.products || [];
  const ps = document.getElementById('itemProdSel');
  ps.innerHTML = '<option value="">— Add Product —</option>' + products.map(p=>`<option value="${p.id}">${p.name} (${p.sku||'—'})</option>`).join('');

  loadSummary();
  loadList();
}

async function loadSummary() {
  const res = await api(`${API}?module=purchases&action=summary`);
  if (res.success) {
    document.getElementById('kTotal').textContent = res.data.total;
    document.getElementById('kPending').textContent = res.data.pending;
    document.getElementById('kReceived').textContent = res.data.received;
    document.getElementById('kDue').textContent = fmt(res.data.due);
  }
}

async function loadList() {
  document.getElementById('poBody').innerHTML = `<tr><td colspan="9" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q = document.getElementById('searchInput').value;
  const s = document.getElementById('statusFilter').value;
  const f = document.getElementById('fromDate').value;
  const t = document.getElementById('toDate').value;
  let url = `${API}?module=purchases&action=list&page=${currentPage}&per_page=20`;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (s) url += `&status=${s}`;
  if (f) url += `&from=${f}`;
  if (t) url += `&to=${t}`;
  const res = await api(url);
  const rows = res.data?.purchases || [];
  const pg = res.data?.pagination || {};
  totalPages = pg.total_pages || 1;

  if (!rows.length) {
    document.getElementById('poBody').innerHTML = `<tr><td colspan="9" class="tbl-empty">No purchases found</td></tr>`;
    document.getElementById('pagination').style.display = 'none';
    return;
  }

  document.getElementById('poBody').innerHTML = rows.map(p => `
    <tr>
      <td><strong>${p.reference||'—'}</strong></td>
      <td>${p.supplier_name||'—'}</td>
      <td style="color:var(--text3);font-size:12px">${p.created_at?.substring(0,10)||'—'}</td>
      <td style="text-align:center">${p.item_count||'—'}</td>
      <td style="font-weight:600">${fmt(p.total)}</td>
      <td style="color:#059669">${fmt(p.paid)}</td>
      <td style="color:${parseFloat(p.due||0)>0?'var(--red)':'var(--text3)'};font-weight:${parseFloat(p.due||0)>0?'700':'400'}">${fmt(p.due)}</td>
      <td><span class="badge ${statusColors[p.status]||'badge-blue'}">${p.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="viewPO(${p.id})" title="View"><svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
          ${p.status==='pending'?`<button class="action-btn" onclick="receivePO(${p.id})" title="Receive Stock" style="color:#059669"><svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.87.18-1.3C18 2.1 15.9 0 13.2 0c-1.6 0-3 .8-3.9 2.03L8 3.5 6.7 2.03C5.8.8 4.4 0 2.8 0 1.12 0 0 1.12 0 2.7c0 .43.11.86.18 1.3H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-5.5 5l-3 3-1-1 1.99-2H9v-1.5h3.49L10.5 7.5l1-1 3 3-.5.5.5.5z"/></svg></button>`:''}
          ${p.status!=='received'?`<button class="action-btn action-btn-danger" onclick="cancelPO(${p.id})" title="Cancel"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>`:''}
        </div>
      </td>
    </tr>`).join('');

  renderPagination(pg, 'loadList');
  document.getElementById('pagination').style.display = totalPages > 1 ? 'flex' : 'none';
}

// Item management
function addItem() {
  const sel = document.getElementById('itemProdSel');
  const id = sel.value; if (!id) return;
  const prod = products.find(p=>p.id==id);
  if (!prod) return;
  if (items.find(i=>i.product_id==id)) { toast('Product already added','warning'); return; }
  items.push({ product_id: id, name: prod.name, quantity: 1, unit_cost: parseFloat(prod.cost_price||0), tax_rate: parseFloat(prod.tax_rate||0) });
  sel.value = '';
  renderItems();
}

function removeItem(idx) {
  items.splice(idx, 1);
  renderItems();
}

function renderItems() {
  const tbody = document.getElementById('itemsBody');
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text3);font-size:13px">No items added yet</td></tr>`;
    calcTotal(); return;
  }
  tbody.innerHTML = items.map((it, i) => `
    <tr>
      <td style="font-weight:500">${it.name}</td>
      <td><input type="number" min="1" step="1" value="${it.quantity}" oninput="items[${i}].quantity=parseFloat(this.value)||1;calcTotal()" style="width:80px"></td>
      <td><input type="number" min="0" step="0.01" value="${it.unit_cost}" oninput="items[${i}].unit_cost=parseFloat(this.value)||0;calcTotal()" style="width:100px"></td>
      <td><input type="number" min="0" step="0.5" value="${it.tax_rate}" oninput="items[${i}].tax_rate=parseFloat(this.value)||0;calcTotal()" style="width:70px"></td>
      <td id="sub${i}" style="font-weight:600">${fmt(it.quantity*it.unit_cost)}</td>
      <td><button class="rm-row" onclick="removeItem(${i})"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button></td>
    </tr>`).join('');
  calcTotal();
}

function calcTotal() {
  let sub=0, tax=0;
  items.forEach((it,i) => {
    const s = (parseFloat(it.quantity)||1) * (parseFloat(it.unit_cost)||0);
    const t = s * ((parseFloat(it.tax_rate)||0)/100);
    sub += s; tax += t;
    const el = document.getElementById(`sub${i}`);
    if (el) el.textContent = fmt(s);
  });
  const disc = parseFloat(document.getElementById('poDiscount')?.value||0);
  const ship = parseFloat(document.getElementById('poShipping')?.value||0);
  const total = sub + tax - disc + ship;
  document.getElementById('tSubtotal').textContent = fmt(sub);
  document.getElementById('tTax').textContent = fmt(tax);
  document.getElementById('tTotal').textContent = fmt(total);
}

function openAdd() {
  document.getElementById('modalTitle').textContent = 'New Purchase Order';
  document.getElementById('saveBtn').textContent = 'Create Purchase';
  document.getElementById('poSupplier').value = '';
  document.getElementById('poExpected').value = '';
  document.getElementById('poStatus').value = 'pending';
  document.getElementById('poPaid').value = '0';
  document.getElementById('poDiscount').value = '0';
  document.getElementById('poShipping').value = '0';
  document.getElementById('poNote').value = '';
  items = [];
  renderItems();
  document.getElementById('poModal').classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

async function savePO() {
  if (saving) return;
  if (!items.length) { toast('Add at least one item', 'error'); return; }
  saving = true;
  document.getElementById('saveBtn').disabled = true;
  const fd = new FormData();
  fd.append('supplier_id', document.getElementById('poSupplier').value);
  fd.append('expected_date', document.getElementById('poExpected').value);
  fd.append('status', document.getElementById('poStatus').value);
  fd.append('paid', document.getElementById('poPaid').value);
  fd.append('discount_amount', document.getElementById('poDiscount').value);
  fd.append('shipping_cost', document.getElementById('poShipping').value);
  fd.append('note', document.getElementById('poNote').value);
  fd.append('items', JSON.stringify(items));
  const res = await apiFD(`${API}?module=purchases&action=save`, fd);
  saving = false;
  document.getElementById('saveBtn').disabled = false;
  if (res.success) {
    toast('Purchase created', 'success');
    closeModal('poModal');
    loadSummary();
    loadList();
  } else {
    toast(res.message || 'Failed to save', 'error');
  }
}

async function viewPO(id) {
  document.getElementById('viewBody').innerHTML = '<div class="tbl-loading"><div class="spin"></div> Loading…</div>';
  document.getElementById('viewModal').classList.add('open');
  const res = await api(`${API}?module=purchases&action=get&id=${id}`);
  if (!res.success) { document.getElementById('viewBody').innerHTML = '<p>Error loading purchase</p>'; return; }
  const p = res.data;
  document.getElementById('viewTitle').textContent = `Purchase ${p.reference}`;
  const statusC = statusColors[p.status] || 'badge-blue';
  document.getElementById('viewBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
      <div><div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-bottom:4px">Reference</div><div style="font-weight:700">${p.reference}</div></div>
      <div><div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-bottom:4px">Status</div><span class="badge ${statusC}">${p.status}</span></div>
      <div><div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-bottom:4px">Supplier</div><div>${p.supplier_name||'—'}</div></div>
      <div><div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-bottom:4px">Date</div><div>${p.created_at?.substring(0,10)||'—'}</div></div>
    </div>
    <table class="po-items" style="margin-bottom:16px">
      <thead><tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th>Subtotal</th></tr></thead>
      <tbody>${(p.items||[]).map(it=>`<tr><td>${it.product_name}</td><td>${it.quantity}</td><td>${fmt(it.unit_cost)}</td><td>${fmt(it.subtotal)}</td></tr>`).join('')}</tbody>
    </table>
    <div style="text-align:right;font-size:13px;display:flex;flex-direction:column;align-items:flex-end;gap:4px">
      <div>Subtotal: <strong>${fmt(p.subtotal)}</strong></div>
      ${parseFloat(p.tax_amount||0)>0?`<div>Tax: <strong>${fmt(p.tax_amount)}</strong></div>`:''}
      ${parseFloat(p.discount_amount||0)>0?`<div>Discount: <strong>-${fmt(p.discount_amount)}</strong></div>`:''}
      ${parseFloat(p.shipping_cost||0)>0?`<div>Shipping: <strong>${fmt(p.shipping_cost)}</strong></div>`:''}
      <div style="font-size:16px;font-weight:700;border-top:2px solid var(--border);padding-top:8px;margin-top:4px">Total: ${fmt(p.total)}</div>
      <div style="color:#059669">Paid: ${fmt(p.paid)}</div>
      <div style="color:${parseFloat(p.due||0)>0?'var(--red)':'var(--text3)'}">Due: ${fmt(p.due)}</div>
    </div>
    ${p.note?`<div style="margin-top:12px;font-size:13px;color:var(--text2);background:var(--bg);padding:12px;border-radius:8px">${p.note}</div>`:''}`;
}

async function receivePO(id) {
  const res = await api(`${API}?module=purchases&action=get&id=${id}`);
  if (!res.success) { toast('Failed to load purchase', 'error'); return; }
  const p = res.data;
  const fd = new FormData();
  fd.append('purchase_id', id);
  const receiveItems = (p.items||[]).map(it => ({
    id: it.id, product_id: it.product_id,
    received_qty: it.quantity - (it.received_qty||0),
    unit_cost: it.unit_cost
  }));
  fd.append('items', JSON.stringify(receiveItems));
  if (!confirm(`Mark purchase ${p.reference} as received? This will update inventory stock.`)) return;
  const rres = await apiFD(`${API}?module=purchases&action=receive`, fd);
  if (rres.success) { toast('Stock received successfully', 'success'); loadSummary(); loadList(); }
  else toast(rres.message||'Failed', 'error');
}

async function cancelPO(id) {
  if (!confirm('Cancel this purchase order?')) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=purchases&action=delete`, fd);
  if (res.success) { toast('Purchase cancelled', 'success'); loadSummary(); loadList(); }
  else toast(res.message||'Failed', 'error');
}

document.getElementById('poModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('poModal'); });
document.getElementById('viewModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('viewModal'); });

init();
</script>
</body>
</html>

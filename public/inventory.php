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
<title>Inventory — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/pages.css">
</head>
<body>
<?php include dirname(__DIR__) . '/resources/views/partials/sidebar.php'; ?>
<div class="main">
<?php include dirname(__DIR__) . '/resources/views/partials/topbar.php'; ?>
<div class="content">

  <div class="page-header">
    <div>
      <h2 class="page-title">Inventory</h2>
      <p class="page-sub">Monitor stock levels and movements</p>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-ghost" onclick="switchView('movements')">
        <svg viewBox="0 0 24 24"><path d="M9 5v2h6.59L4 18.59 5.41 20 17 8.41V15h2V5z"/></svg>
        Movements
      </button>
      <button class="btn btn-primary" onclick="openAdjust()">
        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        Adjust Stock
      </button>
    </div>
  </div>

  <!-- View toggle -->
  <div style="display:flex;gap:8px;margin-bottom:16px">
    <button class="btn btn-primary" id="btnStock" onclick="switchView('stock')">Stock Levels</button>
    <button class="btn btn-ghost" id="btnMov" onclick="switchView('movements')">Stock Movements</button>
  </div>

  <!-- Stock View -->
  <div id="viewStock">
    <div class="filter-row">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="stockSearch" placeholder="Search product, SKU…" oninput="dbLoadStock()">
      </div>
      <select class="filter-select" id="stockCat" onchange="stockPage=1;loadStock()">
        <option value="">All Categories</option>
      </select>
      <select class="filter-select" id="stockAlert" onchange="stockPage=1;loadStock()">
        <option value="">All Stock</option>
        <option value="1">Low Stock Only</option>
      </select>
    </div>
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Unit</th>
            <th>Stock</th>
            <th>Alert Qty</th>
            <th>Stock Value</th>
            <th></th>
          </tr></thead>
          <tbody id="stockBody"><tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="stockPag" style="display:none"></div>
    </div>
  </div>

  <!-- Movements View -->
  <div id="viewMovements" style="display:none">
    <div class="filter-row">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="movSearch" placeholder="Search product…" oninput="dbLoadMov()">
      </div>
    </div>
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>Product</th>
            <th>Type</th>
            <th>Qty Before</th>
            <th>Change</th>
            <th>Qty After</th>
            <th>Reference</th>
            <th>User</th>
            <th>Date</th>
          </tr></thead>
          <tbody id="movBody"><tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="movPag" style="display:none"></div>
    </div>
  </div>
</div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal-backdrop" id="adjModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Adjust Stock</h3>
      <button class="modal-close" onclick="closeModal()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Product *</label>
        <select class="form-control" id="adjProd"></select>
      </div>
      <div class="form-group">
        <label>Adjustment Type</label>
        <select class="form-control" id="adjType">
          <option value="adjustment">Set to value</option>
          <option value="add">Add quantity</option>
          <option value="remove">Remove quantity</option>
        </select>
      </div>
      <div class="form-group">
        <label>Quantity *</label>
        <input class="form-control" id="adjQty" type="number" min="0" step="0.01" placeholder="0">
      </div>
      <div class="form-group">
        <label>Note</label>
        <textarea class="form-control" id="adjNote" placeholder="Reason for adjustment…" rows="2"></textarea>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" id="adjBtn" onclick="doAdjust()">Apply Adjustment</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let stockPage = 1, movPage = 1, saving = false;
let currentView = 'stock';

const dbLoadStock = debounce(() => { stockPage=1; loadStock(); }, 350);
const dbLoadMov = debounce(() => { movPage=1; loadMovements(); }, 350);
const fmt = v => '৳' + parseFloat(v||0).toFixed(2);

const movTypes = { purchase:'badge-green', opening:'badge-blue', adjustment:'badge-amber', sale:'badge-red', return:'badge-purple', transfer:'badge-blue' };

async function init() {
  // Load categories for filter
  const cr = await api(`${API}?module=products&action=categories`);
  const cats = cr.data || [];
  document.getElementById('stockCat').innerHTML = '<option value="">All Categories</option>' + cats.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');

  // Load products for adj modal
  const pr = await api(`${API}?module=products&action=list&per_page=500&status=active`);
  const prods = pr.data?.products || [];
  document.getElementById('adjProd').innerHTML = '<option value="">— Select Product —</option>' + prods.map(p=>`<option value="${p.id}">${p.name} (${p.sku||'—'}) — Stock: ${p.stock||0}</option>`).join('');

  loadStock();
}

function switchView(v) {
  currentView = v;
  document.getElementById('viewStock').style.display = v==='stock' ? 'block' : 'none';
  document.getElementById('viewMovements').style.display = v==='movements' ? 'block' : 'none';
  document.getElementById('btnStock').className = v==='stock' ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('btnMov').className = v==='movements' ? 'btn btn-primary' : 'btn btn-ghost';
  if (v==='movements') loadMovements();
}

async function loadStock() {
  document.getElementById('stockBody').innerHTML = `<tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q = document.getElementById('stockSearch').value;
  const c = document.getElementById('stockCat').value;
  const a = document.getElementById('stockAlert').value;
  let url = `${API}?module=inventory&action=list&page=${stockPage}&per_page=25`;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (c) url += `&category=${c}`;
  if (a) url += `&low_stock=1`;
  const res = await api(url);
  const items = res.data?.items || [];
  const pg = res.data?.pagination || {};

  if (!items.length) {
    document.getElementById('stockBody').innerHTML = `<tr><td colspan="8" class="tbl-empty">No inventory items found</td></tr>`;
    document.getElementById('stockPag').style.display = 'none';
    return;
  }

  document.getElementById('stockBody').innerHTML = items.map(p => {
    const s = parseFloat(p.stock||0);
    const sClass = s<=0 ? 'badge-red' : s<=p.stock_alert_qty ? 'badge-amber' : 'badge-green';
    const sLabel = s<=0 ? 'Out of Stock' : s<=p.stock_alert_qty ? 'Low' : 'OK';
    return `<tr>
      <td style="font-weight:600;color:var(--text1)">${p.name}</td>
      <td style="color:var(--text3);font-size:12px">${p.sku||'—'}</td>
      <td>${p.category||'—'}</td>
      <td>${p.unit||'pcs'}</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <strong style="font-size:16px">${s}</strong>
          <span class="badge ${sClass}">${sLabel}</span>
        </div>
      </td>
      <td>${p.stock_alert_qty||5}</td>
      <td>${fmt(p.stock_value)}</td>
      <td>
        <button class="action-btn" onclick="quickAdjust(${p.id},'${p.name.replace(/'/g,"\\'")}')" title="Adjust Stock"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
      </td>
    </tr>`;
  }).join('');

  renderPagination(pg, 'loadStock');
  document.getElementById('stockPag').style.display = (pg.total_pages||1) > 1 ? 'flex' : 'none';
}

async function loadMovements() {
  document.getElementById('movBody').innerHTML = `<tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const url = `${API}?module=inventory&action=movements&page=${movPage}&per_page=25`;
  const res = await api(url);
  const rows = res.data?.movements || [];
  const pg = res.data?.pagination || {};

  if (!rows.length) {
    document.getElementById('movBody').innerHTML = `<tr><td colspan="8" class="tbl-empty">No movements recorded</td></tr>`;
    document.getElementById('movPag').style.display = 'none';
    return;
  }

  document.getElementById('movBody').innerHTML = rows.map(m => {
    const change = parseFloat(m.quantity_after||0) - parseFloat(m.quantity_before||0);
    const color = change > 0 ? '#059669' : 'var(--red)';
    const sign = change > 0 ? '+' : '';
    return `<tr>
      <td style="font-weight:500;color:var(--text1)">${m.product_name}</td>
      <td><span class="badge ${movTypes[m.type]||'badge-blue'}">${m.type}</span></td>
      <td>${m.quantity_before}</td>
      <td style="font-weight:700;color:${color}">${sign}${change.toFixed(2)}</td>
      <td style="font-weight:600">${m.quantity_after}</td>
      <td style="color:var(--text3);font-size:12px">${m.reference||'—'}</td>
      <td style="color:var(--text2)">${m.user_name||'System'}</td>
      <td style="color:var(--text3);font-size:12px">${m.created_at?.substring(0,16)||'—'}</td>
    </tr>`;
  }).join('');

  renderPagination(pg, 'loadMovements');
  document.getElementById('movPag').style.display = (pg.total_pages||1) > 1 ? 'flex' : 'none';
}

function openAdjust() {
  document.getElementById('adjProd').value = '';
  document.getElementById('adjType').value = 'adjustment';
  document.getElementById('adjQty').value = '';
  document.getElementById('adjNote').value = '';
  document.getElementById('adjModal').classList.add('open');
}

function quickAdjust(id, name) {
  document.getElementById('adjProd').value = id;
  document.getElementById('adjType').value = 'adjustment';
  document.getElementById('adjQty').value = '';
  document.getElementById('adjNote').value = '';
  document.getElementById('adjModal').classList.add('open');
}

async function doAdjust() {
  if (saving) return;
  const pid = document.getElementById('adjProd').value;
  const qty = document.getElementById('adjQty').value;
  if (!pid) { toast('Select a product', 'error'); return; }
  if (!qty || parseFloat(qty) < 0) { toast('Enter a valid quantity', 'error'); return; }
  saving = true;
  document.getElementById('adjBtn').disabled = true;
  const fd = new FormData();
  fd.append('product_id', pid);
  fd.append('quantity', qty);
  fd.append('type', document.getElementById('adjType').value);
  fd.append('note', document.getElementById('adjNote').value);
  const res = await apiFD(`${API}?module=inventory&action=adjust`, fd);
  saving = false;
  document.getElementById('adjBtn').disabled = false;
  if (res.success) {
    toast(`Stock updated: ${res.data.before} → ${res.data.after}`, 'success');
    closeModal();
    loadStock();
  } else {
    toast(res.message || 'Failed', 'error');
  }
}

function closeModal() {
  document.getElementById('adjModal').classList.remove('open');
}

document.getElementById('adjModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

init();
</script>
</body>
</html>

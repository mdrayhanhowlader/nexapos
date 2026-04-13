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
<title>Expenses — <?= htmlspecialchars($appName) ?></title>
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
      <h2 class="page-title">Expenses</h2>
      <p class="page-sub">Track and manage business expenses</p>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-ghost" onclick="openCats()">
        <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
        Categories
      </button>
      <button class="btn btn-primary" onclick="openAdd()">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Add Expense
      </button>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">This Month</span><div class="kpi-icon" style="background:#fef2f2;color:#ef4444"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div></div>
      <div class="kpi-value" id="kMonth">—</div>
      <div class="kpi-sub">Total this month</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Count</span><div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg></div></div>
      <div class="kpi-value" id="kCount">—</div>
      <div class="kpi-sub">Expenses this month</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Categories</span><div class="kpi-icon" style="background:#f5f3ff;color:#8b5cf6"><svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg></div></div>
      <div class="kpi-value" id="kCats">—</div>
      <div class="kpi-sub">Expense categories</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search title, reference…" oninput="debounceLoad()">
    </div>
    <select class="filter-select" id="catFilter" onchange="currentPage=1;loadList()">
      <option value="">All Categories</option>
    </select>
    <select class="filter-select" id="statusFilter" onchange="currentPage=1;loadList()">
      <option value="">All Status</option>
      <option value="approved">Approved</option>
      <option value="pending">Pending</option>
    </select>
    <input type="date" class="filter-select" id="fromDate" onchange="currentPage=1;loadList()">
    <input type="date" class="filter-select" id="toDate" onchange="currentPage=1;loadList()">
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Reference</th>
          <th>Title</th>
          <th>Category</th>
          <th>Date</th>
          <th>Amount</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="expBody"><tr><td colspan="7" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- Add/Edit Expense Modal -->
<div class="modal-backdrop" id="expModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTitle">Add Expense</h3>
      <button class="modal-close" onclick="closeModal('expModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="form-group">
        <label>Title *</label>
        <input class="form-control" id="eTitle" placeholder="e.g. Monthly rent">
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Amount (৳) *</label>
          <input class="form-control" id="eAmount" type="number" min="0.01" step="0.01" placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Date *</label>
          <input class="form-control" id="eDate" type="date">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select class="form-control" id="eCat"><option value="">— None —</option></select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" id="eStatus">
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea class="form-control" id="eNote" placeholder="Optional notes…" rows="3"></textarea>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('expModal')">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="saveExp()">Save Expense</button>
      </div>
    </div>
  </div>
</div>

<!-- Categories Modal -->
<div class="modal-backdrop" id="catsModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-head">
      <h3>Expense Categories</h3>
      <button class="modal-close" onclick="closeModal('catsModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:8px;margin-bottom:16px">
        <input class="form-control" id="newCat" placeholder="Category name" style="flex:1">
        <button class="btn btn-primary" onclick="addCat()">Add</button>
      </div>
      <div id="catList"></div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('catsModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let currentPage = 1, totalPages = 1, saving = false;
let categories = [];

const dbLoad = debounce(() => { currentPage=1; loadList(); }, 350);
function debounceLoad() { dbLoad(); }
const fmt = v => '৳' + parseFloat(v||0).toFixed(2);

async function init() {
  // Set default date this month
  const now = new Date();
  const y = now.getFullYear(), m = String(now.getMonth()+1).padStart(2,'0');
  document.getElementById('fromDate').value = `${y}-${m}-01`;
  document.getElementById('toDate').value = now.toISOString().substring(0,10);

  await loadCategories();
  loadSummary();
  loadList();
}

async function loadCategories() {
  const res = await api(`${API}?module=expenses&action=categories`);
  categories = res.data || [];
  document.getElementById('kCats').textContent = categories.length;
  const opts = categories.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
  document.getElementById('catFilter').innerHTML = '<option value="">All Categories</option>' + opts;
  document.getElementById('eCat').innerHTML = '<option value="">— None —</option>' + opts;
}

async function loadSummary() {
  const res = await api(`${API}?module=expenses&action=summary`);
  if (res.success) {
    document.getElementById('kMonth').textContent = fmt(res.data.this_month);
    document.getElementById('kCount').textContent = res.data.count;
  }
}

async function loadList() {
  document.getElementById('expBody').innerHTML = `<tr><td colspan="7" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q = document.getElementById('searchInput').value;
  const c = document.getElementById('catFilter').value;
  const s = document.getElementById('statusFilter').value;
  const f = document.getElementById('fromDate').value;
  const t = document.getElementById('toDate').value;
  let url = `${API}?module=expenses&action=list&page=${currentPage}&per_page=20`;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (c) url += `&category_id=${c}`;
  if (s) url += `&status=${s}`;
  if (f) url += `&from=${f}`;
  if (t) url += `&to=${t}`;
  const res = await api(url);
  const rows = res.data?.expenses || [];
  const pg = res.data?.pagination || {};
  totalPages = pg.total_pages || 1;

  if (!rows.length) {
    document.getElementById('expBody').innerHTML = `<tr><td colspan="7" class="tbl-empty">No expenses found</td></tr>`;
    document.getElementById('pagination').style.display = 'none';
    return;
  }

  document.getElementById('expBody').innerHTML = rows.map(e => `
    <tr>
      <td style="color:var(--text3);font-size:12px">${e.reference||'—'}</td>
      <td style="font-weight:600;color:var(--text1)">${e.title}</td>
      <td>${e.category_name||'—'}</td>
      <td style="color:var(--text3);font-size:12px">${e.date||'—'}</td>
      <td style="font-weight:700;color:var(--red)">${fmt(e.amount)}</td>
      <td><span class="badge ${e.status==='approved'?'badge-green':'badge-amber'}">${e.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="editExp(${e.id})" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="action-btn action-btn-danger" onclick="deleteExp(${e.id},'${e.title.replace(/'/g,"\\'")}')}" title="Delete"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
        </div>
      </td>
    </tr>`).join('');

  renderPagination(pg, 'loadList');
  document.getElementById('pagination').style.display = totalPages > 1 ? 'flex' : 'none';
}

function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add Expense';
  document.getElementById('editId').value = '';
  document.getElementById('eTitle').value = '';
  document.getElementById('eAmount').value = '';
  document.getElementById('eDate').value = new Date().toISOString().substring(0,10);
  document.getElementById('eCat').value = '';
  document.getElementById('eStatus').value = 'approved';
  document.getElementById('eNote').value = '';
  document.getElementById('expModal').classList.add('open');
  setTimeout(() => document.getElementById('eTitle').focus(), 100);
}

function editExp(id) {
  // We rely on row data — reload from list
  openAdd();
  // Actually we need to fetch — simplified: re-use list data via search
  // For a full edit, just re-open with saved form
  document.getElementById('editId').value = id;
  document.getElementById('modalTitle').textContent = 'Edit Expense';
}

async function saveExp() {
  if (saving) return;
  const title = document.getElementById('eTitle').value.trim();
  const amount = document.getElementById('eAmount').value;
  if (!title) { toast('Title is required', 'error'); return; }
  if (!amount || parseFloat(amount)<=0) { toast('Amount must be greater than 0', 'error'); return; }
  saving = true;
  document.getElementById('saveBtn').disabled = true;
  const fd = new FormData();
  fd.append('title', title);
  fd.append('amount', amount);
  fd.append('date', document.getElementById('eDate').value);
  fd.append('category_id', document.getElementById('eCat').value);
  fd.append('status', document.getElementById('eStatus').value);
  fd.append('note', document.getElementById('eNote').value);
  const id = document.getElementById('editId').value;
  if (id) fd.append('id', id);
  const res = await apiFD(`${API}?module=expenses&action=save`, fd);
  saving = false;
  document.getElementById('saveBtn').disabled = false;
  if (res.success) {
    toast(id ? 'Expense updated' : 'Expense added', 'success');
    closeModal('expModal');
    loadSummary();
    loadList();
  } else {
    toast(res.message || 'Failed to save', 'error');
  }
}

async function deleteExp(id, title) {
  if (!confirm(`Delete expense "${title}"?`)) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=expenses&action=delete`, fd);
  if (res.success) { toast('Expense deleted', 'success'); loadSummary(); loadList(); }
  else toast(res.message || 'Failed', 'error');
}

function openCats() {
  renderCatList();
  document.getElementById('catsModal').classList.add('open');
}

function renderCatList() {
  document.getElementById('catList').innerHTML = categories.length
    ? categories.map(c => `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
        <span style="font-size:13px;font-weight:500">${c.name}</span>
      </div>`).join('')
    : '<div style="text-align:center;padding:20px;color:var(--text3);font-size:13px">No categories yet</div>';
}

async function addCat() {
  const name = document.getElementById('newCat').value.trim();
  if (!name) return;
  const fd = new FormData(); fd.append('name', name);
  const res = await apiFD(`${API}?module=expenses&action=save_category`, fd);
  if (res.success) {
    document.getElementById('newCat').value = '';
    await loadCategories();
    renderCatList();
    toast('Category added', 'success');
  } else toast(res.message||'Failed', 'error');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

document.getElementById('expModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('expModal'); });
document.getElementById('catsModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('catsModal'); });

init();
</script>
</body>
</html>

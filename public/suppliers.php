<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
$user    = Auth::user();
$appName = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? 'NexaPOS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Suppliers — <?= htmlspecialchars($appName) ?></title>
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
      <h2 class="page-title">Suppliers</h2>
      <p class="page-sub">Manage your product suppliers</p>
    </div>
    <button class="btn btn-primary" onclick="openAdd()">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      New Supplier
    </button>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Suppliers</span>
        <div class="kpi-icon" style="background:#eff6ff;color:#2563eb">
          <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
      </div>
      <div class="kpi-value" id="kTotal">—</div>
      <div class="kpi-sub">All suppliers</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Active</span>
        <div class="kpi-icon" style="background:#ecfdf5;color:#059669">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        </div>
      </div>
      <div class="kpi-value" id="kActive">—</div>
      <div class="kpi-sub">Currently active</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Inactive</span>
        <div class="kpi-icon" style="background:#f9fafb;color:#6b7280">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        </div>
      </div>
      <div class="kpi-value" id="kInactive">—</div>
      <div class="kpi-sub">Deactivated</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Due</span>
        <div class="kpi-icon" style="background:#fef2f2;color:#ef4444">
          <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        </div>
      </div>
      <div class="kpi-value" id="kDue">—</div>
      <div class="kpi-sub">Across all purchases</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search name, company, phone…" oninput="debounceLoad()">
    </div>
    <select class="filter-select" id="statusFilter" onchange="currentPage=1;loadList()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Name</th>
          <th>Company</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Purchases</th>
          <th>Due</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="suppBody"><tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="suppModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-head">
      <h3 id="modalTitle">New Supplier</h3>
      <button class="modal-close" onclick="closeModal('suppModal')">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="suppId">
      <div class="form-grid">
        <div class="form-group">
          <label>Name <span style="color:var(--red)">*</span></label>
          <input class="form-control" id="suppName" placeholder="Supplier / person name">
        </div>
        <div class="form-group">
          <label>Company</label>
          <input class="form-control" id="suppCompany" placeholder="Company name (optional)">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input class="form-control" id="suppPhone" placeholder="+880…">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input class="form-control" id="suppEmail" type="email" placeholder="email@example.com">
        </div>
        <div class="form-group">
          <label>Tax / VAT Number</label>
          <input class="form-control" id="suppTax" placeholder="Optional">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" id="suppStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea class="form-control" id="suppAddress" rows="2" placeholder="Full address…"></textarea>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('suppModal')">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="save()">Create Supplier</button>
      </div>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal-backdrop" id="viewModal">
  <div class="modal" style="max-width:500px">
    <div class="modal-head">
      <h3 id="viewTitle">Supplier Details</h3>
      <button class="modal-close" onclick="closeModal('viewModal')">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <div class="modal-body" id="viewBody">Loading…</div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let currentPage = 1, saving = false;
const fmt = v => '৳' + parseFloat(v||0).toFixed(2);
const dbLoad = debounce(() => { currentPage=1; loadList(); }, 350);
function debounceLoad() { dbLoad(); }

function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

async function loadSummary() {
  const res = await api(`${API}?module=suppliers&action=summary`);
  if (!res.success) return;
  document.getElementById('kTotal').textContent   = res.data.total;
  document.getElementById('kActive').textContent  = res.data.active;
  document.getElementById('kInactive').textContent= res.data.inactive;
  document.getElementById('kDue').textContent     = fmt(res.data.total_due);
}

async function loadList() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const res = await api(`${API}?module=suppliers&action=list&page=${currentPage}&search=${encodeURIComponent(search)}&status=${status}`);
  const tbody = document.getElementById('suppBody');
  if (!res.success) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--red)">Error loading data</td></tr>`;
    return;
  }
  const rows = res.data.suppliers || [];
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="tbl-empty">No suppliers found</td></tr>`;
    renderPagination(null, 'loadList');
    return;
  }
  tbody.innerHTML = rows.map(s => `
    <tr>
      <td><strong>${esc(s.name)}</strong></td>
      <td>${esc(s.company || '—')}</td>
      <td>${esc(s.phone || '—')}</td>
      <td>${esc(s.email || '—')}</td>
      <td><span class="badge badge-blue">${s.purchase_count || 0}</span></td>
      <td>${parseFloat(s.total_due||0) > 0 ? `<span style="color:var(--red);font-weight:600">${fmt(s.total_due)}</span>` : '<span style="color:var(--text3)">—</span>'}</td>
      <td><span class="badge ${s.status==='active'?'badge-green':'badge-red'}">${s.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" title="View" onclick="viewSupplier(${s.id})">
            <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
          </button>
          <button class="action-btn" title="Edit" onclick="editSupplier(${s.id})">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          </button>
          <button class="action-btn action-btn-danger" title="Delete" onclick="deleteSupplier(${s.id}, '${esc(s.name)}')">
            <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          </button>
        </div>
      </td>
    </tr>`).join('');
  renderPagination(res.data.pagination, 'loadList');
}

function openAdd() {
  document.getElementById('suppId').value    = '';
  document.getElementById('suppName').value  = '';
  document.getElementById('suppCompany').value = '';
  document.getElementById('suppPhone').value = '';
  document.getElementById('suppEmail').value = '';
  document.getElementById('suppTax').value   = '';
  document.getElementById('suppAddress').value = '';
  document.getElementById('suppStatus').value = 'active';
  document.getElementById('modalTitle').textContent = 'New Supplier';
  document.getElementById('saveBtn').textContent    = 'Create Supplier';
  openModal('suppModal');
}

async function editSupplier(id) {
  const res = await api(`${API}?module=suppliers&action=get&id=${id}`);
  if (!res.success) { toast('Could not load supplier', 'error'); return; }
  const s = res.data;
  document.getElementById('suppId').value      = s.id;
  document.getElementById('suppName').value    = s.name;
  document.getElementById('suppCompany').value = s.company || '';
  document.getElementById('suppPhone').value   = s.phone   || '';
  document.getElementById('suppEmail').value   = s.email   || '';
  document.getElementById('suppTax').value     = s.tax_number || '';
  document.getElementById('suppAddress').value = s.address || '';
  document.getElementById('suppStatus').value  = s.status;
  document.getElementById('modalTitle').textContent = 'Edit Supplier';
  document.getElementById('saveBtn').textContent    = 'Save Changes';
  openModal('suppModal');
}

async function viewSupplier(id) {
  document.getElementById('viewBody').innerHTML = '<div style="padding:20px;text-align:center"><div class="spin"></div></div>';
  openModal('viewModal');
  const res = await api(`${API}?module=suppliers&action=get&id=${id}`);
  if (!res.success) { document.getElementById('viewBody').innerHTML = '<p style="color:var(--red);padding:20px">Could not load supplier.</p>'; return; }
  const s = res.data;
  const purchases = (s.recent_purchases || []).map(p => `
    <tr>
      <td>${esc(p.reference)}</td>
      <td>${fmt(p.total)}</td>
      <td>${fmt(p.paid)}</td>
      <td>${parseFloat(p.due)>0?`<span style="color:var(--red)">${fmt(p.due)}</span>`:'<span style="color:var(--green)">Paid</span>'}</td>
      <td><span class="badge badge-${p.status==='received'?'green':p.status==='pending'?'amber':'red'}">${p.status}</span></td>
    </tr>`).join('') || '<tr><td colspan="5" class="tbl-empty">No purchases yet</td></tr>';
  document.getElementById('viewTitle').textContent = s.name;
  document.getElementById('viewBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;margin-bottom:16px;font-size:13px">
      ${s.company  ? `<div><span style="color:var(--text2)">Company</span><br><strong>${esc(s.company)}</strong></div>` : ''}
      ${s.phone    ? `<div><span style="color:var(--text2)">Phone</span><br><strong>${esc(s.phone)}</strong></div>` : ''}
      ${s.email    ? `<div><span style="color:var(--text2)">Email</span><br><strong>${esc(s.email)}</strong></div>` : ''}
      ${s.tax_number ? `<div><span style="color:var(--text2)">Tax No.</span><br><strong>${esc(s.tax_number)}</strong></div>` : ''}
      ${s.address  ? `<div style="grid-column:1/-1"><span style="color:var(--text2)">Address</span><br><strong>${esc(s.address)}</strong></div>` : ''}
    </div>
    <div style="font-size:13px;font-weight:600;margin-bottom:8px">Recent Purchases</div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="background:var(--bg)">
          <th style="padding:6px 8px;text-align:left">Reference</th>
          <th style="padding:6px 8px;text-align:left">Total</th>
          <th style="padding:6px 8px;text-align:left">Paid</th>
          <th style="padding:6px 8px;text-align:left">Due</th>
          <th style="padding:6px 8px;text-align:left">Status</th>
        </tr></thead>
        <tbody>${purchases}</tbody>
      </table>
    </div>
    <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
      <button class="btn btn-ghost" onclick="closeModal('viewModal')">Close</button>
      <button class="btn btn-primary" onclick="closeModal('viewModal');editSupplier(${s.id})">Edit</button>
    </div>`;
}

async function save() {
  if (saving) return;
  const name = document.getElementById('suppName').value.trim();
  if (!name) { toast('Supplier name is required', 'warning'); return; }
  saving = true;
  const btn = document.getElementById('saveBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const fd = new FormData();
  fd.append('id',         document.getElementById('suppId').value);
  fd.append('name',       name);
  fd.append('company',    document.getElementById('suppCompany').value);
  fd.append('phone',      document.getElementById('suppPhone').value);
  fd.append('email',      document.getElementById('suppEmail').value);
  fd.append('tax_number', document.getElementById('suppTax').value);
  fd.append('address',    document.getElementById('suppAddress').value);
  fd.append('status',     document.getElementById('suppStatus').value);
  const res = await apiFD(`${API}?module=suppliers&action=save`, fd);
  saving = false; btn.disabled = false;
  if (res.success) {
    toast(res.message || 'Saved', 'success');
    closeModal('suppModal');
    loadList(); loadSummary();
  } else {
    toast(res.message || 'Save failed', 'error');
    btn.textContent = document.getElementById('suppId').value ? 'Save Changes' : 'Create Supplier';
  }
}

async function deleteSupplier(id, name) {
  if (!confirm(`Delete supplier "${name}"?\n\nIf they have purchase history they will be deactivated instead.`)) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=suppliers&action=delete`, fd);
  if (res.success) { toast(res.message || 'Done', 'success'); loadList(); loadSummary(); }
  else toast(res.message || 'Delete failed', 'error');
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
loadSummary();
loadList();
</script>
</body>
</html>

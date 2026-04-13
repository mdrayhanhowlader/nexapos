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
<title>Employees — <?= htmlspecialchars($appName) ?></title>
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
      <h2 class="page-title">Employees</h2>
      <p class="page-sub">Manage staff accounts and roles</p>
    </div>
    <button class="btn btn-primary" onclick="openAdd()">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      Add Employee
    </button>
  </div>

  <!-- Filters -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search name, email, phone…" oninput="debounceLoad()">
    </div>
    <select class="filter-select" id="roleFilter" onchange="currentPage=1;loadList()">
      <option value="">All Roles</option>
    </select>
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Employee</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Last Login</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="empBody"><tr><td colspan="7" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- Add/Edit Employee Modal -->
<div class="modal-backdrop" id="empModal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 id="modalTitle">Add Employee</h3>
      <button class="modal-close" onclick="closeModal('empModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="form-grid">
        <div class="form-group">
          <label>Full Name *</label>
          <input class="form-control" id="eName" placeholder="Employee name">
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input class="form-control" id="eEmail" type="email" placeholder="email@example.com">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input class="form-control" id="ePhone" placeholder="+880…">
        </div>
        <div class="form-group">
          <label>Role *</label>
          <select class="form-control" id="eRole"></select>
        </div>
        <div class="form-group">
          <label>Password <span id="pwHint" style="color:var(--text3);font-weight:400;font-size:11px">(required for new)</span></label>
          <input class="form-control" id="ePass" type="password" placeholder="Min 6 characters">
        </div>
        <div class="form-group">
          <label>PIN <span style="color:var(--text3);font-weight:400;font-size:11px">(4-6 digits)</span></label>
          <input class="form-control" id="ePin" type="text" maxlength="6" placeholder="e.g. 1234">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-control" id="eStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('empModal')">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="saveEmp()">Save Employee</button>
      </div>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-backdrop" id="pwModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-head">
      <h3>Reset Password</h3>
      <button class="modal-close" onclick="closeModal('pwModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="pwEmpId">
      <div class="form-group">
        <label>New Password *</label>
        <input class="form-control" id="pwNew" type="password" placeholder="Min 6 characters">
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal('pwModal')">Cancel</button>
        <button class="btn btn-primary" onclick="doResetPw()">Reset Password</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let currentPage = 1, totalPages = 1, saving = false;
let roles = [];

const dbLoad = debounce(() => { currentPage=1; loadList(); }, 350);
function debounceLoad() { dbLoad(); }
const roleColors = { admin:'badge-red', manager:'badge-purple', cashier:'badge-blue', staff:'badge-green' };

async function init() {
  const res = await api(`${API}?module=employees&action=roles`);
  roles = res.data || [];
  const roleOpts = roles.map(r=>`<option value="${r.id}">${r.name}</option>`).join('');
  document.getElementById('roleFilter').innerHTML = '<option value="">All Roles</option>' + roles.map(r=>`<option value="${r.slug}">${r.name}</option>`).join('');
  document.getElementById('eRole').innerHTML = '<option value="">— Select Role —</option>' + roleOpts;
  loadList();
}

async function loadList() {
  document.getElementById('empBody').innerHTML = `<tr><td colspan="7" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q = document.getElementById('searchInput').value;
  const r = document.getElementById('roleFilter').value;
  let url = `${API}?module=employees&action=list&page=${currentPage}&per_page=20`;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (r) url += `&role=${r}`;
  const res = await api(url);
  const emps = res.data?.employees || [];
  const pg = res.data?.pagination || {};
  totalPages = pg.total_pages || 1;

  if (!emps.length) {
    document.getElementById('empBody').innerHTML = `<tr><td colspan="7" class="tbl-empty">No employees found</td></tr>`;
    document.getElementById('pagination').style.display = 'none';
    return;
  }

  document.getElementById('empBody').innerHTML = emps.map(e => {
    const initials = e.name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
    const roleColor = roleColors[e.role_slug] || 'badge-blue';
    const lastLogin = e.last_login ? new Date(e.last_login).toLocaleDateString() : 'Never';
    return `<tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:50%;background:var(--accent);display:grid;place-items:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0">${initials}</div>
          <div style="font-weight:600;color:var(--text1)">${e.name}</div>
        </div>
      </td>
      <td style="color:var(--text2)">${e.email}</td>
      <td>${e.phone||'—'}</td>
      <td><span class="badge ${roleColor}">${e.role_name}</span></td>
      <td style="color:var(--text3);font-size:12px">${lastLogin}</td>
      <td><span class="badge ${e.status==='active'?'badge-green':'badge-red'}">${e.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="editEmp(${e.id})" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="action-btn" onclick="openResetPw(${e.id})" title="Reset Password" style="color:#8b5cf6"><svg viewBox="0 0 24 24"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg></button>
          <button class="action-btn action-btn-danger" onclick="deleteEmp(${e.id},'${e.name.replace(/'/g,"\\'")}')}" title="Suspend"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></button>
        </div>
      </td>
    </tr>`;
  }).join('');

  renderPagination(pg, 'loadList');
  document.getElementById('pagination').style.display = totalPages > 1 ? 'flex' : 'none';
}

function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add Employee';
  document.getElementById('editId').value = '';
  document.getElementById('eName').value = '';
  document.getElementById('eEmail').value = '';
  document.getElementById('ePhone').value = '';
  document.getElementById('eRole').value = '';
  document.getElementById('ePass').value = '';
  document.getElementById('ePin').value = '';
  document.getElementById('eStatus').value = 'active';
  document.getElementById('pwHint').textContent = '(required for new)';
  document.getElementById('empModal').classList.add('open');
  setTimeout(() => document.getElementById('eName').focus(), 100);
}

async function editEmp(id) {
  const res = await api(`${API}?module=employees&action=get&id=${id}`);
  if (!res.success) { toast('Failed to load employee', 'error'); return; }
  const e = res.data;
  document.getElementById('modalTitle').textContent = 'Edit Employee';
  document.getElementById('editId').value = e.id;
  document.getElementById('eName').value = e.name;
  document.getElementById('eEmail').value = e.email;
  document.getElementById('ePhone').value = e.phone||'';
  document.getElementById('eRole').value = e.role_id;
  document.getElementById('ePass').value = '';
  document.getElementById('ePin').value = '';
  document.getElementById('eStatus').value = e.status||'active';
  document.getElementById('pwHint').textContent = '(leave blank to keep current)';
  document.getElementById('empModal').classList.add('open');
}

async function saveEmp() {
  if (saving) return;
  const name = document.getElementById('eName').value.trim();
  const email = document.getElementById('eEmail').value.trim();
  const role = document.getElementById('eRole').value;
  if (!name || !email || !role) { toast('Name, email and role are required', 'error'); return; }
  saving = true;
  document.getElementById('saveBtn').disabled = true;
  const fd = new FormData();
  fd.append('name', name);
  fd.append('email', email);
  fd.append('phone', document.getElementById('ePhone').value);
  fd.append('role_id', role);
  fd.append('status', document.getElementById('eStatus').value);
  const pw = document.getElementById('ePass').value;
  if (pw) fd.append('password', pw);
  const pin = document.getElementById('ePin').value;
  if (pin) fd.append('pin', pin);
  const id = document.getElementById('editId').value;
  if (id) fd.append('id', id);
  const res = await apiFD(`${API}?module=employees&action=save`, fd);
  saving = false;
  document.getElementById('saveBtn').disabled = false;
  if (res.success) {
    toast(id ? 'Employee updated' : 'Employee added', 'success');
    closeModal('empModal');
    loadList();
  } else {
    toast(res.message || 'Failed to save', 'error');
  }
}

function openResetPw(id) {
  document.getElementById('pwEmpId').value = id;
  document.getElementById('pwNew').value = '';
  document.getElementById('pwModal').classList.add('open');
}

async function doResetPw() {
  const id = document.getElementById('pwEmpId').value;
  const pw = document.getElementById('pwNew').value;
  if (!pw || pw.length < 6) { toast('Password must be at least 6 characters', 'error'); return; }
  const fd = new FormData();
  fd.append('id', id);
  fd.append('password', pw);
  const res = await apiFD(`${API}?module=employees&action=reset_password`, fd);
  if (res.success) { toast('Password reset successfully', 'success'); closeModal('pwModal'); }
  else toast(res.message || 'Failed', 'error');
}

async function deleteEmp(id, name) {
  if (!confirm(`Suspend employee "${name}"? They will no longer be able to log in.`)) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=employees&action=delete`, fd);
  if (res.success) { toast('Employee suspended', 'success'); loadList(); }
  else toast(res.message || 'Failed', 'error');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

document.getElementById('empModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('empModal'); });
document.getElementById('pwModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('pwModal'); });

init();
</script>
</body>
</html>

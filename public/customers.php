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
<title>Customers — <?= htmlspecialchars($appName) ?></title>
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
      <h2 class="page-title">Customers</h2>
      <p class="page-sub">Manage customer accounts and walk-in guests</p>
    </div>
    <button class="btn btn-primary" onclick="openAdd()" id="addCustBtn">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      Add Customer
    </button>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Customers</span><div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div></div>
      <div class="kpi-value" id="kTotal">—</div>
      <div class="kpi-sub">Named customers</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">VIP</span><div class="kpi-icon" style="background:#f5f3ff;color:#8b5cf6"><svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></div></div>
      <div class="kpi-value" id="kVip">—</div>
      <div class="kpi-sub">VIP customers</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Guests Today</span><div class="kpi-icon" style="background:#fff7ed;color:#f97316"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div></div>
      <div class="kpi-value" id="kGuests">—</div>
      <div class="kpi-sub">Walk-in orders today</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">New Today</span><div class="kpi-icon" style="background:#fffbeb;color:#f59e0b"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg></div></div>
      <div class="kpi-value" id="kNew">—</div>
      <div class="kpi-sub">Registered today</div>
    </div>
  </div>

  <!-- View Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px">
    <button class="btn btn-primary" id="tabCustomers" onclick="switchTab('customers')">
      <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      Named Customers
    </button>
    <button class="btn btn-ghost" id="tabGuests" onclick="switchTab('guests')">
      <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
      Walk-in Guests
    </button>
  </div>

  <!-- ═══ NAMED CUSTOMERS VIEW ═══ -->
  <div id="viewCustomers">
    <div class="filter-row">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="searchInput" placeholder="Name, phone, email, code…" oninput="debounceLoad()">
      </div>
      <select class="filter-select" id="groupFilter" onchange="custPage=1;loadCustomers()">
        <option value="">All Groups</option>
        <option value="regular">Regular</option>
        <option value="vip">VIP</option>
        <option value="wholesale">Wholesale</option>
      </select>
    </div>

    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Group</th>
            <th>Loyalty</th>
            <th>Balance</th>
            <th>Joined</th>
            <th></th>
          </tr></thead>
          <tbody id="custBody"><tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="custPag" style="display:none"></div>
    </div>
  </div>

  <!-- ═══ GUESTS VIEW ═══ -->
  <div id="viewGuests" style="display:none">
    <div class="filter-row">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="guestSearch" placeholder="Search invoice no, cashier…" oninput="debounceGuests()">
      </div>
      <input type="date" class="filter-select" id="guestFrom" onchange="guestPage=1;loadGuests()" placeholder="From date" style="max-width:150px">
      <input type="date" class="filter-select" id="guestTo"   onchange="guestPage=1;loadGuests()" placeholder="To date"   style="max-width:150px">
      <button class="btn btn-ghost" onclick="clearGuestFilters()" style="white-space:nowrap">Clear</button>
    </div>
    <p style="font-size:12px;color:var(--text3);margin-bottom:12px">Walk-in sales with no customer account linked. Total: <strong id="guestCount">—</strong> orders.</p>

    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>Invoice</th>
            <th>Items</th>
            <th>Items Preview</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Change</th>
            <th>Cashier</th>
            <th>Date & Time</th>
          </tr></thead>
          <tbody id="guestBody"><tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="guestPag" style="display:none"></div>
    </div>
  </div>

</div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal-backdrop" id="custModal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 id="modalTitle">Add Customer</h3>
      <button class="modal-close" onclick="closeModal()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="form-grid">
        <div class="form-group">
          <label>Full Name *</label>
          <input class="form-control" id="fName" placeholder="Customer name">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input class="form-control" id="fPhone" placeholder="+880…">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input class="form-control" id="fEmail" type="email" placeholder="email@example.com">
        </div>
        <div class="form-group">
          <label>Date of Birth</label>
          <input class="form-control" id="fDob" type="date">
        </div>
        <div class="form-group">
          <label>Group</label>
          <select class="form-control" id="fGroup">
            <option value="regular">Regular</option>
            <option value="vip">VIP</option>
            <option value="wholesale">Wholesale</option>
          </select>
        </div>
        <div class="form-group">
          <label>Discount Rate (%)</label>
          <input class="form-control" id="fDiscount" type="number" min="0" max="100" step="0.5" placeholder="0">
        </div>
        <div class="form-group">
          <label>Credit Limit (৳)</label>
          <input class="form-control" id="fCredit" type="number" min="0" step="0.01" placeholder="0">
        </div>
        <div class="form-group span-2">
          <label>Address</label>
          <textarea class="form-control" id="fAddress" placeholder="Full address" rows="2"></textarea>
        </div>
        <div class="form-group span-2">
          <label>Notes</label>
          <textarea class="form-control" id="fNotes" placeholder="Internal notes…" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" id="saveBtn" onclick="saveCust()">Save Customer</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let custPage = 1, guestPage = 1, saving = false, currentTab = 'customers';
const groupLabels = { regular:'Regular', vip:'VIP', wholesale:'Wholesale' };
const groupColors  = { regular:'badge-blue', vip:'badge-purple', wholesale:'badge-green' };

const dbLoad    = debounce(() => { custPage=1;  loadCustomers(); }, 350);
const dbGuests  = debounce(() => { guestPage=1; loadGuests(); }, 350);
function debounceLoad()   { dbLoad(); }
function debounceGuests() { dbGuests(); }

// ── Tab switch ─────────────────────────────────
function switchTab(tab) {
  currentTab = tab;
  document.getElementById('viewCustomers').style.display = tab === 'customers' ? 'block' : 'none';
  document.getElementById('viewGuests').style.display    = tab === 'guests'    ? 'block' : 'none';
  document.getElementById('tabCustomers').className = tab === 'customers' ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('tabGuests').className    = tab === 'guests'    ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('addCustBtn').style.display = tab === 'customers' ? '' : 'none';
  if (tab === 'guests') loadGuests();
}

// ── KPIs ───────────────────────────────────────
async function loadStats() {
  const [sr, gr] = await Promise.all([
    api(`${API}?module=customers&action=stats`),
    api(`${API}?module=customers&action=guests&per_page=1&date_from=${new Date().toISOString().slice(0,10)}&date_to=${new Date().toISOString().slice(0,10)}`),
  ]);
  if (sr.success) {
    document.getElementById('kTotal').textContent = sr.data.total;
    document.getElementById('kVip').textContent   = sr.data.vip;
    document.getElementById('kNew').textContent   = sr.data.new_today;
  }
  document.getElementById('kGuests').textContent = gr.data?.total_guests ?? '—';
}

// ── Named customers list ───────────────────────
async function loadCustomers() {
  document.getElementById('custBody').innerHTML = `<tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q = document.getElementById('searchInput').value;
  const g = document.getElementById('groupFilter').value;
  let url = `${API}?module=customers&action=list&page=${custPage}&per_page=20`;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (g) url += `&group=${g}`;
  const res = await api(url);
  const custs = res.data?.customers || [];
  const pg    = res.data?.pagination || {};

  if (!custs.length) {
    document.getElementById('custBody').innerHTML = `<tr><td colspan="8" class="tbl-empty">No customers found</td></tr>`;
    document.getElementById('custPag').style.display = 'none';
    return;
  }

  document.getElementById('custBody').innerHTML = custs.map(c => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:50%;background:var(--accent);display:grid;place-items:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">${(c.name||'?')[0].toUpperCase()}</div>
          <div>
            <div style="font-weight:600;color:var(--text1)">${c.name}</div>
            <div style="font-size:11px;color:var(--text3)">${c.code||''}</div>
          </div>
        </div>
      </td>
      <td>${c.phone||'—'}</td>
      <td style="color:var(--text3);font-size:12px">${c.email||'—'}</td>
      <td><span class="badge ${groupColors[c.group]||'badge-blue'}">${groupLabels[c.group]||c.group}</span></td>
      <td style="font-weight:600;color:#8b5cf6">${c.loyalty_points||0} pts</td>
      <td style="color:${parseFloat(c.outstanding_balance||0)>0?'var(--red)':'var(--text2)'}">৳${parseFloat(c.outstanding_balance||0).toFixed(2)}</td>
      <td style="color:var(--text3);font-size:12px">${c.created_at ? c.created_at.substring(0,10) : '—'}</td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="editCust(${c.id})" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="action-btn action-btn-danger" onclick="deleteCust(${c.id},'${c.name.replace(/'/g,"\\'")}')}" title="Delete"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
        </div>
      </td>
    </tr>`).join('');

  renderPagination(pg, 'loadCustomers', 'custPag', 'custPage');
}

// ── Guests list ────────────────────────────────
async function loadGuests() {
  document.getElementById('guestBody').innerHTML = `<tr><td colspan="8" class="tbl-loading"><div class="spin"></div> Loading...</td></tr>`;
  const q  = document.getElementById('guestSearch').value;
  const df = document.getElementById('guestFrom').value;
  const dt = document.getElementById('guestTo').value;
  let url = `${API}?module=customers&action=guests&page=${guestPage}&per_page=20`;
  if (q)  url += `&search=${encodeURIComponent(q)}`;
  if (df) url += `&date_from=${df}`;
  if (dt) url += `&date_to=${dt}`;
  const res = await api(url);
  const orders = res.data?.orders || [];
  const pg     = res.data?.pagination || {};
  const total  = res.data?.total_guests || 0;

  document.getElementById('guestCount').textContent = total;

  if (!orders.length) {
    document.getElementById('guestBody').innerHTML = `<tr><td colspan="8" class="tbl-empty">No guest orders found</td></tr>`;
    document.getElementById('guestPag').style.display = 'none';
    return;
  }

  document.getElementById('guestBody').innerHTML = orders.map(o => {
    const dt2 = o.created_at ? new Date(o.created_at) : null;
    const dateStr  = dt2 ? dt2.toLocaleDateString() : '—';
    const timeStr  = dt2 ? dt2.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '';
    const preview  = (o.items_preview || '').length > 50 ? o.items_preview.substring(0,50)+'…' : (o.items_preview||'—');
    return `<tr>
      <td>
        <div style="font-weight:600;color:var(--accent)">${o.invoice_no}</div>
        <div style="font-size:11px;color:var(--text3)">Order #${o.id}</div>
      </td>
      <td style="font-weight:600;text-align:center">${o.item_count}</td>
      <td style="font-size:12px;color:var(--text2);max-width:200px">${preview}</td>
      <td style="font-weight:700;color:var(--text1)">৳${parseFloat(o.total).toFixed(2)}</td>
      <td style="color:var(--green-color,#16a34a)">৳${parseFloat(o.paid).toFixed(2)}</td>
      <td style="color:var(--text3)">৳${parseFloat(o.change_due||0).toFixed(2)}</td>
      <td style="color:var(--text2)">${o.cashier_name||'—'}</td>
      <td style="color:var(--text3);font-size:12px">${dateStr}<br><span style="font-size:11px">${timeStr}</span></td>
    </tr>`;
  }).join('');

  renderPagination(pg, 'loadGuests', 'guestPag', 'guestPage');
}

function clearGuestFilters() {
  document.getElementById('guestSearch').value = '';
  document.getElementById('guestFrom').value   = '';
  document.getElementById('guestTo').value     = '';
  guestPage = 1;
  loadGuests();
}

// ── CRUD ───────────────────────────────────────
function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add Customer';
  document.getElementById('editId').value = '';
  ['fName','fPhone','fEmail','fAddress','fNotes'].forEach(id => document.getElementById(id).value='');
  document.getElementById('fGroup').value    = 'regular';
  document.getElementById('fDiscount').value = '0';
  document.getElementById('fCredit').value   = '0';
  document.getElementById('fDob').value      = '';
  document.getElementById('custModal').classList.add('open');
  setTimeout(() => document.getElementById('fName').focus(), 100);
}

async function editCust(id) {
  const res = await api(`${API}?module=customers&action=get&id=${id}`);
  if (!res.success) { toast('Failed to load customer', 'error'); return; }
  const c = res.data;
  document.getElementById('modalTitle').textContent = 'Edit Customer';
  document.getElementById('editId').value    = c.id;
  document.getElementById('fName').value     = c.name||'';
  document.getElementById('fPhone').value    = c.phone||'';
  document.getElementById('fEmail').value    = c.email||'';
  document.getElementById('fDob').value      = c.date_of_birth||'';
  document.getElementById('fGroup').value    = c.group||'regular';
  document.getElementById('fDiscount').value = c.discount_rate||0;
  document.getElementById('fCredit').value   = c.credit_limit||0;
  document.getElementById('fAddress').value  = c.address||'';
  document.getElementById('fNotes').value    = c.notes||'';
  document.getElementById('custModal').classList.add('open');
}

function closeModal() {
  document.getElementById('custModal').classList.remove('open');
}

async function saveCust() {
  if (saving) return;
  const name = document.getElementById('fName').value.trim();
  if (!name) { toast('Customer name is required', 'error'); return; }
  saving = true;
  document.getElementById('saveBtn').disabled = true;
  const fd = new FormData();
  fd.append('name',         name);
  fd.append('phone',        document.getElementById('fPhone').value);
  fd.append('email',        document.getElementById('fEmail').value);
  fd.append('date_of_birth',document.getElementById('fDob').value);
  fd.append('group',        document.getElementById('fGroup').value);
  fd.append('discount_rate',document.getElementById('fDiscount').value);
  fd.append('credit_limit', document.getElementById('fCredit').value);
  fd.append('address',      document.getElementById('fAddress').value);
  fd.append('notes',        document.getElementById('fNotes').value);
  const id = document.getElementById('editId').value;
  if (id) fd.append('id', id);
  const res = await apiFD(`${API}?module=customers&action=save`, fd);
  saving = false;
  document.getElementById('saveBtn').disabled = false;
  if (res.success) {
    toast(id ? 'Customer updated' : 'Customer added', 'success');
    closeModal();
    loadStats();
    loadCustomers();
  } else {
    toast(res.message || 'Failed to save', 'error');
  }
}

async function deleteCust(id, name) {
  if (!confirm(`Delete customer "${name}"? Order history is preserved.`)) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await apiFD(`${API}?module=customers&action=delete`, fd);
  if (res.success) { toast('Customer deleted', 'success'); loadStats(); loadCustomers(); }
  else toast(res.message || 'Failed', 'error');
}

document.getElementById('custModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

loadStats();
loadCustomers();
</script>
</body>
</html>

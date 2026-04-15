<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requireAuth();
$user    = Auth::user();
$appName = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Orders — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --font:'Inter',sans-serif;
  --bg:#f0f2f5;--white:#fff;--accent:#2563eb;--accent-d:#1d4ed8;--accent-l:#eff6ff;
  --sidebar:#111827;--text1:#111827;--text2:#6b7280;--text3:#9ca3af;--border:#e5e7eb;
  --red:#dc2626;--red-bg:#fef2f2;--green:#16a34a;--green-bg:#f0fdf4;
  --yellow:#d97706;--yellow-bg:#fffbeb;--purple:#7c3aed;--purple-bg:#f5f3ff;
  --r:8px;--sh:0 1px 3px rgba(0,0,0,.08);--sh-md:0 4px 12px rgba(0,0,0,.1);
}
html,body{height:100%;font-family:var(--font);font-size:14px;color:var(--text1);background:var(--bg)}
body{display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}

/* Topbar */
#topbar{
  display:flex;align-items:center;gap:12px;padding:0 20px;height:56px;
  background:var(--white);border-bottom:1px solid var(--border);flex-shrink:0;
}
.page-title{font-size:16px;font-weight:700}
.page-sub{font-size:12px;color:var(--text3);margin-left:4px}
.tb-sp{flex:1}
.tb-btn{
  display:flex;align-items:center;gap:6px;height:34px;padding:0 14px;
  border-radius:6px;font-size:13px;font-weight:600;font-family:var(--font);cursor:pointer;
  transition:all .15s;border:none;
}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accent-d)}
.btn-outline{background:none;border:1px solid var(--border);color:var(--text2)}
.btn-outline:hover{border-color:var(--text2);color:var(--text1);background:var(--bg)}
.tb-btn svg{width:14px;height:14px;fill:currentColor}

/* Content */
#content{flex:1;overflow-y:auto;padding:20px}
#content::-webkit-scrollbar{width:5px}
#content::-webkit-scrollbar-thumb{background:var(--border);border-radius:5px}

/* Stats row */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat-card{background:var(--white);border-radius:var(--r);padding:16px;box-shadow:var(--sh)}
.stat-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:6px}
.stat-val{font-size:22px;font-weight:700;color:var(--text1);margin-bottom:2px}
.stat-sub{font-size:11px;color:var(--text3)}
.stat-accent{color:var(--accent)}
.stat-green{color:var(--green)}
.stat-red{color:var(--red)}
.stat-yellow{color:var(--yellow)}

/* Filters */
.filters-bar{
  display:flex;align-items:center;gap:10px;padding:12px 16px;
  background:var(--white);border-radius:var(--r);box-shadow:var(--sh);margin-bottom:14px;
  flex-wrap:wrap;
}
.filter-search{position:relative;flex:1;min-width:200px}
.filter-search svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;fill:var(--text3)}
.filter-search input{
  width:100%;height:36px;padding:0 12px 0 34px;
  border:1px solid var(--border);border-radius:6px;
  font-size:13px;font-family:var(--font);outline:none;color:var(--text1);
  transition:border-color .15s;
}
.filter-search input:focus{border-color:var(--accent)}
.filter-sel{
  height:36px;padding:0 10px;border:1px solid var(--border);border-radius:6px;
  font-size:13px;font-family:var(--font);color:var(--text1);background:var(--white);
  outline:none;cursor:pointer;min-width:130px;
}
.filter-date{height:36px;padding:0 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);color:var(--text1);background:var(--white);outline:none;cursor:pointer}

/* Table */
.table-wrap{background:var(--white);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
.table-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)}
.table-head h3{font-size:14px;font-weight:700}
.table-meta{font-size:12px;color:var(--text3)}
table{width:100%;border-collapse:collapse}
th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);background:var(--bg);border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafafa}
.td-invoice{font-weight:700;color:var(--accent);cursor:pointer}
.td-invoice:hover{text-decoration:underline}

/* Status badges */
.badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600}
.badge-completed{background:#dcfce7;color:var(--green)}
.badge-pending{background:var(--yellow-bg);color:var(--yellow)}
.badge-cancelled{background:var(--red-bg);color:var(--red)}
.badge-refunded{background:var(--purple-bg);color:var(--purple)}
.badge-partial{background:#e0f2fe;color:#0369a1}

/* Payment badges */
.pay-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
.pay-paid{background:#dcfce7;color:var(--green)}
.pay-partial{background:var(--yellow-bg);color:var(--yellow)}
.pay-unpaid{background:var(--red-bg);color:var(--red)}

/* Action btns */
.act-btns{display:flex;align-items:center;gap:4px}
.act-btn{
  width:28px;height:28px;border-radius:5px;border:1px solid var(--border);
  background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--text2);transition:all .15s;
}
.act-btn:hover{background:var(--bg);color:var(--text1);border-color:var(--text2)}
.act-btn.red:hover{background:var(--red-bg);border-color:var(--red);color:var(--red)}
.act-btn svg{width:13px;height:13px;fill:currentColor}

/* Pagination */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border)}
.pag-info{font-size:12px;color:var(--text3)}
.pag-btns{display:flex;gap:4px}
.pag-btn{
  width:30px;height:30px;border:1px solid var(--border);border-radius:6px;
  background:none;cursor:pointer;font-size:12px;font-weight:600;color:var(--text2);
  display:flex;align-items:center;justify-content:center;transition:all .15s;
}
.pag-btn:hover{border-color:var(--accent);color:var(--accent)}
.pag-btn.active{background:var(--accent);border-color:var(--accent);color:#fff}
.pag-btn:disabled{opacity:.4;cursor:not-allowed}

/* Table loading / empty */
.t-loading,.t-empty{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:60px 20px;color:var(--text3);gap:12px;
}
.t-empty svg{width:40px;height:40px;fill:currentColor;opacity:.3}
.t-empty p{font-size:13px}
.spin{width:24px;height:24px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Order Detail Modal ── */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px}
.modal.open{display:flex}
.mc{background:var(--white);border-radius:12px;width:100%;max-width:640px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:var(--sh-md)}
.mh{display:flex;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0}
.mh h3{font-size:15px;font-weight:700;flex:1}
.mc-x{width:28px;height:28px;border:none;background:none;cursor:pointer;color:var(--text2);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px}
.mc-x:hover{background:var(--bg)}
.mb{padding:20px;overflow-y:auto;flex:1}
.mb::-webkit-scrollbar{width:4px}
.mb::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.mf{display:flex;gap:8px;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--border);flex-shrink:0}

/* Order detail sections */
.od-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.od-box{background:var(--bg);border-radius:var(--r);padding:12px}
.od-box-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
.od-box-val{font-size:13px;font-weight:600;color:var(--text1)}
.od-box-sub{font-size:11px;color:var(--text2);margin-top:2px}
.od-items table{width:100%;border-collapse:collapse}
.od-items th{padding:7px 10px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--text3);background:var(--bg);text-align:left}
.od-items td{padding:9px 10px;font-size:12px;border-top:1px solid var(--border)}
.od-totals{margin-top:12px;padding:12px;background:var(--bg);border-radius:var(--r)}
.od-tr{display:flex;justify-content:space-between;font-size:12px;color:var(--text2);padding:3px 0}
.od-tr.total{font-size:15px;font-weight:700;color:var(--text1);padding-top:8px;margin-top:4px;border-top:1px dashed var(--border)}

/* Toast */
.toast-c{position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:8px;z-index:9999;pointer-events:none}
.toast{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--sidebar);color:#fff;border-radius:var(--r);font-size:13px;font-weight:500;box-shadow:var(--sh-md);animation:tin .2s ease;pointer-events:none}
.toast.success{background:var(--green)}.toast.error{background:var(--red)}.toast.warning{background:var(--yellow)}
@keyframes tin{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:translateX(0)}}

@media(max-width:900px){
  .stats-row{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
  .stats-row{grid-template-columns:1fr}
  .filters-bar{flex-direction:column;align-items:stretch}
}
</style>
</head>
<body>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="sb-main">
  <div id="topbar">
      <span class="page-title">Orders</span>
      <span class="page-sub" id="dateRange">Today</span>
      <div class="tb-sp"></div>
      <button class="tb-btn btn-outline" onclick="exportCSV()">
        <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        Export
      </button>
      <button class="tb-btn btn-primary" onclick="location.href='/nexapos/public/pos.php'">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        New Sale
      </button>
    </div>

    <div id="content">

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Today's Orders</div>
          <div class="stat-val stat-accent" id="stat-orders">—</div>
          <div class="stat-sub">Total transactions</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Today's Revenue</div>
          <div class="stat-val stat-green" id="stat-revenue">—</div>
          <div class="stat-sub">Paid amount</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Avg. Order Value</div>
          <div class="stat-val" id="stat-avg">—</div>
          <div class="stat-sub">Per transaction</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending / Due</div>
          <div class="stat-val stat-red" id="stat-due">—</div>
          <div class="stat-sub">Unpaid amount</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="filter-search">
          <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" id="searchInp" placeholder="Search invoice, customer…" oninput="filterOrders()">
        </div>
        <select class="filter-sel" id="statusFilter" onchange="filterOrders()">
          <option value="">All Status</option>
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="cancelled">Cancelled</option>
          <option value="refunded">Refunded</option>
        </select>
        <select class="filter-sel" id="payFilter" onchange="filterOrders()">
          <option value="">All Payments</option>
          <option value="paid">Paid</option>
          <option value="partial">Partial</option>
          <option value="unpaid">Unpaid</option>
        </select>
        <input type="date" class="filter-date" id="dateFrom" onchange="filterOrders()" title="From date">
        <input type="date" class="filter-date" id="dateTo" onchange="filterOrders()" title="To date">
        <button class="tb-btn btn-outline" onclick="clearFilters()">Clear</button>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <div class="table-head">
          <h3>Order List</h3>
          <span class="table-meta" id="tableMeta">Loading…</span>
        </div>
        <div id="tableBody">
          <div class="t-loading"><div class="spin"></div></div>
        </div>
        <div class="pagination" id="pagination" style="display:none">
          <span class="pag-info" id="pagInfo"></span>
          <div class="pag-btns" id="pagBtns"></div>
        </div>
      </div>

    </div>
  </div>
</div><!-- /.sb-main -->

<!-- Order Detail Modal -->
<div class="modal" id="orderModal">
  <div class="mc">
    <div class="mh">
      <h3 id="modalTitle">Order Detail</h3>
      <div style="display:flex;gap:8px;margin-right:8px">
        <button class="tb-btn btn-outline" style="height:30px;font-size:12px" onclick="printOrder()">
          <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
          Print
        </button>
      </div>
      <button class="mc-x" onclick="document.getElementById('orderModal').classList.remove('open')">×</button>
    </div>
    <div class="mb" id="orderDetail"></div>
    <div class="mf" id="orderModalFoot"></div>
  </div>
</div>

<div class="toast-c" id="toastWrap"></div>

<script>
const API = '../routes/api.php';
let allOrders = [], filteredOrders = [], currentPage = 1;
const perPage = 20;

// ── Fetch stats ──
async function loadStats() {
  const res = await fetch(`${API}?module=orders&action=stats`);
  const data = await res.json();
  if (!data.success) return;
  const s = data.data;
  const fmt = n => '৳' + parseFloat(n||0).toFixed(2);
  document.getElementById('stat-orders').textContent  = s.today_count  || 0;
  document.getElementById('stat-revenue').textContent = fmt(s.today_revenue);
  document.getElementById('stat-avg').textContent     = fmt(s.today_avg);
  document.getElementById('stat-due').textContent     = fmt(s.today_due);
}

// ── Fetch orders ──
async function loadOrders() {
  document.getElementById('tableBody').innerHTML = '<div class="t-loading"><div class="spin"></div></div>';
  const res  = await fetch(`${API}?module=orders&action=list&per_page=500`);
  const data = await res.json();
  if (!data.success) { toast('Failed to load orders','error'); return; }
  allOrders = data.data?.orders || [];
  // Set today's date range label
  const today = new Date().toLocaleDateString('en-BD',{weekday:'short',month:'short',day:'numeric'});
  document.getElementById('dateRange').textContent = today;
  filterOrders();
}

// ── Filter ──
function filterOrders() {
  const q       = document.getElementById('searchInp').value.toLowerCase();
  const status  = document.getElementById('statusFilter').value;
  const pay     = document.getElementById('payFilter').value;
  const dateFrom= document.getElementById('dateFrom').value;
  const dateTo  = document.getElementById('dateTo').value;

  filteredOrders = allOrders.filter(o => {
    if (q && !o.invoice_no?.toLowerCase().includes(q) && !o.customer_name?.toLowerCase().includes(q)) return false;
    if (status && o.status !== status) return false;
    if (pay) {
      const p = parseFloat(o.paid||0), t = parseFloat(o.total||0);
      if (pay === 'paid'    && !(p >= t && t > 0)) return false;
      if (pay === 'partial' && !(p > 0 && p < t))  return false;
      if (pay === 'unpaid'  && p > 0)               return false;
    }
    if (dateFrom && o.created_at?.slice(0,10) < dateFrom) return false;
    if (dateTo   && o.created_at?.slice(0,10) > dateTo)   return false;
    return true;
  });

  currentPage = 1;
  renderTable();
}

function clearFilters() {
  ['searchInp','statusFilter','payFilter','dateFrom','dateTo'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  filterOrders();
}

// ── Render table ──
function renderTable() {
  const total  = filteredOrders.length;
  const start  = (currentPage - 1) * perPage;
  const page   = filteredOrders.slice(start, start + perPage);
  const meta   = document.getElementById('tableMeta');
  const body   = document.getElementById('tableBody');
  const pag    = document.getElementById('pagination');

  meta.textContent = total + ' order' + (total !== 1 ? 's' : '') + ' found';

  if (!total) {
    body.innerHTML = `<div class="t-empty">
      <svg viewBox="0 0 24 24"><path d="M19 3H4.99C3.89 3 3 3.9 3 5l.01 14c0 1.1.89 2 1.99 2H19c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
      <p>No orders found</p></div>`;
    pag.style.display = 'none';
    return;
  }

  body.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Invoice</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Cashier</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        ${page.map((o, i) => {
          const paid  = parseFloat(o.paid  || 0);
          const total = parseFloat(o.total || 0);
          const due   = Math.max(0, total - paid);
          const payStatus = paid >= total && total > 0 ? 'paid' : paid > 0 ? 'partial' : 'unpaid';
          const payLabel  = {paid:'Paid', partial:'Partial', unpaid:'Unpaid'}[payStatus];
          const payClass  = {paid:'pay-paid', partial:'pay-partial', unpaid:'pay-unpaid'}[payStatus];
          const sClass    = {completed:'badge-completed',pending:'badge-pending',cancelled:'badge-cancelled',refunded:'badge-refunded'}[o.status] || 'badge-pending';
          const date = new Date(o.created_at);
          const dateStr = date.toLocaleDateString('en-BD',{month:'short',day:'numeric'});
          const timeStr = date.toLocaleTimeString('en-BD',{hour:'2-digit',minute:'2-digit'});
          return `<tr>
            <td style="color:var(--text3);font-size:12px">${start + i + 1}</td>
            <td class="td-invoice" onclick="openOrder(${o.id})">${o.invoice_no}</td>
            <td>${o.customer_name || '<span style="color:var(--text3)">Walk-in</span>'}</td>
            <td style="color:var(--text2)">${o.items_count || '—'}</td>
            <td style="font-weight:600">৳${parseFloat(o.total||0).toFixed(2)}</td>
            <td style="color:var(--green)">৳${paid.toFixed(2)}</td>
            <td><span class="badge ${sClass}">${o.status}</span></td>
            <td><span class="pay-badge ${payClass}">${payLabel}${due > 0 ? ' · ৳'+due.toFixed(2)+' due' : ''}</span></td>
            <td style="color:var(--text2);font-size:12px">${o.cashier_name || '—'}</td>
            <td style="color:var(--text2);font-size:12px">${dateStr}<br><span style="color:var(--text3)">${timeStr}</span></td>
            <td>
              <div class="act-btns">
                <button class="act-btn" onclick="openOrder(${o.id})" title="View">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </button>
                <button class="act-btn" onclick="printOrderId(${o.id})" title="Print">
                  <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                </button>
                ${o.status !== 'cancelled' && o.status !== 'refunded' ? `
                <button class="act-btn red" onclick="cancelOrder(${o.id})" title="Cancel">
                  <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>` : ''}
              </div>
            </td>
          </tr>`;
        }).join('')}
      </tbody>
    </table>`;

  // Pagination
  if (total > perPage) {
    pag.style.display = 'flex';
    const pages = Math.ceil(total / perPage);
    document.getElementById('pagInfo').textContent =
      `Showing ${start+1}–${Math.min(start+perPage,total)} of ${total}`;
    const btns = document.getElementById('pagBtns');
    btns.innerHTML = '';
    const addBtn = (label, page, disabled, active) => {
      const b = document.createElement('button');
      b.className = 'pag-btn' + (active ? ' active' : '');
      b.textContent = label;
      b.disabled = disabled;
      b.onclick = () => { currentPage = page; renderTable(); };
      btns.appendChild(b);
    };
    addBtn('‹', currentPage-1, currentPage===1, false);
    for (let p = Math.max(1,currentPage-2); p <= Math.min(pages,currentPage+2); p++) {
      addBtn(p, p, false, p===currentPage);
    }
    addBtn('›', currentPage+1, currentPage===pages, false);
  } else {
    pag.style.display = 'none';
  }
}

// ── Open order detail ──
let currentOrderId = null;
async function openOrder(id) {
  currentOrderId = id;
  document.getElementById('orderDetail').innerHTML = '<div class="t-loading"><div class="spin"></div></div>';
  document.getElementById('orderModal').classList.add('open');

  const res  = await fetch(`${API}?module=orders&action=get&id=${id}`);
  const data = await res.json();
  if (!data.success) { document.getElementById('orderDetail').innerHTML = '<p style="color:var(--red);padding:20px">Failed to load order</p>'; return; }

  const { order: o, items, payments } = data.data;
  document.getElementById('modalTitle').textContent = 'Order ' + o.invoice_no;

  const sClass = {completed:'badge-completed',pending:'badge-pending',cancelled:'badge-cancelled',refunded:'badge-refunded'}[o.status] || 'badge-pending';

  document.getElementById('orderDetail').innerHTML = `
    <div class="od-grid">
      <div class="od-box">
        <div class="od-box-label">Invoice</div>
        <div class="od-box-val">${o.invoice_no}</div>
        <div class="od-box-sub">${new Date(o.created_at).toLocaleString()}</div>
      </div>
      <div class="od-box">
        <div class="od-box-label">Status</div>
        <div class="od-box-val"><span class="badge ${sClass}">${o.status}</span></div>
        <div class="od-box-sub">Cashier: ${o.cashier_name || '—'}</div>
      </div>
      <div class="od-box">
        <div class="od-box-label">Customer</div>
        <div class="od-box-val">${o.customer_name || 'Walk-in Customer'}</div>
        <div class="od-box-sub">${o.customer_phone || ''}</div>
      </div>
      <div class="od-box">
        <div class="od-box-label">Payment</div>
        <div class="od-box-val">${payments?.map(p => p.method_name + ': ৳' + parseFloat(p.amount).toFixed(2)).join('<br>') || '—'}</div>
      </div>
    </div>

    <div class="od-items">
      <table>
        <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Price</th><th>Disc</th><th>Sub</th></tr></thead>
        <tbody>
          ${(items||[]).map(i => `
          <tr>
            <td style="font-weight:600">${i.name || i.product_name}</td>
            <td style="color:var(--text3)">${i.sku||'—'}</td>
            <td>${i.quantity} ${i.unit||'pcs'}</td>
            <td>৳${parseFloat(i.unit_price).toFixed(2)}</td>
            <td style="color:var(--green)">${parseFloat(i.discount_amount||0) > 0 ? '-৳'+parseFloat(i.discount_amount).toFixed(2) : '—'}</td>
            <td style="font-weight:600">৳${parseFloat(i.subtotal).toFixed(2)}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>

    <div class="od-totals">
      <div class="od-tr"><span>Subtotal</span><span>৳${parseFloat(o.subtotal||0).toFixed(2)}</span></div>
      ${parseFloat(o.discount_amount||0) > 0 ? `<div class="od-tr" style="color:var(--green)"><span>Discount</span><span>-৳${parseFloat(o.discount_amount).toFixed(2)}</span></div>` : ''}
      ${parseFloat(o.tax_amount||0)      > 0 ? `<div class="od-tr" style="color:var(--yellow)"><span>Tax</span><span>৳${parseFloat(o.tax_amount).toFixed(2)}</span></div>` : ''}
      <div class="od-tr total"><span>Total</span><span>৳${parseFloat(o.total||0).toFixed(2)}</span></div>
      <div class="od-tr"><span>Paid</span><span style="color:var(--green)">৳${parseFloat(o.paid||0).toFixed(2)}</span></div>
      ${parseFloat(o.change_due||0) > 0  ? `<div class="od-tr"><span>Change</span><span>৳${parseFloat(o.change_due).toFixed(2)}</span></div>` : ''}
      ${parseFloat(o.due||0)        > 0  ? `<div class="od-tr" style="color:var(--red)"><span>Due</span><span>৳${parseFloat(o.due).toFixed(2)}</span></div>` : ''}
    </div>
    ${o.note ? `<div style="margin-top:12px;padding:10px 12px;background:var(--yellow-bg);border-radius:var(--r);font-size:12px;color:var(--text2)"><strong>Note:</strong> ${o.note}</div>` : ''}`;

  const foot = document.getElementById('orderModalFoot');
  foot.innerHTML = '';
  if (o.status !== 'cancelled' && o.status !== 'refunded') {
    const cb = document.createElement('button');
    cb.className = 'tb-btn'; cb.style.cssText = 'background:var(--red-bg);border:1px solid rgba(220,38,38,.25);color:var(--red)';
    cb.innerHTML = 'Cancel Order';
    cb.onclick = () => cancelOrder(o.id);
    foot.appendChild(cb);
  }
  const pb = document.createElement('button');
  pb.className = 'tb-btn btn-primary'; pb.innerHTML = '🖨 Print Receipt';
  pb.onclick = () => printOrderId(o.id);
  foot.appendChild(pb);
}

// ── Cancel order ──
async function cancelOrder(id) {
  if (!confirm('Cancel this order?')) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('reason', 'Cancelled by ' + <?= json_encode($user['name'] ?? 'admin') ?>);
  const res  = await fetch(`${API}?module=orders&action=cancel`, { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    toast('Order cancelled','success');
    document.getElementById('orderModal').classList.remove('open');
    loadOrders(); loadStats();
  } else {
    toast(data.message || 'Failed to cancel','error');
  }
}

// ── Print ──
function printOrder() { if (currentOrderId) printOrderId(currentOrderId); }
async function printOrderId(id) {
  const res  = await fetch(`${API}?module=pos&action=get_receipt&id=${id}`);
  const data = await res.json();
  if (!data.success) { toast('Receipt not available','error'); return; }
  const { order:o, items, payments, outlet } = data.data;
  const rows = (items||[]).map(i => `<div style="display:flex;justify-content:space-between"><span>${i.name||i.product_name} ×${i.quantity}</span><span>৳${parseFloat(i.subtotal).toFixed(2)}</span></div>`).join('');
  const pays = (payments||[]).map(p => `<div style="display:flex;justify-content:space-between"><span>${p.method_name}</span><span>৳${parseFloat(p.amount).toFixed(2)}</span></div>`).join('');
  const w = window.open('','_blank','width=380,height=640');
  w.document.write(`<!DOCTYPE html><html><head><title>Receipt ${o.invoice_no}</title>
    <style>body{font-family:'Courier New',monospace;font-size:12px;margin:0;padding:12px}hr{border:none;border-top:1px dashed #ccc;margin:7px 0}.tot{font-weight:700;font-size:14px}</style>
    </head><body onload="window.print();window.close()">
    <div style="text-align:center"><strong style="font-size:14px">${outlet?.name||'NexaPOS'}</strong><br><small>${outlet?.address||''}</small></div>
    <hr>
    <div style="display:flex;justify-content:space-between"><span>Invoice</span><span>${o.invoice_no}</span></div>
    <div style="display:flex;justify-content:space-between"><span>Date</span><span>${new Date(o.created_at).toLocaleString()}</span></div>
    <hr>${rows}<hr>
    <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>৳${parseFloat(o.subtotal||0).toFixed(2)}</span></div>
    ${parseFloat(o.discount_amount||0)>0?`<div style="display:flex;justify-content:space-between"><span>Discount</span><span>-৳${parseFloat(o.discount_amount).toFixed(2)}</span></div>`:''}
    <div class="tot" style="display:flex;justify-content:space-between"><span>TOTAL</span><span>৳${parseFloat(o.total||0).toFixed(2)}</span></div>
    <hr>${pays}
    <div style="display:flex;justify-content:space-between"><span>Change</span><span>৳${parseFloat(o.change_due||0).toFixed(2)}</span></div>
    <hr><div style="text-align:center"><small>Thank you!</small></div>
    </body></html>`);
  w.document.close();
}

// ── Export CSV ──
function exportCSV() {
  const rows = [['Invoice','Customer','Items','Total','Paid','Status','Date']];
  filteredOrders.forEach(o => rows.push([
    o.invoice_no, o.customer_name||'Walk-in', o.items_count||0,
    parseFloat(o.total||0).toFixed(2), parseFloat(o.paid||0).toFixed(2),
    o.status, o.created_at?.slice(0,16)
  ]));
  const csv  = rows.map(r => r.map(v => '"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');
  const a    = document.createElement('a');
  a.href     = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = 'orders_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
}

// ── Toast ──
function toast(msg, type='info', dur=3000) {
  const wrap = document.getElementById('toastWrap');
  const el   = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => { el.style.cssText='opacity:0;transform:translateX(10px);transition:all .25s'; setTimeout(()=>el.remove(),260); }, dur);
}

// Close modal on backdrop click
document.getElementById('orderModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});

// Init
loadStats();
loadOrders();
</script>
</body>
</html>

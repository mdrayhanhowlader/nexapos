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
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Returns & Refunds — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/pages.css">
<style>
/* Step wizard */
.wizard-steps{display:flex;align-items:center;gap:0;margin-bottom:24px}
.wstep{display:flex;align-items:center;gap:8px;flex:1}
.wstep-num{width:28px;height:28px;border-radius:50%;background:var(--border);color:var(--text3);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.wstep-lbl{font-size:12px;font-weight:500;color:var(--text3);transition:color .2s}
.wstep-line{flex:1;height:2px;background:var(--border);margin:0 8px;transition:background .2s}
.wstep.done .wstep-num{background:#10b981;color:#fff}
.wstep.done .wstep-lbl{color:#10b981}
.wstep.done .wstep-line{background:#10b981}
.wstep.active .wstep-num{background:var(--accent);color:#fff}
.wstep.active .wstep-lbl{color:var(--accent);font-weight:600}
/* Return items table */
.ret-table{width:100%;border-collapse:collapse;font-size:13px}
.ret-table th{text-align:left;padding:8px 10px;font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:var(--bg)}
.ret-table td{padding:10px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
.ret-table tr:last-child td{border-bottom:none}
.ret-table input[type=number]{width:80px;height:32px;padding:0 8px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;outline:none;text-align:center}
.ret-table input:focus{border-color:var(--accent)}
.qty-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:var(--accent-bg);color:var(--accent)}
/* View detail */
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
.detail-label{font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.detail-val{font-size:14px;font-weight:500;color:var(--text1)}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/resources/views/partials/sidebar.php'; ?>
<div class="main">
<?php include dirname(__DIR__) . '/resources/views/partials/topbar.php'; ?>
<div class="content">

  <div class="page-header">
    <div>
      <h2 class="page-title">Returns &amp; Refunds</h2>
      <p class="page-sub">Process product returns and customer refunds</p>
    </div>
    <button class="btn btn-primary" onclick="openNewReturn()">
      <svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
      New Return
    </button>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Returns</span><div class="kpi-icon" style="background:#fef2f2;color:#ef4444"><svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg></div></div>
      <div class="kpi-value" id="kTotal">—</div><div class="kpi-sub">All time</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Today</span><div class="kpi-icon" style="background:#fffbeb;color:#f59e0b"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div></div>
      <div class="kpi-value" id="kToday">—</div><div class="kpi-sub">Returns today</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">This Month</span><div class="kpi-icon" style="background:#ecfdf5;color:#10b981"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg></div></div>
      <div class="kpi-value" id="kMonth">—</div><div class="kpi-sub">Returns this month</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><span class="kpi-label">Total Refunded</span><div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div></div>
      <div class="kpi-value" id="kRefunded">—</div><div class="kpi-sub">All-time refunds</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-row">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="searchInput" placeholder="Search return ref, invoice, customer…" oninput="dbLoad()">
    </div>
    <select class="filter-select" id="statusFilter" onchange="currentPage=1;loadList()">
      <option value="">All Status</option>
      <option value="approved">Approved</option>
      <option value="pending">Pending</option>
      <option value="rejected">Rejected</option>
    </select>
    <input type="date" class="filter-select" id="fromDate" onchange="currentPage=1;loadList()">
    <input type="date" class="filter-select" id="toDate" onchange="currentPage=1;loadList()">
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Return Ref</th><th>Invoice</th><th>Customer</th>
          <th>Items</th><th>Refund Amount</th><th>Method</th>
          <th>Reason</th><th>Date</th><th>Status</th><th></th>
        </tr></thead>
        <tbody id="retBody"><tr><td colspan="10" class="tbl-loading"><div class="spin"></div> Loading…</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="pagination" style="display:none"></div>
  </div>
</div>
</div>

<!-- ══ NEW RETURN MODAL ══ -->
<div class="modal-backdrop" id="retModal">
  <div class="modal" style="max-width:700px">
    <div class="modal-head">
      <h3>New Return / Refund</h3>
      <button class="modal-close" onclick="closeModal('retModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">

      <!-- Step indicator -->
      <div class="wizard-steps" id="wizardSteps">
        <div class="wstep active" id="ws1">
          <div class="wstep-num">1</div>
          <div class="wstep-lbl">Find Order</div>
          <div class="wstep-line"></div>
        </div>
        <div class="wstep" id="ws2">
          <div class="wstep-num">2</div>
          <div class="wstep-lbl">Select Items</div>
          <div class="wstep-line"></div>
        </div>
        <div class="wstep" id="ws3">
          <div class="wstep-num">3</div>
          <div class="wstep-lbl">Refund Details</div>
        </div>
      </div>

      <!-- Step 1: Search order -->
      <div id="step1">
        <div class="form-group">
          <label>Invoice Number or Order ID</label>
          <div style="display:flex;gap:8px">
            <input class="form-control" id="orderSearch" placeholder="e.g. INV-20260415-XXXX" style="flex:1"
              onkeydown="if(event.key==='Enter')searchOrder()">
            <button class="btn btn-primary" id="searchBtn" onclick="searchOrder()">Search</button>
          </div>
        </div>
        <div id="orderResult" style="display:none;margin-top:16px;padding:16px;background:var(--bg);border-radius:10px;border:1.5px solid var(--border)"></div>
      </div>

      <!-- Step 2: Select items -->
      <div id="step2" style="display:none">
        <div style="margin-bottom:12px;padding:10px 14px;background:var(--accent-bg);border-radius:8px;font-size:13px;color:var(--accent);font-weight:500" id="step2OrderInfo"></div>
        <p style="font-size:12px;color:var(--text2);margin-bottom:10px">Select items and quantities to return. Only returnable quantities are shown.</p>
        <table class="ret-table" id="itemsTable">
          <thead><tr><th>Product</th><th>Ordered</th><th>Already Returned</th><th>Return Qty</th><th>Refund</th></tr></thead>
          <tbody id="itemsTbody"></tbody>
        </table>
        <div style="text-align:right;margin-top:10px;font-size:14px;font-weight:700;color:var(--text1)">
          Refund Total: <span id="refundTotal" style="color:var(--accent)">৳0.00</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:16px">
          <button class="btn btn-ghost" onclick="goStep(1)">← Back</button>
          <button class="btn btn-primary" onclick="goStep(3)">Next: Refund Details →</button>
        </div>
      </div>

      <!-- Step 3: Reason + method -->
      <div id="step3" style="display:none">
        <div class="form-grid">
          <div class="form-group">
            <label>Return Reason *</label>
            <select class="form-control" id="retReason">
              <option value="Customer request">Customer request</option>
              <option value="Defective product">Defective / damaged product</option>
              <option value="Wrong item">Wrong item delivered</option>
              <option value="Quality issue">Quality issue</option>
              <option value="Expired product">Expired product</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Refund Method</label>
            <select class="form-control" id="retRefundMethod">
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="card">Card</option>
              <option value="store_credit">Store Credit</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="retRestock" checked>
            <span>Restock items back to inventory</span>
          </label>
          <p style="font-size:11px;color:var(--text3);margin-top:4px">If unchecked, items will be returned but NOT added back to stock (e.g. damaged goods).</p>
        </div>
        <div class="form-group">
          <label>Internal Note (optional)</label>
          <textarea class="form-control" id="retNote" rows="2" placeholder="Any notes about this return…"></textarea>
        </div>

        <!-- Summary box -->
        <div style="background:var(--bg);border:1.5px solid var(--border);border-radius:10px;padding:16px;margin-top:4px" id="retSummary"></div>

        <div style="display:flex;justify-content:space-between;margin-top:16px">
          <button class="btn btn-ghost" onclick="goStep(2)">← Back</button>
          <button class="btn btn-primary" id="processBtn" onclick="processReturn()">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fff;margin-right:4px"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            Process Refund
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ══ VIEW RETURN MODAL ══ -->
<div class="modal-backdrop" id="viewModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-head">
      <h3 id="viewTitle">Return Details</h3>
      <button class="modal-close" onclick="closeModal('viewModal')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body" id="viewBody">Loading…</div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
const fmt = v => '৳' + parseFloat(v||0).toFixed(2);
let currentPage = 1, totalPages = 1;
let currentOrder = null, selectedItems = [];

const dbLoad = debounce(() => { currentPage = 1; loadList(); }, 350);
const statusColors = { approved:'badge-green', pending:'badge-amber', rejected:'badge-red' };

// ── KPIs ─────────────────────────────────────────────────────────────────────
async function loadSummary() {
  const res = await api(`${API}?module=returns&action=summary`);
  if (!res.success) return;
  const d = res.data;
  document.getElementById('kTotal').textContent    = d.total;
  document.getElementById('kToday').textContent    = d.today;
  document.getElementById('kMonth').textContent    = d.this_month;
  document.getElementById('kRefunded').textContent = fmt(d.refunded);
}

// ── List ──────────────────────────────────────────────────────────────────────
async function loadList() {
  document.getElementById('retBody').innerHTML = `<tr><td colspan="10" class="tbl-loading"><div class="spin"></div> Loading…</td></tr>`;
  let url = `${API}?module=returns&action=list&page=${currentPage}&per_page=20`;
  const q = document.getElementById('searchInput').value;
  const s = document.getElementById('statusFilter').value;
  const f = document.getElementById('fromDate').value;
  const t = document.getElementById('toDate').value;
  if (q) url += `&search=${encodeURIComponent(q)}`;
  if (s) url += `&status=${s}`;
  if (f) url += `&from=${f}`;
  if (t) url += `&to=${t}`;

  const res = await api(url);
  const rows = res.data?.returns || [];
  const pg   = res.data?.pagination || {};
  totalPages = pg.total_pages || 1;

  if (!rows.length) {
    document.getElementById('retBody').innerHTML = `<tr><td colspan="10" class="tbl-empty">No returns found</td></tr>`;
    document.getElementById('pagination').style.display = 'none';
    return;
  }

  document.getElementById('retBody').innerHTML = rows.map(r => `
    <tr>
      <td><strong style="color:var(--accent)">${r.reference}</strong></td>
      <td style="font-size:12px;color:var(--text2)">${r.invoice_no||'—'}</td>
      <td>${r.customer_name||'Walk-in'}</td>
      <td style="text-align:center">${r.item_count}</td>
      <td style="font-weight:700;color:var(--red)">${fmt(r.refund_amount)}</td>
      <td><span class="badge badge-blue" style="text-transform:capitalize">${r.refund_method}</span></td>
      <td style="font-size:12px;color:var(--text2);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.reason||'—'}</td>
      <td style="font-size:12px;color:var(--text3)">${r.created_at?.substring(0,10)||'—'}</td>
      <td><span class="badge ${statusColors[r.status]||'badge-blue'}">${r.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="action-btn" onclick="viewReturn(${r.id})" title="View">
            <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
          </button>
        </div>
      </td>
    </tr>`).join('');

  renderPagination(pg, 'loadList');
  document.getElementById('pagination').style.display = totalPages > 1 ? 'flex' : 'none';
}

// ── View return ───────────────────────────────────────────────────────────────
async function viewReturn(id) {
  document.getElementById('viewBody').innerHTML = '<div class="tbl-loading"><div class="spin"></div> Loading…</div>';
  document.getElementById('viewModal').classList.add('open');
  const res = await api(`${API}?module=returns&action=get&id=${id}`);
  if (!res.success) { document.getElementById('viewBody').innerHTML = '<p>Error loading return</p>'; return; }
  const r = res.data;
  document.getElementById('viewTitle').textContent = `Return ${r.reference}`;
  document.getElementById('viewBody').innerHTML = `
    <div class="detail-grid">
      <div><div class="detail-label">Reference</div><div class="detail-val" style="color:var(--accent)">${r.reference}</div></div>
      <div><div class="detail-label">Status</div><span class="badge ${statusColors[r.status]||'badge-blue'}">${r.status}</span></div>
      <div><div class="detail-label">Original Invoice</div><div class="detail-val">${r.invoice_no||'—'}</div></div>
      <div><div class="detail-label">Customer</div><div class="detail-val">${r.customer_name||'Walk-in'}</div></div>
      <div><div class="detail-label">Reason</div><div class="detail-val">${r.reason||'—'}</div></div>
      <div><div class="detail-label">Refund Method</div><div class="detail-val" style="text-transform:capitalize">${r.refund_method}</div></div>
      <div><div class="detail-label">Processed By</div><div class="detail-val">${r.cashier_name||'—'}</div></div>
      <div><div class="detail-label">Date</div><div class="detail-val">${r.created_at?.substring(0,16)||'—'}</div></div>
    </div>
    <table class="ret-table" style="margin-bottom:16px">
      <thead><tr><th>Product</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Subtotal</th><th>Restocked</th></tr></thead>
      <tbody>
        ${(r.items||[]).map(it=>`
          <tr>
            <td style="font-weight:500">${it.name}</td>
            <td style="text-align:center">${parseFloat(it.quantity)}</td>
            <td style="text-align:right">${fmt(it.unit_price)}</td>
            <td style="text-align:right;font-weight:600">${fmt(it.subtotal)}</td>
            <td style="text-align:center">${it.restock?'<span class="badge badge-green">Yes</span>':'<span class="badge badge-red">No</span>'}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div style="text-align:right;font-size:16px;font-weight:700;color:var(--red)">
      Total Refund: ${fmt(r.refund_amount)}
    </div>
    ${r.note?`<div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:8px;font-size:13px;color:var(--text2)">${r.note}</div>`:''}`;
}

// ── New Return Wizard ─────────────────────────────────────────────────────────
function openNewReturn() {
  currentOrder = null; selectedItems = [];
  document.getElementById('orderSearch').value = '';
  document.getElementById('orderResult').style.display = 'none';
  goStep(1);
  document.getElementById('retModal').classList.add('open');
  setTimeout(() => document.getElementById('orderSearch').focus(), 100);
}

function goStep(n) {
  [1,2,3].forEach(i => {
    document.getElementById(`step${i}`).style.display = i===n ? 'block' : 'none';
    const ws = document.getElementById(`ws${i}`);
    ws.className = 'wstep' + (i < n ? ' done' : i===n ? ' active' : '');
  });
  if (n === 3) buildSummary();
}

// Step 1: Search order
async function searchOrder() {
  const q = document.getElementById('orderSearch').value.trim();
  if (!q) { toast('Enter invoice number or order ID', 'warning'); return; }
  const btn = document.getElementById('searchBtn');
  btn.disabled = true; btn.textContent = 'Searching…';
  const res = await api(`${API}?module=returns&action=get_order&q=${encodeURIComponent(q)}`);
  btn.disabled = false; btn.textContent = 'Search';

  const box = document.getElementById('orderResult');
  if (!res.success) {
    box.style.display = 'block';
    box.innerHTML = `<div style="color:var(--red);font-size:13px;font-weight:500">⚠ ${res.message||'Order not found'}</div>`;
    return;
  }
  currentOrder = res.data;
  const o = currentOrder;
  box.style.display = 'block';
  box.innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div>
        <div style="font-size:13px;font-weight:700;color:var(--text1)">${o.invoice_no} &nbsp;·&nbsp; ${o.customer_name||'Walk-in'}</div>
        <div style="font-size:12px;color:var(--text3);margin-top:2px">${o.created_at?.substring(0,10)||''} &nbsp;·&nbsp; Total: ${fmt(o.total)} &nbsp;·&nbsp; ${o.items.length} item(s) returnable</div>
      </div>
      <button class="btn btn-primary" style="padding:7px 16px;font-size:12px" onclick="goToItems()">
        Select Items →
      </button>
    </div>`;
}

// Step 2: Item selection
function goToItems() {
  if (!currentOrder) return;
  selectedItems = currentOrder.items.map(it => ({
    ...it,
    return_qty: 0,
    max_qty: parseFloat(it.quantity) - parseFloat(it.returned_qty||0),
  }));

  const info = document.getElementById('step2OrderInfo');
  info.textContent = `Order: ${currentOrder.invoice_no} — ${currentOrder.customer_name||'Walk-in'} — ${fmt(currentOrder.total)}`;

  renderItemsTable();
  goStep(2);
}

function renderItemsTable() {
  const tbody = document.getElementById('itemsTbody');
  tbody.innerHTML = selectedItems.map((it, i) => {
    const returnable = it.max_qty;
    return `<tr>
      <td style="font-weight:500">${it.name}<br><span style="font-size:11px;color:var(--text3)">${it.sku||''}</span></td>
      <td style="text-align:center"><span class="qty-badge">${parseFloat(it.quantity)}</span></td>
      <td style="text-align:center">${parseFloat(it.returned_qty||0) > 0 ? `<span style="color:var(--red);font-size:12px">${parseFloat(it.returned_qty)}</span>` : '—'}</td>
      <td style="text-align:center">
        <input type="number" min="0" max="${returnable}" step="1" value="${it.return_qty}"
          oninput="selectedItems[${i}].return_qty=Math.min(Math.max(0,parseFloat(this.value)||0),${returnable});calcRefundTotal()"
          style="width:75px">
        <div style="font-size:10px;color:var(--text3);margin-top:3px">max ${returnable}</div>
      </td>
      <td style="text-align:right;font-weight:600" id="lineRef${i}">৳0.00</td>
    </tr>`;
  }).join('');
  calcRefundTotal();
}

function calcRefundTotal() {
  let total = 0;
  selectedItems.forEach((it, i) => {
    const netPrice = parseFloat(it.unit_price) - parseFloat(it.discount_amount || 0);
    const line = (it.return_qty || 0) * netPrice;
    total += line;
    const el = document.getElementById(`lineRef${i}`);
    if (el) el.textContent = fmt(line);
  });
  document.getElementById('refundTotal').textContent = fmt(total);
}

// Step 3: Summary
function buildSummary() {
  const items = selectedItems.filter(it => it.return_qty > 0);
  if (!items.length) { toast('Select at least one item to return', 'warning'); goStep(2); return; }
  let total = items.reduce((s, it) => {
    const net = parseFloat(it.unit_price) - parseFloat(it.discount_amount || 0);
    return s + it.return_qty * net;
  }, 0);
  document.getElementById('retSummary').innerHTML = `
    <div style="font-size:13px;font-weight:600;margin-bottom:10px">Return Summary</div>
    ${items.map(it => {
      const net = parseFloat(it.unit_price) - parseFloat(it.discount_amount || 0);
      return `
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-bottom:1px solid var(--border)">
        <span>${it.name} × ${it.return_qty}${parseFloat(it.discount_amount||0)>0 ? ` <span style="font-size:11px;color:var(--text2)">(disc. ${fmt(parseFloat(it.discount_amount))})</span>` : ''}</span>
        <span>${fmt(it.return_qty * net)}</span>
      </div>`;
    }).join('')}
    <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;margin-top:10px;color:var(--red)">
      <span>Total Refund</span><span>${fmt(total)}</span>
    </div>`;
}

// Process return
async function processReturn() {
  const items = selectedItems.filter(it => it.return_qty > 0);
  if (!items.length) { toast('No items selected', 'warning'); return; }
  const btn = document.getElementById('processBtn');
  btn.disabled = true; btn.textContent = 'Processing…';

  const fd = new FormData();
  fd.append('order_id',      currentOrder.id);
  fd.append('reason',        document.getElementById('retReason').value);
  fd.append('refund_method', document.getElementById('retRefundMethod').value);
  fd.append('note',          document.getElementById('retNote').value);
  fd.append('restock',       document.getElementById('retRestock').checked ? 1 : 0);
  fd.append('items', JSON.stringify(items.map(it => ({
    order_item_id: it.id,
    product_id:    it.product_id,
    quantity:      it.return_qty,
  }))));

  const res = await apiFD(`${API}?module=returns&action=save`, fd);
  btn.disabled = false; btn.textContent = 'Process Refund';

  if (res.success) {
    toast(`Return ${res.data.reference} processed`, 'success');
    closeModal('retModal');
    loadSummary();
    loadList();
  } else {
    toast(res.message || 'Failed to process return', 'error');
  }
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.getElementById('retModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('retModal'); });
document.getElementById('viewModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal('viewModal'); });
document.getElementById('orderSearch').addEventListener('keydown', e => { if(e.key==='Enter') searchOrder(); });

loadSummary();
loadList();
</script>
</body>
</html>

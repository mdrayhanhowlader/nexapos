<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
$user       = Auth::user();
$appName    = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? Config::get('app.name', 'NexaPOS');
$canShifts  = Auth::can('shifts') || Auth::can('reports');
$canSeeAll  = Auth::can('reports');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Reports — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/pages.css">
<style>
.tab-bar{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--border);padding-bottom:0;flex-wrap:wrap}
.tab{padding:10px 16px;font-size:13px;font-weight:600;color:var(--text2);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
.tab:hover{color:var(--accent)}
.tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.tab-panel{display:none}.tab-panel.active{display:block}
.chart-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:20px;margin-bottom:20px}
.chart-card h4{font-size:14px;font-weight:700;margin-bottom:16px;color:var(--text1)}
canvas{max-height:300px}
.sum-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
.sum-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px}
.sum-lbl{font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.sum-val{font-size:22px;font-weight:700;color:var(--text1);letter-spacing:-.4px}
.sum-sub{font-size:12px;color:var(--text3);margin-top:3px}
.pl-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:14px}
.pl-total{display:flex;justify-content:space-between;padding:14px 0;margin-top:4px;font-size:16px;font-weight:700}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/resources/views/partials/sidebar.php'; ?>
<div class="main">
<?php include dirname(__DIR__) . '/resources/views/partials/topbar.php'; ?>
<div class="content">

  <div class="page-header">
    <div>
      <h2 class="page-title">Reports</h2>
      <p class="page-sub">Business analytics and insights</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="date" class="filter-select" id="fromDate">
      <span style="color:var(--text3);font-size:13px">to</span>
      <input type="date" class="filter-select" id="toDate">
      <button class="btn btn-primary" onclick="loadTab(activeTab)">
        <svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
        Refresh
      </button>
      <button class="btn" id="exportBtn" onclick="exportCSV()" style="background:#fff;border:1.5px solid var(--border);color:var(--text2);display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--r);font-size:13px;font-weight:500;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text2)'">
        <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        Export CSV
      </button>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tab-bar">
    <div class="tab active" data-tab="sales" onclick="switchTab('sales')">Sales Summary</div>
    <div class="tab" data-tab="products" onclick="switchTab('products')">Top Products</div>
    <div class="tab" data-tab="profit" onclick="switchTab('profit')">Profit & Loss</div>
    <div class="tab" data-tab="inventory" onclick="switchTab('inventory')">Inventory Value</div>
    <div class="tab" data-tab="customers" onclick="switchTab('customers')">Customers</div>
    <div class="tab" data-tab="cashier" onclick="switchTab('cashier')">Cashier</div>
    <?php if ($canShifts): ?>
    <div class="tab" data-tab="shifts" onclick="switchTab('shifts')">Shifts</div>
    <?php endif; ?>
  </div>

  <!-- Sales Summary -->
  <div class="tab-panel active" id="panel-sales">
    <div class="sum-grid">
      <div class="sum-card"><div class="sum-lbl">Total Orders</div><div class="sum-val" id="sk1">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Total Revenue</div><div class="sum-val" id="sk2">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Avg Order Value</div><div class="sum-val" id="sk3">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Net Revenue</div><div class="sum-val" id="sk4">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Total Discounts</div><div class="sum-val" id="sk5">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Total Tax</div><div class="sum-val" id="sk6">—</div></div>
    </div>
    <div class="chart-card">
      <h4>Daily Sales Revenue</h4>
      <canvas id="salesChart"></canvas>
    </div>
  </div>

  <!-- Top Products -->
  <div class="tab-panel" id="panel-products">
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Revenue</th><th>Orders</th></tr></thead>
          <tbody id="topProdsBody"><tr><td colspan="5" class="tbl-loading"><div class="spin"></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Profit & Loss -->
  <div class="tab-panel" id="panel-profit">
    <div class="chart-card" style="max-width:480px">
      <h4>Profit & Loss Statement</h4>
      <div id="plBody"><div class="tbl-loading"><div class="spin"></div></div></div>
    </div>
  </div>

  <!-- Inventory Value -->
  <div class="tab-panel" id="panel-inventory">
    <div class="sum-grid" style="grid-template-columns:1fr 1fr;max-width:480px">
      <div class="sum-card"><div class="sum-lbl">Cost Value</div><div class="sum-val" id="iv1">—</div><div class="sum-sub">At purchase cost</div></div>
      <div class="sum-card"><div class="sum-lbl">Retail Value</div><div class="sum-val" id="iv2">—</div><div class="sum-sub">At selling price</div></div>
    </div>
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Stock</th><th>Cost Value</th><th>Retail Value</th></tr></thead>
          <tbody id="invBody"><tr><td colspan="6" class="tbl-loading"><div class="spin"></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Customer Report -->
  <div class="tab-panel" id="panel-customers">
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Customer</th><th>Phone</th><th>Group</th><th>Orders</th><th>Total Spent</th><th>Last Visit</th></tr></thead>
          <tbody id="custRepBody"><tr><td colspan="6" class="tbl-loading"><div class="spin"></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Cashier Report -->
  <div class="tab-panel" id="panel-cashier">
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Cashier</th><th>Orders</th><th>Revenue</th><th>Discounts Given</th></tr></thead>
          <tbody id="cashierBody"><tr><td colspan="4" class="tbl-loading"><div class="spin"></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($canShifts): ?>
  <div class="tab-panel" id="panel-shifts">

    <!-- Filters -->
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
      <?php if ($canSeeAll): ?>
      <select id="shiftCashierFilter" onchange="loadShifts(document.getElementById('fromDate').value,document.getElementById('toDate').value)"
        style="height:36px;padding:0 12px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;font-family:inherit;color:var(--text1);background:#fff;min-width:180px">
        <option value="">All Cashiers</option>
      </select>
      <?php endif; ?>
      <span id="shiftPagInfo" style="font-size:12px;color:var(--text3);margin-left:auto"></span>
      <button id="shiftPrevBtn" onclick="shiftPage(-1)" style="height:32px;padding:0 12px;border:1.5px solid var(--border);border-radius:var(--r);background:#fff;cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <button id="shiftNextBtn" onclick="shiftPage(1)"  style="height:32px;padding:0 12px;border:1.5px solid var(--border);border-radius:var(--r);background:#fff;cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>

    <!-- Summary cards -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px" id="shiftStatCards">
      <div class="sum-card"><div class="sum-lbl">Total Shifts</div><div class="sum-val" id="sst1">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Open Now</div><div class="sum-val" id="sst2" style="color:#16a34a">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Closed</div><div class="sum-val" id="sst3">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Total Sales</div><div class="sum-val" id="sst4" style="color:var(--accent)">—</div></div>
      <div class="sum-card"><div class="sum-lbl">Avg Duration</div><div class="sum-val" id="sst5">—</div></div>
    </div>

    <!-- Shifts table -->
    <div class="table-card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Cashier</th>
              <th>Role</th>
              <th>Shift Opened</th>
              <th>Shift Closed</th>
              <th>Duration</th>
              <th>Opening Cash</th>
              <th>Orders</th>
              <th>Total Sales</th>
              <th>Expected Cash</th>
              <th>Actual Cash</th>
              <th>Difference</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="shiftsBody">
            <tr><td colspan="14" class="tbl-loading"><div class="spin"></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Shift Detail Modal -->
  <?php if ($canShifts): ?>
  <div id="shiftDetailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);position:sticky;top:0;background:#fff;z-index:1">
        <h3 style="font-size:15px;font-weight:700">Shift Details</h3>
        <button onclick="document.getElementById('shiftDetailModal').style.display='none'"
          style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text3);line-height:1">×</button>
      </div>
      <div id="shiftDetailBody" style="padding:20px 22px">
        <div style="text-align:center;padding:30px;color:var(--text3)">Loading…</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let salesChart = null;
let activeTab = 'sales';
let csvData = { tab: '', rows: [], cols: [] }; // for CSV export

// Default date range: this month
(function() {
  const now = new Date();
  const y = now.getFullYear(), m = String(now.getMonth()+1).padStart(2,'0');
  document.getElementById('fromDate').value = `${y}-${m}-01`;
  document.getElementById('toDate').value = now.toISOString().substring(0,10);
})();

function switchTab(tab) {
  activeTab = tab;
  document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab===tab));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById(`panel-${tab}`).classList.add('active');
  loadTab(tab);
}

const fmt = v => '৳' + parseFloat(v||0).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2});

async function loadTab(tab) {
  const from = document.getElementById('fromDate').value;
  const to = document.getElementById('toDate').value;
  if (!from || !to) { toast('Select a date range', 'warning'); return; }
  if (tab === 'sales') await loadSales(from, to);
  else if (tab === 'products') await loadTopProducts(from, to);
  else if (tab === 'profit') await loadPL(from, to);
  else if (tab === 'inventory') await loadInventory();
  else if (tab === 'customers') await loadCustReport(from, to);
  else if (tab === 'cashier') await loadCashier(from, to);
  else if (tab === 'shifts')  { _shiftPage = 1; await loadShifts(from, to); }
}

function exportCSV() {
  if (!csvData.rows.length) { toast('No data to export', 'warning'); return; }
  const esc = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
  const lines = [csvData.cols.map(c => esc(c.label)).join(',')];
  csvData.rows.forEach(r => {
    lines.push(csvData.cols.map(c => esc(r[c.key] ?? '')).join(','));
  });
  const from = document.getElementById('fromDate').value;
  const to   = document.getElementById('toDate').value;
  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = `${csvData.tab}_${from}_${to}.csv`;
  a.click();
  URL.revokeObjectURL(a.href);
}

async function loadSales(from, to) {
  const res = await api(`${API}?module=reports&action=sales_summary&from=${from}&to=${to}`);
  if (!res.success) return;
  const s = res.data.summary;
  document.getElementById('sk1').textContent = s.total_orders;
  document.getElementById('sk2').textContent = fmt(s.total_revenue);
  document.getElementById('sk3').textContent = fmt(s.avg_order_value);
  document.getElementById('sk4').textContent = fmt(s.net_revenue);
  document.getElementById('sk5').textContent = fmt(s.total_discounts);
  document.getElementById('sk6').textContent = fmt(s.total_tax);

  const daily = res.data.daily || [];
  csvData = {
    tab: 'sales_daily',
    rows: daily,
    cols: [{key:'date',label:'Date'},{key:'revenue',label:'Revenue'},{key:'orders',label:'Orders'}]
  };
  if (salesChart) salesChart.destroy();
  const ctx = document.getElementById('salesChart').getContext('2d');
  salesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: daily.map(d => d.date),
      datasets: [{
        label: 'Revenue (৳)', data: daily.map(d => parseFloat(d.revenue)),
        borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)',
        fill: true, tension: 0.3, pointRadius: 3
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { ticks: { callback: v => '৳'+v.toLocaleString() } } }
    }
  });
}

async function loadTopProducts(from, to) {
  const res = await api(`${API}?module=reports&action=top_products&from=${from}&to=${to}&limit=20`);
  const rows = res.data || [];
  csvData = {
    tab: 'top_products',
    rows,
    cols: [{key:'name',label:'Product'},{key:'qty_sold',label:'Qty Sold'},{key:'revenue',label:'Revenue'},{key:'orders',label:'Orders'}]
  };
  document.getElementById('topProdsBody').innerHTML = rows.length
    ? rows.map((r,i) => `<tr>
        <td style="font-weight:700;color:var(--text3)">${i+1}</td>
        <td style="font-weight:600;color:var(--text1)">${r.name}</td>
        <td>${parseFloat(r.qty_sold).toFixed(0)}</td>
        <td style="font-weight:700">${fmt(r.revenue)}</td>
        <td>${r.orders}</td>
      </tr>`).join('')
    : `<tr><td colspan="5" class="tbl-empty">No sales data in this period</td></tr>`;
}

async function loadPL(from, to) {
  const res = await api(`${API}?module=reports&action=profit_loss&from=${from}&to=${to}`);
  if (!res.success) { document.getElementById('plBody').innerHTML = '<p style="color:var(--red)">Failed to load</p>'; return; }
  const d = res.data;
  csvData = {
    tab: 'profit_loss',
    rows: [{label:'Revenue',value:d.revenue},{label:'COGS',value:d.cogs},{label:'Refunds',value:d.refunds},{label:'Gross Profit',value:d.grossProfit},{label:'Expenses',value:d.expenses},{label:'Net Profit',value:d.netProfit}],
    cols: [{key:'label',label:'Item'},{key:'value',label:'Amount'}]
  };
  const gColor = parseFloat(d.grossProfit)>=0 ? '#059669' : 'var(--red)';
  const nColor = parseFloat(d.netProfit)>=0 ? '#059669' : 'var(--red)';
  document.getElementById('plBody').innerHTML = `
    <div class="pl-row"><span style="color:var(--text2)">Revenue</span><strong>${fmt(d.revenue)}</strong></div>
    <div class="pl-row"><span style="color:var(--text2)">Cost of Goods (COGS)</span><span style="color:var(--red)">-${fmt(d.cogs)}</span></div>
    <div class="pl-row"><span style="color:var(--text2)">Refunds</span><span style="color:var(--red)">-${fmt(d.refunds)}</span></div>
    <div class="pl-row" style="border-bottom:2px solid var(--border)"><strong>Gross Profit</strong><strong style="color:${gColor}">${fmt(d.grossProfit)}</strong></div>
    <div class="pl-row"><span style="color:var(--text2)">Operating Expenses</span><span style="color:var(--red)">-${fmt(d.expenses)}</span></div>
    <div class="pl-total"><span>Net Profit</span><span style="color:${nColor}">${fmt(d.netProfit)}</span></div>
  `;
}

async function loadInventory() {
  const res = await api(`${API}?module=reports&action=inventory_value`);
  if (!res.success) return;
  const { items, totals } = res.data;
  csvData = {
    tab: 'inventory',
    rows: items,
    cols: [{key:'name',label:'Product'},{key:'sku',label:'SKU'},{key:'category',label:'Category'},{key:'stock',label:'Stock'},{key:'cost_value',label:'Cost Value'},{key:'retail_value',label:'Retail Value'}]
  };
  document.getElementById('iv1').textContent = fmt(totals.cost_total);
  document.getElementById('iv2').textContent = fmt(totals.retail_total);
  document.getElementById('invBody').innerHTML = items.length
    ? items.map(r => `<tr>
        <td style="font-weight:600;color:var(--text1)">${r.name}</td>
        <td style="color:var(--text3);font-size:12px">${r.sku||'—'}</td>
        <td>${r.category||'—'}</td>
        <td style="font-weight:600">${r.stock}</td>
        <td>${fmt(r.cost_value)}</td>
        <td style="font-weight:700">${fmt(r.retail_value)}</td>
      </tr>`).join('')
    : `<tr><td colspan="6" class="tbl-empty">No inventory data</td></tr>`;
}

async function loadCustReport(from, to) {
  const res = await api(`${API}?module=reports&action=customer_report&from=${from}&to=${to}`);
  const rows = res.data || [];
  csvData = {
    tab: 'customers',
    rows,
    cols: [{key:'name',label:'Customer'},{key:'phone',label:'Phone'},{key:'group',label:'Group'},{key:'orders',label:'Orders'},{key:'spent',label:'Total Spent'},{key:'last_visit',label:'Last Visit'}]
  };
  const gc = { regular:'badge-blue', vip:'badge-purple', wholesale:'badge-green' };
  document.getElementById('custRepBody').innerHTML = rows.length
    ? rows.map(r => `<tr>
        <td style="font-weight:600;color:var(--text1)">${r.name}</td>
        <td>${r.phone||'—'}</td>
        <td><span class="badge ${gc[r.group]||'badge-blue'}">${r.group||'regular'}</span></td>
        <td>${r.orders}</td>
        <td style="font-weight:700">${fmt(r.spent)}</td>
        <td style="color:var(--text3);font-size:12px">${r.last_visit?.substring(0,10)||'—'}</td>
      </tr>`).join('')
    : `<tr><td colspan="6" class="tbl-empty">No customer data in this period</td></tr>`;
}

async function loadCashier(from, to) {
  const res = await api(`${API}?module=reports&action=cashier_report&from=${from}&to=${to}`);
  const rows = res.data || [];
  csvData = {
    tab: 'cashier',
    rows,
    cols: [{key:'cashier',label:'Cashier'},{key:'orders',label:'Orders'},{key:'revenue',label:'Revenue'},{key:'discounts',label:'Discounts'}]
  };
  document.getElementById('cashierBody').innerHTML = rows.length
    ? rows.map(r => `<tr>
        <td style="font-weight:600;color:var(--text1)">${r.cashier}</td>
        <td>${r.orders}</td>
        <td style="font-weight:700">${fmt(r.revenue)}</td>
        <td style="color:var(--red)">${fmt(r.discounts)}</td>
      </tr>`).join('')
    : `<tr><td colspan="4" class="tbl-empty">No data in this period</td></tr>`;
}

// ── Shift report state ─────────────────────────
let _shiftPage = 1;

function shiftPage(dir) {
  _shiftPage = Math.max(1, _shiftPage + dir);
  const from = document.getElementById('fromDate').value;
  const to   = document.getElementById('toDate').value;
  loadShifts(from, to);
}

function _shiftDur(openedAt, closedAt) {
  const from = new Date(openedAt);
  const to   = closedAt ? new Date(closedAt) : new Date();
  const mins = Math.floor((to - from) / 60000);
  if (mins < 60) return mins + 'm';
  return Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm';
}

async function loadShifts(from, to) {
  const cashierEl = document.getElementById('shiftCashierFilter');
  const cashierId = cashierEl ? cashierEl.value : '';
  let url = `${API}?module=pos&action=get_shift_history&from=${from}&to=${to}&page=${_shiftPage}`;
  if (cashierId) url += `&cashier_id=${cashierId}`;

  const res  = await api(url);
  const d    = res.data || {};
  const rows = d.rows     || [];
  const stats= d.stats    || {};

  // Populate cashier filter dropdown (first load only)
  if (cashierEl && (d.cashiers || []).length && cashierEl.options.length <= 1) {
    cashierEl.innerHTML = '<option value="">All Cashiers</option>' +
      (d.cashiers || []).map(c => `<option value="${c.id}">${c.name} (${c.role_name})</option>`).join('');
  }

  // Summary cards
  const avgMins = Math.round(parseFloat(stats.avg_duration_mins) || 0);
  const avgDur  = avgMins < 60 ? avgMins + 'm' : Math.floor(avgMins/60) + 'h ' + (avgMins%60) + 'm';
  document.getElementById('sst1').textContent = stats.total_shifts  || '0';
  document.getElementById('sst2').textContent = stats.open_shifts   || '0';
  document.getElementById('sst3').textContent = stats.closed_shifts || '0';
  document.getElementById('sst4').textContent = fmt(stats.grand_sales || 0);
  document.getElementById('sst5').textContent = avgDur || '—';

  // Pagination info
  const total   = d.total || 0;
  const perPage = 25;
  const pages   = Math.max(1, Math.ceil(total / perPage));
  const pagEl   = document.getElementById('shiftPagInfo');
  if (pagEl) pagEl.textContent = `Page ${_shiftPage} of ${pages} (${total} shifts)`;
  const prevBtn = document.getElementById('shiftPrevBtn');
  const nextBtn = document.getElementById('shiftNextBtn');
  if (prevBtn) prevBtn.disabled = _shiftPage <= 1;
  if (nextBtn) nextBtn.disabled = _shiftPage >= pages;

  // CSV export data
  csvData = {
    tab: 'shifts',
    rows,
    cols: [
      {key:'cashier_name',     label:'Cashier'},
      {key:'role_name',        label:'Role'},
      {key:'opened_at',        label:'Opened At'},
      {key:'closed_at',        label:'Closed At'},
      {key:'opening_balance',  label:'Opening Cash'},
      {key:'orders_count',     label:'Orders'},
      {key:'sales_total',      label:'Total Sales'},
      {key:'expected_balance', label:'Expected Cash'},
      {key:'closing_balance',  label:'Actual Cash'},
      {key:'difference',       label:'Difference'},
      {key:'note',             label:'Note'},
      {key:'status',           label:'Status'},
    ]
  };

  const tbody = document.getElementById('shiftsBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="14" class="tbl-empty">No shifts found in this period</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map((r, idx) => {
    const openDt  = new Date(r.opened_at);
    const closeDt = r.closed_at ? new Date(r.closed_at) : null;
    const dur     = _shiftDur(r.opened_at, r.closed_at);
    const diff    = parseFloat(r.difference || 0);
    const diffClr = diff < 0 ? '#dc2626' : diff > 0 ? '#16a34a' : '#6b7280';
    const diffTxt = r.closing_balance != null
      ? (diff > 0 ? '+' : '') + fmt(diff)
      : '—';
    const stOpen  = r.status === 'open';
    const statusHtml = stOpen
      ? `<span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">● Open</span>`
      : `<span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">Closed</span>`;

    const rowNum = (_shiftPage - 1) * 25 + idx + 1;

    return `<tr>
      <td style="color:var(--text3);font-size:12px">${rowNum}</td>
      <td>
        <div style="font-weight:700;font-size:13px">${r.cashier_name}</div>
        <div style="font-size:11px;color:var(--text3)">${r.role_name||''}</div>
      </td>
      <td><span style="font-size:11px;color:var(--text3)">${r.role_name||''}</span></td>
      <td>
        <div style="font-size:13px;font-weight:600">${openDt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div>
        <div style="font-size:11px;color:var(--text3)">${openDt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
      </td>
      <td>
        ${closeDt
          ? `<div style="font-size:13px;font-weight:600">${closeDt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div>
             <div style="font-size:11px;color:var(--text3)">${closeDt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>`
          : `<span style="color:#16a34a;font-size:12px;font-weight:600">Still open</span>`}
      </td>
      <td style="font-size:13px;color:var(--text2)">${dur}</td>
      <td style="font-weight:600">${fmt(r.opening_balance)}</td>
      <td style="font-weight:700;color:var(--accent)">${r.orders_count||0}</td>
      <td style="font-weight:700;color:#16a34a">${fmt(r.sales_total||r.total_sales||0)}</td>
      <td>${fmt(r.expected_balance||0)}</td>
      <td style="font-weight:600">${r.closing_balance != null ? fmt(r.closing_balance) : '<span style="color:var(--text3)">—</span>'}</td>
      <td style="font-weight:700;color:${diffClr}">${diffTxt}</td>
      <td>${statusHtml}</td>
      <td>
        <button onclick="openShiftDetail(${r.id})"
          style="padding:4px 12px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">
          Details
        </button>
      </td>
    </tr>`;
  }).join('');
}

// ── Shift detail modal ──────────────────────────
async function openShiftDetail(id) {
  const modal = document.getElementById('shiftDetailModal');
  const body  = document.getElementById('shiftDetailBody');
  if (!modal) return;
  modal.style.display = 'flex';
  body.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><div class="spin" style="margin:0 auto"></div></div>';

  const res = await api(`${API}?module=pos&action=get_shift_detail&id=${id}`);
  if (!res.success) { body.innerHTML = `<p style="color:var(--red);text-align:center">${res.message||'Failed to load'}</p>`; return; }

  const { shift: s, payments, movements, orders, orders_count } = res.data;
  const openDt  = new Date(s.opened_at);
  const closeDt = s.closed_at ? new Date(s.closed_at) : null;
  const dur     = _shiftDur(s.opened_at, s.closed_at);
  const diff    = parseFloat(s.difference || 0);
  const diffClr = diff < 0 ? '#dc2626' : diff > 0 ? '#16a34a' : '#6b7280';
  const cashSales = payments.filter(p => p.type === 'cash').reduce((a,p) => a + parseFloat(p.total), 0);

  body.innerHTML = `
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1e40af,#2563eb);border-radius:10px;padding:18px 20px;color:#fff;margin-bottom:18px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-size:18px;font-weight:800">${s.cashier_name}</div>
          <div style="font-size:12px;opacity:.8;margin-top:2px">${s.role_name} &nbsp;·&nbsp; Shift #${s.id}</div>
        </div>
        <span style="background:${s.status==='open'?'#22c55e':'rgba(255,255,255,.2)'};padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700">
          ${s.status === 'open' ? '● Open' : 'Closed'}
        </span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;font-size:12px">
        <div><span style="opacity:.7">Opened</span><br><strong>${openDt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})} ${openDt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</strong></div>
        <div><span style="opacity:.7">Closed</span><br><strong>${closeDt ? closeDt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) + ' ' + closeDt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : 'Still open'}</strong></div>
        <div><span style="opacity:.7">Duration</span><br><strong>${dur}</strong></div>
        <div><span style="opacity:.7">Total Orders</span><br><strong>${orders_count}</strong></div>
      </div>
    </div>

    <!-- Cash summary -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px">
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:11px;font-weight:600;color:#16a34a;margin-bottom:4px">OPENING CASH</div>
        <div style="font-size:18px;font-weight:800;color:#15803d">${fmt(s.opening_balance)}</div>
      </div>
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:11px;font-weight:600;color:#2563eb;margin-bottom:4px">TOTAL SALES</div>
        <div style="font-size:18px;font-weight:800;color:#1d4ed8">${fmt(s.sales_total||s.total_sales||0)}</div>
      </div>
      <div style="background:${diff < 0 ? '#fef2f2' : '#f0fdf4'};border:1px solid ${diff < 0 ? '#fecaca' : '#bbf7d0'};border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:11px;font-weight:600;color:${diffClr};margin-bottom:4px">DIFFERENCE</div>
        <div style="font-size:18px;font-weight:800;color:${diffClr}">${s.closing_balance != null ? (diff > 0 ? '+' : '') + fmt(diff) : '—'}</div>
      </div>
    </div>

    <!-- Payment breakdown -->
    <div style="margin-bottom:18px">
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text3);margin-bottom:10px">Payment Method Breakdown</div>
      ${payments.length ? payments.map(p => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:#f9fafb;border-radius:7px;margin-bottom:6px;border:1px solid #f3f4f6">
          <div>
            <span style="font-weight:600;font-size:13px">${p.name}</span>
            <span style="font-size:11px;color:var(--text3);margin-left:6px">${p.txn_count} transaction${p.txn_count!=1?'s':''}</span>
          </div>
          <strong style="font-size:14px;color:#1d4ed8">${fmt(p.total)}</strong>
        </div>`).join('')
      : '<div style="color:var(--text3);font-size:13px;padding:8px 0">No payments recorded</div>'}
    </div>

    <!-- Expected vs Actual cash -->
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;margin-bottom:18px">
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#92400e;margin-bottom:10px">Cash Reconciliation</div>
      ${[
        ['Opening Balance', fmt(s.opening_balance)],
        ['+ Cash Sales',    fmt(cashSales)],
        ['+ Cash In',       fmt(s.total_cash_in||0)],
        ['− Cash Out',      '−' + fmt(s.total_cash_out||0)],
        ['− Refunds',       '−' + fmt(s.total_refunds||0)],
        ['= Expected Cash', fmt(s.expected_balance||0), true],
        ['Actual Cash',     s.closing_balance != null ? fmt(s.closing_balance) : '—', false, true],
      ].map(([lbl,val,bold,highlight]) => `
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #fde68a;font-size:13px${bold?';font-weight:800;border-top:2px solid #f59e0b;padding-top:8px':''}${highlight?';color:'+diffClr+';font-weight:800':''}">
          <span style="color:#78350f">${lbl}</span>
          <strong>${val}</strong>
        </div>`).join('')}
    </div>

    <!-- Cash movements -->
    ${movements.length ? `
    <div style="margin-bottom:18px">
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text3);margin-bottom:10px">Cash In / Out Movements</div>
      ${movements.map(m => {
        const isCashIn = m.type === 'cash_in';
        return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#f9fafb;border-radius:7px;margin-bottom:5px;border:1px solid #f3f4f6">
          <div>
            <span style="font-size:12px;font-weight:600;color:${isCashIn ? '#16a34a' : '#dc2626'}">${isCashIn ? '↑ Cash In' : '↓ Cash Out'}</span>
            ${m.reason ? `<span style="font-size:11px;color:var(--text3);margin-left:6px">— ${m.reason}</span>` : ''}
            <div style="font-size:11px;color:var(--text3);margin-top:1px">By ${m.by_name} · ${new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
          </div>
          <strong style="color:${isCashIn ? '#16a34a' : '#dc2626'}">${isCashIn ? '+' : '−'}${fmt(m.amount)}</strong>
        </div>`;
      }).join('')}
    </div>` : ''}

    <!-- Recent orders -->
    ${orders.length ? `
    <div>
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text3);margin-bottom:10px">
        Recent Orders ${orders_count > 10 ? `(showing 10 of ${orders_count})` : ''}
      </div>
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="background:#f9fafb">
              <th style="text-align:left;padding:6px 10px;font-size:11px;color:var(--text3);font-weight:600;border-bottom:1px solid #e5e7eb">Invoice</th>
              <th style="text-align:left;padding:6px 10px;font-size:11px;color:var(--text3);font-weight:600;border-bottom:1px solid #e5e7eb">Customer</th>
              <th style="text-align:right;padding:6px 10px;font-size:11px;color:var(--text3);font-weight:600;border-bottom:1px solid #e5e7eb">Total</th>
              <th style="text-align:right;padding:6px 10px;font-size:11px;color:var(--text3);font-weight:600;border-bottom:1px solid #e5e7eb">Time</th>
            </tr>
          </thead>
          <tbody>
            ${orders.map(o => `<tr style="border-bottom:1px solid #f3f4f6">
              <td style="padding:7px 10px;font-weight:600;color:var(--accent)">${o.invoice_no}</td>
              <td style="padding:7px 10px;color:var(--text2)">${o.customer_name || 'Walk-in'}</td>
              <td style="padding:7px 10px;text-align:right;font-weight:700">${fmt(o.total)}</td>
              <td style="padding:7px 10px;text-align:right;color:var(--text3)">${new Date(o.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>` : ''}

    ${s.note ? `<div style="margin-top:14px;padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;font-size:12px;color:var(--text2)">
      <strong>Note:</strong> ${s.note}
    </div>` : ''}
  `;
}

loadTab('sales');
</script>
</body>
</html>

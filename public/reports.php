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

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
const API = '../routes/api.php';
let salesChart = null;
let activeTab = 'sales';

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
  document.getElementById('cashierBody').innerHTML = rows.length
    ? rows.map(r => `<tr>
        <td style="font-weight:600;color:var(--text1)">${r.cashier}</td>
        <td>${r.orders}</td>
        <td style="font-weight:700">${fmt(r.revenue)}</td>
        <td style="color:var(--red)">${fmt(r.discounts)}</td>
      </tr>`).join('')
    : `<tr><td colspan="4" class="tbl-empty">No data in this period</td></tr>`;
}

loadTab('sales');
</script>
</body>
</html>

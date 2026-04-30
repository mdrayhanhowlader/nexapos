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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include __DIR__ . '/includes/pwa.php'; ?>
<title>Dashboard — <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f0f2f5;--white:#fff;--border:#e2e5eb;
  --accent:#2563eb;--accent-h:#1d4ed8;--accent-bg:#eff6ff;--accent-text:#1d4ed8;
  --text1:#111827;--text2:#6b7280;--text3:#9ca3af;
  --green:#10b981;--green-bg:#ecfdf5;
  --red:#ef4444;--red-bg:#fef2f2;
  --amber:#f59e0b;--amber-bg:#fffbeb;
  --purple:#8b5cf6;--purple-bg:#f5f3ff;
  --font:'Inter',-apple-system,sans-serif;
  --r:8px;--r-lg:12px;
  --shadow:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);
}
html,body{height:100%;font-family:var(--font);-webkit-font-smoothing:antialiased;background:var(--bg)}
body{display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}

/* Topbar */
.topbar{
  background:var(--white);border-bottom:1px solid var(--border);
  padding:0 24px;height:58px;
  display:flex;align-items:center;gap:14px;flex-shrink:0;
}
.topbar h1{font-size:16px;font-weight:700;color:var(--text1);flex:1}
.topbar-date{font-size:12px;color:var(--text2);white-space:nowrap}
.tb-pos{
  display:flex;align-items:center;gap:7px;
  padding:7px 14px;border-radius:var(--r);
  background:var(--accent);color:#fff;
  font-family:var(--font);font-size:13px;font-weight:600;
  text-decoration:none;cursor:pointer;border:none;
  transition:background .15s;box-shadow:0 2px 6px rgba(37,99,235,.25);
}
.tb-pos:hover{background:var(--accent-h)}
.tb-pos svg{width:14px;height:14px;fill:#fff}

/* Content */
.content{flex:1;overflow-y:auto;padding:22px}
.content::-webkit-scrollbar{width:5px}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:5px}

/* Filter bar */
.filter-bar{
  display:flex;align-items:center;gap:10px;
  background:var(--white);padding:14px 18px;
  border:1px solid var(--border);border-radius:var(--r-lg);
  margin-bottom:20px;flex-wrap:wrap;
}
.filter-bar label{font-size:12px;font-weight:600;color:var(--text2)}
.filter-inp{
  height:34px;padding:0 12px;background:var(--bg);
  border:1.5px solid var(--border);border-radius:var(--r);
  color:var(--text1);font-family:var(--font);font-size:13px;outline:none;
  transition:border-color .15s;
}
.filter-inp:focus{border-color:var(--accent);background:var(--white)}
.filter-btn{
  height:34px;padding:0 14px;background:var(--accent);border:none;
  border-radius:var(--r);color:#fff;font-family:var(--font);
  font-size:13px;font-weight:600;cursor:pointer;
  display:flex;align-items:center;gap:6px;transition:background .15s;
}
.filter-btn:hover{background:var(--accent-h)}
.filter-btn svg{width:14px;height:14px;fill:#fff}

/* KPI grid */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.kpi-card{
  background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);
  padding:18px 20px;transition:box-shadow .15s,transform .15s;
}
.kpi-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px}
.kpi-label{font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}
.kpi-icon{width:36px;height:36px;border-radius:9px;display:grid;place-items:center;flex-shrink:0}
.kpi-icon svg{width:18px;height:18px;fill:currentColor}
.kpi-value{font-size:24px;font-weight:700;color:var(--text1);letter-spacing:-.5px;margin-bottom:4px;font-variant-numeric:tabular-nums}
.kpi-sub{font-size:11px;color:var(--text3)}

/* Quick actions */
.qa-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px}
.qa-btn{
  display:flex;flex-direction:column;align-items:center;gap:8px;
  padding:14px 8px;border-radius:var(--r-lg);
  background:var(--white);border:1.5px solid var(--border);
  cursor:pointer;text-decoration:none;
  transition:all .15s;text-align:center;
}
.qa-btn:hover{border-color:var(--accent);background:var(--accent-bg);transform:translateY(-2px);box-shadow:var(--shadow-md)}
.qa-ico{
  width:40px;height:40px;border-radius:10px;
  display:grid;place-items:center;flex-shrink:0;
}
.qa-ico svg{width:20px;height:20px;fill:currentColor}
.qa-t{font-size:12px;font-weight:600;color:var(--text1)}
.qa-s{font-size:10px;color:var(--text3);margin-top:1px}

/* Charts row */
.charts-row{display:grid;grid-template-columns:1.7fr 1fr;gap:16px;margin-bottom:20px}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 20px}
.card-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px}
.card-title{font-size:14px;font-weight:700;color:var(--text1)}
.card-sub{font-size:11px;color:var(--text3);margin-top:2px}

/* Bottom grid */
.bottom-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px;padding:8px 14px;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:11px 14px;font-size:13px;color:var(--text2);border-bottom:1px solid #f3f4f6}
td:first-child{color:var(--text1);font-weight:500}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafafa}

/* Badges */
.badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600}
.badge-green{background:var(--green-bg);color:#059669}
.badge-amber{background:var(--amber-bg);color:#d97706}
.badge-red{background:var(--red-bg);color:var(--red)}
.badge-blue{background:var(--accent-bg);color:var(--accent-text)}
.badge-gray{background:var(--bg);color:var(--text3)}

/* Low stock */
.ls-item{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #f3f4f6}
.ls-item:last-child{border:none}
.ls-name{font-size:13px;font-weight:500;color:var(--text1)}
.ls-cat{font-size:11px;color:var(--text3);margin-top:1px}
.ls-qty{font-size:13px;font-weight:700;color:var(--red)}

/* Spinner */
.spin{width:18px;height:18px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:rot .6s linear infinite}
@keyframes rot{to{transform:rotate(360deg)}}
.loading{display:flex;align-items:center;justify-content:center;padding:28px;color:var(--text3);font-size:13px;gap:10px}

/* Responsive */
@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.charts-row{grid-template-columns:1fr}.qa-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.bottom-grid{grid-template-columns:1fr}.topbar{padding:0 16px 0 56px}.qa-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr 1fr}.content{padding:14px}.qa-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="sb-main">
  <header class="topbar">
    <h1>Dashboard</h1>
    <span class="topbar-date" id="topbar-date"></span>
    <!-- Notification Bell -->
    <div class="notif-wrap" id="notifWrap" style="position:relative">
      <button class="notif-btn" title="Stock Alerts" id="dashBellBtn" style="background:none;border:none;cursor:pointer;width:36px;height:36px;border-radius:8px;display:grid;place-items:center;color:#6b7280;position:relative;transition:background .15s">
        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        <span id="dashNotifCount" style="display:none;position:absolute;top:4px;right:3px;min-width:16px;height:16px;border-radius:8px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;align-items:center;justify-content:center;padding:0 3px;line-height:1">0</span>
      </button>
      <div id="notifPanel" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:280px;background:#fff;border:1px solid #e2e5eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:600">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #e2e5eb;font-size:13px">
          <strong style="color:#111827;font-weight:600">Low Stock Alerts</strong>
          <a href="inventory.php" style="font-size:12px;color:#2563eb;font-weight:500">View all →</a>
        </div>
        <div id="dashNotifList" style="max-height:260px;overflow-y:auto">
          <div style="padding:16px;text-align:center;color:#9ca3af;font-size:13px">Loading…</div>
        </div>
      </div>
    </div>
    <a href="pos.php" class="tb-pos">
      <svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
      Open POS
    </a>
  </header>

  <div class="content">

    <!-- Period filter -->
    <div class="filter-bar">
      <label>Period:</label>
      <input class="filter-inp" type="date" id="fromDate" value="<?= date('Y-m-01') ?>">
      <span style="color:var(--text3);font-size:12px">to</span>
      <input class="filter-inp" type="date" id="toDate" value="<?= date('Y-m-d') ?>">
      <button class="filter-btn" onclick="loadAll()">
        <svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
        Refresh
      </button>
      <div style="margin-left:auto;display:flex;gap:6px">
        <button class="filter-btn" style="background:var(--bg);color:var(--text2);border:1.5px solid var(--border)" onclick="quickRange('today')">Today</button>
        <button class="filter-btn" style="background:var(--bg);color:var(--text2);border:1.5px solid var(--border)" onclick="quickRange('week')">This Week</button>
        <button class="filter-btn" style="background:var(--bg);color:var(--text2);border:1.5px solid var(--border)" onclick="quickRange('month')">This Month</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid" id="kpiGrid">
      <div class="loading" style="grid-column:1/-1"><div class="spin"></div> Loading...</div>
    </div>

    <!-- Quick Actions -->
    <div class="qa-grid">
      <a href="pos.php" class="qa-btn">
        <div class="qa-ico" style="background:#eff6ff;color:#2563eb">
          <svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
        </div>
        <div><div class="qa-t">New Sale</div><div class="qa-s">Open POS</div></div>
      </a>
      <a href="products.php" class="qa-btn">
        <div class="qa-ico" style="background:#f0fdf4;color:#10b981">
          <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        </div>
        <div><div class="qa-t">Add Product</div><div class="qa-s">To catalog</div></div>
      </a>
      <a href="customers.php" class="qa-btn">
        <div class="qa-ico" style="background:#fdf4ff;color:#9333ea">
          <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <div><div class="qa-t">Add Customer</div><div class="qa-s">Register</div></div>
      </a>
      <a href="purchases.php" class="qa-btn">
        <div class="qa-ico" style="background:#fff7ed;color:#f59e0b">
          <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0023.44 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
        </div>
        <div><div class="qa-t">Purchase</div><div class="qa-s">Stock in</div></div>
      </a>
      <a href="reports.php" class="qa-btn">
        <div class="qa-ico" style="background:#fef2f2;color:#ef4444">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
        </div>
        <div><div class="qa-t">Reports</div><div class="qa-s">Analytics</div></div>
      </a>
      <a href="settings.php" class="qa-btn">
        <div class="qa-ico" style="background:#f8fafc;color:#64748b">
          <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87a.48.48 0 00.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 00-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
        </div>
        <div><div class="qa-t">Settings</div><div class="qa-s">Configure</div></div>
      </a>
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <div class="card">
        <div class="card-head">
          <div>
            <div class="card-title">Sales Overview</div>
            <div class="card-sub">Daily revenue for selected period</div>
          </div>
        </div>
        <canvas id="salesChart" height="200"></canvas>
      </div>
      <div class="card">
        <div class="card-head">
          <div>
            <div class="card-title">Payment Methods</div>
            <div class="card-sub">Revenue by method</div>
          </div>
        </div>
        <canvas id="payChart" height="200"></canvas>
      </div>
    </div>

    <!-- Bottom -->
    <div class="bottom-grid">
      <div class="card">
        <div class="card-head">
          <div class="card-title">Recent Orders</div>
          <a href="orders.php" style="font-size:12px;color:var(--accent);font-weight:500">View all →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Status</th><th>Time</th></tr></thead>
            <tbody id="recentOrders">
              <tr><td colspan="5"><div class="loading"><div class="spin"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-head">
          <div class="card-title">Low Stock Alert</div>
          <a href="inventory.php" style="font-size:12px;color:var(--accent);font-weight:500">View all →</a>
        </div>
        <div id="lowStockList"><div class="loading"><div class="spin"></div></div></div>
      </div>
    </div>

    <!-- Top Products + Top Customers -->
    <div class="bottom-grid" style="margin-top:16px">
      <div class="card">
        <div class="card-head">
          <div>
            <div class="card-title">Top Products</div>
            <div class="card-sub">Best sellers this period</div>
          </div>
          <a href="reports.php" style="font-size:12px;color:var(--accent);font-weight:500">Full report →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody id="topProductsBody"><tr><td colspan="4"><div class="loading"><div class="spin"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-head">
          <div>
            <div class="card-title">Top Customers</div>
            <div class="card-sub">Highest spend this period</div>
          </div>
          <a href="customers.php" style="font-size:12px;color:var(--accent);font-weight:500">View all →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Customer</th><th>Orders</th><th>Total Spent</th></tr></thead>
            <tbody id="topCustsBody"><tr><td colspan="3"><div class="loading"><div class="spin"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const now = new Date();
document.getElementById('topbar-date').textContent = now.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

let salesChart, payChart;

async function api(url){
  try{const r=await fetch(url);return await r.json();}
  catch(e){return{success:false};}
}

function quickRange(type){
  const now=new Date();
  const fmt=d=>d.toISOString().split('T')[0];
  if(type==='today'){
    document.getElementById('fromDate').value=fmt(now);
    document.getElementById('toDate').value=fmt(now);
  } else if(type==='week'){
    const start=new Date(now);start.setDate(now.getDate()-now.getDay());
    document.getElementById('fromDate').value=fmt(start);
    document.getElementById('toDate').value=fmt(now);
  } else {
    document.getElementById('fromDate').value=fmt(new Date(now.getFullYear(),now.getMonth(),1));
    document.getElementById('toDate').value=fmt(now);
  }
  loadAll();
}

async function loadAll(){
  const from=document.getElementById('fromDate').value;
  const to=document.getElementById('toDate').value;
  await Promise.all([loadKPIs(from,to),loadCharts(from,to),loadRecentOrders(),loadLowStock(),loadTopProducts(from,to),loadTopCustomers(from,to)]);
}

async function loadKPIs(from,to){
  const [stats,summary,profit]=await Promise.all([
    api('../routes/api.php?module=pos&action=dashboard_stats'),
    api(`../routes/api.php?module=reports&action=sales_summary&from=${from}&to=${to}`),
    api(`../routes/api.php?module=reports&action=profit_loss&from=${from}&to=${to}`)
  ]);
  const s=stats.data||{};
  const sm=summary.data?.summary||{};
  const p=profit.data||{};
  const fmt=n=>'৳'+parseFloat(n||0).toLocaleString('en-BD',{minimumFractionDigits:0,maximumFractionDigits:0});
  document.getElementById('kpiGrid').innerHTML=`
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Today's Sales</div>
        <div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
      </div>
      <div class="kpi-value">${fmt(s.today_sales)}</div>
      <div class="kpi-sub">${s.today_orders||0} orders today</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Monthly Revenue</div>
        <div class="kpi-icon" style="background:#f0fdf4;color:#10b981"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg></div>
      </div>
      <div class="kpi-value">${fmt(s.monthly_sales)}</div>
      <div class="kpi-sub">This month</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Net Profit</div>
        <div class="kpi-icon" style="background:#fdf4ff;color:#9333ea"><svg viewBox="0 0 24 24"><path d="M23 8c0 1.1-.9 2-2 2-.18 0-.35-.02-.51-.07l-3.56 3.55c.05.16.07.34.07.52 0 1.1-.9 2-2 2s-2-.9-2-2c0-.18.02-.36.07-.52l-2.55-2.55c-.16.05-.34.07-.52.07s-.36-.02-.52-.07l-4.55 4.56c.05.16.07.33.07.51 0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2c.18 0 .35.02.51.07l4.56-4.55C8.02 9.36 8 9.18 8 9c0-1.1.9-2 2-2s2 .9 2 2c0 .18-.02.36-.07.52l2.55 2.55c.16-.05.34-.07.52-.07s.36.02.52.07l3.55-3.56C19.02 8.35 19 8.18 19 8c0-1.1.9-2 2-2s2 .9 2 2z"/></svg></div>
      </div>
      <div class="kpi-value">${fmt(p.netProfit)}</div>
      <div class="kpi-sub">Selected period</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Low Stock</div>
        <div class="kpi-icon" style="background:#fff7ed;color:#f59e0b"><svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg></div>
      </div>
      <div class="kpi-value" style="color:var(--amber)">${s.low_stock||0}</div>
      <div class="kpi-sub">Items need restock</div>
    </div>`;
}

async function loadCharts(from,to){
  const [summary,payments]=await Promise.all([
    api(`../routes/api.php?module=reports&action=sales_summary&from=${from}&to=${to}`),
    api(`../routes/api.php?module=reports&action=payment_methods_report&from=${from}&to=${to}`)
  ]);
  const daily=summary.data?.daily||[];
  if(salesChart)salesChart.destroy();
  salesChart=new Chart(document.getElementById('salesChart').getContext('2d'),{
    type:'line',
    data:{
      labels:daily.map(d=>new Date(d.date).toLocaleDateString('en-US',{month:'short',day:'numeric'})),
      datasets:[{
        label:'Revenue',
        data:daily.map(d=>parseFloat(d.revenue)),
        borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,0.07)',
        borderWidth:2,fill:true,tension:0.4,pointRadius:3,pointBackgroundColor:'#2563eb',
      }]
    },
    options:{
      responsive:true,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false},ticks:{font:{family:'Inter',size:11},color:'#9ca3af'}},
        y:{grid:{color:'#f3f4f6'},ticks:{font:{family:'Inter',size:11},color:'#9ca3af',callback:v=>'৳'+v.toLocaleString()}}
      }
    }
  });
  const pmData=payments.data||[];
  if(payChart)payChart.destroy();
  if(pmData.length){
    payChart=new Chart(document.getElementById('payChart').getContext('2d'),{
      type:'doughnut',
      data:{
        labels:pmData.map(m=>m.name),
        datasets:[{data:pmData.map(m=>parseFloat(m.total)),backgroundColor:['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'],borderWidth:2,borderColor:'#fff',hoverOffset:4}]
      },
      options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{family:'Inter',size:11},padding:12,color:'#6b7280'}}}}
    });
  }
}

async function loadRecentOrders(){
  const res=await api('../routes/api.php?module=orders&action=list&per_page=8');
  const orders=res.data?.orders||[];
  const tbody=document.getElementById('recentOrders');
  if(!orders.length){tbody.innerHTML='<tr><td colspan="5" style="text-align:center;color:var(--text3);padding:20px;font-size:13px">No orders yet</td></tr>';return;}
  tbody.innerHTML=orders.map(o=>`
    <tr>
      <td><a href="orders.php" style="color:var(--accent);font-weight:600">${o.invoice_no}</a></td>
      <td>${o.customer_name||'Walk-in'}</td>
      <td style="font-weight:700;color:var(--text1)">৳${parseFloat(o.total).toFixed(2)}</td>
      <td><span class="badge badge-${o.status==='completed'?'green':o.status==='pending'?'amber':o.status==='processing'?'blue':'red'}">${o.status}</span></td>
      <td>${new Date(o.created_at).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'})}</td>
    </tr>`).join('');
}

async function loadLowStock(){
  const res=await api('../routes/api.php?module=products&action=low_stock');
  const items=res.data||[];
  const el=document.getElementById('lowStockList');
  // Also update notification bell badge
  const cEl=document.getElementById('dashNotifCount');
  const lEl=document.getElementById('dashNotifList');
  if(items.length){
    cEl.textContent=items.length>99?'99+':items.length;
    cEl.style.display='flex';
    if(lEl)lEl.innerHTML=items.slice(0,8).map(i=>
      `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #f3f4f6;font-size:13px"><div style="font-weight:500;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px">${i.name}</div><div style="font-weight:700;color:#ef4444;font-size:12px;margin-left:8px">${parseFloat(i.stock).toFixed(0)} left</div></div>`
    ).join('')+(items.length>8?`<div style="padding:8px 14px;font-size:12px;color:#9ca3af;text-align:center">+${items.length-8} more</div>`:'');
  } else {
    if(lEl)lEl.innerHTML='<div style="padding:20px;text-align:center;color:#10b981;font-size:13px">✓ All stock levels healthy</div>';
  }
  if(!items.length){el.innerHTML='<div style="text-align:center;padding:20px;color:var(--text3);font-size:13px">✓ All stock levels healthy</div>';return;}
  el.innerHTML=items.slice(0,8).map(i=>`
    <div class="ls-item">
      <div><div class="ls-name">${i.name}</div><div class="ls-cat">${i.category||'—'}</div></div>
      <div class="ls-qty">${parseFloat(i.stock).toFixed(0)} left</div>
    </div>`).join('');
}

async function loadTopProducts(from,to){
  const res=await api(`../routes/api.php?module=reports&action=top_products&from=${from}&to=${to}&limit=6`);
  const rows=res.data||[];
  const fmt=v=>'৳'+parseFloat(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('topProductsBody').innerHTML=rows.length
    ?rows.map((r,i)=>`<tr>
        <td style="font-weight:700;color:var(--text3);font-size:12px">${i+1}</td>
        <td style="font-weight:600;color:var(--text1)">${r.name}</td>
        <td>${parseFloat(r.qty_sold).toFixed(0)}</td>
        <td style="font-weight:700">${fmt(r.revenue)}</td>
      </tr>`).join('')
    :`<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text3);font-size:13px">No sales data in this period</td></tr>`;
}

async function loadTopCustomers(from,to){
  const res=await api(`../routes/api.php?module=reports&action=customer_report&from=${from}&to=${to}`);
  const rows=(res.data||[]).slice(0,6);
  const fmt=v=>'৳'+parseFloat(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('topCustsBody').innerHTML=rows.length
    ?rows.map(r=>`<tr>
        <td style="font-weight:600;color:var(--text1)">${r.name}</td>
        <td>${r.orders}</td>
        <td style="font-weight:700">${fmt(r.spent)}</td>
      </tr>`).join('')
    :`<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text3);font-size:13px">No customer data in this period</td></tr>`;
}

// Notification bell toggle
document.getElementById('dashBellBtn')?.addEventListener('click', function(e){
  e.stopPropagation();
  const p=document.getElementById('notifPanel');
  p.style.display=p.style.display==='block'?'none':'block';
});
document.addEventListener('click', function(e){
  if(!document.getElementById('notifWrap')?.contains(e.target))
    document.getElementById('notifPanel').style.display='none';
});

loadAll();
</script>
</body>
</html>

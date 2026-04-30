<?php
$titles = [
  'dashboard.php' => 'Dashboard',
  'products.php'  => 'Products',
  'categories.php'=> 'Categories',
  'orders.php'    => 'Orders',
  'returns.php'   => 'Returns & Refunds',
  'customers.php' => 'Customers',
  'inventory.php' => 'Inventory',
  'purchases.php' => 'Purchases',
  'suppliers.php' => 'Suppliers',
  'reports.php'   => 'Reports',
  'expenses.php'  => 'Expenses',
  'employees.php' => 'Employees',
  'settings.php'  => 'Settings',
  'pos.php'       => 'POS Terminal',
];
$pageTitle = $titles[basename($_SERVER['PHP_SELF'])] ?? 'NexaPOS';
?>
<header class="topbar">
  <button class="hamburger" onclick="sbOpen()">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
  </button>
  <h1><?= $pageTitle ?></h1>

  <!-- Notification Bell -->
  <div class="notif-wrap" id="notifWrap">
    <button class="notif-btn" id="notifBtn" title="Stock Alerts" onclick="document.getElementById('notifPanel').classList.toggle('open')">
      <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
      <span class="notif-count" id="notifCount" style="display:none">0</span>
    </button>
    <div class="notif-panel" id="notifPanel">
      <div class="notif-head">
        <strong>Low Stock Alerts</strong>
        <a href="inventory.php">View all →</a>
      </div>
      <div class="notif-list" id="notifList">
        <div style="padding:16px;text-align:center;color:#9ca3af;font-size:13px">Loading…</div>
      </div>
    </div>
  </div>

  <a href="pos.php" class="topbar-btn primary">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
    Open POS
  </a>
</header>
<script>
(function(){
  document.addEventListener('DOMContentLoaded', async function(){
    try {
      const r   = await fetch('../routes/api.php?module=products&action=low_stock');
      const res = await r.json();
      const items = res.data || [];
      const cEl = document.getElementById('notifCount');
      const lEl = document.getElementById('notifList');
      if (items.length) {
        cEl.textContent = items.length > 99 ? '99+' : items.length;
        cEl.style.display = 'flex';
        lEl.innerHTML = items.slice(0, 8).map(i =>
          `<div class="notif-item"><div class="notif-item-name">${i.name}</div><div class="notif-item-qty">${parseFloat(i.stock).toFixed(0)} left</div></div>`
        ).join('') + (items.length > 8 ? `<div style="padding:8px 14px;font-size:12px;color:#9ca3af;text-align:center">+${items.length-8} more items</div>` : '');
      } else {
        lEl.innerHTML = '<div class="notif-empty">✓ All stock levels healthy</div>';
      }
    } catch(e) {}
    document.addEventListener('click', function(e){
      if (!document.getElementById('notifWrap')?.contains(e.target))
        document.getElementById('notifPanel')?.classList.remove('open');
    });
  });
})();
</script>

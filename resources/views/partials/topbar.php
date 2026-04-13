<?php
$titles = [
  'dashboard.php' => 'Dashboard',
  'products.php'  => 'Products',
  'categories.php'=> 'Categories',
  'orders.php'    => 'Orders',
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
  <a href="pos.php" class="topbar-btn primary">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
    Open POS
  </a>
</header>

<?php
/**
 * Shared Sidebar — included by all admin pages
 * Requires: $user (from Auth::user())
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function sbActive(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<style>
/* ── Universal Sidebar ── */
#sidebar{
  width:230px;flex-shrink:0;background:#111827;
  display:flex;flex-direction:column;
  height:100vh;position:fixed;left:0;top:0;
  z-index:200;overflow:hidden;
  transition:transform .25s ease;
}
.sb-brand{
  display:flex;align-items:center;gap:10px;
  padding:18px 16px 16px;
  border-bottom:1px solid rgba(255,255,255,.07);flex-shrink:0;
}
.sb-brand-ico{
  width:32px;height:32px;background:#2563eb;border-radius:8px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.sb-brand-ico svg{width:17px;height:17px;fill:#fff}
.sb-brand-name{font-size:15px;font-weight:700;color:#fff;letter-spacing:-.3px}
.sb-brand-ver{font-size:10px;color:rgba(255,255,255,.3);margin-top:1px}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px;scrollbar-width:none}
.sb-nav::-webkit-scrollbar{display:none}
.sb-section{
  font-size:10px;font-weight:600;text-transform:uppercase;
  letter-spacing:.07em;color:rgba(255,255,255,.28);
  padding:12px 10px 5px;margin-top:2px;
}
.sb-item{
  display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:7px;
  color:rgba(255,255,255,.58);font-size:13px;font-weight:500;
  text-decoration:none;cursor:pointer;
  transition:background .15s,color .15s;margin-bottom:1px;
}
.sb-item:hover{background:rgba(255,255,255,.07);color:rgba(255,255,255,.9)}
.sb-item.active{background:rgba(37,99,235,.3);color:#fff;font-weight:600}
.sb-item.active .sb-icon{opacity:1}
.sb-icon{width:16px;height:16px;fill:currentColor;flex-shrink:0;opacity:.7}
.sb-item.active .sb-icon,.sb-item:hover .sb-icon{opacity:1}
.sb-badge{margin-left:auto;background:#2563eb;color:#fff;font-size:10px;font-weight:700;padding:0 6px;border-radius:8px;line-height:16px}
.sb-foot{
  padding:10px 8px;border-top:1px solid rgba(255,255,255,.07);
  flex-shrink:0;
}
.sb-user{
  display:flex;align-items:center;gap:9px;padding:9px 10px;
  border-radius:7px;text-decoration:none;
  transition:background .15s;cursor:pointer;
}
.sb-user:hover{background:rgba(255,255,255,.07)}
.sb-ava{
  width:30px;height:30px;border-radius:50%;background:#2563eb;
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;color:#fff;flex-shrink:0;
}
.sb-uinfo{flex:1;min-width:0}
.sb-uname{font-size:12px;font-weight:600;color:#f3f4f6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-urole{font-size:10px;color:rgba(255,255,255,.38);text-transform:capitalize}
.sb-logout{color:rgba(255,255,255,.35);display:flex;align-items:center;flex-shrink:0}
.sb-logout svg{width:15px;height:15px;fill:currentColor}
.sb-user:hover .sb-logout{color:rgba(255,255,255,.7)}

/* Hamburger for mobile */
#sbToggle{
  display:none;position:fixed;top:12px;left:12px;z-index:300;
  width:36px;height:36px;background:#111827;border:none;border-radius:8px;
  cursor:pointer;align-items:center;justify-content:center;color:#fff;
}
#sbToggle svg{width:18px;height:18px;fill:currentColor}
#sbOverlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199}

/* Main offset */
.sb-main{margin-left:230px;flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden}

@media(max-width:900px){
  #sidebar{transform:translateX(-100%)}
  #sidebar.open{transform:translateX(0)}
  .sb-main{margin-left:0}
  #sbToggle{display:flex}
  #sbOverlay.show{display:block}
}
</style>

<button id="sbToggle" onclick="sbOpen()" aria-label="Menu">
  <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
</button>
<div id="sbOverlay" onclick="sbClose()"></div>

<nav id="sidebar">
  <div class="sb-brand">
    <div class="sb-brand-ico">
      <svg viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
    </div>
    <div>
      <div class="sb-brand-name">NexaPOS</div>
      <div class="sb-brand-ver">v2.0</div>
    </div>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Main</div>
    <a href="/nexapos/public/dashboard.php" class="sb-item<?= sbActive('dashboard', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
      Dashboard
    </a>
    <a href="/nexapos/public/pos.php" class="sb-item<?= sbActive('pos', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
      POS Terminal
    </a>

    <div class="sb-section">Catalog</div>
    <a href="/nexapos/public/products.php" class="sb-item<?= sbActive('products', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg>
      Products
    </a>
    <a href="/nexapos/public/categories.php" class="sb-item<?= sbActive('categories', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5S15.01 22 17.5 22s4.5-2.01 4.5-4.5S19.99 13 17.5 13zm0 7c-1.38 0-2.5-1.12-2.5-2.5S16.12 15 17.5 15s2.5 1.12 2.5 2.5S18.88 20 17.5 20zM3 21.5h8v-8H3v8zm2-6h4v4H5v-4z"/></svg>
      Categories
    </a>

    <div class="sb-section">Sales</div>
    <a href="/nexapos/public/orders.php" class="sb-item<?= sbActive('orders', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
      Orders
    </a>
    <a href="/nexapos/public/customers.php" class="sb-item<?= sbActive('customers', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      Customers
    </a>

    <div class="sb-section">Inventory</div>
    <a href="/nexapos/public/inventory.php" class="sb-item<?= sbActive('inventory', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
      Stock
    </a>
    <a href="/nexapos/public/purchases.php" class="sb-item<?= sbActive('purchases', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0023.44 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
      Purchases
    </a>

    <div class="sb-section">Finance</div>
    <a href="/nexapos/public/reports.php" class="sb-item<?= sbActive('reports', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
      Reports
    </a>
    <a href="/nexapos/public/expenses.php" class="sb-item<?= sbActive('expenses', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
      Expenses
    </a>

    <div class="sb-section">Settings</div>
    <a href="/nexapos/public/employees.php" class="sb-item<?= sbActive('employees', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
      Employees
    </a>
    <a href="/nexapos/public/settings.php" class="sb-item<?= sbActive('settings', $currentPage) ?>">
      <svg class="sb-icon" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87a.48.48 0 00.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 00-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
      Settings
    </a>
  </div>

  <div class="sb-foot">
    <a href="/nexapos/public/logout.php" class="sb-user">
      <div class="sb-ava"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
      <div class="sb-uinfo">
        <div class="sb-uname"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></div>
        <div class="sb-urole"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role'] ?? 'admin'))) ?></div>
      </div>
      <div class="sb-logout">
        <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
      </div>
    </a>
  </div>
</nav>

<script>
function sbOpen(){document.getElementById('sidebar').classList.add('open');document.getElementById('sbOverlay').classList.add('show');}
function sbClose(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sbOverlay').classList.remove('show');}
</script>

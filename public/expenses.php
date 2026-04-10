<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= ucfirst('expenses') ?> — NexaPOS</title>
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
      <h2 class="page-title"><?= ucfirst('expenses') ?></h2>
      <p class="page-sub">Coming soon — full implementation in next update</p>
    </div>
  </div>
  <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:60px;text-align:center;color:var(--text3)">
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;margin-bottom:16px;opacity:.3"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    <div style="font-size:16px;font-weight:600;color:var(--text2);margin-bottom:8px"><?= ucfirst('expenses') ?> module loading...</div>
    <div style="font-size:13px">This page will be fully built in the next step.</div>
  </div>
</div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>

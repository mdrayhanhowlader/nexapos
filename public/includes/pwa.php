<?php
// PWA meta tags + manifest — include inside <head> on every page
$base = '/nexapos/public';
?>
<link rel="manifest" href="<?= $base ?>/manifest.json">
<meta name="theme-color" content="#2563eb">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="NexaPOS">
<link rel="apple-touch-icon" href="<?= $base ?>/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $base ?>/assets/icons/favicon-32.png">
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/nexapos/public/sw.js', { scope: '/nexapos/public/' })
      .catch(() => {});
  });
}
</script>

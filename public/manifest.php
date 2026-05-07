<?php
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/manifest+json');
header('Cache-Control: public, max-age=3600');

$base = rtrim(app_url('public'), '/');
$name = DB::fetch("SELECT value FROM settings WHERE `key`='business_name'")['value'] ?? 'NexaPOS';

echo json_encode([
    'name'       => $name,
    'short_name' => 'NexaPOS',
    'description'=> 'Point of Sale System',
    'start_url'  => $base . '/dashboard.php',
    'scope'      => $base . '/',
    'display'    => 'standalone',
    'background_color' => '#111827',
    'theme_color'=> '#2563eb',
    'icons' => [
        ['src' => $base . '/assets/icons/favicon-32.png',      'sizes' => '32x32',   'type' => 'image/png'],
        ['src' => $base . '/assets/icons/apple-touch-icon.png','sizes' => '180x180', 'type' => 'image/png'],
        ['src' => $base . '/assets/icons/icon-192.png',        'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ['src' => $base . '/assets/icons/icon-512.png',        'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
    'shortcuts' => [
        ['name' => 'POS Terminal', 'url' => $base . '/pos.php',       'icons' => [['src' => $base . '/assets/icons/icon-192.png', 'sizes' => '192x192']]],
        ['name' => 'Dashboard',    'url' => $base . '/dashboard.php', 'icons' => [['src' => $base . '/assets/icons/icon-192.png', 'sizes' => '192x192']]],
        ['name' => 'Orders',       'url' => $base . '/orders.php',    'icons' => [['src' => $base . '/assets/icons/icon-192.png', 'sizes' => '192x192']]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
header('Content-Type: application/json; charset=utf-8');

$module = $_REQUEST['module'] ?? '';
$action = $_REQUEST['action'] ?? '';

$allowed = ['pos','products','orders','customers','reports','settings','employees','inventory','purchases','expenses','suppliers','returns','mfs'];
if (!in_array($module, $allowed)) {
    Response::error('Invalid module', 404);
}

$file = dirname(__DIR__) . "/app/Controllers/{$module}.php";
if (!file_exists($file)) {
    Response::error("Module not found: {$module}", 404);
}

require_once $file;

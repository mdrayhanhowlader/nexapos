<?php

declare(strict_types=1);

define('ROOT_PATH',    __DIR__);
define('APP_PATH',     ROOT_PATH . '/app');
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH',    ROOT_PATH . '/resources/views');

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [
        APP_PATH . '/Helpers/',
        APP_PATH . '/Controllers/',
        APP_PATH . '/Models/',
        APP_PATH . '/Middleware/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Load core helpers first (order matters) ───────────────────────────────────
require_once APP_PATH . '/Helpers/Config.php';
require_once APP_PATH . '/Helpers/Logger.php';
require_once APP_PATH . '/Helpers/Response.php';
require_once APP_PATH . '/Helpers/DB.php';
require_once APP_PATH . '/Helpers/Auth.php';
require_once APP_PATH . '/Helpers/Validator.php';
require_once APP_PATH . '/Helpers/Helpers.php';

// ── Timezone & error handling ─────────────────────────────────────────────────
date_default_timezone_set(Config::app('timezone', 'Asia/Dhaka'));

if (Config::app('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Global error & exception handlers ────────────────────────────────────────
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    Logger::error("PHP Error [{$errno}]: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
    ]);
    return true;
});

set_exception_handler(function (Throwable $e): void {
    Logger::exception($e, 'Uncaught exception');
    if (is_ajax()) {
        Response::error(
            Config::app('debug')
                ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'An unexpected error occurred. Please try again.',
            500
        );
    }
    if (Config::app('debug')) {
        echo '<pre style="background:#1a1a2e;color:#ff4d6a;padding:20px;font-family:monospace">';
        echo '<strong>Exception:</strong> ' . htmlspecialchars($e->getMessage()) . "\n";
        echo '<strong>File:</strong> ' . $e->getFile() . ' (line ' . $e->getLine() . ")\n";
        echo '<strong>Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        echo 'Something went wrong. Please try again.';
    }
    exit;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        Logger::critical('Fatal PHP error', [
            'message' => $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line'],
        ]);
    }
});

// ── Security headers ──────────────────────────────────────────────────────────
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ── Start session ─────────────────────────────────────────────────────────────
Auth::init();

// ── Auto-migrations (create new tables / add columns if not exist) ───────────
try {
    DB::query("CREATE TABLE IF NOT EXISTS product_addons (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id     INT UNSIGNED NOT NULL,
        addon_id       INT UNSIGNED NOT NULL,
        is_required    TINYINT(1)   NOT NULL DEFAULT 0,
        sort_order     INT          NOT NULL DEFAULT 0,
        created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_product_addon (product_id, addon_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// Add PIN column to users if missing
try {
    DB::query("ALTER TABLE users ADD COLUMN pin VARCHAR(6) NULL DEFAULT NULL UNIQUE AFTER password");
} catch (Throwable $e) {
    // Column already exists — ignore
}

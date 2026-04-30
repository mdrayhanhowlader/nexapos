<?php

function config(string $file, string $key = null, mixed $default = null): mixed
{
    return Config::get($file, $key, $default);
}

function get_setting(string $key, mixed $default = null): mixed
{
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $row = DB::fetch("SELECT value FROM settings WHERE `key` = ?", [$key]);
        $cache[$key] = $row ? $row['value'] : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function set_setting(string $key, mixed $value): void
{
    DB::query(
        "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = ?",
        [$key, $value, $value]
    );
}

function generate_invoice(): string
{
    $prefix = get_setting('invoice_prefix', 'INV-');
    $start  = (int) get_setting('invoice_start', 1000);
    $last   = DB::fetch(
        "SELECT MAX(CAST(SUBSTRING(invoice_no, LENGTH(?) + 1) AS UNSIGNED)) AS n
         FROM orders WHERE invoice_no LIKE ?",
        [$prefix, $prefix . '%']
    );
    $next = max($start, ($last['n'] ?? $start - 1) + 1);
    return $prefix . $next;
}

function generate_ref(string $prefix = 'REF'): string
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Generate a valid EAN-13 barcode for internal store use.
 * Prefix 200–209 is reserved by GS1 for in-store / variable-measure items.
 */
function generate_ean13(): string
{
    // 12 digits: prefix 200 + 9 random digits
    $digits = '200' . str_pad((string)rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    // Calculate check digit
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$digits[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    $check = (10 - ($sum % 10)) % 10;
    return $digits . $check;
}

function customer_code(): string
{
    return 'CUS-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function paginate(int $total, int $page, int $perPage = 20): array
{
    $totalPages = (int) ceil($total / max(1, $perPage));
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $page,
        'total_pages'  => $totalPages,
        'offset'       => ($page - 1) * $perPage,
        'has_prev'     => $page > 1,
        'has_next'     => $page < $totalPages,
    ];
}

function upload_file(array $file, string $dir = 'products'): ?string
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $ext;
    $dest     = dirname(__DIR__, 2) . "/public/uploads/{$dir}/{$filename}";

    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    return move_uploaded_file($file['tmp_name'], $dest)
        ? "public/uploads/{$dir}/{$filename}"
        : null;
}

function sanitize(string $val): string
{
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function money(float $amount, string $symbol = null): string
{
    $symbol = $symbol ?? get_setting('currency_symbol', '৳');
    return $symbol . number_format($amount, 2);
}

function format_date(string $date, string $format = 'd M Y, h:i A'): string
{
    return date($format, strtotime($date));
}

function barcode_exists(string $barcode, ?int $excludeId = null): bool
{
    $sql    = "SELECT id FROM products WHERE barcode = ?";
    $params = [$barcode];
    if ($excludeId) {
        $sql    .= " AND id != ?";
        $params[] = $excludeId;
    }
    return (bool) DB::fetch($sql, $params);
}

function log_activity(string $action, string $module, string $desc = '', ?int $refId = null): void
{
    try {
        DB::insert('activity_logs', [
            'user_id'      => Auth::id(),
            'action'       => $action,
            'module'       => $module,
            'description'  => $desc,
            'reference_id' => $refId,
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
        ]);
    } catch (Exception $e) {}
}

function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function app_url(string $path = ''): string
{
    // Auto-detect base URL from current request so ngrok / any domain works
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // ngrok always sends https via its own header
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        $host    = $_SERVER['HTTP_HOST'];
        $script  = $_SERVER['SCRIPT_NAME'] ?? '';
        // Walk up from current file to find the nexapos root segment in the URL
        // e.g. /nexapos/public/login.php → base = /nexapos
        $parts   = explode('/', trim($script, '/'));
        $base    = '';
        foreach ($parts as $part) {
            if (strtolower($part) === 'public') break;
            $base .= '/' . $part;
        }
        return $scheme . '://' . $host . rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    return rtrim(Config::app('url'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return app_url('public/assets/' . ltrim($path, '/'));
}

<?php
/**
 * NexaPOS Auto-Installer
 * Visit: yourdomain.com/install/install.php
 * Self-deletes after successful installation.
 */
define('INSTALLER_VERSION', '2.0');
define('ROOT', dirname(__DIR__));
define('SCHEMA_FILE', ROOT . '/database/schema.sql');
define('DB_CONFIG',   ROOT . '/config/database.php');
define('APP_CONFIG',  ROOT . '/config/app.php');

// ── Detect base URL ───────────────────────────────────────────────────────────
function installer_base_url(): string {
    $s = $_SERVER;
    $scheme = (!empty($s['HTTPS']) && $s['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($s['HTTP_X_FORWARDED_PROTO'])) $scheme = $s['HTTP_X_FORWARDED_PROTO'];
    $host   = $s['HTTP_HOST'] ?? 'localhost';
    $script = $s['SCRIPT_NAME'] ?? '/install/install.php';
    // Walk up until we find 'install' segment
    $parts = explode('/', trim($script, '/'));
    $base  = '';
    foreach ($parts as $part) {
        if (strtolower($part) === 'install') break;
        $base .= '/' . $part;
    }
    return $scheme . '://' . $host . rtrim($base, '/');
}

$baseUrl   = installer_base_url();
$step      = (int)($_GET['step'] ?? 1);
$error     = '';
$success   = '';
$formData  = [];

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'db_host'   => trim($_POST['db_host']   ?? 'localhost'),
        'db_port'   => trim($_POST['db_port']   ?? '3306'),
        'db_name'   => trim($_POST['db_name']   ?? 'nexapos_db'),
        'db_user'   => trim($_POST['db_user']   ?? ''),
        'db_pass'   => $_POST['db_pass']        ?? '',
        'site_url'  => rtrim(trim($_POST['site_url'] ?? $baseUrl), '/'),
        'biz_name'  => trim($_POST['biz_name']  ?? 'My Business'),
        'admin_email'=> trim($_POST['admin_email'] ?? ''),
        'admin_pass' => $_POST['admin_pass']    ?? '',
        'timezone'  => trim($_POST['timezone']  ?? 'Asia/Dhaka'),
    ];

    // Basic validation
    if (!$formData['db_user'])     $error = 'Database username is required.';
    elseif (!$formData['admin_email']) $error = 'Admin email is required.';
    elseif (!filter_var($formData['admin_email'], FILTER_VALIDATE_EMAIL)) $error = 'Invalid admin email address.';
    elseif (strlen($formData['admin_pass']) < 6) $error = 'Admin password must be at least 6 characters.';
    elseif (!$formData['biz_name']) $error = 'Business name is required.';

    if (!$error) {
        // ── Test DB connection ────────────────────────────────────────────────
        try {
            $dsn = "mysql:host={$formData['db_host']};port={$formData['db_port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $formData['db_user'], $formData['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (PDOException $e) {
            $error = 'Cannot connect to MySQL: ' . $e->getMessage();
        }
    }

    if (!$error) {
        // ── Create database if missing ────────────────────────────────────────
        try {
            $dbName = $formData['db_name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
        } catch (PDOException $e) {
            $error = 'Cannot create database: ' . $e->getMessage();
        }
    }

    if (!$error) {
        // ── Run schema SQL ────────────────────────────────────────────────────
        if (!file_exists(SCHEMA_FILE)) {
            $error = 'Schema file not found: database/schema.sql';
        } else {
            try {
                $sql = file_get_contents(SCHEMA_FILE);
                // Split on semicolons but ignore comment lines
                $statements = array_filter(
                    array_map('trim', explode(";\n", $sql)),
                    fn($s) => $s !== '' && !str_starts_with(ltrim($s), '--')
                );
                foreach ($statements as $stmt) {
                    if (trim($stmt)) $pdo->exec($stmt);
                }
            } catch (PDOException $e) {
                // Ignore duplicate key / already-exists errors
                if (!str_contains($e->getMessage(), 'Duplicate entry') &&
                    !str_contains($e->getMessage(), 'already exists')) {
                    $error = 'Schema error: ' . $e->getMessage();
                }
            }
        }
    }

    if (!$error) {
        // ── Update business_name in settings ─────────────────────────────────
        try {
            $stmt = $pdo->prepare("INSERT INTO settings (`key`,value,`group`) VALUES ('business_name',?,'general')
                                   ON DUPLICATE KEY UPDATE value=?");
            $stmt->execute([$formData['biz_name'], $formData['biz_name']]);

            $stmt = $pdo->prepare("INSERT INTO settings (`key`,value,`group`) VALUES ('timezone',?,'general')
                                   ON DUPLICATE KEY UPDATE value=?");
            $stmt->execute([$formData['timezone'], $formData['timezone']]);
        } catch (PDOException $e) { /* non-fatal */ }
    }

    if (!$error) {
        // ── Insert admin user ─────────────────────────────────────────────────
        try {
            $hash = password_hash($formData['admin_pass'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (role_id, name, email, password, status)
                 VALUES (1, 'Super Admin', ?, ?, 'active')
                 ON DUPLICATE KEY UPDATE password=?, role_id=1, status='active'"
            );
            $stmt->execute([$formData['admin_email'], $hash, $hash]);
        } catch (PDOException $e) {
            $error = 'Cannot create admin user: ' . $e->getMessage();
        }
    }

    if (!$error) {
        // ── Write config/database.php ─────────────────────────────────────────
        $dbConfig = <<<PHP
<?php
return [
    'host'    => '{$formData['db_host']}',
    'port'    => '{$formData['db_port']}',
    'name'    => '{$formData['db_name']}',
    'user'    => '{$formData['db_user']}',
    'pass'    => '{$formData['db_pass']}',
    'charset' => 'utf8mb4',
];
PHP;
        if (!file_put_contents(DB_CONFIG, $dbConfig)) {
            $error = 'Cannot write config/database.php — check folder permissions.';
        }
    }

    if (!$error) {
        // ── Write config/app.php ──────────────────────────────────────────────
        $siteUrl  = $formData['site_url'];
        $bizName  = addslashes($formData['biz_name']);
        $timezone = $formData['timezone'];
        $appConfig = <<<PHP
<?php
return [
    'name'     => '{$bizName}',
    'version'  => '2.0.0',
    'url'      => '{$siteUrl}',
    'env'      => 'production',
    'debug'    => false,
    'timezone' => '{$timezone}',
    'locale'   => 'en',
];
PHP;
        if (!file_put_contents(APP_CONFIG, $appConfig)) {
            $error = 'Cannot write config/app.php — check folder permissions.';
        }
    }

    if (!$error) {
        // ── Create required directories ───────────────────────────────────────
        $dirs = [
            ROOT . '/public/uploads/products',
            ROOT . '/public/uploads/qr',
            ROOT . '/public/uploads/logos',
            ROOT . '/public/uploads/avatars',
            ROOT . '/storage/logs',
            ROOT . '/storage/cache',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) @mkdir($d, 0755, true);
        }
        // Self-delete this installer
        @unlink(__FILE__);

        // Redirect to login
        header('Location: ' . $formData['site_url'] . '/public/login.php?installed=1');
        exit;
    }
}

// ── Detect if already installed ───────────────────────────────────────────────
$alreadyInstalled = false;
if (file_exists(DB_CONFIG)) {
    try {
        $cfg = require DB_CONFIG;
        $testDsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
        $testPdo = new PDO($testDsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_TIMEOUT => 3]);
        $cnt = $testPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($cnt > 0) $alreadyInstalled = true;
    } catch (Throwable) {}
}

$defaults = array_merge([
    'db_host'    => 'localhost',
    'db_port'    => '3306',
    'db_name'    => 'nexapos_db',
    'db_user'    => '',
    'db_pass'    => '',
    'site_url'   => $baseUrl,
    'biz_name'   => 'My Business',
    'admin_email'=> '',
    'admin_pass' => '',
    'timezone'   => 'Asia/Dhaka',
], $formData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NexaPOS Installer</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:36px 40px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.4)}
h1{font-size:22px;font-weight:700;color:#f8fafc;margin-bottom:4px;display:flex;align-items:center;gap:10px}
h1 span{background:#2563eb;color:#fff;font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;vertical-align:middle}
.sub{font-size:13px;color:#94a3b8;margin-bottom:28px}
.section-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin:20px 0 10px;padding-top:16px;border-top:1px solid #334155}
.section-title:first-of-type{margin-top:0;padding-top:0;border-top:none}
label{display:block;font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:5px}
input,select{width:100%;padding:9px 12px;border:1px solid #334155;border-radius:8px;background:#0f172a;color:#f1f5f9;font-size:14px;outline:none;transition:border-color .15s}
input:focus,select:focus{border-color:#2563eb}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
.alert-error{background:#450a0a;border:1px solid #7f1d1d;color:#fca5a5}
.alert-warn{background:#422006;border:1px solid #78350f;color:#fdba74;font-size:12px}
.btn{width:100%;padding:11px;border:none;border-radius:8px;background:#2563eb;color:#fff;font-size:14px;font-weight:600;cursor:pointer;margin-top:20px;transition:background .15s}
.btn:hover{background:#1d4ed8}
.check{display:flex;align-items:flex-start;gap:8px;margin-top:12px}
.check svg{width:16px;height:16px;flex-shrink:0;margin-top:1px}
.ok{color:#22c55e}.bad{color:#ef4444}.warn{color:#f59e0b}
.check span{font-size:12px;color:#94a3b8}
.installed-box{text-align:center;padding:20px 0}
.installed-box h2{font-size:18px;color:#22c55e;margin-bottom:8px}
.installed-box p{font-size:13px;color:#94a3b8}
.installed-box a{color:#60a5fa;text-decoration:none}
.fg{margin-bottom:14px}
</style>
</head>
<body>
<div class="card">
  <h1>
    <svg viewBox="0 0 24 24" fill="#2563eb" style="width:24px;height:24px"><path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-1 11H5V10h14v8zM7 15h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2zM3 5h18V3H3z"/></svg>
    NexaPOS <span>Installer v<?= INSTALLER_VERSION ?></span>
  </h1>
  <p class="sub">Set up your POS system in under a minute</p>

  <?php if ($alreadyInstalled): ?>
  <div class="installed-box">
    <svg viewBox="0 0 24 24" fill="#22c55e" style="width:48px;height:48px;margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    <h2>Already Installed!</h2>
    <p>NexaPOS is already set up and running.<br>
    <a href="../public/login.php">Go to Login →</a></p>
    <p style="margin-top:12px;font-size:11px;color:#475569">Want to reinstall? Delete <code>config/database.php</code> first.</p>
  </div>
  <?php else: ?>

  <!-- Pre-flight checks -->
  <div class="section-title">Pre-flight checks</div>
  <?php
  $checks = [
      ['PHP ≥ 7.4',         PHP_VERSION_ID >= 70400,    PHP_VERSION],
      ['PDO MySQL',         extension_loaded('pdo_mysql'), null],
      ['OpenSSL',           extension_loaded('openssl'),   null],
      ['config/ writable',  is_writable(ROOT . '/config'), null],
      ['database/schema.sql exists', file_exists(SCHEMA_FILE), null],
  ];
  foreach ($checks as [$label, $ok, $detail]):
      $cls = $ok ? 'ok' : 'bad';
      $ico = $ok
          ? '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>'
          : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
  ?>
  <div class="check">
    <svg viewBox="0 0 24 24" fill="currentColor" class="<?= $cls ?>"><?= $ico ?></svg>
    <span><?= $label ?><?= $detail ? " ($detail)" : '' ?></span>
  </div>
  <?php endforeach; ?>

  <?php if ($error): ?>
  <div class="alert alert-error" style="margin-top:16px"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="section-title">Database</div>
    <div class="row2">
      <div class="fg">
        <label>Host</label>
        <input name="db_host" value="<?= htmlspecialchars($defaults['db_host']) ?>" placeholder="localhost">
      </div>
      <div class="fg">
        <label>Port</label>
        <input name="db_port" value="<?= htmlspecialchars($defaults['db_port']) ?>" placeholder="3306">
      </div>
    </div>
    <div class="fg">
      <label>Database Name</label>
      <input name="db_name" value="<?= htmlspecialchars($defaults['db_name']) ?>" placeholder="nexapos_db">
    </div>
    <div class="row2">
      <div class="fg">
        <label>Username</label>
        <input name="db_user" value="<?= htmlspecialchars($defaults['db_user']) ?>" placeholder="root" required>
      </div>
      <div class="fg">
        <label>Password</label>
        <input type="password" name="db_pass" placeholder="(leave blank if none)">
      </div>
    </div>

    <div class="section-title">Site</div>
    <div class="fg">
      <label>Site URL <small style="color:#475569">(no trailing slash)</small></label>
      <input name="site_url" value="<?= htmlspecialchars($defaults['site_url']) ?>" placeholder="https://yourdomain.com">
    </div>
    <div class="row2">
      <div class="fg">
        <label>Business Name</label>
        <input name="biz_name" value="<?= htmlspecialchars($defaults['biz_name']) ?>" required>
      </div>
      <div class="fg">
        <label>Timezone</label>
        <select name="timezone">
          <?php
          $tzs = ['Asia/Dhaka','Asia/Kolkata','Asia/Karachi','Asia/Colombo','Asia/Singapore',
                  'Asia/Dubai','Asia/Bangkok','Asia/Tokyo','Europe/London','Europe/Paris',
                  'America/New_York','America/Chicago','America/Los_Angeles','UTC'];
          foreach ($tzs as $tz):
          ?>
          <option value="<?= $tz ?>" <?= $defaults['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="section-title">Admin Account</div>
    <div class="fg">
      <label>Email</label>
      <input type="email" name="admin_email" value="<?= htmlspecialchars($defaults['admin_email']) ?>" placeholder="admin@example.com" required>
    </div>
    <div class="fg">
      <label>Password <small style="color:#475569">(min 6 chars)</small></label>
      <input type="password" name="admin_pass" placeholder="••••••••" minlength="6" required>
    </div>

    <div class="alert alert-warn" style="margin-top:16px">
      This will create all database tables and seed initial data. Existing tables will not be dropped.
    </div>

    <button type="submit" class="btn">Install NexaPOS →</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

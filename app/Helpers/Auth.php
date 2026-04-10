<?php

class Auth
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = Config::auth('session_lifetime', 28800);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function attempt(string $email, string $password): array
    {
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $maxTries = Config::auth('max_login_attempts', 5);
        $lockMins = Config::auth('lockout_minutes', 15);

        $attempts = DB::fetch(
            "SELECT COUNT(*) as cnt FROM activity_logs
             WHERE action = 'login_failed'
               AND ip_address = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ip, $lockMins]
        );

        if (($attempts['cnt'] ?? 0) >= $maxTries) {
            Logger::warning('Login locked out', ['ip' => $ip, 'email' => $email]);
            return [
                'success' => false,
                'message' => "Too many failed attempts. Try again in {$lockMins} minutes.",
            ];
        }

        $user = DB::fetch(
            "SELECT u.*, r.slug AS role_slug, r.permissions
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = 'active'
             LIMIT 1",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            log_activity('login_failed', 'auth', "Failed login attempt for: {$email}");
            Logger::warning('Login failed', ['email' => $email, 'ip' => $ip]);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        self::createSession($user);
        DB::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        log_activity('login', 'auth', "User logged in: {$user['email']}");
        Logger::info('User logged in', ['user_id' => $user['id'], 'email' => $user['email']]);

        return ['success' => true];
    }

    public static function attemptPin(string $pin): array
    {
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            return ['success' => false, 'message' => 'Invalid PIN format.'];
        }

        $user = DB::fetch(
            "SELECT u.*, r.slug AS role_slug, r.permissions
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.pin = ? AND u.status = 'active'
             LIMIT 1",
            [$pin]
        );

        if (!$user) {
            Logger::warning('PIN login failed', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            return ['success' => false, 'message' => 'Invalid PIN.'];
        }

        self::createSession($user);
        DB::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        log_activity('login_pin', 'auth', "PIN login: {$user['name']}");

        return ['success' => true, 'user' => ['name' => $user['name'], 'role' => $user['role_slug']]];
    }

    private static function createSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_name']   = $user['name'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['role_slug']   = $user['role_slug'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
        $_SESSION['logged_in']   = true;
        $_SESSION['login_at']    = time();
    }

    public static function logout(): void
    {
        if (self::check()) {
            log_activity('logout', 'auth', 'User logged out');
            Logger::info('User logged out', ['user_id' => self::id()]);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['role_slug'],
        ];
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) return false;
        $perms = $_SESSION['permissions'] ?? [];
        return !empty($perms['all']) || !empty($perms[$permission]);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Logger::warning('Unauthenticated access attempt', [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            if (is_ajax()) {
                Response::unauthorized();
            }
            Response::redirect(app_url('public/login.php'));
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireAuth();
        if (!self::can($permission)) {
            Logger::warning('Permission denied', [
                'user_id'    => self::id(),
                'permission' => $permission,
                'uri'        => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            if (is_ajax()) {
                Response::forbidden();
            }
            http_response_code(403);
            die('Access Denied');
        }
    }
}

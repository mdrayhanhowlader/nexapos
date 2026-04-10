<?php

class Logger
{
    private static string $logDir = '';

    private static function getLogDir(): string
    {
        if (!self::$logDir) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    public static function write(string $level, string $message, array $context = []): void
    {
        $dir      = self::getLogDir();
        $date     = date('Y-m-d');
        $time     = date('Y-m-d H:i:s');
        $file     = "{$dir}/{$level}-{$date}.log";
        $userId   = '';

        try {
            $userId = Auth::id() ? 'user:' . Auth::id() : 'guest';
        } catch (Exception $e) {}

        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $uri     = $_SERVER['REQUEST_URI'] ?? 'cli';
        $method  = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

        $contextStr = '';
        if (!empty($context)) {
            $contextStr = "\n  Context: " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $entry = "[{$time}] [{$level}] [{$userId}] [{$ip}] {$method} {$uri}\n"
               . "  Message: {$message}"
               . $contextStr
               . "\n"
               . str_repeat('-', 80)
               . "\n";

        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    public static function exception(Throwable $e, string $extra = ''): void
    {
        $context = [
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString(),
            'extra'   => $extra,
        ];
        self::write('EXCEPTION', $e->getMessage(), $context);
    }

    public static function query(string $sql, array $params = [], float $time = 0): void
    {
        if (Config::app('env') !== 'local') return;
        $context = [
            'sql'    => $sql,
            'params' => $params,
            'time'   => round($time * 1000, 2) . 'ms',
        ];
        self::write('QUERY', 'DB Query executed', $context);
    }
}

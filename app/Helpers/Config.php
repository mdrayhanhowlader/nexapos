<?php

class Config
{
    private static array $cache = [];

    public static function get(string $file, string $key = null, mixed $default = null): mixed
    {
        if (!isset(self::$cache[$file])) {
            $path = dirname(__DIR__, 2) . "/config/{$file}.php";
            self::$cache[$file] = file_exists($path) ? require $path : [];
        }

        $data = self::$cache[$file];

        if ($key === null) return $data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public static function app(string $key = null, mixed $default = null): mixed
    {
        return self::get('app', $key, $default);
    }

    public static function db(string $key = null, mixed $default = null): mixed
    {
        return self::get('database', $key, $default);
    }

    public static function auth(string $key = null, mixed $default = null): mixed
    {
        return self::get('auth', $key, $default);
    }
}

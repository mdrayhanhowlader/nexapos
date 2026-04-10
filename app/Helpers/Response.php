<?php

class Response
{
    public static function json(bool $success, mixed $data = null, string $message = '', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $payload = ['success' => $success, 'message' => $message];
        if ($data !== null) $payload['data'] = $data;

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = ''): void
    {
        self::json(true, $data, $message);
    }

    public static function error(string $message, int $code = 400, mixed $data = null): void
    {
        self::json(false, $data, $message, $code);
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    public static function unauthorized(): void
    {
        self::json(false, null, 'Unauthorized', 401);
    }

    public static function forbidden(): void
    {
        self::json(false, null, 'Access denied', 403);
    }

    public static function notFound(): void
    {
        self::json(false, null, 'Not found', 404);
    }
}

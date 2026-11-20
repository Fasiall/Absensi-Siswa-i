<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

final class Api
{
    public static function body(): array
    {
        $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($type !== 'application/json') {
            throw new ApiException(415, 'unsupported_media_type', 'Gunakan Content-Type application/json.');
        }
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || strlen($raw) > 65536) {
            throw new ApiException(413, 'payload_too_large', 'Ukuran request melebihi batas.');
        }
        try {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiException(400, 'invalid_json', 'Format JSON tidak valid.');
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new ApiException(400, 'invalid_json', 'Request JSON harus berupa objek.');
        }
        return $data;
    }

    public static function csrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($token)) {
            throw new ApiException(419, 'csrf_invalid', 'Token CSRF tidak valid.');
        }
    }

    public static function respond(mixed $data = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function error(ApiException $exception): never
    {
        http_response_code($exception->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

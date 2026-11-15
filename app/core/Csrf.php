<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        self::requireSession();
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function verify(mixed $token): bool
    {
        self::requireSession();
        return is_string($token) && isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . \e(self::token()) . '">';
    }

    /** Panggil untuk setiap endpoint yang mengubah state, termasuk login/logout. */
    public static function enforce(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Metode request tidak diizinkan.');
        }
        if (!self::verify($_POST['_csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
    }

    private static function requireSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Mulai session sebelum memakai CSRF.');
        }
    }
}

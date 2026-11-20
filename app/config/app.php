<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Sistem Absensi Siswa Berbasis QR Code'),
    'key' => env('APP_KEY', ''),
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) env('APP_URL', 'http://localhost/Absensi-Siswa-i/public'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'cookie_path' => env('SESSION_COOKIE_PATH', '/'),
    'session_lifetime' => 1800,
];

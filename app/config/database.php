<?php

declare(strict_types=1);

/** Koneksi lazy; homepage tetap dapat dibuka sebelum database diimpor. */
function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_DATABASE', 'absensi_qr_sekolah');
    $user = env('DB_USERNAME');
    $password = env('DB_PASSWORD');

    if ($user === null || $user === '' || $password === null) {
        throw new RuntimeException('Konfigurasi database belum lengkap. Isi .env lokal.');
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $host)
        || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535
        || !preg_match('/^[a-zA-Z0-9_]+$/D', $name)) {
        throw new RuntimeException('Host, port, atau nama database tidak valid.');
    }

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    } catch (PDOException $exception) {
        // Jangan meneruskan DSN, username, atau detail driver ke respons browser/log publik.
        error_log('Koneksi database gagal. SQLSTATE: ' . $exception->getCode());
        throw new RuntimeException('Database belum dapat dihubungkan. Periksa konfigurasi dan layanan MySQL.');
    }

    return $pdo;
}

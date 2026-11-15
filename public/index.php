<?php

declare(strict_types=1);

try {
    require dirname(__DIR__) . '/app/bootstrap.php';

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    $route = substr((string) $path, strlen($base));
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!in_array($route, ['', '/', '/index.php'], true)) {
        http_response_code(404);
        echo 'Halaman tidak ditemukan.';
    } elseif (!in_array($method, ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD');
        echo 'Metode request tidak diizinkan.';
    } else {
        view('dashboard/index', ['title' => 'Pondasi Proyek']);
    }
} catch (Throwable $exception) {
    // Pesan exception bisa berisi data sensitif; log hanya jenis exception.
    error_log('Bootstrap/request gagal: ' . get_class($exception));
    http_response_code(500);
    echo 'Aplikasi belum dapat dijalankan. Periksa konfigurasi dan log server lokal.';
}

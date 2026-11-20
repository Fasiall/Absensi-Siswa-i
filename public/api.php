<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\ApiController;
use App\Core\Api;
use App\Core\ApiException;

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        throw new ApiException(405, 'method_not_allowed', 'Metode request tidak didukung.');
    }
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api.php')), '/');
    $path = substr((string) $uriPath, strlen($scriptDirectory));
    if ($method === 'GET' && $path === '/api/v1/auth/csrf') {
        Api::respond(['csrf_token' => \App\Core\Csrf::token()]);
    }
    (new ApiController())->handle($method, $path);
} catch (ApiException $exception) {
    Api::error($exception);
} catch (Throwable $exception) {
    error_log('API request gagal: ' . get_class($exception));
    Api::error(new ApiException(500, 'internal_error', 'Terjadi kesalahan pada server.'));
}

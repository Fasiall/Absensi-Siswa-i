<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/security.php';
load_env(BASE_PATH . '/.env');
$GLOBALS['app_config'] = require BASE_PATH . '/app/config/app.php';
date_default_timezone_set((string) config('timezone'));
// Error detail tidak ditampilkan ke browser, termasuk pada tahap awal development.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

spl_autoload_register(static function (string $class): void {
    $directories = [
        'App\\Core\\' => 'core',
        'App\\Controllers\\' => 'controllers',
        'App\\Models\\' => 'models',
        'App\\Services\\' => 'services',
    ];
    foreach ($directories as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $name = substr($class, strlen($prefix));
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $name)) {
            return;
        }
        $file = BASE_PATH . '/app/' . $directory . '/' . $name . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return;
    }
});

require_once BASE_PATH . '/app/config/database.php';
if (PHP_SAPI !== 'cli') {
    send_security_headers();
    start_secure_session();
}

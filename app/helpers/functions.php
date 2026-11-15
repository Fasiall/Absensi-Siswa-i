<?php

declare(strict_types=1);

/** Minimal .env loader: environment proses selalu didahulukan, tanpa ekspansi variabel. */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('File konfigurasi tidak dapat dibaca.');
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=(.*)$/', $line, $matches)) {
            throw new RuntimeException('Format .env tidak valid. Gunakan KEY=value.');
        }
        [, $key, $value] = $matches;
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"'))
            || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['app_config'][$key] ?? $default;
}

function url(string $path = ''): string
{
    return rtrim((string) config('url'), '/') . '/' . ltrim($path, '/');
}

/** View hanya dipilih dari kode aplikasi, bukan dari parameter request. */
function view(string $template, array $data = []): void
{
    $path = BASE_PATH . '/app/views/' . $template . '.php';
    if (!is_file($path)) {
        throw new RuntimeException('Template tidak ditemukan.');
    }
    extract($data, EXTR_SKIP);
    require $path;
}

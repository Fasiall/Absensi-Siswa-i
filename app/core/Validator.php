<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function required(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    public static function username(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_.-]{3,64}$/D', $value) === 1;
    }

    public static function role(mixed $value): bool
    {
        return is_string($value) && in_array($value, ['admin', 'guru'], true);
    }

    public static function positiveId(mixed $value): bool
    {
        return !is_bool($value) && (is_string($value) || is_int($value))
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    /** Batas byte 72 mencegah pemotongan diam-diam oleh bcrypt di PHP 8.1. */
    public static function password(string $value): bool
    {
        return strlen($value) >= 12 && strlen($value) <= 72 && !str_contains($value, "\0");
    }
}

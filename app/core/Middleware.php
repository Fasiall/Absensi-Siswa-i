<?php

declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public function __construct(private Auth $auth)
    {
    }

    /** Mengembalikan user yang telah diverifikasi untuk pemeriksaan kepemilikan kelas. */
    public function requireRoles(string ...$roles): array
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw new ApiException(401, 'unauthenticated', 'Silakan login terlebih dahulu.');
        }
        if (!in_array($user['role'], $roles, true)) {
            throw new ApiException(403, 'forbidden', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }
        return $user;
    }

    public function admin(): array
    {
        return $this->requireRoles('admin');
    }

    /** Guru hanya boleh mengakses kelas penugasannya; periksa lagi di service/query. */
    public function guru(): array
    {
        return $this->requireRoles('guru');
    }

    public function adminOrGuru(): array
    {
        return $this->requireRoles('admin', 'guru');
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/** Kerangka autentikasi; belum dipasang ke endpoint login publik. */
final class Auth
{
    public function __construct(private ?PDO $connection = null)
    {
    }

    private function db(): PDO
    {
        return $this->connection ??= \database();
    }

    private function requireSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Mulai session sebelum memakai Auth.');
        }
    }

    /** Controller login nanti wajib memeriksa CSRF dan rate limit sebelum memanggil ini. */
    public function attempt(string $username, string $password): bool
    {
        $this->requireSession();
        $username = trim($username);
        if ($username === '' || strlen($username) > 64 || $password === '' || strlen($password) > 72) {
            return false;
        }
        $statement = $this->db()->prepare(
            'SELECT id, password_hash, role, is_active FROM users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        if (!$user || !(bool) $user['is_active'] || !is_string($user['password_hash'])
            || !in_array($user['role'], ['admin', 'guru'], true)
            || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $hash = $user['password_hash'];
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }
        $update = $this->db()->prepare(
            'UPDATE users SET last_login_at = UTC_TIMESTAMP(), password_hash = :hash WHERE id = :id'
        );
        $update->execute(['hash' => $hash, 'id' => $user['id']]);
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Session login tidak dapat diperbarui.');
        }
        $_SESSION = ['auth_user_id' => (int) $user['id'], 'auth_last_activity' => time()];
        return true;
    }

    /** Role/status dibaca ulang agar penonaktifan akun berlaku pada request berikutnya. */
    public function user(): ?array
    {
        $this->requireSession();
        $id = $_SESSION['auth_user_id'] ?? null;
        if (!is_int($id) || $id < 1) {
            return null;
        }
        if (time() - (int) ($_SESSION['auth_last_activity'] ?? 0) >= (int) \config('session_lifetime', 1800)) {
            $this->logout();
            return null;
        }
        $statement = $this->db()->prepare(
            'SELECT id, name, username, role FROM users WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        if (!$user || !in_array($user['role'], ['admin', 'guru'], true)) {
            $this->logout();
            return null;
        }
        $_SESSION['auth_last_activity'] = time();
        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function hasRole(string ...$roles): bool
    {
        $user = $this->user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    /** Gunakan hanya melalui request POST yang lolos CSRF ketika endpoint dibuat. */
    public function logout(): void
    {
        $this->requireSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use PDO;

final class LoginThrottle
{
    public function __construct(private PDO $db, private string $key)
    {
        if (strlen($this->key) < 32) {
            throw new \RuntimeException('APP_KEY minimal 32 byte dan wajib diset untuk pembatas login.');
        }
    }

    public function assertAllowed(string $username, string $ip): void
    {
        [$usernameHash, $ipHash] = $this->fingerprints($username, $ip);
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM auth_login_attempts
             WHERE username_hash = :username_hash AND ip_hash = :ip_hash
               AND attempted_at > UTC_TIMESTAMP() - INTERVAL 15 MINUTE'
        );
        $statement->execute(['username_hash' => $usernameHash, 'ip_hash' => $ipHash]);
        if ((int) $statement->fetchColumn() >= 5) {
            throw new ApiException(429, 'too_many_attempts', 'Terlalu banyak percobaan login dari perangkat ini. Tunggu 15 menit.');
        }
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM auth_login_attempts
             WHERE ip_hash = :ip_hash
               AND attempted_at > UTC_TIMESTAMP() - INTERVAL 15 MINUTE'
        );
        $statement->execute(['ip_hash' => $ipHash]);
        if ((int) $statement->fetchColumn() >= 30) {
            throw new ApiException(429, 'too_many_attempts', 'Terlalu banyak percobaan login dari jaringan ini. Tunggu 15 menit.');
        }
    }

    public function recordFailure(string $username, string $ip): void
    {
        [$usernameHash, $ipHash] = $this->fingerprints($username, $ip);
        $this->db->beginTransaction();
        try {
            $this->db->exec('DELETE FROM auth_login_attempts WHERE attempted_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
            $statement = $this->db->prepare(
                'INSERT INTO auth_login_attempts (username_hash, ip_hash, attempted_at)
                 VALUES (:username_hash, :ip_hash, UTC_TIMESTAMP())'
            );
            $statement->execute(['username_hash' => $usernameHash, 'ip_hash' => $ipHash]);
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function clear(string $username, string $ip): void
    {
        [$usernameHash, $ipHash] = $this->fingerprints($username, $ip);
        $statement = $this->db->prepare(
            'DELETE FROM auth_login_attempts WHERE username_hash = :username_hash AND ip_hash = :ip_hash'
        );
        $statement->execute(['username_hash' => $usernameHash, 'ip_hash' => $ipHash]);
    }

    private function fingerprints(string $username, string $ip): array
    {
        $normalizedIp = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
        return [
            hash_hmac('sha256', strtolower(trim($username)), $this->key),
            hash_hmac('sha256', $normalizedIp, $this->key),
        ];
    }
}

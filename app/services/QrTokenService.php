<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use PDO;

final class QrTokenService
{
    public function __construct(private PDO $db)
    {
    }

    /** Token mentah hanya dikembalikan saat diterbitkan, jangan dicatat ke log. */
    public function issue(int $studentId, int $actorId): array
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT id FROM students WHERE id = :id AND is_active = 1 FOR UPDATE');
            $statement->execute(['id' => $studentId]);
            if (!$statement->fetch()) {
                throw new ApiException(404, 'student_not_found', 'Siswa aktif tidak ditemukan.');
            }
            $statement = $this->db->prepare(
                'UPDATE qr_tokens SET revoked_at = UTC_TIMESTAMP()
                 WHERE student_id = :student_id AND revoked_at IS NULL AND expires_at > UTC_TIMESTAMP()'
            );
            $statement->execute(['student_id' => $studentId]);
            $rawToken = bin2hex(random_bytes(32));
            $statement = $this->db->prepare(
                'INSERT INTO qr_tokens (student_id, token_hash, expires_at, created_by)
                 VALUES (:student_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 365 DAY), :actor)'
            );
            $statement->execute([
                'student_id' => $studentId, 'token_hash' => hash('sha256', $rawToken), 'actor' => $actorId,
            ]);
            $tokenId = (int) $this->db->lastInsertId();
            $statement = $this->db->prepare(
                "INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details)
                 VALUES (:actor, 'qr_token.issue', 'qr_tokens', :id, '{\"format\":\"random_sha256\"}')"
            );
            $statement->execute(['actor' => $actorId, 'id' => $tokenId]);
            $this->db->commit();
            return ['token_id' => $tokenId, 'token' => $rawToken, 'expires_in_days' => 365];
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}

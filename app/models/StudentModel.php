<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class StudentModel
{
    public function __construct(private PDO $db)
    {
    }

    public function listForClass(int $classId, int $limit, int $offset, ?string $date = null): array
    {
        $statement = $this->db->prepare(
            'SELECT s.id, s.student_number, s.name, s.is_active
             FROM students s JOIN student_classes sc ON sc.student_id = s.id
             WHERE sc.class_id = :class_id AND sc.enrolled_on <= :membership_start
               AND (sc.left_on IS NULL OR sc.left_on >= :membership_end)
             ORDER BY s.name, s.id LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':class_id', $classId, PDO::PARAM_INT);
        $membershipDate = $date ?? (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $statement->bindValue(':membership_start', $membershipDate);
        $statement->bindValue(':membership_end', $membershipDate);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function create(string $studentNumber, string $name, int $classId, array $actor): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT id FROM classes WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $classId]);
            if (!$statement->fetch()) {
                throw new \App\Core\ApiException(404, 'class_not_found', 'Kelas tidak ditemukan.');
            }
            $statement = $this->db->prepare(
                'INSERT INTO students (student_number, name, is_active) VALUES (:number, :name, 1)'
            );
            $statement->execute(['number' => $studentNumber, 'name' => $name]);
            $studentId = (int) $this->db->lastInsertId();
            $statement = $this->db->prepare(
                'INSERT INTO student_classes (student_id, class_id, enrolled_on) VALUES (:student_id, :class_id, :enrolled_on)'
            );
            $statement->execute([
                'student_id' => $studentId,
                'class_id' => $classId,
                'enrolled_on' => (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d'),
            ]);
            $this->audit((int) $actor['id'], 'student.create', $studentId);
            $this->db->commit();
            return $studentId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function audit(int $actorId, string $action, int $entityId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details)
             VALUES (:actor, :action, :entity, :id, :details)'
        );
        $statement->execute([
            'actor' => $actorId, 'action' => $action, 'entity' => 'students',
            'id' => $entityId, 'details' => '{}',
        ]);
    }
}

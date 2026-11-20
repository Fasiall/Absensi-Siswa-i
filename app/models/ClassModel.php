<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class ClassModel
{
    public function __construct(private PDO $db)
    {
    }

    public function listForUser(array $user): array
    {
        if ($user['role'] === 'admin') {
            $statement = $this->db->query(
                'SELECT c.id, c.name, c.grade_level, ay.name AS academic_year
                 FROM classes c JOIN academic_years ay ON ay.id = c.academic_year_id
                 ORDER BY ay.starts_on DESC, c.grade_level, c.name'
            );
            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            'SELECT DISTINCT c.id, c.name, c.grade_level, ay.name AS academic_year
             FROM teachers t JOIN teacher_classes tc ON tc.teacher_id = t.id
             JOIN classes c ON c.id = tc.class_id
             JOIN academic_years ay ON ay.id = c.academic_year_id
             WHERE t.user_id = :user_id AND tc.assigned_on <= :today_start
               AND (tc.ended_on IS NULL OR tc.ended_on >= :today_end)
             ORDER BY ay.starts_on DESC, c.grade_level, c.name'
        );
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $statement->execute(['user_id' => $user['id'], 'today_start' => $today, 'today_end' => $today]);
        return $statement->fetchAll();
    }

    public function assignmentForUser(int $classId, array $user, bool $lock = false, ?string $date = null): ?array
    {
        $sql = 'SELECT tc.id AS teacher_class_id, tc.teacher_id, tc.class_id
                FROM teacher_classes tc';
        if ($user['role'] === 'guru') {
            $sql .= ' JOIN teachers t ON t.id = tc.teacher_id AND t.user_id = :user_id';
        }
        $sql .= ' WHERE tc.class_id = :class_id AND tc.assigned_on <= :assignment_start
                  AND (tc.ended_on IS NULL OR tc.ended_on >= :assignment_end)
                  ORDER BY tc.id LIMIT 1';
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->db->prepare($sql);
        $assignmentDate = $date ?? (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $params = ['class_id' => $classId, 'assignment_start' => $assignmentDate, 'assignment_end' => $assignmentDate];
        if ($user['role'] === 'guru') {
            $params['user_id'] = $user['id'];
        }
        $statement->execute($params);
        $assignment = $statement->fetch();
        return $assignment ?: null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Models\ClassModel;
use PDO;
use PDOException;

final class AttendanceService
{
    private ClassModel $classes;

    public function __construct(private PDO $db)
    {
        $this->classes = new ClassModel($db);
    }

    private function today(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
    }

    public function openSession(int $classId, string $date, array $actor): int
    {
        if ($date !== $this->today()) {
            throw new ApiException(422, 'invalid_session_date', 'Sesi hanya dapat dibuka untuk tanggal hari ini.');
        }
        $this->db->beginTransaction();
        try {
            $assignment = $this->classes->assignmentForUser($classId, $actor, true, $date);
            if (!$assignment) {
                throw new ApiException(403, 'class_forbidden', 'Anda tidak memiliki penugasan aktif pada kelas ini.');
            }
            $statement = $this->db->prepare(
                "INSERT INTO attendance_sessions (class_id, teacher_class_id, session_date, status, created_by)
                 VALUES (:class_id, :assignment_id, :session_date, 'open', :actor)"
            );
            $statement->execute([
                'class_id' => $classId, 'assignment_id' => $assignment['teacher_class_id'],
                'session_date' => $date, 'actor' => $actor['id'],
            ]);
            $sessionId = (int) $this->db->lastInsertId();
            $this->audit((int) $actor['id'], 'attendance_session.open', 'attendance_sessions', $sessionId);
            $this->db->commit();
            return $sessionId;
        } catch (PDOException $exception) {
            $this->rollback();
            if ($exception->getCode() === '23000') {
                throw new ApiException(409, 'session_exists', 'Sesi absensi kelas ini untuk hari ini sudah pernah dibuat.');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function closeSession(int $sessionId, array $actor): void
    {
        $this->db->beginTransaction();
        try {
            $session = $this->sessionForActor($sessionId, $actor, true);
            if ($session['status'] !== 'open') {
                throw new ApiException(409, 'session_not_open', 'Sesi tidak sedang terbuka.');
            }
            $statement = $this->db->prepare(
                "UPDATE attendance_sessions SET status = 'closed', closed_at = UTC_TIMESTAMP() WHERE id = :id"
            );
            $statement->execute(['id' => $sessionId]);
            $this->audit((int) $actor['id'], 'attendance_session.close', 'attendance_sessions', $sessionId);
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function recordManual(int $sessionId, int $studentId, string $status, ?string $notes, array $actor): int
    {
        if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpa'], true)) {
            throw new ApiException(422, 'invalid_attendance_status', 'Status absensi tidak dikenal.');
        }
        $this->db->beginTransaction();
        try {
            $session = $this->sessionForActor($sessionId, $actor, true);
            if ($session['status'] !== 'open') {
                throw new ApiException(409, 'session_not_open', 'Sesi absensi sudah ditutup.');
            }
            $this->assertStudentInClass($studentId, (int) $session['class_id'], $session['session_date']);
            $attendanceId = $this->insertAttendance($sessionId, $studentId, $status, 'manual', null, $notes, (int) $actor['id']);
            $this->logAttendance($attendanceId, $status, $notes, (int) $actor['id']);
            $this->db->commit();
            return $attendanceId;
        } catch (PDOException $exception) {
            $this->rollback();
            if ($exception->getCode() === '23000') {
                throw new ApiException(409, 'attendance_exists', 'Siswa sudah tercatat pada sesi ini.');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function recordQr(int $sessionId, string $rawToken, array $actor): array
    {
        if (strlen($rawToken) !== 64 || !ctype_xdigit($rawToken)) {
            throw new ApiException(422, 'invalid_qr_token', 'Token QR tidak valid.');
        }
        $this->db->beginTransaction();
        try {
            $session = $this->sessionForActor($sessionId, $actor, true);
            if ($session['status'] !== 'open') {
                throw new ApiException(409, 'session_not_open', 'Sesi absensi sudah ditutup.');
            }
            $statement = $this->db->prepare(
                'SELECT id, student_id FROM qr_tokens
                 WHERE token_hash = :token_hash AND revoked_at IS NULL
                   AND expires_at > UTC_TIMESTAMP() AND created_at <= UTC_TIMESTAMP()
                 LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['token_hash' => hash('sha256', strtolower($rawToken))]);
            $token = $statement->fetch();
            if (!$token) {
                throw new ApiException(422, 'qr_token_invalid', 'QR tidak valid, sudah kedaluwarsa, atau telah dicabut.');
            }
            $studentId = (int) $token['student_id'];
            $this->assertStudentInClass($studentId, (int) $session['class_id'], $session['session_date']);
            $attendanceId = $this->insertAttendance($sessionId, $studentId, 'hadir', 'qr', (int) $token['id'], null, (int) $actor['id']);
            $this->logAttendance($attendanceId, 'hadir', null, (int) $actor['id']);
            $this->db->commit();
            return ['attendance_id' => $attendanceId, 'student_id' => $studentId, 'status' => 'hadir'];
        } catch (PDOException $exception) {
            $this->rollback();
            if ($exception->getCode() === '23000') {
                throw new ApiException(409, 'attendance_exists', 'Siswa sudah tercatat pada sesi ini.');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function attendanceList(int $sessionId, array $actor): array
    {
        $session = $this->sessionForActor($sessionId, $actor);
        $statement = $this->db->prepare(
            'SELECT a.id, a.student_id, s.student_number, s.name AS student_name,
                    a.status, a.method, a.notes, a.recorded_at
             FROM attendances a JOIN students s ON s.id = a.student_id
             WHERE a.attendance_session_id = :id ORDER BY s.name, s.id'
        );
        $statement->execute(['id' => $session['id']]);
        return $statement->fetchAll();
    }

    public function sessions(array $actor): array
    {
        $sql = 'SELECT s.id, s.class_id, c.name AS class_name, s.session_date, s.status,
                       s.opened_at, s.closed_at
                FROM attendance_sessions s JOIN classes c ON c.id = s.class_id';
        if ($actor['role'] === 'guru') {
            $sql .= ' JOIN teacher_classes tc ON tc.id = s.teacher_class_id
                      JOIN teachers t ON t.id = tc.teacher_id AND t.user_id = :user_id
                      AND tc.assigned_on <= s.session_date
                      AND (tc.ended_on IS NULL OR tc.ended_on >= s.session_date)';
        }
        $sql .= ' ORDER BY s.session_date DESC, s.id DESC LIMIT 100';
        $statement = $this->db->prepare($sql);
        $statement->execute($actor['role'] === 'guru' ? ['user_id' => $actor['id']] : []);
        return $statement->fetchAll();
    }

    private function sessionForActor(int $sessionId, array $actor, bool $lock = false): array
    {
        $sql = 'SELECT s.id, s.class_id, s.session_date, s.status
                FROM attendance_sessions s';
        if ($actor['role'] === 'guru') {
            $sql .= ' JOIN teacher_classes tc ON tc.id = s.teacher_class_id
                     JOIN teachers t ON t.id = tc.teacher_id AND t.user_id = :user_id
                     AND tc.assigned_on <= s.session_date
                     AND (tc.ended_on IS NULL OR tc.ended_on >= s.session_date)';
        }
        $sql .= ' WHERE s.id = :id';
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->db->prepare($sql);
        $params = ['id' => $sessionId];
        if ($actor['role'] === 'guru') {
            $params['user_id'] = $actor['id'];
        }
        $statement->execute($params);
        $session = $statement->fetch();
        if (!$session) {
            throw new ApiException(404, 'session_not_found', 'Sesi tidak ditemukan atau bukan milik kelas Anda.');
        }
        return $session;
    }

    private function assertStudentInClass(int $studentId, int $classId, string $date): void
    {
        $stmt = $this->db->prepare(
            'SELECT s.id FROM students s JOIN student_classes sc ON sc.student_id = s.id
             WHERE s.id = :student_id AND s.is_active = 1 AND sc.class_id = :class_id
               AND sc.enrolled_on <= :session_start
               AND (sc.left_on IS NULL OR sc.left_on >= :session_end) LIMIT 1 FOR UPDATE'
        );
        $stmt->execute(['student_id' => $studentId, 'class_id' => $classId, 'session_start' => $date, 'session_end' => $date]);
        if (!$stmt->fetch()) {
            throw new ApiException(422, 'student_not_in_class', 'Siswa aktif tidak terdaftar pada kelas di tanggal sesi.');
        }
    }

    private function insertAttendance(int $sessionId, int $studentId, string $status, string $method, ?int $tokenId, ?string $notes, int $actorId): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO attendances (attendance_session_id, student_id, status, method, qr_token_id, notes, recorded_by)
             VALUES (:session, :student, :status, :method, :token, :notes, :actor)'
        );
        $statement->execute([
            'session' => $sessionId, 'student' => $studentId, 'status' => $status,
            'method' => $method, 'token' => $tokenId, 'notes' => $notes, 'actor' => $actorId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function logAttendance(int $attendanceId, string $status, ?string $notes, int $actorId): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO attendance_logs (attendance_id, action, old_status, new_status, old_notes, new_notes, changed_by)
             VALUES (:attendance, 'create', NULL, :status, NULL, :notes, :actor)"
        );
        $statement->execute(['attendance' => $attendanceId, 'status' => $status, 'notes' => $notes, 'actor' => $actorId]);
    }

    private function audit(int $actorId, string $action, string $entity, int $entityId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details)
             VALUES (:actor, :action, :entity, :id, :details)'
        );
        $statement->execute(['actor' => $actorId, 'action' => $action, 'entity' => $entity, 'id' => $entityId, 'details' => '{}']);
    }

    private function rollback(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}

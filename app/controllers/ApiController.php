<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Validator;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Services\AttendanceService;
use App\Services\LoginThrottle;
use App\Services\QrTokenService;
use PDO;
use PDOException;

final class ApiController
{
    private Auth $auth;
    private Middleware $middleware;
    private PDO $db;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->middleware = new Middleware($this->auth);
    }

    private function db(): PDO
    {
        return $this->db ??= \database();
    }

    public function handle(string $method, string $path): void
    {
        if ($method === 'POST' && $path === '/api/v1/auth/login') {
            $input = Api::body();
            Api::csrf();
            if (!Validator::username($input['username'] ?? null) || !is_string($input['password'] ?? null)) {
                throw new ApiException(422, 'validation_error', 'Username atau password tidak valid.');
            }
            $throttle = new LoginThrottle($this->db(), (string) \config('key'));
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $throttle->assertAllowed($input['username'], $ip);
            if (!$this->auth->attempt($input['username'], $input['password'])) {
                $throttle->recordFailure($input['username'], $ip);
                throw new ApiException(401, 'invalid_credentials', 'Username atau password salah.');
            }
            $throttle->clear($input['username'], $ip);
            Api::respond(['user' => $this->auth->user(), 'csrf_token' => Csrf::token()]);
        }
        if ($method === 'GET' && $path === '/api/v1/auth/me') {
            $user = $this->auth->user();
            if ($user === null) {
                throw new ApiException(401, 'unauthenticated', 'Silakan login terlebih dahulu.');
            }
            Api::respond(['user' => $user, 'csrf_token' => Csrf::token()]);
        }
        if ($method === 'POST' && $path === '/api/v1/auth/logout') {
            $this->middleware->adminOrGuru();
            Api::csrf();
            $this->auth->logout();
            Api::respond(['message' => 'Logout berhasil.']);
        }

        $user = $this->middleware->adminOrGuru();
        if ($method === 'GET' && $path === '/api/v1/classes') {
            Api::respond((new ClassModel($this->db()))->listForUser($user));
        }
        if ($method === 'GET' && $path === '/api/v1/sessions') {
            Api::respond((new AttendanceService($this->db()))->sessions($user));
        }
        if (preg_match('#^/api/v1/classes/([1-9][0-9]*)/students$#D', $path, $matches)) {
            $classId = (int) $matches[1];
            $this->assertClassAccess($classId, $user);
            if ($method === 'GET') {
                $limit = min(100, max(1, (int) ($_GET['per_page'] ?? 50)));
                $page = max(1, (int) ($_GET['page'] ?? 1));
                $students = (new StudentModel($this->db()))->listForClass($classId, $limit, ($page - 1) * $limit);
                Api::respond(['items' => $students, 'page' => $page, 'per_page' => $limit]);
            }
            if ($method === 'POST') {
                $this->middleware->admin();
                Api::csrf();
                $input = Api::body();
                if (!is_string($input['student_number'] ?? null) || trim($input['student_number']) === ''
                    || strlen(trim($input['student_number'])) > 30 || !is_string($input['name'] ?? null)
                    || trim($input['name']) === '' || strlen(trim($input['name'])) > 100) {
                    throw new ApiException(422, 'validation_error', 'Nom dan nomor siswa wajib diisi (maks. 100/30 karakter).');
                }
                try {
                    $id = (new StudentModel($this->db()))->create(trim($input['student_number']), trim($input['name']), $classId, $user);
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        throw new ApiException(409, 'student_number_exists', 'Nomor siswa sudah digunakan.');
                    }
                    throw $exception;
                }
                Api::respond(['id' => $id], 201);
            }
        }
        if ($method === 'POST' && $path === '/api/v1/sessions') {
            Api::csrf();
            $input = Api::body();
            if (!Validator::positiveId($input['class_id'] ?? null)) {
                throw new ApiException(422, 'validation_error', 'class_id harus berupa ID positif.');
            }
            $id = (new AttendanceService($this->db()))->openSession((int) $input['class_id'], date('Y-m-d'), $user);
            Api::respond(['id' => $id, 'status' => 'open'], 201);
        }
        if (preg_match('#^/api/v1/sessions/([1-9][0-9]*)/close$#D', $path, $matches) && $method === 'POST') {
            Api::csrf();
            (new AttendanceService($this->db()))->closeSession((int) $matches[1], $user);
            Api::respond(['message' => 'Sesi ditutup.']);
        }
        if (preg_match('#^/api/v1/sessions/([1-9][0-9]*)/attendances$#D', $path, $matches)) {
            $service = new AttendanceService($this->db());
            if ($method === 'GET') {
                Api::respond($service->attendanceList((int) $matches[1], $user));
            }
            if ($method === 'POST') {
                Api::csrf();
                $input = Api::body();
                if (!Validator::positiveId($input['student_id'] ?? null) || !is_string($input['status'] ?? null)
                    || (isset($input['notes']) && !is_string($input['notes']))
                    || strlen((string) ($input['notes'] ?? '')) > 2000) {
                    throw new ApiException(422, 'validation_error', 'Data absensi tidak valid.');
                }
                $id = $service->recordManual((int) $matches[1], (int) $input['student_id'], $input['status'], $input['notes'] ?? null, $user);
                Api::respond(['id' => $id], 201);
            }
        }
        if (preg_match('#^/api/v1/sessions/([1-9][0-9]*)/scan$#D', $path, $matches) && $method === 'POST') {
            Api::csrf();
            $input = Api::body();
            if (!is_string($input['token'] ?? null)) {
                throw new ApiException(422, 'validation_error', 'Token QR wajib dikirim.');
            }
            Api::respond((new AttendanceService($this->db()))->recordQr((int) $matches[1], $input['token'], $user), 201);
        }
        if (preg_match('#^/api/v1/students/([1-9][0-9]*)/qr-token$#D', $path, $matches) && $method === 'POST') {
            $this->middleware->admin();
            Api::csrf();
            Api::respond((new QrTokenService($this->db()))->issue((int) $matches[1], (int) $user['id']), 201);
        }

        throw new ApiException(404, 'not_found', 'Endpoint tidak ditemukan.');
    }

    public function assertClassAccess(int $classId, array $user): void
    {
        if ($user['role'] === 'admin') {
            $statement = $this->db()->prepare('SELECT id FROM classes WHERE id = :id');
            $statement->execute(['id' => $classId]);
            if (!$statement->fetch()) {
                throw new ApiException(404, 'class_not_found', 'Kelas tidak ditemukan.');
            }
            return;
        }
        if (!(new ClassModel($this->db()))->assignmentForUser($classId, $user)) {
            throw new ApiException(403, 'class_forbidden', 'Anda tidak memiliki penugasan aktif pada kelas ini.');
        }
    }
}

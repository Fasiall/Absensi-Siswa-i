<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Validator;
use App\Services\LoginThrottle;

/** Double PDO; menguji alur Auth, bukan kompatibilitas SQL MySQL. */
final class MemoryPdo extends PDO
{
    public array $account;

    public function __construct()
    {
        $this->account = [
            'id' => 1, 'username' => 'test.user', 'name' => 'User Uji',
            'role' => 'guru', 'is_active' => 1,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ];
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new MemoryStatement($this, $query);
    }
}

final class MemoryStatement extends PDOStatement
{
    private array $params = [];

    public function __construct(private MemoryPdo $db, private string $sql)
    {
    }

    public function execute(?array $params = null): bool
    {
        $this->params = $params ?? [];
        if (str_starts_with($this->sql, 'UPDATE')) {
            $this->db->account['password_hash'] = $this->params['hash'];
        }
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        $account = $this->db->account;
        if (isset($this->params['username']) && $this->params['username'] !== $account['username']) {
            return false;
        }
        if (isset($this->params['id']) && ($this->params['id'] !== $account['id'] || !$account['is_active'])) {
            return false;
        }
        if (isset($this->params['id'])) {
            return array_intersect_key($account, array_flip(['id', 'name', 'username', 'role']));
        }
        return $account;
    }
}

$count = 0;
function expect(bool $condition, string $description): void
{
    global $count;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $description);
    }
    $count++;
}

start_secure_session();
expect(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'escape HTML');
expect(e(null) === '', 'escape null');
expect(url('assets/css/app.css') === rtrim((string) config('url'), '/') . '/assets/css/app.css', 'base URL');
expect(Validator::required('0') && !Validator::required('  '), 'required');
expect(Validator::username('guru.demo') && !Validator::username('../bad'), 'username');
expect(Validator::role('guru') && !Validator::role('superadmin'), 'role allowlist');
expect(Validator::positiveId('12') && !Validator::positiveId('0') && !Validator::positiveId(true), 'positive ID');
$password = bin2hex(random_bytes(16));
expect(Validator::password($password) && !Validator::password('short') && !Validator::password(str_repeat('a', 73)), 'password length');
expect(!Csrf::verify(null) && !Csrf::verify([]), 'CSRF reject missing/array');
$token = Csrf::token();
expect(strlen($token) === 64 && $token === Csrf::token(), 'CSRF stable random token');
expect(Csrf::verify($token) && !Csrf::verify('bad'), 'CSRF matching');
expect(str_contains(Csrf::field(), 'type="hidden"'), 'CSRF field');
$apiControllerSource = file_get_contents(BASE_PATH . '/app/controllers/ApiController.php');
expect(is_string($apiControllerSource) && !str_contains($apiControllerSource, 'Api::input()'), 'API controller uses existing JSON body parser');
$attendanceSource = file_get_contents(BASE_PATH . '/app/services/AttendanceService.php');
expect(is_string($attendanceSource) && str_contains($attendanceSource, 'a.status, a.method'), 'attendance list query uses valid column reference');
$_SERVER['CONTENT_TYPE'] = 'text/plain';
try {
    Api::body();
    expect(false, 'API rejects non-JSON content type');
} catch (ApiException $exception) {
    expect($exception->status === 415, 'API rejects non-JSON content type');
}
$_SERVER['CONTENT_TYPE'] = 'application/json';
try {
    Api::body();
    expect(false, 'API rejects empty/invalid JSON body');
} catch (ApiException $exception) {
    expect($exception->status === 400, 'API rejects empty/invalid JSON body');
}
try {
    new LoginThrottle(new MemoryPdo(), 'short-key');
    expect(false, 'login throttle rejects weak APP_KEY');
} catch (RuntimeException) {
    expect(true, 'login throttle rejects weak APP_KEY');
}
$db = new MemoryPdo();
$db->account['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
$auth = new Auth($db);
expect(!$auth->check(), 'anonymous');
expect(!$auth->attempt('missing.user', $password), 'unknown account');
expect(!$auth->attempt('test.user', 'wrong-password'), 'wrong password');
expect(!$auth->attempt('test.user', $password . str_repeat('a', 73)), 'reject bcrypt truncation');
$db->account['is_active'] = 0;
expect(!$auth->attempt('test.user', $password), 'inactive account');
$db->account['is_active'] = 1;
$oldSession = session_id();
expect($auth->attempt('test.user', $password), 'valid login');
expect(session_id() !== $oldSession, 'rotate session ID');
expect(!isset($_SESSION['_csrf_token']), 'invalidate old CSRF after login');
expect($auth->hasRole('guru') && !$auth->hasRole('admin'), 'role isolation');
expect(!isset($auth->user()['password_hash']), 'Auth user exposes no hash');
expect((new Middleware($auth))->guru()['id'] === 1, 'guru middleware allowed');
expect((new Middleware($auth))->adminOrGuru()['id'] === 1, 'shared middleware allowed');
$db->account['role'] = 'admin';
expect($auth->hasRole('admin'), 'role reloaded from DB');
expect((new Middleware($auth))->admin()['id'] === 1, 'admin middleware allowed');
$db->account['is_active'] = 0;
expect($auth->user() === null && session_status() !== PHP_SESSION_ACTIVE, 'revoked account logs out');
start_secure_session();
expect($auth->attempt('test.user', $password) === false, 'revoked login rejected');
$db->account['is_active'] = 1;
expect($auth->attempt('test.user', $password), 're-login');
$_SESSION['auth_last_activity'] = time() - 1801;
expect($auth->user() === null, 'idle timeout');
start_secure_session();
expect($auth->attempt('test.user', $password), 'login before explicit logout');
$auth->logout();
expect($_SESSION === [] && session_status() !== PHP_SESSION_ACTIVE, 'logout destroys session');
echo 'PASS: ' . $count . ' pemeriksaan fondasi (tanpa koneksi MySQL).' . PHP_EOL;

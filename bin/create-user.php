<?php

declare(strict_types=1);

// CLI lokal saja; password tidak diterima sebagai argumen atau dicetak kembali.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Validator;

$options = getopt('', ['username:', 'name:', 'role:', 'help']);
if (isset($options['help'])) {
    echo "Aktivasi/buat akun lokal (role existing tidak boleh berubah).\n";
    echo 'php bin/create-user.php --username=... --name="..." --role=admin|guru < file-password-privat' . PHP_EOL;
    echo "Password: satu baris, 12–72 byte. Simpan file di luar repository lalu hapus.\n";
    exit(0);
}

$username = $options['username'] ?? null;
$name = $options['name'] ?? null;
$role = $options['role'] ?? null;
if (!Validator::username($username) || !Validator::role($role)
    || !Validator::required($name) || strlen($name) > 100) {
    fwrite(STDERR, "Argumen tidak valid. Jalankan dengan --help. Nama maksimal 100 byte.\n");
    exit(1);
}
if (stream_isatty(STDIN)) {
    fwrite(STDERR, "Password harus dibaca melalui STDIN redirect dari file privat di luar repository, bukan diketik di terminal/argumen.\n");
    exit(1);
}
$passwordLine = fgets(STDIN, 1024);
$password = $passwordLine === false ? '' : rtrim($passwordLine, "\r\n");
if (!Validator::password($password) || stream_get_contents(STDIN) !== '') {
    fwrite(STDERR, "Password harus tepat satu baris dengan panjang 12–72 byte.\n");
    exit(1);
}

$pdo = null;
try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    unset($password, $passwordLine);
    $pdo = database();
    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT id, role FROM users WHERE username = :username FOR UPDATE');
    $statement->execute(['username' => $username]);
    $existing = $statement->fetch();
    if ($existing && $existing['role'] !== $role) {
        throw new RuntimeException('Role akun existing tidak boleh diubah oleh skrip aktivasi.');
    }
    if ($existing) {
        $id = (int) $existing['id'];
        $statement = $pdo->prepare('UPDATE users SET name = :name, password_hash = :hash, is_active = 1 WHERE id = :id');
        $statement->execute(['name' => trim($name), 'hash' => $hash, 'id' => $id]);
    } else {
        $statement = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, is_active) VALUES (:name, :username, :hash, :role, 1)');
        $statement->execute(['name' => trim($name), 'username' => $username, 'hash' => $hash, 'role' => $role]);
        $id = (int) $pdo->lastInsertId();
    }
    if ($role === 'guru') {
        $statement = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :id');
        $statement->execute(['id' => $id]);
        if (!$statement->fetch()) {
            $statement = $pdo->prepare('INSERT INTO teachers (user_id) VALUES (:id)');
            $statement->execute(['id' => $id]);
        }
    }
    $statement = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details) VALUES (NULL, 'cli.user.activate', 'users', :id, :details)");
    $statement->execute(['id' => $id, 'details' => '{"source":"local_cli"}']);
    $pdo->commit();
    echo "Akun berhasil diaktifkan. Halaman login belum diimplementasikan. Hapus file password sementara.\n";
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Aktivasi gagal. Periksa koneksi, migrasi, username unik, dan kesamaan role akun existing.\n");
    exit(1);
}

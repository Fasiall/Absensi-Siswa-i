-- KHUSUS development pada database kosong yang sudah menerima migrasi 001.
-- Seluruh nama dan nomor siswa di bawah FIKTIF, bukan data sekolah sebenarnya.
-- Seed dijalankan sekali; ID tetap sengaja membuat rerun gagal, bukan menimpa data.
-- Jangan gunakan --force. Jika impor gagal, hentikan dan rollback koneksi tersebut.
-- Tidak ada password literal, hash credential, maupun token QR yang dapat dipakai.
-- Akun harus diaktivasi dengan CLI lokal bin/create-user.php yang akan dibuat terpisah.
-- CLI wajib meng-update akun berdasarkan username, bukan menghapus/membuat ulang ID.
-- Dengan demikian teachers.user_id dan seluruh relasi historis tetap terjaga.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
START TRANSACTION;

INSERT INTO users (id, name, username, password_hash, role, is_active)
VALUES
    (1, 'Administrator Demo', 'admin.demo', NULL, 'admin', 0),
    (2, 'Guru Fiktif Melati', 'guru.demo', NULL, 'guru', 0);

INSERT INTO teachers (id, user_id)
VALUES (1, 2);

INSERT INTO academic_years (id, name, starts_on, ends_on, is_active)
VALUES (1, '2026/2027', '2026-07-01', '2027-06-30', 1);

INSERT INTO classes (id, academic_year_id, name, grade_level)
VALUES (1, 1, '4A', 4);

INSERT INTO teacher_classes (id, teacher_id, class_id, assigned_on, ended_on)
VALUES (1, 1, 1, '2026-07-01', NULL);

INSERT INTO students (id, student_number, name, is_active)
VALUES
    (1, 'FIKTIF-001', 'Alya Contoh', 1),
    (2, 'FIKTIF-002', 'Bima Rekaan', 1),
    (3, 'FIKTIF-003', 'Citra Simulasi', 1),
    (4, 'FIKTIF-004', 'Danu Ilustrasi', 1);

INSERT INTO student_classes (id, student_id, class_id, enrolled_on, left_on)
VALUES
    (1, 1, 1, '2026-07-01', NULL),
    (2, 2, 1, '2026-07-01', NULL),
    (3, 3, 1, '2026-07-01', NULL),
    (4, 4, 1, '2026-07-01', NULL);

-- qr_tokens, attendance_sessions, attendances, attendance_logs, audit_logs kosong.
-- Buat token melalui generator server setelah akun aktif; jangan seed token mentah/hash.
COMMIT;

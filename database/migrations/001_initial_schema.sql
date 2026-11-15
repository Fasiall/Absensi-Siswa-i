-- Skema awal aplikasi absensi SD Negeri Purwodadi Simpang.
-- Target: MySQL 5.7+ / MariaDB 10.2+; seluruh tabel memakai InnoDB.
-- Pilih database kosong sebelum menjalankan file ini. Tidak membuat database/user DB.
-- DDL melakukan implicit commit: migrasi ini bukan transaksi dan dijalankan sekali.
-- ENUM membatasi kosakata, tetapi backend tetap wajib memvalidasi seluruh input.
-- Gunakan SQL mode strict pada koneksi aplikasi; tidak mengandalkan CHECK.
-- Seluruh DATETIME disimpan sebagai UTC; tanggal sekolah dihitung di Asia/Jakarta.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) NULL COMMENT 'NULL untuk akun belum diaktivasi; isi dengan password_hash() PHP',
    role ENUM('admin', 'guru') NOT NULL,
    is_active TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teachers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teachers_user (user_id),
    CONSTRAINT fk_teachers_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academic_years (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    is_active TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_academic_years_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(30) NOT NULL COMMENT 'Contoh: 4A; kelas dibuat ulang untuk setiap tahun ajaran',
    grade_level TINYINT UNSIGNED NOT NULL COMMENT 'Tingkat SD 1 sampai 6; validasi di backend',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_year_name (academic_year_id, name),
    CONSTRAINT fk_classes_academic_year FOREIGN KEY (academic_year_id)
        REFERENCES academic_years (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    assigned_on DATE NOT NULL,
    ended_on DATE NULL COMMENT 'Batas akhir inklusif; NULL berarti sampai akhir tahun ajaran',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teacher_classes_assignment (teacher_id, class_id),
    UNIQUE KEY uq_teacher_classes_id_class (id, class_id),
    KEY idx_teacher_classes_class (class_id),
    CONSTRAINT fk_teacher_classes_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_teacher_classes_class FOREIGN KEY (class_id)
        REFERENCES classes (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_number VARCHAR(30) NOT NULL COMMENT 'Nomor internal sekolah, bukan isi token QR',
    name VARCHAR(100) NOT NULL,
    is_active TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_number (student_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu tabel kecil untuk menyimpan riwayat kelas tanpa menimpa kelas siswa lama.
CREATE TABLE student_classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    enrolled_on DATE NOT NULL,
    left_on DATE NULL COMMENT 'Batas akhir inklusif; NULL berarti sampai akhir tahun ajaran',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_classes_membership (student_id, class_id),
    KEY idx_student_classes_class (class_id),
    CONSTRAINT fk_student_classes_student FOREIGN KEY (student_id)
        REFERENCES students (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_student_classes_class FOREIGN KEY (class_id)
        REFERENCES classes (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generate di server nanti: $rawToken = bin2hex(random_bytes(32));
-- Simpan hanya hash('sha256', $rawToken), tampilkan raw token sebagai QR sekali.
-- Jangan memakai NIS, nama, ID berurutan, atau password sebagai token.
-- Scan: hash payload yang diterima lalu lookup token_hash dengan parameter binding.
-- Token bersifat reusable sampai kedaluwarsa/dicabut, bukan bukti kehadiran mandiri.
CREATE TABLE qr_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'SHA-256 heksadesimal lowercase; tidak menyimpan token mentah',
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_qr_tokens_hash (token_hash),
    KEY idx_qr_tokens_student (student_id),
    KEY idx_qr_tokens_created_by (created_by),
    CONSTRAINT fk_qr_tokens_student FOREIGN KEY (student_id)
        REFERENCES students (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_qr_tokens_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    teacher_class_id BIGINT UNSIGNED NOT NULL,
    session_date DATE NOT NULL COMMENT 'Tanggal lokal sekolah, bukan tanggal UTC',
    status ENUM('open', 'closed', 'cancelled') NOT NULL DEFAULT 'open',
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_sessions_class_date (class_id, session_date),
    KEY idx_attendance_sessions_assignment (teacher_class_id, class_id),
    KEY idx_attendance_sessions_created_by (created_by),
    CONSTRAINT fk_attendance_sessions_class FOREIGN KEY (class_id)
        REFERENCES classes (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    -- FK komposit memastikan assignment guru berasal dari kelas sesi yang sama.
    CONSTRAINT fk_attendance_sessions_assignment FOREIGN KEY (teacher_class_id, class_id)
        REFERENCES teacher_classes (id, class_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendance_sessions_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attendance_session_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('hadir', 'sakit', 'izin', 'alpa') NOT NULL,
    method ENUM('manual', 'qr') NOT NULL DEFAULT 'manual',
    qr_token_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    recorded_by BIGINT UNSIGNED NOT NULL COMMENT 'Pengguna yang terakhir mencatat/mengoreksi',
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendances_session_student (attendance_session_id, student_id),
    KEY idx_attendances_student (student_id),
    KEY idx_attendances_qr_token (qr_token_id),
    KEY idx_attendances_recorded_by (recorded_by),
    CONSTRAINT fk_attendances_session FOREIGN KEY (attendance_session_id)
        REFERENCES attendance_sessions (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendances_student FOREIGN KEY (student_id)
        REFERENCES students (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendances_qr_token FOREIGN KEY (qr_token_id)
        REFERENCES qr_tokens (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendances_recorder FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attendance_id BIGINT UNSIGNED NOT NULL,
    action ENUM('create', 'update') NOT NULL,
    old_status ENUM('hadir', 'sakit', 'izin', 'alpa') NULL,
    new_status ENUM('hadir', 'sakit', 'izin', 'alpa') NOT NULL,
    old_notes TEXT NULL,
    new_notes TEXT NULL,
    reason VARCHAR(255) NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attendance_logs_attendance_time (attendance_id, created_at),
    KEY idx_attendance_logs_actor (changed_by),
    CONSTRAINT fk_attendance_logs_attendance FOREIGN KEY (attendance_id)
        REFERENCES attendances (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendance_logs_actor FOREIGN KEY (changed_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL COMMENT 'NULL untuk proses sistem atau login tanpa akun teridentifikasi',
    action VARCHAR(64) NOT NULL,
    entity_type VARCHAR(64) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details TEXT NULL COMMENT 'JSON tervalidasi backend; tanpa password, hash credential, atau token QR mentah',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_actor_time (actor_user_id, created_at),
    KEY idx_audit_logs_entity_time (entity_type, entity_id, created_at),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

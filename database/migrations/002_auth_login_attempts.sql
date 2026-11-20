-- Throttle percobaan login. Hash HMAC tidak menyimpan username atau IP mentah.
-- Secret APP_KEY hanya ada pada environment lokal/server, jangan commit nilainya.
-- Jalankan setelah 001_initial_schema.sql.
CREATE TABLE auth_login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_username_time (username_hash, attempted_at),
    KEY idx_login_attempts_ip_time (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

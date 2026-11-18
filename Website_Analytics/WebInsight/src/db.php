<?php
// Kết nối MySQL qua PDO và khởi tạo cấu trúc bảng.
function wi_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = (string) wi_cfg('db_host', '127.0.0.1');
    $port = (string) wi_cfg('db_port', '3306');
    $name = (string) wi_cfg('db_name', 'webinsight');
    $charset = (string) wi_cfg('db_charset', 'utf8mb4');
    $user = (string) wi_cfg('db_user', 'webinsight_user');
    $pass = (string) wi_cfg('db_pass', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

/** Tạo bảng MySQL nếu chưa tồn tại. Database phải được tạo trước. */
function wi_init_schema(PDO $pdo): void
{
    $statements = [
        'CREATE TABLE IF NOT EXISTS admins (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS websites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(2048) NOT NULL,
            api_key VARCHAR(100) NOT NULL UNIQUE,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS rules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            website_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            event_type VARCHAR(20) NOT NULL,
            target VARCHAR(500) NOT NULL,
            product VARCHAR(255) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_rules_site_type_target (website_id, event_type, target),
            CONSTRAINT fk_rules_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            website_id BIGINT UNSIGNED NOT NULL,
            rule_id BIGINT UNSIGNED NULL,
            event_uid VARCHAR(191) NOT NULL,
            session_id VARCHAR(191) NOT NULL,
            event_type VARCHAR(20) NOT NULL,
            target VARCHAR(500) NOT NULL,
            product VARCHAR(255) NULL,
            path VARCHAR(2048) NOT NULL,
            is_test TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            day DATE NOT NULL,
            UNIQUE KEY uq_events_site_uid (website_id, event_uid),
            KEY idx_events_site_day (website_id, day),
            KEY idx_events_session (session_id),
            KEY idx_events_day (day),
            CONSTRAINT fk_events_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
            CONSTRAINT fk_events_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS funnels (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            website_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            steps LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_funnels_website (website_id),
            CONSTRAINT fk_funnels_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(191) NOT NULL PRIMARY KEY,
            value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            created_at BIGINT NOT NULL,
            KEY idx_login_ip_created (ip, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];
    foreach ($statements as $sql) $pdo->exec($sql);
}

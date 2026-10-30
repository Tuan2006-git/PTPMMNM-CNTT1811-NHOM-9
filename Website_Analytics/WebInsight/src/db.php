<?php
// Kết nối SQLite và tạo cấu trúc bảng.

/** Trả về đối tượng PDO dùng chung (SQLite). */
function wi_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = (string) wi_cfg('db_path');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');   // bật khóa ngoại (xóa website -> xóa dữ liệu liên quan)
    $pdo->exec('PRAGMA busy_timeout = 5000'); // chờ tối đa 5s nếu DB đang bận
    return $pdo;
}

/** Tạo các bảng nếu chưa tồn tại (an toàn khi chạy nhiều lần). */
function wi_init_schema(PDO $pdo): void
{
    $statements = [
        // Tài khoản quản trị
        'CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        )',
        // Website được theo dõi (mỗi website có API key riêng)
        'CREATE TABLE IF NOT EXISTS websites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            url TEXT NOT NULL,
            api_key TEXT NOT NULL UNIQUE,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL
        )',
        // Quy tắc sự kiện: chỉ sự kiện khớp quy tắc đang bật mới được ghi nhận
        'CREATE TABLE IF NOT EXISTS rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            event_type TEXT NOT NULL CHECK (event_type IN (\'pageview\',\'click\',\'submit\')),
            target TEXT NOT NULL,
            product TEXT,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            UNIQUE (website_id, event_type, target)
        )',
        // Sự kiện đã thu thập. UNIQUE(website_id, event_uid) giúp chống đếm trùng.
        'CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            rule_id INTEGER REFERENCES rules(id) ON DELETE SET NULL,
            event_uid TEXT NOT NULL,
            session_id TEXT NOT NULL,
            event_type TEXT NOT NULL,
            target TEXT NOT NULL,
            product TEXT,
            path TEXT NOT NULL,
            is_test INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            day TEXT NOT NULL,
            UNIQUE (website_id, event_uid)
        )',
        'CREATE INDEX IF NOT EXISTS idx_events_site_day ON events (website_id, day)',
        'CREATE INDEX IF NOT EXISTS idx_events_session ON events (session_id)',
        'CREATE INDEX IF NOT EXISTS idx_events_day ON events (day)',
        // Chuỗi sự kiện: steps là mảng JSON các id quy tắc theo thứ tự
        'CREATE TABLE IF NOT EXISTS funnels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            steps TEXT NOT NULL,
            created_at TEXT NOT NULL
        )',
        // Cài đặt hệ thống (ví dụ thời hạn lưu trữ)
        'CREATE TABLE IF NOT EXISTS settings (
            name TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )',
        // Lịch sử đăng nhập sai (chống dò mật khẩu)
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            created_at INTEGER NOT NULL
        )',
    ];
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
}

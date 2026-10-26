<?php
declare(strict_types=1);

final class Database
{
    public static function connection(): PDO
    {
        $dir = dirname(__DIR__) . '/var';
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $pdo = new PDO('sqlite:' . $dir . '/analytics.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        self::migrate($pdo);
        return $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS websites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            api_key TEXT NOT NULL UNIQUE,
            report_token TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS event_definitions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            event_name TEXT NOT NULL,
            event_type TEXT NOT NULL CHECK(event_type IN ('page_view','action_start','action_complete','button_click')),
            enabled INTEGER NOT NULL DEFAULT 1,
            UNIQUE(website_id,event_name)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id TEXT NOT NULL,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            started_at TEXT NOT NULL,
            last_seen_at TEXT NOT NULL,
            PRIMARY KEY(website_id,id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL,
            website_id INTEGER NOT NULL REFERENCES websites(id) ON DELETE CASCADE,
            session_id TEXT NOT NULL,
            event_name TEXT NOT NULL,
            event_type TEXT NOT NULL,
            page_path TEXT NOT NULL,
            occurred_at TEXT NOT NULL,
            received_at TEXT NOT NULL,
            payload_hash TEXT NOT NULL,
            UNIQUE(website_id,event_id),
            FOREIGN KEY(website_id,session_id) REFERENCES sessions(website_id,id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS ingestion_errors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            website_id INTEGER,
            reason TEXT NOT NULL,
            created_at TEXT NOT NULL
        )");
        $count = (int)$pdo->query('SELECT COUNT(*) FROM websites')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO websites(name,slug,api_key,report_token,created_at) VALUES(?,?,?,?,?)');
            $now = gmdate('c');
            $stmt->execute(['Website thử nghiệm A','demo-a','demo-key-a-change-me','report-a-change-me',$now]);
            $stmt->execute(['Website thử nghiệm B','demo-b','demo-key-b-change-me','report-b-change-me',$now]);
            $definitions = [
                ['page_view','page_view'], ['start','action_start'],
                ['complete','action_complete'], ['button_click','button_click']
            ];
            foreach ([1,2] as $wid) {
                foreach ($definitions as [$name,$type]) {
                    $s = $pdo->prepare('INSERT OR IGNORE INTO event_definitions(website_id,event_name,event_type) VALUES(?,?,?)');
                    $s->execute([$wid,$name,$type]);
                }
            }
        }
    }
}

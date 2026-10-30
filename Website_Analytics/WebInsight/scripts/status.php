<?php
// Xem nhanh dữ liệu đã ghi vào CSDL. Chạy: php scripts/status.php
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$pdo = wi_db();
echo "CSDL: " . wi_cfg('db_path') . "\n\n";
echo "== Website ==\n";
foreach (wi_sites($pdo) as $s) {
    printf("#%d %-24s %-9s %s\n   API key: %s\n", $s['id'], $s['name'], $s['active'] ? 'hoạt động' : 'đã tắt', $s['url'], $s['api_key']);
}
echo "\n== Số sự kiện ==\n";
$c = $pdo->query('SELECT COALESCE(SUM(is_test = 0),0) AS r, COALESCE(SUM(is_test = 1),0) AS t FROM events')->fetch();
echo "Thật: {$c['r']}   Kiểm thử: {$c['t']}\n";
echo "\n== Sự kiện thật theo ngày và loại (14 ngày gần nhất) ==\n";
$rows = $pdo->query("SELECT day, event_type, COUNT(*) AS c FROM events WHERE is_test = 0
                     GROUP BY day, event_type ORDER BY day DESC, event_type LIMIT 42")->fetchAll();
if (!$rows) { echo "(chưa có)\n"; }
foreach ($rows as $r) { printf("%s  %-9s %d\n", $r['day'], $r['event_type'], $r['c']); }
echo "\n== 10 sự kiện thật mới nhất ==\n";
$rows = $pdo->query("SELECT created_at, event_type, target, product, path FROM events WHERE is_test = 0 ORDER BY id DESC LIMIT 10")->fetchAll();
if (!$rows) { echo "(chưa có)\n"; }
foreach ($rows as $r) { printf("%s  %-9s %-14s %-5s %s\n", $r['created_at'], $r['event_type'], $r['target'], $r['product'] ?? '-', $r['path']); }

<?php
// Xóa sự kiện cũ hơn thời hạn lưu trữ. Chạy: php scripts/purge.php [số_ngày]
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$pdo = wi_db();
$days = isset($argv[1]) ? max(1, (int) $argv[1]) : wi_retention_days($pdo);
echo "[OK] Đã xóa " . wi_purge_old($pdo, $days) . " sự kiện cũ hơn $days ngày.\n";

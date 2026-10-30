<?php
// Chạy bộ kiểm thử tự động. Chạy: php scripts/run_tests.php
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$pdo = wi_db();
wi_init_schema($pdo);
$results = wi_run_selftests($pdo);
$fail = 0;
foreach ($results as $i => $r) {
    printf("%2d. [%s] %s%s\n", $i + 1, $r['ok'] ? 'ĐẠT' : 'LỖI', $r['name'], $r['detail'] !== '' ? '  — ' . $r['detail'] : '');
    if (!$r['ok']) { $fail++; }
}
echo "\nTổng kết: " . (count($results) - $fail) . '/' . count($results) . " kịch bản đạt.\n";
exit($fail === 0 ? 0 : 1);

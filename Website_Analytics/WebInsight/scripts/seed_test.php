<?php
// Sinh dữ liệu kiểm thử (is_test = 1), tách biệt với dữ liệu thật.
// Chạy: php scripts/seed_test.php [id_website] [số_phiên]
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$pdo = wi_db();
$siteId = (int) ($argv[1] ?? 0);
$n = max(1, min(2000, (int) ($argv[2] ?? 100)));
if ($siteId <= 0) {
    $siteId = (int) $pdo->query('SELECT id FROM websites ORDER BY id LIMIT 1')->fetchColumn();
}
if (!wi_site_get($pdo, $siteId)) {
    fwrite(STDERR, "Không tìm thấy website. Hãy chạy: ./lab.sh init\n");
    exit(1);
}
$total = wi_generate_test_data($pdo, $siteId, $n);
echo "[OK] Đã tạo $total sự kiện kiểm thử cho website #$siteId (xem ở dashboard bằng bộ lọc 'Dữ liệu kiểm thử').\n";

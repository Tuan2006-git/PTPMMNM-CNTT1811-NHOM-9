<?php
// Khởi tạo cơ sở dữ liệu, tài khoản quản trị mặc định và website demo.
// Chạy: php scripts/init_db.php   (an toàn khi chạy lại nhiều lần)
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$pdo = wi_db();
wi_init_schema($pdo);
echo "[OK] Đã tạo cấu trúc bảng: " . wi_cfg('db_path') . "\n";

// Tài khoản quản trị: chỉ tạo nếu chưa có tài khoản nào (không ghi đè mật khẩu đã đổi)
$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    wi_create_admin($pdo, (string) wi_cfg('default_admin_user'), (string) wi_cfg('default_admin_pass'));
    echo "[OK] Đã tạo tài khoản quản trị: " . wi_cfg('default_admin_user') . " / " . wi_cfg('default_admin_pass') . "\n";
} else {
    echo "[--] Đã có tài khoản quản trị, giữ nguyên.\n";
}

if (wi_setting($pdo, 'retention_days', '') === '') {
    wi_set_setting($pdo, 'retention_days', (string) wi_cfg('default_retention_days'));
}

// Website demo + quy tắc mẫu (chỉ tạo khi chưa có website nào). KHÔNG tạo số liệu thống kê giả.
$siteCount = (int) $pdo->query('SELECT COUNT(*) FROM websites')->fetchColumn();
if ($siteCount === 0) {
    $now = date('Y-m-d H:i:s');
    $key = wi_new_api_key();
    $pdo->prepare('INSERT INTO websites (name, url, api_key, active, created_at) VALUES (?, ?, ?, 1, ?)')
        ->execute(['Cửa hàng Demo', 'http://localhost:8000', $key, $now]);
    $siteId = (int) $pdo->lastInsertId();

    $rules = [
        ['Xem trang cửa hàng', 'pageview', '/demo.php', null],
        ['Nhấp xem quần', 'click', 'btn-quan', 'quan'],
        ['Nhấp xem áo', 'click', 'btn-ao', 'ao'],
        ['Nhấp xem kính', 'click', 'btn-kinh', 'kinh'],
        ['Nhấp xem giày', 'click', 'btn-giay', 'giay'],
        ['Gửi biểu mẫu tư vấn', 'submit', 'form-lien-he', null],
    ];
    $ins = $pdo->prepare('INSERT INTO rules (website_id, name, event_type, target, product, active, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)');
    $ids = [];
    foreach ($rules as $r) {
        $ins->execute([$siteId, $r[0], $r[1], $r[2], $r[3], $now]);
        $ids[$r[2]] = (int) $pdo->lastInsertId();
    }
    $pdo->prepare('INSERT INTO funnels (website_id, name, steps, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$siteId, 'Xem trang → Nhấp áo → Gửi biểu mẫu',
                   json_encode([$ids['/demo.php'], $ids['btn-ao'], $ids['form-lien-he']]), $now]);
    echo "[OK] Đã tạo website demo (API key: $key) cùng 6 quy tắc mẫu và 1 chuỗi sự kiện mẫu.\n";
} else {
    echo "[--] Đã có website, không tạo website demo.\n";
}
echo "Hoàn tất.\n";

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

// Không tạo website mẫu; quản trị viên tự thêm website cần theo dõi trong mục Quản lý website.
echo "Hoàn tất.\n";

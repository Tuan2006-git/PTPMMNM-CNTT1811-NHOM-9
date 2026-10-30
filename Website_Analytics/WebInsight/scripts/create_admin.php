<?php
// Tạo tài khoản quản trị hoặc đặt lại mật khẩu.
// Chạy: php scripts/create_admin.php <tên_đăng_nhập> <mật_khẩu>
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
require __DIR__ . '/../src/bootstrap.php';

$user = $argv[1] ?? '';
$pass = $argv[2] ?? '';
if ($user === '' || $pass === '') {
    fwrite(STDERR, "Cách dùng: php scripts/create_admin.php <tên_đăng_nhập> <mật_khẩu>\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $user)) {
    fwrite(STDERR, "Tên đăng nhập chỉ gồm chữ, số, _ . - (3-32 ký tự).\n");
    exit(1);
}
if (strlen($pass) < 8) {
    fwrite(STDERR, "Mật khẩu phải có ít nhất 8 ký tự.\n");
    exit(1);
}
$pdo = wi_db();
wi_init_schema($pdo);
wi_create_admin($pdo, $user, $pass);
echo "[OK] Đã lưu tài khoản quản trị '$user'.\n";

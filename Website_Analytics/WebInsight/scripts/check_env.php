<?php
// Kiểm tra môi trường chạy WebInsight. Chạy: php scripts/check_env.php
if (PHP_SAPI !== 'cli') { exit("Chỉ chạy từ dòng lệnh.\n"); }
$root = dirname(__DIR__);
$bad = 0;
$check = function (string $label, bool $ok, string $fix = '') use (&$bad) {
    echo ($ok ? '[OK]  ' : '[LỖI]') . ' ' . $label . ($ok ? '' : "  → $fix") . "\n";
    if (!$ok) { $bad++; }
};
$check('PHP >= 8.1 (hiện tại ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.1.0', '>='), 'sudo apt install php-cli');
$check('Extension pdo_sqlite', extension_loaded('pdo_sqlite'), 'sudo apt install php-sqlite3');
$check('Extension sqlite3', extension_loaded('sqlite3'), 'sudo apt install php-sqlite3');
$check('Extension json', extension_loaded('json'), 'sudo apt install php-cli');
foreach (['data', 'logs'] as $d) {
    if (!is_dir("$root/$d")) { @mkdir("$root/$d", 0775, true); }
    $check("Thư mục $d/ ghi được", is_writable("$root/$d"), "chmod -R u+rwX $d  (hoặc chuyển dự án ra ~/ nếu đang ở /mnt/c)");
}
echo $bad === 0 ? "\nMôi trường sẵn sàng.\n" : "\nCó $bad vấn đề cần xử lý.\n";
exit($bad === 0 ? 0 : 1);

<?php
// Nạp cấu hình và các thành phần dùng chung. Mọi trang đều require file này đầu tiên.

define('WI_ROOT', dirname(__DIR__));

/** Đọc một giá trị cấu hình từ config/config.php */
function wi_cfg(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require WI_ROOT . '/config/config.php';
    }
    return $cfg[$key] ?? $default;
}

date_default_timezone_set((string) wi_cfg('timezone', 'Asia/Ho_Chi_Minh'));

require_once WI_ROOT . '/src/db.php';
require_once WI_ROOT . '/src/helpers.php';
require_once WI_ROOT . '/src/auth.php';
require_once WI_ROOT . '/src/stats.php';
require_once WI_ROOT . '/src/collector.php';
require_once WI_ROOT . '/src/testdata.php';
require_once WI_ROOT . '/src/selftest.php';
require_once WI_ROOT . '/src/layout.php';

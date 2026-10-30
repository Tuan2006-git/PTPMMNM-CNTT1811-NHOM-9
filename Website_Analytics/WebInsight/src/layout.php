<?php
// Giao diện dùng chung: khung trang, biểu tượng, biểu đồ, bộ lọc.

/** Biểu tượng SVG đơn giản (nét viền). */
function wi_icon(string $name, int $size = 20): string
{
    $paths = [
        'person'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'rule'     => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'box'      => '<path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10"/>',
        'funnel'   => '<path d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>',
        'download' => '<path d="M12 4v11M7 11l5 5 5-5M4 20h16"/>',
        'flask'    => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 002 3h10a2 2 0 002-3l-5-9V3"/>',
        'play'     => '<circle cx="12" cy="12" r="9"/><path d="M10 8l6 4-6 4V8z"/>',
        'key'      => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'logout'   => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'click'    => '<path d="M5 3l14 7-6 2-2 6L5 3z"/>',
        'session'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $d = $paths[$name] ?? $paths['chart'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $d . '</svg>';
}

/** Đầu trang quản trị: thanh bên + thanh trên. */
function wi_header(string $title, string $active = ''): void
{
    $admin = wi_current_admin();
    $items = [
        'dashboard' => ['Tổng quan', 'dashboard.php', 'chart'],
        'websites'  => ['Quản lý website', 'websites.php', 'globe'],
        'rules'     => ['Quy tắc sự kiện', 'rules.php', 'rule'],
        'products'  => ['Thống kê sản phẩm', 'products.php', 'box'],
        'funnels'   => ['Phân tích chuỗi', 'funnels.php', 'funnel'],
        'reports'   => ['Xuất báo cáo', 'reports.php', 'download'],
        'tools'     => ['Kiểm thử & dữ liệu', 'tools.php', 'flask'],
        'demo'      => ['Website demo', 'demo.php', 'play'],
        'password'  => ['Đổi mật khẩu', 'password.php', 'key'],
    ];
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($title) . ' – WebInsight</title>'
        . '<link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<div class="layout"><aside class="sidebar" id="sidebar">'
        . '<div class="brand">' . wi_icon('chart', 26) . '<span>WebInsight</span></div><nav>';
    foreach ($items as $key => $it) {
        echo '<a class="nav-link' . ($key === $active ? ' active' : '') . '" href="' . e($it[1]) . '"'
            . ($key === 'demo' ? ' target="_blank" rel="noopener"' : '') . '>'
            . wi_icon($it[2], 18) . '<span>' . e($it[0]) . '</span></a>';
    }
    echo '</nav></aside><div class="main"><header class="topbar">'
        . '<button class="menu-btn" id="menuBtn" type="button" aria-label="Mở menu">☰</button>'
        . '<h1>' . e($title) . '</h1><div class="user">' . wi_icon('person', 18)
        . '<span>' . e($admin['username'] ?? '') . '</span>'
        . '<form method="post" action="logout.php">' . csrf_field()
        . '<button class="btn btn-ghost btn-sm" type="submit">' . wi_icon('logout', 16) . ' Đăng xuất</button></form>'
        . '</div></header><main class="content">';
    foreach (wi_flash_pull() as $m) {
        echo '<div class="alert alert-' . e($m[0]) . '">' . e($m[1]) . '</div>';
    }
}

function wi_footer(): void
{
    echo '</main></div></div><script src="assets/app.js"></script></body></html>';
}

/** Khung trang cho login (không có thanh bên). */
function wi_auth_header(string $title): void
{
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($title) . ' – WebInsight</title>'
        . '<link rel="stylesheet" href="assets/style.css"></head><body class="auth-body">';
}

/** Bộ lọc dùng chung: website, khoảng thời gian, loại dữ liệu. */
function wi_filter_form(array $f, array $sites, array $hidden = []): void
{
    echo '<form method="get" class="filters card">';
    foreach ($hidden as $k => $v) {
        echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    echo '<label>Website<select name="site"><option value="0">Tất cả website</option>';
    foreach ($sites as $s) {
        echo '<option value="' . (int) $s['id'] . '"' . ((int) $s['id'] === $f['site'] ? ' selected' : '') . '>'
            . e($s['name']) . '</option>';
    }
    echo '</select></label>'
        . '<label>Từ ngày<input type="date" name="from" value="' . e($f['from']) . '"></label>'
        . '<label>Đến ngày<input type="date" name="to" value="' . e($f['to']) . '"></label>'
        . '<label>Loại dữ liệu<select name="mode">'
        . '<option value="real"' . ($f['mode'] === 'real' ? ' selected' : '') . '>Dữ liệu thật</option>'
        . '<option value="test"' . ($f['mode'] === 'test' ? ' selected' : '') . '>Dữ liệu kiểm thử</option>'
        . '</select></label>'
        . '<button class="btn" type="submit">Lọc</button></form>';
}

/** Biểu đồ thanh ngang. $rows = [[nhãn, giá trị], ...] */
function wi_hbars(array $rows): string
{
    $max = 0;
    foreach ($rows as $r) {
        $max = max($max, (int) $r[1]);
    }
    if ($max === 0) {
        return '<p class="empty">Chưa có dữ liệu trong khoảng thời gian này.</p>';
    }
    $html = '<div class="hbars">';
    foreach ($rows as $r) {
        $w = round((int) $r[1] * 100 / $max, 1);
        $html .= '<div class="hbar"><span class="hbar-label">' . e($r[0]) . '</span>'
            . '<span class="hbar-track"><span class="hbar-fill" style="width:' . $w . '%"></span></span>'
            . '<span class="hbar-value">' . (int) $r[1] . '</span></div>';
    }
    return $html . '</div>';
}

/** Biểu đồ cột theo ngày, 2 chuỗi: lượt xem trang và tương tác. */
function wi_daily_chart(array $days): string
{
    $max = 0;
    foreach ($days as $d) {
        $max = max($max, $d['pv'], $d['inter']);
    }
    if ($max === 0) {
        return '<p class="empty">Chưa có dữ liệu trong khoảng thời gian này.</p>';
    }
    $html = '<div class="cols-wrap"><div class="cols">';
    foreach ($days as $d) {
        $h1 = round($d['pv'] * 100 / $max, 1);
        $h2 = round($d['inter'] * 100 / $max, 1);
        $html .= '<div class="col"><div class="col-bars">'
            . '<span class="bar bar-a" style="height:' . $h1 . '%" title="Lượt xem: ' . $d['pv'] . '"></span>'
            . '<span class="bar bar-b" style="height:' . $h2 . '%" title="Tương tác: ' . $d['inter'] . '"></span>'
            . '</div><span class="col-label">' . e(date('d/m', strtotime($d['day']))) . '</span></div>';
    }
    return $html . '</div></div><div class="legend"><span><i class="dot dot-a"></i> Lượt xem trang</span>'
        . '<span><i class="dot dot-b"></i> Lượt tương tác</span></div>';
}

<?php
// Giao diện dùng chung: khung trang, biểu tượng, biểu đồ, bộ lọc.

function wi_icon(string $name, int $size = 20): string
{
    $paths = [
        'person' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'chart' => '<path d="M3 3v18h18M8 15l4-4 4 3 5-7"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'rule' => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'box' => '<path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10"/>',
        'funnel' => '<path d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>',
        'download' => '<path d="M12 4v11M7 11l5 5 5-5M4 20h16"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'logout' => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'click' => '<path d="M5 3l14 7-6 2-2 6L5 3z"/>',
        'session' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    ];
    $d = $paths[$name] ?? $paths['chart'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

function wi_header(string $title, string $active = ''): void
{
    $admin = wi_current_admin();
    $items = [
        'dashboard' => ['Tổng quan', 'dashboard.php', 'chart'],
        'websites' => ['Quản lý website', 'websites.php', 'globe'],
        'rules' => ['Quy tắc sự kiện', 'rules.php', 'rule'],
        'products' => ['Thống kê sản phẩm', 'products.php', 'box'],
        'funnels' => ['Phân tích chuỗi', 'funnels.php', 'funnel'],
        'reports' => ['Xuất báo cáo', 'reports.php', 'download'],
    ];
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="theme-color" content="#101b36"><title>' . e($title) . ' – WebInsight</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="assets/style.css"></head><body class="app-body">';
    echo '<div class="layout"><aside class="sidebar" id="sidebar">'
        . '<a class="brand" href="dashboard.php"><span class="brand-mark">' . wi_icon('chart', 24) . '</span><span class="brand-copy"><strong>WebInsight</strong><small>ANALYTICS PLATFORM</small></span></a>'
        . '<div class="nav-caption">WORKSPACE</div><nav class="main-nav">';
    foreach ($items as $key => $it) {
        echo '<a class="nav-link' . ($key === $active ? ' active' : '') . '" href="' . e($it[1]) . '">' . wi_icon($it[2], 19) . '<span>' . e($it[0]) . '</span>' . ($key === $active ? '<i class="nav-indicator"></i>' : '') . '</a>';
    }
    echo '</nav><div class="sidebar-bottom"><div class="sidebar-help"><span class="help-orb">?</span><div><strong>WebInsight</strong><small>Theo dõi hiệu quả website</small></div></div>'
        . '<a class="nav-link settings-link' . ($active === 'password' ? ' active' : '') . '" href="password.php">' . wi_icon('key', 18) . '<span>Đổi mật khẩu</span></a></div></aside>'
        . '<div class="main"><header class="topbar"><button class="menu-btn" id="menuBtn" type="button" aria-label="Mở menu">' . wi_icon('menu', 22) . '</button>'
        . '<div class="topbar-heading"><span class="eyebrow">WORKSPACE / WEBINSIGHT</span><h1>' . e($title) . '</h1></div>'
        . '<div class="topbar-actions"><span class="system-status"><i></i> Hệ thống hoạt động</span><div class="user-avatar">' . e(strtoupper(substr((string)($admin['username'] ?? 'A'), 0, 1))) . '</div>'
        . '<div class="user-meta"><strong>' . e($admin['username'] ?? '') . '</strong><small>Quản trị viên</small></div>'
        . '<form method="post" action="logout.php">' . csrf_field() . '<button class="logout-btn" type="submit" title="Đăng xuất">' . wi_icon('logout', 18) . '</button></form></div></header><main class="content">';
    foreach (wi_flash_pull() as $m) echo '<div class="alert alert-' . e($m[0]) . '">' . e($m[1]) . '</div>';
}

function wi_footer(): void { echo '</main><footer class="app-footer"><span>© ' . date('Y') . ' WebInsight Analytics</span><span>Website performance, made clear.</span></footer></div></div><script src="assets/app.js"></script></body></html>'; }

function wi_auth_header(string $title): void
{
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' – WebInsight</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-body">';
}

function wi_filter_form(array $f, array $sites, array $hidden = []): void
{
    echo '<form method="get" class="filters card">';
    foreach ($hidden as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    echo '<div class="filter-heading"><span class="filter-symbol">' . wi_icon('chart', 18) . '</span><div><strong>Bộ lọc dữ liệu</strong><small>Tùy chỉnh phạm vi thống kê</small></div></div>';
    echo '<label>Website<select name="site"><option value="0">Tất cả website</option>';
    foreach ($sites as $s) echo '<option value="' . (int)$s['id'] . '"' . ((int)$s['id'] === $f['site'] ? ' selected' : '') . '>' . e($s['name']) . '</option>';
    echo '</select></label><label>Từ ngày<input type="date" name="from" value="' . e($f['from']) . '"></label><label>Đến ngày<input type="date" name="to" value="' . e($f['to']) . '"></label><button class="btn" type="submit">Áp dụng bộ lọc</button></form>';
}

function wi_hbars(array $rows): string
{
    $max = 0; foreach ($rows as $r) $max = max($max, (int)$r[1]);
    if ($max === 0) return '<p class="empty">Chưa có dữ liệu trong khoảng thời gian này.</p>';
    $html = '<div class="hbars">';
    foreach ($rows as $r) { $w = round((int)$r[1] * 100 / $max, 1); $html .= '<div class="hbar"><span class="hbar-label">' . e($r[0]) . '</span><span class="hbar-track"><span class="hbar-fill" style="width:' . $w . '%"></span></span><span class="hbar-value">' . (int)$r[1] . '</span></div>'; }
    return $html . '</div>';
}

function wi_daily_chart(array $days): string
{
    $max = 0; foreach ($days as $d) $max = max($max, $d['pv'], $d['inter']);
    if ($max === 0) return '<p class="empty">Chưa có dữ liệu trong khoảng thời gian này.</p>';
    $html = '<div class="cols-wrap"><div class="cols">';
    foreach ($days as $d) { $h1 = round($d['pv'] * 100 / $max, 1); $h2 = round($d['inter'] * 100 / $max, 1); $html .= '<div class="col"><div class="col-bars"><span class="bar bar-a" style="height:' . $h1 . '%" title="Lượt xem: ' . $d['pv'] . '"></span><span class="bar bar-b" style="height:' . $h2 . '%" title="Tương tác: ' . $d['inter'] . '"></span></div><span class="col-label">' . e(date('d/m', strtotime($d['day']))) . '</span></div>'; }
    return $html . '</div></div><div class="legend"><span><i class="dot dot-a"></i> Lượt xem trang</span><span><i class="dot dot-b"></i> Lượt tương tác</span></div>';
}

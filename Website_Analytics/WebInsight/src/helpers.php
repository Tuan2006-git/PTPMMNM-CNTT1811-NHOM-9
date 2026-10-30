<?php
// Các hàm tiện ích dùng chung.

/** Escape HTML để chống XSS. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Danh sách sản phẩm cố định theo đề bài. */
function wi_products(): array
{
    return ['quan' => 'Quần', 'ao' => 'Áo', 'kinh' => 'Kính', 'giay' => 'Giày'];
}

/** Tên tiếng Việt của loại sự kiện. */
function wi_event_types(): array
{
    return ['pageview' => 'Xem trang', 'click' => 'Nhấp chuột', 'submit' => 'Gửi biểu mẫu'];
}

/** Ghi log lỗi; file log tự cắt bớt khi quá dung lượng cho phép. */
function wi_log(string $msg): void
{
    $file = (string) wi_cfg('log_file');
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $max = (int) wi_cfg('log_max_bytes', 262144);
    if (is_file($file) && filesize($file) > $max) {
        // Giữ lại nửa cuối của file, bỏ phần cũ
        $tail = (string) @file_get_contents($file, false, null, (int) ($max / 2));
        @file_put_contents($file, "[...đã cắt bớt log cũ...]\n" . $tail, LOCK_EX);
    }
    $msg = substr(str_replace(["\r", "\n"], ' ', $msg), 0, 500);
    @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

/** Chuyển hướng rồi dừng chương trình. */
function wi_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Thông báo flash: lưu trong session, hiển thị một lần. */
function wi_flash(string $type, string $message): void
{
    wi_session_start();
    $_SESSION['flash'][] = [$type, $message];
}

function wi_flash_pull(): array
{
    wi_session_start();
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/** CSRF token cho các biểu mẫu POST của trang quản trị. */
function csrf_token(): string
{
    wi_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Phiên làm việc không hợp lệ hoặc đã hết hạn. Hãy quay lại và tải lại trang.');
    }
}

/** Đọc / ghi cài đặt hệ thống. */
function wi_setting(PDO $pdo, string $name, string $default = ''): string
{
    $st = $pdo->prepare('SELECT value FROM settings WHERE name = ?');
    $st->execute([$name]);
    $v = $st->fetchColumn();
    return $v === false ? $default : (string) $v;
}

function wi_set_setting(PDO $pdo, string $name, string $value): void
{
    $st = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)
                         ON CONFLICT(name) DO UPDATE SET value = excluded.value');
    $st->execute([$name, $value]);
}

function wi_retention_days(PDO $pdo): int
{
    return max(1, (int) wi_setting($pdo, 'retention_days', (string) wi_cfg('default_retention_days', 90)));
}

/** Danh sách / chi tiết website. */
function wi_sites(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM websites ORDER BY id')->fetchAll();
}

function wi_site_get(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM websites WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Tạo API key ngẫu nhiên: "wi_" + 32 ký tự hex. */
function wi_new_api_key(): string
{
    return 'wi_' . bin2hex(random_bytes(16));
}

/** Chống CSV injection khi mở file bằng Excel/LibreOffice. */
function wi_csv_cell($value): string
{
    $s = (string) $value;
    if ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) {
        return "'" . $s;
    }
    return $s;
}

/** Xóa sự kiện cũ hơn $days ngày (tùy chọn chỉ trong một website). Trả về số dòng đã xóa. */
function wi_purge_old(PDO $pdo, int $days, ?int $siteId = null): int
{
    $limit = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));
    $sql = 'DELETE FROM events WHERE day < ?';
    $params = [$limit];
    if ($siteId !== null) {
        $sql .= ' AND website_id = ?';
        $params[] = $siteId;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

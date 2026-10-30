<?php
// Xử lý và kiểm tra sự kiện gửi từ tracker. Dùng chung cho API và bộ kiểm thử.

/**
 * Nhận một yêu cầu JSON, kiểm tra và ghi sự kiện.
 *
 * @param string      $raw    nội dung JSON thô
 * @param string|null $origin giá trị header Origin (nếu có)
 * @param bool        $isTest true = ghi dưới dạng dữ liệu kiểm thử (chỉ dùng nội bộ)
 * @return array      [mã HTTP, mảng kết quả]
 */
function wi_collect(PDO $pdo, string $raw, ?string $origin = null, bool $isTest = false): array
{
    // 1. Giới hạn kích thước
    if (strlen($raw) > (int) wi_cfg('max_payload_bytes', 4096)) {
        return [413, ['ok' => false, 'error' => 'payload_too_large']];
    }

    // 2. JSON hợp lệ
    $d = json_decode($raw, true);
    if (!is_array($d)) {
        return [400, ['ok' => false, 'error' => 'invalid_json']];
    }

    // 3. Kiểm tra định dạng các trường bắt buộc (chỉ nhận chuỗi)
    foreach (['api_key', 'event_id', 'session_id', 'type', 'target', 'path'] as $field) {
        if (!isset($d[$field]) || !is_string($d[$field])) {
            return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => $field]];
        }
    }
    if (!preg_match('/^wi_[a-f0-9]{32}$/', $d['api_key'])) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'api_key']];
    }
    if (!preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $d['event_id'])) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'event_id']];
    }
    if (!preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $d['session_id'])) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'session_id']];
    }
    if (!in_array($d['type'], ['pageview', 'click', 'submit'], true)) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'type']];
    }
    if (!preg_match('/^[A-Za-z0-9_\-\/\.:@]{1,150}$/', $d['target'])) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'target']];
    }
    if (strlen($d['path']) > 300 || !preg_match('/^\/[A-Za-z0-9_\-\/\.~%@:+]*$/', $d['path'])) {
        return [400, ['ok' => false, 'error' => 'invalid_field', 'field' => 'path']];
    }

    // 4. API key -> website
    $st = $pdo->prepare('SELECT id, url, active FROM websites WHERE api_key = ?');
    $st->execute([$d['api_key']]);
    $site = $st->fetch();
    if (!$site) {
        return [401, ['ok' => false, 'error' => 'invalid_api_key']];
    }
    if ((int) $site['active'] !== 1) {
        return [403, ['ok' => false, 'error' => 'website_disabled']];
    }

    // 5. Nếu trình duyệt gửi Origin thì tên miền phải khớp với URL đã đăng ký
    if (!$isTest && $origin !== null && $origin !== '' && $origin !== 'null') {
        $oh = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $sh = strtolower((string) parse_url((string) $site['url'], PHP_URL_HOST));
        $oh = preg_replace('/^www\./', '', $oh);
        $sh = preg_replace('/^www\./', '', $sh);
        if ($oh === '' || $oh !== $sh) {
            return [403, ['ok' => false, 'error' => 'origin_not_allowed']];
        }
    }

    // 6. Sự kiện phải khớp một quy tắc đang bật
    $st = $pdo->prepare('SELECT id, product FROM rules
                         WHERE website_id = ? AND event_type = ? AND target = ? AND active = 1');
    $st->execute([(int) $site['id'], $d['type'], $d['target']]);
    $rule = $st->fetch();
    if (!$rule) {
        return [422, ['ok' => false, 'error' => 'event_not_allowed']];
    }

    // 7. Giới hạn tần suất theo phiên
    if (!$isTest) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM events WHERE website_id = ? AND session_id = ? AND created_at >= ?');
        $st->execute([(int) $site['id'], $d['session_id'], date('Y-m-d H:i:s', time() - 60)]);
        if ((int) $st->fetchColumn() >= (int) wi_cfg('max_events_per_session_per_min', 120)) {
            return [429, ['ok' => false, 'error' => 'rate_limited']];
        }
    }

    // 8. Ghi sự kiện; INSERT OR IGNORE + UNIQUE(website_id, event_uid) => không đếm trùng
    $now = time();
    $st = $pdo->prepare('INSERT OR IGNORE INTO events
        (website_id, rule_id, event_uid, session_id, event_type, target, product, path, is_test, created_at, day)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $st->execute([
        (int) $site['id'], (int) $rule['id'], $d['event_id'], $d['session_id'], $d['type'], $d['target'],
        $rule['product'] !== null && $rule['product'] !== '' ? $rule['product'] : null,
        $d['path'], $isTest ? 1 : 0, date('Y-m-d H:i:s', $now), date('Y-m-d', $now),
    ]);
    if ($st->rowCount() === 0) {
        return [200, ['ok' => true, 'duplicate' => true]];
    }
    return [200, ['ok' => true, 'duplicate' => false]];
}

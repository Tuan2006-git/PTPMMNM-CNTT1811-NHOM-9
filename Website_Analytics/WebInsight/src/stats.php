<?php
// Truy vấn thống kê cho dashboard, báo cáo và phân tích chuỗi sự kiện.

function wi_valid_date($s): bool
{
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/** Đọc bộ lọc từ URL: website, khoảng ngày, loại dữ liệu (thật / kiểm thử). */
function wi_filters(): array
{
    $from = wi_valid_date($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-d', strtotime('-6 days'));
    $to   = wi_valid_date($_GET['to'] ?? null) ? $_GET['to'] : date('Y-m-d');
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [
        'site' => (int) ($_GET['site'] ?? 0),
        'from' => $from,
        'to'   => $to,
        'mode' => (($_GET['mode'] ?? 'real') === 'test') ? 'test' : 'real',
    ];
}

/** Tạo mệnh đề WHERE từ bộ lọc. Trả về [sql, params]. */
function wi_where(array $f): array
{
    $sql = 'day BETWEEN :from AND :to AND is_test = :test';
    $p = [':from' => $f['from'], ':to' => $f['to'], ':test' => $f['mode'] === 'test' ? 1 : 0];
    if ($f['site'] > 0) {
        $sql .= ' AND website_id = :site';
        $p[':site'] = $f['site'];
    }
    return [$sql, $p];
}

function wi_query_all(PDO $pdo, string $sql, array $params = []): array
{
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Tổng quan: lượt xem trang, số phiên, số lượt tương tác (không tính xem trang). */
function wi_totals(PDO $pdo, array $f): array
{
    [$w, $p] = wi_where($f);
    $row = wi_query_all($pdo,
        "SELECT
            COALESCE(SUM(event_type = 'pageview'), 0) AS pageviews,
            COALESCE(SUM(event_type <> 'pageview'), 0) AS interactions,
            COUNT(DISTINCT session_id) AS sessions
         FROM events WHERE $w", $p)[0];
    return ['pageviews' => (int) $row['pageviews'], 'interactions' => (int) $row['interactions'], 'sessions' => (int) $row['sessions']];
}

/** Thống kê theo ngày (điền 0 cho ngày không có dữ liệu). */
function wi_by_day(PDO $pdo, array $f): array
{
    [$w, $p] = wi_where($f);
    $rows = wi_query_all($pdo,
        "SELECT day,
            SUM(event_type = 'pageview') AS pv,
            SUM(event_type <> 'pageview') AS inter,
            COUNT(DISTINCT session_id) AS sess
         FROM events WHERE $w GROUP BY day", $p);
    $map = [];
    foreach ($rows as $r) {
        $map[$r['day']] = ['pv' => (int) $r['pv'], 'inter' => (int) $r['inter'], 'sess' => (int) $r['sess']];
    }
    $out = [];
    $t = strtotime($f['from']);
    $end = strtotime($f['to']);
    for ($i = 0; $t <= $end && $i < 366; $i++, $t = strtotime('+1 day', $t)) {
        $d = date('Y-m-d', $t);
        $out[] = ['day' => $d] + ($map[$d] ?? ['pv' => 0, 'inter' => 0, 'sess' => 0]);
    }
    return $out;
}

/** Thống kê theo trang. */
function wi_by_page(PDO $pdo, array $f): array
{
    [$w, $p] = wi_where($f);
    return wi_query_all($pdo,
        "SELECT path,
            SUM(event_type = 'pageview') AS pv,
            SUM(event_type <> 'pageview') AS inter
         FROM events WHERE $w GROUP BY path ORDER BY (pv + inter) DESC, path LIMIT 50", $p);
}

/** Thống kê theo loại sự kiện (luôn trả đủ 3 loại). */
function wi_by_type(PDO $pdo, array $f): array
{
    [$w, $p] = wi_where($f);
    $rows = wi_query_all($pdo, "SELECT event_type, COUNT(*) AS c FROM events WHERE $w GROUP BY event_type", $p);
    $out = array_fill_keys(array_keys(wi_event_types()), 0);
    foreach ($rows as $r) {
        $out[$r['event_type']] = (int) $r['c'];
    }
    return $out;
}

/** Thống kê theo sản phẩm: quần, áo, kính, giày. */
function wi_by_product(PDO $pdo, array $f): array
{
    [$w, $p] = wi_where($f);
    $rows = wi_query_all($pdo,
        "SELECT product,
            SUM(event_type = 'pageview') AS pv,
            SUM(event_type = 'click') AS clicks,
            SUM(event_type = 'submit') AS subs
         FROM events WHERE $w AND product IS NOT NULL AND product <> '' GROUP BY product", $p);
    $out = [];
    foreach (wi_products() as $key => $label) {
        $out[$key] = ['label' => $label, 'pv' => 0, 'clicks' => 0, 'subs' => 0];
    }
    foreach ($rows as $r) {
        if (isset($out[$r['product']])) {
            $out[$r['product']]['pv'] = (int) $r['pv'];
            $out[$r['product']]['clicks'] = (int) $r['clicks'];
            $out[$r['product']]['subs'] = (int) $r['subs'];
        }
    }
    return $out;
}

/**
 * Phân tích chuỗi sự kiện: đếm số phiên đi đúng thứ tự các bước.
 * Với mỗi phiên, duyệt sự kiện theo thời gian và tiến dần qua từng bước.
 */
function wi_funnel_run(PDO $pdo, array $funnel, array $f): array
{
    $ids = json_decode((string) $funnel['steps'], true);
    $ids = is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    $names = [];
    foreach ($ids as $i => $rid) {
        $st = $pdo->prepare('SELECT name FROM rules WHERE id = ?');
        $st->execute([$rid]);
        $n = $st->fetchColumn();
        $names[$i] = $n === false ? 'Quy tắc #' . $rid . ' (đã xóa)' : (string) $n;
    }
    $counts = array_fill(0, count($ids), 0);
    if (!$ids) {
        return ['names' => [], 'counts' => [], 'started' => 0, 'completed' => 0, 'rate' => 0.0];
    }

    $uniq = array_values(array_unique($ids));
    $ph = implode(',', array_fill(0, count($uniq), '?'));
    $params = array_merge(
        [(int) $funnel['website_id'], $f['mode'] === 'test' ? 1 : 0, $f['from'], $f['to']],
        $uniq
    );
    $st = $pdo->prepare(
        "SELECT session_id, rule_id FROM events
         WHERE website_id = ? AND is_test = ? AND day BETWEEN ? AND ? AND rule_id IN ($ph)
         ORDER BY session_id, id"
    );
    $st->execute($params);

    $cur = null;
    $k = 0;
    $n = count($ids);
    foreach ($st as $row) {
        if ($row['session_id'] !== $cur) {
            if ($cur !== null) {
                for ($i = 0; $i < $k; $i++) {
                    $counts[$i]++;
                }
            }
            $cur = $row['session_id'];
            $k = 0;
        }
        if ($k < $n && (int) $row['rule_id'] === $ids[$k]) {
            $k++;
        }
    }
    if ($cur !== null) {
        for ($i = 0; $i < $k; $i++) {
            $counts[$i]++;
        }
    }

    $started = $counts[0];
    $completed = $counts[$n - 1];
    return [
        'names'     => $names,
        'counts'    => $counts,
        'started'   => $started,
        'completed' => $completed,
        'rate'      => $started > 0 ? round($completed * 100 / $started, 1) : 0.0,
    ];
}

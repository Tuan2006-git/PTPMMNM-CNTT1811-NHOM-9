<?php
// Sinh dữ liệu kiểm thử (is_test = 1), tách biệt hoàn toàn với dữ liệu thật.

/**
 * Tạo $sessions phiên giả lập trong 7 ngày gần nhất dựa trên các quy tắc đang bật của website.
 * Trả về tổng số sự kiện đã ghi.
 */
function wi_generate_test_data(PDO $pdo, int $siteId, int $sessions): int
{
    $st = $pdo->prepare('SELECT * FROM rules WHERE website_id = ? AND active = 1');
    $st->execute([$siteId]);
    $byType = ['pageview' => [], 'click' => [], 'submit' => []];
    foreach ($st->fetchAll() as $r) {
        $byType[$r['event_type']][] = $r;
    }
    if (!$byType['pageview'] && !$byType['click'] && !$byType['submit']) {
        return 0;
    }

    $ins = $pdo->prepare('INSERT OR IGNORE INTO events
        (website_id, rule_id, event_uid, session_id, event_type, target, product, path, is_test, created_at, day)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)');
    $total = 0;
    $pdo->beginTransaction();
    try {
        for ($i = 0; $i < $sessions; $i++) {
            $sid = 'tst' . bin2hex(random_bytes(8));
            $t = strtotime('-' . random_int(0, 6) . ' days 08:00') + random_int(0, 36000);
            $seq = [];
            if ($byType['pageview']) {
                $seq[] = $byType['pageview'][array_rand($byType['pageview'])];
            }
            $clicked = false;
            if ($byType['click'] && random_int(1, 100) <= 70) {
                $n = random_int(1, min(2, count($byType['click'])));
                foreach ((array) array_rand($byType['click'], $n) as $idx) {
                    $seq[] = $byType['click'][$idx];
                    $clicked = true;
                }
            }
            if ($byType['submit'] && $clicked && random_int(1, 100) <= 40) {
                $seq[] = $byType['submit'][array_rand($byType['submit'])];
            }
            foreach ($seq as $r) {
                $t += random_int(5, 40);
                $ts = min($t, time());
                $path = $r['event_type'] === 'pageview' ? $r['target'] : '/';
                $ins->execute([
                    $siteId, (int) $r['id'], 'tst_' . bin2hex(random_bytes(8)), $sid,
                    $r['event_type'], $r['target'], $r['product'] ?: null, $path,
                    date('Y-m-d H:i:s', $ts), date('Y-m-d', $ts),
                ]);
                $total += $ins->rowCount();
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $total;
}

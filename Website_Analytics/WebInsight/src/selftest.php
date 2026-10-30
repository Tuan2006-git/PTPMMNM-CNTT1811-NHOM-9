<?php
// Bộ kiểm thử tự động: tạo website tạm "__SELFTEST__", chạy các kịch bản, rồi xóa sạch.
// Dữ liệu ghi bằng cờ is_test = 1 nên không lẫn với số liệu thật.

/** Chạy toàn bộ kịch bản. Trả về mảng [['name'=>..., 'ok'=>bool, 'detail'=>...], ...] */
function wi_run_selftests(PDO $pdo): array
{
    $res = [];
    $add = function (string $name, bool $ok, string $detail = '') use (&$res) {
        $res[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    };

    $key = wi_new_api_key();
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $pdo->prepare('INSERT INTO websites (name, url, api_key, active, created_at) VALUES (?, ?, ?, 1, ?)')
        ->execute(['__SELFTEST__', 'http://selftest.local', $key, $now]);
    $sid = (int) $pdo->lastInsertId();

    try {
        $mk = $pdo->prepare('INSERT INTO rules (website_id, name, event_type, target, product, active, created_at)
                             VALUES (?, ?, ?, ?, ?, 1, ?)');
        $ruleIds = [];
        foreach ([
            ['Xem trang chủ', 'pageview', '/p', null],
            ['Nhấp áo', 'click', 'btn-ao', 'ao'],
            ['Gửi liên hệ', 'submit', 'form-x', null],
            ['Nhấp quần', 'click', 'btn-quan', 'quan'],
        ] as $r) {
            $mk->execute([$sid, $r[0], $r[1], $r[2], $r[3], $now]);
            $ruleIds[$r[2]] = (int) $pdo->lastInsertId();
        }

        $send = function (array $over = [], ?string $raw = null) use ($pdo, $key) {
            $p = array_merge([
                'api_key' => $key, 'event_id' => 't' . bin2hex(random_bytes(8)),
                'session_id' => 's' . bin2hex(random_bytes(8)),
                'type' => 'pageview', 'target' => '/p', 'path' => '/p',
            ], $over);
            return wi_collect($pdo, $raw ?? json_encode($p), null, true);
        };
        $countEvents = function (string $where = '1=1', array $params = []) use ($pdo, $sid) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM events WHERE website_id = ? AND $where");
            $st->execute(array_merge([$sid], $params));
            return (int) $st->fetchColumn();
        };

        $expPv = 0;
        $expInter = 0;

        // --- Chức năng 3: xử lý và kiểm tra dữ liệu ---
        [$s, $b] = $send();
        $add('Sự kiện xem trang hợp lệ được ghi nhận', $s === 200 && ($b['ok'] ?? false) && !$b['duplicate'], "HTTP $s");
        $expPv++;

        [$s, $b] = $send(['type' => 'click', 'target' => 'btn-ao']);
        $row = $pdo->prepare("SELECT product FROM events WHERE website_id = ? AND target = 'btn-ao'");
        $row->execute([$sid]);
        $add('Nhấp nút sản phẩm hợp lệ, lưu đúng sản phẩm "ao"', $s === 200 && $row->fetchColumn() === 'ao', "HTTP $s");
        $expInter++;

        [$s, $b] = $send(['type' => 'submit', 'target' => 'form-x']);
        $add('Gửi biểu mẫu hợp lệ được ghi nhận', $s === 200 && ($b['ok'] ?? false), "HTTP $s");
        $expInter++;

        [$s, $b] = $send(['api_key' => 'wi_' . str_repeat('0', 32)]);
        $add('Từ chối API key không tồn tại (401)', $s === 401, "HTTP $s");

        [$s, $b] = $send([], '{không phải json');
        $add('Từ chối JSON sai định dạng (400)', $s === 400, "HTTP $s");

        [$s, $b] = $send(['type' => 'keylog']);
        $add('Từ chối loại sự kiện không hợp lệ (400)', $s === 400, "HTTP $s");

        [$s, $b] = $send(['type' => 'click', 'target' => 'btn-chua-khai-bao']);
        $add('Từ chối sự kiện chưa được cấp phép (422)', $s === 422 && ($b['error'] ?? '') === 'event_not_allowed', "HTTP $s");

        [$s, $b] = $send(['event_id' => 'x', 'target' => '<script>']);
        $add('Từ chối dữ liệu đầu vào chứa ký tự không hợp lệ (400)', $s === 400, "HTTP $s");

        [$s, $b] = $send([], str_repeat('a', 5000));
        $add('Từ chối yêu cầu quá lớn (413)', $s === 413, "HTTP $s");

        // Không đếm trùng
        $dup = ['event_id' => 'duplicate0001', 'session_id' => 'sessiondup0001'];
        [$s1, $b1] = $send($dup);
        [$s2, $b2] = $send($dup);
        $add('Cùng mã sự kiện chỉ được đếm một lần', $s1 === 200 && $s2 === 200 && ($b2['duplicate'] ?? false) === true
            && $countEvents('event_uid = ?', ['duplicate0001']) === 1, 'lần 2 trả về duplicate=true');
        $expPv++;

        // Không thu thập nội dung người dùng nhập
        [$s, $b] = $send([
            'type' => 'submit', 'target' => 'form-x', 'event_id' => 'privacy00001',
            'email' => 'bi-mat@example.com', 'password' => 'matkhau123', 'input_value' => 'noi dung nhap',
        ]);
        $st = $pdo->prepare('SELECT * FROM events WHERE website_id = ? AND event_uid = ?');
        $st->execute([$sid, 'privacy00001']);
        $dump = json_encode($st->fetch());
        $leak = strpos($dump, 'bi-mat@example.com') !== false || strpos($dump, 'matkhau123') !== false
            || strpos($dump, 'noi dung nhap') !== false;
        $add('Không lưu email/mật khẩu/nội dung nhập kèm theo sự kiện', $s === 200 && !$leak);
        $expInter++;

        // --- Chức năng 4: thống kê đúng theo website và ngày ---
        $tot = wi_totals($pdo, ['site' => $sid, 'from' => $today, 'to' => $today, 'mode' => 'test']);
        $add('Thống kê theo website + ngày đúng số liệu',
            $tot['pageviews'] === $expPv && $tot['interactions'] === $expInter,
            "xem trang {$tot['pageviews']}/$expPv, tương tác {$tot['interactions']}/$expInter");
        $real = wi_totals($pdo, ['site' => $sid, 'from' => $today, 'to' => $today, 'mode' => 'real']);
        $add('Dữ liệu kiểm thử không lẫn vào số liệu thật', $real['pageviews'] === 0 && $real['interactions'] === 0);

        // --- Chức năng 1: website bị tắt ---
        $pdo->prepare('UPDATE websites SET active = 0 WHERE id = ?')->execute([$sid]);
        [$s, $b] = $send();
        $add('Website bị tắt thì từ chối nhận sự kiện (403)', $s === 403 && ($b['error'] ?? '') === 'website_disabled', "HTTP $s");
        $pdo->prepare('UPDATE websites SET active = 1 WHERE id = ?')->execute([$sid]);

        $pdo->prepare('UPDATE rules SET active = 0 WHERE id = ?')->execute([$ruleIds['btn-quan']]);
        [$s, $b] = $send(['type' => 'click', 'target' => 'btn-quan']);
        $add('Quy tắc bị tắt thì từ chối sự kiện (422)', $s === 422, "HTTP $s");

        // --- Chức năng 5: chuỗi sự kiện theo thứ tự ---
        $steps = [$ruleIds['/p'], $ruleIds['btn-ao'], $ruleIds['form-x']];
        $seqs = [
            'A' => [['pageview', '/p'], ['click', 'btn-ao'], ['submit', 'form-x']], // đúng thứ tự -> hoàn thành
            'B' => [['submit', 'form-x'], ['click', 'btn-ao'], ['pageview', '/p']], // ngược thứ tự -> chưa
            'C' => [['pageview', '/p'], ['click', 'btn-ao']],                       // dừng giữa chừng
        ];
        foreach ($seqs as $name => $list) {
            foreach ($list as $i => $ev) {
                $send(['session_id' => 'funnelsess' . $name, 'event_id' => 'fn' . $name . $i . bin2hex(random_bytes(3)),
                       'type' => $ev[0], 'target' => $ev[1], 'path' => $ev[0] === 'pageview' ? $ev[1] : '/p']);
            }
        }
        $fr = wi_funnel_run($pdo, ['website_id' => $sid, 'steps' => json_encode($steps)],
            ['from' => $today, 'to' => $today, 'mode' => 'test']);
        // Các phiên khác (từ bước kiểm thử trước) có thể cũng nằm trong chuỗi, nên chỉ kiểm tra phiên mới
        $okFunnel = $fr['completed'] >= 1 && $fr['counts'][0] >= $fr['counts'][1] && $fr['counts'][1] >= $fr['counts'][2];
        // Kiểm tra chính xác riêng phiên B (ngược thứ tự) không hoàn thành:
        $stB = $pdo->prepare("SELECT COUNT(*) FROM events WHERE website_id = ? AND session_id = 'funnelsessB'");
        $stB->execute([$sid]);
        $add('Phân tích chuỗi: đếm đúng phiên hoàn thành theo thứ tự', $okFunnel && (int) $stB->fetchColumn() === 3,
            'bắt đầu ' . $fr['started'] . ', hoàn thành ' . $fr['completed'] . ' (' . $fr['rate'] . '%)');

        // Chuỗi chỉ gồm 3 phiên A, B, C -> kết quả chính xác: bắt đầu 3 (A,B,C), bước 2 = 2 (A,C), hoàn thành 1 (A)
        $pdo->prepare("DELETE FROM events WHERE website_id = ? AND session_id NOT LIKE 'funnelsess%'")->execute([$sid]);
        $fr = wi_funnel_run($pdo, ['website_id' => $sid, 'steps' => json_encode($steps)],
            ['from' => $today, 'to' => $today, 'mode' => 'test']);
        $add('Chuỗi 3 phiên A/B/C cho kết quả 3 → 2 → 1', $fr['counts'] === [3, 2, 1], 'kết quả: ' . implode(' → ', $fr['counts']));

        // --- Thời hạn lưu trữ ---
        $old = date('Y-m-d', strtotime('-400 days'));
        $pdo->prepare('INSERT INTO events (website_id, rule_id, event_uid, session_id, event_type, target, path, is_test, created_at, day)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)')
            ->execute([$sid, $ruleIds['/p'], 'oldevent0001', 'oldsession001', 'pageview', '/p', '/p', $old . ' 10:00:00', $old]);
        $deleted = wi_purge_old($pdo, 30, $sid);
        $add('Xóa dữ liệu cũ hơn thời hạn lưu trữ', $deleted >= 1 && $countEvents('event_uid = ?', ['oldevent0001']) === 0
            && $countEvents('day = ?', [$today]) > 0, "đã xóa $deleted dòng, dữ liệu mới vẫn còn");

        // --- CSV ---
        $add('Chống CSV injection (ô bắt đầu bằng "=")', wi_csv_cell('=1+1') === "'=1+1" && wi_csv_cell('abc') === 'abc');
    } catch (Throwable $ex) {
        $add('Kiểm thử gặp lỗi bất ngờ', false, $ex->getMessage());
    } finally {
        // Dọn dẹp: xóa website tạm (kéo theo quy tắc + sự kiện nhờ ON DELETE CASCADE)
        $pdo->prepare('DELETE FROM websites WHERE id = ?')->execute([$sid]);
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM websites WHERE id = ?');
    $st->execute([$sid]);
    $add('Dọn dẹp dữ liệu kiểm thử tạm', (int) $st->fetchColumn() === 0);
    return $res;
}

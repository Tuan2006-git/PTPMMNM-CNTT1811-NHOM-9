<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();
$f = wi_filters();
$sites = wi_sites($pdo);

$reportTypes = [
    'daily'    => 'Thống kê theo ngày',
    'pages'    => 'Thống kê theo trang',
    'products' => 'Thống kê theo sản phẩm',
    'types'    => 'Thống kê theo loại sự kiện',
    'funnels'  => 'Kết quả chuỗi sự kiện',
    'events'   => 'Danh sách sự kiện chi tiết',
];
$type = (string) ($_GET['type'] ?? 'daily');
if (!isset($reportTypes[$type])) {
    $type = 'daily';
}

/** Tạo [tiêu đề cột, các dòng] cho loại báo cáo đã chọn. */
function wi_report_data(PDO $pdo, string $type, array $f, int $limit): array
{
    switch ($type) {
        case 'pages':
            $rows = [];
            foreach (wi_by_page($pdo, $f) as $r) {
                $rows[] = [$r['path'], $r['pv'], $r['inter']];
            }
            return [['Trang', 'Lượt xem', 'Tương tác'], $rows];
        case 'products':
            $rows = [];
            foreach (wi_by_product($pdo, $f) as $r) {
                $rows[] = [$r['label'], $r['pv'], $r['clicks'], $r['subs']];
            }
            return [['Sản phẩm', 'Lượt xem', 'Lượt nhấp', 'Gửi biểu mẫu'], $rows];
        case 'types':
            $rows = [];
            foreach (wi_by_type($pdo, $f) as $k => $c) {
                $rows[] = [wi_event_types()[$k], $c];
            }
            return [['Loại sự kiện', 'Số lượng'], $rows];
        case 'funnels':
            $rows = [];
            $sql = 'SELECT * FROM funnels' . ($f['site'] > 0 ? ' WHERE website_id = ' . (int) $f['site'] : '') . ' ORDER BY id';
            foreach ($pdo->query($sql)->fetchAll() as $fn) {
                $r = wi_funnel_run($pdo, $fn, $f);
                foreach ($r['names'] as $i => $n) {
                    $rows[] = [$fn['name'], $i + 1, $n, $r['counts'][$i], $r['rate'] . '%'];
                }
            }
            return [['Chuỗi', 'Bước', 'Tên bước', 'Số phiên', 'Tỷ lệ hoàn thành chuỗi'], $rows];
        case 'events':
            [$w, $p] = wi_where($f);
            $names = [];
            foreach (wi_sites($pdo) as $s) {
                $names[(int) $s['id']] = $s['name'];
            }
            $st = $pdo->prepare("SELECT created_at, website_id, event_type, target, product, path, session_id, event_uid
                                 FROM events WHERE $w ORDER BY id DESC LIMIT " . (int) $limit);
            $st->execute($p);
            $rows = [];
            foreach ($st->fetchAll() as $r) {
                $rows[] = [$r['created_at'], $names[(int) $r['website_id']] ?? '?', $r['event_type'], $r['target'], $r['product'] ?? '', $r['path'], $r['session_id'], $r['event_uid']];
            }
            return [['Thời gian', 'Website', 'Loại', 'Mục tiêu', 'Sản phẩm', 'Trang', 'Phiên', 'Mã sự kiện'], $rows];
        default: // daily
            $rows = [];
            foreach (wi_by_day($pdo, $f) as $r) {
                $rows[] = [$r['day'], $r['pv'], $r['inter'], $r['sess']];
            }
            return [['Ngày', 'Lượt xem trang', 'Lượt tương tác', 'Số phiên'], $rows];
    }
}

// Tải CSV
if (isset($_GET['download'])) {
    [$head, $rows] = wi_report_data($pdo, $type, $f, 50000);
    $fname = 'webinsight_' . $type . '_' . $f['from'] . '_' . $f['to'] . ($f['mode'] === 'test' ? '_test' : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
    fputcsv($out, $head, ',', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($out, array_map('wi_csv_cell', $r), ',', '"', '\\');
    }
    fclose($out);
    exit;
}

[$head, $rows] = wi_report_data($pdo, $type, $f, 50);
$qs = http_build_query(['site' => $f['site'], 'from' => $f['from'], 'to' => $f['to'], 'mode' => $f['mode'], 'type' => $type, 'download' => 1]);

wi_header('Xuất báo cáo', 'reports');
wi_filter_form($f, $sites, ['type' => $type]);
?>
<div class="card">
  <form method="get" class="form-row">
    <input type="hidden" name="site" value="<?= (int) $f['site'] ?>">
    <input type="hidden" name="from" value="<?= e($f['from']) ?>">
    <input type="hidden" name="to" value="<?= e($f['to']) ?>">
    <input type="hidden" name="mode" value="<?= e($f['mode']) ?>">
    <label>Loại báo cáo<select name="type" onchange="this.form.submit()">
      <?php foreach ($reportTypes as $k => $label): ?><option value="<?= e($k) ?>"<?= $k === $type ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></label>
    <noscript><button class="btn" type="submit">Xem</button></noscript>
    <a class="btn" href="reports.php?<?= e($qs) ?>"><?= wi_icon('download', 16) ?> Tải CSV</a>
  </form>
  <p class="hint">Bộ lọc website, thời gian và loại dữ liệu ở trên cũng áp dụng cho file CSV.</p>
</div>
<div class="card">
  <h2>Xem trước: <?= e($reportTypes[$type]) ?><?= $type === 'events' ? ' (50 dòng mới nhất)' : '' ?></h2>
  <?php if (!$rows): ?><p class="empty">Không có dữ liệu cho bộ lọc hiện tại.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($rows as $r): ?><tr><?php foreach ($r as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php wi_footer();

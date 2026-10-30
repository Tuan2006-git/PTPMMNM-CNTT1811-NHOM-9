<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();
$f = wi_filters();
$sites = wi_sites($pdo);

$tot   = wi_totals($pdo, $f);
$days  = wi_by_day($pdo, $f);
$pages = wi_by_page($pdo, $f);
$types = wi_by_type($pdo, $f);
$prods = wi_by_product($pdo, $f);

// Tên website hiển thị cạnh biểu tượng lượt truy cập
$siteLabel = 'Tất cả website';
if ($f['site'] > 0) {
    $s = wi_site_get($pdo, $f['site']);
    $siteLabel = $s ? $s['name'] : 'Website không tồn tại';
} elseif ($sites) {
    $siteLabel = implode(', ', array_column($sites, 'name'));
}

// So sánh hai ngày bất kỳ
$d1 = wi_valid_date($_GET['d1'] ?? null) ? $_GET['d1'] : date('Y-m-d', strtotime('-1 day'));
$d2 = wi_valid_date($_GET['d2'] ?? null) ? $_GET['d2'] : date('Y-m-d');
$c1 = wi_totals($pdo, ['site' => $f['site'], 'from' => $d1, 'to' => $d1, 'mode' => $f['mode']]);
$c2 = wi_totals($pdo, ['site' => $f['site'], 'from' => $d2, 'to' => $d2, 'mode' => $f['mode']]);

/** Hiển thị % thay đổi giữa hai số */
function wi_delta($old, $new): string
{
    if ($old == 0) {
        return $new == 0 ? '–' : '<span class="delta-up">mới</span>';
    }
    $p = round(($new - $old) * 100 / $old);
    $cls = $p >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($p >= 0 ? '+' : '') . $p . '%</span>';
}

wi_header('Tổng quan', 'dashboard');
wi_filter_form($f, $sites);
if ($f['mode'] === 'test') {
    echo '<div class="alert alert-info">Đang xem <strong>dữ liệu kiểm thử</strong> (tách biệt với dữ liệu thật).</div>';
}
?>
<div class="grid grid-3">
  <div class="card stat">
    <div class="stat-icon"><?= wi_icon('person', 28) ?></div>
    <div>
      <div class="stat-label">Lượt xem trang</div>
      <div class="stat-value"><?= number_format($tot['pageviews']) ?></div>
      <span class="chip" title="<?= e($siteLabel) ?>"><?= wi_icon('globe', 12) ?> <?= e($siteLabel) ?></span>
    </div>
  </div>
  <div class="card stat">
    <div class="stat-icon"><?= wi_icon('session', 28) ?></div>
    <div>
      <div class="stat-label">Số phiên có hoạt động</div>
      <div class="stat-value"><?= number_format($tot['sessions']) ?></div>
      <span class="stat-label"><?= e(date('d/m/Y', strtotime($f['from']))) ?> – <?= e(date('d/m/Y', strtotime($f['to']))) ?></span>
    </div>
  </div>
  <div class="card stat">
    <div class="stat-icon"><?= wi_icon('click', 28) ?></div>
    <div>
      <div class="stat-label">Số lượt tương tác</div>
      <div class="stat-value"><?= number_format($tot['interactions']) ?></div>
      <span class="stat-label">Không tính lượt xem trang</span>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <h2>Thống kê theo ngày</h2>
  <?= wi_daily_chart($days) ?>
  <div class="table-wrap" style="margin-top:12px"><table>
    <thead><tr><th>Ngày</th><th class="num">Lượt xem trang</th><th class="num">So với hôm trước</th><th class="num">Lượt tương tác</th><th class="num">Số phiên</th></tr></thead>
    <tbody>
    <?php foreach (array_reverse(array_keys($days)) as $i): $d = $days[$i]; $prev = $days[$i - 1]['pv'] ?? null; ?>
      <tr><td><?= e(date('d/m/Y', strtotime($d['day']))) ?></td><td class="num"><?= $d['pv'] ?></td>
        <td class="num"><?= $prev === null ? '–' : wi_delta($prev, $d['pv']) ?></td>
        <td class="num"><?= $d['inter'] ?></td><td class="num"><?= $d['sess'] ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>

<div class="card">
  <h2>So sánh hai ngày</h2>
  <form method="get" class="form-row">
    <input type="hidden" name="site" value="<?= (int) $f['site'] ?>">
    <input type="hidden" name="from" value="<?= e($f['from']) ?>">
    <input type="hidden" name="to" value="<?= e($f['to']) ?>">
    <input type="hidden" name="mode" value="<?= e($f['mode']) ?>">
    <label>Ngày thứ nhất<input type="date" name="d1" value="<?= e($d1) ?>"></label>
    <label>Ngày thứ hai<input type="date" name="d2" value="<?= e($d2) ?>"></label>
    <button class="btn" type="submit">So sánh</button>
  </form>
  <div class="table-wrap" style="margin-top:12px"><table>
    <thead><tr><th>Chỉ số</th><th class="num"><?= e(date('d/m/Y', strtotime($d1))) ?></th><th class="num"><?= e(date('d/m/Y', strtotime($d2))) ?></th><th class="num">Thay đổi</th></tr></thead>
    <tbody>
      <tr><td>Lượt xem trang</td><td class="num"><?= $c1['pageviews'] ?></td><td class="num"><?= $c2['pageviews'] ?></td><td class="num"><?= wi_delta($c1['pageviews'], $c2['pageviews']) ?></td></tr>
      <tr><td>Số phiên</td><td class="num"><?= $c1['sessions'] ?></td><td class="num"><?= $c2['sessions'] ?></td><td class="num"><?= wi_delta($c1['sessions'], $c2['sessions']) ?></td></tr>
      <tr><td>Lượt tương tác</td><td class="num"><?= $c1['interactions'] ?></td><td class="num"><?= $c2['interactions'] ?></td><td class="num"><?= wi_delta($c1['interactions'], $c2['interactions']) ?></td></tr>
    </tbody></table></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Thống kê theo sản phẩm (lượt nhấp)</h2>
    <?php $rows = []; foreach ($prods as $p) { $rows[] = [$p['label'], $p['clicks']]; } echo wi_hbars($rows); ?>
  </div>
  <div class="card">
    <h2>Thống kê theo loại sự kiện</h2>
    <?php $rows = []; foreach (wi_event_types() as $k => $label) { $rows[] = [$label, $types[$k]]; } echo wi_hbars($rows); ?>
  </div>
</div>

<div class="card">
  <h2>Thống kê theo trang</h2>
  <?php if (!$pages): ?><p class="empty">Chưa có dữ liệu. Hãy mở <a href="demo.php" target="_blank" rel="noopener">website demo</a> và thao tác để phát sinh sự kiện.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Trang</th><th class="num">Lượt xem</th><th class="num">Tương tác</th></tr></thead>
    <tbody><?php foreach ($pages as $p): ?>
      <tr><td><code><?= e($p['path']) ?></code></td><td class="num"><?= (int) $p['pv'] ?></td><td class="num"><?= (int) $p['inter'] ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php wi_footer();

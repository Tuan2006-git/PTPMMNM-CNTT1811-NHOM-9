<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();
$sites = wi_sites($pdo);
$testResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'selftest') {
        $testResults = wi_run_selftests($pdo);
    } else {
        if ($action === 'gen') {
            $siteId = (int) ($_POST['site'] ?? 0);
            $n = max(1, min(2000, (int) ($_POST['count'] ?? 50)));
            if (!wi_site_get($pdo, $siteId)) {
                wi_flash('error', 'Hãy chọn website.');
            } else {
                $total = wi_generate_test_data($pdo, $siteId, $n);
                wi_flash($total > 0 ? 'success' : 'error',
                    $total > 0 ? "Đã tạo $total sự kiện kiểm thử (xem bằng bộ lọc \"Dữ liệu kiểm thử\")." : 'Website chưa có quy tắc đang bật nên không tạo được dữ liệu.');
            }
        } elseif ($action === 'del_test') {
            $st = $pdo->prepare('DELETE FROM events WHERE is_test = 1');
            $st->execute();
            wi_flash('success', 'Đã xóa ' . $st->rowCount() . ' sự kiện kiểm thử.');
        } elseif ($action === 'retention') {
            $days = (int) ($_POST['days'] ?? 0);
            if ($days < 1 || $days > 3650) {
                wi_flash('error', 'Thời hạn lưu trữ phải từ 1 đến 3650 ngày.');
            } else {
                wi_set_setting($pdo, 'retention_days', (string) $days);
                wi_flash('success', "Đã đặt thời hạn lưu trữ: $days ngày.");
            }
        } elseif ($action === 'purge') {
            $days = wi_retention_days($pdo);
            wi_flash('success', 'Đã xóa ' . wi_purge_old($pdo, $days) . " sự kiện cũ hơn $days ngày.");
        } elseif ($action === 'del_real') {
            if (trim((string) ($_POST['confirm'] ?? '')) !== 'XOA') {
                wi_flash('error', 'Cần gõ đúng chữ XOA để xác nhận.');
            } else {
                $st = $pdo->prepare('DELETE FROM events WHERE is_test = 0');
                $st->execute();
                wi_flash('success', 'Đã xóa ' . $st->rowCount() . ' sự kiện thật.');
            }
        }
        wi_redirect('tools.php');
    }
}

$cnt = $pdo->query('SELECT SUM(is_test = 0) AS real_c, SUM(is_test = 1) AS test_c, MIN(day) AS first_day FROM events')->fetch();
$logFile = (string) wi_cfg('log_file');
$logSize = is_file($logFile) ? filesize($logFile) : 0;

wi_header('Công cụ kiểm thử & quản lý dữ liệu', 'tools');
?>
<div class="grid grid-3">
  <div class="card"><div class="stat-label">Sự kiện thật</div><div class="stat-value"><?= (int) $cnt['real_c'] ?></div></div>
  <div class="card"><div class="stat-label">Sự kiện kiểm thử</div><div class="stat-value"><?= (int) $cnt['test_c'] ?></div></div>
  <div class="card"><div class="stat-label">Dung lượng log lỗi</div><div class="stat-value"><?= number_format($logSize / 1024, 1) ?> KB</div>
    <span class="hint">Giới hạn <?= number_format(((int) wi_cfg('log_max_bytes')) / 1024) ?> KB, tự cắt bớt</span></div>
</div>

<div class="card">
  <h2>1. Chạy kịch bản kiểm thử tự động</h2>
  <p class="hint">Tạo một website tạm, gửi sự kiện hợp lệ / không hợp lệ / trùng lặp / website bị tắt, kiểm tra kết quả rồi xóa sạch dữ liệu tạm. Không ảnh hưởng số liệu thật.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="selftest"><button class="btn" type="submit"><?= wi_icon('flask', 16) ?> Chạy kiểm thử</button></form>
  <?php if ($testResults !== null):
      $pass = count(array_filter($testResults, fn($r) => $r['ok'])); ?>
    <div class="alert alert-<?= $pass === count($testResults) ? 'success' : 'error' ?>" style="margin-top:14px">
      Kết quả: <strong><?= $pass ?>/<?= count($testResults) ?></strong> kịch bản đạt.</div>
    <div class="table-wrap"><table>
      <thead><tr><th>#</th><th>Kịch bản</th><th>Kết quả</th><th>Chi tiết</th></tr></thead>
      <tbody><?php foreach ($testResults as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><td><?= e($r['name']) ?></td>
          <td><span class="badge <?= $r['ok'] ? 'badge-pass' : 'badge-fail' ?>"><?= $r['ok'] ? 'ĐẠT' : 'LỖI' ?></span></td>
          <td><?= e($r['detail']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>2. Tạo dữ liệu kiểm thử (tách biệt với dữ liệu thật)</h2>
  <form method="post" class="form-row"><?= csrf_field() ?><input type="hidden" name="action" value="gen">
    <label>Website<select name="site"><?php foreach ($sites as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
    <label>Số phiên cần tạo (1–2000)<input type="number" name="count" min="1" max="2000" value="50"></label>
    <button class="btn" type="submit">Tạo dữ liệu kiểm thử</button>
  </form>
  <p class="hint">Dữ liệu được rải trong 7 ngày gần nhất theo các quy tắc đang bật. Xem tại Tổng quan bằng bộ lọc "Dữ liệu kiểm thử".</p>
  <form method="post" class="inline" data-confirm="Xóa toàn bộ dữ liệu kiểm thử?" style="display:block;margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="del_test">
    <button class="btn btn-ghost" type="submit">Xóa toàn bộ dữ liệu kiểm thử</button></form>
</div>

<div class="card">
  <h2>3. Thời hạn lưu trữ & xóa dữ liệu cũ</h2>
  <form method="post" class="form-row"><?= csrf_field() ?><input type="hidden" name="action" value="retention">
    <label>Giữ dữ liệu trong (ngày)<input type="number" name="days" min="1" max="3650" value="<?= wi_retention_days($pdo) ?>"></label>
    <button class="btn" type="submit">Lưu thời hạn</button>
  </form>
  <form method="post" class="inline" data-confirm="Xóa các sự kiện cũ hơn thời hạn lưu trữ?" style="display:block;margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="purge">
    <button class="btn btn-ghost" type="submit">Xóa dữ liệu cũ ngay bây giờ</button></form>
  <p class="hint">Ngày có dữ liệu sớm nhất: <?= e($cnt['first_day'] ?? 'chưa có') ?>. Cũng có thể chạy <code>./lab.sh purge</code> (VD đặt vào cron).</p>
</div>

<div class="card">
  <h2>4. Xóa toàn bộ dữ liệu thật</h2>
  <form method="post" class="form-row" data-confirm="Hành động này không thể hoàn tác. Tiếp tục?"><?= csrf_field() ?><input type="hidden" name="action" value="del_real">
    <label>Gõ <strong>XOA</strong> để xác nhận<input type="text" name="confirm" autocomplete="off"></label>
    <button class="btn btn-danger" type="submit">Xóa dữ liệu thật</button>
  </form>
</div>
<?php wi_footer();

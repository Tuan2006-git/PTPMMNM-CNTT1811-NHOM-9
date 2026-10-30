<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();
$sites = wi_sites($pdo);
$f = wi_filters();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $back = 'funnels.php?' . http_build_query(['site' => (int) ($_POST['site'] ?? 0), 'from' => $f['from'], 'to' => $f['to'], 'mode' => $f['mode']]);
    if ($action === 'add') {
        $siteId = (int) ($_POST['site'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $steps = [];
        foreach ((array) ($_POST['steps'] ?? []) as $sid) {
            if ((int) $sid > 0) {
                $steps[] = (int) $sid;
            }
        }
        // Mỗi bước phải là quy tắc của đúng website
        $okSteps = true;
        foreach ($steps as $sid) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM rules WHERE id = ? AND website_id = ?');
            $st->execute([$sid, $siteId]);
            if ((int) $st->fetchColumn() === 0) {
                $okSteps = false;
            }
        }
        if (!wi_site_get($pdo, $siteId)) {
            wi_flash('error', 'Hãy chọn một website cụ thể.');
        } elseif ($name === '' || strlen($name) > 100) {
            wi_flash('error', 'Tên chuỗi không được để trống (tối đa 100 ký tự).');
        } elseif (count($steps) < 2) {
            wi_flash('error', 'Chuỗi cần ít nhất 2 bước.');
        } elseif (!$okSteps) {
            wi_flash('error', 'Có bước không thuộc website đã chọn.');
        } else {
            $pdo->prepare('INSERT INTO funnels (website_id, name, steps, created_at) VALUES (?, ?, ?, ?)')
                ->execute([$siteId, $name, json_encode($steps), date('Y-m-d H:i:s')]);
            wi_flash('success', 'Đã tạo chuỗi sự kiện.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        wi_flash('success', 'Đã xóa chuỗi sự kiện.');
    }
    wi_redirect($back);
}

// Danh sách quy tắc của website đang chọn để tạo chuỗi mới
$siteForNew = $f['site'] > 0 ? $f['site'] : ($sites ? (int) $sites[0]['id'] : 0);
$rulesForNew = [];
if ($siteForNew) {
    $st = $pdo->prepare('SELECT id, name, event_type, target FROM rules WHERE website_id = ? AND active = 1 ORDER BY id');
    $st->execute([$siteForNew]);
    $rulesForNew = $st->fetchAll();
}
$sql = 'SELECT * FROM funnels' . ($f['site'] > 0 ? ' WHERE website_id = ' . (int) $f['site'] : '') . ' ORDER BY id DESC';
$funnels = $pdo->query($sql)->fetchAll();

wi_header('Phân tích chuỗi sự kiện', 'funnels');
wi_filter_form($f, $sites);
?>
<div class="card">
  <h2>Tạo chuỗi sự kiện mới (website: <?= e(wi_site_get($pdo, $siteForNew)['name'] ?? '—') ?>)</h2>
  <?php if (count($rulesForNew) < 2): ?>
    <p class="empty">Cần ít nhất 2 quy tắc đang bật. Hãy thêm ở trang <a href="rules.php">Quy tắc sự kiện</a>.</p>
  <?php else: ?>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="site" value="<?= $siteForNew ?>">
    <label>Tên chuỗi<input type="text" name="name" maxlength="100" required placeholder="VD: Xem trang → Nhấp áo → Gửi liên hệ"></label>
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <label>Bước <?= $i ?><?= $i > 2 ? ' (tùy chọn)' : '' ?>
        <select name="steps[]"<?= $i <= 2 ? ' required' : '' ?>><option value="">—</option>
          <?php foreach ($rulesForNew as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?> (<?= e($r['event_type']) ?>: <?= e($r['target']) ?>)</option><?php endforeach; ?>
        </select></label>
    <?php endfor; ?>
    <button class="btn" type="submit">Tạo chuỗi</button>
  </form>
  <p class="hint">Phiên được tính là hoàn thành khi thực hiện các bước đúng thứ tự (có thể xen kẽ sự kiện khác ở giữa). Để chọn website khác, dùng bộ lọc phía trên.</p>
  <?php endif; ?>
</div>

<?php if (!$funnels): ?>
  <div class="card"><p class="empty">Chưa có chuỗi sự kiện nào.</p></div>
<?php endif; ?>
<?php foreach ($funnels as $fn): $r = wi_funnel_run($pdo, $fn, $f); $sname = wi_site_get($pdo, (int) $fn['website_id'])['name'] ?? '?'; ?>
<div class="card">
  <div class="form-row" style="align-items:center">
    <h2 style="margin:0;flex:1"><?= e($fn['name']) ?> <span class="chip"><?= e($sname) ?></span></h2>
    <form method="post" class="inline" data-confirm="Xóa chuỗi này?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $fn['id'] ?>"><input type="hidden" name="site" value="<?= (int) $f['site'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Xóa</button></form>
  </div>
  <p><strong><?= $r['completed'] ?></strong> / <?= $r['started'] ?> phiên hoàn thành chuỗi (<strong><?= $r['rate'] ?>%</strong>)</p>
  <?php foreach ($r['names'] as $i => $n): $w = $r['started'] > 0 ? max(4, round($r['counts'][$i] * 100 / $r['started'])) : 4; ?>
    <div class="funnel-step"><small>Bước <?= $i + 1 ?>: <?= e($n) ?></small>
      <div class="funnel-bar" style="width:<?= $w ?>%"><?= $r['counts'][$i] ?> phiên</div></div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php wi_footer();

<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();
$sites = wi_sites($pdo);
$siteId = (int) ($_GET['site'] ?? ($_POST['site'] ?? 0));
if ($siteId <= 0 && $sites) {
    $siteId = (int) $sites[0]['id'];
}
$site = $siteId > 0 ? wi_site_get($pdo, $siteId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $site) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $type = (string) ($_POST['event_type'] ?? '');
    $target = trim((string) ($_POST['target'] ?? ''));
    $product = (string) ($_POST['product'] ?? '');

    if ($action === 'add' || $action === 'update') {
        $err = '';
        if ($name === '' || strlen($name) > 100) {
            $err = 'Tên quy tắc không được để trống (tối đa 100 ký tự).';
        } elseif (!array_key_exists($type, wi_event_types())) {
            $err = 'Loại sự kiện không hợp lệ.';
        } elseif (!preg_match('/^[A-Za-z0-9_\-\/\.:@]{1,150}$/', $target)) {
            $err = 'Mục tiêu chỉ gồm chữ, số và các ký tự _ - / . : @ (tối đa 150 ký tự).';
        } elseif ($type === 'pageview' && $target[0] !== '/') {
            $err = 'Với quy tắc xem trang, mục tiêu phải là đường dẫn bắt đầu bằng "/" (ví dụ /demo.php).';
        } elseif ($product !== '' && !array_key_exists($product, wi_products())) {
            $err = 'Sản phẩm không hợp lệ.';
        }
        if ($err) {
            wi_flash('error', $err);
        } else {
            try {
                if ($action === 'add') {
                    $pdo->prepare('INSERT INTO rules (website_id, name, event_type, target, product, active, created_at)
                                   VALUES (?, ?, ?, ?, ?, 1, ?)')
                        ->execute([$siteId, $name, $type, $target, $product ?: null, date('Y-m-d H:i:s')]);
                    wi_flash('success', 'Đã thêm quy tắc.');
                } else {
                    $pdo->prepare('UPDATE rules SET name = ?, event_type = ?, target = ?, product = ? WHERE id = ? AND website_id = ?')
                        ->execute([$name, $type, $target, $product ?: null, $id, $siteId]);
                    wi_flash('success', 'Đã cập nhật quy tắc.');
                }
            } catch (PDOException $ex) {
                wi_flash('error', 'Quy tắc cho loại sự kiện và mục tiêu này đã tồn tại.');
            }
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $pdo->prepare('UPDATE rules SET active = 1 - active WHERE id = ? AND website_id = ?')->execute([$id, $siteId]);
        wi_flash('success', 'Đã đổi trạng thái quy tắc.');
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM rules WHERE id = ? AND website_id = ?')->execute([$id, $siteId]);
        wi_flash('success', 'Đã xóa quy tắc.');
    }
    wi_redirect('rules.php?site=' . $siteId);
}

$rules = [];
$edit = null;
if ($site) {
    $st = $pdo->prepare('SELECT * FROM rules WHERE website_id = ? ORDER BY event_type, target');
    $st->execute([$siteId]);
    $rules = $st->fetchAll();
    if (isset($_GET['edit'])) {
        foreach ($rules as $r) {
            if ((int) $r['id'] === (int) $_GET['edit']) {
                $edit = $r;
            }
        }
    }
}

wi_header('Quản lý quy tắc sự kiện', 'rules');
if (!$sites) {
    echo '<div class="card"><p class="empty">Hãy <a href="websites.php">đăng ký website</a> trước khi tạo quy tắc.</p></div>';
    wi_footer();
    exit;
}
?>
<form method="get" class="filters card">
  <label>Website<select name="site" onchange="this.form.submit()">
    <?php foreach ($sites as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $s['id'] === $siteId ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
  </select></label>
  <noscript><button class="btn" type="submit">Chọn</button></noscript>
</form>

<div class="card">
  <h2><?= $edit ? 'Sửa quy tắc' : 'Thêm quy tắc sự kiện' ?></h2>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="site" value="<?= $siteId ?>">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
    <label>Tên quy tắc<input type="text" name="name" maxlength="100" required value="<?= e($edit['name'] ?? '') ?>" placeholder="VD: Nhấp nút mua áo"></label>
    <label>Loại sự kiện<select name="event_type">
      <?php foreach (wi_event_types() as $k => $label): ?><option value="<?= e($k) ?>"<?= ($edit['event_type'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></label>
    <label>Mục tiêu<input type="text" name="target" maxlength="150" required value="<?= e($edit['target'] ?? '') ?>" placeholder="/demo.php hoặc btn-ao hoặc form-lien-he"></label>
    <label>Sản phẩm (nếu có)<select name="product"><option value="">— Không —</option>
      <?php foreach (wi_products() as $k => $label): ?><option value="<?= e($k) ?>"<?= ($edit['product'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></label>
    <button class="btn" type="submit"><?= $edit ? 'Lưu' : 'Thêm quy tắc' ?></button>
    <?php if ($edit): ?><a class="btn btn-ghost" href="rules.php?site=<?= $siteId ?>">Hủy</a><?php endif; ?>
  </form>
  <p class="hint"><strong>Xem trang</strong>: mục tiêu là đường dẫn trang (VD <code>/demo.php</code>).
    <strong>Nhấp chuột</strong>: mục tiêu là <code>id</code> hoặc <code>data-wi-track</code> của nút.
    <strong>Gửi biểu mẫu</strong>: mục tiêu là <code>id</code> hoặc <code>data-wi-track</code> của biểu mẫu.
    Chỉ sự kiện khớp quy tắc đang bật mới được ghi nhận.</p>
</div>

<div class="card">
  <h2>Quy tắc của "<?= e($site['name']) ?>" (<?= count($rules) ?>)</h2>
  <?php if (!$rules): ?><p class="empty">Website này chưa có quy tắc nào.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Tên</th><th>Loại</th><th>Mục tiêu</th><th>Sản phẩm</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody><?php foreach ($rules as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e(wi_event_types()[$r['event_type']] ?? $r['event_type']) ?></td>
        <td><code><?= e($r['target']) ?></code></td>
        <td><?= e(wi_products()[$r['product']] ?? '—') ?></td>
        <td><span class="badge <?= $r['active'] ? 'badge-on' : 'badge-off' ?>"><?= $r['active'] ? 'Bật' : 'Tắt' ?></span></td>
        <td><div class="actions">
          <a class="btn btn-ghost btn-sm" href="?site=<?= $siteId ?>&amp;edit=<?= (int) $r['id'] ?>">Sửa</a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="site" value="<?= $siteId ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit"><?= $r['active'] ? 'Tắt' : 'Bật' ?></button></form>
          <form method="post" class="inline" data-confirm="Xóa quy tắc này?"><?= csrf_field() ?><input type="hidden" name="site" value="<?= $siteId ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Xóa</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php wi_footer();

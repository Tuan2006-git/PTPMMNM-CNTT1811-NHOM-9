<?php
require __DIR__ . '/../src/bootstrap.php';
wi_require_login();
$pdo = wi_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $url = trim((string) ($_POST['url'] ?? ''));

    // Kiểm tra tên + URL cho thao tác thêm / sửa
    $valid = true;
    if ($action === 'add' || $action === 'update') {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($name === '' || strlen($name) > 100) {
            wi_flash('error', 'Tên website không được để trống và tối đa 100 ký tự.');
            $valid = false;
        } elseif (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            wi_flash('error', 'URL không hợp lệ (phải bắt đầu bằng http:// hoặc https://).');
            $valid = false;
        }
    }

    if ($valid) {
        if ($action === 'add') {
            $pdo->prepare('INSERT INTO websites (name, url, api_key, active, created_at) VALUES (?, ?, ?, 1, ?)')
                ->execute([$name, $url, wi_new_api_key(), date('Y-m-d H:i:s')]);
            wi_flash('success', 'Đã thêm website "' . $name . '" và tạo API key riêng.');
        } elseif ($action === 'update' && $id > 0) {
            $pdo->prepare('UPDATE websites SET name = ?, url = ? WHERE id = ?')->execute([$name, $url, $id]);
            wi_flash('success', 'Đã cập nhật website.');
        } elseif ($action === 'toggle' && $id > 0) {
            $pdo->prepare('UPDATE websites SET active = 1 - active WHERE id = ?')->execute([$id]);
            wi_flash('success', 'Đã đổi trạng thái hoạt động.');
        } elseif ($action === 'regen' && $id > 0) {
            $pdo->prepare('UPDATE websites SET api_key = ? WHERE id = ?')->execute([wi_new_api_key(), $id]);
            wi_flash('success', 'Đã tạo API key mới. Hãy cập nhật lại mã nhúng trên website.');
        } elseif ($action === 'delete' && $id > 0) {
            $pdo->prepare('DELETE FROM websites WHERE id = ?')->execute([$id]);
            wi_flash('success', 'Đã xóa website cùng quy tắc và dữ liệu liên quan.');
        }
    }
    wi_redirect('websites.php');
}

$edit = isset($_GET['edit']) ? wi_site_get($pdo, (int) $_GET['edit']) : null;
$sites = wi_sites($pdo);
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');

wi_header('Quản lý website', 'websites');
?>
<div class="card">
  <h2><?= $edit ? 'Sửa website' : 'Đăng ký website cần theo dõi' ?></h2>
  <form method="post" class="form-row">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
    <label>Tên website<input type="text" name="name" maxlength="100" required value="<?= e($edit['name'] ?? '') ?>"></label>
    <label>URL website<input type="url" name="url" placeholder="https://cua-hang.example.com" required value="<?= e($edit['url'] ?? '') ?>"></label>
    <button class="btn" type="submit"><?= $edit ? 'Lưu thay đổi' : 'Thêm website' ?></button>
    <?php if ($edit): ?><a class="btn btn-ghost" href="websites.php">Hủy</a><?php endif; ?>
  </form>
  <p class="hint">Mỗi website là một website riêng, có API key riêng. Tracker chỉ gửi dữ liệu từ đúng tên miền đã đăng ký.</p>
</div>

<div class="card">
  <h2>Danh sách website (<?= count($sites) ?>)</h2>
  <?php if (!$sites): ?><p class="empty">Chưa có website nào.</p><?php endif; ?>
  <?php foreach ($sites as $s):
      $snippet = '<script src="' . $base . '/assets/tracker.js" data-key="' . $s['api_key'] . '" defer></script>'; ?>
  <div class="card" style="background:var(--blue-bg)">
    <div class="form-row" style="align-items:center">
      <div style="flex:2;min-width:220px">
        <strong><?= e($s['name']) ?></strong>
        <span class="badge <?= $s['active'] ? 'badge-on' : 'badge-off' ?>"><?= $s['active'] ? 'Đang hoạt động' : 'Đã tắt' ?></span><br>
        <span class="hint"><?= e($s['url']) ?></span>
      </div>
      <div class="actions">
        <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $s['id'] ?>">Sửa</a>
        <a class="btn btn-ghost btn-sm" href="rules.php?site=<?= (int) $s['id'] ?>">Quy tắc</a>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
          <button class="btn btn-ghost btn-sm" type="submit"><?= $s['active'] ? 'Tắt' : 'Bật' ?></button></form>
        <form method="post" class="inline" data-confirm="Tạo API key mới? Key cũ sẽ ngừng hoạt động."><?= csrf_field() ?><input type="hidden" name="action" value="regen"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
          <button class="btn btn-ghost btn-sm" type="submit">Đổi API key</button></form>
        <form method="post" class="inline" data-confirm="Xóa website này cùng toàn bộ quy tắc và dữ liệu thống kê?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
          <button class="btn btn-danger btn-sm" type="submit">Xóa</button></form>
      </div>
    </div>
    <p class="hint">API key: <code><?= e($s['api_key']) ?></code></p>
    <label>Mã nhúng vào website (đặt trước thẻ &lt;/body&gt;)
      <textarea id="snip<?= (int) $s['id'] ?>" rows="2" readonly><?= e($snippet) ?></textarea></label>
    <p><button class="btn btn-ghost btn-sm" type="button" data-copy="#snip<?= (int) $s['id'] ?>">Sao chép mã nhúng</button></p>
  </div>
  <?php endforeach; ?>
</div>
<?php wi_footer();

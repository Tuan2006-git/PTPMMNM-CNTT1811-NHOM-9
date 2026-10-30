<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = wi_require_login();
$pdo = wi_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = (string) ($_POST['old'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $again = (string) ($_POST['again'] ?? '');
    $st = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$admin['id']]);
    $hash = (string) $st->fetchColumn();
    if (!password_verify($old, $hash)) {
        wi_flash('error', 'Mật khẩu hiện tại không đúng.');
    } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        wi_flash('error', 'Mật khẩu mới phải có ít nhất 8 ký tự, gồm cả chữ và số.');
    } elseif ($new !== $again) {
        wi_flash('error', 'Hai lần nhập mật khẩu mới không khớp.');
    } else {
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        wi_flash('success', 'Đã đổi mật khẩu thành công.');
    }
    wi_redirect('password.php');
}
wi_header('Đổi mật khẩu quản trị', 'password');
?>
<div class="card" style="max-width:460px">
  <form method="post" class="form-stack" style="display:flex;flex-direction:column;gap:14px" autocomplete="off">
    <?= csrf_field() ?>
    <label>Mật khẩu hiện tại<input type="password" name="old" required></label>
    <label>Mật khẩu mới (tối thiểu 8 ký tự, có chữ và số)<input type="password" name="new" required minlength="8"></label>
    <label>Nhập lại mật khẩu mới<input type="password" name="again" required minlength="8"></label>
    <button class="btn" type="submit">Đổi mật khẩu</button>
  </form>
</div>
<?php wi_footer();

<?php
// Đăng nhập, phiên làm việc và quản lý tài khoản quản trị.

function wi_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('WISESS');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/** Trả về thông tin quản trị viên đang đăng nhập (hoặc null). */
function wi_current_admin(): ?array
{
    wi_session_start();
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $st = wi_db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Bắt buộc đăng nhập; nếu chưa thì chuyển về trang login. */
function wi_require_login(): array
{
    $admin = wi_current_admin();
    if (!$admin) {
        wi_redirect('login.php');
    }
    return $admin;
}

/** Thử đăng nhập. Trả về [thành công?, thông báo]. */
function wi_try_login(string $username, string $password): array
{
    $pdo = wi_db();
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
    $window = time() - 60 * (int) wi_cfg('login_window_minutes', 10);

    $pdo->prepare('DELETE FROM login_attempts WHERE created_at < ?')->execute([$window]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $st->execute([$ip]);
    if ((int) $st->fetchColumn() >= (int) wi_cfg('login_max_attempts', 5)) {
        return [false, 'Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ít phút.'];
    }

    $st = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        $pdo->prepare('INSERT INTO login_attempts (ip, created_at) VALUES (?, ?)')->execute([$ip, time()]);
        return [false, 'Sai tên đăng nhập hoặc mật khẩu.'];
    }

    $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
    wi_session_start();
    session_regenerate_id(true); // chống session fixation
    $_SESSION['admin_id'] = (int) $row['id'];
    return [true, ''];
}

function wi_logout(): void
{
    wi_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/** Tạo mới hoặc đặt lại mật khẩu cho tài khoản quản trị. */
function wi_create_admin(PDO $pdo, string $username, string $password): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)
                         ON CONFLICT(username) DO UPDATE SET password_hash = excluded.password_hash');
    $st->execute([$username, $hash, date('Y-m-d H:i:s')]);
}

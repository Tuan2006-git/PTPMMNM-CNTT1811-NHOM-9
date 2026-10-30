<?php
declare(strict_types=1);

session_name('LUXEWEARSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const DATA_DIR = __DIR__ . '/data';
const USERS_FILE = DATA_DIR . '/users.json';

function respond(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function read_users(): array {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true) && !is_dir(DATA_DIR)) {
        respond(500, ['ok' => false, 'message' => 'Không thể tạo thư mục lưu dữ liệu.']);
    }
    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, "{}");
    }
    $raw = file_get_contents(USERS_FILE);
    $users = json_decode($raw ?: '{}', true);
    return is_array($users) ? $users : [];
}
function save_users(array $users): bool {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true) && !is_dir(DATA_DIR)) return false;
    $fp = fopen(USERS_FILE, 'c+');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }
    ftruncate($fp, 0);
    rewind($fp);
    $ok = fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}
function input_data(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}
$action = $_GET['action'] ?? '';
if ($action === 'me') {
    if (!isset($_SESSION['user'])) respond(200, ['ok' => true, 'authenticated' => false]);
    respond(200, ['ok' => true, 'authenticated' => true, 'user' => $_SESSION['user']]);
}
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
    respond(200, ['ok' => true, 'message' => 'Đã đăng xuất.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'message' => 'Phương thức không được hỗ trợ.']);
$data = input_data();
$users = read_users();

if ($action === 'register') {
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 80) respond(422, ['ok' => false, 'message' => 'Họ tên phải có từ 2 đến 80 ký tự.']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) respond(422, ['ok' => false, 'message' => 'Email không hợp lệ.']);
    if (strlen($password) < 8) respond(422, ['ok' => false, 'message' => 'Mật khẩu cần ít nhất 8 ký tự.']);
    if (strlen($password) > 200) respond(422, ['ok' => false, 'message' => 'Mật khẩu quá dài.']);
    if (isset($users[$email])) respond(409, ['ok' => false, 'message' => 'Email này đã được đăng ký. Hãy đăng nhập hoặc dùng email khác.']);
    $user = [
        'id' => bin2hex(random_bytes(8)),
        'name' => $name,
        'email' => $email,
        'created_at' => date(DATE_ATOM),
    ];
    $users[$email] = ['profile' => $user, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)];
    if (!save_users($users)) respond(500, ['ok' => false, 'message' => 'Không lưu được tài khoản. Kiểm tra quyền ghi thư mục dự án.']);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    respond(201, ['ok' => true, 'message' => 'Tạo tài khoản thành công.', 'user' => $user]);
}
if ($action === 'login') {
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') respond(422, ['ok' => false, 'message' => 'Vui lòng nhập email và mật khẩu hợp lệ.']);
    $record = $users[$email] ?? null;
    if (!$record || !isset($record['password_hash']) || !password_verify($password, $record['password_hash'])) {
        respond(401, ['ok' => false, 'message' => 'Email hoặc mật khẩu chưa chính xác.']);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = $record['profile'];
    respond(200, ['ok' => true, 'message' => 'Đăng nhập thành công.', 'user' => $record['profile']]);
}
respond(400, ['ok' => false, 'message' => 'Yêu cầu không hợp lệ.']);

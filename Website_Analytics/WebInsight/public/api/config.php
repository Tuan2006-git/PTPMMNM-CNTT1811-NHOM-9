<?php
// Trả về danh sách quy tắc đang bật của một website để tracker biết cần theo dõi gì.
require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

$key = (string) ($_GET['key'] ?? '');
if (!preg_match('/^wi_[a-f0-9]{32}$/', $key)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_api_key']);
    exit;
}
try {
    $pdo = wi_db();
    $st = $pdo->prepare('SELECT id, active FROM websites WHERE api_key = ?');
    $st->execute([$key]);
    $site = $st->fetch();
    if (!$site) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'invalid_api_key']);
        exit;
    }
    if ((int) $site['active'] !== 1) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'website_disabled']);
        exit;
    }
    $st = $pdo->prepare('SELECT event_type AS type, target FROM rules WHERE website_id = ? AND active = 1');
    $st->execute([(int) $site['id']]);
    echo json_encode(['ok' => true, 'rules' => $st->fetchAll()]);
} catch (Throwable $ex) {
    wi_log('config exception: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}

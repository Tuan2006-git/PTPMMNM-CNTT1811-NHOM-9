<?php
// API thu thập sự kiện: POST JSON -> kiểm tra -> ghi vào CSDL.
require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Chỉ đọc tối đa 8 KB; collector sẽ từ chối nếu vượt giới hạn cấu hình
$raw = (string) file_get_contents('php://input', false, null, 0, 8192);

try {
    [$status, $body] = wi_collect(wi_db(), $raw, $_SERVER['HTTP_ORIGIN'] ?? null);
} catch (Throwable $ex) {
    wi_log('collect exception: ' . $ex->getMessage());
    $status = 500;
    $body = ['ok' => false, 'error' => 'server_error'];
}

if ($status >= 400 && $status < 500) {
    // Ghi log có giới hạn dung lượng để theo dõi sự kiện bị từ chối
    wi_log('collect rejected status=' . $status . ' error=' . ($body['error'] ?? '') . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}
http_response_code($status);
echo json_encode($body);

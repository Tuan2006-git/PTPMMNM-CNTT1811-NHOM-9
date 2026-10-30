<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode(['ok' => false, 'message' => 'Content-Type must be application/json']);
    exit;
}
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}
$allowed = [
    'page_view','form_submit','product_detail','add_to_cart','remove_from_cart',
    'cart_open','checkout_click','category_filter','search','gender_filter',
    'price_filter','sort_products','reset_filters','newsletter_submit',
    'login_success','register_success'
];
$eventName = trim((string)($data['event_name'] ?? ''));
$sessionId = trim((string)($data['session_id'] ?? ''));
$pageUrl = (string)($data['page_url'] ?? '/');
$details = $data['details'] ?? [];
if (!in_array($eventName, $allowed, true) || $sessionId === '' || strlen($sessionId) > 100 || !is_array($details)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid event payload']);
    exit;
}
// Keep URLs bounded and reject control characters. Never accept form field values.
$pageUrl = substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $pageUrl) ?? '/', 0, 500);
$details = array_intersect_key($details, array_flip([
    'page_title','referrer','product_id','product_name','size','item_count','cart_total',
    'category','query_length','has_query','value','form_id'
]));
// Store the SQLite file beside the project folder, not in the public web root.
$dbPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'luxe_wear_analytics.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS analytics_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        event_name TEXT NOT NULL,
        page_url TEXT NOT NULL,
        details_json TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
    )');
    $stmt = $pdo->prepare('INSERT INTO analytics_events (session_id, event_name, page_url, details_json) VALUES (:session_id, :event_name, :page_url, :details_json)');
    $stmt->execute([
        ':session_id' => $sessionId,
        ':event_name' => $eventName,
        ':page_url' => $pageUrl,
        ':details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
    ]);
    echo json_encode(['ok' => true, 'event_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    error_log('Analytics storage error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not store analytics event']);
}

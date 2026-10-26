<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Analytics.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method_not_allowed']); exit; }
$length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($length > MAX_BODY_BYTES) { http_response_code(413); echo json_encode(['error'=>'payload_too_large']); exit; }
$raw = file_get_contents('php://input', false, null, 0, MAX_BODY_BYTES + 1);
$data = json_decode($raw ?: '', true);
if (!is_array($data)) { http_response_code(400); echo json_encode(['error'=>'invalid_json']); exit; }
$key = (string)($_SERVER['HTTP_X_ANALYTICS_KEY'] ?? '');
$result = (new Analytics(Database::connection()))->ingest($data,$key);
http_response_code($result['status']); echo json_encode($result['body'], JSON_UNESCAPED_UNICODE);

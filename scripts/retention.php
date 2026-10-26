<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
$db=Database::connection();
$cutoff=(new DateTimeImmutable('-'.RETENTION_DAYS.' days',new DateTimeZone('UTC')))->format('c');
$s=$db->prepare('DELETE FROM events WHERE occurred_at < ?'); $s->execute([$cutoff]);
$db->exec('DELETE FROM sessions WHERE id NOT IN (SELECT DISTINCT session_id FROM events)');
$db->exec('DELETE FROM ingestion_errors WHERE created_at < datetime("now","-30 days")');
echo 'RETENTION_DELETED_EVENTS='.$s->rowCount().PHP_EOL;

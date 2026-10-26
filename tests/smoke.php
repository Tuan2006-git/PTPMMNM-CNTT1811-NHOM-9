<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Analytics.php';
$db=Database::connection();
function ok(bool $v,string $name):void { echo ($v?'PASS ':'FAIL ').$name.PHP_EOL; if(!$v) $GLOBALS['failed']++; }
$failed=0;
$site=$db->query("SELECT id FROM websites WHERE slug='demo-a'")->fetchColumn();
$key='demo-key-a-change-me';
$session='test-session-'.bin2hex(random_bytes(4));
$id='test-event-'.bin2hex(random_bytes(4));
$data=['event_id'=>$id,'session_id'=>$session,'event_name'=>'page_view','page_path'=>'/test','occurred_at'=>gmdate('c')];
$a=new Analytics($db);
$r1=$a->ingest($data,$key);
$r2=$a->ingest($data,$key);
ok($r1['status']===201,'first event created');
ok($r2['status']===200 && $r2['body']['status']==='duplicate_ignored','same event id ignored');
$conflict=$data; $conflict['page_path']='/different';
$r3=$a->ingest($conflict,$key);
ok($r3['status']===409,'same id different payload rejected');
ok((int)$site===1,'seed website exists');
echo 'TESTS='.(4).' FAILED='.$failed.PHP_EOL;
exit($failed?1:0);

<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
session_start();
if (isset($_POST['username'],$_POST['password'])) {
    if (hash_equals(DEMO_ADMIN_USER,(string)$_POST['username']) && hash_equals(DEMO_ADMIN_PASSWORD,(string)$_POST['password'])) {
        session_regenerate_id(true); $_SESSION['analytics_admin']=true;
    }
}
$db = Database::connection();
$token = (string)($_GET['token'] ?? '');
$site = null;
if ($token !== '') { $s=$db->prepare('SELECT * FROM websites WHERE report_token=?'); $s->execute([$token]); $site=$s->fetch(); }
$admin = !empty($_SESSION['analytics_admin']);
if (!$site && !$admin) {
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><link rel="stylesheet" href="/assets/app.css"><title>Đăng nhập báo cáo</title></head><body><main class="wrap narrow"><section class="panel"><h1>Đăng nhập báo cáo</h1><form method="post"><label>Tài khoản<input name="username" required autocomplete="username"></label><label>Mật khẩu<input type="password" name="password" required autocomplete="current-password"></label><button>Đăng nhập</button></form><p class="notice">Tài khoản demo cục bộ: admin / ChangeMe-Local-Only!</p></section></main></body></html><?php exit; }
if (!$site) { $site=$db->query('SELECT * FROM websites ORDER BY id LIMIT 1')->fetch(); }
$from = (string)($_GET['from'] ?? date('Y-m-d',strtotime('-6 days')));
$to = (string)($_GET['to'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $from=date('Y-m-d',strtotime('-6 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) $to=date('Y-m-d');
$fromUtc=(new DateTimeImmutable($from.' 00:00:00',new DateTimeZone(APP_TIMEZONE)))->setTimezone(new DateTimeZone('UTC'))->format('c');
$toUtc=(new DateTimeImmutable($to.' 23:59:59',new DateTimeZone(APP_TIMEZONE)))->setTimezone(new DateTimeZone('UTC'))->format('c');
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="analytics-report.csv"');
    $out=fopen('php://output','w'); fputcsv($out,['date_local','page_path','event_type','count']);
    $s=$db->prepare("SELECT substr(datetime(occurred_at,'+7 hours'),1,10) day,page_path,event_type,COUNT(*) total FROM events WHERE website_id=? AND occurred_at BETWEEN ? AND ? GROUP BY day,page_path,event_type ORDER BY day,page_path,event_type");
    $s->execute([(int)$site['id'],$fromUtc,$toUtc]); foreach($s as $r) fputcsv($out,[$r['day'],$r['page_path'],$r['event_type'],$r['total']]); fclose($out); exit;
}
$base=$db->prepare('SELECT COUNT(*) total,COUNT(DISTINCT session_id) sessions FROM events WHERE website_id=? AND occurred_at BETWEEN ? AND ?');
$base->execute([(int)$site['id'],$fromUtc,$toUtc]); $totals=$base->fetch();
$s=$db->prepare('SELECT event_type,COUNT(*) total FROM events WHERE website_id=? AND occurred_at BETWEEN ? AND ? GROUP BY event_type ORDER BY event_type');
$s->execute([(int)$site['id'],$fromUtc,$toUtc]); $byType=$s->fetchAll();
$s=$db->prepare('SELECT page_path,COUNT(*) total FROM events WHERE website_id=? AND event_type="page_view" AND occurred_at BETWEEN ? AND ? GROUP BY page_path ORDER BY total DESC');
$s->execute([(int)$site['id'],$fromUtc,$toUtc]); $byPage=$s->fetchAll();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Báo cáo</title><link rel="stylesheet" href="/assets/app.css"></head><body><header><div class="wrap"><span class="eyebrow">BÁO CÁO</span><h1><?=htmlspecialchars($site['name'])?></h1><p>Số liệu tính từ sự kiện đã lưu, theo bộ lọc ngày.</p></div></header><main class="wrap">
<form class="panel filters" method="get"><?php if($token!==''): ?><input type="hidden" name="token" value="<?=htmlspecialchars($token)?>"><?php endif; ?><label>Từ ngày<input type="date" name="from" value="<?=htmlspecialchars($from)?>"></label><label>Đến ngày<input type="date" name="to" value="<?=htmlspecialchars($to)?>"></label><button>Lọc</button><a class="button secondary" href="?<?=http_build_query(['token'=>$token,'from'=>$from,'to'=>$to,'export'=>1])?>">Xuất CSV</a></form>
<div class="cards"><article class="card"><span>Tổng sự kiện</span><strong><?= (int)$totals['total']?></strong></article><article class="card"><span>Phiên có hoạt động</span><strong><?= (int)$totals['sessions']?></strong></article></div>
<section class="panel"><h2>Theo loại sự kiện</h2><table><thead><tr><th>Loại</th><th>Số lần</th></tr></thead><tbody><?php foreach($byType as $r): ?><tr><td><?=htmlspecialchars($r['event_type'])?></td><td><?= (int)$r['total']?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="panel"><h2>Lượt xem theo trang</h2><table><thead><tr><th>Trang</th><th>Lượt xem</th></tr></thead><tbody><?php foreach($byPage as $r): ?><tr><td><?=htmlspecialchars($r['page_path'])?></td><td><?= (int)$r['total']?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="panel"><h2>Chuỗi xem–bắt đầu–hoàn tất</h2><p>Đếm theo phiên, theo thứ tự thời gian và chỉ tính hoàn tất khi có đủ ba bước.</p><strong><?php
$s=$db->prepare("SELECT session_id,event_type,occurred_at FROM events WHERE website_id=? AND occurred_at BETWEEN ? AND ? AND event_type IN ('page_view','action_start','action_complete') ORDER BY session_id,occurred_at,id");
$s->execute([(int)$site['id'],$fromUtc,$toUtc]); $states=[]; $completed=0;
foreach($s as $e){$id=$e['session_id'];$state=$states[$id]??0;if($e['event_type']==='page_view' && $state===0)$states[$id]=1;elseif($e['event_type']==='action_start' && $state===1)$states[$id]=2;elseif($e['event_type']==='action_complete' && $state===2){$states[$id]=3;$completed++;}}
echo $completed;
?></strong><p>chuỗi hoàn tất được đếm từ các sự kiện trong khoảng lọc và cùng phiên.</p></section><p><a href="/">← Trang chủ</a></p></main></body></html>

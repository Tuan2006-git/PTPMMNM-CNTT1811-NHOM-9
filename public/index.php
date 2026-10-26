<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
$db = Database::connection();
$websites = $db->query('SELECT id,name,slug,api_key,report_token FROM websites ORDER BY id')->fetchAll();
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Website Analytics — Đề tài 17</title><link rel="stylesheet" href="/assets/app.css"></head>
<body><header><div class="wrap"><span class="eyebrow">ĐỀ TÀI 17</span><h1>Website Analytics</h1><p>Công cụ thống kê lượt xem và thao tác cho website thử nghiệm có thông báo.</p></div></header>
<main class="wrap">
<section class="panel"><h2>Website thử nghiệm</h2><p>Chọn website để mở trang demo. Mã khóa dưới đây chỉ dành cho máy demo cục bộ.</p>
<div class="cards"><?php foreach($websites as $w): ?><article class="card"><h3><?=htmlspecialchars($w['name'])?></h3><p>Slug: <code><?=htmlspecialchars($w['slug'])?></code></p><p><a class="button" href="/demo.php?site=<?=urlencode($w['slug'])?>">Mở website thử</a></p><p><a href="/report.php?token=<?=urlencode($w['report_token'])?>">Xem báo cáo</a></p></article><?php endforeach; ?></div></section>
<section class="panel"><h2>Luồng sử dụng</h2><ol><li>Mở website thử và chấp thuận thông báo theo dõi demo.</li><li>Mở trang, bấm nút thao tác và chạy chuỗi bắt đầu–hoàn tất.</li><li>Mở báo cáo để đối chiếu số liệu hoặc xuất CSV.</li></ol><p class="notice">Không nhập dữ liệu cá nhân thật. Tracker không gửi giá trị trong ô nhập.</p></section>
</main><footer class="wrap">PHP thuần · PDO · JavaScript · SQLite · Dữ liệu demo</footer></body></html>

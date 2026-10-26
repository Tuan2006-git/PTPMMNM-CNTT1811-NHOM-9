<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
$db = Database::connection();
$slug = (string)($_GET['site'] ?? 'demo-a');
$s = $db->prepare('SELECT * FROM websites WHERE slug=?');
$s->execute([$slug]); $site = $s->fetch();
if (!$site) { http_response_code(404); exit('Không tìm thấy website demo.'); }
$pages = ['/' => 'Trang chủ', '/product' => 'Sản phẩm', '/guide' => 'Hướng dẫn'];
$page = (string)($_GET['page'] ?? '/');
if (!isset($pages[$page])) $page='/';
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=htmlspecialchars($pages[$page])?> — <?=htmlspecialchars($site['name'])?></title><link rel="stylesheet" href="/assets/app.css"></head><body>
<header><div class="wrap"><span class="eyebrow">WEBSITE THỬ NGHIỆM · CÓ THÔNG BÁO</span><h1><?=htmlspecialchars($site['name'])?></h1><p>Trang demo phục vụ kiểm thử công cụ thống kê của Đề tài 17.</p></div></header><main class="wrap"><section class="panel"><nav><?php foreach($pages as $path=>$label): ?><a href="/demo.php?site=<?=urlencode($slug)?>&page=<?=urlencode($path)?>"><?=htmlspecialchars($label)?></a><?php endforeach; ?></nav><h2><?=htmlspecialchars($pages[$page])?></h2><p>Nội dung mẫu cho bài tập lớn. Không nhập thông tin cá nhân thật.</p>
<div class="actions"><button data-track="button_click">Bấm nút thử nghiệm</button><button data-track="start">Bắt đầu chuỗi thao tác</button><button data-track="complete">Hoàn tất chuỗi thao tác</button></div>
<p id="tracker-status" role="status">Chưa gửi sự kiện. Tracker chỉ hoạt động sau khi bạn đồng ý.</p>
<label><input type="checkbox" id="consent"> Tôi đồng ý ghi nhận lượt xem và các nút demo trên website thử nghiệm này.</label>
</section><p><a href="/">← Trang quản lý demo</a></p></main>
<script>
window.ANALYTICS_CONFIG = <?=json_encode(['apiUrl'=>'/api/collect.php','apiKey'=>$site['api_key'],'pagePath'=>$page],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
</script><script src="/assets/tracker.js" defer></script></body></html>

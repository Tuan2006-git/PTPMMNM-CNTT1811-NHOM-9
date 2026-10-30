LUXE WEAR - ĐĂNG NHẬP / ĐĂNG KÝ (BẢN DEMO PHP)

CÁC FILE MỚI:
- login.html: giao diện đăng nhập
- register.html: giao diện đăng ký
- auth.php: API đăng ký, đăng nhập, kiểm tra phiên, đăng xuất
- auth-page.js: xử lý form đăng nhập/đăng ký
- auth-common.js: hiển thị trạng thái tài khoản trên trang chủ
- data/users.json: tự tạo khi đăng ký tài khoản đầu tiên
- .htaccess: chặn truy cập trực tiếp users.json trên Apache

CÁCH CHẠY:
1. Cài PHP 8.1 trở lên.
2. Mở terminal trong thư mục này.
3. Chạy: php -S 127.0.0.1:8000
4. Mở http://127.0.0.1:8000
5. Chọn Tài khoản hoặc mở http://127.0.0.1:8000/register.html để đăng ký.

QUAN TRỌNG:
- Không mở index.html bằng cách nhấp đúp (file://); PHP API sẽ không chạy.
- Mật khẩu được băm bằng password_hash() và kiểm tra bằng password_verify(), không lưu mật khẩu thô.
- Dữ liệu tài khoản demo nằm ở data/users.json. Sao lưu và không đưa dữ liệu người dùng thật lên Git.
- Đây là bản demo học tập dùng JSON, không thay thế hệ thống tài khoản production. Khi triển khai thật nên dùng MySQL, HTTPS, CSRF protection, rate limiting, xác minh email và quy trình khôi phục mật khẩu.


THONG KE HOAT DONG (MOI)
- tracker.js ghi nhan page_view va cac su kien tim kiem/loc/xem chi tiet/gio hang.
- api/track-event.php luu su kien vao SQLite, file luxe_wear_analytics.sqlite duoc tao o thu muc cha cua du an.
- Chay website bang PHP server, khong mo file HTML truc tiep. Vi du: php -S 127.0.0.1:8000 -t .
- API chi luu metadata su kien; khong gui mat khau hay gia tri truong form.
- Ban Dashboard co the doc bang analytics_events trong file SQLite nay; thong nhat duong dan DB voi thanh vien phu trach Dashboard.

- Can PHP co PDO_SQLITE/pdo_sqlite. Kiem tra bang: php -m | grep -i sqlite. Neu chua co, cai extension SQLite phu hop voi phien ban PHP truoc khi dung API.

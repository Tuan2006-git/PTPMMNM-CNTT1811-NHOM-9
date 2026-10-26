# Đề tài 17 — Công cụ thống kê lượt truy cập và thao tác trên website

Ứng dụng PHP thuần, SQLite (PDO), JavaScript và giao diện HTML/CSS. Bản demo phục vụ website thử nghiệm được thông báo cho người tham gia.

## Yêu cầu
- PHP 8.1+ có PDO SQLite.
- Không cần Composer hoặc framework.
- Chạy trong thư mục dự án:
  ```bash
  php -S 127.0.0.1:8080 -t public
  ```
  Mở http://127.0.0.1:8080
- CSDL SQLite được tạo tự động tại `var/analytics.sqlite` khi mở ứng dụng lần đầu.
- Tài khoản báo cáo demo: `admin` / `ChangeMe-Local-Only!`. Đổi mật khẩu trước khi triển khai thật.

## Chức năng hiện có
- Hai website demo và cấu hình sự kiện được phép.
- JavaScript tracker ghi page view và thao tác đã cấu hình, không gửi giá trị input/textarea.
- API xác thực website, giới hạn payload, deduplicate theo event ID và phát hiện cùng ID nhưng khác nội dung.
- Báo cáo theo ngày, trang và loại sự kiện; lọc ngày; CSV export.
- Chuỗi `page_view -> action_start -> action_complete` phải theo đúng thứ tự trong cùng session.
- Quy tắc UTC lưu trữ, ngày báo cáo theo múi giờ cấu hình; giới hạn sự kiện đến muộn 48 giờ.
- Xóa dữ liệu cũ bằng nút chạy retention (xóa sự kiện quá 90 ngày).
- Dữ liệu demo có thể nạp lại bằng `php scripts/seed.php`.

## Lưu ý an toàn
Đây là bản đồ án/demo cục bộ, không phải dịch vụ analytics production. Trước triển khai thật cần HTTPS, quản lý tài khoản và mật khẩu qua biến môi trường, CSRF bảo vệ form quản trị, logging/backup, migration và kiểm tra bảo mật độc lập. Không dùng session ID làm định danh con người. Không ghi IP đầy đủ, mật khẩu hoặc nội dung trường nhập.

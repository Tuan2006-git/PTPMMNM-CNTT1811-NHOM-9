# WebInsight – Hệ thống thống kê và phân tích tương tác website

- **Backend:** PHP 8.1+ (PDO)
- **Cơ sở dữ liệu:** MySQL 8.0+ / MariaDB tương thích
- **Frontend:** HTML5, CSS3, JavaScript
- **Luồng dữ liệu:** JavaScript tracker → PHP REST API → MySQL

## 1. Cài đặt trên Ubuntu / WSL

### Bước 1. Cài PHP, MySQL và extension

```bash
sudo apt update
sudo apt install -y php-cli php-mysql php-sqlite3 mysql-server zip unzip curl
sudo service mysql start
```

`php-sqlite3` chỉ cần khi muốn chuyển dữ liệu từ file SQLite cũ. Khi chạy WebInsight bình thường, ứng dụng kết nối MySQL bằng `pdo_mysql`.

### Bước 2. Tạo database và tài khoản MySQL

```bash
sudo mysql
```

Trong dấu nhắc MySQL, chạy (hãy thay mật khẩu bằng mật khẩu riêng của bạn):

```sql
CREATE DATABASE webinsight
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'webinsight_user'@'127.0.0.1'
  IDENTIFIED BY 'ChangeThisPassword!';

GRANT ALL PRIVILEGES ON webinsight.* TO 'webinsight_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

### Bước 3. Cấu hình kết nối

Mở `config/config.php`, kiểm tra các giá trị:

```php
'db_host'    => '127.0.0.1',
'db_port'    => '3306',
'db_name'    => 'webinsight',
'db_charset' => 'utf8mb4',
'db_user'    => 'webinsight_user',
'db_pass'    => 'ChangeThisPassword!',
```

Mật khẩu trong file phải khớp với mật khẩu đã dùng ở câu lệnh `CREATE USER`. Có thể dùng biến môi trường `WI_DB_HOST`, `WI_DB_PORT`, `WI_DB_NAME`, `WI_DB_USER`, `WI_DB_PASS` để không ghi mật khẩu trực tiếp trong mã nguồn.

### Bước 4. Kiểm tra, tạo bảng và chạy ứng dụng

```bash
chmod +x lab.sh
./lab.sh check
./lab.sh lint
./lab.sh init
./lab.sh run
```

Mở `http://localhost:8000`. Tài khoản mặc định khi khởi tạo database mới là `admin` / `Admin@123`. Hãy đổi mật khẩu sau lần đăng nhập đầu tiên.

## 2. Chuyển dữ liệu từ SQLite cũ sang MySQL

Gói dự án giữ lại file `data/webinsight.sqlite` làm nguồn để chuyển dữ liệu. Nếu bạn đã có dữ liệu SQLite cần giữ, hãy sao lưu file đó trước.

1. Hoàn thành các bước tạo MySQL database, tài khoản và cấu hình ở mục 1.
2. Cài extension đọc SQLite (nếu chưa cài): `sudo apt install php-sqlite3`.
3. Chạy lệnh:

```bash
php scripts/migrate_sqlite_to_mysql.php
```

Script sẽ chuyển các bảng `admins`, `websites`, `rules`, `events`, `funnels`, `settings`, `login_attempts` sang MySQL. Script xóa dữ liệu hiện có trong các bảng đích trước khi nhập để tránh trộn dữ liệu; hãy chạy trên database mới hoặc sao lưu MySQL trước. File SQLite nguồn không bị xóa.

Sau khi chuyển, đăng nhập bằng tài khoản quản trị đã có trong SQLite cũ. Nếu không cần giữ dữ liệu cũ, có thể bỏ qua bước chuyển và chỉ chạy `./lab.sh init`.

## 3. Các lệnh thường dùng

```bash
./lab.sh check       # Kiểm tra PHP và extension
./lab.sh lint        # Kiểm tra cú pháp PHP/JavaScript
./lab.sh init        # Tạo bảng và tài khoản admin mặc định nếu chưa có
./lab.sh admin admin MatKhauMoi123
./lab.sh run         # Chạy tại http://localhost:8000
./lab.sh purge 90    # Xóa sự kiện cũ hơn 90 ngày
```

## 4. Kiểm tra MySQL

Đăng nhập MySQL:

```bash
mysql -u webinsight_user -p webinsight
```

Một số câu lệnh SQL hữu ích:

```sql
SHOW TABLES;
SELECT COUNT(*) FROM events;
SELECT created_at, event_type, target, product
FROM events
ORDER BY id DESC
LIMIT 10;
```

## 5. Cấu trúc cơ sở dữ liệu

| Bảng | Nội dung |
|---|---|
| `admins` | Tài khoản quản trị và mật khẩu đã băm |
| `websites` | Website được theo dõi, API key, trạng thái |
| `rules` | Các quy tắc sự kiện theo website |
| `events` | Lượt xem trang và tương tác đã thu thập |
| `funnels` | Các chuỗi sự kiện theo thứ tự |
| `settings` | Cấu hình hệ thống, ví dụ thời hạn lưu trữ |
| `login_attempts` | Lịch sử đăng nhập sai |

MySQL dùng InnoDB và khóa ngoại để đảm bảo toàn vẹn dữ liệu. Các truy vấn PHP tiếp tục sử dụng PDO prepared statements; `UNIQUE(website_id, event_uid)` ngăn một sự kiện bị ghi nhận trùng.

## 6. Lưu ý bảo mật

- Đổi mật khẩu admin mặc định ngay sau khi đăng nhập.
- Đặt mật khẩu MySQL riêng, không dùng tài khoản `root` cho ứng dụng.
- Không đưa `config/config.php` chứa mật khẩu lên kho Git công khai.
- Khi triển khai thực tế, sử dụng HTTPS và Apache/Nginx thay cho máy chủ PHP tích hợp.

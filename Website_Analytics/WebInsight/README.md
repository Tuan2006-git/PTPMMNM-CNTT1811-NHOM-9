# WebInsight – Hệ thống thống kê và phân tích tương tác website (Đề tài 17)

WebInsight thu thập lượt truy cập và hành vi tương tác (xem trang, nhấp nút sản phẩm, gửi biểu mẫu) từ các website được liên kết, rồi hiển thị trên dashboard quản trị.

- **Backend:** PHP 8.1+ (không dùng framework) · **CSDL:** SQLite · **Frontend:** HTML5/CSS3/JavaScript
- **Giao tiếp:** tracker (JavaScript) → REST API (PHP, JSON) → SQLite
- **Chạy trên:** Ubuntu hoặc Ubuntu trên WSL

> **Tài khoản mặc định:** `admin` / `Admin@123` (hãy đổi tại trang "Đổi mật khẩu" sau khi đăng nhập).

---

## 1. Cài đặt và chạy nhanh trên Ubuntu / WSL

```bash
# 1. Giải nén và vào thư mục dự án
unzip WebInsight_DeTai17.zip -d WebInsight
cd WebInsight

# 2. Cài PHP + extension (cần mật khẩu sudo)
./lab.sh install
#   tương đương: sudo apt update && sudo apt install -y php-cli php-sqlite3 zip unzip curl

# 3. Kiểm tra môi trường và cú pháp mã nguồn
./lab.sh check
./lab.sh lint

# 4. Khởi tạo CSDL, tài khoản admin, website demo
./lab.sh init

# 5. Chạy máy chủ PHP (giữ cửa sổ terminal này mở)
./lab.sh run
```

Mở trình duyệt: <http://localhost:8000> (đăng nhập `admin` / `Admin@123`). Trang demo: <http://localhost:8000/demo.php>.
Ở một terminal khác có thể gõ `./lab.sh open` để mở trình duyệt (WSL sẽ mở trình duyệt Windows).

Nếu `./lab.sh` báo "Permission denied": `chmod +x lab.sh`. Đổi cổng: `PORT=9000 ./lab.sh run`.

### Đường dẫn có dấu cách
Khi dùng lệnh `cd`, đặt đường dẫn trong dấu ngoặc kép:

```bash
cd "/mnt/c/Users/Nguyen Van A/Bai tap lon/WebInsight"
```

Hoặc chép dự án vào thư mục Linux (khuyến nghị trên WSL, chạy ổn định và nhanh hơn): `cp -r "/mnt/c/Users/Nguyen Van A/Bai tap lon/WebInsight" ~/WebInsight`

---

## 2. Cấu trúc mã nguồn

```
config/        cấu hình hệ thống (config.php)
src/           thành phần dùng chung: db, auth, helpers, stats, collector, layout, testdata, selftest
public/        trang giao diện (document root của máy chủ PHP)
public/api/    API: collect.php (nhận sự kiện), config.php (cấp quy tắc cho tracker)
public/assets/ style.css, app.js, tracker.js
scripts/       khởi tạo CSDL, tạo admin, dữ liệu kiểm thử, chạy test, xem trạng thái
data/          CSDL SQLite cục bộ
logs/          log lỗi (tự cắt bớt khi vượt 256 KB)
lab.sh         các lệnh kiểm tra, khởi tạo và chạy dự án
```

## 3. Cách hệ thống hoạt động

1. Quản trị viên **đăng ký website** (tên, URL) → hệ thống sinh **API key riêng**.
2. Khai báo **quy tắc sự kiện**: trang nào, nút nào (`id` hoặc `data-wi-track`), biểu mẫu nào được theo dõi.
3. Nhúng tracker vào website cần theo dõi:
   ```html
   <script src="http://localhost:8000/assets/tracker.js" data-key="wi_xxxxxxxx" defer></script>
   ```
4. Tracker tải danh sách quy tắc đang bật, tự ghi nhận xem trang / nhấp / gửi biểu mẫu và gửi JSON tới `api/collect.php`.
5. API kiểm tra API key, website, tên miền gửi (Origin), loại sự kiện, định dạng dữ liệu, quy tắc đang bật, giới hạn tần suất, rồi ghi vào CSDL. Mã sự kiện (`event_id`) là duy nhất nên gửi lại không bị đếm trùng.
6. Dashboard thống kê theo website, khoảng thời gian, ngày, trang, sản phẩm, loại sự kiện.

**Riêng tư:** tracker chỉ gửi loại sự kiện, mã mục tiêu (id nút/biểu mẫu/đường dẫn) và mã phiên ngẫu nhiên. Tracker không đọc nội dung ô nhập, mật khẩu hay thông tin cá nhân; bảng `events` cũng không có cột nào để lưu các dữ liệu đó. Dashboard chỉ hiển thị số liệu thật từ CSDL, không có số liệu giả. Dữ liệu kiểm thử luôn gắn cờ riêng (`is_test = 1`).

## 4. Các trang giao diện

| Trang | Tệp | Chức năng |
|---|---|---|
| Đăng nhập | `login.php` | Đăng nhập quản trị, chặn dò mật khẩu (5 lần sai/10 phút) |
| Tổng quan | `dashboard.php` | Lượt xem trang (kèm tên website), số phiên, số tương tác, theo ngày/trang/loại sự kiện, so sánh hai ngày |
| Quản lý website | `websites.php` | Thêm/sửa/bật-tắt/xóa website, đổi API key, mã nhúng |
| Quy tắc sự kiện | `rules.php` | Thêm/sửa/bật-tắt/xóa quy tắc |
| Thống kê sản phẩm | `products.php` | Quần, áo, kính, giày |
| Phân tích chuỗi | `funnels.php` | Chuỗi sự kiện theo thứ tự, số phiên hoàn thành |
| Xuất báo cáo | `reports.php` | Xem trước và tải CSV (ngày, trang, sản phẩm, loại, chuỗi, chi tiết) |
| Kiểm thử & dữ liệu | `tools.php` | Chạy kịch bản kiểm thử, tạo/xóa dữ liệu kiểm thử, thời hạn lưu trữ, xóa dữ liệu |
| Website demo | `demo.php` | Cửa hàng giả lập để thử tracker |
| Đổi mật khẩu | `password.php` | Đổi mật khẩu quản trị |

---

## 5. Kiểm tra dữ liệu có được ghi vào CSDL

1. `./lab.sh run` rồi mở <http://localhost:8000/demo.php>.
2. Nhấp các nút Quần/Áo/Kính/Giày và gửi biểu mẫu "Đăng ký nhận tư vấn". Khung "Nhật ký tracker" hiển thị `[HTTP 200]` cho mỗi sự kiện được ghi nhận.
3. Ở terminal khác: `./lab.sh status` để xem sự kiện đã nằm trong CSDL.
4. Mở **Tổng quan** (<http://localhost:8000/dashboard.php>) để thấy số liệu tăng lên.

Hoặc tự xem bằng sqlite3 (cài thêm bằng `sudo apt install sqlite3`):

```bash
sqlite3 data/webinsight.sqlite "SELECT created_at, event_type, target, product FROM events ORDER BY id DESC LIMIT 10;"
```

Thử gửi sự kiện bằng `curl` (lấy API key bằng `./lab.sh status`; lệnh này ghi **dữ liệu thật** vào website demo):

```bash
KEY=wi_xxxxxxxx   # thay bằng API key của bạn
curl -s -X POST http://localhost:8000/api/collect.php \
  -d '{"api_key":"'$KEY'","event_id":"curl-test-0001","session_id":"curlsession01","type":"pageview","target":"/demo.php","path":"/demo.php"}'
# Lần 1: {"ok":true,"duplicate":false}   Lần 2 (cùng event_id): {"ok":true,"duplicate":true}
```

---

## 6. Kịch bản kiểm thử

### 6.1. Kiểm thử tự động
```bash
./lab.sh test            # hoặc nhấn "Chạy kiểm thử" ở trang Kiểm thử & dữ liệu
```
Bộ kiểm thử tạo website tạm `__SELFTEST__`, chạy các kịch bản bên dưới rồi xóa sạch, không ảnh hưởng số liệu thật:
sự kiện hợp lệ (xem trang, nhấp, gửi biểu mẫu), API key sai (401), JSON sai (400), loại sự kiện sai (400), sự kiện chưa cấp phép (422), ký tự không hợp lệ (400), yêu cầu quá lớn (413), **mã sự kiện trùng (chỉ đếm 1 lần)**, **không lưu nội dung nhập**, thống kê đúng theo ngày + website, dữ liệu kiểm thử không lẫn dữ liệu thật, **website bị tắt (403)**, quy tắc bị tắt (422), phân tích chuỗi theo thứ tự (3 → 2 → 1), xóa dữ liệu theo thời hạn, chống CSV injection.

### 6.2. Kiểm thử thủ công
| # | Kịch bản | Cách thực hiện | Kết quả mong đợi |
|---|---|---|---|
| 1 | Đăng nhập đúng | `admin` / `Admin@123` | Vào Tổng quan |
| 2 | Đăng nhập sai | Nhập sai mật khẩu | Báo "Sai tên đăng nhập hoặc mật khẩu"; sai 5 lần thì bị khóa tạm |
| 3 | Tạo website + API key | Quản lý website → thêm | Xuất hiện website mới với API key `wi_…` |
| 4 | Ghi nhận lượt xem trang | Mở `demo.php` | Nhật ký demo `pageview → /demo.php [HTTP 200]`; Lượt xem trang +1 |
| 5 | Ghi nhận lượt nhấp | Nhấp nút "Xem áo" | `click → btn-ao [HTTP 200]`; thống kê sản phẩm Áo +1 |
| 6 | Gửi biểu mẫu, không thu nội dung | Nhập tên/email rồi Gửi | `submit → form-lien-he`; chạy `./lab.sh status` hoặc xem báo cáo "Danh sách sự kiện chi tiết": không có tên/email |
| 7 | Từ chối sự kiện chưa cấp phép | Trang Quy tắc: tắt quy tắc `btn-kinh`, nhấp "Xem kính" trên demo (tải lại demo trước) | Không có sự kiện mới; hoặc dùng `curl` với `"type":"click","target":"btn-khong-co"` → HTTP 422 |
| 8 | Không đếm trùng | Chạy `curl` ở mục 5 hai lần | Lần 2 trả `"duplicate":true`, số đếm không tăng |
| 9 | Thống kê theo ngày/website | Tổng quan: lọc website + khoảng ngày; "So sánh hai ngày" | Số liệu khớp dữ liệu |
| 10 | Chuỗi sự kiện | Phân tích chuỗi: dùng chuỗi mẫu; trên demo mở trang → nhấp Áo → gửi biểu mẫu | Số phiên hoàn thành tăng. Nhấp Áo trước rồi mới tải trang thì không hoàn thành |
| 11 | Xuất CSV | Xuất báo cáo → Tải CSV | File `.csv` mở được bằng Excel/LibreOffice, tiếng Việt đúng |
| 12 | Xóa theo thời hạn | Kiểm thử & dữ liệu: đặt thời hạn, nhấn "Xóa dữ liệu cũ" (hoặc `./lab.sh purge 30`) | Sự kiện cũ hơn thời hạn bị xóa |
| 13 | Website bị tắt | Quản lý website → Tắt | Tracker không gửi gì; `curl` trả 403 |
| 14 | Cú pháp PHP | `./lab.sh lint` | "Tất cả file PHP đều đúng cú pháp" |

### Dữ liệu mẫu
```bash
./lab.sh seed            # 100 phiên kiểm thử cho website đầu tiên
./lab.sh seed 1 500      # 500 phiên cho website #1
```
Xem bằng cách chọn **Loại dữ liệu = Dữ liệu kiểm thử** ở bộ lọc. Dữ liệu này không lẫn vào số liệu thật.

---

## 7. Khắc phục lỗi thường gặp trên Ubuntu / WSL

| Lỗi | Cách xử lý |
|---|---|
| `php: command not found` | `./lab.sh install` |
| `could not find driver` / thiếu `pdo_sqlite` | `sudo apt install php-sqlite3` rồi `./lab.sh check` |
| `./lab.sh: Permission denied` | `chmod +x lab.sh` |
| `bad interpreter: ^M` (file có CRLF do sửa trên Windows) | `sed -i 's/\r$//' lab.sh` |
| `Failed to listen on localhost:8000 (Address already in use)` | Đổi cổng: `PORT=9000 ./lab.sh run` |
| Không ghi được CSDL / log (`unable to open database file`, `readonly database`) | `chmod -R u+rwX data logs`. Trên WSL nên đặt dự án trong `~/` thay vì `/mnt/c/...` |
| Mở trang không thấy được từ trình duyệt Windows | Dùng `http://localhost:8000` (WSL2 tự chuyển tiếp cổng). Nếu không được: `HOST=0.0.0.0 ./lab.sh run` |
| Đăng nhập báo "Chưa khởi tạo cơ sở dữ liệu" | `./lab.sh init` |
| Quên mật khẩu admin | `./lab.sh admin admin MatKhauMoi123` |
| Tracker không ghi nhận, nhật ký demo hiện `HTTP 403` | URL website đã đăng ký phải cùng tên miền với trang gửi. Demo dùng `http://localhost:8000`, hãy mở bằng `localhost` thay vì `127.0.0.1` hoặc sửa URL website trong trang Quản lý website |
| Tracker không ghi nhận, `HTTP 422` | Chưa có (hoặc đang tắt) quy tắc cho nút/trang đó; thêm ở trang Quy tắc sự kiện |
| Dashboard trống | Đang lọc sai khoảng ngày/website, hoặc đang chọn "Dữ liệu kiểm thử"/"Dữ liệu thật" không đúng loại |
| Cài lại từ đầu | `./lab.sh reset` rồi `./lab.sh init` |

## 8. Quản lý mã nguồn bằng Git
```bash
git init
git add .
git commit -m "WebInsight - Đề tài 17"
```
File `.gitignore` đã loại trừ CSDL, log và file zip.

## 9. Bảo mật (mức bài tập lớn)
Mật khẩu băm bằng `password_hash`; truy vấn dùng prepared statement; CSRF token cho mọi biểu mẫu quản trị; tái tạo session khi đăng nhập; chặn dò mật khẩu; API key riêng từng website, kiểm tra Origin, kiểm tra định dạng dữ liệu, giới hạn kích thước (4 KB) và tần suất (120 sự kiện/phiên/phút); chống CSV injection; log lỗi giới hạn dung lượng. Khi triển khai thật nên dùng HTTPS và máy chủ Apache/Nginx thay cho `php -S`.

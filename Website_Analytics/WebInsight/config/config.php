<?php
// Cấu hình chung của WebInsight (có thể chỉnh sửa theo nhu cầu).
return [
    'app_name'     => 'WebInsight',
    'timezone'     => 'Asia/Ho_Chi_Minh',

    // Cơ sở dữ liệu SQLite nằm trong thư mục data/
    'db_path'      => __DIR__ . '/../data/webinsight.sqlite',

    // Log lỗi, tự cắt bớt khi vượt quá dung lượng tối đa (byte)
    'log_file'      => __DIR__ . '/../logs/error.log',
    'log_max_bytes' => 262144, // 256 KB

    // Tài khoản quản trị mặc định (được tạo bởi scripts/init_db.php)
    'default_admin_user' => 'admin',
    'default_admin_pass' => 'Admin@123',

    // Thời hạn lưu dữ liệu mặc định (ngày)
    'default_retention_days' => 90,

    // Giới hạn bảo vệ API thu thập
    'max_payload_bytes'              => 4096, // kích thước tối đa 1 yêu cầu
    'max_events_per_session_per_min' => 120,  // chống gửi dồn dập

    // Chống dò mật khẩu đăng nhập
    'login_max_attempts'   => 5,
    'login_window_minutes' => 10,
];

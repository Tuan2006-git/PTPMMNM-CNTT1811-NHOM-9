
<?php
require __DIR__ . '/../src/bootstrap.php';

if (wi_current_admin()) {
    wi_redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    try {
        [$ok, $error] = wi_try_login(
            trim((string) ($_POST['username'] ?? '')),
            (string) ($_POST['password'] ?? '')
        );

        if ($ok) {
            wi_redirect('dashboard.php');
        }
    } catch (PDOException $ex) {
        wi_log('login db error: ' . $ex->getMessage());
        $error = 'Chưa khởi tạo cơ sở dữ liệu. Hãy chạy ./lab.sh init rồi thử lại.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - WebInsight</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #ffffff;
        }

        /* BỐ CỤC TOÀN MÀN HÌNH */
        .login-shell {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            display: grid;
            grid-template-columns: 55% 45%;
            overflow: hidden;
        }

        /* ==========================
           BÊN TRÁI - GIỚI THIỆU
        ========================== */

        .login-left {
            position: relative;
            height: 100%;
            min-width: 0;
            overflow: hidden;
            padding: 5vh 8%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #ffffff;
            background: linear-gradient(
                135deg,
                #0754ce 0%,
                #075de5 50%,
                #103d9b 100%
            );
        }

        .login-left::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            top: -170px;
            right: -120px;
        }

        .login-left::after {
            content: "";
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            bottom: -280px;
            left: -150px;
        }

        .left-content {
            width: 100%;
            max-width: 700px;
            margin: auto;
            position: relative;
            z-index: 1;
        }

        /* LOGO */
        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 6vh;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            font-size: 30px;
        }

        .brand-name {
            font-size: 30px;
            font-weight: 750;
            letter-spacing: -0.5px;
        }

        /* TIÊU ĐỀ */
        .intro h1 {
            font-size: clamp(30px, 3vw, 48px);
            line-height: 1.25;
            font-weight: 750;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .intro p {
            max-width: 680px;
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        /* DASHBOARD MINH HỌA */
        .dashboard-preview {
            width: 100%;
            padding: 20px;
            color: #172b4d;
            background: #ffffff;
            border-radius: 17px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.18);
        }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        .preview-title {
            font-size: 17px;
            font-weight: bold;
        }

        .preview-status {
            padding: 7px 12px;
            border-radius: 20px;
            background: #e7f8ef;
            color: #15945c;
            font-size: 11px;
            white-space: nowrap;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .preview-stat {
            padding: 16px 13px;
            border-radius: 11px;
            background: #f2f6ff;
            min-width: 0;
        }

        .preview-stat span {
            display: block;
            color: #73819a;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .preview-stat strong {
            display: block;
            color: #1456c0;
            font-size: 25px;
        }

        /* BIỂU ĐỒ */
        .preview-chart {
            height: clamp(85px, 16vh, 155px);
            margin-top: 18px;
            padding: 12px 20px 0;
            border-radius: 11px;
            background: linear-gradient(
                180deg,
                #e8f1ff,
                #f7faff
            );
            display: flex;
            align-items: end;
            justify-content: space-around;
            overflow: hidden;
        }

        .chart-bar {
            width: 7%;
            min-width: 10px;
            background: linear-gradient(
                180deg,
                #4389f5,
                #1456c0
            );
            border-radius: 5px 5px 0 0;
        }

        /* TÍNH NĂNG */
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 25px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: rgba(255,255,255,0.95);
            white-space: nowrap;
        }

        .feature-icon {
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            font-weight: bold;
        }

        /* ==========================
           BÊN PHẢI - ĐĂNG NHẬP
        ========================== */

        .login-right {
            height: 100%;
            min-width: 0;
            padding: 45px 8%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .login-form-container {
            width: 100%;
            max-width: 520px;
        }

        .login-logo {
            color: #1765df;
            font-size: 42px;
            margin-bottom: 15px;
        }

        .login-heading {
            margin-bottom: 38px;
        }

        .login-heading h2 {
            color: #142c55;
            font-size: clamp(27px, 2.5vw, 36px);
            margin-bottom: 13px;
            font-weight: 750;
        }

        .login-heading p {
            color: #8190ac;
            font-size: 15px;
            line-height: 1.7;
        }

        /* LỖI ĐĂNG NHẬP */
        .login-error {
            padding: 13px 15px;
            margin-bottom: 22px;
            border: 1px solid #ffd5d2;
            border-radius: 9px;
            background: #fff0f0;
            color: #d92d20;
            font-size: 13px;
            line-height: 1.5;
        }

        /* FORM */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group label {
            display: block;
            color: #1b3158;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 11px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            width: 100%;
            height: 55px;
            padding: 0 48px;
            border: 1px solid #d5e0f3;
            border-radius: 11px;
            outline: none;
            background: #ffffff;
            color: #263b5c;
            font-size: 15px;
            transition: 0.2s;
        }

        .input-wrap input:focus {
            border-color: #2768e8;
            box-shadow: 0 0 0 3px rgba(39,104,232,0.1);
        }

        .input-wrap input::placeholder {
            color: #8c9ab2;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #7184a5;
            font-size: 18px;
            pointer-events: none;
        }

        /* HIỆN MẬT KHẨU */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #1765df;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        /* NÚT ĐĂNG NHẬP */
        .login-button {
            width: 100%;
            height: 58px;
            margin-top: 7px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(
                100deg,
                #1769e8,
                #1048b5
            );
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(23,105,232,0.2);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 48px;
            text-align: center;
            color: #98a5bd;
            font-size: 12px;
        }

        /* ==========================
           MÀN HÌNH THẤP
        ========================== */

        @media (min-width: 901px) and (max-height: 750px) {
            .login-left {
                padding-top: 20px;
                padding-bottom: 20px;
            }

            .brand {
                margin-bottom: 22px;
            }

            .brand-icon {
                width: 45px;
                height: 45px;
                font-size: 24px;
            }

            .brand-name {
                font-size: 25px;
            }

            .intro h1 {
                font-size: 34px;
                margin-bottom: 12px;
            }

            .intro p {
                font-size: 13px;
                margin-bottom: 17px;
            }

            .dashboard-preview {
                padding: 13px;
            }

            .preview-header {
                margin-bottom: 12px;
            }

            .preview-stat {
                padding: 10px;
            }

            .preview-stat strong {
                font-size: 21px;
            }

            .features {
                margin-top: 14px;
            }

            .login-heading {
                margin-bottom: 25px;
            }

            .login-form {
                gap: 17px;
            }

            .login-footer {
                margin-top: 28px;
            }
        }

        /* ==========================
           ĐIỆN THOẠI / TABLET
        ========================== */

        @media (max-width: 900px) {
            html, body {
                height: auto;
                min-height: 100%;
                overflow: auto;
            }

            .login-shell {
                position: relative;
                width: 100%;
                height: auto;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: visible;
            }

            .login-left {
                min-height: 650px;
                padding: 45px 7%;
            }

            .login-right {
                min-height: 650px;
                padding: 50px 7%;
            }

            .left-content {
                max-width: 700px;
            }

            .login-form-container {
                max-width: 550px;
            }
        }

        @media (max-width: 520px) {
            .login-left {
                min-height: auto;
                padding: 35px 22px;
            }

            .brand {
                margin-bottom: 35px;
            }

            .brand-name {
                font-size: 26px;
            }

            .intro h1 {
                font-size: 29px;
            }

            .intro p {
                font-size: 14px;
            }

            .preview-stats {
                gap: 7px;
            }

            .preview-stat {
                padding: 11px 8px;
            }

            .preview-stat span {
                font-size: 10px;
            }

            .preview-stat strong {
                font-size: 19px;
            }

            .features {
                gap: 11px;
            }

            .feature {
                font-size: 10px;
            }

            .login-right {
                min-height: 600px;
                padding: 40px 23px;
            }

            .login-heading h2 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="login-shell">

    <!-- BÊN TRÁI -->
    <section class="login-left">

        <div class="left-content">

            <div class="brand">
                <div class="brand-icon">📊</div>
                <div class="brand-name">WebInsight</div>
            </div>

            <div class="intro">
                <h1>
                    Thống kê lượt truy cập<br>
                    và thao tác website.
                </h1>

                <p>
                    WebInsight giúp bạn theo dõi lượt truy cập,
                    phân tích hành vi và thống kê tương tác của
                    người dùng trên website một cách trực quan.
                </p>
            </div>

            <div class="dashboard-preview">

                <div class="preview-header">
                    <div class="preview-title">
                        Tổng quan thống kê
                    </div>

                    <div class="preview-status">
                        ● Đang hoạt động
                    </div>
                </div>

                <div class="preview-stats">

                    <div class="preview-stat">
                        <span>Lượt truy cập</span>
                        <strong>2.8K</strong>
                    </div>

                    <div class="preview-stat">
                        <span>Lượt tương tác</span>
                        <strong>1.2K</strong>
                    </div>

                    <div class="preview-stat">
                        <span>Người dùng</span>
                        <strong>856</strong>
                    </div>

                </div>

                <div class="preview-chart">
                    <div class="chart-bar" style="height:35%"></div>
                    <div class="chart-bar" style="height:50%"></div>
                    <div class="chart-bar" style="height:42%"></div>
                    <div class="chart-bar" style="height:68%"></div>
                    <div class="chart-bar" style="height:55%"></div>
                    <div class="chart-bar" style="height:82%"></div>
                    <div class="chart-bar" style="height:70%"></div>
                    <div class="chart-bar" style="height:95%"></div>
                </div>

            </div>

            <div class="features">

                <div class="feature">
                    <span class="feature-icon">✓</span>
                    Theo dõi lượt truy cập
                </div>

                <div class="feature">
                    <span class="feature-icon">✓</span>
                    Phân tích tương tác
                </div>

                <div class="feature">
                    <span class="feature-icon">✓</span>
                    Báo cáo trực quan
                </div>

            </div>

        </div>

    </section>


    <!-- BÊN PHẢI -->
    <section class="login-right">

        <div class="login-form-container">

            <div class="login-logo">▥</div>

            <div class="login-heading">
                <h2>Chào mừng trở lại!</h2>

                <p>
                    Đăng nhập vào tài khoản quản trị để tiếp tục
                    sử dụng WebInsight.
                </p>
            </div>

            <?php if ($error): ?>
                <div class="login-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="login-form" autocomplete="off">

                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">
                        Tên đăng nhập
                    </label>

                    <div class="input-wrap">
                        <span class="input-icon">♙</span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Nhập tên đăng nhập"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="loginPassword">
                        Mật khẩu
                    </label>

                    <div class="input-wrap">
                        <span class="input-icon">♙</span>

                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            placeholder="Nhập mật khẩu"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                        >
                            Hiện
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Đăng nhập
                    <span style="margin-left:12px">→</span>
                </button>

            </form>

            <div class="login-footer">
                © <?= date('Y') ?> WebInsight. All rights reserved.
            </div>

        </div>

    </section>

</div>

<script>
function togglePassword() {
    const password = document.getElementById('loginPassword');
    const button = document.querySelector('.toggle-password');

    if (password.type === 'password') {
        password.type = 'text';
        button.textContent = 'Ẩn';
    } else {
        password.type = 'password';
        button.textContent = 'Hiện';
    }
}
</script>

</body>
</html>

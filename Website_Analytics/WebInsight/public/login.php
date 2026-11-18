
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="auth-body">

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

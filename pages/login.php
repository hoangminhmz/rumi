<?php
/**
 * RUMI - Login Page
 * Zalo Login integration
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/zalo.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/pages/swipe.php');
}

$pageTitle = 'Đăng nhập';
$zaloLoginUrl = getZaloLoginURL();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - RUMI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">

    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-gray-50);
            padding: var(--space-3);
        }

        .login-card {
            max-width: 400px;
            width: 100%;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-3);
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <div class="login-logo">R</div>

                    <h1 style="font-size: var(--font-size-2xl); margin-bottom: var(--space-1);">Đăng nhập RUMI</h1>
                    <p class="text-secondary mb-4">Bắt đầu tìm bạn cùng phòng ngay hôm nay</p>

                    <?php if ($zaloLoginUrl === '#zalo-not-configured'): ?>
                        <!-- Zalo chưa được cấu hình -->
                        <div class="alert alert-warning" role="alert">
                            <strong>⚠️ Zalo Login chưa được cấu hình</strong>
                            <p class="mb-2 mt-2">Để sử dụng tính năng đăng nhập Zalo, vui lòng:</p>
                            <ol class="mb-0" style="text-align: left; font-size: 14px;">
                                <li>Đăng ký app tại <a href="https://developers.zalo.me/" target="_blank">Zalo Developer Portal</a></li>
                                <li>Lấy <strong>App ID</strong> và <strong>App Secret</strong></li>
                                <li>Cập nhật vào file <code>config/zalo.php</code></li>
                            </ol>
                        </div>
                        <button class="btn btn-secondary btn-block btn-lg" disabled>
                            <svg style="width: 24px; height: 24px; margin-right: var(--space-1);" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/>
                            </svg>
                            Đăng nhập với Zalo (Chưa cấu hình)
                        </button>
                    <?php else: ?>
                        <!-- Zalo đã được cấu hình -->
                        <a href="<?= e($zaloLoginUrl) ?>" class="btn btn-primary btn-block btn-lg">
                            <svg style="width: 24px; height: 24px; margin-right: var(--space-1);" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/>
                            </svg>
                            Đăng nhập với Zalo
                        </a>
                    <?php endif; ?>

                    <div class="mt-4">
                        <small class="text-secondary">
                            Bằng việc đăng nhập, bạn đồng ý với
                            <a href="#">Điều khoản sử dụng</a> và
                            <a href="#">Chính sách bảo mật</a>
                        </small>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <small class="text-secondary">
                            Chưa có tài khoản Zalo? <a href="https://zalo.me" target="_blank">Tải Zalo</a>
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>" class="text-secondary">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/**
 * RUMI - Main Entry Point
 * Landing page và router
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

// If user is logged in, redirect to swipe page
if (isLoggedIn()) {
    redirect(BASE_URL . '/pages/swipe.php');
}

// Show landing page
$pageTitle = 'Tìm bạn cùng phòng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RUMI - Tìm bạn cùng phòng dễ dàng như Tinder. Swipe để match với người cùng phòng hoặc phòng trọ phù hợp.">
    <meta name="theme-color" content="#00D4AA">
    <title><?= e($pageTitle) ?> - RUMI</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- RUMI Styles -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/components.css">

    <style>
        .landing-hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
            padding: var(--space-4);
            text-align: center;
            color: white;
        }

        .landing-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            box-shadow: var(--shadow-xl);
        }

        .landing-logo-text {
            font-size: 60px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .landing-title {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--space-2);
        }

        .landing-subtitle {
            font-size: var(--font-size-lg);
            opacity: 0.9;
            margin-bottom: var(--space-6);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-4);
            margin-top: var(--space-8);
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-2);
            color: var(--color-primary);
        }

        @media (max-width: 767px) {
            .landing-title {
                font-size: var(--font-size-2xl);
            }
        }
    </style>
</head>
<body>
    <div class="landing-hero">
        <div>
            <div class="landing-logo">
                <div class="landing-logo-text">R</div>
            </div>

            <h1 class="landing-title">Chào mừng đến RUMI</h1>
            <p class="landing-subtitle">
                Tìm bạn cùng phòng hoặc phòng trọ phù hợp dễ dàng như swipe Tinder.
                Match ngay hôm nay!
            </p>

            <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-lg">
                <svg style="width: 24px; height: 24px; margin-right: var(--space-1);" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
                Đăng nhập với Zalo
            </a>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg style="width: 30px; height: 30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--space-1);">Swipe để match</h3>
                    <p style="font-size: var(--font-size-sm); opacity: 0.9;">
                        Vuốt trái/phải để tìm người phù hợp với sở thích của bạn
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg style="width: 30px; height: 30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--space-1);">Kết nối nhanh chóng</h3>
                    <p style="font-size: var(--font-size-sm); opacity: 0.9;">
                        Match thành công? Liên hệ ngay qua Zalo để deal chi tiết
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg style="width: 30px; height: 30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--space-1);">Tìm phòng trước</h3>
                    <p style="font-size: var(--font-size-sm); opacity: 0.9;">
                        Đã có phòng? Đăng listing để tìm người thuê phù hợp
                    </p>
                </div>
            </div>

            <div style="margin-top: var(--space-8); font-size: var(--font-size-sm); opacity: 0.8;">
                <p>🏠 Miễn phí cho người tìm phòng | 💰 Phí đăng tin cho chủ nhà</p>
                <p>Hỗ trợ: Hà Nội • TP.HCM • Đà Nẵng</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

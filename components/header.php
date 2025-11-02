<?php
/**
 * RUMI - Header Component
 * Main header với logo và user info
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$currentUser = null;
if (isLoggedIn()) {
    require_once __DIR__ . '/../includes/User.php';
    $userModel = new User();
    $currentUser = $userModel->getById(getCurrentUserId());
}

$pageTitle = $pageTitle ?? 'RUMI';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="RUMI - Tìm bạn cùng phòng dễ dàng như Tinder">
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
</head>
<body>
    <!-- Flash Messages -->
    <?php
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="toast-container">
        <div class="toast toast-<?= $flash['type'] ?>">
            <div><?= e($flash['message']) ?></div>
        </div>
    </div>
    <script>
        // Auto hide toast after 3s
        setTimeout(() => {
            document.querySelector('.toast-container').style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>

    <!-- Top Header (Desktop) -->
    <?php if (isLoggedIn()): ?>
    <header class="navbar hide-mobile">
        <div class="container">
            <div class="d-flex justify-between align-center">
                <a href="<?= BASE_URL ?>" class="d-flex align-center gap-2">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px;">
                        R
                    </div>
                    <h1 style="margin: 0; font-size: var(--font-size-xl); color: var(--color-primary);">RUMI</h1>
                </a>

                <?php if ($currentUser): ?>
                <div class="d-flex align-center gap-3">
                    <span class="text-secondary"><?= e($currentUser['name']) ?></span>
                    <img src="<?= $currentUser['avatar'] ? getUploadURL($currentUser['avatar']) : ASSETS_URL . '/images/default-avatar.svg' ?>"
                         alt="Avatar"
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php endif; ?>

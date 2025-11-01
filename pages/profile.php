<?php
/**
 * RUMI - User Profile Page
 * View and edit user profile
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Room.php';

startSession();
requireLogin();

$userModel = new User();
$roomModel = new Room();
$currentUser = $userModel->getById(getCurrentUserId());
$myRooms = $roomModel->getByOwner(getCurrentUserId());

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    redirect(BASE_URL);
}

$pageTitle = 'Cá nhân';
include __DIR__ . '/../components/header.php';
?>

<div class="container" style="padding-top: var(--space-4); padding-bottom: var(--space-8);">
    <!-- Profile Card -->
    <div class="profile-card mb-4">
        <div class="profile-header"></div>
        <img src="<?= $currentUser['avatar'] ? getUploadURL($currentUser['avatar']) : ASSETS_URL . '/images/default-avatar.png' ?>"
             alt="Avatar" class="profile-avatar">

        <div class="profile-body">
            <h2 class="profile-name"><?= e($currentUser['name']) ?></h2>

            <div class="profile-meta">
                <span><?= $currentUser['age'] ?> tuổi</span>
                <span>•</span>
                <span><?= getGenderLabel($currentUser['gender']) ?></span>
                <span>•</span>
                <span><?= e($currentUser['district_name']) ?></span>
            </div>

            <?php if (!empty($currentUser['bio'])): ?>
            <p class="profile-bio"><?= e($currentUser['bio']) ?></p>
            <?php endif; ?>

            <div class="profile-tags mb-3">
                <span class="badge badge-primary"><?= getSearchModeLabel($currentUser['search_mode']) ?></span>

                <?php
                $preferences = $currentUser['preferences'];
                if (!empty($preferences)):
                ?>
                    <?php if (isset($preferences['budget_min'], $preferences['budget_max'])): ?>
                    <span class="badge badge-secondary"><?= formatPrice($preferences['budget_min']) ?> - <?= formatPrice($preferences['budget_max']) ?></span>
                    <?php endif; ?>

                    <?php if (isset($preferences['smoking']) && $preferences['smoking'] === false): ?>
                    <span class="badge badge-success">Không hút thuốc</span>
                    <?php endif; ?>

                    <?php if (isset($preferences['pets']) && $preferences['pets'] === true): ?>
                    <span class="badge badge-success">Thích thú cưng</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/pages/profile-setup.php?edit=1" class="btn btn-outline flex-1">
                    Chỉnh sửa profile
                </a>
                <a href="?action=logout" class="btn btn-outline flex-1">
                    Đăng xuất
                </a>
            </div>
        </div>
    </div>

    <!-- My Rooms -->
    <?php if (!empty($myRooms)): ?>
    <h3 class="mb-3">Phòng của bạn</h3>
    <?php foreach ($myRooms as $room): ?>
        <div class="room-card mb-3">
            <div class="room-card-images">
                <?php
                $images = $room['images'] ?? [];
                $firstImage = !empty($images) ? getUploadURL($images[0]) : ASSETS_URL . '/images/default-room.jpg';
                ?>
                <img src="<?= e($firstImage) ?>" alt="<?= e($room['title']) ?>" class="room-card-img">
                <div class="room-card-price"><?= formatPrice($room['price']) ?></div>
            </div>

            <div class="room-card-body">
                <h4 class="room-card-title"><?= e($room['title']) ?></h4>
                <div class="room-card-address">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span><?= e($room['district_name']) ?>, <?= e($room['city_name']) ?></span>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <span class="badge <?= $room['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                        <?= ucfirst($room['status']) ?>
                    </span>
                    <span class="badge badge-secondary"><?= $room['views_count'] ?> views</span>
                    <span class="badge badge-secondary"><?= $room['likes_count'] ?> likes</span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

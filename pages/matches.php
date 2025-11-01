<?php
/**
 * RUMI - Matches Page
 * Show all matches for current user
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Match.php';
require_once __DIR__ . '/../components/cards.php';

startSession();
requireLogin();

$matchModel = new Match();
$matches = $matchModel->getByUser(getCurrentUserId());
$stats = $matchModel->getStats(getCurrentUserId());

$pageTitle = 'Matches';
include __DIR__ . '/../components/header.php';
?>

<div class="container" style="padding-top: var(--space-4); padding-bottom: var(--space-8);">
    <h2 class="mb-4">Matches của bạn</h2>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--color-primary); margin: 0;"><?= $stats['total_matches'] ?? 0 ?></h3>
                    <small class="text-secondary">Tổng matches</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--color-warning); margin: 0;"><?= $stats['pending_matches'] ?? 0 ?></h3>
                    <small class="text-secondary">Chờ kết nối</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--color-success); margin: 0;"><?= $stats['connected_matches'] ?? 0 ?></h3>
                    <small class="text-secondary">Đã kết nối</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Matches List -->
    <?php if (empty($matches)): ?>
        <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <h3 class="empty-title">Chưa có match nào</h3>
            <p class="empty-text">Swipe để tìm người phù hợp với bạn</p>
            <a href="<?= BASE_URL ?>/pages/swipe.php" class="btn btn-primary">Bắt đầu swipe</a>
        </div>
    <?php else: ?>
        <?php foreach ($matches as $match): ?>
            <?php renderMatchItem($match, getCurrentUserId()); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

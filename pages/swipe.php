<?php
/**
 * RUMI - Swipe Interface
 * Main swipe page cho matching
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Room.php';
require_once __DIR__ . '/../components/cards.php';

startSession();
requireLogin();

$userModel = new User();
$roomModel = new Room();
$currentUser = $userModel->getById(getCurrentUserId());

// Check profile complete
if (!$userModel->hasCompleteProfile(getCurrentUserId())) {
    redirect(BASE_URL . '/pages/profile-setup.php');
}

// Get search mode - Check URL param first, then user preference
$searchMode = $_GET['mode'] ?? $currentUser['search_mode'] ?? 'find_roommate';

// Validate search mode
if (!in_array($searchMode, ['find_roommate', 'find_room'])) {
    $searchMode = 'find_roommate';
}

// Update user's search mode if changed via URL
if (isset($_GET['mode']) && $searchMode !== $currentUser['search_mode']) {
    $userModel->updateSearchMode(getCurrentUserId(), $searchMode);
}

// Get cards based on mode
if ($searchMode === 'find_roommate') {
    $cards = $userModel->getPotentialMatches(getCurrentUserId());
} else {
    $cards = $roomModel->getPotentialRooms(getCurrentUserId(), $currentUser['district_id']);
}

$pageTitle = 'Swipe';
include __DIR__ . '/../components/header.php';
?>

<div class="container" style="padding-top: var(--space-4); padding-bottom: var(--space-8);">
    <!-- Mode Toggle -->
    <div class="d-flex justify-center mb-4">
        <div class="btn-group">
            <a href="?mode=find_roommate" class="btn <?= $searchMode === 'find_roommate' ? 'btn-primary' : 'btn-outline' ?>">
                Tìm người
            </a>
            <a href="?mode=find_room" class="btn <?= $searchMode === 'find_room' ? 'btn-primary' : 'btn-outline' ?>">
                Tìm phòng
            </a>
        </div>
    </div>

    <!-- Swipe Container -->
    <div class="swipe-container" id="swipeContainer">
        <div class="swipe-cards" id="swipeCards">
            <?php if (empty($cards)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="empty-title">Hết cards rồi!</h3>
                    <p class="empty-text">Quay lại sau để xem thêm match mới nhé</p>
                    <a href="<?= BASE_URL ?>/pages/matches.php" class="btn btn-primary">Xem Matches của bạn</a>
                </div>
            <?php else: ?>
                <?php
                // Render cards based on mode
                foreach ($cards as $card) {
                    if ($searchMode === 'find_roommate') {
                        renderUserCard($card);
                    } else {
                        renderRoomCard($card);
                    }
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Swipe Actions -->
    <?php if (!empty($cards)): ?>
    <div class="swipe-actions">
        <button class="swipe-btn swipe-btn-nope" id="btnNope">
            <svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>

        <button class="swipe-btn swipe-btn-like" id="btnLike" style="width: 70px; height: 70px;">
            <svg fill="currentColor" viewBox="0 0 24 24" style="width: 36px; height: 36px;">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Match Modal -->
<div id="matchModal" class="match-modal" style="display: none;">
    <div class="match-content">
        <h2 class="match-title">🎉 It's a Match!</h2>
        <div class="match-avatars">
            <img id="matchAvatar1" class="match-avatar" src="" alt="">
            <div class="match-heart">❤️</div>
            <img id="matchAvatar2" class="match-avatar" src="" alt="">
        </div>
        <p style="margin-bottom: var(--space-4);">Bạn và <strong id="matchName"></strong> đã match thành công!</p>
        <div class="d-flex gap-2">
            <button class="btn btn-outline flex-1" onclick="closeMatchModal()">Tiếp tục swipe</button>
            <a href="<?= BASE_URL ?>/pages/matches.php" class="btn btn-primary flex-1">Xem Matches</a>
        </div>
    </div>
</div>

<?php
// Include navigation
include __DIR__ . '/../components/navigation.php';

// Additional script for swipe functionality
$additionalScripts = '<script src="' . ASSETS_URL . '/js/swipe.js"></script>';
$additionalScripts .= '<script>
    const SEARCH_MODE = "' . $searchMode . '";
    const API_URL = "' . BASE_URL . '/api";
    const ASSETS_URL = "' . ASSETS_URL . '";
</script>';

include __DIR__ . '/../components/footer.php';
?>

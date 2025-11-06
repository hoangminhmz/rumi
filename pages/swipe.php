<?php
/**
 * RUMI - Swipe Interface (Button-Only Design)
 * Clean, simple matching interface with large buttons
 *
 * DEBUG MODE ENABLED - Check logs/swipe_errors.log for errors
 */

// ========== ERROR HANDLING & DEBUG ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create logs directory if not exists
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/swipe_errors.log');

// Log function for debugging
function debugLog($message, $data = null) {
    $logFile = __DIR__ . '/../logs/swipe_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, $logFile);
}

debugLog("========== SWIPE.PHP START ==========");

try {
    debugLog("Step 1: Loading dependencies");

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/User.php';
    require_once __DIR__ . '/../includes/Room.php';
    require_once __DIR__ . '/../includes/Match.php';

    debugLog("Step 2: Dependencies loaded successfully");

    // Check if empty-state-locked.php exists
    $emptyStateFile = __DIR__ . '/../components/empty-state-locked.php';
    if (file_exists($emptyStateFile)) {
        require_once $emptyStateFile;
        debugLog("Step 3: empty-state-locked.php loaded");
    } else {
        debugLog("WARNING: empty-state-locked.php not found at: $emptyStateFile");
    }

    debugLog("Step 4: Starting session");
    startSession();
    requireLogin();

    $userId = getCurrentUserId();
    debugLog("Step 5: User ID", $userId);

    debugLog("Step 6: Initializing models");
    $userModel = new User();
    $roomModel = new Room();
    $matchModel = new MatchModel();

    debugLog("Step 7: Getting current user");
    $currentUser = $userModel->getById($userId);

    if (!$currentUser) {
        throw new Exception("User not found for ID: $userId");
    }
    debugLog("Step 8: Current user loaded", ['id' => $userId, 'name' => $currentUser['name'] ?? 'N/A']);

    // Check profile complete
    debugLog("Step 9: Checking profile completeness");
    if (!$userModel->hasCompleteProfile($userId)) {
        debugLog("Profile incomplete, redirecting to profile-setup");
        redirect(BASE_URL . '/pages/profile-setup.php');
        exit;
    }

    // Get search mode (what tab user is viewing)
    $searchMode = $_GET['mode'] ?? 'find_roommate';
    debugLog("Step 10: Search mode", $searchMode);

    // Validate search mode
    if (!in_array($searchMode, ['find_roommate', 'find_room'])) {
        $searchMode = 'find_roommate';
    }

    // Get user's preference mode (what they chose to do first)
    $userMode = $currentUser['search_mode'] ?? 'find_roommate_first';
    debugLog("Step 11: User mode", $userMode);

    // Initialize cards array and locked state
    $cards = [];
    $isLocked = false;

    // ============================================
    // TWO-STAGE MATCHING LOGIC
    // ============================================

    debugLog("Step 12: Starting two-stage matching logic");

    if ($searchMode === 'find_roommate') {
        debugLog("Step 13: Find roommate mode");
        // ========== USER IS VIEWING ROOMMATE TAB ==========

        if ($userMode === 'find_room_first') {
            debugLog("Step 14: User mode is find_room_first");
            // User chose find_room_first, so check if they can access roommate tab

            try {
                $canAccess = $userModel->canAccessRoommateTab($userId);
                debugLog("Step 15: Can access roommate tab", $canAccess);

                if (!$canAccess) {
                    // LOCKED: User hasn't liked any rooms yet
                    $isLocked = true;
                    debugLog("Step 16: Tab is LOCKED");
                } else {
                    // UNLOCKED: Get users who liked same rooms
                    debugLog("Step 17: Tab is UNLOCKED, getting liked rooms");
                    $likedRoomIds = $userModel->getLikedRoomIds($userId);
                    debugLog("Step 18: Liked room IDs", $likedRoomIds);

                    $cards = $matchModel->getUsersWhoLikedSameRooms($userId, $likedRoomIds);
                    debugLog("Step 19: Got cards", ['count' => count($cards)]);
                }
            } catch (Exception $e) {
                debugLog("ERROR in find_room_first logic: " . $e->getMessage());
                throw $e;
            }
        } else {
            debugLog("Step 20: User mode is find_roommate_first");
            // Normal find_roommate_first mode - always accessible
            try {
                $cards = $userModel->getPotentialMatches($userId);
                debugLog("Step 21: Got potential matches", ['count' => count($cards)]);
            } catch (Exception $e) {
                debugLog("ERROR in getPotentialMatches: " . $e->getMessage());
                throw $e;
            }
        }

    } elseif ($searchMode === 'find_room') {
        debugLog("Step 22: Find room mode");
        // ========== USER IS VIEWING ROOM TAB ==========

        // Decode user preferences for room filtering
        $userPreferences = is_string($currentUser['preferences'])
            ? json_decode($currentUser['preferences'], true)
            : $currentUser['preferences'];

        if (!is_array($userPreferences)) {
            $userPreferences = [];
        }
        debugLog("Step 23: User preferences", $userPreferences);

        // Override preferences with URL params if present (from filter)
        if (isset($_GET['budget_min'])) {
            $userPreferences['budget_min'] = (int)$_GET['budget_min'];
        }
        if (isset($_GET['budget_max'])) {
            $userPreferences['budget_max'] = (int)$_GET['budget_max'];
        }
        if (isset($_GET['cleanliness'])) {
            $userPreferences['cleanliness'] = (int)$_GET['cleanliness'];
        }
        if (isset($_GET['noise_tolerance'])) {
            $userPreferences['noise_tolerance'] = (int)$_GET['noise_tolerance'];
        }
        if (isset($_GET['smoking'])) {
            $userPreferences['smoking'] = $_GET['smoking'] === '1';
        }
        if (isset($_GET['pets'])) {
            $userPreferences['pets'] = $_GET['pets'] === '1';
        }

        if ($userMode === 'find_roommate_first') {
            debugLog("Step 24: User mode is find_roommate_first in room tab");
            // User chose find_roommate_first, so check if they can access room tab

            try {
                $canAccess = $userModel->canAccessRoomTab($userId);
                debugLog("Step 25: Can access room tab", $canAccess);

                if (!$canAccess) {
                    // LOCKED: User hasn't matched with anyone yet
                    $isLocked = true;
                    debugLog("Step 26: Tab is LOCKED");
                } else {
                    // UNLOCKED: Get rooms suitable for user + all their matches
                    debugLog("Step 27: Tab is UNLOCKED, getting matched users");
                    $matchedUserIds = $matchModel->getMatchedUserIds($userId);
                    debugLog("Step 28: Matched user IDs", $matchedUserIds);

                    $cards = $roomModel->getRoomsForAllMatches($userId, $matchedUserIds, $userPreferences);
                    debugLog("Step 29: Got rooms for matches", ['count' => count($cards)]);
                }
            } catch (Exception $e) {
                debugLog("ERROR in find_roommate_first room tab logic: " . $e->getMessage());
                throw $e;
            }
        } else {
            debugLog("Step 30: User mode is find_room_first");
            // Normal find_room_first mode - always accessible
            try {
                $cards = $roomModel->getPotentialRooms($userId, $currentUser['district_id'], $userPreferences);
                debugLog("Step 31: Got potential rooms", ['count' => count($cards)]);
            } catch (Exception $e) {
                debugLog("ERROR in getPotentialRooms: " . $e->getMessage());
                throw $e;
            }
        }
    }

    debugLog("Step 32: Logic completed successfully", ['cards_count' => count($cards), 'is_locked' => $isLocked]);

} catch (Exception $e) {
    debugLog("========== FATAL ERROR ==========");
    debugLog("Error message: " . $e->getMessage());
    debugLog("Error file: " . $e->getFile());
    debugLog("Error line: " . $e->getLine());
    debugLog("Stack trace: " . $e->getTraceAsString());

    // Display user-friendly error
    die('
    <html>
    <head><title>Error - RUMI</title></head>
    <body style="font-family: sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto;">
        <h1 style="color: #dc2626;">⚠️ Error Loading Page</h1>
        <div style="background: #fef2f2; padding: 1rem; border-left: 4px solid #dc2626; margin: 1rem 0;">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>
        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px;">
            <strong>Debug Information:</strong><br>
            File: ' . htmlspecialchars($e->getFile()) . '<br>
            Line: ' . $e->getLine() . '<br><br>
            <strong>Check logs:</strong> logs/swipe_debug.log and logs/swipe_errors.log
        </div>
        <p><a href="' . BASE_URL . '" style="color: #2563eb;">← Go back to homepage</a></p>
    </body>
    </html>
    ');
}

$pageTitle = 'Tìm kiếm';
include __DIR__ . '/../components/header.php';
?>

<style>
/* Button-Only Card Design */
.swipe-page {
    max-width: 500px;
    margin: 0 auto;
    padding: 1rem;
    padding-bottom: 5rem;
}

.mode-toggle {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.mode-toggle .btn {
    flex: 1;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.2s;
}

/* Single Large Card - Optimized for Viewport */
.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
    max-height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.profile-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.profile-card.animate-out-left {
    animation: slideOutLeft 0.4s cubic-bezier(0.4, 0, 1, 1) forwards;
}

.profile-card.animate-out-right {
    animation: slideOutRight 0.4s cubic-bezier(0.4, 0, 1, 1) forwards;
}

.profile-card.animate-in {
    animation: slideInUp 0.4s cubic-bezier(0, 0, 0.2, 1) forwards;
    opacity: 0;
}

@keyframes slideOutLeft {
    to {
        transform: translateX(-150%) rotate(-15deg);
        opacity: 0;
    }
}

@keyframes slideOutRight {
    to {
        transform: translateX(150%) rotate(15deg);
        opacity: 0;
    }
}

@keyframes slideInUp {
    from {
        transform: translateY(30px) scale(0.95);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

/* Card Image - Optimized Height */
.profile-card-image {
    width: 100%;
    height: min(45vh, 320px);
    object-fit: cover;
    display: block;
    flex-shrink: 0;
}

/* Card Content */
.profile-card-content {
    padding: 1.5rem;
    flex: 1;
    overflow-y: auto;
}

.profile-card-header {
    margin-bottom: 1rem;
}

.profile-card-name {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: var(--color-gray-900);
}

.profile-card-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray-600);
    font-size: 0.95rem;
}

.profile-card-location svg {
    width: 18px;
    height: 18px;
}

.profile-card-bio {
    color: var(--color-gray-700);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.profile-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.profile-card-tags .badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1rem;
    padding: 0 1rem;
}

.action-btn {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 16px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.action-btn:active {
    transform: scale(0.95);
}

.action-btn-pass {
    background: #f3f4f6;
    color: #6b7280;
}

.action-btn-pass:hover {
    background: #e5e7eb;
}

.action-btn-like {
    background: var(--color-primary);
    color: white;
}

.action-btn-like:hover {
    background: var(--color-accent);
}

.action-btn svg {
    width: 24px;
    height: 24px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    color: var(--color-gray-400);
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-gray-700);
    margin-bottom: 0.5rem;
}

.empty-text {
    color: var(--color-gray-600);
    margin-bottom: 1.5rem;
}

/* Match Modal */
.match-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.match-content {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    animation: scaleIn 0.3s cubic-bezier(0, 0, 0.2, 1);
}

@keyframes scaleIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.match-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
}

.match-avatars {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.match-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.match-heart {
    font-size: 2rem;
    animation: heartBeat 0.6s infinite;
}

@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}
</style>

<div class="swipe-page">
    <!-- Header with Mode Toggle and Filter Button -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; align-items: center;">
        <div class="mode-toggle" style="flex: 1; margin-bottom: 0;">
            <a href="?mode=find_roommate" class="btn <?= $searchMode === 'find_roommate' ? 'btn-primary' : 'btn-outline' ?>">
                👥 Tìm người
            </a>
            <a href="?mode=find_room" class="btn <?= $searchMode === 'find_room' ? 'btn-primary' : 'btn-outline' ?>">
                🏠 Tìm phòng
            </a>
        </div>
        <button class="btn btn-outline" style="padding: 0.75rem 1rem; border-radius: 12px;" onclick="openFilterModal()">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
        </button>
    </div>

    <!-- Card Container -->
    <div id="cardContainer">
        <?php if ($isLocked): ?>
            <?php
            // Show locked state
            renderLockedTabState($userMode, $searchMode);
            ?>
        <?php elseif (empty($cards)): ?>
            <div class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="empty-title">Hết profiles rồi!</h3>
                <p class="empty-text">Quay lại sau để xem thêm match mới nhé</p>
                <a href="<?= BASE_URL ?>/pages/matches.php" class="btn btn-primary">Xem Matches</a>
            </div>
        <?php else: ?>
            <!-- Show only first card, rest loaded via JS -->
            <?php $firstCard = $cards[0]; ?>
            <?php if ($searchMode === 'find_roommate'): ?>
                <!-- User Card -->
                <div class="profile-card" data-user-id="<?= $firstCard['id'] ?>">
                    <img src="<?= $firstCard['avatar'] ? getUploadURL($firstCard['avatar']) : ASSETS_URL . '/images/default-avatar.svg' ?>"
                         alt="<?= e($firstCard['name']) ?>"
                         class="profile-card-image">

                    <div class="profile-card-content">
                        <div class="profile-card-header">
                            <h2 class="profile-card-name"><?= e($firstCard['name']) ?>, <?= $firstCard['age'] ?></h2>
                            <div class="profile-card-location">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <?= e($firstCard['district_name']) ?>, <?= e($firstCard['city_name']) ?>
                            </div>
                        </div>

                        <?php if (!empty($firstCard['bio'])): ?>
                        <p class="profile-card-bio"><?= e($firstCard['bio']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($firstCard['preferences'])): ?>
                        <div class="profile-card-tags">
                            <?php $prefs = $firstCard['preferences']; ?>
                            <?php if (isset($prefs['budget_min'], $prefs['budget_max'])): ?>
                            <span class="badge badge-primary">💰 <?= formatPrice($prefs['budget_min']) ?> - <?= formatPrice($prefs['budget_max']) ?></span>
                            <?php endif; ?>
                            <?php if (isset($prefs['cleanliness'])): ?>
                            <span class="badge badge-primary">✨ Sạch: <?= $prefs['cleanliness'] ?>/5</span>
                            <?php endif; ?>
                            <?php if (isset($prefs['smoking']) && $prefs['smoking'] === false): ?>
                            <span class="badge badge-success">🚭 Không hút thuốc</span>
                            <?php endif; ?>
                            <?php if (isset($prefs['pets']) && $prefs['pets'] === true): ?>
                            <span class="badge badge-success">🐕 Thích thú cưng</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Room Card -->
                <div class="profile-card" data-room-id="<?= $firstCard['id'] ?>">
                    <?php
                    $images = $firstCard['images'] ?? [];
                    $firstImage = !empty($images) ? getUploadURL($images[0]) : ASSETS_URL . '/images/default-room.svg';
                    ?>
                    <img src="<?= e($firstImage) ?>"
                         alt="<?= e($firstCard['title']) ?>"
                         class="profile-card-image">

                    <div class="profile-card-content">
                        <div class="profile-card-header">
                            <h2 class="profile-card-name"><?= formatPrice($firstCard['price']) ?>/tháng</h2>
                            <div class="profile-card-location">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <?= e($firstCard['district_name']) ?>, <?= e($firstCard['city_name']) ?>
                                <?php if (!empty($firstCard['distance_formatted'])): ?>
                                    <span style="margin-left: 0.5rem; color: var(--color-primary); font-weight: 600;">
                                        • <?= e($firstCard['distance_formatted']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;"><?= e($firstCard['title']) ?></h3>

                        <?php if (!empty($firstCard['description'])): ?>
                        <p class="profile-card-bio"><?= e(truncate($firstCard['description'], 120)) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($firstCard['amenities'])): ?>
                        <div class="profile-card-tags">
                            <?php if ($firstCard['area']): ?>
                            <span class="badge badge-primary">📐 <?= $firstCard['area'] ?>m²</span>
                            <?php endif; ?>
                            <?php
                            $amenityLabels = [
                                'wifi' => '📶 Wifi',
                                'ac' => '❄️ Điều hòa',
                                'kitchen' => '🍳 Bếp',
                                'parking' => '🅿️ Chỗ xe',
                                'laundry' => '🧺 Máy giặt',
                                'furniture' => '🛋️ Nội thất'
                            ];
                            $count = 0;
                            foreach ($firstCard['amenities'] as $key => $value):
                                if ($value && isset($amenityLabels[$key]) && $count < 5):
                                    $count++;
                            ?>
                            <span class="badge badge-success"><?= $amenityLabels[$key] ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn action-btn-pass" id="btnPass">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                    Pass
                </button>
                <button class="action-btn action-btn-like" id="btnLike">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    Like
                </button>
            </div>
        <?php endif; ?>
    </div>
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
        <p style="margin-bottom: 1.5rem;">Bạn và <strong id="matchName"></strong> đã match thành công!</p>
        <div style="display: flex; gap: 1rem;">
            <button class="btn btn-outline" style="flex: 1;" onclick="closeMatchModal()">Tiếp tục</button>
            <a href="<?= BASE_URL ?>/pages/matches.php" class="btn btn-primary" style="flex: 1;">Xem Matches</a>
        </div>
    </div>
</div>

<?php
// Include enhanced filter modal component
include __DIR__ . '/../components/filter-modal-v2.php';
?>

<!-- Card Detail Modal -->
<div id="detailModal" class="detail-modal" style="display: none;">
    <div class="detail-modal-content">
        <!-- Close Button -->
        <button class="detail-modal-close" onclick="closeDetailModal()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <!-- Image Gallery -->
        <div class="detail-image-gallery" id="detailImageGallery">
            <!-- Images will be dynamically inserted -->
        </div>

        <!-- Content Scrollable Area -->
        <div class="detail-content" id="detailContent">
            <!-- Content will be dynamically inserted -->
        </div>

        <!-- Action Buttons -->
        <div class="detail-actions">
            <button class="detail-action-btn detail-btn-pass" id="detailBtnPass">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Pass</span>
            </button>
            <button class="detail-action-btn detail-btn-like" id="detailBtnLike">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span>Like</span>
            </button>
        </div>
    </div>
</div>

<style>
/* Detail Modal */
.detail-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: white;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.detail-modal-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.detail-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 10;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.detail-modal-close:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
}

.detail-modal-close svg {
    width: 24px;
    height: 24px;
}

/* Image Gallery */
.detail-image-gallery {
    position: relative;
    width: 100%;
    height: 50vh;
    overflow: hidden;
    background: #000;
}

.detail-gallery-track {
    display: flex;
    height: 100%;
    transition: transform 0.3s ease-out;
}

.detail-gallery-image {
    flex-shrink: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.detail-gallery-dots {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
}

.detail-gallery-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s;
}

.detail-gallery-dot.active {
    background: white;
    width: 24px;
    border-radius: 4px;
}

/* Content Area */
.detail-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    padding-bottom: 120px;
}

.detail-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--color-gray-900);
}

.detail-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray-600);
    margin-bottom: 1rem;
}

.detail-location svg {
    width: 18px;
    height: 18px;
}

.detail-bio {
    color: var(--color-gray-700);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.detail-section {
    margin-bottom: 1.5rem;
}

.detail-section-title {
    font-weight: 600;
    color: var(--color-gray-800);
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.detail-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.detail-tag {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border-radius: 20px;
    font-size: 0.85rem;
    color: var(--color-gray-700);
}

/* Info Grid Layout for Key-Value Pairs */
.detail-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-info-item {
    background: #f9fafb;
    padding: 0.75rem;
    border-radius: 8px;
}

.detail-info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-bottom: 0.25rem;
}

.detail-info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--color-gray-900);
}

/* Color-Coded Tag Variants */
.detail-tag-primary {
    background: #dbeafe;
    color: #1e40af;
}

.detail-tag-success {
    background: #d1fae5;
    color: #065f46;
}

.detail-tag-warning {
    background: #fed7aa;
    color: #92400e;
}

.detail-tag-info {
    background: #e0e7ff;
    color: #3730a3;
}

.detail-tag-secondary {
    background: #e5e7eb;
    color: #374151;
}

/* Action Buttons */
.detail-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: white;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

.detail-action-btn {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.detail-action-btn svg {
    width: 24px;
    height: 24px;
}

.detail-btn-pass {
    background: #f3f4f6;
    color: #6b7280;
}

.detail-btn-pass:hover {
    background: #e5e7eb;
}

.detail-btn-like {
    background: var(--color-primary);
    color: white;
}

.detail-btn-like:hover {
    background: var(--color-accent);
    transform: scale(1.05);
}
</style>

<?php
// Pass remaining cards as JSON
$remainingCards = array_slice($cards, 1);
?>

<script>
// Config
const SEARCH_MODE = "<?= $searchMode ?>";
const API_URL = "<?= BASE_URL ?>/api";
const ASSETS_URL = "<?= ASSETS_URL ?>";
const BASE_URL = "<?= BASE_URL ?>";

// Remaining cards queue
let cardsQueue = <?= json_encode($remainingCards) ?>;

// Current card index
let currentCardIndex = 0;

// Get elements
const cardContainer = document.getElementById('cardContainer');
const btnPass = document.getElementById('btnPass');
const btnLike = document.getElementById('btnLike');

// Button click handlers
if (btnPass) {
    btnPass.addEventListener('click', () => handleAction(false));
}

if (btnLike) {
    btnLike.addEventListener('click', () => handleAction(true));
}

// Handle action (pass or like)
async function handleAction(isLike) {
    const currentCard = document.querySelector('.profile-card');
    if (!currentCard) return;

    // Disable buttons during animation
    if (btnPass) btnPass.disabled = true;
    if (btnLike) btnLike.disabled = true;

    // Get target ID
    const targetId = currentCard.dataset.userId || currentCard.dataset.roomId;

    // Animate out
    currentCard.classList.add(isLike ? 'animate-out-right' : 'animate-out-left');

    // Call API
    try {
        const result = await saveSwipe(targetId, isLike);

        // Check for match
        if (result.success && result.data && result.data.matched) {
            showMatchModal(result.data.match);
        }
    } catch (error) {
        console.error('Swipe error:', error);
    }

    // Wait for animation to finish
    setTimeout(() => {
        // Remove current card
        currentCard.remove();

        // Show next card
        showNextCard();

        // Re-enable buttons
        if (btnPass) btnPass.disabled = false;
        if (btnLike) btnLike.disabled = false;
    }, 400);
}

// Show next card
function showNextCard() {
    if (cardsQueue.length === 0) {
        // No more cards
        cardContainer.innerHTML = `
            <div class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="empty-title">Hết profiles rồi!</h3>
                <p class="empty-text">Quay lại sau để xem thêm match mới nhé</p>
                <a href="${BASE_URL}/pages/matches.php" class="btn btn-primary">Xem Matches</a>
            </div>
        `;
        return;
    }

    // Get next card data
    const nextCard = cardsQueue.shift();

    // Create new card element
    const newCardHtml = createCardHTML(nextCard);

    // Add to container
    const temp = document.createElement('div');
    temp.innerHTML = newCardHtml;
    const newCard = temp.firstElementChild;
    newCard.classList.add('animate-in');

    cardContainer.insertBefore(newCard, cardContainer.firstChild);
}

// Create card HTML
function createCardHTML(card) {
    if (SEARCH_MODE === 'find_roommate') {
        return createUserCardHTML(card);
    } else {
        return createRoomCardHTML(card);
    }
}

// Create user card HTML
function createUserCardHTML(user) {
    const avatar = user.avatar ? `${ASSETS_URL}/images/uploads/${user.avatar}` : `${ASSETS_URL}/images/default-avatar.svg`;
    const prefs = user.preferences || {};

    let tagsHTML = '';
    if (prefs.budget_min && prefs.budget_max) {
        tagsHTML += `<span class="badge badge-primary">💰 ${formatPrice(prefs.budget_min)} - ${formatPrice(prefs.budget_max)}</span>`;
    }
    if (prefs.cleanliness) {
        tagsHTML += `<span class="badge badge-primary">✨ Sạch: ${prefs.cleanliness}/5</span>`;
    }
    if (prefs.smoking === false) {
        tagsHTML += `<span class="badge badge-success">🚭 Không hút thuốc</span>`;
    }
    if (prefs.pets === true) {
        tagsHTML += `<span class="badge badge-success">🐕 Thích thú cưng</span>`;
    }

    return `
        <div class="profile-card" data-user-id="${user.id}">
            <img src="${avatar}" alt="${escapeHtml(user.name)}" class="profile-card-image">
            <div class="profile-card-content">
                <div class="profile-card-header">
                    <h2 class="profile-card-name">${escapeHtml(user.name)}, ${user.age}</h2>
                    <div class="profile-card-location">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        ${escapeHtml(user.district_name)}, ${escapeHtml(user.city_name)}
                    </div>
                </div>
                ${user.bio ? `<p class="profile-card-bio">${escapeHtml(user.bio)}</p>` : ''}
                <div class="profile-card-tags">${tagsHTML}</div>
            </div>
        </div>
    `;
}

// Create room card HTML
function createRoomCardHTML(room) {
    const images = room.images || [];
    const firstImage = images.length > 0 ? `${ASSETS_URL}/images/uploads/${images[0]}` : `${ASSETS_URL}/images/default-room.svg`;

    // Build amenities badges
    let amenitiesBadges = '';
    if (room.area) {
        amenitiesBadges += `<span class="badge badge-primary">📐 ${room.area}m²</span>`;
    }

    const amenityLabels = {
        'wifi': '📶 Wifi',
        'ac': '❄️ Điều hòa',
        'kitchen': '🍳 Bếp',
        'parking': '🅿️ Chỗ xe',
        'laundry': '🧺 Máy giặt',
        'furniture': '🛋️ Nội thất'
    };

    let amenityCount = 0;
    const maxAmenities = 5;

    if (room.amenities) {
        for (const [key, value] of Object.entries(room.amenities)) {
            if (value && amenityLabels[key] && amenityCount < maxAmenities) {
                amenitiesBadges += `<span class="badge badge-primary">${amenityLabels[key]}</span>`;
                amenityCount++;
            }
        }
    }

    return `
        <div class="profile-card" data-room-id="${room.id}">
            <img src="${firstImage}" alt="${escapeHtml(room.title)}" class="profile-card-image">
            <div class="profile-card-content">
                <div class="profile-card-header">
                    <h2 class="profile-card-name">${formatPrice(room.price)}/tháng</h2>
                    <div class="profile-card-location">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        ${escapeHtml(room.district_name)}, ${escapeHtml(room.city_name)}
                        ${room.distance_formatted ? `<span style="margin-left: 0.5rem; color: var(--color-primary); font-weight: 600;">• ${escapeHtml(room.distance_formatted)}</span>` : ''}
                    </div>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">${escapeHtml(room.title)}</h3>
                ${room.description ? `<p class="profile-card-bio">${escapeHtml(room.description.substring(0, 120))}...</p>` : ''}
                ${amenitiesBadges ? `<div class="profile-card-tags">${amenitiesBadges}</div>` : ''}
            </div>
        </div>
    `;
}

// Save swipe to API
async function saveSwipe(targetId, isLike) {
    const endpoint = SEARCH_MODE === 'find_roommate'
        ? `${API_URL}/swipe-user-simple.php`
        : `${API_URL}/swipe-room-simple.php`;

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            target_id: parseInt(targetId),
            is_like: isLike
        })
    });

    return await response.json();
}

// Show match modal
function showMatchModal(matchData) {
    const modal = document.getElementById('matchModal');
    const avatar1 = document.getElementById('matchAvatar1');
    const avatar2 = document.getElementById('matchAvatar2');
    const matchName = document.getElementById('matchName');

    if (avatar1) avatar1.src = matchData.user1_avatar || `${ASSETS_URL}/images/default-avatar.svg`;
    if (avatar2) avatar2.src = matchData.user2_avatar || `${ASSETS_URL}/images/default-avatar.svg`;
    if (matchName) matchName.textContent = matchData.matched_user_name || 'người này';

    if (modal) modal.style.display = 'flex';
}

// Close match modal
function closeMatchModal() {
    const modal = document.getElementById('matchModal');
    if (modal) modal.style.display = 'none';
}

// Utility functions
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

// Filter modal logic is now handled in filter-modal-v2.php component

// ===== CARD DETAIL MODAL =====
let currentDetailCard = null;
let currentGalleryIndex = 0;

// Add click handlers to cards
document.addEventListener('click', (e) => {
    const card = e.target.closest('.profile-card');
    if (card && !e.target.closest('.action-btn')) {
        openDetailModal(card);
    }
});

async function openDetailModal(card) {
    currentDetailCard = card;
    const modal = document.getElementById('detailModal');

    // Show loading state
    document.getElementById('detailContent').innerHTML = '<div style="text-align: center; padding: 2rem;">Loading...</div>';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    try {
        // Fetch complete data from API
        let cardData;
        if (SEARCH_MODE === 'find_roommate') {
            const userId = card.dataset.userId;
            console.log('Fetching user detail for ID:', userId);
            const response = await fetch(`${API_URL}/get-user-detail.php?user_id=${userId}`);
            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('API result:', result);
            if (!result.success) {
                console.error('API Error:', result.message, result.error_detail);
                throw new Error(result.message || 'Failed to load user data');
            }
            cardData = result.data;
        } else {
            const roomId = card.dataset.roomId;
            console.log('Fetching room detail for ID:', roomId);
            const response = await fetch(`${API_URL}/get-room-detail.php?room_id=${roomId}`);
            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('API result:', result);
            if (!result.success) {
                console.error('API Error:', result.message, result.error_detail);
                throw new Error(result.message || 'Failed to load room data');
            }
            cardData = result.data;
        }

        // Render with complete data
        renderDetailModal(cardData);
    } catch (error) {
        console.error('Error loading detail:', error);
        const errorMsg = error.message || 'Unknown error';
        document.getElementById('detailContent').innerHTML = `
            <div style="text-align: center; padding: 2rem; color: red;">
                <p style="font-weight: bold;">Error loading data</p>
                <p style="font-size: 0.9rem; color: #666;">${escapeHtml(errorMsg)}</p>
                <p style="font-size: 0.85rem; color: #999;">Check console for details (F12)</p>
            </div>
        `;
    }
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    currentDetailCard = null;
    currentGalleryIndex = 0;
}

function getCardUserData(card) {
    const userId = card.dataset.userId;
    // Find user data from queue or first card
    let userData = cardsQueue.find(c => c.id == userId);
    if (!userData && card.classList.contains('profile-card')) {
        // Parse from DOM
        userData = {
            id: userId,
            name: card.querySelector('.profile-card-name')?.textContent || '',
            avatar: card.querySelector('.profile-card-image')?.src || '',
            bio: card.querySelector('.profile-card-bio')?.textContent || '',
            district_name: card.querySelector('.profile-card-location')?.textContent.split(',')[0].trim() || '',
            preferences: {}
        };
    }
    return userData;
}

function getCardRoomData(card) {
    const roomId = card.dataset.roomId;
    let roomData = cardsQueue.find(c => c.id == roomId);
    if (!roomData && card.classList.contains('profile-card')) {
        // Parse from DOM
        roomData = {
            id: roomId,
            title: card.querySelector('h3')?.textContent || '',
            price: card.querySelector('.profile-card-name')?.textContent || '',
            images: [card.querySelector('.profile-card-image')?.src] || [],
            description: card.querySelector('.profile-card-bio')?.textContent || '',
            district_name: card.querySelector('.profile-card-location')?.textContent || ''
        };
    }
    return roomData;
}

function renderDetailModal(data) {
    const gallery = document.getElementById('detailImageGallery');
    const content = document.getElementById('detailContent');

    if (SEARCH_MODE === 'find_roommate') {
        // User detail
        const avatarUrl = data.avatar
            ? (data.avatar.startsWith('http') ? data.avatar : `${ASSETS_URL}/images/uploads/${data.avatar}`)
            : `${ASSETS_URL}/images/default-avatar.svg`;

        gallery.innerHTML = `
            <img src="${avatarUrl}"
                 class="detail-gallery-image" alt="${escapeHtml(data.name)}">
        `;

        content.innerHTML = `
            <h1 class="detail-title">${escapeHtml(data.name)}, ${data.age || '?'}</h1>
            <div class="detail-location">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ${escapeHtml(data.district_name || '')}
            </div>
            ${data.bio ? `<p class="detail-bio">${escapeHtml(data.bio)}</p>` : ''}
            ${renderUserDetails(data)}
        `;
    } else {
        // Room detail - format image URLs
        const images = (data.images || []).map(img =>
            img.startsWith('http') ? img : `${ASSETS_URL}/images/uploads/${img}`
        );

        if (images.length > 1) {
            gallery.innerHTML = `
                <div class="detail-gallery-track" id="galleryTrack">
                    ${images.map((img, i) => `
                        <img src="${img}" class="detail-gallery-image" alt="Image ${i + 1}">
                    `).join('')}
                </div>
                <div class="detail-gallery-dots">
                    ${images.map((_, i) => `
                        <div class="detail-gallery-dot ${i === 0 ? 'active' : ''}" onclick="goToGalleryImage(${i})"></div>
                    `).join('')}
                </div>
            `;
            enableGallerySwipe();
        } else {
            gallery.innerHTML = `
                <img src="${images[0] || ASSETS_URL + '/images/default-room.svg'}"
                     class="detail-gallery-image" alt="${escapeHtml(data.title || '')}">
            `;
        }

        content.innerHTML = `
            <h1 class="detail-title">${formatPrice(data.price)}/tháng</h1>
            <div class="detail-location">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ${escapeHtml(data.district_name || '')}
            </div>
            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.75rem;">${escapeHtml(data.title || '')}</h3>
            ${data.description ? `<p class="detail-bio">${escapeHtml(data.description)}</p>` : ''}
            ${renderRoomAmenities(data.amenities || {}, data.area)}
        `;
    }
}

// Enhanced User Details Rendering
function renderUserDetails(data) {
    let html = '';

    // Basic Info Section
    if (data.occupation || data.gender) {
        html += '<div class="detail-section">';
        html += '<div class="detail-section-title">👤 Basic Info</div>';
        html += '<div class="detail-info-grid">';

        if (data.occupation) {
            html += `<div class="detail-info-item">
                <div class="detail-info-label">💼 Occupation</div>
                <div class="detail-info-value">${escapeHtml(data.occupation)}</div>
            </div>`;
        }

        if (data.gender) {
            const genderLabels = {male: 'Nam', female: 'Nữ', other: 'Khác'};
            html += `<div class="detail-info-item">
                <div class="detail-info-label">⚧ Gender</div>
                <div class="detail-info-value">${genderLabels[data.gender] || data.gender}</div>
            </div>`;
        }

        html += '</div></div>';
    }

    // Lifestyle Preferences Section
    if (data.sleep_schedule || data.work_schedule || data.drinking || data.guests_policy) {
        html += '<div class="detail-section">';
        html += '<div class="detail-section-title">🏠 Lifestyle</div>';
        html += '<div class="detail-info-grid">';

        if (data.sleep_schedule) {
            const scheduleLabels = {
                early_bird: '🌅 Early Bird',
                night_owl: '🦉 Night Owl',
                flexible: '⏰ Flexible'
            };
            html += `<div class="detail-info-item">
                <div class="detail-info-label">Sleep Schedule</div>
                <div class="detail-info-value">${scheduleLabels[data.sleep_schedule] || data.sleep_schedule}</div>
            </div>`;
        }

        if (data.work_schedule) {
            const workLabels = {
                office: '🏢 Office (9-5)',
                shift: '🔄 Shift Work',
                wfh: '🏡 Work from Home',
                student: '📚 Student'
            };
            html += `<div class="detail-info-item">
                <div class="detail-info-label">Work Schedule</div>
                <div class="detail-info-value">${workLabels[data.work_schedule] || data.work_schedule}</div>
            </div>`;
        }

        if (data.drinking) {
            const drinkLabels = {
                no: '🚫 No Drinking',
                social: '🍺 Social Drinker',
                frequent: '🍻 Frequent'
            };
            html += `<div class="detail-info-item">
                <div class="detail-info-label">Drinking</div>
                <div class="detail-info-value">${drinkLabels[data.drinking] || data.drinking}</div>
            </div>`;
        }

        if (data.guests_policy) {
            const guestLabels = {
                no_guests: '🚫 No Guests',
                occasional: '👥 Occasional OK',
                frequent: '🎉 Guests Welcome'
            };
            html += `<div class="detail-info-item">
                <div class="detail-info-label">Guests Policy</div>
                <div class="detail-info-value">${guestLabels[data.guests_policy] || data.guests_policy}</div>
            </div>`;
        }

        html += '</div></div>';
    }

    // Budget & Preferences from JSON
    const prefs = data.preferences || {};
    if (prefs.budget_min || prefs.cleanliness || prefs.noise_tolerance || prefs.smoking !== undefined || prefs.pets !== undefined) {
        html += '<div class="detail-section">';
        html += '<div class="detail-section-title">⚖️ Preferences</div>';
        html += '<div class="detail-tags">';

        if (prefs.budget_min && prefs.budget_max) {
            html += `<div class="detail-tag detail-tag-primary">💰 ${formatPrice(prefs.budget_min)} - ${formatPrice(prefs.budget_max)}</div>`;
        }

        if (prefs.cleanliness) {
            const stars = '⭐'.repeat(prefs.cleanliness);
            html += `<div class="detail-tag detail-tag-success">✨ Cleanliness ${stars}</div>`;
        }

        if (prefs.noise_tolerance) {
            const level = prefs.noise_tolerance <= 2 ? 'Quiet' : prefs.noise_tolerance == 3 ? 'Moderate' : 'Tolerant';
            html += `<div class="detail-tag detail-tag-info">🔊 Noise: ${level}</div>`;
        }

        if (prefs.smoking === false) {
            html += `<div class="detail-tag detail-tag-warning">🚭 No Smoking</div>`;
        } else if (prefs.smoking === true) {
            html += `<div class="detail-tag detail-tag-secondary">🚬 Smoking OK</div>`;
        }

        if (prefs.pets === false) {
            html += `<div class="detail-tag detail-tag-warning">🚫🐕 No Pets</div>`;
        } else if (prefs.pets === true) {
            html += `<div class="detail-tag detail-tag-success">🐕 Pet Friendly</div>`;
        }

        html += '</div></div>';
    }

    return html;
}

// Enhanced Room Amenities Rendering
function renderRoomAmenities(amenities, area) {
    let html = '';

    // Property Details
    html += '<div class="detail-section">';
    html += '<div class="detail-section-title">📐 Property Details</div>';
    html += '<div class="detail-info-grid">';

    if (area) {
        html += `<div class="detail-info-item">
            <div class="detail-info-label">Area</div>
            <div class="detail-info-value">${area}m²</div>
        </div>`;
    }

    html += '</div></div>';

    // Amenities Section
    const amenityLabels = {
        'wifi': {icon: '📶', label: 'Wifi'},
        'ac': {icon: '❄️', label: 'Air Conditioning'},
        'kitchen': {icon: '🍳', label: 'Kitchen'},
        'parking': {icon: '🅿️', label: 'Parking'},
        'laundry': {icon: '🧺', label: 'Washing Machine'},
        'furniture': {icon: '🛋️', label: 'Furnished'},
        'elevator': {icon: '🛗', label: 'Elevator'},
        'security': {icon: '🔒', label: 'Security'},
        'balcony': {icon: '🌿', label: 'Balcony'},
        'gym': {icon: '💪', label: 'Gym'},
        'pool': {icon: '🏊', label: 'Swimming Pool'},
        'pet_friendly': {icon: '🐕', label: 'Pet Friendly'}
    };

    const availableAmenities = [];
    const notAvailable = [];

    for (const [key, info] of Object.entries(amenityLabels)) {
        if (amenities[key] === true || amenities[key] === 1) {
            availableAmenities.push({...info, key});
        } else if (amenities[key] === false || amenities[key] === 0) {
            notAvailable.push({...info, key});
        }
    }

    if (availableAmenities.length > 0) {
        html += '<div class="detail-section">';
        html += '<div class="detail-section-title">✅ Amenities</div>';
        html += '<div class="detail-tags">';

        availableAmenities.forEach(item => {
            html += `<div class="detail-tag detail-tag-success">${item.icon} ${item.label}</div>`;
        });

        html += '</div></div>';
    }

    return html;
}

// Gallery swipe functionality
function enableGallerySwipe() {
    const gallery = document.getElementById('detailImageGallery');
    const track = document.getElementById('galleryTrack');
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    gallery.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
    });

    gallery.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        const diff = currentX - startX;
        track.style.transform = `translateX(calc(-${currentGalleryIndex * 100}% + ${diff}px))`;
    });

    gallery.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;

        const diff = currentX - startX;
        const images = track.querySelectorAll('.detail-gallery-image');

        if (diff < -50 && currentGalleryIndex < images.length - 1) {
            currentGalleryIndex++;
        } else if (diff > 50 && currentGalleryIndex > 0) {
            currentGalleryIndex--;
        }

        updateGalleryPosition();
    });
}

function goToGalleryImage(index) {
    currentGalleryIndex = index;
    updateGalleryPosition();
}

function updateGalleryPosition() {
    const track = document.getElementById('galleryTrack');
    if (track) {
        track.style.transform = `translateX(-${currentGalleryIndex * 100}%)`;

        // Update dots
        document.querySelectorAll('.detail-gallery-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === currentGalleryIndex);
        });
    }
}

// Detail modal action handlers
document.getElementById('detailBtnPass')?.addEventListener('click', async () => {
    closeDetailModal();
    if (currentDetailCard) {
        handleAction(false);
    }
});

document.getElementById('detailBtnLike')?.addEventListener('click', async () => {
    closeDetailModal();
    if (currentDetailCard) {
        handleAction(true);
    }
});
</script>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

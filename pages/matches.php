<?php
/**
 * RUMI - Matches Page (Simple Version)
 * Simplified to avoid 500 errors
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = getDB();
$userId = getCurrentUserId();

// Get matches with simple query
try {
    $stmt = $db->prepare("
        SELECT m.*,
            u1.name as user1_name, u1.avatar as user1_avatar, u1.phone as user1_phone,
            u2.name as user2_name, u2.avatar as user2_avatar, u2.phone as user2_phone
        FROM matches m
        JOIN users u1 ON m.user1_id = u1.id
        JOIN users u2 ON m.user2_id = u2.id
        WHERE (m.user1_id = ? OR m.user2_id = ?)
        ORDER BY m.matched_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $matches = $stmt->fetchAll();
} catch (Exception $e) {
    die("Query error: " . $e->getMessage());
}

// Get stats
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM matches WHERE (user1_id = ? OR user2_id = ?) AND status != 'disconnected'");
    $stmt->execute([$userId, $userId]);
    $totalMatches = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as pending FROM matches WHERE (user1_id = ? OR user2_id = ?) AND status = 'pending'");
    $stmt->execute([$userId, $userId]);
    $pendingMatches = $stmt->fetch()['pending'];

    $stmt = $db->prepare("SELECT COUNT(*) as connected FROM matches WHERE (user1_id = ? OR user2_id = ?) AND status = 'connected'");
    $stmt->execute([$userId, $userId]);
    $connectedMatches = $stmt->fetch()['connected'];
} catch (Exception $e) {
    die("Stats error: " . $e->getMessage());
}

$pageTitle = 'Matches';
include __DIR__ . '/../components/header.php';
?>

<style>
.matches-page {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
    padding-bottom: 5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--color-gray-600);
}

.match-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    gap: 1rem;
    align-items: center;
}

.match-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.match-info {
    flex: 1;
}

.match-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.match-meta {
    font-size: 0.85rem;
    color: var(--color-gray-600);
}

.match-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-pending {
    background: #fef3c7;
    color: #92400e;
}

.badge-connected {
    background: #d1fae5;
    color: #065f46;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    color: var(--color-gray-400);
}
</style>

<div class="matches-page">
    <h2 style="margin-bottom: 1.5rem;">💕 Matches của bạn</h2>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $totalMatches ?></div>
            <div class="stat-label">Tổng matches</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $pendingMatches ?></div>
            <div class="stat-label">Chờ kết nối</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $connectedMatches ?></div>
            <div class="stat-label">Đã kết nối</div>
        </div>
    </div>

    <!-- Matches List -->
    <?php if (empty($matches)): ?>
        <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <h3 style="color: var(--color-gray-700); margin-bottom: 0.5rem;">Chưa có match nào</h3>
            <p style="color: var(--color-gray-600); margin-bottom: 1.5rem;">Swipe để tìm người phù hợp với bạn</p>
            <a href="<?= BASE_URL ?>/pages/swipe.php" class="btn btn-primary">Bắt đầu swipe</a>
        </div>
    <?php else: ?>
        <?php foreach ($matches as $match): ?>
            <?php
            // Determine matched user
            $isUser1 = ($match['user1_id'] == $userId);
            $matchedName = $isUser1 ? $match['user2_name'] : $match['user1_name'];
            $matchedAvatar = $isUser1 ? $match['user2_avatar'] : $match['user1_avatar'];
            $matchedPhone = $isUser1 ? $match['user2_phone'] : $match['user1_phone'];

            $avatarUrl = $matchedAvatar ? getUploadURL($matchedAvatar) : ASSETS_URL . '/images/default-avatar.svg';
            ?>
            <div class="match-card">
                <img src="<?= e($avatarUrl) ?>" alt="<?= e($matchedName) ?>" class="match-avatar">

                <div class="match-info">
                    <div class="match-name"><?= e($matchedName) ?></div>
                    <div class="match-meta">
                        Matched <?= timeAgo($match['matched_at']) ?>
                    </div>
                </div>

                <?php if ($match['status'] === 'pending'): ?>
                    <span class="match-badge badge-pending">Mới</span>
                <?php elseif ($match['status'] === 'connected'): ?>
                    <span class="match-badge badge-connected">Đã kết nối</span>
                <?php endif; ?>
            </div>

            <?php if ($matchedPhone): ?>
            <div style="margin-top: -0.5rem; margin-bottom: 1rem; padding-left: 76px;">
                <a href="https://zalo.me/<?= e($matchedPhone) ?>" target="_blank" class="btn btn-primary btn-sm">
                    💬 Nhắn tin Zalo
                </a>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

<?php
/**
 * RUMI - Swipe Interface (Button-Only Design)
 * Clean, simple matching interface with large buttons
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

// Check profile complete
if (!$userModel->hasCompleteProfile(getCurrentUserId())) {
    redirect(BASE_URL . '/pages/profile-setup.php');
}

// Get search mode
$searchMode = $_GET['mode'] ?? 'find_roommate';

// Validate search mode
if (!in_array($searchMode, ['find_roommate', 'find_room'])) {
    $searchMode = 'find_roommate';
}

// Get cards based on mode
if ($searchMode === 'find_roommate') {
    $cards = $userModel->getPotentialMatches(getCurrentUserId());
} else {
    // Decode user preferences for room filtering
    $userPreferences = is_string($currentUser['preferences'])
        ? json_decode($currentUser['preferences'], true)
        : $currentUser['preferences'];

    if (!is_array($userPreferences)) {
        $userPreferences = [];
    }

    $cards = $roomModel->getPotentialRooms(
        getCurrentUserId(),
        $currentUser['district_id'],
        $userPreferences
    );
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

/* Single Large Card */
.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

/* Card Image */
.profile-card-image {
    width: 100%;
    height: 350px;
    object-fit: cover;
    display: block;
}

/* Card Content */
.profile-card-content {
    padding: 1.5rem;
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
    <!-- Mode Toggle -->
    <div class="mode-toggle">
        <a href="?mode=find_roommate" class="btn <?= $searchMode === 'find_roommate' ? 'btn-primary' : 'btn-outline' ?>">
            👥 Tìm người
        </a>
        <a href="?mode=find_room" class="btn <?= $searchMode === 'find_room' ? 'btn-primary' : 'btn-outline' ?>">
            🏠 Tìm phòng
        </a>
    </div>

    <!-- Card Container -->
    <div id="cardContainer">
        <?php if (empty($cards)): ?>
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
</script>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

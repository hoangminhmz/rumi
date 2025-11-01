<?php
/**
 * RUMI - Card Templates
 * Reusable card components cho users và rooms
 */

/**
 * Render user card cho swipe
 * @param array $user
 */
function renderUserCard($user) {
    $avatar = $user['avatar'] ? getUploadURL($user['avatar']) : ASSETS_URL . '/images/default-avatar.png';
    $preferences = $user['preferences'] ?? [];
    ?>
    <div class="swipe-card" data-user-id="<?= $user['id'] ?>">
        <!-- Swipe overlays -->
        <div class="swipe-overlay like">LIKE</div>
        <div class="swipe-overlay nope">NOPE</div>

        <!-- Card image -->
        <img src="<?= e($avatar) ?>" alt="<?= e($user['name']) ?>" class="swipe-card-img">

        <!-- Card content -->
        <div class="swipe-card-content">
            <h3 class="swipe-card-name"><?= e($user['name']) ?>, <?= $user['age'] ?></h3>

            <div class="swipe-card-info">
                <span>
                    <svg style="width: 16px; height: 16px; display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= e($user['district_name']) ?>, <?= e($user['city_name']) ?>
                </span>
                <span>
                    <svg style="width: 16px; height: 16px; display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?= getGenderLabel($user['gender']) ?>
                </span>
            </div>

            <?php if (!empty($user['bio'])): ?>
            <p class="swipe-card-bio"><?= e($user['bio']) ?></p>
            <?php endif; ?>

            <?php if (!empty($preferences)): ?>
            <div class="profile-tags">
                <?php if (isset($preferences['budget_min'], $preferences['budget_max'])): ?>
                <span class="badge badge-primary">Ngân sách: <?= formatPrice($preferences['budget_min']) ?> - <?= formatPrice($preferences['budget_max']) ?></span>
                <?php endif; ?>

                <?php if (isset($preferences['cleanliness'])): ?>
                <span class="badge badge-primary">Sạch sẽ: <?= $preferences['cleanliness'] ?>/5</span>
                <?php endif; ?>

                <?php if (isset($preferences['smoking']) && $preferences['smoking'] === false): ?>
                <span class="badge badge-success">Không hút thuốc</span>
                <?php endif; ?>

                <?php if (isset($preferences['pets']) && $preferences['pets'] === true): ?>
                <span class="badge badge-success">Thích thú cưng</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render room card cho swipe
 * @param array $room
 */
function renderRoomCard($room) {
    $images = $room['images'] ?? [];
    $firstImage = !empty($images) ? getUploadURL($images[0]) : ASSETS_URL . '/images/default-room.jpg';
    $amenities = $room['amenities'] ?? [];
    ?>
    <div class="swipe-card" data-room-id="<?= $room['id'] ?>">
        <!-- Swipe overlays -->
        <div class="swipe-overlay like">LIKE</div>
        <div class="swipe-overlay nope">NOPE</div>

        <!-- Card image -->
        <img src="<?= e($firstImage) ?>" alt="<?= e($room['title']) ?>" class="swipe-card-img">

        <!-- Card content -->
        <div class="swipe-card-content">
            <h3 class="swipe-card-name"><?= formatPrice($room['price']) ?>/tháng</h3>

            <div class="swipe-card-info">
                <span>
                    <svg style="width: 16px; height: 16px; display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= e($room['district_name']) ?>, <?= e($room['city_name']) ?>
                </span>
                <?php if ($room['area']): ?>
                <span>
                    <svg style="width: 16px; height: 16px; display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5z"/>
                    </svg>
                    <?= $room['area'] ?>m²
                </span>
                <?php endif; ?>
            </div>

            <h4 style="font-size: var(--font-size-base); margin-bottom: var(--space-1);"><?= e($room['title']) ?></h4>

            <?php if (!empty($room['description'])): ?>
            <p class="swipe-card-bio"><?= e(truncate($room['description'], 100)) ?></p>
            <?php endif; ?>

            <?php if (!empty($amenities)): ?>
            <div class="room-card-amenities">
                <?php
                $trueAmenities = array_filter($amenities);
                $count = 0;
                foreach ($trueAmenities as $key => $value):
                    if ($count >= 4) break;
                    $count++;
                ?>
                <span class="badge badge-primary"><?= e(getAmenityLabel($key)) ?></span>
                <?php endforeach; ?>

                <?php if (count($trueAmenities) > 4): ?>
                <span class="badge badge-secondary">+<?= count($trueAmenities) - 4 ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render match list item
 * @param array $match
 * @param int $currentUserId
 */
function renderMatchItem($match, $currentUserId) {
    $matchedUser = getMatchedUser($match, $currentUserId);
    $avatar = getMatchedUserAvatar($match, $currentUserId);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-center gap-3">
                <img src="<?= e($avatar) ?>" alt="<?= e($matchedUser['name']) ?>"
                     style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">

                <div style="flex: 1;">
                    <h4 style="margin: 0; font-size: var(--font-size-lg);"><?= e($matchedUser['name']) ?></h4>

                    <?php if ($matchedUser['age'] || $matchedUser['district']): ?>
                    <p style="margin: 0; font-size: var(--font-size-sm); color: var(--color-gray-600);">
                        <?php if ($matchedUser['age']): ?><?= $matchedUser['age'] ?> tuổi<?php endif; ?>
                        <?php if ($matchedUser['age'] && $matchedUser['district']): ?> • <?php endif; ?>
                        <?php if ($matchedUser['district']): ?><?= e($matchedUser['district']) ?><?php endif; ?>
                    </p>
                    <?php endif; ?>

                    <small class="text-secondary"><?= timeAgo($match['matched_at']) ?></small>
                </div>

                <div>
                    <?php if ($match['status'] === 'pending'): ?>
                    <span class="badge badge-warning">Mới match</span>
                    <?php elseif ($match['status'] === 'connected'): ?>
                    <span class="badge badge-success">Đã kết nối</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($match['status'] === 'pending'): ?>
            <div class="mt-3">
                <a href="<?= BASE_URL ?>/pages/match-detail.php?id=<?= $match['id'] ?>" class="btn btn-primary btn-block">
                    Xem thông tin liên hệ
                </a>
            </div>
            <?php elseif ($match['status'] === 'connected' && $matchedUser['phone']): ?>
            <div class="mt-3">
                <a href="https://zalo.me/<?= e($matchedUser['phone']) ?>" target="_blank" class="btn btn-primary btn-block">
                    Nhắn tin qua Zalo
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

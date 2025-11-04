<?php
/**
 * Minimal swipe test to find exact 500 error location
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting minimal swipe test...<br>";

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/User.php';
    require_once __DIR__ . '/includes/Room.php';

    echo "✅ All files loaded<br>";

    startSession();
    $_SESSION['user_id'] = 1; // Force login

    $userModel = new User();
    $roomModel = new Room();
    $currentUser = $userModel->getById(getCurrentUserId());

    echo "✅ Models initialized<br>";

    // Get search mode
    $searchMode = $_GET['mode'] ?? 'find_roommate';

    if (!in_array($searchMode, ['find_roommate', 'find_room'])) {
        $searchMode = 'find_roommate';
    }

    echo "Search mode: {$searchMode}<br>";

    // Get cards based on mode
    if ($searchMode === 'find_roommate') {
        echo "Getting roommates...<br>";
        $cards = $userModel->getPotentialMatches(getCurrentUserId());
    } else {
        echo "Getting rooms...<br>";

        // THIS IS THE EXACT CODE FROM swipe.php
        $userPreferences = is_string($currentUser['preferences'])
            ? json_decode($currentUser['preferences'], true)
            : $currentUser['preferences'];

        if (!is_array($userPreferences)) {
            $userPreferences = [];
        }

        echo "Preferences decoded<br>";

        $cards = $roomModel->getPotentialRooms(
            getCurrentUserId(),
            $currentUser['district_id'],
            $userPreferences
        );
    }

    echo "✅ Cards retrieved: " . count($cards) . "<br>";

    if (empty($cards)) {
        echo "<h2>No cards found</h2>";
        exit;
    }

    $firstCard = $cards[0];
    echo "First card ID: {$firstCard['id']}<br>";
    echo "First card title: {$firstCard['title']}<br>";

    // NOW TEST RENDERING - This is where error might be
    echo "<h2>Testing HTML Rendering...</h2>";

    ob_start();
    ?>

    <!-- Test rendering the exact code from swipe.php for room card -->
    <?php if ($searchMode === 'find_room'): ?>
        <div class="profile-card" data-room-id="<?= $firstCard['id'] ?>">
            <?php
            echo "<!-- Step 1: Get images -->\n";
            $images = $firstCard['images'] ?? [];
            echo "<!-- Images count: " . count($images) . " -->\n";

            $firstImage = !empty($images) ? getUploadURL($images[0]) : ASSETS_URL . '/images/default-room.svg';
            echo "<!-- First image: {$firstImage} -->\n";
            ?>
            <img src="<?= e($firstImage) ?>"
                 alt="<?= e($firstCard['title']) ?>"
                 class="profile-card-image">

            <div class="profile-card-content">
                <div class="profile-card-header">
                    <?php
                    echo "<!-- Step 2: Format price -->\n";
                    $formattedPrice = formatPrice($firstCard['price']);
                    echo "<!-- Price: {$formattedPrice} -->\n";
                    ?>
                    <h2 class="profile-card-name"><?= $formattedPrice ?>/tháng</h2>
                    <div class="profile-card-location">
                        <?php
                        echo "<!-- Step 3: District info -->\n";
                        $districtName = e($firstCard['district_name'] ?? 'Unknown');
                        $cityName = e($firstCard['city_name'] ?? 'Unknown');
                        echo "<!-- District: {$districtName}, City: {$cityName} -->\n";
                        ?>
                        <?= $districtName ?>, <?= $cityName ?>

                        <?php
                        echo "<!-- Step 4: Distance (this might fail) -->\n";
                        if (!empty($firstCard['distance_formatted'])):
                            echo "<!-- Distance formatted: " . $firstCard['distance_formatted'] . " -->\n";
                        ?>
                            <span style="margin-left: 0.5rem; color: var(--color-primary); font-weight: 600;">
                                • <?= e($firstCard['distance_formatted']) ?>
                            </span>
                        <?php else: ?>
                            <!-- No distance -->
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                echo "<!-- Step 5: Title -->\n";
                ?>
                <h3><?= e($firstCard['title']) ?></h3>

                <?php
                echo "<!-- Step 6: Description -->\n";
                if (!empty($firstCard['description'])):
                    $truncatedDesc = truncate($firstCard['description'], 120);
                    echo "<!-- Description truncated -->\n";
                ?>
                    <p><?= e($truncatedDesc) ?></p>
                <?php endif; ?>

                <?php
                echo "<!-- Step 7: Amenities -->\n";
                if (!empty($firstCard['amenities'])):
                ?>
                    <div class="profile-card-tags">
                        <?php if ($firstCard['area']): ?>
                            <span class="badge">📐 <?= $firstCard['area'] ?>m²</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $output = ob_get_clean();

    echo "✅ HTML rendering completed without errors!<br>";
    echo "<hr>";
    echo $output;
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ ALL TESTS PASSED!</h2>";
    echo "<p>The error must be in the actual swipe.php file. Let me check the file directly...</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR CAUGHT!</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

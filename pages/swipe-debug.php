<?php
/**
 * SWIPE.PHP DEBUG VERSION
 * Turn on all errors to see what's causing 500
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DEBUG MODE: Error reporting ON -->\n";

try {
    echo "<!-- Loading config/database.php -->\n";
    require_once __DIR__ . '/../config/database.php';

    echo "<!-- Loading config/constants.php -->\n";
    require_once __DIR__ . '/../config/constants.php';

    echo "<!-- Loading includes/functions.php -->\n";
    require_once __DIR__ . '/../includes/functions.php';

    echo "<!-- Loading includes/User.php -->\n";
    require_once __DIR__ . '/../includes/User.php';

    echo "<!-- Loading includes/Room.php -->\n";
    require_once __DIR__ . '/../includes/Room.php';

    echo "<!-- Starting session -->\n";
    startSession();

    echo "<!-- Checking login -->\n";
    requireLogin();

    echo "<!-- Creating models -->\n";
    $userModel = new User();
    $roomModel = new Room();
    $currentUser = $userModel->getById(getCurrentUserId());

    echo "<!-- Checking profile -->\n";
    if (!$userModel->hasCompleteProfile(getCurrentUserId())) {
        redirect(BASE_URL . '/pages/profile-setup.php');
    }

    echo "<!-- Getting search mode -->\n";
    $searchMode = $_GET['mode'] ?? 'find_roommate';

    if (!in_array($searchMode, ['find_roommate', 'find_room'])) {
        $searchMode = 'find_roommate';
    }

    echo "<!-- Getting cards for mode: $searchMode -->\n";
    if ($searchMode === 'find_roommate') {
        $cards = $userModel->getPotentialMatches(getCurrentUserId());
    } else {
        $cards = $roomModel->getPotentialRooms(getCurrentUserId(), $currentUser['district_id']);
    }

    echo "<!-- Found " . count($cards) . " cards -->\n";

    $pageTitle = 'Swipe Debug';

} catch (Exception $e) {
    echo "<div style='background: #fee; border: 2px solid red; padding: 2rem; margin: 2rem;'>";
    echo "<h1 style='color: red;'>ERROR CAUGHT!</h1>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    die();
}

include __DIR__ . '/../components/header.php';
?>

<div class="swipe-page" style="max-width: 600px; margin: 2rem auto; padding: 1rem;">
    <div style="background: #efe; border: 2px solid green; padding: 1rem; margin-bottom: 1rem;">
        <h3>✅ PHP Code Executed Successfully!</h3>
        <p>Search Mode: <strong><?= $searchMode ?></strong></p>
        <p>Cards Found: <strong><?= count($cards) ?></strong></p>
        <p>User ID: <strong><?= getCurrentUserId() ?></strong></p>
    </div>

    <h2>Mode Toggle</h2>
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
        <a href="?mode=find_roommate" class="btn <?= $searchMode === 'find_roommate' ? 'btn-primary' : 'btn-outline' ?>">
            👥 Tìm người
        </a>
        <a href="?mode=find_room" class="btn <?= $searchMode === 'find_room' ? 'btn-primary' : 'btn-outline' ?>">
            🏠 Tìm phòng
        </a>
    </div>

    <?php if (empty($cards)): ?>
        <div style="text-align: center; padding: 2rem; background: #f9f9f9; border-radius: 12px;">
            <h3>No cards found</h3>
            <p>Mode: <?= $searchMode ?></p>
        </div>
    <?php else: ?>
        <h2>First Card Debug:</h2>
        <?php $firstCard = $cards[0]; ?>

        <div style="background: white; border: 1px solid #ddd; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <?php if ($searchMode === 'find_roommate'): ?>
                <h3>User Card</h3>
                <pre><?php print_r($firstCard); ?></pre>
            <?php else: ?>
                <h3>Room Card</h3>
                <p><strong>ID:</strong> <?= $firstCard['id'] ?></p>
                <p><strong>Title:</strong> <?= e($firstCard['title']) ?></p>
                <p><strong>Price:</strong> <?= formatPrice($firstCard['price']) ?></p>
                <p><strong>District:</strong> <?= e($firstCard['district_name']) ?></p>

                <?php if (!empty($firstCard['description'])): ?>
                <p><strong>Description (truncated):</strong> <?= e(truncate($firstCard['description'], 120)) ?></p>
                <?php endif; ?>

                <h4>Full Data:</h4>
                <pre><?php print_r($firstCard); ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../components/navigation.php';
include __DIR__ . '/../components/footer.php';
?>

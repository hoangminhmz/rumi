<?php
/**
 * Test Swipe API
 * Check if swipe is being saved to database
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/User.php';

startSession();

// Create test session if not logged in
if (!isLoggedIn()) {
    $_SESSION['user_id'] = 1;
    echo "<p>✓ Created test session with user_id = 1</p>";
}

$userId = getCurrentUserId();
$db = getDB();

echo "<h2>Swipe API Test</h2>";
echo "<p>Current User ID: $userId</p>";

// Get current swipe count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
$stmt->execute([$userId]);
$beforeCount = $stmt->fetch()['count'];

echo "<h3>Before Test</h3>";
echo "<p>Total swipes in database: $beforeCount</p>";

// Get list of swiped users
$stmt = $db->prepare("SELECT target_user_id, is_like FROM user_swipes WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$swipes = $stmt->fetchAll();

if (!empty($swipes)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Target User ID</th><th>Is Like</th></tr>";
    foreach ($swipes as $swipe) {
        echo "<tr><td>{$swipe['target_user_id']}</td><td>" . ($swipe['is_like'] ? 'LIKE' : 'NOPE') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No swipes found in database.</p>";
}

// Test swipe function
echo "<h3>Testing Swipe Function</h3>";

$userModel = new User();

// Get a target user to test with
$stmt = $db->prepare("SELECT id FROM users WHERE id != ? AND is_active = 1 LIMIT 1");
$stmt->execute([$userId]);
$targetUser = $stmt->fetch();

if ($targetUser) {
    $targetId = $targetUser['id'];
    echo "<p>Testing swipe on user ID: $targetId</p>";

    // Test swipe
    $result = $userModel->swipe($userId, $targetId, true);

    if ($result) {
        echo "<p style='color: green;'>✅ Swipe saved successfully!</p>";

        // Check database
        $stmt = $db->prepare("SELECT * FROM user_swipes WHERE user_id = ? AND target_user_id = ?");
        $stmt->execute([$userId, $targetId]);
        $swipeRecord = $stmt->fetch();

        if ($swipeRecord) {
            echo "<p style='color: green;'>✅ Swipe found in database!</p>";
            echo "<pre>";
            print_r($swipeRecord);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Swipe NOT found in database after save!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Swipe failed!</p>";
    }

    // Get new count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $afterCount = $stmt->fetch()['count'];

    echo "<h3>After Test</h3>";
    echo "<p>Total swipes in database: $afterCount</p>";
    echo "<p>Difference: " . ($afterCount - $beforeCount) . "</p>";

} else {
    echo "<p style='color: orange;'>⚠️ No target user found to test with</p>";
}

// Test getPotentialMatches
echo "<h3>Testing getPotentialMatches</h3>";

$cards = $userModel->getPotentialMatches($userId, 5);
echo "<p>Found " . count($cards) . " potential matches</p>";

if (!empty($cards)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 1rem;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Age</th><th>District</th></tr>";
    foreach ($cards as $card) {
        echo "<tr>";
        echo "<td>{$card['id']}</td>";
        echo "<td>{$card['name']}</td>";
        echo "<td>{$card['age']}</td>";
        echo "<td>{$card['district_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check if any of these cards have been swiped
    echo "<h4>Checking if any returned cards have been swiped:</h4>";
    foreach ($cards as $card) {
        $stmt = $db->prepare("SELECT * FROM user_swipes WHERE user_id = ? AND target_user_id = ?");
        $stmt->execute([$userId, $card['id']]);
        $existing = $stmt->fetch();
        if ($existing) {
            echo "<p style='color: red;'>❌ ERROR: User ID {$card['id']} ({$card['name']}) HAS been swiped but still returned!</p>";
        } else {
            echo "<p style='color: green;'>✓ User ID {$card['id']} ({$card['name']}) has NOT been swiped - OK</p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='pages/swipe.php'>Go to Swipe Page</a></p>";
echo "<p><a href='reset-swipes.php'>Reset Swipes</a></p>";
?>

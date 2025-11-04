<?php
/**
 * Quick debug for room swipe 500 error
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Room Swipe Error</h2>";

try {
    echo "<!-- Step 1: Load config -->\n";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    echo "✅ Config loaded<br>";

    echo "<!-- Step 2: Load functions -->\n";
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ Functions loaded<br>";

    echo "<!-- Step 3: Check GeoLocationService exists -->\n";
    if (file_exists(__DIR__ . '/includes/GeoLocationService.php')) {
        echo "✅ GeoLocationService.php exists<br>";
        require_once __DIR__ . '/includes/GeoLocationService.php';
        echo "✅ GeoLocationService loaded<br>";
    } else {
        echo "❌ GeoLocationService.php NOT FOUND<br>";
    }

    echo "<!-- Step 4: Load Room model -->\n";
    require_once __DIR__ . '/includes/Room.php';
    echo "✅ Room model loaded<br>";

    echo "<!-- Step 5: Load User model -->\n";
    require_once __DIR__ . '/includes/User.php';
    echo "✅ User model loaded<br>";

    echo "<!-- Step 6: Start session -->\n";
    startSession();
    echo "✅ Session started<br>";

    echo "<!-- Step 7: Check login -->\n";
    if (!isLoggedIn()) {
        echo "⚠️ Not logged in, using dummy user ID 1<br>";
        $_SESSION['user_id'] = 1;
    }
    $userId = getCurrentUserId();
    echo "✅ User ID: {$userId}<br>";

    echo "<!-- Step 8: Get user data -->\n";
    $userModel = new User();
    $currentUser = $userModel->getById($userId);
    if (!$currentUser) {
        echo "❌ User not found! Create users first.<br>";
        exit;
    }
    echo "✅ User loaded: {$currentUser['name']}<br>";
    echo "District ID: {$currentUser['district_id']}<br>";

    echo "<!-- Step 9: Check database columns -->\n";
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM rooms LIKE 'latitude'");
    if ($stmt->fetch()) {
        echo "✅ rooms.latitude column exists<br>";
    } else {
        echo "❌ rooms.latitude column MISSING - Run migration first!<br>";
    }

    $stmt = $db->query("SHOW COLUMNS FROM districts LIKE 'latitude'");
    if ($stmt->fetch()) {
        echo "✅ districts.latitude column exists<br>";
    } else {
        echo "❌ districts.latitude column MISSING - Run migration first!<br>";
    }

    echo "<!-- Step 10: Decode preferences -->\n";
    $userPreferences = is_string($currentUser['preferences'])
        ? json_decode($currentUser['preferences'], true)
        : $currentUser['preferences'];

    if (!is_array($userPreferences)) {
        $userPreferences = [];
    }
    echo "✅ Preferences decoded<br>";
    echo "<pre>" . print_r($userPreferences, true) . "</pre>";

    echo "<!-- Step 11: Call getPotentialRooms -->\n";
    $roomModel = new Room();

    echo "Calling: \$roomModel->getPotentialRooms({$userId}, {$currentUser['district_id']}, preferences)<br>";

    $cards = $roomModel->getPotentialRooms(
        $userId,
        $currentUser['district_id'],
        $userPreferences
    );

    echo "✅ getPotentialRooms executed successfully<br>";
    echo "Cards returned: " . count($cards) . "<br>";

    if (count($cards) > 0) {
        echo "<h3>First card:</h3>";
        echo "<pre>" . print_r($cards[0], true) . "</pre>";
    }

    echo "<h2 style='color: green;'>✅ NO ERRORS FOUND - Everything works!</h2>";
    echo "<p>If you see this, the error is elsewhere. Try accessing <a href='pages/swipe.php?mode=find_room'>swipe.php</a> again.</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR FOUND!</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

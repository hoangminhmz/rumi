<?php
/**
 * TEST ROOM SWIPE (TÌM PHÒNG)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>ROOM SWIPE TEST</h1>";

// Step 1: Database
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>");
}

// Step 2: Check rooms table
echo "<h2>Step 2: Check Rooms Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'active'");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ Rooms table exists. Active rooms: $count</p>";

    if ($count == 0) {
        echo "<p style='color: red;'>❌ No active rooms! Need to import dummy data or activate rooms.</p>";
    }
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Rooms table error: " . $e->getMessage() . "</p>");
}

// Step 3: Check room_swipes table
echo "<h2>Step 3: Check room_swipes Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM room_swipes");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ room_swipes table exists. Total swipes: $count</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ room_swipes table error: " . $e->getMessage() . "</p>");
}

// Step 4: Test getPotentialRooms query
echo "<h2>Step 4: Test getPotentialRooms Query</h2>";
try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/Room.php';

    echo "<p>✅ Files loaded</p>";

    // Create session
    startSession();
    if (!isLoggedIn()) {
        $_SESSION['user_id'] = 1;
    }
    $userId = getCurrentUserId();
    echo "<p>Testing with user ID: $userId</p>";

    // Get user district
    $stmt = $db->prepare("SELECT district_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userDistrictId = $stmt->fetch()['district_id'];
    echo "<p>User district ID: $userDistrictId</p>";

    // Test Room model
    $roomModel = new Room();
    echo "<p>✅ Room model created</p>";

    $rooms = $roomModel->getPotentialRooms($userId, $userDistrictId, 5);
    echo "<p style='color: green;'>✅ getPotentialRooms executed. Found " . count($rooms) . " rooms</p>";

    if (!empty($rooms)) {
        echo "<h3>First Room:</h3>";
        echo "<pre>";
        print_r($rooms[0]);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ No rooms found. Might have swiped all, or no active rooms.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ getPotentialRooms error: " . $e->getMessage() . "</p>";
    echo "<pre style='background: #ffe0e0; padding: 1rem;'>";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

// Step 5: Test rendering
echo "<h2>Step 5: Test Room Card Rendering</h2>";
try {
    require_once __DIR__ . '/components/cards.php';
    echo "<p>✅ cards.php loaded</p>";

    if (!empty($rooms)) {
        echo "<div style='max-width: 400px;'>";
        renderRoomCard($rooms[0]);
        echo "</div>";
        echo "<p style='color: green;'>✅ renderRoomCard works!</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Render error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><a href='pages/swipe.php?mode=find_room'>Go to Room Swipe</a></p>";
?>

<?php
/**
 * TEST ROOM SWIPE STEP BY STEP
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/Room.php';
require_once __DIR__ . '/includes/User.php';

startSession();
if (!isLoggedIn()) {
    $_SESSION['user_id'] = 1;
}

$userId = getCurrentUserId();
$db = getDB();

echo "<h1>🏠 ROOM SWIPE DEBUG</h1>";

// Step 1: Check active rooms
echo "<h2>Step 1: Check Active Rooms</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'active'");
    $activeRooms = $stmt->fetch()['count'];
    echo "<p style='color: " . ($activeRooms > 0 ? 'green' : 'red') . "';'>";
    echo $activeRooms > 0 ? "✅" : "❌";
    echo " Active rooms: <strong>$activeRooms</strong></p>";

    if ($activeRooms == 0) {
        echo "<p style='color: orange;'>⚠️ No active rooms! Need to import dummy data or activate rooms.</p>";
        echo "<p>SQL to activate rooms:</p>";
        echo "<pre>UPDATE rooms SET status = 'active', expired_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id > 0;</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 2: Check if rooms table has data
echo "<h2>Step 2: Show First 5 Rooms</h2>";
try {
    $stmt = $db->query("SELECT * FROM rooms LIMIT 5");
    $rooms = $stmt->fetchAll();

    if (empty($rooms)) {
        echo "<p style='color: red;'>❌ No rooms in database at all!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($rooms) . " rooms</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Price</th><th>Status</th><th>Expired At</th></tr>";
        foreach ($rooms as $room) {
            echo "<tr>";
            echo "<td>{$room['id']}</td>";
            echo "<td>" . substr($room['title'], 0, 30) . "...</td>";
            echo "<td>" . number_format($room['price']) . "</td>";
            echo "<td style='color: " . ($room['status'] == 'active' ? 'green' : 'red') . ";'>{$room['status']}</td>";
            echo "<td>{$room['expired_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 3: Check user district
echo "<h2>Step 3: User Info</h2>";
try {
    $stmt = $db->prepare("SELECT id, name, district_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    echo "<p>User ID: {$user['id']}</p>";
    echo "<p>Name: {$user['name']}</p>";
    echo "<p>District ID: {$user['district_id']}</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 4: Test Room model
echo "<h2>Step 4: Test Room Model</h2>";
try {
    $roomModel = new Room();
    echo "<p style='color: green;'>✅ Room model created</p>";

    $userModel = new User();
    $currentUser = $userModel->getById($userId);

    $rooms = $roomModel->getPotentialRooms($userId, $currentUser['district_id'], 5);

    echo "<p style='color: green;'>✅ getPotentialRooms executed</p>";
    echo "<p>Found: <strong>" . count($rooms) . "</strong> potential rooms</p>";

    if (!empty($rooms)) {
        echo "<h3>First Room Data:</h3>";
        echo "<pre style='background: #f3f4f6; padding: 1rem;'>";
        print_r($rooms[0]);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre style='background: #ffe0e0; padding: 1rem;'>";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

// Step 5: Check room_swipes table
echo "<h2>Step 5: Check room_swipes Table</h2>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM room_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $swipeCount = $stmt->fetch()['count'];

    echo "<p>Room swipes by user $userId: <strong>$swipeCount</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 6: Test swipe.php with mode parameter
echo "<h2>Step 6: Test Links</h2>";
echo "<p><a href='pages/swipe.php?mode=find_room' target='_blank' style='padding: 1rem; background: #00D4AA; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Open Swipe Page (Find Room Mode)</a></p>";
echo "<p><a href='pages/swipe.php?mode=find_roommate' target='_blank' style='padding: 1rem; background: #00D4AA; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Open Swipe Page (Find Roommate Mode)</a></p>";

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests passed, swipe room should work. If not, check errors above.</p>";
?>

<?php
// TEST 5: LOAD MODELS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 5: Load Models</h2>";

try {
    // Database first
    echo "Step 1: Database...<br>";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    echo "✓ Database config loaded<br>";

    // Functions
    echo "<br>Step 2: Functions...<br>";
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ functions.php loaded<br>";

    // User model
    echo "<br>Step 3: User model...<br>";
    $userFile = __DIR__ . '/includes/User.php';
    if (!file_exists($userFile)) {
        die("ERROR: User.php not found");
    }
    require_once $userFile;
    echo "✓ User.php loaded<br>";

    $userModel = new User();
    echo "✓ User object created<br>";

    // Check User methods
    $userMethods = ['canAccessRoomTab', 'canAccessRoommateTab', 'getLikedRoomIds', 'getMatchedUsers'];
    echo "<br>User methods:<br><ul>";
    foreach ($userMethods as $method) {
        $exists = method_exists($userModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    // Room model
    echo "<br>Step 4: Room model...<br>";
    require_once __DIR__ . '/includes/Room.php';
    echo "✓ Room.php loaded<br>";

    $roomModel = new Room();
    echo "✓ Room object created<br>";

    // Check Room methods
    $roomMethods = ['getRoomsForAllMatches', 'getPotentialRooms'];
    echo "<br>Room methods:<br><ul>";
    foreach ($roomMethods as $method) {
        $exists = method_exists($roomModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    // Match model
    echo "<br>Step 5: Match model...<br>";
    require_once __DIR__ . '/includes/Match.php';
    echo "✓ Match.php loaded<br>";

    $matchModel = new MatchModel();
    echo "✓ MatchModel object created<br>";

    // Check Match methods
    $matchMethods = ['getUsersWhoLikedSameRooms', 'getMatchedUserIds', 'calculateCompatibilityScore'];
    echo "<br>Match methods:<br><ul>";
    foreach ($matchMethods as $method) {
        $exists = method_exists($matchModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    echo "<br><h3 style='color: green;'>✓ ALL MODELS LOADED SUCCESSFULLY!</h3>";

} catch (Exception $e) {
    echo "<br><b style='color: red;'>ERROR:</b> " . htmlspecialchars($e->getMessage());
    echo "<br><br>File: " . htmlspecialchars($e->getFile());
    echo "<br>Line: " . $e->getLine();
    echo "<br><br>Stack trace:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

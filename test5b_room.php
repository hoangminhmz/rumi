<?php
// TEST 5B: Load Room.php only
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 5B: Load Room.php</h2>";

try {
    echo "Step 1: Loading dependencies...<br>";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ Dependencies loaded<br>";

    echo "<br>Step 2: Loading Room.php...<br>";

    $roomFile = __DIR__ . '/includes/Room.php';
    if (!file_exists($roomFile)) {
        die("ERROR: Room.php not found");
    }
    echo "✓ Room.php exists<br>";

    $fileSize = filesize($roomFile);
    echo "✓ File size: " . number_format($fileSize) . " bytes<br>";

    echo "<br>Step 3: Requiring Room.php...<br>";
    require_once $roomFile;
    echo "✓ Room.php loaded!<br>";

    echo "<br>Step 4: Creating Room object...<br>";
    $roomModel = new Room();
    echo "✓ Room object created!<br>";

    echo "<br>Step 5: Checking methods...<br>";
    $methods = get_class_methods($roomModel);
    echo "✓ Total methods: " . count($methods) . "<br>";

    echo "<details><summary>All methods:</summary><ul>";
    foreach ($methods as $method) {
        echo "<li>$method()</li>";
    }
    echo "</ul></details>";

    // Check required methods
    $requiredMethods = [
        'getRoomsForAllMatches',
        'getRoomsForMatchedPair',
        'getPotentialRooms',
        'create',
        'getById'
    ];

    echo "<br><h3>Required Methods Check:</h3><ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($roomModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    echo "<br><h3 style='color: green;'>✓ ROOM MODEL LOADED SUCCESSFULLY!</h3>";

} catch (ParseError $e) {
    echo "<br><h3 style='color: red;'>✗ PARSE ERROR</h3>";
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</div>";
} catch (Exception $e) {
    echo "<br><h3 style='color: red;'>✗ ERROR</h3>";
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

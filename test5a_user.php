<?php
// TEST 5A: Load User.php only
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 5A: Load User.php</h2>";

try {
    echo "Step 1: Database config...<br>";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    echo "✓ Config loaded<br>";

    echo "<br>Step 2: Functions...<br>";
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ functions.php loaded<br>";

    echo "<br>Step 3: Loading User.php...<br>";

    // Check file exists
    $userFile = __DIR__ . '/includes/User.php';
    if (!file_exists($userFile)) {
        die("ERROR: User.php not found at: $userFile");
    }
    echo "✓ User.php file exists<br>";

    // Check file size
    $fileSize = filesize($userFile);
    echo "✓ File size: " . number_format($fileSize) . " bytes<br>";

    // Try to read first 100 lines to check syntax
    echo "<br>Step 4: Checking file syntax...<br>";
    $lines = file($userFile);
    echo "✓ Total lines: " . count($lines) . "<br>";

    // Show first 20 lines
    echo "<details><summary>First 20 lines:</summary><pre>";
    echo htmlspecialchars(implode('', array_slice($lines, 0, 20)));
    echo "</pre></details>";

    echo "<br>Step 5: Requiring User.php...<br>";
    require_once $userFile;
    echo "✓ User.php loaded successfully!<br>";

    echo "<br>Step 6: Creating User object...<br>";
    $userModel = new User();
    echo "✓ User object created!<br>";

    echo "<br>Step 7: Checking methods...<br>";
    $methods = get_class_methods($userModel);
    echo "✓ Total methods: " . count($methods) . "<br>";

    echo "<details><summary>All methods:</summary><ul>";
    foreach ($methods as $method) {
        echo "<li>$method()</li>";
    }
    echo "</ul></details>";

    // Check specific required methods
    $requiredMethods = [
        'canAccessRoomTab',
        'canAccessRoommateTab',
        'getLikedRoomIds',
        'getMatchedUsers'
    ];

    echo "<br><h3>Required Methods Check:</h3><ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($userModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    echo "<br><h3 style='color: green;'>✓ USER MODEL LOADED SUCCESSFULLY!</h3>";

} catch (ParseError $e) {
    echo "<br><h3 style='color: red;'>✗ PARSE ERROR (Syntax Error)</h3>";
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
    echo "<p><strong>Fix:</strong> Check syntax in User.php around line " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<br><h3 style='color: red;'>✗ ERROR</h3>";
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<br><strong>Stack trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

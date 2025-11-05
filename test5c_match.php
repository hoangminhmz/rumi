<?php
// TEST 5C: Load Match.php only
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 5C: Load Match.php</h2>";

try {
    echo "Step 1: Loading dependencies...<br>";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ Dependencies loaded<br>";

    echo "<br>Step 2: Loading Match.php...<br>";

    $matchFile = __DIR__ . '/includes/Match.php';
    if (!file_exists($matchFile)) {
        die("ERROR: Match.php not found");
    }
    echo "✓ Match.php exists<br>";

    $fileSize = filesize($matchFile);
    echo "✓ File size: " . number_format($fileSize) . " bytes<br>";

    echo "<br>Step 3: Requiring Match.php...<br>";
    require_once $matchFile;
    echo "✓ Match.php loaded!<br>";

    echo "<br>Step 4: Creating Match object...<br>";
    $matchModel = new Match();
    echo "✓ Match object created!<br>";

    echo "<br>Step 5: Checking methods...<br>";
    $methods = get_class_methods($matchModel);
    echo "✓ Total methods: " . count($methods) . "<br>";

    echo "<details><summary>All methods:</summary><ul>";
    foreach ($methods as $method) {
        echo "<li>$method()</li>";
    }
    echo "</ul></details>";

    // Check required methods
    $requiredMethods = [
        'getUsersWhoLikedSameRooms',
        'getMatchedUserIds',
        'calculateCompatibilityScore',
        'getMatchStatus',
        'createTripleMatch'
    ];

    echo "<br><h3>Required Methods Check:</h3><ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($matchModel, $method);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    echo "<br><h3 style='color: green;'>✓ MATCH MODEL LOADED SUCCESSFULLY!</h3>";

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

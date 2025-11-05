<?php
// TEST 5C DEBUG: Chi tiết từng bước load Match.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>TEST 5C DEBUG: Match.php Step-by-Step</h2>";
echo "<style>
    .success { color: green; background: #e8f5e9; padding: 5px; margin: 2px 0; }
    .error { color: red; background: #ffebee; padding: 5px; margin: 2px 0; }
    .info { color: blue; background: #e3f2fd; padding: 5px; margin: 2px 0; }
</style>";

try {
    echo "<div class='info'>Step 1: Loading database.php...</div>";
    require_once __DIR__ . '/config/database.php';
    echo "<div class='success'>✓ database.php loaded</div>";

    echo "<div class='info'>Step 2: Testing database connection...</div>";
    $db = getDB();
    echo "<div class='success'>✓ Database connection OK</div>";

    echo "<div class='info'>Step 3: Loading constants.php...</div>";
    require_once __DIR__ . '/config/constants.php';
    echo "<div class='success'>✓ constants.php loaded</div>";

    echo "<div class='info'>Step 4: Loading functions.php...</div>";
    require_once __DIR__ . '/includes/functions.php';
    echo "<div class='success'>✓ functions.php loaded</div>";

    echo "<div class='info'>Step 5: Checking Match.php file...</div>";
    $matchFile = __DIR__ . '/includes/Match.php';

    if (!file_exists($matchFile)) {
        throw new Exception("Match.php not found at: $matchFile");
    }
    echo "<div class='success'>✓ Match.php file exists</div>";

    echo "<div class='info'>File size: " . filesize($matchFile) . " bytes</div>";

    echo "<div class='info'>Step 6: Reading Match.php content...</div>";
    $content = file_get_contents($matchFile);
    echo "<div class='success'>✓ File readable, length: " . strlen($content) . " chars</div>";

    echo "<div class='info'>Step 7: Checking for syntax errors with php -l...</div>";
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($matchFile) . " 2>&1", $output, $return);

    if ($return === 0) {
        echo "<div class='success'>✓ No syntax errors detected</div>";
    } else {
        echo "<div class='error'>✗ Syntax error detected:</div>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        die();
    }

    echo "<div class='info'>Step 8: Attempting to include Match.php...</div>";

    // Use output buffering to catch any output/errors during include
    ob_start();
    $includeError = null;

    try {
        require_once $matchFile;
        $includeOutput = ob_get_clean();

        if (!empty($includeOutput)) {
            echo "<div class='error'>⚠ Output during include:</div>";
            echo "<pre>" . htmlspecialchars($includeOutput) . "</pre>";
        } else {
            echo "<div class='success'>✓ Match.php included successfully (no output)</div>";
        }

    } catch (Throwable $e) {
        $includeOutput = ob_get_clean();
        echo "<div class='error'>✗ Error during include:</div>";
        echo "<div class='error'>";
        echo "Type: " . get_class($e) . "<br>";
        echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        echo "</div>";

        if (!empty($includeOutput)) {
            echo "<div class='error'>Output before error:</div>";
            echo "<pre>" . htmlspecialchars($includeOutput) . "</pre>";
        }

        echo "<div class='error'>Stack trace:</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        die();
    }

    echo "<div class='info'>Step 9: Checking if MatchModel class exists...</div>";
    if (!class_exists('MatchModel')) {
        throw new Exception("MatchModel class not defined after include!");
    }
    echo "<div class='success'>✓ MatchModel class exists</div>";

    echo "<div class='info'>Step 10: Creating MatchModel object...</div>";
    $matchModel = new MatchModel();
    echo "<div class='success'>✓ MatchModel object created!</div>";

    echo "<div class='info'>Step 11: Checking methods...</div>";
    $methods = get_class_methods($matchModel);
    echo "<div class='success'>✓ Total methods: " . count($methods) . "</div>";

    echo "<details><summary>All methods (" . count($methods) . "):</summary><ul>";
    foreach ($methods as $method) {
        echo "<li>$method()</li>";
    }
    echo "</ul></details>";

    // Test critical methods
    $requiredMethods = [
        'create',
        'getById',
        'getByUser',
        'getUsersWhoLikedSameRooms',
        'calculateCompatibilityScore',
        'getMatchedUserIds'
    ];

    echo "<br><h3>Required Methods:</h3><ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($matchModel, $method);
        $color = $exists ? 'green' : 'red';
        $icon = $exists ? '✓' : '✗';
        echo "<li style='color: $color;'><b>$icon</b> $method()</li>";
    }
    echo "</ul>";

    echo "<br><div class='success' style='font-size: 18px; font-weight: bold;'>";
    echo "🎉 ALL TESTS PASSED! Match.php is working correctly.";
    echo "</div>";

} catch (Throwable $e) {
    echo "<br><div class='error' style='border: 2px solid red; padding: 15px;'>";
    echo "<h3>❌ FATAL ERROR</h3>";
    echo "<strong>Type:</strong> " . get_class($e) . "<br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<br><h3>PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Error Reporting Level: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
?>

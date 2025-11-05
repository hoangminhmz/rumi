<?php
/**
 * SIMPLE TEST - Chỉ test load Match.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/match_error.log');

// Create logs directory
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0755, true);
}

echo "<h2>SIMPLE MATCH.PHP TEST</h2>";
echo "<pre>";

// Step 1
echo "Step 1: Load database.php\n";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ OK\n\n";
} catch (Throwable $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    die();
}

// Step 2
echo "Step 2: Load constants.php\n";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "✓ OK\n\n";
} catch (Throwable $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    die();
}

// Step 3
echo "Step 3: Test database connection\n";
try {
    $db = getDB();
    echo "✓ OK\n\n";
} catch (Throwable $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    die();
}

// Step 4
echo "Step 4: Load Match.php\n";
echo "File path: " . __DIR__ . '/includes/Match.php' . "\n";
echo "File exists: " . (file_exists(__DIR__ . '/includes/Match.php') ? 'YES' : 'NO') . "\n";

try {
    require_once __DIR__ . '/includes/Match.php';
    echo "✓ Match.php loaded\n\n";
} catch (ParseError $e) {
    echo "✗ PARSE ERROR (Syntax Error):\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    die();
} catch (Throwable $e) {
    echo "✗ ERROR:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

// Step 5
echo "Step 5: Check Match class exists\n";
if (class_exists('Match')) {
    echo "✓ Match class exists\n\n";
} else {
    echo "✗ Match class NOT found\n";
    die();
}

// Step 6
echo "Step 6: Create Match object\n";
try {
    $match = new Match();
    echo "✓ Match object created\n\n";
} catch (Throwable $e) {
    echo "✗ ERROR creating Match object:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    die();
}

// Step 7
echo "Step 7: Check methods\n";
$methods = get_class_methods($match);
echo "Total methods: " . count($methods) . "\n";
echo "Methods:\n";
foreach ($methods as $m) {
    echo "  - $m()\n";
}

echo "\n✓✓✓ ALL TESTS PASSED ✓✓✓\n";
echo "</pre>";
?>

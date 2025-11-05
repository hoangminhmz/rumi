<?php
// TEST 3: DATABASE CONNECTION
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 3: Database Connection</h2>";

try {
    echo "Step 1: Loading database.php...<br>";

    // Check file exists
    $dbFile = __DIR__ . '/config/database.php';
    if (!file_exists($dbFile)) {
        die("ERROR: database.php not found at: $dbFile");
    }
    echo "✓ database.php exists<br>";

    // Include file
    require_once $dbFile;
    echo "✓ database.php loaded<br>";

    // Check function exists
    if (!function_exists('getDB')) {
        die("ERROR: getDB() function not found");
    }
    echo "✓ getDB() function exists<br>";

    // Get database connection
    echo "Step 2: Connecting to database...<br>";
    $db = getDB();
    echo "✓ Database connected!<br>";

    // Test query
    echo "Step 3: Testing query...<br>";
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✓ Query works! Result: " . $result['test'] . "<br>";

    // Count users
    echo "Step 4: Counting users...<br>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Total users: " . $result['count'] . "<br>";

    echo "<br><b style='color: green;'>✓ ALL TESTS PASSED!</b>";

} catch (Exception $e) {
    echo "<br><b style='color: red;'>ERROR:</b> " . htmlspecialchars($e->getMessage());
    echo "<br><br>Stack trace:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

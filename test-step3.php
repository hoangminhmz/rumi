<?php
/**
 * Step 3: Test Database Connection
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Step 3</title></head><body>";
echo "<h1>STEP 3: Database Connection</h1>";

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<h3>Database Config:</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Password: " . (DB_PASS ? '***' : 'EMPTY') . "<br>";

echo "<h3>Attempting Connection...</h3>";
try {
    $pdo = getDB();
    echo "✅ <strong style='color:green;'>Database Connected Successfully!</strong><br>";

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM districts");
    $result = $stmt->fetch();
    echo "✅ Test query OK - Found " . $result['count'] . " districts<br>";

} catch (PDOException $e) {
    echo "❌ <strong style='color:red;'>Database Connection FAILED!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<hr>";
    echo "<h3>🔧 FIX INSTRUCTIONS:</h3>";
    echo "<ol>";
    echo "<li>Vào cPanel → MySQL Databases</li>";
    echo "<li>Tạo database tên: <code>" . DB_NAME . "</code></li>";
    echo "<li>Tạo user: <code>" . DB_USER . "</code></li>";
    echo "<li>Add user to database với ALL PRIVILEGES</li>";
    echo "<li>Vào phpMyAdmin → Import file <code>database/schema.sql</code></li>";
    echo "</ol>";
    die();
}

echo "<hr>";
echo "<a href='test-step4.php'>→ Next: Test Step 4</a>";
echo "</body></html>";
?>

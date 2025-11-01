<?php
/**
 * Step 4: Test Session & Functions
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Step 4</title></head><body>";
echo "<h1>STEP 4: Session & Helper Functions</h1>";

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<h3>Loading functions.php...</h3>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ functions.php loaded<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h3>Testing Session...</h3>";
try {
    startSession();
    echo "✅ Session started OK<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Throwable $e) {
    echo "❌ Session Error: " . $e->getMessage() . "<br>";
    echo "<h4>🔧 Fix Session:</h4>";
    echo "<p>Trong cPanel → PHP Configuration → session.save_path = /home/hoangmi5/tmp</p>";
    die();
}

echo "<h3>Testing Helper Functions...</h3>";
if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn() exists<br>";
}
if (function_exists('formatPrice')) {
    echo "✅ formatPrice() exists - Test: " . formatPrice(1000000) . "<br>";
}

echo "<hr>";
echo "<h2 style='color:green;'>🎉 ALL TESTS PASSED!</h2>";
echo "<p>Bây giờ thử test trang thật:</p>";
echo "<ul>";
echo "<li><a href='index.php'>index.php (Landing)</a></li>";
echo "<li><a href='pages/login.php'>pages/login.php (Login)</a></li>";
echo "</ul>";
echo "</body></html>";
?>

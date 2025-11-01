<?php
/**
 * RUMI Error Display
 * File này sẽ hiện lỗi chi tiết
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing RUMI Dependencies...</h1>";

// Test 1: Check PHP version
echo "<h2>1. PHP Version:</h2>";
echo "Current: " . phpversion() . "<br>";
if (version_compare(phpversion(), '8.1.0', '>=')) {
    echo "✅ PHP version OK<br>";
} else {
    echo "❌ Need PHP >= 8.1<br>";
}

// Test 2: Check required files exist
echo "<h2>2. Required Files:</h2>";
$required_files = [
    'config/database.php',
    'config/constants.php',
    'config/zalo.php',
    'includes/functions.php',
    'includes/User.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ <strong style='color:red;'>$file MISSING!</strong><br>";
    }
}

// Test 3: Try to include config files
echo "<h2>3. Loading Config Files:</h2>";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "✅ constants.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error in constants.php: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error in database.php: " . $e->getMessage() . "<br>";
}

// Test 4: Try to connect to database
echo "<h2>4. Database Connection:</h2>";
try {
    if (function_exists('getDB')) {
        $db = getDB();
        echo "✅ Database connected successfully!<br>";
    } else {
        echo "❌ getDB() function not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: <strong style='color:red;'>" . $e->getMessage() . "</strong><br>";
    echo "<p>Check your database credentials in config/database.php</p>";
}

// Test 5: Check includes/functions.php
echo "<h2>5. Helper Functions:</h2>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ functions.php loaded<br>";

    if (function_exists('isLoggedIn')) {
        echo "✅ Helper functions available<br>";
    }
} catch (Exception $e) {
    echo "❌ Error in functions.php: " . $e->getMessage() . "<br>";
}

// Test 6: Check session
echo "<h2>6. Session:</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session started<br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li>Nếu tất cả ✅ → Test <a href='pages/login.php'>login.php</a></li>";
echo "<li>Nếu có ❌ → Fix lỗi đó trước</li>";
echo "<li>Check <a href='check-errors.php'>check-errors.php</a> để xem PHP errors</li>";
echo "</ul>";
?>

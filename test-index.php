<?php
/**
 * Test index.php in safe mode
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing index.php Components</h1>";

// Load dependencies
echo "<h3>1. Loading config...</h3>";
try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/config/zalo.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ Config loaded<br>";
} catch (Throwable $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
    die();
}

// Test session
echo "<h3>2. Testing session...</h3>";
try {
    startSession();
    echo "✅ Session OK<br>";
} catch (Throwable $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

// Check if logged in (should redirect if true)
echo "<h3>3. Checking login status...</h3>";
if (isLoggedIn()) {
    echo "⚠️ User is logged in - index.php would redirect to swipe.php<br>";
} else {
    echo "✅ Not logged in - can show landing page<br>";
}

// Now try to render the actual index.php content
echo "<h3>4. Rendering Landing Page...</h3>";
echo "<hr>";

// Copy the landing page HTML from index.php
$pageTitle = 'Tìm bạn cùng phòng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - RUMI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>
    <div style="text-align: center; padding: 50px;">
        <h1 style="color: #00D4AA;">✅ Landing Page Content Renders OK!</h1>
        <p>Nếu thấy trang này → index.php có thể render được</p>
        <hr>
        <h3>Test Links:</h3>
        <p><a href="index.php">Test Real index.php</a></p>
        <p><a href="test-login.php">Test Login Page</a></p>
    </div>
</body>
</html>

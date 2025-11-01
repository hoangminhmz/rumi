<?php
/**
 * Test pages/login.php in safe mode
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Login Page Components</h1>";

// Load dependencies
echo "<h3>1. Loading dependencies...</h3>";
try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/config/zalo.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ Dependencies loaded<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
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

// Check if already logged in
echo "<h3>3. Checking if already logged in...</h3>";
if (isLoggedIn()) {
    echo "⚠️ Already logged in - would redirect to swipe.php<br>";
} else {
    echo "✅ Not logged in - can show login page<br>";
}

// Test Zalo URL generation
echo "<h3>4. Testing Zalo Login URL...</h3>";
try {
    $zaloLoginUrl = getZaloLoginURL();
    echo "✅ Zalo URL generated: " . htmlspecialchars($zaloLoginUrl) . "<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Render login page
echo "<h3>5. Rendering Login Page...</h3>";
echo "<hr>";

$pageTitle = 'Đăng nhập';
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
    <div style="text-align: center; padding: 50px; max-width: 500px; margin: 0 auto;">
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h1 style="color: #00D4AA;">✅ Login Page Renders OK!</h1>
            <p>Nếu thấy trang này → Login page có thể render</p>

            <hr>

            <h3>Zalo Login Button:</h3>
            <a href="<?= htmlspecialchars($zaloLoginUrl) ?>" class="btn btn-primary">
                Đăng nhập với Zalo (TEST)
            </a>

            <hr>

            <p><a href="pages/login.php">Test Real Login Page</a></p>
            <p><a href="test-index.php">Back to Test Index</a></p>
        </div>
    </div>
</body>
</html>

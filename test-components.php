<?php
/**
 * Test if component files exist and can be included
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Component Files</h1>";

$components = [
    'components/header.php',
    'components/footer.php',
    'components/navigation.php',
    'components/cards.php'
];

echo "<h3>Checking component files:</h3>";
foreach ($components as $component) {
    $path = __DIR__ . '/' . $component;
    if (file_exists($path)) {
        echo "✅ $component exists<br>";

        // Try to include it
        try {
            // Don't actually include, just check syntax
            $content = file_get_contents($path);
            if ($content === false) {
                echo "  ⚠️ Cannot read file<br>";
            } else {
                echo "  ✅ File readable (" . strlen($content) . " bytes)<br>";
            }
        } catch (Throwable $e) {
            echo "  ❌ Error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ <strong>$component MISSING!</strong><br>";
    }
}

echo "<hr>";
echo "<h3>Checking pages files:</h3>";
$pages = [
    'index.php',
    'pages/login.php',
    'pages/profile-setup.php',
    'pages/swipe.php'
];

foreach ($pages as $page) {
    $path = __DIR__ . '/' . $page;
    if (file_exists($path)) {
        echo "✅ $page exists<br>";
    } else {
        echo "❌ <strong>$page MISSING!</strong><br>";
    }
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<p>Nếu tất cả files ✅ → Test thử include header component:</p>";
echo "<a href='test-header.php'>Test Header Component</a>";
?>

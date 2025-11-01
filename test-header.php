<?php
/**
 * Test including header component
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Header Component Include</h1>";

// Load dependencies first
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/User.php';
    echo "<p>✅ Dependencies loaded</p>";
} catch (Throwable $e) {
    echo "<p>❌ Dependency error: " . $e->getMessage() . "</p>";
    die();
}

// Set page title
$pageTitle = 'Test Header';

echo "<p>Attempting to include header component...</p>";
echo "<hr>";

// Try to include header
try {
    include __DIR__ . '/components/header.php';
    echo "<h2 style='color:green;'>✅ Header Included Successfully!</h2>";
} catch (Throwable $e) {
    echo "<h2 style='color:red;'>❌ Header Include Failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Content
echo "<div style='padding: 20px;'>";
echo "<h3>If you see the RUMI header above → Component works!</h3>";
echo "<p>If not, there's an error in header.php</p>";
echo "</div>";

// Try to include footer
try {
    include __DIR__ . '/components/footer.php';
} catch (Throwable $e) {
    echo "<p>Footer error: " . $e->getMessage() . "</p>";
}
?>

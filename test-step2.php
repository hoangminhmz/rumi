<?php
/**
 * Step 2: Test Include Config Files
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Step 2</title></head><body>";
echo "<h1>STEP 2: Loading Config Files</h1>";

// Test loading constants.php
echo "<h3>Loading constants.php...</h3>";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "✅ constants.php loaded OK<br>";
    echo "BASE_URL = " . BASE_URL . "<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    die();
}

// Test loading database.php
echo "<h3>Loading database.php...</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php loaded OK<br>";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    die();
}

echo "<hr>";
echo "<a href='test-step3.php'>→ Next: Test Step 3</a>";
echo "</body></html>";
?>

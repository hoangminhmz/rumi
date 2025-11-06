<?php
/**
 * Simple UTF-8 Charset Fix
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();

echo "<h1>Fix Charset to UTF-8mb4</h1>";
echo "<pre>";

try {
    // Fix preferences_list
    echo "Fixing preferences_list...\n";
    $db->exec("ALTER TABLE preferences_list CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ preferences_list converted\n\n";

    // Fix amenities_list
    echo "Fixing amenities_list...\n";
    $db->exec("ALTER TABLE amenities_list CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ amenities_list converted\n\n";

    // Check result
    echo "Checking results...\n";
    $result = $db->query("
        SELECT TABLE_NAME, TABLE_COLLATION
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = 'rumi_db'
        AND TABLE_NAME IN ('preferences_list', 'amenities_list')
    ");

    while ($row = $result->fetch()) {
        echo "  {$row['TABLE_NAME']}: {$row['TABLE_COLLATION']}\n";
    }

    echo "\n✅ Done! Check phpMyAdmin again.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='preferences.php'>Go to Preferences</a>";
?>

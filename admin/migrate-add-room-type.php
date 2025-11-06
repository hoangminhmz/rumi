<?php
/**
 * Add room_type column to rooms table
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

echo "<h1>Add room_type Column Migration</h1>";
echo "<pre>";

try {
    // Check if column already exists
    $result = $db->query("SHOW COLUMNS FROM rooms LIKE 'room_type'");
    if ($result->fetch()) {
        echo "✓ Column 'room_type' already exists. No action needed.\n";
    } else {
        echo "Adding room_type column...\n";

        // Add column
        $db->exec("ALTER TABLE rooms ADD COLUMN room_type VARCHAR(50) NULL AFTER area");
        echo "✓ Added room_type column\n";

        // Add index
        $db->exec("ALTER TABLE rooms ADD INDEX idx_room_type (room_type)");
        echo "✓ Added index on room_type\n";

        // Set default value for existing rooms
        $db->exec("UPDATE rooms SET room_type = 'apartment' WHERE room_type IS NULL");
        echo "✓ Set default value 'apartment' for existing rooms\n";
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='preferences.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Back to Admin</a>";
?>

<?php
/**
 * SIMPLE TEST - Check cơ bản
 */

// Hiển thị tất cả lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Test</title></head><body>";
echo "<h1>Simple Test</h1>";

// Test 1: PHP hoạt động
echo "<h2>✅ Test 1: PHP is working!</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test 2: Database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/constants.php';

    $db = getDB();
    echo "<p style='color: green;'>✅ Database connected!</p>";

    // Query test
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Total users: " . $result['count'] . "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 3: Check columns
echo "<h2>Test 3: Check Database Schema</h2>";
try {
    // Check users table
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Users table columns:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col['Field']) . " (" . htmlspecialchars($col['Type']) . ")</li>";
    }
    echo "</ul>";

    // Check for new columns
    $columnNames = array_column($columns, 'Field');
    $newColumns = ['search_mode', 'sleep_schedule', 'work_schedule', 'drinking', 'guests_policy'];

    echo "<h3>New columns check:</h3>";
    echo "<ul>";
    foreach ($newColumns as $col) {
        $exists = in_array($col, $columnNames);
        $icon = $exists ? "✅" : "❌";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'>$icon $col</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Check tables
echo "<h2>Test 4: Check New Tables</h2>";
try {
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>All tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";

    $requiredTables = ['amenities_list', 'preferences_list'];
    echo "<h3>Required new tables:</h3>";
    echo "<ul>";
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $tables);
        $icon = $exists ? "✅" : "❌";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'>$icon $table";

        if ($exists) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo " ($count rows)";
        }
        echo "</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Load models
echo "<h2>Test 5: Load Models</h2>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p style='color: green;'>✅ functions.php loaded</p>";

    require_once __DIR__ . '/includes/User.php';
    echo "<p style='color: green;'>✅ User.php loaded</p>";

    require_once __DIR__ . '/includes/Room.php';
    echo "<p style='color: green;'>✅ Room.php loaded</p>";

    require_once __DIR__ . '/includes/Match.php';
    echo "<p style='color: green;'>✅ Match.php loaded</p>";

    // Create objects
    $userModel = new User();
    echo "<p style='color: green;'>✅ User object created</p>";

    $roomModel = new Room();
    echo "<p style='color: green;'>✅ Room object created</p>";

    $matchModel = new MatchModel();
    echo "<p style='color: green;'>✅ MatchModel object created</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading models: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 6: Check methods
echo "<h2>Test 6: Check Methods</h2>";
if (isset($userModel)) {
    $requiredMethods = [
        'canAccessRoomTab',
        'canAccessRoommateTab',
        'getLikedRoomIds',
        'getMatchedUsers'
    ];

    echo "<h3>User Model Methods:</h3>";
    echo "<ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($userModel, $method);
        $icon = $exists ? "✅" : "❌";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'>$icon $method</li>";
    }
    echo "</ul>";
}

if (isset($matchModel)) {
    $requiredMethods = [
        'getUsersWhoLikedSameRooms',
        'getMatchedUserIds',
        'calculateCompatibilityScore'
    ];

    echo "<h3>Match Model Methods:</h3>";
    echo "<ul>";
    foreach ($requiredMethods as $method) {
        $exists = method_exists($matchModel, $method);
        $icon = $exists ? "✅" : "❌";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'>$icon $method</li>";
    }
    echo "</ul>";
}

// Final instructions
echo "<h2>📋 Next Steps</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<h3>If you see ❌ above:</h3>";
echo "<ol>";
echo "<li>Missing columns? Run migration: <a href='database/migrations/run_v2_migration.php'>Run Migration</a></li>";
echo "<li>Missing tables? Check MIGRATION_GUIDE.md</li>";
echo "<li>Missing methods? Update Model files from latest code</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;'>";
echo "<h3>📝 Files to check:</h3>";
echo "<ul>";
echo "<li><strong>debug_test.php</strong> - Detailed debugging (this file)</li>";
echo "<li><strong>simple_test.php</strong> - Simple checks</li>";
echo "<li><strong>logs/swipe_debug.log</strong> - Detailed logs from swipe.php</li>";
echo "<li><strong>MIGRATION_GUIDE.md</strong> - Full migration guide</li>";
echo "<li><strong>DEBUG_INSTRUCTIONS.md</strong> - Debug instructions</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>

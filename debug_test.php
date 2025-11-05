<?php
/**
 * DEBUG TEST FILE
 * Test từng phần để tìm lỗi
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 RUMI Debug Test</h1>";
echo "<pre>";

// Test 1: PHP Info
echo "=== TEST 1: PHP VERSION ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n\n";

// Test 2: File Paths
echo "=== TEST 2: FILE PATHS ===\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Config exists: " . (file_exists(__DIR__ . '/config/database.php') ? 'YES' : 'NO') . "\n";
echo "User.php exists: " . (file_exists(__DIR__ . '/includes/User.php') ? 'YES' : 'NO') . "\n";
echo "Match.php exists: " . (file_exists(__DIR__ . '/includes/Match.php') ? 'YES' : 'NO') . "\n";
echo "Room.php exists: " . (file_exists(__DIR__ . '/includes/Room.php') ? 'YES' : 'NO') . "\n\n";

// Test 3: Database Connection
echo "=== TEST 3: DATABASE CONNECTION ===\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "✅ Database connected successfully\n";

    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users count: " . $result['count'] . "\n\n";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check Tables Structure
echo "=== TEST 4: CHECK USERS TABLE ===\n";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredColumns = [
        'search_mode', 'sleep_schedule', 'work_schedule',
        'drinking', 'guests_policy', 'move_in_date',
        'stay_duration', 'occupation', 'facebook_url', 'linkedin_url'
    ];

    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columns);
        echo ($exists ? "✅" : "❌") . " Column '$col': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "\n\n";
}

// Test 5: Check Rooms Table
echo "=== TEST 5: CHECK ROOMS TABLE ===\n";
try {
    $stmt = $db->query("DESCRIBE rooms");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredColumns = ['ward', 'latitude', 'longitude', 'room_type', 'geocoded'];

    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columns);
        echo ($exists ? "✅" : "❌") . " Column '$col': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error checking rooms table: " . $e->getMessage() . "\n\n";
}

// Test 6: Check New Tables
echo "=== TEST 6: CHECK NEW TABLES ===\n";
try {
    $tables = ['amenities_list', 'preferences_list'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? "✅" : "❌") . " Table '$table': " . ($exists ? "EXISTS" : "MISSING") . "\n";

        if ($exists) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "   └─ Rows: " . $result['count'] . "\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "\n\n";
}

// Test 7: Load Models
echo "=== TEST 7: LOAD MODELS ===\n";
try {
    require_once __DIR__ . '/includes/User.php';
    echo "✅ User.php loaded\n";

    require_once __DIR__ . '/includes/Room.php';
    echo "✅ Room.php loaded\n";

    require_once __DIR__ . '/includes/Match.php';
    echo "✅ Match.php loaded\n\n";
} catch (Exception $e) {
    echo "❌ Error loading models: " . $e->getMessage() . "\n\n";
}

// Test 8: Check Methods
echo "=== TEST 8: CHECK METHODS EXIST ===\n";
try {
    $userModel = new User();
    echo "✅ User object created\n";

    $methods = [
        'canAccessRoomTab',
        'canAccessRoommateTab',
        'getLikedRoomIds',
        'getMatchedUsers'
    ];

    foreach ($methods as $method) {
        $exists = method_exists($userModel, $method);
        echo ($exists ? "✅" : "❌") . " Method '$method': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";

    $roomModel = new Room();
    echo "✅ Room object created\n";

    $methods = [
        'getRoomsForAllMatches',
        'getPotentialRooms'
    ];

    foreach ($methods as $method) {
        $exists = method_exists($roomModel, $method);
        echo ($exists ? "✅" : "❌") . " Method '$method': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";

    $matchModel = new Match();
    echo "✅ Match object created\n";

    $methods = [
        'getUsersWhoLikedSameRooms',
        'getMatchedUserIds',
        'calculateCompatibilityScore'
    ];

    foreach ($methods as $method) {
        $exists = method_exists($matchModel, $method);
        echo ($exists ? "✅" : "❌") . " Method '$method': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "❌ Error checking methods: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test 9: Test Session
echo "=== TEST 9: SESSION TEST ===\n";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ functions.php loaded\n";

    session_start();
    echo "✅ Session started\n";

    $_SESSION['test'] = 'Hello World';
    echo "✅ Session write test: " . $_SESSION['test'] . "\n\n";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "\n\n";
}

// Test 10: Check Logs Directory
echo "=== TEST 10: LOGS DIRECTORY ===\n";
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    echo "⚠️  Logs directory doesn't exist, creating...\n";
    if (mkdir($logsDir, 0755, true)) {
        echo "✅ Logs directory created\n";
    } else {
        echo "❌ Failed to create logs directory\n";
    }
} else {
    echo "✅ Logs directory exists\n";
}

if (is_writable($logsDir)) {
    echo "✅ Logs directory is writable\n";

    // Test write
    $testFile = $logsDir . '/test.txt';
    if (file_put_contents($testFile, "Test write at " . date('Y-m-d H:i:s'))) {
        echo "✅ Test write successful\n";
        unlink($testFile);
    } else {
        echo "❌ Test write failed\n";
    }
} else {
    echo "❌ Logs directory is NOT writable\n";
}
echo "\n";

// Test 11: Test swipe.php include
echo "=== TEST 11: TEST COMPONENTS ===\n";
$emptyStateFile = __DIR__ . '/components/empty-state-locked.php';
if (file_exists($emptyStateFile)) {
    echo "✅ empty-state-locked.php exists\n";
    try {
        require_once $emptyStateFile;
        echo "✅ empty-state-locked.php loaded successfully\n";

        if (function_exists('renderLockedTabState')) {
            echo "✅ Function renderLockedTabState exists\n";
        } else {
            echo "❌ Function renderLockedTabState NOT found\n";
        }
    } catch (Exception $e) {
        echo "❌ Error loading empty-state-locked.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  empty-state-locked.php NOT found (optional)\n";
}
echo "\n";

// Final Summary
echo "=== 📊 SUMMARY ===\n";
echo "If you see ❌ above, those are the issues to fix.\n";
echo "Check MIGRATION_GUIDE.md for solutions.\n\n";

echo "Next steps:\n";
echo "1. Fix any ❌ issues above\n";
echo "2. Run: http://your-domain.com/database/migrations/run_v2_migration.php\n";
echo "3. Try swipe.php again\n";
echo "4. Check logs/swipe_debug.log for detailed errors\n";

echo "</pre>";
?>

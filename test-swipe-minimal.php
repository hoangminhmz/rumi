<?php
/**
 * SUPER SIMPLE SWIPE TEST
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>SWIPE DATABASE TEST</h1>";

// Step 1: Database
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>");
}

// Step 2: Check user_swipes table
echo "<h2>Step 2: Check user_swipes Table</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'user_swipes'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ user_swipes table exists</p>";
    } else {
        die("<p style='color: red;'>❌ user_swipes table does NOT exist!</p>");
    }

    // Get count
    $stmt = $db->query("SELECT COUNT(*) as count FROM user_swipes");
    $count = $stmt->fetch()['count'];
    echo "<p>Total swipes in database: <strong>$count</strong></p>";

    // Show last 5 swipes
    $stmt = $db->query("SELECT * FROM user_swipes ORDER BY created_at DESC LIMIT 5");
    $swipes = $stmt->fetchAll();

    if (!empty($swipes)) {
        echo "<h3>Last 5 swipes:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>User ID</th><th>Target ID</th><th>Like?</th><th>Created At</th></tr>";
        foreach ($swipes as $s) {
            echo "<tr>";
            echo "<td>{$s['user_id']}</td>";
            echo "<td>{$s['target_user_id']}</td>";
            echo "<td>" . ($s['is_like'] ? 'YES' : 'NO') . "</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No swipes in database yet</p>";
    }

} catch (Exception $e) {
    die("<p style='color: red;'>❌ Table check error: " . $e->getMessage() . "</p>");
}

// Step 3: Test INSERT manually
echo "<h2>Step 3: Test Manual INSERT</h2>";
$testUserId = 1;
$testTargetId = 2;

try {
    // Check before
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$testUserId]);
    $beforeCount = $stmt->fetch()['count'];
    echo "<p>Swipes for user $testUserId BEFORE: $beforeCount</p>";

    // Try insert
    $stmt = $db->prepare("
        INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_like = ?, created_at = NOW()
    ");

    $result = $stmt->execute([$testUserId, $testTargetId, 1, 1]);

    if ($result) {
        echo "<p style='color: green;'>✅ INSERT executed successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ INSERT failed</p>";
    }

    // Check after
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$testUserId]);
    $afterCount = $stmt->fetch()['count'];
    echo "<p>Swipes for user $testUserId AFTER: $afterCount</p>";

    $diff = $afterCount - $beforeCount;
    if ($diff > 0) {
        echo "<p style='color: green;'>✅ INSERT worked! Added $diff swipe(s)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No new swipe added (might be duplicate - that's OK)</p>";
    }

    // Verify the record
    $stmt = $db->prepare("SELECT * FROM user_swipes WHERE user_id = ? AND target_user_id = ?");
    $stmt->execute([$testUserId, $testTargetId]);
    $record = $stmt->fetch();

    if ($record) {
        echo "<p style='color: green;'>✅ Record found in database!</p>";
        echo "<pre>";
        print_r($record);
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ INSERT error: " . $e->getMessage() . "</p>";
}

// Step 4: Test User model swipe function
echo "<h2>Step 4: Test User Model</h2>";
try {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/User.php';

    $userModel = new User();
    echo "<p style='color: green;'>✅ User model loaded</p>";

    // Get count before
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([1]);
    $beforeCount = $stmt->fetch()['count'];
    echo "<p>Swipes BEFORE User::swipe(): $beforeCount</p>";

    // Test swipe
    $result = $userModel->swipe(1, 3, true);

    if ($result) {
        echo "<p style='color: green;'>✅ User::swipe() returned TRUE</p>";
    } else {
        echo "<p style='color: red;'>❌ User::swipe() returned FALSE</p>";
    }

    // Get count after
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([1]);
    $afterCount = $stmt->fetch()['count'];
    echo "<p>Swipes AFTER User::swipe(): $afterCount</p>";

    $diff = $afterCount - $beforeCount;
    if ($diff > 0) {
        echo "<p style='color: green;'>✅ User::swipe() works! Added $diff swipe</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No change (might be duplicate)</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ User model error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests passed, swipe SHOULD work. If not, check the errors above.</p>";
echo "<p><a href='pages/swipe.php'>Go to Swipe Page</a></p>";
?>

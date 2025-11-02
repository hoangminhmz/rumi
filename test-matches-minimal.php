<?php
/**
 * SUPER SIMPLE MATCHES TEST
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>MATCHES PAGE DEBUG</h1>";

// Step 1: Test database
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>");
}

// Step 2: Check if matches table exists
echo "<h2>Step 2: Check Matches Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM matches");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ Matches table exists. Total matches: $count</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Matches table error: " . $e->getMessage() . "</p>");
}

// Step 3: Test query
echo "<h2>Step 3: Test Query</h2>";
try {
    $userId = 1; // Test with user 1

    $stmt = $db->prepare("
        SELECT m.*,
            u1.name as user1_name, u1.avatar as user1_avatar,
            u1.age as user1_age, u1.gender as user1_gender, u1.phone as user1_phone,
            d1.name as user1_district,
            u2.name as user2_name, u2.avatar as user2_avatar,
            u2.age as user2_age, u2.gender as user2_gender, u2.phone as user2_phone,
            d2.name as user2_district,
            r.title as room_title, r.price as room_price
        FROM matches m
        JOIN users u1 ON m.user1_id = u1.id
        JOIN users u2 ON m.user2_id = u2.id
        LEFT JOIN districts d1 ON u1.district_id = d1.id
        LEFT JOIN districts d2 ON u2.district_id = d2.id
        LEFT JOIN rooms r ON m.room_id = r.id
        WHERE (m.user1_id = ? OR m.user2_id = ?)
        ORDER BY m.matched_at DESC
    ");

    $stmt->execute([$userId, $userId]);
    $matches = $stmt->fetchAll();

    echo "<p style='color: green;'>✅ Query executed. Found " . count($matches) . " matches for user $userId</p>";

    if (!empty($matches)) {
        echo "<h3>First Match Data:</h3>";
        echo "<pre>";
        print_r($matches[0]);
        echo "</pre>";
    }

} catch (Exception $e) {
    die("<p style='color: red;'>❌ Query error: " . $e->getMessage() . "</p>");
}

// Step 4: Test loading matches page directly
echo "<h2>Step 4: Summary</h2>";
echo "<p>✅ All database checks passed!</p>";
echo "<p><a href='pages/matches.php' style='padding: 1rem; background: #00D4AA; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Try Matches Page Now</a></p>";
?>

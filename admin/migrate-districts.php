<?php
/**
 * RUMI - Admin: District Coordinates Migration
 * Run this page once to add coordinates to districts table
 */

require_once __DIR__ . '/../config/database.php';

// Simple auth (remove or improve in production)
$adminToken = $_GET['token'] ?? '';
if ($adminToken !== 'rumi_admin_2024') {
    die('⛔ Unauthorized. Add ?token=rumi_admin_2024 to URL');
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Migration - RUMI Admin</title>
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f7;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d1d1f;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #86868b;
            margin-bottom: 30px;
        }
        .log {
            background: #1d1d1f;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        .error { color: #ff3b30; }
        .info { color: #0a84ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ District Coordinates Migration</h1>
        <p class="subtitle">Adding latitude/longitude to districts table</p>

        <div class="log"><?php

try {
    $db = getDB();

    echo "🚀 <span class='info'>Starting district coordinates migration...</span>\n\n";

    // Step 1: Add latitude and longitude columns
    echo "📊 <span class='info'>Adding latitude and longitude columns...</span>\n";

    // Check if columns exist
    $stmt = $db->query("SHOW COLUMNS FROM districts LIKE 'latitude'");
    $hasLatitude = $stmt->rowCount() > 0;

    $stmt = $db->query("SHOW COLUMNS FROM districts LIKE 'longitude'");
    $hasLongitude = $stmt->rowCount() > 0;

    if (!$hasLatitude || !$hasLongitude) {
        if (!$hasLatitude) {
            $db->exec("ALTER TABLE districts ADD COLUMN latitude DECIMAL(10, 8) NULL");
            echo "   ✅ Added latitude column\n";
        }
        if (!$hasLongitude) {
            $db->exec("ALTER TABLE districts ADD COLUMN longitude DECIMAL(11, 8) NULL");
            echo "   ✅ Added longitude column\n";
        }
    } else {
        echo "   ℹ️ Columns already exist\n";
    }
    echo "\n";

    // Step 2: Populate coordinates for Hanoi districts
    echo "📍 <span class='info'>Populating coordinates for districts...</span>\n\n";

    // District coordinates (approximate centers)
    $districtCoordinates = [
        // Hanoi urban districts
        ['name' => 'Ba Đình', 'city' => 'Hà Nội', 'lat' => 21.0341, 'lng' => 105.8195],
        ['name' => 'Hoàn Kiếm', 'city' => 'Hà Nội', 'lat' => 21.0285, 'lng' => 105.8542],
        ['name' => 'Hai Bà Trưng', 'city' => 'Hà Nội', 'lat' => 21.0097, 'lng' => 105.8479],
        ['name' => 'Đống Đa', 'city' => 'Hà Nội', 'lat' => 21.0182, 'lng' => 105.8270],
        ['name' => 'Tây Hồ', 'city' => 'Hà Nội', 'lat' => 21.0702, 'lng' => 105.8195],
        ['name' => 'Cầu Giấy', 'city' => 'Hà Nội', 'lat' => 21.0333, 'lng' => 105.7941],
        ['name' => 'Thanh Xuân', 'city' => 'Hà Nội', 'lat' => 20.9952, 'lng' => 105.8041],
        ['name' => 'Hoàng Mai', 'city' => 'Hà Nội', 'lat' => 20.9776, 'lng' => 105.8516],
        ['name' => 'Long Biên', 'city' => 'Hà Nội', 'lat' => 21.0364, 'lng' => 105.8938],
        ['name' => 'Nam Từ Liêm', 'city' => 'Hà Nội', 'lat' => 21.0147, 'lng' => 105.7543],
        ['name' => 'Bắc Từ Liêm', 'city' => 'Hà Nội', 'lat' => 21.0715, 'lng' => 105.7638],
        ['name' => 'Hà Đông', 'city' => 'Hà Nội', 'lat' => 20.9719, 'lng' => 105.7784],

        // Hanoi suburban districts
        ['name' => 'Sơn Tây', 'city' => 'Hà Nội', 'lat' => 21.1390, 'lng' => 105.5066],
        ['name' => 'Đông Anh', 'city' => 'Hà Nội', 'lat' => 21.1378, 'lng' => 105.8465],
        ['name' => 'Gia Lâm', 'city' => 'Hà Nội', 'lat' => 21.0228, 'lng' => 105.9731],
        ['name' => 'Sóc Sơn', 'city' => 'Hà Nội', 'lat' => 21.2544, 'lng' => 105.8400],
        ['name' => 'Thanh Trì', 'city' => 'Hà Nội', 'lat' => 20.9486, 'lng' => 105.8731],
        ['name' => 'Thường Tín', 'city' => 'Hà Nội', 'lat' => 20.8667, 'lng' => 105.8667],
        ['name' => 'Hoài Đức', 'city' => 'Hà Nội', 'lat' => 21.0242, 'lng' => 105.6872],
        ['name' => 'Mê Linh', 'city' => 'Hà Nội', 'lat' => 21.1752, 'lng' => 105.7169],
        ['name' => 'Phúc Thọ', 'city' => 'Hà Nội', 'lat' => 21.0900, 'lng' => 105.5511],
        ['name' => 'Quốc Oai', 'city' => 'Hà Nội', 'lat' => 21.0169, 'lng' => 105.6283],
        ['name' => 'Chương Mỹ', 'city' => 'Hà Nội', 'lat' => 20.8833, 'lng' => 105.6500],
        ['name' => 'Đan Phượng', 'city' => 'Hà Nội', 'lat' => 21.0772, 'lng' => 105.6414],
        ['name' => 'Ba Vì', 'city' => 'Hà Nội', 'lat' => 21.2333, 'lng' => 105.3833],
        ['name' => 'Thạch Thất', 'city' => 'Hà Nội', 'lat' => 21.0833, 'lng' => 105.5500],
        ['name' => 'Phú Xuyên', 'city' => 'Hà Nội', 'lat' => 20.7500, 'lng' => 105.9000],
        ['name' => 'Mỹ Đức', 'city' => 'Hà Nội', 'lat' => 20.7333, 'lng' => 105.7667],
        ['name' => 'Ứng Hòa', 'city' => 'Hà Nội', 'lat' => 20.7500, 'lng' => 105.7500],
        ['name' => 'Thanh Oai', 'city' => 'Hà Nội', 'lat' => 20.8500, 'lng' => 105.7833],
    ];

    $stmt = $db->prepare("
        UPDATE districts
        SET latitude = ?, longitude = ?
        WHERE name = ? AND city_name = ?
    ");

    $updated = 0;
    $notFound = [];

    foreach ($districtCoordinates as $district) {
        $stmt->execute([
            $district['lat'],
            $district['lng'],
            $district['name'],
            $district['city']
        ]);

        if ($stmt->rowCount() > 0) {
            $updated++;
            echo "   <span class='success'>✓</span> Updated: {$district['name']}, {$district['city']}\n";
        } else {
            $notFound[] = "{$district['name']}, {$district['city']}";
        }
    }

    echo "\n";
    echo "✅ <span class='success'>Migration completed!</span>\n";
    echo "   Updated: <span class='success'>{$updated}</span> districts\n\n";

    if (!empty($notFound)) {
        echo "⚠️  <span class='warning'>Districts not found in database:</span>\n";
        foreach ($notFound as $name) {
            echo "   - {$name}\n";
        }
        echo "\n";
    }

    // Show summary
    echo "📊 <span class='info'>Database Summary:</span>\n";

    $stmt = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 ELSE 0 END) as with_coords,
            SUM(CASE WHEN latitude IS NULL OR longitude IS NULL THEN 1 ELSE 0 END) as without_coords
        FROM districts
    ");
    $summary = $stmt->fetch();

    echo "   Total districts: {$summary['total']}\n";
    echo "   <span class='success'>With coordinates: {$summary['with_coords']}</span>\n";

    if ($summary['without_coords'] > 0) {
        echo "   <span class='warning'>Without coordinates: {$summary['without_coords']}</span>\n\n";

        echo "📋 <span class='warning'>Districts still needing coordinates:</span>\n";
        $stmt = $db->query("
            SELECT name, city_name
            FROM districts
            WHERE latitude IS NULL OR longitude IS NULL
            ORDER BY city_name, name
            LIMIT 20
        ");
        $missing = $stmt->fetchAll();
        foreach ($missing as $d) {
            echo "   - {$d['name']}, {$d['city_name']}\n";
        }
    } else {
        echo "   <span class='success'>✅ All districts have coordinates!</span>\n";
    }

    echo "\n🎉 <span class='success'>Done! Distance calculations should now work.</span>\n";

} catch (PDOException $e) {
    echo "\n❌ <span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

?></div>

        <p style="margin-top: 20px; color: #86868b;">
            💡 You can now go to the swipe page to see distances calculated correctly.
        </p>
    </div>
</body>
</html>

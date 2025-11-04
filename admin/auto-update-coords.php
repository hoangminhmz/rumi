<?php
/**
 * RUMI - Admin: Auto-Update District Coordinates
 * Automatically reads district names from DB and updates with correct coordinates
 */

require_once __DIR__ . '/../config/database.php';

// Simple auth
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
    <title>Auto-Update Coordinates - RUMI Admin</title>
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
        .log {
            background: #1d1d1f;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
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
        <h1>🔄 Auto-Update District Coordinates</h1>
        <p style="color: #86868b; margin-bottom: 30px;">Reading DB district names and updating coordinates</p>

        <div class="log"><?php

try {
    $db = getDB();

    echo "🚀 <span class='info'>Starting auto-update...</span>\n\n";

    // Step 1: Get all districts from DB
    echo "📊 <span class='info'>Reading districts from database...</span>\n";
    $stmt = $db->query("SELECT id, name, city_name FROM districts ORDER BY city_name, name");
    $dbDistricts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found <span class='success'>" . count($dbDistricts) . "</span> districts in database\n\n";

    // Step 2: Prepare coordinate mappings using similar matching
    echo "📍 <span class='info'>Preparing coordinate mappings...</span>\n\n";

    // Coordinate data (using normalized names for matching)
    $coordsMap = [
        // Hanoi districts - normalize without diacritics
        'ba dinh|ha noi' => ['lat' => 21.0341, 'lng' => 105.8195],
        'hoan kiem|ha noi' => ['lat' => 21.0285, 'lng' => 105.8542],
        'hai ba trung|ha noi' => ['lat' => 21.0097, 'lng' => 105.8479],
        'dong da|ha noi' => ['lat' => 21.0182, 'lng' => 105.8270],
        'tay ho|ha noi' => ['lat' => 21.0702, 'lng' => 105.8195],
        'cau giay|ha noi' => ['lat' => 21.0333, 'lng' => 105.7941],
        'thanh xuan|ha noi' => ['lat' => 20.9952, 'lng' => 105.8041],
        'hoang mai|ha noi' => ['lat' => 20.9776, 'lng' => 105.8516],
        'long bien|ha noi' => ['lat' => 21.0364, 'lng' => 105.8938],
        'nam tu liem|ha noi' => ['lat' => 21.0147, 'lng' => 105.7543],
        'bac tu liem|ha noi' => ['lat' => 21.0715, 'lng' => 105.7638],
        'ha dong|ha noi' => ['lat' => 20.9719, 'lng' => 105.7784],
        'son tay|ha noi' => ['lat' => 21.1390, 'lng' => 105.5066],
        'dong anh|ha noi' => ['lat' => 21.1378, 'lng' => 105.8465],
        'gia lam|ha noi' => ['lat' => 21.0228, 'lng' => 105.9731],
        'soc son|ha noi' => ['lat' => 21.2544, 'lng' => 105.8400],
        'thanh tri|ha noi' => ['lat' => 20.9486, 'lng' => 105.8731],
        'thuong tin|ha noi' => ['lat' => 20.8667, 'lng' => 105.8667],
        'hoai duc|ha noi' => ['lat' => 21.0242, 'lng' => 105.6872],
        'me linh|ha noi' => ['lat' => 21.1752, 'lng' => 105.7169],
        'phuc tho|ha noi' => ['lat' => 21.0900, 'lng' => 105.5511],
        'quoc oai|ha noi' => ['lat' => 21.0169, 'lng' => 105.6283],
        'chuong my|ha noi' => ['lat' => 20.8833, 'lng' => 105.6500],
        'dan phuong|ha noi' => ['lat' => 21.0772, 'lng' => 105.6414],
        'ba vi|ha noi' => ['lat' => 21.2333, 'lng' => 105.3833],
        'thach that|ha noi' => ['lat' => 21.0833, 'lng' => 105.5500],
        'phu xuyen|ha noi' => ['lat' => 20.7500, 'lng' => 105.9000],
        'my duc|ha noi' => ['lat' => 20.7333, 'lng' => 105.7667],
        'ung hoa|ha noi' => ['lat' => 20.7500, 'lng' => 105.7500],
        'thanh oai|ha noi' => ['lat' => 20.8500, 'lng' => 105.7833],

        // Da Nang
        'cam le|da nang' => ['lat' => 16.0295, 'lng' => 108.2022],
        'hai chau|da nang' => ['lat' => 16.0544, 'lng' => 108.2217],
        'lien chieu|da nang' => ['lat' => 16.0647, 'lng' => 108.1508],
        'ngu hanh son|da nang' => ['lat' => 16.0011, 'lng' => 108.2619],
        'son tra|da nang' => ['lat' => 16.0893, 'lng' => 108.2436],
        'thanh khe|da nang' => ['lat' => 16.0617, 'lng' => 108.1974],

        // HCMC
        'quan 1|tp.hcm' => ['lat' => 10.7756, 'lng' => 106.7019],
        'binh thanh|tp.hcm' => ['lat' => 10.8083, 'lng' => 106.7139],
        'go vap|tp.hcm' => ['lat' => 10.8383, 'lng' => 106.6717],
    ];

    // Helper function to normalize Vietnamese text for matching
    function normalizeText($text) {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Remove Vietnamese diacritics
        $text = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $text);
        $text = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $text);
        $text = preg_replace('/[ìíịỉĩ]/u', 'i', $text);
        $text = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $text);
        $text = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $text);
        $text = preg_replace('/[ỳýỵỷỹ]/u', 'y', $text);
        $text = preg_replace('/đ/u', 'd', $text);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    // Step 3: Update each district
    $updated = 0;
    $notFound = [];

    $updateStmt = $db->prepare("UPDATE districts SET latitude = ?, longitude = ? WHERE id = ?");

    foreach ($dbDistricts as $district) {
        $normalizedKey = normalizeText($district['name']) . '|' . normalizeText($district['city_name']);

        if (isset($coordsMap[$normalizedKey])) {
            $coords = $coordsMap[$normalizedKey];
            $updateStmt->execute([$coords['lat'], $coords['lng'], $district['id']]);
            $updated++;
            echo "   <span class='success'>✓</span> Updated: {$district['name']}, {$district['city_name']} ({$coords['lat']}, {$coords['lng']})\n";
        } else {
            $notFound[] = "{$district['name']}, {$district['city_name']}";
        }
    }

    echo "\n";
    echo "✅ <span class='success'>Update completed!</span>\n";
    echo "   Updated: <span class='success'>{$updated}</span> districts\n";
    echo "   Not found: <span class='warning'>" . count($notFound) . "</span> districts\n\n";

    if (!empty($notFound)) {
        echo "⚠️  <span class='warning'>Districts without coordinates (will use fallback):</span>\n";
        foreach ($notFound as $name) {
            echo "   - {$name}\n";
        }
        echo "\n";
    }

    // Summary
    echo "📊 <span class='info'>Final Summary:</span>\n";
    $stmt = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 ELSE 0 END) as with_coords
        FROM districts
    ");
    $summary = $stmt->fetch();

    echo "   Total: {$summary['total']}\n";
    echo "   <span class='success'>With coordinates: {$summary['with_coords']}</span>\n";
    echo "   Coverage: <span class='success'>" . round(($summary['with_coords'] / $summary['total']) * 100, 1) . "%</span>\n";

    echo "\n🎉 <span class='success'>Done! Distance calculations should now work.</span>\n";
    echo "\n💡 Go to the swipe page to test distance display.\n";

} catch (PDOException $e) {
    echo "\n❌ <span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

?></div>
    </div>
</body>
</html>

<?php
/**
 * RUMI - Migration: Add District Coordinates
 * Adds latitude/longitude columns to districts table and populates Hanoi districts
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    echo "🚀 Starting district coordinates migration...\n\n";

    // Step 1: Add latitude and longitude columns
    echo "📊 Adding latitude and longitude columns...\n";
    $db->exec("
        ALTER TABLE districts
        ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL,
        ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL
    ");
    echo "✅ Columns added successfully\n\n";

    // Step 2: Populate coordinates for Hanoi districts
    echo "📍 Populating coordinates for Hanoi districts...\n";

    // Hanoi district coordinates (approximate centers)
    $districtCoordinates = [
        // Hanoi districts
        'Ba Đình' => ['lat' => 21.0341, 'lng' => 105.8195],
        'Hoàn Kiếm' => ['lat' => 21.0285, 'lng' => 105.8542],
        'Hai Bà Trưng' => ['lat' => 21.0097, 'lng' => 105.8479],
        'Đống Đa' => ['lat' => 21.0182, 'lng' => 105.8270],
        'Tây Hồ' => ['lat' => 21.0702, 'lng' => 105.8195],
        'Cầu Giấy' => ['lat' => 21.0333, 'lng' => 105.7941],
        'Thanh Xuân' => ['lat' => 20.9952, 'lng' => 105.8041],
        'Hoàng Mai' => ['lat' => 20.9776, 'lng' => 105.8516],
        'Long Biên' => ['lat' => 21.0364, 'lng' => 105.8938],
        'Nam Từ Liêm' => ['lat' => 21.0147, 'lng' => 105.7543],
        'Bắc Từ Liêm' => ['lat' => 21.0715, 'lng' => 105.7638],
        'Hà Đông' => ['lat' => 20.9719, 'lng' => 105.7784],

        // Hanoi suburban districts
        'Sơn Tây' => ['lat' => 21.1390, 'lng' => 105.5066],
        'Đông Anh' => ['lat' => 21.1378, 'lng' => 105.8465],
        'Gia Lâm' => ['lat' => 21.0228, 'lng' => 105.9731],
        'Sóc Sơn' => ['lat' => 21.2544, 'lng' => 105.8400],
        'Thanh Trì' => ['lat' => 20.9486, 'lng' => 105.8731],
        'Thường Tín' => ['lat' => 20.8667, 'lng' => 105.8667],
        'Hoài Đức' => ['lat' => 21.0242, 'lng' => 105.6872],
        'Mê Linh' => ['lat' => 21.1752, 'lng' => 105.7169],
        'Phúc Thọ' => ['lat' => 21.0900, 'lng' => 105.5511],
        'Quốc Oai' => ['lat' => 21.0169, 'lng' => 105.6283],
        'Chương Mỹ' => ['lat' => 20.8833, 'lng' => 105.6500],
        'Đan Phượng' => ['lat' => 21.0772, 'lng' => 105.6414],
        'Ba Vì' => ['lat' => 21.2333, 'lng' => 105.3833],
        'Thạch Thất' => ['lat' => 21.0833, 'lng' => 105.5500],
        'Phú Xuyên' => ['lat' => 20.7500, 'lng' => 105.9000],
        'Mỹ Đức' => ['lat' => 20.7333, 'lng' => 105.7667],
        'Ứng Hòa' => ['lat' => 20.7500, 'lng' => 105.7500],
        'Thanh Oai' => ['lat' => 20.8500, 'lng' => 105.7833],

        // Ho Chi Minh City districts
        'Quận 1' => ['lat' => 10.7756, 'lng' => 106.7019],
        'Quận 2' => ['lat' => 10.7882, 'lng' => 106.7448],
        'Quận 3' => ['lat' => 10.7860, 'lng' => 106.6917],
        'Quận 4' => ['lat' => 10.7575, 'lng' => 106.7054],
        'Quận 5' => ['lat' => 10.7563, 'lng' => 106.6668],
        'Quận 6' => ['lat' => 10.7477, 'lng' => 106.6374],
        'Quận 7' => ['lat' => 10.7336, 'lng' => 106.7219],
        'Quận 8' => ['lat' => 10.7383, 'lng' => 106.6762],
        'Quận 9' => ['lat' => 10.8512, 'lng' => 106.7897],
        'Quận 10' => ['lat' => 10.7726, 'lng' => 106.6696],
        'Quận 11' => ['lat' => 10.7629, 'lng' => 106.6509],
        'Quận 12' => ['lat' => 10.8663, 'lng' => 106.6670],
        'Bình Thạnh' => ['lat' => 10.8083, 'lng' => 106.7139],
        'Tân Bình' => ['lat' => 10.7999, 'lng' => 106.6530],
        'Tân Phú' => ['lat' => 10.7872, 'lng' => 106.6284],
        'Phú Nhuận' => ['lat' => 10.7976, 'lng' => 106.6830],
        'Gò Vấp' => ['lat' => 10.8383, 'lng' => 106.6717],
        'Bình Tân' => ['lat' => 10.7694, 'lng' => 106.6053],
        'Thủ Đức' => ['lat' => 10.8524, 'lng' => 106.7595],
    ];

    $stmt = $db->prepare("
        UPDATE districts
        SET latitude = ?, longitude = ?
        WHERE name = ? AND city_name = ?
    ");

    $updated = 0;
    $notFound = [];

    foreach ($districtCoordinates as $districtName => $coords) {
        // Try Hanoi first
        $stmt->execute([$coords['lat'], $coords['lng'], $districtName, 'Hà Nội']);
        if ($stmt->rowCount() > 0) {
            $updated++;
            echo "  ✓ Updated: {$districtName}, Hà Nội\n";
        }
        // Try HCMC for Quận districts
        elseif (strpos($districtName, 'Quận') === 0 || in_array($districtName, ['Bình Thạnh', 'Tân Bình', 'Tân Phú', 'Phú Nhuận', 'Gò Vấp', 'Bình Tân', 'Thủ Đức'])) {
            $stmt->execute([$coords['lat'], $coords['lng'], $districtName, 'Hồ Chí Minh']);
            if ($stmt->rowCount() > 0) {
                $updated++;
                echo "  ✓ Updated: {$districtName}, Hồ Chí Minh\n";
            } else {
                $notFound[] = $districtName;
            }
        } else {
            $notFound[] = $districtName;
        }
    }

    echo "\n✅ Migration completed!\n";
    echo "   Updated: {$updated} districts\n";

    if (!empty($notFound)) {
        echo "\n⚠️  Not found in database:\n";
        foreach ($notFound as $name) {
            echo "   - {$name}\n";
        }
    }

    // Show districts without coordinates
    echo "\n📋 Checking for districts without coordinates...\n";
    $stmt = $db->query("
        SELECT id, name, city_name
        FROM districts
        WHERE latitude IS NULL OR longitude IS NULL
        ORDER BY city_name, name
    ");
    $missingCoords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($missingCoords) > 0) {
        echo "   ⚠️  {" . count($missingCoords) . "} districts still need coordinates:\n";
        foreach ($missingCoords as $district) {
            echo "      - {$district['name']}, {$district['city_name']}\n";
        }
        echo "\n   💡 These districts will use district-to-district distance fallback.\n";
    } else {
        echo "   ✅ All districts have coordinates!\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Done!\n";

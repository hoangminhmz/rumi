<?php
/**
 * RUN MIGRATION MANUAL - Từng bước
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';

echo "<h2>🚀 Manual Migration Runner</h2>";

$db = getDB();
$errors = [];
$success = [];

// Step 1: ALTER users table
echo "<h3>Step 1: Alter USERS table</h3>";

$userAlters = [
    "ALTER TABLE users MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first') DEFAULT 'find_roommate_first'",
    "ALTER TABLE users ADD COLUMN sleep_schedule ENUM('early_bird', 'night_owl', 'flexible') AFTER preferences",
    "ALTER TABLE users ADD COLUMN work_schedule ENUM('office', 'shift', 'wfh', 'student') AFTER sleep_schedule",
    "ALTER TABLE users ADD COLUMN drinking ENUM('no', 'social', 'frequent') AFTER work_schedule",
    "ALTER TABLE users ADD COLUMN guests_policy ENUM('no_guests', 'occasional', 'frequent') AFTER drinking",
    "ALTER TABLE users ADD COLUMN move_in_date DATE AFTER guests_policy",
    "ALTER TABLE users ADD COLUMN stay_duration ENUM('1month', '3months', '6months', '1year_plus') AFTER move_in_date",
    "ALTER TABLE users ADD COLUMN occupation VARCHAR(100) AFTER stay_duration",
    "ALTER TABLE users ADD COLUMN matching_stage ENUM('finding_initial', 'finding_secondary', 'completed') DEFAULT 'finding_initial' AFTER occupation",
    "ALTER TABLE users ADD COLUMN verification_status ENUM('unverified', 'pending', 'verified') DEFAULT 'unverified' AFTER matching_stage",
    "ALTER TABLE users ADD COLUMN facebook_url VARCHAR(255) AFTER verification_status",
    "ALTER TABLE users ADD COLUMN linkedin_url VARCHAR(255) AFTER facebook_url",
    "ALTER TABLE users ADD COLUMN id_verified BOOLEAN DEFAULT 0 AFTER linkedin_url"
];

foreach ($userAlters as $i => $sql) {
    try {
        $db->exec($sql);
        echo "<div style='color: green;'>✓ Query " . ($i + 1) . " success</div>";
        $success[] = "Users: Query " . ($i + 1);
    } catch (PDOException $e) {
        // Ignore "Duplicate column" errors
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div style='color: orange;'>⚠ Query " . ($i + 1) . " skipped (column exists)</div>";
        } else {
            echo "<div style='color: red;'>✗ Query " . ($i + 1) . " failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors[] = "Users Query " . ($i + 1) . ": " . $e->getMessage();
        }
    }
}

// Step 2: ALTER rooms table
echo "<h3>Step 2: Alter ROOMS table</h3>";

$roomAlters = [
    "ALTER TABLE rooms ADD COLUMN ward VARCHAR(100) AFTER address",
    "ALTER TABLE rooms ADD COLUMN latitude DECIMAL(10, 8) AFTER ward",
    "ALTER TABLE rooms ADD COLUMN longitude DECIMAL(11, 8) AFTER latitude",
    "ALTER TABLE rooms ADD COLUMN room_type ENUM('apartment', 'house', 'mini_apartment', 'villa') AFTER longitude",
    "ALTER TABLE rooms ADD COLUMN geocoded BOOLEAN DEFAULT 0 AFTER room_type"
];

foreach ($roomAlters as $i => $sql) {
    try {
        $db->exec($sql);
        echo "<div style='color: green;'>✓ Query " . ($i + 1) . " success</div>";
        $success[] = "Rooms: Query " . ($i + 1);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div style='color: orange;'>⚠ Query " . ($i + 1) . " skipped (column exists)</div>";
        } else {
            echo "<div style='color: red;'>✗ Query " . ($i + 1) . " failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors[] = "Rooms Query " . ($i + 1) . ": " . $e->getMessage();
        }
    }
}

// Step 3: CREATE amenities_list table
echo "<h3>Step 3: Create AMENITIES_LIST table</h3>";

$createAmenities = "CREATE TABLE IF NOT EXISTS amenities_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name_vi VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($createAmenities);
    echo "<div style='color: green;'>✓ Table created</div>";
    $success[] = "amenities_list table";
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ Failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    $errors[] = "Create amenities_list: " . $e->getMessage();
}

// Step 4: CREATE preferences_list table
echo "<h3>Step 4: Create PREFERENCES_LIST table</h3>";

$createPreferences = "CREATE TABLE IF NOT EXISTS preferences_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name_vi VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    weight INT DEFAULT 0,
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($createPreferences);
    echo "<div style='color: green;'>✓ Table created</div>";
    $success[] = "preferences_list table";
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ Failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    $errors[] = "Create preferences_list: " . $e->getMessage();
}

// Step 5: INSERT amenities data
echo "<h3>Step 5: Insert AMENITIES data</h3>";

$amenitiesData = [
    ['wifi', 'Wifi', 'Wifi', '📶', 1],
    ['ac', 'Điều hòa', 'Air Conditioning', '❄️', 2],
    ['kitchen', 'Bếp', 'Kitchen', '🍳', 3],
    ['parking', 'Chỗ đỗ xe', 'Parking', '🅿️', 4],
    ['laundry', 'Máy giặt', 'Washing Machine', '🧺', 5],
    ['furniture', 'Nội thất', 'Furniture', '🛋️', 6],
    ['elevator', 'Thang máy', 'Elevator', '🛗', 7],
    ['security', 'An ninh', 'Security', '🔒', 8],
    ['balcony', 'Ban công', 'Balcony', '🌿', 9],
    ['gym', 'Phòng gym', 'Gym', '💪', 10],
    ['pool', 'Hồ bơi', 'Swimming Pool', '🏊', 11],
    ['pet_friendly', 'Cho phép thú cưng', 'Pet Friendly', '🐕', 12]
];

$stmt = $db->prepare("INSERT IGNORE INTO amenities_list (code, name_vi, name_en, icon, sort_order) VALUES (?, ?, ?, ?, ?)");

$inserted = 0;
foreach ($amenitiesData as $data) {
    try {
        $stmt->execute($data);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
    } catch (PDOException $e) {
        // Skip duplicates
    }
}

echo "<div style='color: green;'>✓ Inserted $inserted amenities</div>";

// Step 6: INSERT preferences data
echo "<h3>Step 6: Insert PREFERENCES data</h3>";

$preferencesData = [
    ['cleanliness', 'Sạch sẽ', 'Cleanliness', '✨', 25, 'lifestyle'],
    ['noise_tolerance', 'Độ ồn', 'Noise Tolerance', '🔊', 25, 'lifestyle'],
    ['sleep_schedule', 'Lịch ngủ', 'Sleep Schedule', '😴', 20, 'lifestyle'],
    ['smoking', 'Hút thuốc', 'Smoking', '🚬', 15, 'lifestyle'],
    ['drinking', 'Uống rượu', 'Drinking', '🍺', 10, 'lifestyle'],
    ['guests_policy', 'Chính sách khách', 'Guests Policy', '👥', 5, 'lifestyle'],
    ['budget', 'Ngân sách', 'Budget', '💰', 30, 'financial'],
    ['location', 'Vị trí', 'Location', '📍', 25, 'location']
];

$stmt = $db->prepare("INSERT IGNORE INTO preferences_list (code, name_vi, name_en, icon, weight, category) VALUES (?, ?, ?, ?, ?, ?)");

$inserted = 0;
foreach ($preferencesData as $data) {
    try {
        $stmt->execute($data);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
    } catch (PDOException $e) {
        // Skip duplicates
    }
}

echo "<div style='color: green;'>✓ Inserted $inserted preferences</div>";

// Summary
echo "<h2>📊 Summary</h2>";
echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
echo "<h3>✓ Success: " . count($success) . " operations</h3>";
echo "</div>";

if (count($errors) > 0) {
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 20px 0;'>";
    echo "<h3>✗ Errors: " . count($errors) . "</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Run test again: <a href='../../test4_tables.php'>test4_tables.php</a></li>";
echo "<li>All ✓ green? Test: <a href='../../test5_models.php'>test5_models.php</a></li>";
echo "<li>Finally test: <a href='../../pages/swipe.php'>swipe.php</a></li>";
echo "</ol>";
?>

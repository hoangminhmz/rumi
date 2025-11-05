<?php
/**
 * RUMI - Create Dummy Data for Testing
 * Creates fake users, rooms, and swipes to test matching functionality
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$db = getDB();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Create Dummy Data</title>";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f7; }
    .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h1 { color: #667eea; margin-bottom: 20px; }
    .success { background: #d1fae5; color: #065f46; padding: 10px 15px; border-radius: 6px; margin: 10px 0; }
    .error { background: #fee2e2; color: #991b1b; padding: 10px 15px; border-radius: 6px; margin: 10px 0; }
    .info { background: #e0e7ff; color: #3730a3; padding: 10px 15px; border-radius: 6px; margin: 10px 0; }
    .section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px; }
    .section-title { font-size: 1.2rem; font-weight: 700; color: #111827; margin-bottom: 15px; }
    pre { background: #1f2937; color: #10b981; padding: 15px; border-radius: 6px; overflow-x: auto; }
</style></head><body><div class='container'>";

echo "<h1>🏠 RUMI Dummy Data Generator</h1>";

// Districts for HCM
$districts = [
    1 => 'Quận 1',
    3 => 'Quận 3',
    7 => 'Quận 7',
    10 => 'Quận 10',
    'Bình Thạnh' => 'Quận Bình Thạnh',
    'Phú Nhuận' => 'Quận Phú Nhuận'
];

// Get district IDs from database
$districtIds = [];
foreach ($districts as $name) {
    $stmt = $db->prepare("SELECT id FROM districts WHERE name LIKE ? AND city_name = 'TP.HCM' LIMIT 1");
    $stmt->execute(['%' . $name . '%']);
    $result = $stmt->fetch();
    if ($result) {
        $districtIds[] = $result['id'];
    }
}

if (empty($districtIds)) {
    $districtIds = [1, 2, 3, 4, 5]; // fallback
}

echo "<div class='info'>Found " . count($districtIds) . " districts in database</div>";

// Dummy user data templates
$firstNames = ['Minh', 'Anh', 'Hùng', 'Linh', 'Phương', 'Tuấn', 'Lan', 'Dũng', 'Hoa', 'Khoa', 'Mai', 'Nam', 'Nga', 'Quân', 'Thu', 'Trang', 'Việt', 'Hà', 'Khánh', 'Long'];
$lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Phan', 'Vũ', 'Đặng', 'Bùi', 'Đỗ'];

$occupations = ['Student', 'Software Engineer', 'Designer', 'Marketing', 'Teacher', 'Doctor', 'Accountant', 'Freelancer'];
$bios = [
    'Tìm bạn cùng phòng hợp gu, thích sạch sẽ và vui vẻ',
    'Sinh viên năm 3, thích xem phim và đọc sách',
    'Nhân viên văn phòng, làm việc từ 9-5',
    'Freelancer, thời gian linh hoạt',
    'Thích nấu ăn và chia sẻ không gian sống thoải mái',
    'Tìm roommate để share chi phí và có không gian riêng',
];

echo "<div class='section'>";
echo "<div class='section-title'>Creating Dummy Users...</div>";

$createdUsers = [];
$numUsers = 20;

for ($i = 1; $i <= $numUsers; $i++) {
    $name = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
    $gender = ['male', 'female', 'other'][array_rand(['male', 'female', 'other'])];
    $age = rand(20, 35);
    $districtId = $districtIds[array_rand($districtIds)];
    $phone = '09' . rand(10000000, 99999999);

    // Lifestyle preferences
    $sleepSchedule = ['early_bird', 'night_owl', 'flexible'][array_rand(['early_bird', 'night_owl', 'flexible'])];
    $workSchedule = ['office', 'shift', 'wfh', 'student'][array_rand(['office', 'shift', 'wfh', 'student'])];
    $drinking = ['no', 'social', 'frequent'][array_rand(['no', 'social', 'frequent'])];
    $guestsPolicy = ['no_guests', 'occasional', 'frequent'][array_rand(['no_guests', 'occasional', 'frequent'])];
    $occupation = $occupations[array_rand($occupations)];
    $bio = $bios[array_rand($bios)];

    // Preferences JSON
    $preferences = json_encode([
        'budget_min' => rand(2000000, 4000000),
        'budget_max' => rand(5000000, 8000000),
        'cleanliness' => rand(3, 5),
        'noise_tolerance' => rand(2, 4),
        'smoking' => rand(0, 1) == 1,
        'pets' => rand(0, 1) == 1,
    ]);

    $searchMode = ['find_roommate_first', 'find_room_first'][array_rand(['find_roommate_first', 'find_room_first'])];

    try {
        $stmt = $db->prepare("
            INSERT INTO users (
                zalo_id, name, phone, gender, age, district_id, bio, preferences,
                search_mode, sleep_schedule, work_schedule, drinking, guests_policy,
                occupation, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $zaloId = 'dummy_' . $i . '_' . time();

        $stmt->execute([
            $zaloId, $name, $phone, $gender, $age, $districtId, $bio, $preferences,
            $searchMode, $sleepSchedule, $workSchedule, $drinking, $guestsPolicy,
            $occupation
        ]);

        $userId = $db->lastInsertId();
        $createdUsers[] = $userId;

        echo "<div class='success'>✓ Created user: {$name} (ID: {$userId}, {$age}, {$gender}, {$searchMode})</div>";

    } catch (PDOException $e) {
        echo "<div class='error'>✗ Failed to create user {$name}: " . $e->getMessage() . "</div>";
    }
}

echo "<div class='info'><strong>Created " . count($createdUsers) . " users</strong></div>";
echo "</div>";

// Create Dummy Rooms
echo "<div class='section'>";
echo "<div class='section-title'>Creating Dummy Rooms...</div>";

$roomTitles = [
    'Phòng trọ gần Đại học Bách Khoa',
    'Căn hộ mini quận 3, full nội thất',
    'Phòng đẹp có ban công, yên tĩnh',
    'Nhà nguyên căn cho thuê quận 7',
    'Phòng trọ giá rẻ sinh viên',
    'Căn hộ 2 phòng ngủ Bình Thạnh',
    'Phòng VIP gần chợ Bến Thành',
    'Studio mini đầy đủ tiện nghi',
    'Phòng có gác, WC riêng',
    'Căn hộ dịch vụ cao cấp',
];

$createdRooms = [];
$numRooms = 15;

for ($i = 0; $i < $numRooms; $i++) {
    $ownerId = $createdUsers[array_rand($createdUsers)];
    $title = $roomTitles[array_rand($roomTitles)] . ' #' . ($i + 1);
    $price = rand(2500000, 7000000);
    $area = rand(15, 40);
    $districtId = $districtIds[array_rand($districtIds)];
    $address = rand(1, 500) . ' Đường ' . ['Lê Lợi', 'Nguyễn Huệ', 'Hai Bà Trưng', 'Trần Hưng Đạo'][array_rand(['Lê Lợi', 'Nguyễn Huệ', 'Hai Bà Trưng', 'Trần Hưng Đạo'])];

    $amenities = json_encode([
        'wifi' => rand(0, 1) == 1,
        'ac' => rand(0, 1) == 1,
        'kitchen' => rand(0, 1) == 1,
        'parking' => rand(0, 1) == 1,
        'laundry' => rand(0, 1) == 1,
        'furniture' => rand(0, 1) == 1,
    ]);

    $description = "Phòng trọ sạch sẽ, thoáng mát, đầy đủ tiện nghi. Gần trường học, chợ, siêu thị. An ninh tốt, chủ trọ thân thiện.";

    try {
        $stmt = $db->prepare("
            INSERT INTO rooms (
                owner_id, title, description, price, area, district_id, address,
                amenities, status, payment_status, expired_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'paid', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
        ");

        $stmt->execute([
            $ownerId, $title, $description, $price, $area, $districtId, $address, $amenities
        ]);

        $roomId = $db->lastInsertId();
        $createdRooms[] = $roomId;

        echo "<div class='success'>✓ Created room: {$title} (ID: {$roomId}, " . number_format($price) . " VND)</div>";

    } catch (PDOException $e) {
        echo "<div class='error'>✗ Failed to create room: " . $e->getMessage() . "</div>";
    }
}

echo "<div class='info'><strong>Created " . count($createdRooms) . " rooms</strong></div>";
echo "</div>";

// Create some random swipes
echo "<div class='section'>";
echo "<div class='section-title'>Creating Random Swipes...</div>";

$swipesCreated = 0;

// User swipes (like/pass other users)
foreach ($createdUsers as $userId) {
    // Each user swipes 5-10 random other users
    $numSwipes = rand(5, 10);
    $targetUsers = array_diff($createdUsers, [$userId]);
    shuffle($targetUsers);
    $targetUsers = array_slice($targetUsers, 0, $numSwipes);

    foreach ($targetUsers as $targetUserId) {
        $isLike = rand(0, 10) > 3; // 70% like, 30% pass

        try {
            $stmt = $db->prepare("
                INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_like = is_like
            ");
            $stmt->execute([$userId, $targetUserId, $isLike]);
            $swipesCreated++;
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
}

// Room swipes
foreach ($createdUsers as $userId) {
    $numSwipes = rand(3, 8);
    $targetRooms = $createdRooms;
    shuffle($targetRooms);
    $targetRooms = array_slice($targetRooms, 0, $numSwipes);

    foreach ($targetRooms as $roomId) {
        $isLike = rand(0, 10) > 4; // 60% like, 40% pass

        try {
            $stmt = $db->prepare("
                INSERT INTO room_swipes (user_id, room_id, is_like, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_like = is_like
            ");
            $stmt->execute([$userId, $roomId, $isLike]);
            $swipesCreated++;
        } catch (PDOException $e) {
            // Ignore
        }
    }
}

echo "<div class='info'><strong>Created ~{$swipesCreated} swipes</strong></div>";
echo "</div>";

// Create some matches (find mutual likes and create matches)
echo "<div class='section'>";
echo "<div class='section-title'>Creating Matches...</div>";

$matchesCreated = 0;

// Find mutual likes and create matches
$stmt = $db->query("
    SELECT s1.user_id as user1_id, s1.target_user_id as user2_id
    FROM user_swipes s1
    INNER JOIN user_swipes s2 ON s1.user_id = s2.target_user_id AND s1.target_user_id = s2.user_id
    WHERE s1.is_like = 1 AND s2.is_like = 1
    AND s1.user_id < s1.target_user_id
");

$mutualLikes = $stmt->fetchAll();

foreach ($mutualLikes as $like) {
    try {
        $stmt = $db->prepare("
            INSERT INTO matches (user1_id, user2_id, status, matched_at)
            VALUES (?, ?, 'pending', NOW())
            ON DUPLICATE KEY UPDATE matched_at = matched_at
        ");
        $stmt->execute([$like['user1_id'], $like['user2_id']]);

        if ($db->lastInsertId()) {
            $matchesCreated++;
            echo "<div class='success'>✓ Created match between User {$like['user1_id']} and User {$like['user2_id']}</div>";
        }
    } catch (PDOException $e) {
        // Ignore duplicates
    }
}

echo "<div class='info'><strong>Created {$matchesCreated} matches</strong></div>";
echo "</div>";

// Summary
echo "<div class='section' style='background: #d1fae5;'>";
echo "<div class='section-title' style='color: #065f46;'>✓ Dummy Data Creation Complete!</div>";
echo "<pre>";
echo "Users Created: " . count($createdUsers) . "\n";
echo "Rooms Created: " . count($createdRooms) . "\n";
echo "Swipes Created: ~{$swipesCreated}\n";
echo "Matches Created: {$matchesCreated}\n";
echo "\nYou can now:\n";
echo "1. Login to admin panel to view data\n";
echo "2. Test the app with any dummy user\n";
echo "3. Create manual matches in admin panel\n";
echo "</pre>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Next Steps:</strong><br>";
echo "• Go to <a href='../admin/dashboard.php'>Admin Dashboard</a> to view all data<br>";
echo "• Go to <a href='../admin/matches.php'>Matches Management</a> to create manual matches<br>";
echo "• Test user login with any dummy phone number (format: 09XXXXXXXX)<br>";
echo "</div>";

echo "</div></body></html>";
?>

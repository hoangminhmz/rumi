<?php
/**
 * RUMI - Generate Dummy Room Data
 * Creates 100 test rooms in Hanoi districts
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

$db = getDB();

// Simple authentication
$AUTH_KEY = 'rumi_dummy_2024';

if (!isset($_GET['key']) || $_GET['key'] !== $AUTH_KEY) {
    die("Access denied. Use: ?key={$AUTH_KEY}");
}

// Hanoi districts (from database)
$hanoiDistricts = [
    ['id' => 1, 'name' => 'Ba Đình'],
    ['id' => 2, 'name' => 'Hoàn Kiếm'],
    ['id' => 3, 'name' => 'Tây Hồ'],
    ['id' => 4, 'name' => 'Long Biên'],
    ['id' => 5, 'name' => 'Cầu Giấy'],
    ['id' => 6, 'name' => 'Đống Đa'],
    ['id' => 7, 'name' => 'Hai Bà Trưng'],
    ['id' => 8, 'name' => 'Hoàng Mai'],
    ['id' => 9, 'name' => 'Thanh Xuân'],
    ['id' => 10, 'name' => 'Nam Từ Liêm'],
    ['id' => 11, 'name' => 'Bắc Từ Liêm'],
    ['id' => 12, 'name' => 'Hà Đông'],
];

// Real street names in Hanoi
$streets = [
    'Ba Đình' => ['Nguyễn Trãi', 'Đội Cấn', 'Kim Mã', 'Nguyễn Chí Thanh', 'Hoàng Hoa Thám'],
    'Hoàn Kiếm' => ['Trần Hưng Đạo', 'Hàng Bài', 'Hàng Gai', 'Tràng Tiền', 'Lý Thường Kiệt'],
    'Tây Hồ' => ['Lạc Long Quân', 'Âu Cơ', 'Quảng An', 'Yên Phụ', 'Thụy Khuê'],
    'Long Biên' => ['Nguyễn Văn Cừ', 'Ngọc Lâm', 'Cổ Linh', 'Phúc Lợi', 'Việt Hưng'],
    'Cầu Giấy' => ['Trần Duy Hưng', 'Xuân Thủy', 'Cầu Giấy', 'Hoàng Quốc Việt', 'Phạm Văn Đồng'],
    'Đống Đa' => ['Láng Hạ', 'Xã Đàn', 'Thái Hà', 'Chùa Bộc', 'Ô Chợ Dừa'],
    'Hai Bà Trưng' => ['Bạch Mai', 'Minh Khai', 'Lê Duẩn', 'Trần Khát Chân', 'Thanh Nhàn'],
    'Hoàng Mai' => ['Giải Phóng', 'Tam Trinh', 'Yên Duyên', 'Định Công', 'Trần Phú'],
    'Thanh Xuân' => ['Nguyễn Trãi', 'Khuất Duy Tiến', 'Lê Văn Lương', 'Nguyễn Xiển', 'Hà Đông'],
    'Nam Từ Liêm' => ['Phạm Văn Đồng', 'Trần Cung', 'Mễ Trì', 'Đại Lộ Thăng Long', 'Lê Quang Đạo'],
    'Bắc Từ Liêm' => ['Cổ Nhuế', 'Xuân Đỉnh', 'Phúc Diễn', 'Thụy Phương', 'Liên Mạc'],
    'Hà Đông' => ['Quang Trung', 'Hà Huy Tập', 'Lê Lợi', 'Nguyễn Trãi', 'Tô Hiệu'],
];

// Room title templates
$roomTypes = [
    'Phòng trọ giá rẻ',
    'Phòng đầy đủ tiện nghi',
    'Phòng cho sinh viên',
    'Phòng cao cấp',
    'Căn hộ mini',
    'Phòng đẹp mới xây',
    'Phòng gần chợ',
    'Phòng gần trường',
    'Phòng yên tĩnh',
    'Phòng thoáng mát',
];

// Description templates
$descriptions = [
    'Phòng sạch sẽ, thoáng mát, đầy đủ nội thất. Gần chợ, gần trường, an ninh tốt.',
    'Nhà mới xây, phòng rộng rãi, có ban công. Giờ giấc tự do, không chung chủ.',
    'Khu vực yên tĩnh, an toàn. Phù hợp cho sinh viên và người đi làm.',
    'Phòng đầy đủ tiện nghi: giường, tủ, bàn ghế. Điện nước giá dân.',
    'Gần bến xe bus, thuận tiện di chuyển. Môi trường sống văn minh.',
    'Cho thuê dài hạn, giá ổn định. Chủ nhà dễ tính, thân thiện.',
    'Khu vực nhiều tiện ích: chợ, siêu thị, quán ăn. An ninh 24/7.',
    'Phòng sáng, không bị ẩm ướt. Có chỗ để xe máy miễn phí.',
];

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Generate Dummy Rooms - RUMI</title>
    <style>
        body { font-family: system-ui; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #00D4AA; }
        .success { background: #d4edda; padding: 1rem; border-radius: 8px; color: #155724; margin: 1rem 0; }
        .error { background: #f8d7da; padding: 1rem; border-radius: 8px; color: #721c24; margin: 1rem 0; }
        .info { background: #d1ecf1; padding: 1rem; border-radius: 8px; color: #0c5460; margin: 1rem 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #00D4AA; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 5px; }
        .btn:hover { background: #00B891; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f4f4f4; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1rem 0; }
        .stat { background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #00D4AA; }
        .stat-label { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class='container'>
<h1>🏠 Generate Dummy Rooms - RUMI</h1>
";

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete') {
        // Delete all dummy rooms
        try {
            $stmt = $db->prepare("DELETE FROM rooms WHERE title LIKE 'Phòng trọ%' OR title LIKE 'Căn hộ%' OR title LIKE 'Phòng đầy%' OR title LIKE 'Phòng cho%' OR title LIKE 'Phòng cao%' OR title LIKE 'Phòng đẹp%' OR title LIKE 'Phòng gần%' OR title LIKE 'Phòng yên%' OR title LIKE 'Phòng thoáng%'");
            $stmt->execute();
            $deleted = $stmt->rowCount();

            echo "<div class='success'>✅ Deleted {$deleted} dummy rooms successfully!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }

    if ($_GET['action'] === 'generate') {
        // Check if we have users
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        if ($userCount == 0) {
            echo "<div class='info'>⚠️ No users found. Creating 5 dummy users first...</div>";

            // Create dummy users
            $dummyUsers = [
                ['Nguyễn Văn A', '0901234567', 'male', 25],
                ['Trần Thị B', '0912345678', 'female', 23],
                ['Lê Văn C', '0923456789', 'male', 28],
                ['Phạm Thị D', '0934567890', 'female', 26],
                ['Hoàng Văn E', '0945678901', 'male', 24],
            ];

            foreach ($dummyUsers as $i => $user) {
                $district = rand(1, 12); // Random Hanoi district

                $stmt = $db->prepare("
                    INSERT INTO users (zalo_id, name, phone, gender, age, district_id, bio, preferences, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $prefs = json_encode([
                    'budget_min' => rand(15, 25) * 100000,
                    'budget_max' => rand(30, 50) * 100000,
                    'cleanliness' => rand(3, 5),
                    'noise_tolerance' => rand(2, 4),
                    'smoking' => rand(0, 1) ? false : true,
                    'pets' => rand(0, 1) ? true : false,
                ]);

                $stmt->execute([
                    'dummy_user_' . ($i + 1),
                    $user[0],
                    $user[1],
                    $user[2],
                    $user[3],
                    $district,
                    'Đây là tài khoản test',
                    $prefs
                ]);
            }

            echo "<div class='success'>✅ Created 5 dummy users</div>";
        }

        // Get user IDs for owners
        $userIds = $db->query("SELECT id FROM users LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userIds)) {
            echo "<div class='error'>❌ No users available to be owners!</div>";
            exit;
        }

        echo "<div class='info'>📝 Generating 100 dummy rooms in Hanoi...</div>";

        $created = 0;
        $errors = 0;

        for ($i = 0; $i < 100; $i++) {
            try {
                // Random district
                $district = $hanoiDistricts[array_rand($hanoiDistricts)];
                $districtId = $district['id'];
                $districtName = $district['name'];

                // Random street in that district
                $streetList = $streets[$districtName] ?? ['Đường 1', 'Đường 2'];
                $street = $streetList[array_rand($streetList)];

                // Random house number
                $houseNumber = rand(1, 300);

                // Build address
                $address = $houseNumber . ' ' . $street;

                // Random room type
                $roomType = $roomTypes[array_rand($roomTypes)];
                $title = $roomType . ' ' . $districtName;

                // Random description
                $description = $descriptions[array_rand($descriptions)];

                // Random price (1.5M - 5M)
                $price = rand(15, 50) * 100000;

                // Random area (15-40 m²)
                $area = rand(15, 40);

                // Random amenities
                $amenities = [
                    'wifi' => rand(0, 1) ? true : false,
                    'ac' => rand(0, 1) ? true : false,
                    'kitchen' => rand(0, 1) ? true : false,
                    'parking' => rand(0, 1) ? true : false,
                    'laundry' => rand(0, 1) ? true : false,
                    'furniture' => rand(0, 1) ? true : false,
                ];

                // Random owner
                $ownerId = $userIds[array_rand($userIds)];

                // Insert room
                $stmt = $db->prepare("
                    INSERT INTO rooms (
                        owner_id, title, description, price, area, district_id, address,
                        images, amenities, status, payment_status, payment_date,
                        expired_at, likes_count, views_count, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        '[]', ?, 'active', 'paid', NOW(),
                        DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?, NOW()
                    )
                ");

                $stmt->execute([
                    $ownerId,
                    $title,
                    $description,
                    $price,
                    $area,
                    $districtId,
                    $address,
                    json_encode($amenities),
                    rand(0, 15), // Random likes
                    rand(0, 100)  // Random views
                ]);

                $created++;

            } catch (PDOException $e) {
                $errors++;
                echo "<div class='error'>Error creating room: " . $e->getMessage() . "</div>";
            }
        }

        echo "<div class='success'>
            ✅ Successfully created <strong>{$created}</strong> dummy rooms!
            " . ($errors > 0 ? "<br>⚠️ Errors: {$errors}" : "") . "
        </div>";

        echo "<div class='info'>
            <strong>Next steps:</strong><br>
            1. Go to <a href='admin-geocode.php'>admin-geocode.php</a><br>
            2. Run migration if not done yet<br>
            3. Click 'Geocode Rooms' to add coordinates<br>
            4. Test at <a href='pages/swipe.php?mode=find_room'>swipe.php?mode=find_room</a>
        </div>";
    }
}

// Show current stats
try {
    $totalRooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $activeRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active'")->fetchColumn();
    $hanoiRooms = $db->query("SELECT COUNT(*) FROM rooms r JOIN districts d ON r.district_id = d.id WHERE d.city_name = 'Hà Nội'")->fetchColumn();

    echo "<div class='stats'>
        <div class='stat'>
            <div class='stat-number'>{$totalRooms}</div>
            <div class='stat-label'>Total Rooms</div>
        </div>
        <div class='stat'>
            <div class='stat-number'>{$activeRooms}</div>
            <div class='stat-label'>Active Rooms</div>
        </div>
        <div class='stat'>
            <div class='stat-number'>{$hanoiRooms}</div>
            <div class='stat-label'>Hanoi Rooms</div>
        </div>
    </div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "
<h2>Actions</h2>
<a href='?key={$AUTH_KEY}&action=generate' class='btn' onclick='return confirm(\"Generate 100 dummy rooms in Hanoi?\")'>
    🏗️ Generate 100 Dummy Rooms
</a>
<a href='?key={$AUTH_KEY}&action=delete' class='btn btn-danger' onclick='return confirm(\"Delete all dummy rooms? This cannot be undone!\")'>
    🗑️ Delete All Dummy Rooms
</a>

<h2>Notes</h2>
<ul>
    <li>✅ All rooms will be in <strong>Hanoi</strong> districts (12 quận)</li>
    <li>✅ Real street names used</li>
    <li>✅ Random prices: 1.5M - 5M VND/month</li>
    <li>✅ Random areas: 15-40 m²</li>
    <li>✅ Random amenities (WiFi, AC, Kitchen, etc.)</li>
    <li>✅ Status: 'active', Payment: 'paid'</li>
    <li>✅ Expires in 30 days</li>
    <li>⚠️ No coordinates yet - use admin-geocode.php to add them</li>
</ul>

<h2>Example Addresses Generated</h2>
<pre>";

// Show some example addresses
foreach (array_slice($hanoiDistricts, 0, 3) as $district) {
    $districtName = $district['name'];
    $streetList = $streets[$districtName] ?? ['Đường 1'];
    $street = $streetList[0];
    echo "• " . rand(1, 300) . " {$street}, {$districtName}, Hà Nội\n";
}

echo "</pre>

</div>
</body>
</html>";

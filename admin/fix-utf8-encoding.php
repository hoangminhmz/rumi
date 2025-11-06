<?php
/**
 * RUMI - Fix UTF-8 Encoding
 * Convert database to utf8mb4 and re-seed Vietnamese data
 */

// Set output encoding
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();
$errors = [];
$success = [];

// Step 1: Convert database charset
try {
    echo "<h2>🔄 Step 1: Converting Database to UTF-8mb4...</h2>";

    $sql = file_get_contents(__DIR__ . '/../database/migrations/fix_utf8_charset.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;

        try {
            $db->exec($statement);
            $tableName = '';
            if (preg_match('/ALTER TABLE (\w+)/', $statement, $matches)) {
                $tableName = $matches[1];
                echo "<span style='color: green;'>✓ Converted table: {$tableName}</span><br>";
                $success[] = "Converted table: {$tableName}";
            } else if (preg_match('/ALTER DATABASE (\w+)/', $statement, $matches)) {
                echo "<span style='color: green;'>✓ Set database default charset</span><br>";
                $success[] = "Set database default charset";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            echo "<span style='color: red;'>✗ {$error}</span><br>";
            $errors[] = $error;
        }
    }

    echo "<br>";

} catch (Exception $e) {
    $errors[] = "Charset conversion failed: " . $e->getMessage();
}

// Step 2: Re-seed preferences with correct UTF-8
try {
    echo "<h2>🔄 Step 2: Re-seeding Preferences with UTF-8...</h2>";

    // Set connection charset explicitly
    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $preferences = [
        'sleep_schedule' => [
            'name_vi' => 'Lịch ngủ',
            'name_en' => 'Sleep Schedule',
            'icon' => '😴',
            'weight' => 20,
            'category' => 'lifestyle',
            'field_type' => 'enum',
            'options_config' => json_encode([
                'options' => [
                    ['code' => 'early_bird', 'name_vi' => 'Dậy sớm', 'name_en' => 'Early Bird', 'icon' => '🌅'],
                    ['code' => 'night_owl', 'name_vi' => 'Thức khuya', 'name_en' => 'Night Owl', 'icon' => '🦉'],
                    ['code' => 'flexible', 'name_vi' => 'Linh hoạt', 'name_en' => 'Flexible', 'icon' => '⏰']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'description_vi' => 'Lịch ngủ và thời gian hoạt động của bạn'
        ],
        'work_schedule' => [
            'name_vi' => 'Lịch làm việc',
            'name_en' => 'Work Schedule',
            'icon' => '💼',
            'weight' => 15,
            'category' => 'lifestyle',
            'field_type' => 'enum',
            'options_config' => json_encode([
                'options' => [
                    ['code' => 'office', 'name_vi' => 'Văn phòng (9-5)', 'name_en' => 'Office (9-5)', 'icon' => '🏢'],
                    ['code' => 'shift', 'name_vi' => 'Ca xoay', 'name_en' => 'Shift Work', 'icon' => '🔄'],
                    ['code' => 'wfh', 'name_vi' => 'Làm từ xa', 'name_en' => 'Work from Home', 'icon' => '🏡'],
                    ['code' => 'student', 'name_vi' => 'Sinh viên', 'name_en' => 'Student', 'icon' => '📚']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'description_vi' => 'Lịch làm việc hoặc học tập'
        ],
        'drinking' => [
            'name_vi' => 'Uống rượu',
            'name_en' => 'Drinking',
            'icon' => '🍺',
            'weight' => 10,
            'category' => 'lifestyle',
            'field_type' => 'enum',
            'options_config' => json_encode([
                'options' => [
                    ['code' => 'no', 'name_vi' => 'Không', 'name_en' => 'No', 'icon' => '🚫'],
                    ['code' => 'social', 'name_vi' => 'Xã giao', 'name_en' => 'Social', 'icon' => '🍻'],
                    ['code' => 'frequent', 'name_vi' => 'Thường xuyên', 'name_en' => 'Frequent', 'icon' => '🍷']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'description_vi' => 'Thói quen uống rượu'
        ],
        'guests_policy' => [
            'name_vi' => 'Chính sách khách',
            'name_en' => 'Guests Policy',
            'icon' => '👥',
            'weight' => 5,
            'category' => 'lifestyle',
            'field_type' => 'enum',
            'options_config' => json_encode([
                'options' => [
                    ['code' => 'no_guests', 'name_vi' => 'Không khách', 'name_en' => 'No Guests', 'icon' => '🚫'],
                    ['code' => 'occasional', 'name_vi' => 'Thỉnh thoảng', 'name_en' => 'Occasional', 'icon' => '👤'],
                    ['code' => 'frequent', 'name_vi' => 'Thường xuyên', 'name_en' => 'Frequent', 'icon' => '👥']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'description_vi' => 'Chính sách đón khách về phòng'
        ],
        'cleanliness' => [
            'name_vi' => 'Sạch sẽ',
            'name_en' => 'Cleanliness',
            'icon' => '✨',
            'weight' => 25,
            'category' => 'lifestyle',
            'field_type' => 'scale',
            'description_vi' => 'Mức độ sạch sẽ bạn mong muốn'
        ],
        'noise_tolerance' => [
            'name_vi' => 'Dung nạp tiếng ồn',
            'name_en' => 'Noise Tolerance',
            'icon' => '🔊',
            'weight' => 25,
            'category' => 'lifestyle',
            'field_type' => 'scale',
            'description_vi' => 'Khả năng chịu đựng tiếng ồn'
        ],
        'smoking' => [
            'name_vi' => 'Hút thuốc',
            'name_en' => 'Smoking',
            'icon' => '🚬',
            'weight' => 15,
            'category' => 'lifestyle',
            'field_type' => 'boolean',
            'description_vi' => 'Bạn có hút thuốc không'
        ],
        'pets' => [
            'name_vi' => 'Thú cưng',
            'name_en' => 'Pets',
            'icon' => '🐕',
            'weight' => 10,
            'category' => 'lifestyle',
            'field_type' => 'boolean',
            'description_vi' => 'Bạn có nuôi hoặc thích thú cưng'
        ],
        'budget' => [
            'name_vi' => 'Ngân sách',
            'name_en' => 'Budget',
            'icon' => '💰',
            'weight' => 30,
            'category' => 'financial',
            'field_type' => 'range',
            'description_vi' => 'Ngân sách thuê phòng mỗi tháng (VND)'
        ]
    ];

    foreach ($preferences as $code => $data) {
        $checkStmt = $db->prepare("SELECT id FROM preferences_list WHERE code = ?");
        $checkStmt->execute([$code]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // Update existing preference
            $updateStmt = $db->prepare("
                UPDATE preferences_list
                SET name_vi = ?, name_en = ?, icon = ?, weight = ?, category = ?,
                    field_type = ?, options_config = ?, description_vi = ?
                WHERE code = ?
            ");
            $updateStmt->execute([
                $data['name_vi'],
                $data['name_en'],
                $data['icon'],
                $data['weight'],
                $data['category'],
                $data['field_type'],
                $data['options_config'] ?? null,
                $data['description_vi'],
                $code
            ]);
            echo "<span style='color: green;'>✓ Updated: {$data['name_vi']} ({$code})</span><br>";
            $success[] = "Updated preference: {$code}";
        } else {
            // Insert new preference
            $insertStmt = $db->prepare("
                INSERT INTO preferences_list
                (code, name_vi, name_en, icon, weight, category, field_type, options_config, description_vi, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $insertStmt->execute([
                $code,
                $data['name_vi'],
                $data['name_en'],
                $data['icon'],
                $data['weight'],
                $data['category'],
                $data['field_type'],
                $data['options_config'] ?? null,
                $data['description_vi']
            ]);
            echo "<span style='color: green;'>✓ Inserted: {$data['name_vi']} ({$code})</span><br>";
            $success[] = "Inserted preference: {$code}";
        }
    }

    echo "<br>";

} catch (Exception $e) {
    $errors[] = "Re-seeding failed: " . $e->getMessage();
}

// Step 3: Verify UTF-8 data
try {
    echo "<h2>🔍 Step 3: Verifying UTF-8 Data...</h2>";

    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $stmt = $db->query("SELECT code, name_vi, icon FROM preferences_list ORDER BY code");
    $prefs = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Code</th><th>Vietnamese Name</th><th>Icon</th><th>UTF-8 Check</th></tr>";

    foreach ($prefs as $pref) {
        $isUtf8Valid = mb_check_encoding($pref['name_vi'], 'UTF-8');
        $checkMark = $isUtf8Valid ? '✓' : '✗';
        $color = $isUtf8Valid ? 'green' : 'red';

        echo "<tr>";
        echo "<td><code>{$pref['code']}</code></td>";
        echo "<td>{$pref['name_vi']}</td>";
        echo "<td style='font-size: 1.5rem;'>{$pref['icon']}</td>";
        echo "<td style='color: {$color}; font-weight: bold;'>{$checkMark}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<br>";

} catch (Exception $e) {
    $errors[] = "Verification failed: " . $e->getMessage();
}

// Summary
echo "<hr>";
echo "<h2>📊 Summary</h2>";

if (!empty($success)) {
    echo "<h3 style='color: green;'>✓ Success ({count($success)} operations)</h3>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li>{$msg}</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 style='color: red;'>✗ Errors ({count($errors)} operations)</h3>";
    echo "<ul>";
    foreach ($errors as $msg) {
        echo "<li>{$msg}</li>";
    }
    echo "</ul>";
} else {
    echo "<h3 style='color: green;'>🎉 All operations completed successfully!</h3>";
    echo "<p>Vietnamese characters should now display correctly in phpMyAdmin and on the website.</p>";
}

echo "<br><a href='preferences.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Preferences Management</a>";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix UTF-8 Encoding - RUMI Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            background: #f5f5f7;
        }
        h2 {
            color: #111827;
            margin-top: 2rem;
        }
        table {
            background: white;
            margin: 1rem 0;
        }
        th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
        }
        td {
            padding: 0.75rem;
        }
    </style>
</head>
<body>
</body>
</html>

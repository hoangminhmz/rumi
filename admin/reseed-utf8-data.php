<?php
/**
 * Re-seed UTF-8 Data
 * Run this AFTER fix-charset-simple.php
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();
$db->exec("SET NAMES utf8mb4");

echo "<h1>Re-seed UTF-8 Data</h1>";
echo "<pre>";

// Delete old corrupted data
echo "Cleaning old data...\n";
$db->exec("DELETE FROM preferences_list");
echo "✓ Cleared preferences_list\n\n";

// Insert fresh UTF-8 data
echo "Inserting fresh UTF-8 data...\n";

$preferences = [
    ['sleep_schedule', 'Lịch ngủ', 'Sleep Schedule', '😴', 20, 'lifestyle', 'enum', '{"options":[{"code":"early_bird","name_vi":"Dậy sớm","name_en":"Early Bird","icon":"🌅"},{"code":"night_owl","name_vi":"Thức khuya","name_en":"Night Owl","icon":"🦉"},{"code":"flexible","name_vi":"Linh hoạt","name_en":"Flexible","icon":"⏰"}]}', 'Lịch ngủ và thời gian hoạt động của bạn'],
    ['work_schedule', 'Lịch làm việc', 'Work Schedule', '💼', 15, 'lifestyle', 'enum', '{"options":[{"code":"office","name_vi":"Văn phòng (9-5)","name_en":"Office (9-5)","icon":"🏢"},{"code":"shift","name_vi":"Ca xoay","name_en":"Shift Work","icon":"🔄"},{"code":"wfh","name_vi":"Làm từ xa","name_en":"Work from Home","icon":"🏡"},{"code":"student","name_vi":"Sinh viên","name_en":"Student","icon":"📚"}]}', 'Lịch làm việc hoặc học tập'],
    ['drinking', 'Uống rượu', 'Drinking', '🍺', 10, 'lifestyle', 'enum', '{"options":[{"code":"no","name_vi":"Không","name_en":"No","icon":"🚫"},{"code":"social","name_vi":"Xã giao","name_en":"Social","icon":"🍻"},{"code":"frequent","name_vi":"Thường xuyên","name_en":"Frequent","icon":"🍷"}]}', 'Thói quen uống rượu'],
    ['guests_policy', 'Chính sách khách', 'Guests Policy', '👥', 5, 'lifestyle', 'enum', '{"options":[{"code":"no_guests","name_vi":"Không khách","name_en":"No Guests","icon":"🚫"},{"code":"occasional","name_vi":"Thỉnh thoảng","name_en":"Occasional","icon":"👤"},{"code":"frequent","name_vi":"Thường xuyên","name_en":"Frequent","icon":"👥"}]}', 'Chính sách đón khách về phòng'],
    ['cleanliness', 'Sạch sẽ', 'Cleanliness', '✨', 25, 'lifestyle', 'scale', NULL, 'Mức độ sạch sẽ bạn mong muốn'],
    ['noise_tolerance', 'Dung nạp tiếng ồn', 'Noise Tolerance', '🔊', 25, 'lifestyle', 'scale', NULL, 'Khả năng chịu đựng tiếng ồn'],
    ['smoking', 'Hút thuốc', 'Smoking', '🚬', 15, 'lifestyle', 'boolean', NULL, 'Bạn có hút thuốc không'],
    ['pets', 'Thú cưng', 'Pets', '🐕', 10, 'lifestyle', 'boolean', NULL, 'Bạn có nuôi hoặc thích thú cưng'],
    ['budget', 'Ngân sách', 'Budget', '💰', 30, 'financial', 'range', NULL, 'Ngân sách thuê phòng mỗi tháng (VND)']
];

$stmt = $db->prepare("
    INSERT INTO preferences_list
    (code, name_vi, name_en, icon, weight, category, field_type, options_config, description_vi, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
");

foreach ($preferences as $pref) {
    $stmt->execute($pref);
    echo "✓ Inserted: {$pref[1]} ({$pref[0]})\n";
}

echo "\n✅ Done! Check in phpMyAdmin now.\n";
echo "Look at preferences_list table and check if Vietnamese displays correctly.\n";

echo "</pre>";
echo "<br><a href='preferences.php'>Go to Preferences</a>";
?>

<?php
/**
 * ADMIN: Run Database Migration (SAFE VERSION)
 * This version checks column existence before adding
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

// Only admin can run migrations
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND id = 1");
$stmt->execute([getCurrentUserId()]);
if (!$stmt->fetch()) {
    die("⛔ Access denied. Only admin can run migrations.");
}

$pageTitle = 'Database Migration (Safe)';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <style>
        .migration-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .log-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1.5rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn-run {
            background: #2563eb;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-run:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .status-success { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        .status-info { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1>🗄️ Database Migration (Safe Mode)</h1>
        <p style="color: #6b7280; margin-bottom: 2rem;">
            This checks column existence before adding.
        </p>

        <?php if (!isset($_POST['run'])): ?>
        <form method="POST">
            <button type="submit" name="run" value="1" class="btn-run">
                ▶️ Run Safe Migration
            </button>
        </form>
        <?php else: ?>
        <h3>📋 Migration Log:</h3>
        <div class="log-output">
<?php
ob_start();

try {
    $db = getDB();

    echo "🔄 RUMI Safe Database Migration\n";
    echo str_repeat("=", 50) . "\n\n";

    // Helper function to check if column exists
    function columnExists($db, $table, $column) {
        // Cannot use prepared statement for SHOW COLUMNS, need direct query
        // But we sanitize inputs to prevent SQL injection
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $result && $result->fetch() !== false;
    }

    // Check table exists
    $tables = $db->query("SHOW TABLES LIKE 'preferences_list'")->fetchAll();
    if (empty($tables)) {
        throw new Exception("Table preferences_list not found. Please create schema first.");
    }

    // Get current count
    $count = $db->query("SELECT COUNT(*) FROM preferences_list")->fetchColumn();
    echo "<span class='status-info'>✓ Preferences table has {$count} records</span>\n\n";

    // Migration 1: Add columns one by one with checks
    echo "<span class='status-info'>📦 Phase 1: Adding columns...</span>\n";

    // Column 1: field_type
    if (!columnExists($db, 'preferences_list', 'field_type')) {
        $db->exec("ALTER TABLE preferences_list ADD COLUMN field_type VARCHAR(20) NULL");
        echo "<span class='status-success'>  ✓ Added column: field_type</span>\n";
    } else {
        echo "<span class='status-warning'>  ⚠️  Column field_type already exists</span>\n";
    }

    // Column 2: options_config
    if (!columnExists($db, 'preferences_list', 'options_config')) {
        $db->exec("ALTER TABLE preferences_list ADD COLUMN options_config TEXT NULL");
        echo "<span class='status-success'>  ✓ Added column: options_config</span>\n";
    } else {
        echo "<span class='status-warning'>  ⚠️  Column options_config already exists</span>\n";
    }

    // Column 3: description_vi
    if (!columnExists($db, 'preferences_list', 'description_vi')) {
        $db->exec("ALTER TABLE preferences_list ADD COLUMN description_vi TEXT NULL");
        echo "<span class='status-success'>  ✓ Added column: description_vi</span>\n";
    } else {
        echo "<span class='status-warning'>  ⚠️  Column description_vi already exists</span>\n";
    }

    // Column 4: description_en
    if (!columnExists($db, 'preferences_list', 'description_en')) {
        $db->exec("ALTER TABLE preferences_list ADD COLUMN description_en TEXT NULL");
        echo "<span class='status-success'>  ✓ Added column: description_en</span>\n";
    } else {
        echo "<span class='status-warning'>  ⚠️  Column description_en already exists</span>\n";
    }

    // Set defaults
    $updated = $db->exec("UPDATE preferences_list SET field_type = 'enum' WHERE field_type IS NULL");
    if ($updated > 0) {
        echo "<span class='status-info'>  ✓ Set default field_type for {$updated} rows</span>\n";
    }

    echo "<span class='status-success'>✅ Phase 1 completed</span>\n\n";

    // Migration 2: Seed preference options
    echo "<span class='status-info'>📦 Phase 2: Seeding preference options...</span>\n";

    $preferences = [
        'sleep_schedule' => [
            'field_type' => 'enum',
            'options_config' => '{"options":[{"code":"early_bird","name_vi":"Dậy sớm","name_en":"Early Bird","icon":"🌅"},{"code":"night_owl","name_vi":"Thức khuya","name_en":"Night Owl","icon":"🦉"},{"code":"flexible","name_vi":"Linh hoạt","name_en":"Flexible","icon":"⏰"}]}',
            'description_vi' => 'Lịch ngủ và thời gian hoạt động của bạn'
        ],
        'work_schedule' => [
            'field_type' => 'enum',
            'options_config' => '{"options":[{"code":"office","name_vi":"Văn phòng (9-5)","name_en":"Office (9-5)","icon":"🏢"},{"code":"shift","name_vi":"Ca xoay","name_en":"Shift Work","icon":"🔄"},{"code":"wfh","name_vi":"Làm từ xa","name_en":"Work from Home","icon":"🏡"},{"code":"student","name_vi":"Sinh viên","name_en":"Student","icon":"📚"}]}',
            'description_vi' => 'Lịch làm việc hoặc học tập'
        ],
        'drinking' => [
            'field_type' => 'enum',
            'options_config' => '{"options":[{"code":"no","name_vi":"Không uống","name_en":"No Drinking","icon":"🚫"},{"code":"social","name_vi":"Uống xã giao","name_en":"Social Drinker","icon":"🍺"},{"code":"frequent","name_vi":"Thường xuyên","name_en":"Frequent","icon":"🍻"}]}',
            'description_vi' => 'Thói quen uống rượu bia'
        ],
        'guests_policy' => [
            'field_type' => 'enum',
            'options_config' => '{"options":[{"code":"no_guests","name_vi":"Không khách","name_en":"No Guests","icon":"🚫"},{"code":"occasional","name_vi":"Thỉnh thoảng","name_en":"Occasional OK","icon":"👥"},{"code":"frequent","name_vi":"Chào đón khách","name_en":"Guests Welcome","icon":"🎉"}]}',
            'description_vi' => 'Chính sách đón khách ở nhà'
        ],
        'cleanliness' => [
            'field_type' => 'scale',
            'options_config' => '{"min":1,"max":5,"labels":{"1":{"vi":"Thoải mái","en":"Casual"},"3":{"vi":"Trung bình","en":"Moderate"},"5":{"vi":"Rất sạch","en":"Very Clean"}}}',
            'description_vi' => 'Mức độ sạch sẽ mong muốn (1 = thoải mái, 5 = rất sạch)'
        ],
        'noise_tolerance' => [
            'field_type' => 'scale',
            'options_config' => '{"min":1,"max":5,"labels":{"1":{"vi":"Yên tĩnh","en":"Quiet"},"3":{"vi":"Trung bình","en":"Moderate"},"5":{"vi":"OK với ồn","en":"Tolerant"}}}',
            'description_vi' => 'Mức độ chịu đựng tiếng ồn (1 = cần yên tĩnh, 5 = chấp nhận ồn)'
        ],
        'smoking' => [
            'field_type' => 'boolean',
            'options_config' => '{"true_label":{"vi":"Cho phép hút thuốc","en":"Smoking OK","icon":"🚬"},"false_label":{"vi":"Không hút thuốc","en":"No Smoking","icon":"🚭"}}',
            'description_vi' => 'Chấp nhận hút thuốc trong nhà'
        ],
        'pets' => [
            'field_type' => 'boolean',
            'options_config' => '{"true_label":{"vi":"Cho phép thú cưng","en":"Pet Friendly","icon":"🐕"},"false_label":{"vi":"Không thú cưng","en":"No Pets","icon":"🚫"}}',
            'description_vi' => 'Chấp nhận nuôi thú cưng'
        ],
        'budget' => [
            'field_type' => 'range',
            'options_config' => '{"min":0,"max":20000000,"step":500000,"unit":"VND","format":"currency"}',
            'description_vi' => 'Khoảng ngân sách thuê phòng mỗi tháng'
        ]
    ];

    $stmt = $db->prepare("UPDATE preferences_list SET field_type = ?, options_config = ?, description_vi = ? WHERE code = ?");

    $updateCount = 0;
    foreach ($preferences as $code => $data) {
        $affected = $stmt->execute([
            $data['field_type'],
            $data['options_config'],
            $data['description_vi'],
            $code
        ]);

        if ($stmt->rowCount() > 0) {
            echo "<span class='status-success'>  ✓ Updated: {$code}</span>\n";
            $updateCount++;
        } else {
            echo "<span class='status-warning'>  ⚠️  Not found or unchanged: {$code}</span>\n";
        }
    }

    echo "<span class='status-success'>✅ Phase 2 completed ({$updateCount} preferences updated)</span>\n\n";

    // Verification
    echo "<span class='status-info'>🔍 Verification:</span>\n";
    $results = $db->query("
        SELECT code, name_vi, field_type,
               CASE
                 WHEN options_config IS NULL OR options_config = '' THEN 'NULL'
                 ELSE 'Configured'
               END as config_status
        FROM preferences_list
        WHERE is_active = 1
        ORDER BY code
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "\n<span class='status-success'>Total Active Preferences: " . count($results) . "</span>\n";
    foreach ($results as $pref) {
        $status = $pref['config_status'] === 'Configured' ? 'status-success' : 'status-warning';
        echo "  <span class='{$status}'>✓ {$pref['code']}</span>: {$pref['name_vi']} | Type: {$pref['field_type']} | Config: {$pref['config_status']}\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "<span class='status-success'>✅ All migrations completed successfully!</span>\n";

} catch (Exception $e) {
    echo "\n<span class='status-error'>❌ Migration failed: " . $e->getMessage() . "</span>\n";
    echo "<span class='status-error'>Stack trace:\n" . $e->getTraceAsString() . "</span>\n";
}

$output = ob_get_clean();
echo $output;
?>
        </div>

        <div style="margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
            <a href="preferences.php" class="btn btn-primary" style="margin-left: 1rem;">View Preferences →</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

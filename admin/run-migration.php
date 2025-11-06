<?php
/**
 * ADMIN: Run Database Migration
 * URL: /admin/run-migration.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

// Only admin can run migrations
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND id = 1"); // User ID 1 is admin
$stmt->execute([getCurrentUserId()]);
if (!$stmt->fetch()) {
    die("⛔ Access denied. Only admin can run migrations.");
}

$pageTitle = 'Database Migration';
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
        .btn-run:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        .status-success { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        .status-info { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1>🗄️ Database Migration</h1>
        <p style="color: #6b7280; margin-bottom: 2rem;">
            This will add <code>field_type</code> and <code>options_config</code> columns to <code>preferences_list</code> table.
        </p>

        <?php if (!isset($_POST['run'])): ?>
        <form method="POST">
            <button type="submit" name="run" value="1" class="btn-run">
                ▶️ Run Migration
            </button>
        </form>
        <?php else: ?>
        <h3>📋 Migration Log:</h3>
        <div class="log-output">
<?php
// Run migration
ob_start();

try {
    $db = getDB();

    echo "🔄 RUMI Database Migration Runner\n";
    echo str_repeat("=", 50) . "\n\n";

    // Check if preferences_list exists
    $tables = $db->query("SHOW TABLES LIKE 'preferences_list'")->fetchAll();
    if (empty($tables)) {
        echo "<span class='status-warning'>⚠️  Table preferences_list doesn't exist.</span>\n";
        echo "Please run schema.sql first.\n";
        throw new Exception("Table not found");
    }

    // Seed initial preferences if table is empty
    $count = $db->query("SELECT COUNT(*) FROM preferences_list")->fetchColumn();
    if ($count == 0) {
        echo "<span class='status-info'>📦 Seeding initial preferences...</span>\n";
        $seedSql = file_get_contents(__DIR__ . '/../database/seed_initial_preferences.sql');
        $statements = array_filter(
            array_map('trim', explode(';', $seedSql)),
            fn($s) => !empty($s) && !str_starts_with($s, '--') && stripos($s, 'SELECT') !== 0
        );
        foreach ($statements as $sql) {
            $db->exec($sql);
        }
        echo "<span class='status-success'>✅ Initial preferences seeded</span>\n\n";
    } else {
        echo "<span class='status-info'>✓ Preferences table has {$count} records</span>\n\n";
    }

    // Run migration 1: Add columns (use v2 - safer version)
    echo "<span class='status-info'>📦 Running Migration 1: Add preference options config...</span>\n";
    $migration1 = file_get_contents(__DIR__ . '/../database/migrations/add_preference_options_config_v2.sql');

    $statements = array_filter(
        array_map('trim', explode(';', $migration1)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );

    foreach ($statements as $sql) {
        if (stripos($sql, 'DESCRIBE') === 0 || stripos($sql, 'SELECT') === 0) {
            continue;
        }
        try {
            $db->exec($sql);
            echo "  ✓ Executed: " . substr($sql, 0, 60) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<span class='status-warning'>  ⚠️  Column already exists (skipped)</span>\n";
            } else {
                throw $e;
            }
        }
    }
    echo "<span class='status-success'>✅ Migration 1 completed</span>\n\n";

    // Run migration 2: Seed data (use v2 - safer version with TEXT instead of JSON)
    echo "<span class='status-info'>📦 Running Migration 2: Seed preference options...</span>\n";
    $migration2 = file_get_contents(__DIR__ . '/../database/migrations/seed_preference_options_v2.sql');

    $statements = array_filter(
        array_map('trim', explode(';', $migration2)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );

    $updateCount = 0;
    foreach ($statements as $sql) {
        if (stripos($sql, 'SELECT') === 0) {
            continue;
        }

        try {
            $affected = $db->exec($sql);
            if ($affected > 0) {
                $updateCount++;
                // Get preference code from UPDATE statement
                preg_match("/WHERE code = '([^']+)'/", $sql, $matches);
                if (isset($matches[1])) {
                    echo "  ✓ Updated: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            echo "<span class='status-error'>  ⚠️  Error: " . $e->getMessage() . "</span>\n";
        }
    }

    echo "<span class='status-success'>✅ Migration 2 completed ({$updateCount} preferences updated)</span>\n\n";

    // Verify final state
    echo "<span class='status-info'>🔍 Verification:</span>\n";
    $stmt = $db->query("
        SELECT code, name_vi, field_type,
               CASE
                 WHEN options_config IS NULL THEN 'NULL'
                 ELSE 'Configured'
               END as config_status
        FROM preferences_list
        WHERE is_active = 1
        ORDER BY category, sort_order
    ");
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n<span class='status-success'>Total Active Preferences: " . count($preferences) . "</span>\n";
    foreach ($preferences as $pref) {
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

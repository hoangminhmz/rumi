<?php
/**
 * Migration Runner - Execute database migrations
 * Usage: php database/run_migration.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "🔄 RUMI Database Migration Runner\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $db = getDB();

    // Check if preferences_list exists
    $tables = $db->query("SHOW TABLES LIKE 'preferences_list'")->fetchAll();
    if (empty($tables)) {
        echo "⚠️  Table preferences_list doesn't exist. Creating from schema...\n";
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $db->exec($schema);
        echo "✅ Schema created\n\n";
    }

    // Seed initial preferences if table is empty
    $count = $db->query("SELECT COUNT(*) FROM preferences_list")->fetchColumn();
    if ($count == 0) {
        echo "📦 Seeding initial preferences...\n";
        $seedSql = file_get_contents(__DIR__ . '/seed_initial_preferences.sql');
        $statements = array_filter(
            array_map('trim', explode(';', $seedSql)),
            fn($s) => !empty($s) && !str_starts_with($s, '--') && stripos($s, 'SELECT') !== 0
        );
        foreach ($statements as $sql) {
            $db->exec($sql);
        }
        echo "✅ Initial preferences seeded\n\n";
    }

    // Run migration 1: Add columns
    echo "📦 Running Migration 1: Add preference options config...\n";
    $migration1 = file_get_contents(__DIR__ . '/migrations/add_preference_options_config.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $migration1)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );

    foreach ($statements as $sql) {
        if (stripos($sql, 'DESCRIBE') === 0 || stripos($sql, 'SELECT') === 0) {
            // Skip describe/select statements
            continue;
        }
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // Ignore "Duplicate column" errors (column already exists)
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
            echo "⚠️  Column already exists (skipped)\n";
        }
    }
    echo "✅ Migration 1 completed\n\n";

    // Run migration 2: Seed data
    echo "📦 Running Migration 2: Seed preference options...\n";
    $migration2 = file_get_contents(__DIR__ . '/migrations/seed_preference_options.sql');

    $statements = array_filter(
        array_map('trim', explode(';', $migration2)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );

    $updateCount = 0;
    foreach ($statements as $sql) {
        if (stripos($sql, 'SELECT') === 0) {
            // Execute select and show results
            $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo "\n📊 Current Preferences:\n";
            foreach ($results as $row) {
                echo "  - {$row['code']}: {$row['name_vi']} ({$row['field_type']})\n";
            }
            continue;
        }

        try {
            $affected = $db->exec($sql);
            if ($affected > 0) {
                $updateCount++;
            }
        } catch (PDOException $e) {
            echo "⚠️  Error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Migration 2 completed ({$updateCount} preferences updated)\n\n";

    // Verify final state
    echo "🔍 Verification:\n";
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

    echo "\nTotal Active Preferences: " . count($preferences) . "\n";
    foreach ($preferences as $pref) {
        echo "  ✓ {$pref['code']}: {$pref['name_vi']} | Type: {$pref['field_type']} | Config: {$pref['config_status']}\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

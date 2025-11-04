<?php
/**
 * RUMI - Schema V2 Migration Runner
 * Run this page to update database schema for new features
 */

require_once __DIR__ . '/../../config/database.php';

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
    <title>Schema V2 Migration - RUMI</title>
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
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
        .subtitle {
            color: #86868b;
            margin-bottom: 30px;
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
        .section {
            border-left: 3px solid #0a84ff;
            padding-left: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Schema V2 Migration</h1>
        <p class="subtitle">Updating database for enhanced matching features</p>

        <div class="log"><?php

try {
    $db = getDB();

    echo "🚀 <span class='info'>Starting Schema V2 Migration...</span>\n\n";

    // Read SQL file
    $sqlFile = __DIR__ . '/update_schema_v2.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    echo "📄 <span class='info'>Loaded migration SQL file</span>\n\n";

    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^--/', $stmt) &&
                   !preg_match('/^\/\*/', $stmt);
        }
    );

    echo "📊 <span class='info'>Found " . count($statements) . " SQL statements to execute</span>\n\n";

    // Execute each statement
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;

    echo "<span class='section'>\n";
    echo "📝 <span class='info'>Executing migrations...</span>\n\n";

    foreach ($statements as $index => $statement) {
        try {
            // Skip empty statements and comments
            if (empty(trim($statement))) {
                continue;
            }

            // Extract operation type for logging
            $operation = '';
            if (preg_match('/^(ALTER TABLE|CREATE TABLE|INSERT INTO|SELECT)/i', $statement, $matches)) {
                $operation = strtoupper($matches[1]);
            }

            // Execute statement
            $db->exec($statement);

            $successCount++;

            // Log successful operations
            if ($operation) {
                if ($operation === 'ALTER TABLE') {
                    // Extract table name
                    preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $tableMatch);
                    $tableName = $tableMatch[1] ?? 'unknown';
                    echo "  <span class='success'>✓</span> ALTER TABLE {$tableName}\n";
                } elseif ($operation === 'CREATE TABLE') {
                    preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $tableMatch);
                    $tableName = $tableMatch[1] ?? 'unknown';
                    echo "  <span class='success'>✓</span> CREATE TABLE {$tableName}\n";
                } elseif ($operation === 'INSERT INTO') {
                    preg_match('/INSERT INTO\s+(\w+)/i', $statement, $tableMatch);
                    $tableName = $tableMatch[1] ?? 'unknown';
                    echo "  <span class='success'>✓</span> INSERT INTO {$tableName}\n";
                }
            }

        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();

            // Check if it's a "duplicate column" or "already exists" error (can be ignored)
            if (
                strpos($errorMsg, 'Duplicate column') !== false ||
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'duplicate key') !== false
            ) {
                $skipCount++;
                // echo "  <span class='warning'>⊘</span> Skipped (already exists)\n";
            } else {
                $errorCount++;
                echo "  <span class='error'>✗</span> Error: " . htmlspecialchars($errorMsg) . "\n";
                // Continue with other statements
            }
        }
    }

    echo "</span>\n\n";

    // Summary
    echo "<span class='section'>\n";
    echo "📊 <span class='info'>Migration Summary:</span>\n";
    echo "   <span class='success'>Successful: {$successCount}</span>\n";
    if ($skipCount > 0) {
        echo "   <span class='warning'>Skipped (existed): {$skipCount}</span>\n";
    }
    if ($errorCount > 0) {
        echo "   <span class='error'>Errors: {$errorCount}</span>\n";
    }
    echo "</span>\n\n";

    // Verify changes
    echo "<span class='section'>\n";
    echo "🔍 <span class='info'>Verifying schema changes...</span>\n\n";

    // Check users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'sleep_schedule'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> users.sleep_schedule column exists\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'matching_stage'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> users.matching_stage column exists\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'verification_status'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> users.verification_status column exists\n";
    }

    // Check rooms table
    $stmt = $db->query("SHOW COLUMNS FROM rooms LIKE 'latitude'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> rooms.latitude column exists\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM rooms LIKE 'room_type'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> rooms.room_type column exists\n";
    }

    // Check new tables
    $stmt = $db->query("SHOW TABLES LIKE 'amenities_list'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> amenities_list table created\n";

        $stmt = $db->query("SELECT COUNT(*) as count FROM amenities_list");
        $result = $stmt->fetch();
        echo "    → {$result['count']} amenities loaded\n";
    }

    $stmt = $db->query("SHOW TABLES LIKE 'preferences_list'");
    if ($stmt->rowCount() > 0) {
        echo "  <span class='success'>✓</span> preferences_list table created\n";

        $stmt = $db->query("SELECT COUNT(*) as count FROM preferences_list");
        $result = $stmt->fetch();
        echo "    → {$result['count']} preferences loaded\n";
    }

    echo "</span>\n\n";

    // Final status
    if ($errorCount === 0) {
        echo "✅ <span class='success'>Migration completed successfully!</span>\n\n";
        echo "🎉 <span class='info'>You can now:</span>\n";
        echo "   • Update profile setup form with new fields\n";
        echo "   • Use enhanced filter with lifestyle preferences\n";
        echo "   • Enable two-stage matching\n";
        echo "   • Add Mapbox location picker\n";
        echo "   • Start building admin panel\n";
    } else {
        echo "⚠️  <span class='warning'>Migration completed with {$errorCount} errors</span>\n";
        echo "    Please review errors above and fix manually if needed.\n";
    }

} catch (Exception $e) {
    echo "\n❌ <span class='error'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n<span class='error'>Stack trace:</span>\n";
    echo htmlspecialchars($e->getTraceAsString());
}

?></div>

        <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <h3 style="margin-top: 0;">🔄 Next Steps:</h3>
            <ol>
                <li><strong>Verify</strong> that all changes are applied correctly above</li>
                <li><strong>Backup</strong> your database before proceeding (recommended)</li>
                <li><strong>Continue</strong> to Block 2: Core Models implementation</li>
                <li><strong>Test</strong> existing features still work after migration</li>
            </ol>

            <p style="color: #86868b; margin-top: 20px;">
                💡 <strong>Tip:</strong> If you see "Skipped (already exists)" messages, that's normal -
                it means those columns/tables were already added in a previous migration.
            </p>
        </div>
    </div>
</body>
</html>

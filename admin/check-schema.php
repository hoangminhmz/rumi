<?php
/**
 * Check current preferences_list schema
 */
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "=== PREFERENCES_LIST TABLE SCHEMA ===\n\n";

// Show columns
$columns = $db->query("SHOW COLUMNS FROM preferences_list")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']} {$col['Default']}\n";
}

echo "\n=== CURRENT DATA ===\n\n";

// Show all rows
$rows = $db->query("SELECT * FROM preferences_list")->fetchAll(PDO::FETCH_ASSOC);
echo "Total rows: " . count($rows) . "\n\n";

foreach ($rows as $row) {
    echo "Code: {$row['code']}\n";
    echo "  Name: {$row['name_vi']}\n";
    echo "  Category: " . ($row['category'] ?? 'NULL') . "\n";
    echo "  Weight: " . ($row['weight'] ?? 'NULL') . "\n";
    echo "  Active: " . ($row['is_active'] ?? 'NULL') . "\n";

    // Show new columns if exist
    if (isset($row['field_type'])) {
        echo "  Field Type: {$row['field_type']}\n";
    }
    if (isset($row['options_config'])) {
        $config = $row['options_config'];
        echo "  Options Config: " . ($config ? substr($config, 0, 80) . '...' : 'NULL') . "\n";
    }
    echo "\n";
}

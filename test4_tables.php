<?php
// TEST 4: CHECK TABLES
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST 4: Check Database Tables</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "✓ Database connected<br><br>";

    // Check users table
    echo "<h3>Users Table:</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total columns: " . count($columns) . "<br>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";

    // Check for new columns
    $newColumns = ['search_mode', 'sleep_schedule', 'work_schedule', 'drinking', 'guests_policy'];
    echo "<h3>New Columns Check:</h3>";
    echo "<ul>";
    foreach ($newColumns as $col) {
        $exists = in_array($col, $columns);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $col</li>";
    }
    echo "</ul>";

    // Check tables
    echo "<h3>All Tables:</h3>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Check new tables
    $newTables = ['amenities_list', 'preferences_list'];
    echo "<h3>New Tables Check:</h3>";
    echo "<ul>";
    foreach ($newTables as $table) {
        $exists = in_array($table, $tables);
        $icon = $exists ? "✓" : "✗";
        $color = $exists ? "green" : "red";
        echo "<li style='color: $color;'><b>$icon</b> $table";

        if ($exists) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo " ($count rows)";
        }
        echo "</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<br><b style='color: red;'>ERROR:</b> " . htmlspecialchars($e->getMessage());
    echo "<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<?php
/**
 * Check PHP Errors
 * Đọc error log và hiện lên
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>RUMI - Check Errors</h1>";

// Try to read PHP error log
echo "<h2>PHP Error Log:</h2>";

// Common error log locations
$error_logs = [
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    ini_get('error_log'),
    '/home/hoangmi5/www/error_log',
    '/home/hoangmi5/logs/error_log',
    '/tmp/error_log'
];

$found = false;
foreach ($error_logs as $log_path) {
    if ($log_path && file_exists($log_path)) {
        echo "<h3>Found: $log_path</h3>";
        echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ddd; max-height:400px; overflow:auto;'>";

        // Read last 50 lines
        $lines = file($log_path);
        $last_lines = array_slice($lines, -50);
        echo htmlspecialchars(implode('', $last_lines));

        echo "</pre>";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "<p>❌ Không tìm thấy error log file.</p>";
    echo "<p>Kiểm tra trong cPanel → Errors để xem log.</p>";
}

echo "<hr>";
echo "<h2>Trigger Test Error:</h2>";
echo "<p>Click link này để trigger 1 error có chủ đích:</p>";
echo "<a href='?trigger=1'>Trigger Test Error</a><br><br>";

if (isset($_GET['trigger'])) {
    echo "<div style='background:#fee; padding:10px; border:1px solid #f00;'>";
    echo "Attempting to trigger error...<br>";

    // This will cause an error
    require_once 'nonexistent-file.php';
}

echo "<hr>";
echo "<a href='show-errors.php'>← Back to Show Errors</a> | ";
echo "<a href='debug.php'>Debug Info</a>";
?>

<?php
/**
 * Step 1: Test PHP Basic
 * File đơn giản nhất - không include gì cả
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Step 1</title></head><body>";
echo "<h1 style='color:green;'>✅ STEP 1 WORKS!</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<p>Server: " . php_sapi_name() . "</p>";
echo "<hr>";
echo "<a href='test-step2.php'>→ Next: Test Step 2</a>";
echo "</body></html>";
?>

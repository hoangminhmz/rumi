<?php
// TEST 2: HIỂN THỊ LỖI
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "TEST 2: Error display enabled<br>";
echo "PHP Version: " . phpversion() . "<br><br>";

// Test intentional error
echo "Testing error display...<br>";
// Uncomment dòng dưới để test hiển thị lỗi:
// $test = $undefined_variable;

echo "If you see this, error reporting is working!<br>";
?>

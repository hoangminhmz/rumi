<?php
/**
 * BASE TEST - Không load Match.php, chỉ test các dependency
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "BASE TEST - Testing dependencies only\n\n";

echo "1. Test database.php\n";
require_once __DIR__ . '/config/database.php';
echo "   ✓ OK\n\n";

echo "2. Test constants.php\n";
require_once __DIR__ . '/config/constants.php';
echo "   ✓ OK\n\n";

echo "3. Test functions.php\n";
require_once __DIR__ . '/includes/functions.php';
echo "   ✓ OK\n\n";

echo "4. Test database connection\n";
$db = getDB();
echo "   ✓ OK\n\n";

echo "5. Test User.php\n";
require_once __DIR__ . '/includes/User.php';
$user = new User();
echo "   ✓ User.php loaded\n\n";

echo "6. Test Room.php\n";
require_once __DIR__ . '/includes/Room.php';
$room = new Room();
echo "   ✓ Room.php loaded\n\n";

echo "7. Now test Match.php\n";
echo "   Match.php path: " . __DIR__ . '/includes/Match.php' . "\n";
echo "   File exists: " . (file_exists(__DIR__ . '/includes/Match.php') ? 'YES' : 'NO') . "\n";

try {
    require_once __DIR__ . '/includes/Match.php';
    echo "   ✓ Match.php loaded\n\n";

    $match = new MatchModel();
    echo "   ✓ MatchModel object created\n\n";

    echo "ALL TESTS PASSED!\n";

} catch (Throwable $e) {
    echo "   ✗ ERROR:\n";
    echo "   Type: " . get_class($e) . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}
?>

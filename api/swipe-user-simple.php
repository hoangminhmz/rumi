<?php
/**
 * SIMPLE SWIPE API - Minimal version to test
 */

// Enable error reporting
ini_set('display_errors', 0); // Don't display, will return in JSON
error_reporting(E_ALL);

// Capture errors
$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = "$errstr in $errfile:$errline";
});

// Start output buffering to catch any output
ob_start();

try {
    // Set header first
    header('Content-Type: application/json');

    // Load files
    if (!file_exists(__DIR__ . '/../config/database.php')) {
        throw new Exception('database.php not found');
    }
    require_once __DIR__ . '/../config/database.php';

    if (!file_exists(__DIR__ . '/../config/constants.php')) {
        throw new Exception('constants.php not found');
    }
    require_once __DIR__ . '/../config/constants.php';

    if (!file_exists(__DIR__ . '/../includes/functions.php')) {
        throw new Exception('functions.php not found');
    }
    require_once __DIR__ . '/../includes/functions.php';

    if (!file_exists(__DIR__ . '/../includes/User.php')) {
        throw new Exception('User.php not found');
    }
    require_once __DIR__ . '/../includes/User.php';

    // Start session
    startSession();

    // Check if logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Not logged in',
            'session' => $_SESSION,
            'errors' => $errors
        ]);
        exit;
    }

    $userId = getCurrentUserId();

    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'method' => $_SERVER['REQUEST_METHOD'],
            'errors' => $errors
        ]);
        exit;
    }

    // Get input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON: ' . json_last_error_msg(),
            'raw_input' => $rawInput,
            'errors' => $errors
        ]);
        exit;
    }

    // Validate input
    if (!isset($input['target_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing target_id',
            'input' => $input,
            'errors' => $errors
        ]);
        exit;
    }

    if (!isset($input['is_like'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing is_like',
            'input' => $input,
            'errors' => $errors
        ]);
        exit;
    }

    $targetUserId = (int)$input['target_id'];
    $isLike = (bool)$input['is_like'];

    // Validate
    if ($userId === $targetUserId) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot swipe yourself',
            'errors' => $errors
        ]);
        exit;
    }

    // Get database
    $db = getDB();

    // Count before
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $beforeCount = $stmt->fetch()['count'];

    // DO THE SWIPE - Direct database insert (bypass User model for now)
    $stmt = $db->prepare("
        INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_like = ?, created_at = NOW()
    ");

    $result = $stmt->execute([$userId, $targetUserId, $isLike, $isLike]);

    // Count after
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $afterCount = $stmt->fetch()['count'];

    // Clean output buffer
    $output = ob_get_clean();

    // Return success
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Swipe saved' : 'Swipe failed',
        'data' => [
            'user_id' => $userId,
            'target_id' => $targetUserId,
            'is_like' => $isLike,
            'before_count' => $beforeCount,
            'after_count' => $afterCount,
            'difference' => $afterCount - $beforeCount,
            'matched' => false
        ],
        'errors' => $errors,
        'output' => $output
    ]);

} catch (Exception $e) {
    // Clean output buffer
    ob_end_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'errors' => $errors
    ]);
}
?>

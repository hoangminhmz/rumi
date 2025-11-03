<?php
/**
 * RUMI API - Swipe Room (Simple Version)
 * Handle room swipe actions
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = "$errstr in $errfile:$errline";
});

ob_start();

try {
    header('Content-Type: application/json');

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../includes/functions.php';

    startSession();

    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Not logged in',
            'errors' => $errors
        ]);
        exit;
    }

    $userId = getCurrentUserId();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'errors' => $errors
        ]);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON: ' . json_last_error_msg(),
            'errors' => $errors
        ]);
        exit;
    }

    if (!isset($input['target_id']) || !isset($input['is_like'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters',
            'errors' => $errors
        ]);
        exit;
    }

    $roomId = (int)$input['target_id'];
    $isLike = (bool)$input['is_like'];

    $db = getDB();

    // Insert room swipe
    $stmt = $db->prepare("
        INSERT INTO room_swipes (user_id, room_id, is_like, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_like = ?, created_at = NOW()
    ");

    $result = $stmt->execute([$userId, $roomId, $isLike, $isLike]);

    ob_end_clean();

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Room swipe saved' : 'Room swipe failed',
        'data' => [
            'matched' => false
        ],
        'errors' => $errors
    ]);

} catch (Exception $e) {
    ob_end_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'errors' => $errors
    ]);
}
?>

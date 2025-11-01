<?php
/**
 * RUMI API - Swipe Room
 * Handle room swipe actions
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Room.php';

startSession();

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized');
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed');
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['target_id']) || !isset($input['is_like'])) {
        throw new Exception('Missing required parameters');
    }

    $userId = getCurrentUserId();
    $roomId = (int)$input['target_id'];
    $isLike = (bool)$input['is_like'];

    // Record swipe
    $roomModel = new Room();
    if (!$roomModel->swipe($userId, $roomId, $isLike)) {
        throw new Exception('Failed to record swipe');
    }

    // For room swipes, we don't need immediate matching
    // Room owner can see who liked their room and reach out

    jsonResponse(true, [
        'matched' => false
    ], 'Swipe recorded successfully');

} catch (Exception $e) {
    jsonResponse(false, null, $e->getMessage());
}

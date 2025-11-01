<?php
/**
 * RUMI API - Swipe User
 * Handle user swipe actions
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Match.php';

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
    $targetUserId = (int)$input['target_id'];
    $isLike = (bool)$input['is_like'];

    // Validate
    if ($userId === $targetUserId) {
        throw new Exception('Cannot swipe yourself');
    }

    // Check swipe limit
    if (!checkSwipeLimit($userId)) {
        throw new Exception('Daily swipe limit reached. Try again tomorrow!');
    }

    // Record swipe
    $userModel = new User();
    if (!$userModel->swipe($userId, $targetUserId, $isLike)) {
        throw new Exception('Failed to record swipe');
    }

    // Check for match if like
    $matched = false;
    $matchData = null;

    if ($isLike && $userModel->checkMutualLike($userId, $targetUserId)) {
        // Create match
        $matchModel = new Match();
        $matchId = $matchModel->create($userId, $targetUserId);

        if ($matchId) {
            $matched = true;
            $matchInfo = $matchModel->getById($matchId);

            // Get matched user info
            $targetUser = $userModel->getById($targetUserId);

            $matchData = [
                'match_id' => $matchId,
                'user1_avatar' => $matchInfo['user1_avatar'] ? getUploadURL($matchInfo['user1_avatar']) : ASSETS_URL . '/images/default-avatar.png',
                'user2_avatar' => $matchInfo['user2_avatar'] ? getUploadURL($matchInfo['user2_avatar']) : ASSETS_URL . '/images/default-avatar.png',
                'matched_user_name' => $targetUser['name']
            ];

            // Log activity
            logActivity($userId, 'match', ['target_user_id' => $targetUserId, 'match_id' => $matchId]);
        }
    }

    jsonResponse(true, [
        'matched' => $matched,
        'match' => $matchData
    ], 'Swipe recorded successfully');

} catch (Exception $e) {
    jsonResponse(false, null, $e->getMessage());
}

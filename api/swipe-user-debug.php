<?php
/**
 * RUMI API - Swipe User (DEBUG VERSION)
 * Handle user swipe actions with detailed logging
 */

// Start output buffering to capture any errors
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$debugLog = [];
$debugLog[] = "=== SWIPE API DEBUG LOG ===";
$debugLog[] = "Time: " . date('Y-m-d H:i:s');
$debugLog[] = "Request Method: " . $_SERVER['REQUEST_METHOD'];
$debugLog[] = "Request URI: " . $_SERVER['REQUEST_URI'];

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Match.php';

$debugLog[] = "Files loaded successfully";

startSession();
$debugLog[] = "Session started";

// Check authentication
if (!isLoggedIn()) {
    $debugLog[] = "❌ NOT LOGGED IN";
    $debugLog[] = "Session data: " . print_r($_SESSION, true);

    jsonResponse(false, ['debug' => $debugLog], 'Unauthorized');
    exit;
}

$userId = getCurrentUserId();
$debugLog[] = "✅ Logged in as user ID: $userId";

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $debugLog[] = "❌ Wrong method: " . $_SERVER['REQUEST_METHOD'];
    jsonResponse(false, ['debug' => $debugLog], 'Method not allowed');
    exit;
}

$debugLog[] = "✅ POST method confirmed";

try {
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $debugLog[] = "Raw input: " . $rawInput;

    // Get JSON input
    $input = json_decode($rawInput, true);
    $debugLog[] = "Decoded input: " . print_r($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $debugLog[] = "❌ JSON decode error: " . json_last_error_msg();
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    if (!isset($input['target_id']) || !isset($input['is_like'])) {
        $debugLog[] = "❌ Missing parameters. target_id: " . (isset($input['target_id']) ? 'YES' : 'NO') . ", is_like: " . (isset($input['is_like']) ? 'YES' : 'NO');
        throw new Exception('Missing required parameters');
    }

    $targetUserId = (int)$input['target_id'];
    $isLike = (bool)$input['is_like'];

    $debugLog[] = "✅ Parameters OK - target_id: $targetUserId, is_like: " . ($isLike ? 'true' : 'false');

    // Validate
    if ($userId === $targetUserId) {
        $debugLog[] = "❌ Cannot swipe yourself";
        throw new Exception('Cannot swipe yourself');
    }

    $debugLog[] = "✅ Validation passed";

    // Check swipe limit
    if (!checkSwipeLimit($userId)) {
        $debugLog[] = "❌ Swipe limit reached";
        throw new Exception('Daily swipe limit reached. Try again tomorrow!');
    }

    $debugLog[] = "✅ Swipe limit OK";

    // Get database connection
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $beforeCount = $stmt->fetch()['count'];
    $debugLog[] = "Swipes before: $beforeCount";

    // Record swipe
    $userModel = new User();
    $swipeResult = $userModel->swipe($userId, $targetUserId, $isLike);

    $debugLog[] = "Swipe function returned: " . ($swipeResult ? 'TRUE' : 'FALSE');

    if (!$swipeResult) {
        $debugLog[] = "❌ Swipe function failed";
        throw new Exception('Failed to record swipe');
    }

    // Check database again
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $afterCount = $stmt->fetch()['count'];
    $debugLog[] = "Swipes after: $afterCount";
    $debugLog[] = "Difference: " . ($afterCount - $beforeCount);

    // Verify the specific swipe was saved
    $stmt = $db->prepare("SELECT * FROM user_swipes WHERE user_id = ? AND target_user_id = ?");
    $stmt->execute([$userId, $targetUserId]);
    $swipeRecord = $stmt->fetch();

    if ($swipeRecord) {
        $debugLog[] = "✅ Swipe record found in database!";
        $debugLog[] = "Record: " . print_r($swipeRecord, true);
    } else {
        $debugLog[] = "❌ Swipe record NOT found in database!";
    }

    // Check for match if like
    $matched = false;
    $matchData = null;

    if ($isLike) {
        $debugLog[] = "Checking for mutual like...";
        $mutualLike = $userModel->checkMutualLike($userId, $targetUserId);
        $debugLog[] = "Mutual like: " . ($mutualLike ? 'YES' : 'NO');

        if ($mutualLike) {
            // Create match
            $matchModel = new Match();
            $matchId = $matchModel->create($userId, $targetUserId);
            $debugLog[] = "Match ID: " . ($matchId ?: 'FAILED');

            if ($matchId) {
                $matched = true;
                $matchInfo = $matchModel->getById($matchId);

                // Get matched user info
                $targetUser = $userModel->getById($targetUserId);

                $matchData = [
                    'match_id' => $matchId,
                    'user1_avatar' => $matchInfo['user1_avatar'] ? getUploadURL($matchInfo['user1_avatar']) : ASSETS_URL . '/images/default-avatar.svg',
                    'user2_avatar' => $matchInfo['user2_avatar'] ? getUploadURL($matchInfo['user2_avatar']) : ASSETS_URL . '/images/default-avatar.svg',
                    'matched_user_name' => $targetUser['name']
                ];

                $debugLog[] = "✅ Match created successfully";

                // Log activity
                logActivity($userId, 'match', ['target_user_id' => $targetUserId, 'match_id' => $matchId]);
            }
        }
    }

    $debugLog[] = "=== SUCCESS ===";

    jsonResponse(true, [
        'matched' => $matched,
        'match' => $matchData,
        'debug' => $debugLog
    ], 'Swipe recorded successfully');

} catch (Exception $e) {
    $debugLog[] = "❌ EXCEPTION: " . $e->getMessage();
    $debugLog[] = "File: " . $e->getFile() . ":" . $e->getLine();
    $debugLog[] = "Trace: " . $e->getTraceAsString();

    jsonResponse(false, ['debug' => $debugLog], $e->getMessage());
}

// Clean up output buffer
ob_end_clean();
?>

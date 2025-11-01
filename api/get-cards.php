<?php
/**
 * RUMI API - Get Cards
 * Fetch new cards for swipe interface (AJAX reload)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Room.php';

startSession();

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized');
}

try {
    $mode = $_GET['mode'] ?? 'find_roommate';
    $limit = (int)($_GET['limit'] ?? CARDS_PER_SWIPE);

    $userModel = new User();
    $roomModel = new Room();
    $currentUser = $userModel->getById(getCurrentUserId());

    if (!$currentUser) {
        throw new Exception('User not found');
    }

    // Get cards based on mode
    if ($mode === 'find_roommate') {
        $cards = $userModel->getPotentialMatches(getCurrentUserId(), $limit);
    } else {
        $cards = $roomModel->getPotentialRooms(getCurrentUserId(), $currentUser['district_id'], $limit);
    }

    jsonResponse(true, [
        'cards' => $cards,
        'count' => count($cards)
    ]);

} catch (Exception $e) {
    jsonResponse(false, null, $e->getMessage());
}

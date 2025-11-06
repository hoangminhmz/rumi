<?php
/**
 * Get Room Detail for Modal Display
 * Returns complete room information including all amenities
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $roomId = $_GET['room_id'] ?? null;

    if (!$roomId) {
        throw new Exception('Room ID is required');
    }

    $db = getDBConnection();

    // Get complete room data including all amenities
    $stmt = $db->prepare("
        SELECT
            r.id,
            r.title,
            r.description,
            r.price,
            r.area,
            r.address,
            r.amenities,
            r.images,
            d.name as district_name,
            c.name as city_name,
            u.name as owner_name,
            u.phone as owner_phone
        FROM rooms r
        LEFT JOIN districts d ON r.district_id = d.id
        LEFT JOIN cities c ON d.city_id = c.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.id = ? AND r.status = 'active'
    ");

    $stmt->execute([$roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        throw new Exception('Room not found');
    }

    // Decode JSON fields
    if ($room['amenities']) {
        $room['amenities'] = json_decode($room['amenities'], true);
    } else {
        $room['amenities'] = [];
    }

    if ($room['images']) {
        $room['images'] = json_decode($room['images'], true);
    } else {
        $room['images'] = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $room
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

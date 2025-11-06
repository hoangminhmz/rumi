<?php
/**
 * Get User Detail for Modal Display
 * Returns complete user information including lifestyle preferences
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
    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    $db = getDBConnection();

    // Get complete user data including all lifestyle fields
    $stmt = $db->prepare("
        SELECT
            u.id,
            u.name,
            u.avatar,
            u.age,
            u.gender,
            u.bio,
            u.occupation,
            u.sleep_schedule,
            u.work_schedule,
            u.drinking,
            u.guests_policy,
            u.preferences,
            d.name as district_name,
            c.name as city_name
        FROM users u
        LEFT JOIN districts d ON u.district_id = d.id
        LEFT JOIN cities c ON d.city_id = c.id
        WHERE u.id = ? AND u.is_active = 1
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Decode preferences JSON
    if ($user['preferences']) {
        $user['preferences'] = json_decode($user['preferences'], true);
    } else {
        $user['preferences'] = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

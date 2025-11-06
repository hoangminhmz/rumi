<?php
/**
 * RUMI - API: Get Amenities
 * Returns all active amenities for dynamic loading
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $sql = "
        SELECT
            id, code, name_vi, name_en, icon, sort_order, is_active
        FROM amenities_list
        WHERE is_active = 1
        ORDER BY sort_order ASC, name_vi ASC
    ";

    $stmt = $db->query($sql);
    $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $amenities,
        'count' => count($amenities)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

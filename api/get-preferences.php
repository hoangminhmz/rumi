<?php
/**
 * RUMI - API: Get Preferences
 * Returns all active preferences with options for dynamic loading
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Get category filter if provided
    $category = $_GET['category'] ?? null;

    // Build query
    $sql = "
        SELECT
            id, code, name_vi, name_en, icon,
            field_type, options_config, description_vi, description_en,
            weight, category, is_active
        FROM preferences_list
        WHERE is_active = 1
    ";

    if ($category) {
        $sql .= " AND category = :category";
    }

    $sql .= " ORDER BY category ASC, weight DESC, name_vi ASC";

    $stmt = $db->prepare($sql);

    if ($category) {
        $stmt->execute(['category' => $category]);
    } else {
        $stmt->execute();
    }

    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON options_config for each preference
    foreach ($preferences as &$pref) {
        if (!empty($pref['options_config'])) {
            $pref['options'] = json_decode($pref['options_config'], true);
            unset($pref['options_config']); // Remove raw JSON from response
        } else {
            $pref['options'] = null;
        }
    }

    // Group by category if requested
    if (isset($_GET['grouped']) && $_GET['grouped'] === 'true') {
        $grouped = [];
        foreach ($preferences as $pref) {
            $cat = $pref['category'] ?? 'other';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $pref;
        }

        echo json_encode([
            'success' => true,
            'data' => $grouped,
            'count' => count($preferences)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $preferences,
            'count' => count($preferences)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

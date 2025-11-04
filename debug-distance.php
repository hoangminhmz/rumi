<?php
/**
 * Debug: Check if distance is calculated for cards
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Room.php';

startSession();
$_SESSION['user_id'] = 1; // Force user

$userModel = new User();
$roomModel = new Room();
$currentUser = $userModel->getById(1);

echo "<h2>Debug: Room Cards with Distance</h2>";

echo "<h3>Current User Info:</h3>";
echo "<pre>";
echo "User ID: {$currentUser['id']}\n";
echo "Name: {$currentUser['name']}\n";
echo "District ID: {$currentUser['district_id']}\n";
echo "District Name: {$currentUser['district_name']}\n";
echo "</pre>";

// Decode preferences
$userPreferences = is_string($currentUser['preferences'])
    ? json_decode($currentUser['preferences'], true)
    : $currentUser['preferences'];

if (!is_array($userPreferences)) {
    $userPreferences = [];
}

echo "<h3>User Preferences:</h3>";
echo "<pre>" . print_r($userPreferences, true) . "</pre>";

// Get cards
$cards = $roomModel->getPotentialRooms(
    1,
    $currentUser['district_id'],
    $userPreferences
);

echo "<h3>Cards Retrieved: " . count($cards) . "</h3>";

if (count($cards) > 0) {
    echo "<h3>First 3 Cards (with distance info):</h3>";

    foreach (array_slice($cards, 0, 3) as $i => $card) {
        echo "<div style='background: #f5f5f5; padding: 1rem; margin: 1rem 0; border-radius: 8px;'>";
        echo "<h4>Card #" . ($i + 1) . ": {$card['title']}</h4>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$card['id']}</td></tr>";
        echo "<tr><td>Title</td><td>{$card['title']}</td></tr>";
        echo "<tr><td>Price</td><td>" . number_format($card['price']) . "</td></tr>";
        echo "<tr><td>District</td><td>{$card['district_name']}</td></tr>";
        echo "<tr><td>Address</td><td>{$card['address']}</td></tr>";

        echo "<tr style='background: #fff3cd;'><td><strong>Room Latitude</strong></td><td>" . ($card['latitude'] ?? 'NULL') . "</td></tr>";
        echo "<tr style='background: #fff3cd;'><td><strong>Room Longitude</strong></td><td>" . ($card['longitude'] ?? 'NULL') . "</td></tr>";

        echo "<tr style='background: #d1ecf1;'><td><strong>District Lat</strong></td><td>" . ($card['district_lat'] ?? 'NULL') . "</td></tr>";
        echo "<tr style='background: #d1ecf1;'><td><strong>District Lng</strong></td><td>" . ($card['district_lng'] ?? 'NULL') . "</td></tr>";

        echo "<tr style='background: #d4edda;'><td><strong>distance_km</strong></td><td>" . ($card['distance_km'] ?? 'NULL') . "</td></tr>";
        echo "<tr style='background: #d4edda;'><td><strong>distance_formatted</strong></td><td>" . ($card['distance_formatted'] ?? 'EMPTY') . "</td></tr>";

        echo "<tr style='background: #e7d4f7;'><td><strong>ranking_score</strong></td><td>" . ($card['ranking_score'] ?? 'NULL') . "</td></tr>";

        echo "<tr><td>Area</td><td>{$card['area']}m²</td></tr>";
        echo "</table>";
        echo "</div>";
    }

    // Summary
    echo "<h3>Distance Summary:</h3>";
    $withDistance = 0;
    $withoutDistance = 0;

    foreach ($cards as $card) {
        if (!empty($card['distance_formatted'])) {
            $withDistance++;
        } else {
            $withoutDistance++;
        }
    }

    echo "<p><strong>Cards WITH distance:</strong> {$withDistance}</p>";
    echo "<p><strong>Cards WITHOUT distance:</strong> {$withoutDistance}</p>";

    if ($withoutDistance > 0) {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px;'>";
        echo "<h4>❌ Problem Found!</h4>";
        echo "<p>Rooms không có distance. Nguyên nhân có thể:</p>";
        echo "<ul>";
        echo "<li>Rooms chưa có latitude/longitude (chưa geocode)</li>";
        echo "<li>User's district chưa có coordinates</li>";
        echo "<li>GeoLocationService không tính được distance</li>";
        echo "</ul>";
        echo "<p><strong>Giải pháp:</strong></p>";
        echo "<ol>";
        echo "<li>Check rooms đã geocode chưa: <a href='check-rooms-status.php'>check-rooms-status.php</a></li>";
        echo "<li>Geocode rooms: <a href='admin-geocode.php'>admin-geocode.php</a></li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px;'>";
        echo "<p>✅ All cards have distance! Khoảng cách đang được tính.</p>";
        echo "<p>Nếu vẫn không thấy trên swipe page, có thể là JavaScript không render đúng.</p>";
        echo "</div>";
    }
}

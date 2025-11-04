<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "<h2>Room Geocoding Status</h2>";

// Check rooms
$totalRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active'")->fetchColumn();
$geocodedRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active' AND latitude IS NOT NULL")->fetchColumn();

echo "<p><strong>Active Rooms:</strong> {$totalRooms}</p>";
echo "<p><strong>Geocoded Rooms:</strong> {$geocodedRooms}</p>";
echo "<p><strong>Pending Geocode:</strong> " . ($totalRooms - $geocodedRooms) . "</p>";

if ($totalRooms == 0) {
    echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px;'>";
    echo "⚠️ <strong>No rooms found!</strong><br>";
    echo "Tạo dummy rooms tại: <a href='generate-dummy-rooms.php?key=rumi_dummy_2024'>generate-dummy-rooms.php</a>";
    echo "</div>";
}

if ($geocodedRooms == 0 && $totalRooms > 0) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px;'>";
    echo "❌ <strong>Rooms chưa có tọa độ!</strong><br>";
    echo "Geocode rooms tại: <a href='admin-geocode.php'>admin-geocode.php</a><br>";
    echo "Cần set MAPBOX_API_KEY trong config/constants.php trước";
    echo "</div>";
}

if ($geocodedRooms > 0) {
    echo "<h3>Sample Geocoded Rooms:</h3>";
    $stmt = $db->query("SELECT id, title, address, latitude, longitude FROM rooms WHERE latitude IS NOT NULL LIMIT 5");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Address</th><th>Lat</th><th>Lng</th></tr>";
    foreach ($rooms as $room) {
        echo "<tr>";
        echo "<td>{$room['id']}</td>";
        echo "<td>{$room['title']}</td>";
        echo "<td>{$room['address']}</td>";
        echo "<td>{$room['latitude']}</td>";
        echo "<td>{$room['longitude']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

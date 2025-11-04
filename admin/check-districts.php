<?php
/**
 * RUMI - Admin: Check District Names
 * View actual district names in database to fix migration
 */

require_once __DIR__ . '/../config/database.php';

// Simple auth
$adminToken = $_GET['token'] ?? '';
if ($adminToken !== 'rumi_admin_2024') {
    die('⛔ Unauthorized. Add ?token=rumi_admin_2024 to URL');
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Districts - RUMI Admin</title>
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f7;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d1d1f;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #1d1d1f;
        }
        tr:hover {
            background: #f9fafb;
        }
        .has-coords {
            color: #00aa00;
            font-weight: 600;
        }
        .no-coords {
            color: #ff3b30;
        }
        .code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Districts in Database</h1>
        <p style="color: #86868b;">Showing actual district names as stored in database</p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>District Name</th>
                    <th>City</th>
                    <th>Has Coordinates?</th>
                    <th>PHP Array Format</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT id, name, city_name, latitude, longitude
                        FROM districts
                        ORDER BY city_name, name
                    ");
                    $districts = $stmt->fetchAll();

                    foreach ($districts as $d) {
                        $hasCoords = ($d['latitude'] !== null && $d['longitude'] !== null);
                        $coordsClass = $hasCoords ? 'has-coords' : 'no-coords';
                        $coordsText = $hasCoords ? '✓ Yes' : '✗ No';

                        // Generate PHP array format for easy copy-paste
                        $arrayFormat = sprintf(
                            "['name' => '%s', 'city' => '%s', 'lat' => %.8f, 'lng' => %.8f],",
                            addslashes($d['name']),
                            addslashes($d['city_name']),
                            $d['latitude'] ?? 0.0,
                            $d['longitude'] ?? 0.0
                        );

                        echo "<tr>";
                        echo "<td>{$d['id']}</td>";
                        echo "<td><strong>" . htmlspecialchars($d['name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($d['city_name']) . "</td>";
                        echo "<td class='{$coordsClass}'>{$coordsText}</td>";
                        echo "<td><span class='code'>{$arrayFormat}</span></td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='5' style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <h3 style="margin-top: 0;">💡 Instructions:</h3>
            <ol>
                <li>Copy the "PHP Array Format" values from the table above</li>
                <li>Replace the coordinates in <code>admin/migrate-districts.php</code></li>
                <li>Run the migration again</li>
            </ol>
        </div>
    </div>
</body>
</html>

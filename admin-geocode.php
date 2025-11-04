<?php
/**
 * RUMI - Admin Geocoding Tool
 * Use this tool to:
 * 1. Run geolocation migration
 * 2. Geocode existing rooms
 *
 * IMPORTANT: Set your Mapbox API key in config/constants.php before using this tool
 * Get free API key at: https://www.mapbox.com/
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/GeoLocationService.php';

// Simple authentication - change this password for production!
$ADMIN_PASSWORD = 'rumi_admin_2024';

session_start();

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>RUMI Geocoding Tool - Login</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { width: 100%; padding: 12px; background: #00D4AA; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
            button:hover { background: #00B891; }
            .error { color: red; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <h2>🗺️ RUMI Geocoding Tool</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter admin password" required>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Initialize services
$db = getDB();
$geoService = new GeoLocationService();

// Handle actions
$message = '';
$messageType = 'info';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'run_migration':
            try {
                $migrationSQL = file_get_contents(__DIR__ . '/database/migrations/001_add_geolocation.sql');
                $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }

                $message = "✅ Migration completed successfully! Districts have been geocoded.";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "❌ Migration error: " . $e->getMessage();
                $messageType = 'error';
            }
            break;

        case 'geocode_rooms':
            $apiKey = defined('MAPBOX_API_KEY') ? MAPBOX_API_KEY : '';
            if (empty($apiKey)) {
                $message = "❌ Please set MAPBOX_API_KEY in config/constants.php first!";
                $messageType = 'error';
                break;
            }

            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;

            // Get rooms without coordinates
            $stmt = $db->prepare("
                SELECT id, address, district_id
                FROM rooms
                WHERE (latitude IS NULL OR longitude IS NULL)
                AND status = 'active'
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rooms)) {
                $message = "✅ All active rooms are already geocoded!";
                $messageType = 'success';
                break;
            }

            $geocoded = 0;
            $failed = 0;
            $failedRooms = [];

            foreach ($rooms as $room) {
                // Build full address
                $stmt = $db->prepare("SELECT name, city_name FROM districts WHERE id = ?");
                $stmt->execute([$room['district_id']]);
                $district = $stmt->fetch(PDO::FETCH_ASSOC);

                $fullAddress = $room['address'] . ', ' . $district['name'] . ', ' . $district['city_name'] . ', Vietnam';

                if ($geoService->geocodeRoom($room['id'], $fullAddress)) {
                    $geocoded++;
                    // Add small delay to respect API rate limits
                    usleep(200000); // 200ms delay
                } else {
                    $failed++;
                    $failedRooms[] = "Room #{$room['id']}: {$room['address']}";
                }
            }

            $message = "✅ Geocoded {$geocoded} rooms successfully.";
            if ($failed > 0) {
                $message .= " ⚠️ Failed: {$failed} rooms.";
            }
            $messageType = $failed === 0 ? 'success' : 'warning';

            if (!empty($failedRooms)) {
                $message .= "<br><small>" . implode("<br>", $failedRooms) . "</small>";
            }
            break;
    }
}

// Get statistics
try {
    $stats = [
        'total_rooms' => $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
        'active_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active'")->fetchColumn(),
        'geocoded_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn(),
        'pending_geocode' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active' AND (latitude IS NULL OR longitude IS NULL)")->fetchColumn(),
        'districts_geocoded' => $db->query("SELECT COUNT(*) FROM districts WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn(),
        'total_districts' => $db->query("SELECT COUNT(*) FROM districts")->fetchColumn(),
    ];
} catch (PDOException $e) {
    $stats = null;
    if (!isset($message)) {
        $message = "⚠️ Database tables may not have geolocation columns yet. Run migration first.";
        $messageType = 'warning';
    }
}

$apiKeySet = !empty(MAPBOX_API_KEY);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUMI Geocoding Admin Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header h1 { color: #00D4AA; margin-bottom: 0.5rem; }
        .header p { color: #666; }
        .logout { float: right; color: #999; text-decoration: none; }
        .logout:hover { color: #666; }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .message.warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .message.info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card h3 { font-size: 2rem; color: #00D4AA; margin-bottom: 0.5rem; }
        .stat-card p { color: #666; font-size: 0.9rem; }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .section h2 { margin-bottom: 1rem; color: #333; }
        .section p { color: #666; margin-bottom: 1rem; }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 1rem;
        }
        .btn-primary { background: #00D4AA; color: white; }
        .btn-primary:hover { background: #00B891; transform: translateY(-2px); }
        .btn-primary:disabled { background: #ccc; cursor: not-allowed; transform: none; }

        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .input-group {
            margin: 1rem 0;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .input-group input {
            width: 200px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }

        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #00D4AA;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="?logout" class="logout">Logout</a>
            <h1>🗺️ RUMI Geocoding Admin Tool</h1>
            <p>Manage geolocation data for rooms and districts</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (!$apiKeySet): ?>
            <div class="warning-box">
                ⚠️ <strong>Mapbox API Key not set!</strong><br>
                Please set <code>MAPBOX_API_KEY</code> in <code>config/constants.php</code><br>
                Get your free API key at <a href="https://www.mapbox.com/" target="_blank">https://www.mapbox.com/</a>
            </div>
        <?php endif; ?>

        <?php if ($stats): ?>
            <div class="stats">
                <div class="stat-card">
                    <h3><?= $stats['geocoded_rooms'] ?> / <?= $stats['active_rooms'] ?></h3>
                    <p>Active Rooms Geocoded</p>
                </div>
                <div class="stat-card">
                    <h3><?= $stats['pending_geocode'] ?></h3>
                    <p>Rooms Pending Geocode</p>
                </div>
                <div class="stat-card">
                    <h3><?= $stats['districts_geocoded'] ?> / <?= $stats['total_districts'] ?></h3>
                    <p>Districts Geocoded</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2><span class="step-number">1</span>Run Migration</h2>
            <p>This will add <code>latitude</code> and <code>longitude</code> columns to the database and geocode all districts with predefined coordinates.</p>

            <form method="POST" onsubmit="return confirm('Run migration? This is safe to run multiple times.')">
                <input type="hidden" name="action" value="run_migration">
                <button type="submit" class="btn btn-primary">Run Migration</button>
            </form>
        </div>

        <div class="section">
            <h2><span class="step-number">2</span>Geocode Rooms</h2>
            <p>This will geocode room addresses using Mapbox API. Free tier allows 100,000 requests/month.</p>

            <?php if ($stats && $stats['pending_geocode'] > 0): ?>
                <p><strong><?= $stats['pending_geocode'] ?> rooms</strong> are waiting to be geocoded.</p>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirm('Start geocoding? This will use Mapbox API calls.')">
                <input type="hidden" name="action" value="geocode_rooms">

                <div class="input-group">
                    <label>Batch size (rooms per request):</label>
                    <input type="number" name="limit" value="10" min="1" max="100">
                    <p style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                        Start with 10 for testing. Max 100 per batch.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" <?= !$apiKeySet ? 'disabled' : '' ?>>
                    Geocode Rooms
                </button>
            </form>
        </div>

        <div class="section">
            <h2>📚 Documentation</h2>
            <p><strong>Phase 1 & 2 Features Implemented:</strong></p>
            <ul style="margin-left: 2rem; color: #666; line-height: 1.8;">
                <li>✅ Distance-based filtering (5km default, 20km max)</li>
                <li>✅ Smart ranking algorithm with 4 factors:
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>Distance (40%): Closer rooms ranked higher</li>
                        <li>Price match (30%): Rooms matching budget preferences</li>
                        <li>Amenities match (20%): WiFi, AC, kitchen, etc.</li>
                        <li>Popularity (10%): Based on likes count</li>
                    </ul>
                </li>
                <li>✅ District center geocoding (pre-configured for HN, HCM, DN)</li>
                <li>✅ Room address geocoding via Mapbox</li>
                <li>✅ Distance display on room cards</li>
            </ul>

            <p style="margin-top: 1rem;"><strong>Next Steps:</strong></p>
            <ul style="margin-left: 2rem; color: #666; line-height: 1.8;">
                <li>Add user preferences UI for max_distance, area range</li>
                <li>Implement room image upload</li>
                <li>Implement user avatar upload</li>
            </ul>
        </div>
    </div>
</body>
</html>

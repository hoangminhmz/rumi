<?php
/**
 * RUMI - Admin Dashboard
 * Overview of key metrics and quick actions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Check admin auth
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();

// Get stats
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'total_rooms' => $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'active_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'active'")->fetchColumn(),
    'total_matches' => $db->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
    'pending_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'pending_payment'")->fetchColumn(),
];

// Recent users
$recentUsers = $db->query("
    SELECT id, name, gender, age, created_at, is_active
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

// Recent rooms
$recentRooms = $db->query("
    SELECT r.id, r.title, r.price, r.status, r.created_at, u.name as owner_name
    FROM rooms r
    LEFT JOIN users u ON r.owner_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RUMI Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f7;
        }

        .admin-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .admin-nav {
            display: flex;
            gap: 1.5rem;
        }

        .admin-nav a {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .admin-nav a:hover {
            color: #667eea;
        }

        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .content-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .table {
            width: 100%;
        }

        .table th {
            text-align: left;
            padding: 1rem 1.5rem;
            background: #f9fafb;
            color: #6b7280;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            color: #374151;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-gray {
            background: #e5e7eb;
            color: #374151;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-logo">🏠 RUMI Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="rooms.php">Rooms</a>
            <a href="matches.php">Matches</a>
            <a href="swipes.php">Swipes</a>
            <a href="amenities.php">Amenities</a>
            <a href="preferences.php">Preferences</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <h1 class="admin-title">Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label" style="margin-top: 0.5rem;">
                    <?= $stats['active_users'] ?> active
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Rooms</div>
                <div class="stat-value"><?= number_format($stats['total_rooms']) ?></div>
                <div class="stat-label" style="margin-top: 0.5rem;">
                    <?= $stats['active_rooms'] ?> active
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Matches</div>
                <div class="stat-value"><?= number_format($stats['total_matches']) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending Rooms</div>
                <div class="stat-value"><?= number_format($stats['pending_rooms']) ?></div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Recent Users -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">Recent Users</h2>
                    <a href="users.php" class="btn-sm">View All</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= ucfirst($user['gender']) ?></td>
                            <td><?= $user['age'] ?></td>
                            <td>
                                <span class="badge <?= $user['is_active'] ? 'badge-success' : 'badge-gray' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Rooms -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">Recent Rooms</h2>
                    <a href="rooms.php" class="btn-sm">View All</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Owner</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars(truncate($room['title'], 30)) ?></td>
                            <td><?= formatPrice($room['price']) ?></td>
                            <td><?= htmlspecialchars($room['owner_name'] ?? 'N/A') ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-gray';
                                if ($room['status'] === 'active') $badgeClass = 'badge-success';
                                elseif ($room['status'] === 'pending_payment') $badgeClass = 'badge-warning';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucwords(str_replace('_', ' ', $room['status'])) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

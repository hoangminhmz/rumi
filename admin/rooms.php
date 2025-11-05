<?php
/**
 * RUMI - Admin Rooms Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $roomId = (int)$_POST['room_id'];

    switch ($_POST['action']) {
        case 'activate':
            $db->prepare("UPDATE rooms SET status = 'active', payment_status = 'paid', expired_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?")->execute([$roomId]);
            setFlash('success', 'Room activated');
            break;

        case 'deactivate':
            $db->prepare("UPDATE rooms SET status = 'inactive' WHERE id = ?")->execute([$roomId]);
            setFlash('success', 'Room deactivated');
            break;

        case 'delete':
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM room_swipes WHERE room_id = ?")->execute([$roomId]);
                $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);
                $db->commit();
                setFlash('success', 'Room deleted');
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('error', 'Failed to delete: ' . $e->getMessage());
            }
            break;
    }
    redirect(BASE_URL . '/admin/rooms.php');
}

// Filters
$where = ['1=1'];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "(r.title LIKE ? OR r.address LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "r.status = ?";
    $params[] = $_GET['status'];
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT COUNT(*) FROM rooms r WHERE $whereClause");
$stmt->execute($params);
$totalRooms = $stmt->fetchColumn();
$totalPages = ceil($totalRooms / $perPage);

// Get rooms
$stmt = $db->prepare("
    SELECT r.*, u.name as owner_name, u.phone as owner_phone, d.name as district_name,
           (SELECT COUNT(*) FROM room_swipes WHERE room_id = r.id AND is_like = 1) as likes_count
    FROM rooms r
    LEFT JOIN users u ON r.owner_id = u.id
    LEFT JOIN districts d ON r.district_id = d.id
    WHERE $whereClause
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management - RUMI Admin</title>
    <link rel="stylesheet" href="users.php" style="display:none;">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f7; }
        .admin-header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { font-size: 1.5rem; font-weight: 700; color: #667eea; }
        .admin-nav { display: flex; gap: 1.5rem; }
        .admin-nav a { color: #374151; text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .admin-nav a:hover, .admin-nav a.active { color: #667eea; }
        .admin-container { max-width: 1600px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 700; color: #111827; }
        .filters { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .data-table { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.85rem; }
        td { padding: 1rem; border-top: 1px solid #e5e7eb; color: #374151; font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #e5e7eb; color: #374151; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .room-title { font-weight: 600; color: #111827; margin-bottom: 0.25rem; }
        .room-meta { font-size: 0.85rem; color: #6b7280; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; }
        .pagination a, .pagination span { padding: 0.5rem 1rem; background: white; border-radius: 6px; text-decoration: none; color: #374151; font-weight: 500; }
        .pagination a:hover { background: #667eea; color: white; }
        .pagination .current { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-logo">🏠 RUMI Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="rooms.php" class="active">Rooms</a>
            <a href="matches.php">Matches</a>
            <a href="swipes.php">Swipes</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Rooms Management</h1>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Title, address..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="active" <?= ($_GET['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="pending_payment" <?= ($_GET['status'] ?? '') == 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="inactive" <?= ($_GET['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Owner</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Likes</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= $room['id'] ?></td>
                        <td>
                            <div class="room-title"><?= htmlspecialchars(truncate($room['title'], 40)) ?></div>
                            <div class="room-meta"><?= htmlspecialchars(truncate($room['address'], 50)) ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($room['owner_name'] ?? 'N/A') ?></div>
                            <div class="room-meta"><?= htmlspecialchars($room['owner_phone'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($room['district_name'] ?? 'N/A') ?></td>
                        <td><strong><?= formatPrice($room['price']) ?></strong></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-gray';
                            if ($room['status'] === 'active') $badgeClass = 'badge-success';
                            elseif ($room['status'] === 'pending_payment') $badgeClass = 'badge-warning';
                            elseif ($room['status'] === 'inactive') $badgeClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucwords(str_replace('_', ' ', $room['status'])) ?>
                            </span>
                        </td>
                        <td><?= $room['likes_count'] ?></td>
                        <td><?= date('M d, Y', strtotime($room['created_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <?php if ($room['status'] !== 'active'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-sm" style="background: #10b981; color: white;">Activate</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" class="btn btn-sm" style="background: #f59e0b; color: white;">Deactivate</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this room?');">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">← Previous</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * RUMI - Admin Swipes History
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Filters
$where = [];
$params = [];

if (!empty($_GET['user_search'])) {
    $where[] = "u.name LIKE ?";
    $params[] = '%' . $_GET['user_search'] . '%';
}

if (isset($_GET['is_like']) && $_GET['is_like'] !== '') {
    $where[] = "s.is_like = ?";
    $params[] = (int)$_GET['is_like'];
}

if (isset($_GET['type']) && $_GET['type'] !== '') {
    $tableName = $_GET['type'] === 'user' ? 'user_swipes' : 'room_swipes';
} else {
    $tableName = 'user_swipes';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get swipes based on type
if ($tableName === 'user_swipes') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_swipes s LEFT JOIN users u ON s.user_id = u.id $whereClause");
    $stmt->execute($params);
    $totalSwipes = $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT s.*, u.name as user_name, tu.name as target_name
        FROM user_swipes s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN users tu ON s.target_user_id = tu.id
        $whereClause
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $swipes = $stmt->fetchAll();
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM room_swipes s LEFT JOIN users u ON s.user_id = u.id $whereClause");
    $stmt->execute($params);
    $totalSwipes = $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT s.*, u.name as user_name, r.title as room_title
        FROM room_swipes s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN rooms r ON s.room_id = r.id
        $whereClause
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $swipes = $stmt->fetchAll();
}

$totalPages = ceil($totalSwipes / $perPage);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipes History - RUMI Admin</title>
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
        .filters-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #667eea; color: white; }
        .data-table { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.85rem; }
        td { padding: 1rem; border-top: 1px solid #e5e7eb; color: #374151; font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-like { background: #d1fae5; color: #065f46; }
        .badge-pass { background: #fee2e2; color: #991b1b; }
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
            <a href="rooms.php">Rooms</a>
            <a href="matches.php">Matches</a>
            <a href="swipes.php" class="active">Swipes</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Swipes History</h1>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Swipe Type</label>
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="user" <?= ($_GET['type'] ?? 'user') == 'user' ? 'selected' : '' ?>>User Swipes</option>
                            <option value="room" <?= ($_GET['type'] ?? '') == 'room' ? 'selected' : '' ?>>Room Swipes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search User</label>
                        <input type="text" name="user_search" class="form-control" placeholder="User name..." value="<?= htmlspecialchars($_GET['user_search'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select name="is_like" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="1" <?= isset($_GET['is_like']) && $_GET['is_like'] == '1' ? 'selected' : '' ?>>Likes</option>
                            <option value="0" <?= isset($_GET['is_like']) && $_GET['is_like'] == '0' ? 'selected' : '' ?>>Passes</option>
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
                        <th>User</th>
                        <th><?= $tableName === 'user_swipes' ? 'Target User' : 'Room' ?></th>
                        <th>Action</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($swipes as $swipe): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($swipe['user_name']) ?></strong></td>
                        <td>
                            <?php if ($tableName === 'user_swipes'): ?>
                                <?= htmlspecialchars($swipe['target_name'] ?? 'Deleted User') ?>
                            <?php else: ?>
                                <?= htmlspecialchars($swipe['room_title'] ?? 'Deleted Room') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $swipe['is_like'] ? 'badge-like' : 'badge-pass' ?>">
                                <?= $swipe['is_like'] ? '👍 Like' : '👎 Pass' ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($swipe['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&type=<?= $_GET['type'] ?? 'user' ?>">← Previous</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&type=<?= $_GET['type'] ?? 'user' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&type=<?= $_GET['type'] ?? 'user' ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

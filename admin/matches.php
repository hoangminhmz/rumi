<?php
/**
 * RUMI - Admin Matches Management
 * View matches and create manual matches
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Match.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect(BASE_URL . '/admin/login.php');
}

$db = getDB();
$matchModel = new MatchModel();

// Handle create manual match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_match') {
    $user1Id = (int)$_POST['user1_id'];
    $user2Id = (int)$_POST['user2_id'];
    $roomId = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;

    if ($user1Id && $user2Id && $user1Id !== $user2Id) {
        $matchId = $matchModel->create($user1Id, $user2Id, $roomId);
        if ($matchId) {
            setFlash('success', 'Match created successfully');
        } else {
            setFlash('error', 'Failed to create match');
        }
    } else {
        setFlash('error', 'Invalid user selection');
    }
    redirect(BASE_URL . '/admin/matches.php');
}

// Handle delete match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $matchId = (int)$_POST['match_id'];
    $stmt = $db->prepare("DELETE FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    setFlash('success', 'Match deleted');
    redirect(BASE_URL . '/admin/matches.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "(u1.name LIKE ? OR u2.name LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "m.status = ?";
    $params[] = $_GET['status'];
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT COUNT(*) FROM matches m LEFT JOIN users u1 ON m.user1_id = u1.id LEFT JOIN users u2 ON m.user2_id = u2.id $whereClause");
$stmt->execute($params);
$totalMatches = $stmt->fetchColumn();
$totalPages = ceil($totalMatches / $perPage);

// Get matches
$stmt = $db->prepare("
    SELECT m.*,
           u1.name as user1_name, u1.age as user1_age, u1.gender as user1_gender,
           u2.name as user2_name, u2.age as user2_age, u2.gender as user2_gender,
           r.title as room_title, r.price as room_price
    FROM matches m
    LEFT JOIN users u1 ON m.user1_id = u1.id
    LEFT JOIN users u2 ON m.user2_id = u2.id
    LEFT JOIN rooms r ON m.room_id = r.id
    $whereClause
    ORDER BY m.matched_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$matches = $stmt->fetchAll();

// Get all active users for manual match creation
$allUsers = $db->query("
    SELECT id, name, age, gender
    FROM users
    WHERE is_active = 1
    ORDER BY name
    LIMIT 500
")->fetchAll();

// Get all active rooms for manual match creation
$allRooms = $db->query("
    SELECT id, title, price
    FROM rooms
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 200
")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matches Management - RUMI Admin</title>
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
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .create-match-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .create-match-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 150px; gap: 1rem; align-items: end; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .filters { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
        .data-table { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.85rem; }
        td { padding: 1rem; border-top: 1px solid #e5e7eb; color: #374151; font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #e5e7eb; color: #374151; }
        .match-users { display: flex; align-items: center; gap: 0.5rem; }
        .user-badge { background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 500; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
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
            <a href="matches.php" class="active">Matches</a>
            <a href="swipes.php">Swipes</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Matches Management</h1>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Create Manual Match -->
        <div class="create-match-card">
            <h2 class="create-match-title">Create Manual Match</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_match">
                <div class="form-grid">
                    <div class="form-group">
                        <label>User 1</label>
                        <select name="user1_id" class="form-control" required>
                            <option value="">Select User 1</option>
                            <?php foreach ($allUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= $user['age'] ?>, <?= $user['gender'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>User 2</label>
                        <select name="user2_id" class="form-control" required>
                            <option value="">Select User 2</option>
                            <?php foreach ($allUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= $user['age'] ?>, <?= $user['gender'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room (Optional)</label>
                        <select name="room_id" class="form-control">
                            <option value="">No Room</option>
                            <?php foreach ($allRooms as $room): ?>
                                <option value="<?= $room['id'] ?>">
                                    <?= htmlspecialchars(truncate($room['title'], 30)) ?> (<?= formatPrice($room['price']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Match</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="User name..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="connected" <?= ($_GET['status'] ?? '') == 'connected' ? 'selected' : '' ?>>Connected</option>
                            <option value="disconnected" <?= ($_GET['status'] ?? '') == 'disconnected' ? 'selected' : '' ?>>Disconnected</option>
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
                        <th>Users</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Matched At</th>
                        <th>Connected At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?= $match['id'] ?></td>
                        <td>
                            <div class="match-users">
                                <span class="user-badge"><?= htmlspecialchars($match['user1_name']) ?></span>
                                <span style="color: #6b7280;">↔</span>
                                <span class="user-badge"><?= htmlspecialchars($match['user2_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($match['room_title']): ?>
                                <div><strong><?= htmlspecialchars(truncate($match['room_title'], 30)) ?></strong></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?= formatPrice($match['room_price']) ?></div>
                            <?php else: ?>
                                <span class="badge badge-gray">No Room</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badgeClass = 'badge-gray';
                            if ($match['status'] === 'connected') $badgeClass = 'badge-success';
                            elseif ($match['status'] === 'pending') $badgeClass = 'badge-warning';
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucfirst($match['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($match['matched_at'])) ?></td>
                        <td>
                            <?= $match['connected_at'] ? date('M d, Y H:i', strtotime($match['connected_at'])) : '-' ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this match?');">
                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
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

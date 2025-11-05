<?php
/**
 * RUMI - Admin Users Management
 * View, edit, delete, verify users
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];

    switch ($_POST['action']) {
        case 'toggle_active':
            $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('success', 'User status updated');
            break;

        case 'delete':
            // Delete user and related data
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM user_swipes WHERE user_id = ?")->execute([$userId]);
                $db->prepare("DELETE FROM room_swipes WHERE user_id = ?")->execute([$userId]);
                $db->prepare("DELETE FROM matches WHERE user1_id = ? OR user2_id = ?")->execute([$userId, $userId]);
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                $db->commit();
                setFlash('success', 'User deleted successfully');
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('error', 'Failed to delete user: ' . $e->getMessage());
            }
            break;

        case 'verify':
            $stmt = $db->prepare("UPDATE users SET verification_status = 'verified', id_verified = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('success', 'User verified');
            break;
    }

    redirect(BASE_URL . '/admin/users.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "(name LIKE ? OR phone LIKE ? OR zalo_id LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$_GET['status'];
}

if (isset($_GET['gender']) && $_GET['gender'] !== '') {
    $where[] = "gender = ?";
    $params[] = $_GET['gender'];
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM users $whereClause");
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $db->prepare("
    SELECT u.*, d.name as district_name, d.city_name,
           (SELECT COUNT(*) FROM matches WHERE user1_id = u.id OR user2_id = u.id) as match_count
    FROM users u
    LEFT JOIN districts d ON u.district_id = d.id
    $whereClause
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - RUMI Admin</title>
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

        .admin-nav a:hover,
        .admin-nav a.active {
            color: #667eea;
        }

        .admin-container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f9fafb;
            color: #6b7280;
            font-weight: 600;
            font-size: 0.85rem;
        }

        td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            color: #374151;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6b7280;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .user-meta {
            font-size: 0.85rem;
            color: #6b7280;
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

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-gray {
            background: #e5e7eb;
            color: #374151;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .current {
            background: #667eea;
            color: white;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-logo">🏠 RUMI Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">Users</a>
            <a href="rooms.php">Rooms</a>
            <a href="matches.php">Matches</a>
            <a href="swipes.php">Swipes</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Users Management</h1>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Name, phone, Zalo ID..."
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="1" <?= isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= isset($_GET['status']) && $_GET['status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">All</option>
                            <option value="male" <?= ($_GET['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($_GET['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= ($_GET['gender'] ?? '') == 'other' ? 'selected' : '' ?>>Other</option>
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
                        <th>User</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Matches</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="user-meta">
                                        <?= ucfirst($user['gender']) ?>, <?= $user['age'] ?> tuổi
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($user['district_name']): ?>
                                <?= htmlspecialchars($user['district_name']) ?>, <?= htmlspecialchars($user['city_name']) ?>
                            <?php else: ?>
                                <span class="badge badge-gray">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge <?= $user['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['verification_status'] == 'verified'): ?>
                                <span class="badge badge-success">✓ Verified</span>
                            <?php elseif ($user['verification_status'] == 'pending'): ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Unverified</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['match_count'] ?></td>
                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle user status?');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <button type="submit" class="btn btn-sm" style="background: #f59e0b; color: white;">
                                        <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($user['verification_status'] != 'verified'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit" class="btn btn-sm" style="background: #10b981; color: white;">Verify</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user? This cannot be undone!');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
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
                <a href="?page=<?= $page - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">← Previous</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit filters on change
        document.querySelectorAll('.filters select').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });
    </script>
</body>
</html>

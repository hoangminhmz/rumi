<?php
/**
 * RUMI - Admin Amenities Management
 * Manage room amenities list (wifi, AC, kitchen, etc.)
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $stmt = $db->prepare("
                INSERT INTO amenities_list (code, name_vi, name_en, icon, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $_POST['code'],
                $_POST['name_vi'],
                $_POST['name_en'],
                $_POST['icon'],
                (int)$_POST['sort_order']
            ]);
            setFlash('success', 'Amenity added successfully');
            break;

        case 'edit':
            $stmt = $db->prepare("
                UPDATE amenities_list
                SET code = ?, name_vi = ?, name_en = ?, icon = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['code'],
                $_POST['name_vi'],
                $_POST['name_en'],
                $_POST['icon'],
                (int)$_POST['sort_order'],
                (int)$_POST['id']
            ]);
            setFlash('success', 'Amenity updated successfully');
            break;

        case 'toggle':
            $stmt = $db->prepare("UPDATE amenities_list SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            setFlash('success', 'Status updated');
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM amenities_list WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            setFlash('success', 'Amenity deleted');
            break;
    }

    redirect(BASE_URL . '/admin/amenities.php');
}

// Get all amenities
$amenities = $db->query("
    SELECT * FROM amenities_list
    ORDER BY sort_order ASC, name_vi ASC
")->fetchAll();

// Get edit item if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM amenities_list WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amenities Management - RUMI Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f7; }
        .admin-header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { font-size: 1.5rem; font-weight: 700; color: #667eea; }
        .admin-nav { display: flex; gap: 1.5rem; }
        .admin-nav a { color: #374151; text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .admin-nav a:hover, .admin-nav a.active { color: #667eea; }
        .admin-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 700; color: #111827; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .form-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .form-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 100px 100px 150px; gap: 1rem; align-items: end; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
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
        .icon-display { font-size: 1.5rem; }
        .actions { display: flex; gap: 0.5rem; }
        .info-box { background: #e0e7ff; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; color: #3730a3; }
        .info-box strong { display: block; margin-bottom: 0.5rem; }
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
            <a href="amenities.php" class="active">Amenities</a>
            <a href="preferences.php">Preferences</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">🏘️ Amenities Management</h1>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ About Amenities</strong>
            Amenities are room features that users can filter by when searching for rooms.
            Examples: wifi, AC, kitchen, parking, etc.
            These appear as checkboxes in room search filters and room posting forms.
        </div>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h2 class="form-title"><?= $editItem ? '✏️ Edit Amenity' : '➕ Add New Amenity' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Code (unique key) *</label>
                        <input type="text" name="code" class="form-control"
                               value="<?= htmlspecialchars($editItem['code'] ?? '') ?>"
                               placeholder="wifi" required>
                    </div>

                    <div class="form-group">
                        <label>Name (Vietnamese) *</label>
                        <input type="text" name="name_vi" class="form-control"
                               value="<?= htmlspecialchars($editItem['name_vi'] ?? '') ?>"
                               placeholder="Wifi" required>
                    </div>

                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name_en" class="form-control"
                               value="<?= htmlspecialchars($editItem['name_en'] ?? '') ?>"
                               placeholder="Wifi" required>
                    </div>

                    <div class="form-group">
                        <label>Icon (emoji)</label>
                        <input type="text" name="icon" class="form-control"
                               value="<?= htmlspecialchars($editItem['icon'] ?? '') ?>"
                               placeholder="📶" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="<?= $editItem['sort_order'] ?? 0 ?>"
                               min="0" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <?= $editItem ? '💾 Update' : '➕ Add' ?>
                        </button>
                    </div>
                </div>

                <?php if ($editItem): ?>
                    <div style="margin-top: 1rem;">
                        <a href="amenities.php" class="btn btn-sm" style="background: #6b7280; color: white;">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Icon</th>
                        <th>Code</th>
                        <th>Vietnamese Name</th>
                        <th>English Name</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($amenities)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem; color: #6b7280;">
                                No amenities found. Add your first amenity above.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($amenities as $amenity): ?>
                        <tr>
                            <td><?= $amenity['id'] ?></td>
                            <td class="icon-display"><?= htmlspecialchars($amenity['icon']) ?></td>
                            <td><code><?= htmlspecialchars($amenity['code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($amenity['name_vi']) ?></strong></td>
                            <td><?= htmlspecialchars($amenity['name_en']) ?></td>
                            <td><?= $amenity['sort_order'] ?></td>
                            <td>
                                <span class="badge <?= $amenity['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $amenity['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($amenity['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?= $amenity['id'] ?>" class="btn btn-primary btn-sm">Edit</a>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= $amenity['id'] ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <button type="submit" class="btn btn-sm" style="background: #f59e0b; color: white;">
                                            <?= $amenity['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this amenity? This cannot be undone!');">
                                        <input type="hidden" name="id" value="<?= $amenity['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1rem; color: #111827;">💡 Common Amenities</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">Suggested amenities you can add:</p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">📶 wifi - Wifi</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">❄️ ac - Điều hòa / AC</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🍳 kitchen - Bếp / Kitchen</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🅿️ parking - Chỗ đỗ xe / Parking</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🧺 laundry - Máy giặt / Washing Machine</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🛋️ furniture - Nội thất / Furniture</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🛗 elevator - Thang máy / Elevator</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🔒 security - An ninh / Security</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🌿 balcony - Ban công / Balcony</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">💪 gym - Phòng gym / Gym</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🏊 pool - Hồ bơi / Swimming Pool</span>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">🐕 pet_friendly - Cho phép thú cưng / Pet Friendly</span>
            </div>
        </div>
    </div>
</body>
</html>

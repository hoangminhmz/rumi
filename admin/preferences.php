<?php
/**
 * RUMI - Admin Preferences Management
 * Manage matching preferences list (cleanliness, noise tolerance, etc.)
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
                INSERT INTO preferences_list (code, name_vi, name_en, icon, weight, category, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $_POST['code'],
                $_POST['name_vi'],
                $_POST['name_en'],
                $_POST['icon'],
                (int)$_POST['weight'],
                $_POST['category']
            ]);
            setFlash('success', 'Preference added successfully');
            break;

        case 'edit':
            $stmt = $db->prepare("
                UPDATE preferences_list
                SET code = ?, name_vi = ?, name_en = ?, icon = ?, weight = ?, category = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['code'],
                $_POST['name_vi'],
                $_POST['name_en'],
                $_POST['icon'],
                (int)$_POST['weight'],
                $_POST['category'],
                (int)$_POST['id']
            ]);
            setFlash('success', 'Preference updated successfully');
            break;

        case 'toggle':
            $stmt = $db->prepare("UPDATE preferences_list SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            setFlash('success', 'Status updated');
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM preferences_list WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            setFlash('success', 'Preference deleted');
            break;
    }

    redirect(BASE_URL . '/admin/preferences.php');
}

// Get all preferences
$preferences = $db->query("
    SELECT * FROM preferences_list
    ORDER BY category ASC, weight DESC, name_vi ASC
")->fetchAll();

// Get edit item if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM preferences_list WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Group by category for display
$grouped = [];
foreach ($preferences as $pref) {
    $cat = $pref['category'] ?? 'other';
    if (!isset($grouped[$cat])) {
        $grouped[$cat] = [];
    }
    $grouped[$cat][] = $pref;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferences Management - RUMI Admin</title>
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
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 100px 100px 120px 150px; gap: 1rem; align-items: end; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .category-section { margin-bottom: 2rem; }
        .category-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px 12px 0 0; font-size: 1.2rem; font-weight: 700; }
        .data-table { background: white; border-radius: 0 0 12px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.85rem; }
        td { padding: 1rem; border-top: 1px solid #e5e7eb; color: #374151; font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-category { background: #e0e7ff; color: #3730a3; }
        .icon-display { font-size: 1.5rem; }
        .actions { display: flex; gap: 0.5rem; }
        .info-box { background: #e0e7ff; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; color: #3730a3; }
        .info-box strong { display: block; margin-bottom: 0.5rem; }
        .weight-indicator { display: inline-block; padding: 0.25rem 0.5rem; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
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
            <a href="preferences.php" class="active">Preferences</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">⚖️ Preferences Management</h1>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ About Preferences</strong>
            Preferences are criteria used for matching users (roommates).
            Each preference has a weight that determines its importance in matching algorithm.
            Higher weight = more important for compatibility scoring.
        </div>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h2 class="form-title"><?= $editItem ? '✏️ Edit Preference' : '➕ Add New Preference' ?></h2>
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
                               placeholder="cleanliness" required>
                    </div>

                    <div class="form-group">
                        <label>Name (Vietnamese) *</label>
                        <input type="text" name="name_vi" class="form-control"
                               value="<?= htmlspecialchars($editItem['name_vi'] ?? '') ?>"
                               placeholder="Sạch sẽ" required>
                    </div>

                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name_en" class="form-control"
                               value="<?= htmlspecialchars($editItem['name_en'] ?? '') ?>"
                               placeholder="Cleanliness" required>
                    </div>

                    <div class="form-group">
                        <label>Icon (emoji)</label>
                        <input type="text" name="icon" class="form-control"
                               value="<?= htmlspecialchars($editItem['icon'] ?? '') ?>"
                               placeholder="✨" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label>Weight (0-100)</label>
                        <input type="number" name="weight" class="form-control"
                               value="<?= $editItem['weight'] ?? 25 ?>"
                               min="0" max="100" required>
                        <small style="color: #6b7280; font-size: 0.75rem;">Higher = more important</small>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" class="form-control" required>
                            <option value="lifestyle" <?= ($editItem['category'] ?? '') == 'lifestyle' ? 'selected' : '' ?>>Lifestyle</option>
                            <option value="financial" <?= ($editItem['category'] ?? '') == 'financial' ? 'selected' : '' ?>>Financial</option>
                            <option value="location" <?= ($editItem['category'] ?? '') == 'location' ? 'selected' : '' ?>>Location</option>
                            <option value="other" <?= ($editItem['category'] ?? '') == 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <?= $editItem ? '💾 Update' : '➕ Add' ?>
                        </button>
                    </div>
                </div>

                <?php if ($editItem): ?>
                    <div style="margin-top: 1rem;">
                        <a href="preferences.php" class="btn btn-sm" style="background: #6b7280; color: white;">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Data Tables by Category -->
        <?php if (empty($preferences)): ?>
            <div class="data-table" style="border-radius: 12px;">
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">No preferences found</div>
                    <div>Add your first preference using the form above.</div>
                </div>
            </div>
        <?php else: ?>
            <?php
            $categoryNames = [
                'lifestyle' => '🏠 Lifestyle Preferences',
                'financial' => '💰 Financial Preferences',
                'location' => '📍 Location Preferences',
                'other' => '📌 Other Preferences'
            ];
            ?>

            <?php foreach ($grouped as $category => $items): ?>
            <div class="category-section">
                <div class="category-header">
                    <?= $categoryNames[$category] ?? ucfirst($category) ?>
                    <span style="opacity: 0.8; font-size: 0.9rem; font-weight: normal; margin-left: 1rem;">
                        (<?= count($items) ?> items)
                    </span>
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Code</th>
                                <th>Vietnamese Name</th>
                                <th>English Name</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $pref): ?>
                            <tr>
                                <td><?= $pref['id'] ?></td>
                                <td class="icon-display"><?= htmlspecialchars($pref['icon']) ?></td>
                                <td><code><?= htmlspecialchars($pref['code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($pref['name_vi']) ?></strong></td>
                                <td><?= htmlspecialchars($pref['name_en']) ?></td>
                                <td>
                                    <span class="weight-indicator"><?= $pref['weight'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $pref['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $pref['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($pref['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit=<?= $pref['id'] ?>" class="btn btn-primary btn-sm">Edit</a>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $pref['id'] ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <button type="submit" class="btn btn-sm" style="background: #f59e0b; color: white;">
                                                <?= $pref['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this preference?');">
                                            <input type="hidden" name="id" value="<?= $pref['id'] ?>">
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
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 2rem; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 1rem; color: #111827;">💡 Common Preferences</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">Suggested preferences you can add:</p>

            <div style="margin-bottom: 1.5rem;">
                <strong style="color: #111827; display: block; margin-bottom: 0.5rem;">🏠 Lifestyle:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">✨ cleanliness (25) - Sạch sẽ</span>
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">🔊 noise_tolerance (25) - Độ ồn</span>
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">😴 sleep_schedule (20) - Lịch ngủ</span>
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">🚬 smoking (15) - Hút thuốc</span>
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">🍺 drinking (10) - Uống rượu</span>
                    <span class="badge" style="background: #e0e7ff; color: #3730a3;">👥 guests_policy (5) - Chính sách khách</span>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <strong style="color: #111827; display: block; margin-bottom: 0.5rem;">💰 Financial:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <span class="badge" style="background: #fef3c7; color: #92400e;">💰 budget (30) - Ngân sách</span>
                </div>
            </div>

            <div>
                <strong style="color: #111827; display: block; margin-bottom: 0.5rem;">📍 Location:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <span class="badge" style="background: #dbeafe; color: #1e40af;">📍 location (25) - Vị trí</span>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <strong style="color: #111827;">⚖️ Weight Guide:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem; color: #6b7280;">
                    <li><strong>25-30:</strong> Very important (cleanliness, noise, budget)</li>
                    <li><strong>15-20:</strong> Important (sleep schedule, smoking)</li>
                    <li><strong>5-10:</strong> Nice to match (drinking, guests)</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/**
 * RUMI - Reset Swipes Tool
 * Xóa lịch sử swipe để test lại
 * ⚠️ TESTING ONLY - XÓA FILE NÀY KHI PRODUCTION!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

// Check if logged in
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Bạn phải login trước!</p>";
    echo "<p><a href='pages/login-bypass.php'>Login qua Bypass</a></p>";
    exit;
}

$userId = getCurrentUserId();
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'reset_user_swipes':
                $stmt = $db->prepare("DELETE FROM user_swipes WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->rowCount();
                $message = "✅ Đã xóa $count lượt swipe users của bạn!";
                break;

            case 'reset_room_swipes':
                $stmt = $db->prepare("DELETE FROM room_swipes WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->rowCount();
                $message = "✅ Đã xóa $count lượt swipe rooms của bạn!";
                break;

            case 'reset_all_swipes':
                $stmt1 = $db->prepare("DELETE FROM user_swipes WHERE user_id = ?");
                $stmt1->execute([$userId]);
                $count1 = $stmt1->rowCount();

                $stmt2 = $db->prepare("DELETE FROM room_swipes WHERE user_id = ?");
                $stmt2->execute([$userId]);
                $count2 = $stmt2->rowCount();

                $message = "✅ Đã xóa $count1 lượt swipe users và $count2 lượt swipe rooms!";
                break;

            case 'reset_matches':
                $stmt = $db->prepare("
                    DELETE FROM matches
                    WHERE user1_id = ? OR user2_id = ?
                ");
                $stmt->execute([$userId, $userId]);
                $count = $stmt->rowCount();
                $message = "✅ Đã xóa $count matches của bạn!";
                break;

            case 'reset_everything':
                // Delete user swipes
                $stmt1 = $db->prepare("DELETE FROM user_swipes WHERE user_id = ?");
                $stmt1->execute([$userId]);
                $count1 = $stmt1->rowCount();

                // Delete room swipes
                $stmt2 = $db->prepare("DELETE FROM room_swipes WHERE user_id = ?");
                $stmt2->execute([$userId]);
                $count2 = $stmt2->rowCount();

                // Delete matches
                $stmt3 = $db->prepare("DELETE FROM matches WHERE user1_id = ? OR user2_id = ?");
                $stmt3->execute([$userId, $userId]);
                $count3 = $stmt3->rowCount();

                $message = "✅ Đã reset toàn bộ: $count1 user swipes, $count2 room swipes, $count3 matches!";
                break;

            default:
                $message = "❌ Invalid action";
        }
    } catch (PDOException $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
    }
}

// Get current stats
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userSwipeCount = $stmt->fetch()['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM room_swipes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $roomSwipeCount = $stmt->fetch()['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM matches WHERE user1_id = ? OR user2_id = ?");
    $stmt->execute([$userId, $userId]);
    $matchCount = $stmt->fetch()['count'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Swipes - RUMI</title>
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    <style>
        body {
            padding: 2rem;
            background: #f9fafb;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        .stat-card {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #00D4AA;
        }
        .reset-btn {
            width: 100%;
            padding: 1rem;
            margin-top: 0.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-warning:hover {
            background: #d97706;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #00D4AA; margin-bottom: 1rem;">🔄 Reset Swipes Tool</h1>

        <div class="warning-box">
            <strong>⚠️ LƯU Ý:</strong> Tool này chỉ để TEST! Xóa file này khi đưa lên production!
        </div>

        <?php if (isset($message)): ?>
        <div class="message success"><?= $message ?></div>
        <?php endif; ?>

        <h2 style="margin-top: 2rem; margin-bottom: 1rem;">📊 Thống kê hiện tại</h2>

        <div class="stat-card">
            <div>
                <div style="font-weight: 500;">User Swipes</div>
                <small style="color: #6b7280;">Số lượt swipe người dùng</small>
            </div>
            <div class="stat-number"><?= $userSwipeCount ?></div>
        </div>

        <div class="stat-card">
            <div>
                <div style="font-weight: 500;">Room Swipes</div>
                <small style="color: #6b7280;">Số lượt swipe phòng</small>
            </div>
            <div class="stat-number"><?= $roomSwipeCount ?></div>
        </div>

        <div class="stat-card">
            <div>
                <div style="font-weight: 500;">Matches</div>
                <small style="color: #6b7280;">Số matches</small>
            </div>
            <div class="stat-number"><?= $matchCount ?></div>
        </div>

        <h2 style="margin-top: 2rem; margin-bottom: 1rem;">🗑️ Reset Options</h2>

        <form method="POST">
            <button type="submit" name="action" value="reset_user_swipes" class="reset-btn btn-warning">
                Xóa User Swipes (<?= $userSwipeCount ?>)
            </button>
        </form>

        <form method="POST">
            <button type="submit" name="action" value="reset_room_swipes" class="reset-btn btn-warning">
                Xóa Room Swipes (<?= $roomSwipeCount ?>)
            </button>
        </form>

        <form method="POST">
            <button type="submit" name="action" value="reset_all_swipes" class="reset-btn btn-warning">
                Xóa TẤT CẢ Swipes (<?= $userSwipeCount + $roomSwipeCount ?>)
            </button>
        </form>

        <form method="POST">
            <button type="submit" name="action" value="reset_matches" class="reset-btn btn-warning">
                Xóa Matches (<?= $matchCount ?>)
            </button>
        </form>

        <form method="POST" onsubmit="return confirm('⚠️ BẠN CHẮC CHẮN? Sẽ xóa TẤT CẢ swipes và matches!')">
            <button type="submit" name="action" value="reset_everything" class="reset-btn btn-danger">
                ⚠️ XÓA TẤT CẢ (RESET TOÀN BỘ)
            </button>
        </form>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <a href="<?= BASE_URL ?>/pages/swipe.php" class="btn btn-primary" style="display: block; text-align: center; padding: 1rem; text-decoration: none;">
                ← Quay lại Swipe
            </a>
        </div>
    </div>
</body>
</html>

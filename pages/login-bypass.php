<?php
/**
 * BYPASS LOGIN - Chỉ dùng để test, XÓA khi production!
 * Tạo fake user và login trực tiếp
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';

startSession();

$pageTitle = 'Bypass Login (TEST ONLY)';
include __DIR__ . '/../components/header.php';
?>

<div class="container container-xs" style="padding-top: var(--space-4);">
    <div class="card">
        <div class="card-body p-4">
            <h2 class="text-center mb-3" style="color: #f59e0b;">⚠️ BYPASS LOGIN - TEST ONLY</h2>
            <p class="text-center text-secondary mb-4">Chỉ dùng để test app khi chưa setup Zalo</p>

            <?php
            // Create fake test user
            if (isset($_GET['action']) && $_GET['action'] === 'create') {
                try {
                    $db = getDB();

                    // Check if test user exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE zalo_id = ?");
                    $stmt->execute(['test_user_123']);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        $userId = $existing['id'];
                        echo "<div class='alert alert-info'>Test user already exists. Using existing user.</div>";
                    } else {
                        // Create test user
                        $stmt = $db->prepare("
                            INSERT INTO users (zalo_id, name, phone, gender, age, district_id, avatar, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            'test_user_123',
                            'Test User',
                            '0900000000',
                            'other',
                            25,
                            1,
                            null
                        ]);
                        $userId = $db->lastInsertId();
                        echo "<div class='alert alert-success'>✅ Test user created!</div>";
                    }

                    // Log user in
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['zalo_id'] = 'test_user_123';

                    echo "<div class='alert alert-success'>✅ Logged in as Test User!</div>";
                    echo "<p class='text-center'><a href='" . BASE_URL . "/pages/profile-setup.php' class='btn btn-primary'>Go to Profile Setup</a></p>";

                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                }

            } else {
                ?>
                <div class="text-center">
                    <p>Click button dưới để tạo fake user và login:</p>
                    <a href="?action=create" class="btn btn-warning btn-lg">
                        🔓 Create & Login Test User
                    </a>
                </div>

                <hr>

                <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h4 style="color: #991b1b;">⚠️ QUAN TRỌNG:</h4>
                    <ul style="color: #991b1b;">
                        <li>File này CHỈ để test app</li>
                        <li><strong>XÓA file này khi deploy production!</strong></li>
                        <li>Để sử dụng đăng nhập thật, cần setup Zalo App</li>
                    </ul>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<?php
/**
 * RUMI - Zalo Debug Page
 * Trang debug để kiểm tra Zalo login flow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/zalo.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zalo Debug - RUMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .debug-box {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        h3 { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Zalo Login Debug</h1>
        <p class="text-muted">Trang này giúp debug vấn đề Zalo login</p>

        <!-- Session Info -->
        <div class="debug-box">
            <h3>📋 Session Info</h3>
            <table class="table table-sm">
                <tr>
                    <td><strong>Session Status:</strong></td>
                    <td class="<?= session_status() === PHP_SESSION_ACTIVE ? 'success' : 'error' ?>">
                        <?= session_status() === PHP_SESSION_ACTIVE ? '✓ Active' : '✗ Not Active' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Session ID:</strong></td>
                    <td><?= session_id() ?></td>
                </tr>
                <tr>
                    <td><strong>Session Name:</strong></td>
                    <td><?= session_name() ?></td>
                </tr>
                <tr>
                    <td><strong>Logged In:</strong></td>
                    <td class="<?= isLoggedIn() ? 'success' : 'error' ?>">
                        <?= isLoggedIn() ? '✓ Yes (User ID: ' . $_SESSION['user_id'] . ')' : '✗ No' ?>
                    </td>
                </tr>
            </table>

            <h4>Session Data:</h4>
            <pre><?= print_r($_SESSION, true) ?></pre>
        </div>

        <!-- Zalo Config -->
        <div class="debug-box">
            <h3>⚙️ Zalo Configuration</h3>
            <table class="table table-sm">
                <tr>
                    <td><strong>App ID:</strong></td>
                    <td class="<?= ZALO_APP_ID !== 'YOUR_ZALO_APP_ID' ? 'success' : 'error' ?>">
                        <?= ZALO_APP_ID !== 'YOUR_ZALO_APP_ID' ? '✓ Configured (' . substr(ZALO_APP_ID, 0, 10) . '...)' : '✗ Not Configured' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>App Secret:</strong></td>
                    <td class="<?= ZALO_APP_SECRET !== 'YOUR_ZALO_APP_SECRET' ? 'success' : 'error' ?>">
                        <?= ZALO_APP_SECRET !== 'YOUR_ZALO_APP_SECRET' ? '✓ Configured (' . substr(ZALO_APP_SECRET, 0, 10) . '...)' : '✗ Not Configured' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Callback URL:</strong></td>
                    <td><?= ZALO_CALLBACK_URL ?></td>
                </tr>
                <tr>
                    <td><strong>Login URL:</strong></td>
                    <td><?= getZaloLoginURL() ?></td>
                </tr>
            </table>
        </div>

        <!-- URL Parameters -->
        <div class="debug-box">
            <h3>🔗 URL Parameters</h3>
            <?php if (!empty($_GET)): ?>
                <pre><?= print_r($_GET, true) ?></pre>
            <?php else: ?>
                <p class="text-muted">No URL parameters</p>
            <?php endif; ?>
        </div>

        <!-- Database Connection -->
        <div class="debug-box">
            <h3>💾 Database Connection</h3>
            <?php
            try {
                $db = getDB();
                echo '<p class="success">✓ Database connected successfully</p>';

                // Check users table
                $stmt = $db->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo '<p>Total users: ' . $result['count'] . '</p>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Database error: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- Flash Messages -->
        <div class="debug-box">
            <h3>💬 Flash Messages</h3>
            <?php
            $flash = getFlash();
            if ($flash):
            ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No flash messages</p>
            <?php endif; ?>
        </div>

        <!-- Test Zalo Login Flow -->
        <div class="debug-box">
            <h3>🧪 Test Zalo Login</h3>
            <a href="<?= getZaloLoginURL() ?>" class="btn btn-primary">
                Test Zalo Login
            </a>
            <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-secondary">
                Go to Login Page
            </a>
            <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">
                Go to Homepage
            </a>
        </div>

        <!-- Error Logs -->
        <div class="debug-box">
            <h3>📝 Recent Error Logs</h3>
            <?php
            $logFile = __DIR__ . '/../logs/activity.log';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $recentLogs = array_slice($logs, -20); // Last 20 lines
                echo '<pre>' . implode('', $recentLogs) . '</pre>';
            } else {
                echo '<p class="text-muted">No logs found</p>';
            }
            ?>
        </div>

        <!-- Server Info -->
        <div class="debug-box">
            <h3>🖥️ Server Info</h3>
            <table class="table table-sm">
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?= PHP_VERSION ?></td>
                </tr>
                <tr>
                    <td><strong>Server Software:</strong></td>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                </tr>
                <tr>
                    <td><strong>Document Root:</strong></td>
                    <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></td>
                </tr>
                <tr>
                    <td><strong>cURL Available:</strong></td>
                    <td class="<?= function_exists('curl_init') ? 'success' : 'error' ?>">
                        <?= function_exists('curl_init') ? '✓ Yes' : '✗ No' ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

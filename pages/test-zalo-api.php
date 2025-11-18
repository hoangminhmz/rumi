<?php
/**
 * RUMI - Test Zalo API Direct
 * Test trực tiếp Zalo API để xem response
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/zalo.php';

// Chỉ cho phép access trong development/debug mode
// Comment dòng này nếu muốn test trên production
// die('Access denied');

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Zalo API - RUMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .test-box {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 400px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Zalo API</h1>

        <!-- Config Check -->
        <div class="test-box">
            <h3>⚙️ Zalo Configuration</h3>
            <table class="table table-sm">
                <tr>
                    <td><strong>App ID:</strong></td>
                    <td><?= ZALO_APP_ID !== 'YOUR_ZALO_APP_ID' ? '<span class="success">✓</span> ' . ZALO_APP_ID : '<span class="error">✗ Not configured</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>App Secret:</strong></td>
                    <td><?= ZALO_APP_SECRET !== 'YOUR_ZALO_APP_SECRET' ? '<span class="success">✓</span> ' . substr(ZALO_APP_SECRET, 0, 10) . '...' : '<span class="error">✗ Not configured</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Callback URL:</strong></td>
                    <td><?= ZALO_CALLBACK_URL ?></td>
                </tr>
            </table>
        </div>

        <?php if (isset($_GET['code'])): ?>
            <!-- Test với code từ URL -->
            <div class="test-box">
                <h3>🔄 Testing với Authorization Code</h3>
                <p><strong>Code:</strong> <code><?= htmlspecialchars($_GET['code']) ?></code></p>

                <?php
                // Test get access token
                echo '<h4>Step 1: Exchange Code for Access Token</h4>';

                $params = [
                    'app_id' => ZALO_APP_ID,
                    'app_secret' => ZALO_APP_SECRET,
                    'code' => $_GET['code']
                ];

                $url = ZALO_TOKEN_URL . '?' . http_build_query($params);
                echo '<p><strong>Request URL:</strong></p>';
                echo '<pre>' . htmlspecialchars($url) . '</pre>';

                // Make request
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verify for testing
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_VERBOSE, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                $curlInfo = curl_getinfo($ch);
                curl_close($ch);

                echo '<p><strong>HTTP Code:</strong> <span class="' . ($httpCode === 200 ? 'success' : 'error') . '">' . $httpCode . '</span></p>';

                if ($curlError) {
                    echo '<p class="error"><strong>cURL Error:</strong> ' . htmlspecialchars($curlError) . '</p>';
                }

                echo '<p><strong>Response:</strong></p>';
                echo '<pre>' . htmlspecialchars($response) . '</pre>';

                $tokenData = json_decode($response, true);
                echo '<p><strong>Parsed Response:</strong></p>';
                echo '<pre>' . print_r($tokenData, true) . '</pre>';

                // If we got access token, test get user info
                if (isset($tokenData['access_token'])) {
                    echo '<hr>';
                    echo '<h4 class="success">✓ Step 2: Get User Info with Access Token</h4>';

                    $userInfoUrl = ZALO_USER_INFO_URL . '?access_token=' . $tokenData['access_token'] . '&fields=id,name,picture';
                    echo '<p><strong>Request URL:</strong></p>';
                    echo '<pre>' . htmlspecialchars($userInfoUrl) . '</pre>';

                    $ch = curl_init($userInfoUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                    $userResponse = curl_exec($ch);
                    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    echo '<p><strong>HTTP Code:</strong> <span class="' . ($userHttpCode === 200 ? 'success' : 'error') . '">' . $userHttpCode . '</span></p>';
                    echo '<p><strong>Response:</strong></p>';
                    echo '<pre>' . htmlspecialchars($userResponse) . '</pre>';

                    $userData = json_decode($userResponse, true);
                    echo '<p><strong>Parsed User Data:</strong></p>';
                    echo '<pre>' . print_r($userData, true) . '</pre>';

                    if (isset($userData['id'])) {
                        echo '<div class="alert alert-success mt-3">';
                        echo '<strong>🎉 SUCCESS!</strong> Zalo login would work. User ID: ' . $userData['id'];
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger mt-3">';
                    echo '<strong>❌ FAILED!</strong> Could not get access token. ';
                    if (isset($tokenData['error'])) {
                        echo 'Error: ' . htmlspecialchars($tokenData['error']) . ' - ';
                        echo htmlspecialchars($tokenData['error_description'] ?? 'No description');
                    }
                    echo '</div>';

                    // Common errors and solutions
                    echo '<div class="test-box mt-3">';
                    echo '<h4>💡 Common Issues & Solutions:</h4>';
                    echo '<ul>';
                    echo '<li><strong>Error -14:</strong> Invalid App Secret</li>';
                    echo '<li><strong>Error -216:</strong> Invalid authorization code (expired or already used)</li>';
                    echo '<li><strong>Error -201:</strong> Invalid App ID</li>';
                    echo '<li><strong>Error -10007:</strong> Callback URL mismatch</li>';
                    echo '</ul>';
                    echo '<p><strong>Next steps:</strong></p>';
                    echo '<ol>';
                    echo '<li>Verify App ID and App Secret in Zalo Developer Portal</li>';
                    echo '<li>Check callback URL matches exactly (including https://)</li>';
                    echo '<li>Try the login flow again (authorization codes expire quickly)</li>';
                    echo '</ol>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php else: ?>
            <!-- No code yet, show instructions -->
            <div class="test-box">
                <h3>📝 How to Test</h3>
                <ol>
                    <li>Click "Start Zalo Login Test" button below</li>
                    <li>Login with Zalo</li>
                    <li>Zalo will redirect back to this page with the authorization code</li>
                    <li>This page will test the API calls and show you the exact responses</li>
                </ol>

                <a href="<?= getZaloLoginURL() ?>" class="btn btn-primary btn-lg mt-3">
                    Start Zalo Login Test
                </a>
            </div>

            <div class="test-box">
                <h3>⚠️ Important Notes</h3>
                <ul>
                    <li>This page is for debugging only</li>
                    <li>Authorization codes can only be used once</li>
                    <li>Authorization codes expire after a few minutes</li>
                    <li>Make sure your Zalo App is in "Active" status, not "Draft"</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="<?= BASE_URL ?>/pages/zalo-debug.php" class="btn btn-secondary">Back to Debug Page</a>
            <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline-secondary">Back to Login</a>
        </div>
    </div>
</body>
</html>

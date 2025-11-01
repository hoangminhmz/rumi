<?php
/**
 * Debug Zalo Callback
 * Xem callback nhận được gì từ Zalo
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log all data
$logFile = __DIR__ . '/../logs/zalo-debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$logEntry = "\n\n=== Zalo Callback Debug - $timestamp ===\n";
$logEntry .= "GET params: " . print_r($_GET, true) . "\n";
$logEntry .= "Session: " . print_r($_SESSION, true) . "\n";

file_put_contents($logFile, $logEntry, FILE_APPEND);

// Display debug info
echo "<!DOCTYPE html><html><head><title>Zalo Callback Debug</title></head><body>";
echo "<h1>🔍 Zalo Callback Debug</h1>";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/zalo.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';

startSession();

echo "<h3>1. Zalo Config:</h3>";
echo "ZALO_APP_ID: " . ZALO_APP_ID . "<br>";
echo "ZALO_APP_SECRET: " . (ZALO_APP_SECRET === 'YOUR_ZALO_APP_SECRET' ? '❌ NOT CONFIGURED' : '✅ Configured') . "<br>";
echo "ZALO_CALLBACK_URL: " . ZALO_CALLBACK_URL . "<br>";

echo "<h3>2. Callback Params:</h3>";
if (isset($_GET['error'])) {
    echo "❌ <strong style='color:red;'>Error from Zalo:</strong><br>";
    echo "Error: " . htmlspecialchars($_GET['error']) . "<br>";
    echo "Description: " . htmlspecialchars($_GET['error_description'] ?? 'N/A') . "<br>";
}

if (isset($_GET['code'])) {
    echo "✅ Authorization Code: " . htmlspecialchars(substr($_GET['code'], 0, 20)) . "...<br>";
} else {
    echo "❌ <strong style='color:red;'>NO CODE received!</strong><br>";
}

if (isset($_GET['state'])) {
    echo "✅ State: " . htmlspecialchars(substr($_GET['state'], 0, 20)) . "...<br>";
} else {
    echo "❌ NO STATE received<br>";
}

echo "<h3>3. Session Data:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Zalo State in Session: " . (isset($_SESSION['zalo_state']) ? '✅ Present' : '❌ Missing') . "<br>";
echo "User ID in Session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '❌ Not logged in') . "<br>";

// Try the login flow if we have a code
if (isset($_GET['code'])) {
    echo "<h3>4. Testing Login Flow:</h3>";

    try {
        echo "<p>Step 1: Getting access token...</p>";
        $tokenData = getZaloAccessToken($_GET['code']);

        if ($tokenData) {
            echo "✅ Token data received: <pre>" . print_r($tokenData, true) . "</pre>";

            if (isset($tokenData['access_token'])) {
                echo "<p>Step 2: Getting user info...</p>";
                $zaloUser = getZaloUserInfo($tokenData['access_token']);

                if ($zaloUser) {
                    echo "✅ User data: <pre>" . print_r($zaloUser, true) . "</pre>";

                    echo "<p>Step 3: Creating user in database...</p>";
                    $userModel = new User();
                    $userId = $userModel->createOrUpdateFromZalo($zaloUser);

                    if ($userId) {
                        echo "✅ User created/updated! ID: $userId<br>";

                        $_SESSION['user_id'] = $userId;
                        $_SESSION['zalo_id'] = $zaloUser['id'];

                        echo "<p>Step 4: Checking profile...</p>";
                        if ($userModel->hasCompleteProfile($userId)) {
                            echo "✅ Profile complete → Would redirect to swipe.php<br>";
                        } else {
                            echo "⚠️ Profile incomplete → Would redirect to profile-setup.php<br>";
                        }

                        echo "<hr>";
                        echo "<h2 style='color:green;'>✅ LOGIN SUCCESSFUL!</h2>";
                        echo "<p><a href='" . BASE_URL . "/pages/profile-setup.php'>Go to Profile Setup</a></p>";
                        echo "<p><a href='" . BASE_URL . "/pages/swipe.php'>Go to Swipe</a></p>";

                    } else {
                        echo "❌ Failed to create user in database<br>";
                    }
                } else {
                    echo "❌ Failed to get user info from Zalo<br>";
                }
            } else {
                echo "❌ No access_token in response<br>";
            }
        } else {
            echo "❌ Failed to get token data<br>";
        }

    } catch (Exception $e) {
        echo "<div style='background:#fee;padding:15px;border:1px solid red;'>";
        echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "</div>";
    }
} else {
    echo "<h3>⚠️ No Code - Cannot Test Login</h3>";
    echo "<p>This page should be called FROM Zalo with a code parameter.</p>";
}

echo "<hr>";
echo "<h3>How to Test:</h3>";
echo "<ol>";
echo "<li>Make sure ZALO_APP_ID and ZALO_APP_SECRET are configured</li>";
echo "<li>Go to <a href='" . BASE_URL . "/pages/login.php'>Login Page</a></li>";
echo "<li>Click 'Đăng nhập với Zalo'</li>";
echo "<li>You will be redirected back here with debug info</li>";
echo "</ol>";

echo "</body></html>";
?>

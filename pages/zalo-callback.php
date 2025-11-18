<?php
/**
 * RUMI - Zalo OAuth Callback
 * Handle Zalo login redirect
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/zalo.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/User.php';

startSession();

// Log callback start
error_log("========== Zalo Callback Start ==========");
error_log("GET params: " . json_encode($_GET));
error_log("Session ID: " . session_id());
error_log("Session data: " . json_encode($_SESSION));

// Check for errors
if (isset($_GET['error'])) {
    error_log("Zalo returned error: " . $_GET['error']);
    setFlash('error', 'Đăng nhập thất bại: ' . ($_GET['error_description'] ?? $_GET['error']));
    redirect(BASE_URL . '/pages/login.php');
}

// Check for authorization code
if (!isset($_GET['code'])) {
    error_log("Missing authorization code in callback");
    setFlash('error', 'Thiếu authorization code');
    redirect(BASE_URL . '/pages/login.php');
}

// Verify state token (CSRF protection)
error_log("Verifying state token. Received: " . ($_GET['state'] ?? 'none') . ", Expected: " . ($_SESSION['zalo_state'] ?? 'none'));
if (!isset($_GET['state']) || !verifyStateToken($_GET['state'])) {
    error_log("State token verification failed!");
    setFlash('error', 'Invalid state token (CSRF protection). Vui lòng thử đăng nhập lại.');
    redirect(BASE_URL . '/pages/login.php');
}

try {
    // Exchange code for access token
    $tokenData = getZaloAccessToken($_GET['code']);

    // Debug logging
    error_log("Zalo token response: " . json_encode($tokenData));

    if (!$tokenData) {
        throw new Exception('Không nhận được response từ Zalo API. Kiểm tra App ID và App Secret.');
    }

    // Check for error in token response
    if (isset($tokenData['error'])) {
        $errorMsg = $tokenData['error_description'] ?? $tokenData['error'] ?? 'Unknown error';
        throw new Exception("Zalo API error: " . $errorMsg);
    }

    if (!isset($tokenData['access_token'])) {
        throw new Exception('Access token không tồn tại trong response. Response: ' . json_encode($tokenData));
    }

    // Get user info from Zalo
    $zaloUser = getZaloUserInfo($tokenData['access_token']);

    // Debug logging
    error_log("Zalo user info response: " . json_encode($zaloUser));

    if (!$zaloUser) {
        throw new Exception('Không nhận được thông tin user từ Zalo Graph API');
    }

    // Check for error in user info response
    if (isset($zaloUser['error'])) {
        $errorMsg = $zaloUser['error']['message'] ?? $zaloUser['error'] ?? 'Unknown error';
        throw new Exception("Zalo Graph API error: " . $errorMsg);
    }

    if (!isset($zaloUser['id'])) {
        throw new Exception('User ID không tồn tại trong response. Response: ' . json_encode($zaloUser));
    }

    // Create or update user in database
    $userModel = new User();
    $userId = $userModel->createOrUpdateFromZalo($zaloUser);

    if (!$userId) {
        throw new Exception('Không thể tạo user trong database. Kiểm tra database connection và schema.');
    }

    // Log user in
    $_SESSION['user_id'] = $userId;
    $_SESSION['zalo_id'] = $zaloUser['id'];

    // Log successful login
    error_log("Zalo login successful for user ID: " . $userId);

    // Check if user needs to complete profile
    if (!$userModel->hasCompleteProfile($userId)) {
        setFlash('info', 'Vui lòng hoàn thành profile để bắt đầu sử dụng RUMI');
        redirect(BASE_URL . '/pages/profile-setup.php');
    } else {
        setFlash('success', 'Đăng nhập thành công!');
        redirect(BASE_URL . '/pages/swipe.php');
    }

} catch (Exception $e) {
    // Detailed error logging
    error_log("Zalo login error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());

    // User-friendly error message
    $userMessage = 'Đăng nhập thất bại. ';

    // Add specific guidance based on error
    if (strpos($e->getMessage(), 'App ID') !== false || strpos($e->getMessage(), 'App Secret') !== false) {
        $userMessage .= 'Vui lòng kiểm tra cấu hình Zalo App. Xem hướng dẫn tại ZALO-LOGIN-SETUP.md';
    } elseif (strpos($e->getMessage(), 'database') !== false) {
        $userMessage .= 'Lỗi kết nối database. Vui lòng liên hệ admin.';
    } else {
        $userMessage .= $e->getMessage();
    }

    setFlash('error', $userMessage);
    redirect(BASE_URL . '/pages/login.php');
}

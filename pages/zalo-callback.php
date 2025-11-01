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

// Check for errors
if (isset($_GET['error'])) {
    setFlash('error', 'Đăng nhập thất bại: ' . $_GET['error_description']);
    redirect(BASE_URL . '/pages/login.php');
}

// Check for authorization code
if (!isset($_GET['code'])) {
    setFlash('error', 'Thiếu authorization code');
    redirect(BASE_URL . '/pages/login.php');
}

// Verify state token (CSRF protection)
if (!isset($_GET['state']) || !verifyStateToken($_GET['state'])) {
    setFlash('error', 'Invalid state token');
    redirect(BASE_URL . '/pages/login.php');
}

try {
    // Exchange code for access token
    $tokenData = getZaloAccessToken($_GET['code']);

    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Không thể lấy access token');
    }

    // Get user info from Zalo
    $zaloUser = getZaloUserInfo($tokenData['access_token']);

    if (!$zaloUser || !isset($zaloUser['id'])) {
        throw new Exception('Không thể lấy thông tin user từ Zalo');
    }

    // Create or update user in database
    $userModel = new User();
    $userId = $userModel->createOrUpdateFromZalo($zaloUser);

    if (!$userId) {
        throw new Exception('Không thể tạo user trong database');
    }

    // Log user in
    $_SESSION['user_id'] = $userId;
    $_SESSION['zalo_id'] = $zaloUser['id'];

    // Check if user needs to complete profile
    if (!$userModel->hasCompleteProfile($userId)) {
        setFlash('info', 'Vui lòng hoàn thành profile để bắt đầu sử dụng RUMI');
        redirect(BASE_URL . '/pages/profile-setup.php');
    } else {
        setFlash('success', 'Đăng nhập thành công!');
        redirect(BASE_URL . '/pages/swipe.php');
    }

} catch (Exception $e) {
    error_log("Zalo login error: " . $e->getMessage());
    setFlash('error', 'Đăng nhập thất bại: ' . $e->getMessage());
    redirect(BASE_URL . '/pages/login.php');
}

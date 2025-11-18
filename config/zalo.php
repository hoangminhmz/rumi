<?php
/**
 * RUMI - Zalo Login SDK Configuration
 * https://developers.zalo.me/docs/sdk/login-sdk/
 */

// Zalo App credentials - GET FROM https://developers.zalo.me/
// QUAN TRỌNG: Thay YOUR_ZALO_APP_ID và YOUR_ZALO_APP_SECRET bằng credentials thật
define('ZALO_APP_ID', 'YOUR_ZALO_APP_ID');
define('ZALO_APP_SECRET', 'YOUR_ZALO_APP_SECRET');
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/rummi/pages/zalo-callback.php');

// Zalo API endpoints
define('ZALO_AUTH_URL', 'https://oauth.zaloapp.com/v4/permission');
define('ZALO_TOKEN_URL', 'https://oauth.zaloapp.com/v4/access_token');
define('ZALO_USER_INFO_URL', 'https://graph.zalo.me/v2.0/me');

/**
 * Generate Zalo login URL
 * @return string
 */
function getZaloLoginURL() {
    // Check if constants are defined to avoid errors
    if (!defined('ZALO_APP_ID') || ZALO_APP_ID === 'YOUR_ZALO_APP_ID') {
        // Return placeholder URL if not configured
        return '#zalo-not-configured';
    }

    $params = [
        'app_id' => ZALO_APP_ID,
        'redirect_uri' => ZALO_CALLBACK_URL,
        'state' => generateStateToken()
    ];

    return ZALO_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Generate state token cho CSRF protection
 * @return string
 */
function generateStateToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['zalo_state'] = $state;

    error_log("Generated state token: " . $state . " (Session ID: " . session_id() . ")");

    return $state;
}

/**
 * Verify state token
 * @param string $state
 * @return bool
 */
function verifyStateToken($state) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    error_log("verifyStateToken called - Session ID: " . session_id());
    error_log("Received state: " . $state);
    error_log("Session state: " . ($_SESSION['zalo_state'] ?? 'NOT SET'));

    if (!isset($_SESSION['zalo_state'])) {
        error_log("❌ Session state not found!");
        return false;
    }

    $sessionState = $_SESSION['zalo_state'];
    $valid = hash_equals($sessionState, $state);

    error_log("State comparison: " . ($valid ? '✓ MATCH' : '✗ MISMATCH'));

    // Only unset if valid to allow retry
    if ($valid) {
        unset($_SESSION['zalo_state']);
    }

    return $valid;
}

/**
 * Get access token from authorization code
 * @param string $code
 * @return array|null
 */
function getZaloAccessToken($code) {
    $params = [
        'app_id' => ZALO_APP_ID,
        'app_secret' => ZALO_APP_SECRET,
        'code' => $code
    ];

    $url = ZALO_TOKEN_URL . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Better security
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log request for debugging
    error_log("Zalo Access Token Request - HTTP Code: $httpCode, URL: $url");

    if ($error) {
        error_log("cURL Error: " . $error);
        return null;
    }

    if ($httpCode !== 200) {
        error_log("Zalo API returned HTTP $httpCode: " . $response);
    }

    if ($response) {
        return json_decode($response, true);
    }

    return null;
}

/**
 * Get Zalo user info
 * @param string $access_token
 * @return array|null
 */
function getZaloUserInfo($access_token) {
    $url = ZALO_USER_INFO_URL . '?access_token=' . $access_token . '&fields=id,name,picture';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Better security
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: RUMI/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log request for debugging
    error_log("Zalo User Info Request - HTTP Code: $httpCode");

    if ($error) {
        error_log("cURL Error: " . $error);
        return null;
    }

    if ($httpCode !== 200) {
        error_log("Zalo Graph API returned HTTP $httpCode: " . $response);
    }

    if ($response) {
        return json_decode($response, true);
    }

    return null;
}

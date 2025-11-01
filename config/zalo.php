<?php
/**
 * RUMI - Zalo Login SDK Configuration
 * https://developers.zalo.me/docs/sdk/login-sdk/
 */

// Zalo App credentials - GET FROM https://developers.zalo.me/
define('ZALO_APP_ID', 'YOUR_ZALO_APP_ID');
define('ZALO_APP_SECRET', 'YOUR_ZALO_APP_SECRET');
define('ZALO_CALLBACK_URL', 'http://localhost/rumi/pages/zalo-callback.php');

// Zalo API endpoints
define('ZALO_AUTH_URL', 'https://oauth.zaloapp.com/v4/permission');
define('ZALO_TOKEN_URL', 'https://oauth.zaloapp.com/v4/access_token');
define('ZALO_USER_INFO_URL', 'https://graph.zalo.me/v2.0/me');

/**
 * Generate Zalo login URL
 * @return string
 */
function getZaloLoginURL() {
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

    if (!isset($_SESSION['zalo_state'])) {
        return false;
    }

    $valid = hash_equals($_SESSION['zalo_state'], $state);
    unset($_SESSION['zalo_state']);

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        return json_decode($response, true);
    }

    return null;
}

<?php
/**
 * RUMI - Application Constants
 * Các hằng số và cấu hình chung cho app
 */

// Base URLs
define('BASE_URL', 'https://hoangminhmz.com/rummi');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/images/uploads');

// Session configuration
define('SESSION_NAME', 'RUMI_SESSION');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Upload configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('UPLOAD_MAX_FILES', 5);

// Pagination
define('ITEMS_PER_PAGE', 20);
define('CARDS_PER_SWIPE', 10);

// Matching algorithm weights
define('MATCH_DISTRICT_WEIGHT', 30);
define('MATCH_AGE_WEIGHT', 20);
define('MATCH_PREFERENCE_WEIGHT', 50);
define('MATCH_MIN_SCORE', 40); // Minimum score để show card

// Room listing
define('ROOM_LISTING_FEE', 50000); // 50,000 VND per listing
define('ROOM_LISTING_DURATION', 30); // 30 days

// Rate limiting
define('SWIPE_DAILY_LIMIT', 100);
define('MATCH_REVEAL_DELAY', 2); // seconds

// Geolocation & Maps
define('MAPBOX_API_KEY', ''); // Để trống, sẽ set trong .env hoặc config riêng
define('DEFAULT_MAX_DISTANCE', 5); // km - Default search radius
define('MAX_DISTANCE_LIMIT', 20); // km - Maximum search radius allowed
define('EARTH_RADIUS_KM', 6371); // Earth radius in kilometers

// Smart Ranking Weights (Phase 2)
define('RANKING_DISTANCE_WEIGHT', 0.40); // 40%
define('RANKING_PRICE_WEIGHT', 0.30); // 30%
define('RANKING_AMENITIES_WEIGHT', 0.20); // 20%
define('RANKING_POPULARITY_WEIGHT', 0.10); // 10%

// RUMI Brand Colors
define('COLOR_PRIMARY', '#00D4AA');
define('COLOR_SECONDARY', '#A7F3D0');
define('COLOR_ACCENT', '#059669');

// Cities
define('CITIES', ['Hà Nội', 'TP.HCM', 'Đà Nẵng']);

// Gender options
define('GENDERS', [
    'male' => 'Nam',
    'female' => 'Nữ',
    'other' => 'Khác'
]);

// Search modes
define('SEARCH_MODES', [
    'find_roommate' => 'Tìm người trước',
    'find_room' => 'Tìm phòng trước'
]);

// Room amenities
define('AMENITIES', [
    'wifi' => 'WiFi',
    'ac' => 'Điều hòa',
    'kitchen' => 'Bếp',
    'parking' => 'Chỗ đậu xe',
    'laundry' => 'Máy giặt',
    'furniture' => 'Nội thất',
    'balcony' => 'Ban công',
    'security' => 'Bảo vệ'
]);

// Preference options
define('PREFERENCE_LEVELS', [
    1 => 'Rất thấp',
    2 => 'Thấp',
    3 => 'Trung bình',
    4 => 'Cao',
    5 => 'Rất cao'
]);

/**
 * Get full path to upload directory
 * @return string
 */
function getUploadPath() {
    return __DIR__ . '/../assets/images/uploads/';
}

/**
 * Get full URL to uploaded file
 * @param string $filename
 * @return string
 */
function getUploadURL($filename) {
    return UPLOADS_URL . '/' . $filename;
}

/**
 * Format price to VND
 * @param int $price
 * @return string
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

/**
 * Calculate age from birthdate
 * @param string $birthdate
 * @return int
 */
function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $now = new DateTime();
    return $now->diff($birth)->y;
}

/**
 * Sanitize input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/pages/login.php');
    }
}

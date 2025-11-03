<?php
/**
 * RUMI - Helper Functions
 * Các utility functions dùng chung trong app
 */

/**
 * Start session nếu chưa có
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(SESSION_LIFETIME);
        session_start();
    }
}

/**
 * Set flash message
 * @param string $type success|error|warning|info
 * @param string $message
 */
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get và xóa flash message
 * @return array|null
 */
function getFlash() {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Validate phone number Vietnam
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    // VN phone: 10 digits, starts with 0
    return preg_match('/^0[0-9]{9}$/', $phone);
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Time ago helper
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' tuần trước';
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Get gender label in Vietnamese
 * @param string $gender
 * @return string
 */
function getGenderLabel($gender) {
    $labels = GENDERS;
    return $labels[$gender] ?? 'Không xác định';
}

/**
 * Get search mode label
 * @param string $mode
 * @return string
 */
function getSearchModeLabel($mode) {
    $labels = SEARCH_MODES;
    return $labels[$mode] ?? 'Không xác định';
}

/**
 * Get amenity label
 * @param string $amenity
 * @return string
 */
function getAmenityLabel($amenity) {
    $labels = AMENITIES;
    return $labels[$amenity] ?? $amenity;
}

/**
 * JSON response helper cho AJAX
 * @param bool $success
 * @param mixed $data
 * @param string $message
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRF($token) {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRF() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input field
 * @return string HTML
 */
function csrfField() {
    $token = generateCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Resize và compress image
 * @param string $source Source file path
 * @param string $destination Destination file path
 * @param int $maxWidth
 * @param int $maxHeight
 * @param int $quality
 * @return bool
 */
function resizeImage($source, $destination, $maxWidth = 800, $maxHeight = 800, $quality = 85) {
    try {
        list($width, $height, $type) = getimagesize($source);

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio >= 1) {
            // Image smaller than max, just copy
            return copy($source, $destination);
        }

        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            default:
                return false;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Resize
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $destination, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($newImage, $destination, 9);
                break;
        }

        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    } catch (Exception $e) {
        error_log("Resize image error: " . $e->getMessage());
        return false;
    }
}

/**
 * Paginate array
 * @param array $items
 * @param int $page
 * @param int $perPage
 * @return array
 */
function paginate($items, $page = 1, $perPage = ITEMS_PER_PAGE) {
    $total = count($items);
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'items' => array_slice($items, $offset, $perPage),
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get full name cho matched user (not current user)
 * @param array $match
 * @param int $currentUserId
 * @return string
 */
function getMatchedUserName($match, $currentUserId) {
    if ($match['user1_id'] == $currentUserId) {
        return $match['user2_name'];
    } else {
        return $match['user1_name'];
    }
}

/**
 * Get avatar URL cho matched user
 * @param array $match
 * @param int $currentUserId
 * @return string
 */
function getMatchedUserAvatar($match, $currentUserId) {
    $avatar = null;
    if ($match['user1_id'] == $currentUserId) {
        $avatar = $match['user2_avatar'];
    } else {
        $avatar = $match['user1_avatar'];
    }

    return $avatar ? getUploadURL($avatar) : ASSETS_URL . '/images/default-avatar.svg';
}

/**
 * Get full matched user data
 * @param array $match
 * @param int $currentUserId
 * @return array
 */
function getMatchedUser($match, $currentUserId) {
    $isUser1 = ($match['user1_id'] == $currentUserId);
    $prefix = $isUser1 ? 'user2_' : 'user1_';

    return [
        'id' => $match[$prefix . 'id'] ?? null,
        'name' => $match[$prefix . 'name'] ?? null,
        'avatar' => $match[$prefix . 'avatar'] ?? null,
        'age' => $match[$prefix . 'age'] ?? null,
        'gender' => $match[$prefix . 'gender'] ?? null,
        'district' => $match[$prefix . 'district'] ?? null,
        'phone' => $match[$prefix . 'phone'] ?? null,
        'bio' => $match[$prefix . 'bio'] ?? null
    ];
}

/**
 * Check if user has liked enough cards today (rate limiting)
 * @param int $userId
 * @return bool
 */
function checkSwipeLimit($userId) {
    try {
        $db = getDB();

        // Count swipes today
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM user_swipes
            WHERE user_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return $result['count'] < SWIPE_DAILY_LIMIT;
    } catch (PDOException $e) {
        error_log("Check swipe limit error: " . $e->getMessage());
        return true; // Allow on error
    }
}

/**
 * Log user activity
 * @param int $userId
 * @param string $action
 * @param array $data
 */
function logActivity($userId, $action, $data = []) {
    // Simple file-based logging
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $entry = sprintf(
        "[%s] User %d: %s | %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $action,
        json_encode($data)
    );

    file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Debug helper - chỉ dùng trong development
 * @param mixed $data
 * @param bool $die
 */
function dd($data, $die = true) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ($die) {
        die();
    }
}

/**
 * Escape HTML output
 * @param string $text
 * @return string
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format price in Vietnamese Dong
 * @param int|float $price
 * @return string
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Include view file với data
 * @param string $view Path to view file
 * @param array $data Variables to extract
 */
function view($view, $data = []) {
    extract($data);
    $viewPath = __DIR__ . '/../pages/' . $view . '.php';

    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        die("View not found: $view");
    }
}

/**
 * Get current page name
 * @return string
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

/**
 * Check if current page matches
 * @param string $page
 * @return bool
 */
function isCurrentPage($page) {
    return getCurrentPage() === $page;
}

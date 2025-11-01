<?php
/**
 * RUMI - Database Configuration
 * PDO connection cho MySQL database
 */

// Database credentials - CHANGE IN PRODUCTION!
define('DB_HOST', 'localhost');
define('DB_NAME', 'rumi_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Tạo PDO connection singleton
 * @return PDO
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error và show user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Không thể kết nối database. Vui lòng thử lại sau.");
        }
    }

    return $pdo;
}

/**
 * Test database connection
 * @return bool
 */
function testDBConnection() {
    try {
        $pdo = getDB();
        return $pdo !== null;
    } catch (Exception $e) {
        return false;
    }
}

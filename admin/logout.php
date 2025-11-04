<?php
/**
 * RUMI - Admin Logout
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Clear admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

redirect(BASE_URL . '/admin/login.php');

<?php
/**
 * Main Configuration File
 * MTICS - Bin It to Win It
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Site configuration
define('SITE_NAME', 'MTICS - Manila Technician Institute Computer Society');
define('SITE_URL', 'http://localhost/BinItToWinIt');
define('TOKENS_PER_BOTTLE', 5.00); // Eco-Tokens earned per recycled bottle

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_NAME', 'csrf_token');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Include database
require_once __DIR__ . '/database.php';

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity']) < SESSION_TIMEOUT;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function format_tokens($amount) {
    return number_format($amount, 2);
}

function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    $user_id = get_user_id();
    if (!$user_id) {
        return false;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user && $user['is_admin'] == 1;
    } catch (PDOException $e) {
        return false;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

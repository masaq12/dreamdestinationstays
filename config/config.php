<?php
// Site Configuration
define('SITE_NAME', 'Dream Destination Stays');
define('SITE_URL', 'http://localhost/dream_destinations');
define('BASE_PATH', dirname(__DIR__));

// Upload directories
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('LISTING_PHOTOS_PATH', UPLOAD_PATH . 'listings/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Platform settings (can be overridden from database)
define('PLATFORM_FEE_PERCENT', 15.00);
define('TAX_RATE', 10.00);
define('MIN_PAYOUT_AMOUNT', 50.00);
define('CURRENCY_SYMBOL', '$');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to check user type
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Helper function to check if admin
function isAdmin() {
    return isLoggedIn() && getUserType() === 'admin';
}

// Helper function to check if host
function isHost() {
    return isLoggedIn() && getUserType() === 'host';
}

// Helper function to check if guest
function isGuest() {
    return isLoggedIn() && getUserType() === 'guest';
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to format currency
function formatCurrency($amount) {
    // Handle null or non-numeric values
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        $amount = 0;
    }
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to generate random string
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}
?>

<?php
// Session Configuration (must be before any output)
// Note: These settings should be set before session_start() is called
// They are now handled in index.php before session_start()

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Site Configuration
define('SITE_NAME', 'CheatStore');
define('SITE_URL', 'http://localhost');
define('SITE_EMAIL', 'support@cheatstore.net');

// Database Configuration
define('DB_HOST', 'de17.spaceify.eu:3306');
define('DB_NAME', 's33522_DevTest');
define('DB_USER', 'u33522_oZXvGIzazF');
define('DB_PASS', 'z+=P5vCxzquzD!u+^dZX9q5J');

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51Rw4M8R7zRXgs80ByPvF5HtZn37fnJEk7kKUGCyfD56N9ZuZgzmXttsn5aHj5TFrMplp9MjBPwFtbkPjIhRs3q4m0077BSeuxY');
define('STRIPE_SECRET_KEY', 'sk_test_51Rw4M8R7zRXgs80BibtucGeTIsPBlfi9OW5bVdSof1aQ43HurKkJFvaQxVu8qu1xnjgN1KkjpWPWwybs4PYbutiB00Nt7cUdrg');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret');

// Security Configuration
define('JWT_SECRET', 'your_jwt_secret_key_here');
define('CSRF_TOKEN_SECRET', 'your_csrf_secret_key_here');

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');

// Key Generation Configuration
define('KEY_LENGTH', 32);
define('KEY_PREFIX', 'CS');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
?>

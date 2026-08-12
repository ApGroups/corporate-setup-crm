<?php
/**
 * Application Configuration
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment configuration
require_once __DIR__ . '/EnvLoader.php';

// Application Settings
define('APP_NAME', EnvLoader::get('APP_NAME', 'Corporate Setup CRM'));
define('APP_VERSION', EnvLoader::get('APP_VERSION', '1.0.0'));
define('APP_URL', EnvLoader::get('APP_URL', 'https://corporate-setup.com'));
define('APP_DEBUG', filter_var(EnvLoader::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
define('APP_ENV', EnvLoader::get('APP_ENV', 'production'));
define('MAIL_HOST', EnvLoader::get('MAIL_HOST', ''));
define('MAIL_PORT', (int) EnvLoader::get('MAIL_PORT', 0));
define('MAIL_USERNAME', EnvLoader::get('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', EnvLoader::get('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', EnvLoader::get('MAIL_FROM_ADDRESS', ''));
define('MAIL_FROM_NAME', EnvLoader::get('MAIL_FROM_NAME', APP_NAME));

// Security Settings
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']);

// Date Settings
define('DATE_FORMAT', 'd-m-Y');
define('DATETIME_FORMAT', 'd-m-Y H:i:s');
define('TIMEZONE', 'Africa/Lagos');

date_default_timezone_set(TIMEZONE);

// Error Reporting - Strict in production
if (APP_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', APP_DEBUG ? 1 : 0);
}

// Security Headers
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; font-src 'self' cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self';");
    // HTTPS enforcement
    if (APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// Include core files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validators.php';
require_once __DIR__ . '/../helpers/formatters.php';

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session Security Check
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

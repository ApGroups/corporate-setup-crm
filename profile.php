<?php
/**
 * User Profile
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Helper Functions
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/helpers/validators.php';
require_once __DIR__ . '/helpers/formatters.php';

// General Functions
require_once __DIR__ . '/includes/functions.php';

// Authentication
require_once __DIR__ . '/includes/auth.php';

// User must be logged in
requireLogin();

$pageTitle = 'My Profile';
$activeNav = 'settings';

$db = Database::getInstance();
$user = getCurrentUser();

$profile = $db->fetch(
    "SELECT u.*, r.display_name as role_name, d.name as department_name
     FROM users u
     LEFT JOIN roles r ON u.role_id = r.id
     LEFT JOIN departments d ON u.department_id = d.id
     WHERE u.id = ?",
    [$user['id']]
);

ob_start();
include 'views/profile/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

<?php
/**
 * Elite Members
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

// Permission check
requirePermission('view_elite');

$pageTitle = 'Elite Members';
$activeNav = 'elite';

$db = Database::getInstance();
$user = getCurrentUser();

$members = $db->fetchAll(
    "SELECT em.*, c.company_name, c.email, c.phone, u.name as account_manager
     FROM elite_members em
     JOIN companies c ON em.company_id = c.id
     LEFT JOIN users u ON em.assigned_account_manager = u.id
     ORDER BY em.membership_start_date DESC"
);

ob_start();
include 'views/elite/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

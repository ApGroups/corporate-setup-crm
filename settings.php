<?php
/**
 * Settings
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
requirePermission('view_settings');

$pageTitle = 'Settings';
$activeNav = 'settings';

$db = Database::getInstance();
$user = getCurrentUser();

$tab = $_GET['tab'] ?? 'general';

$config = [];
$settings = $db->fetchAll("SELECT * FROM settings");
foreach ($settings as $s) {
    $config[$s['setting_key']] = $s['setting_value'];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('manage_settings');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('danger', 'Invalid security token');
        redirect('settings.php');
    }

    switch ($tab) {
        case 'general':
        case 'notifications':
            foreach ($_POST as $key => $value) {
                if ($key !== 'csrf_token' && $key !== 'submit') {
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                        [$key, sanitize($value), sanitize($value)]
                    );
                }
            }
            logActivity($user['id'], 'update', 'Updated ' . $tab . ' settings');
            flashMessage('success', 'Settings updated successfully');
            redirect('settings.php?tab=' . urlencode($tab));
            break;
    }
}

ob_start();
include 'views/settings/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

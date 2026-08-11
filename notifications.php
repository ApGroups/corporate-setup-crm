<?php
/**
 * Notifications
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

$pageTitle = 'Notifications';
$activeNav = 'notifications';

$db = Database::getInstance();
$user = getCurrentUser();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if (isset($_POST['mark_read'])) {
        $id = intval($_POST['mark_read']);
        $db->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => $user['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    if (isset($_POST['mark_all_read'])) {
        $db->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'user_id = :uid AND is_read = 0', ['uid' => $user['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

$notifications = $db->fetchAll(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
    [$user['id']]
);

$unreadCount = $db->query("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$user['id']])->fetchColumn();

ob_start();
include 'views/notifications/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

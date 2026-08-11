<?php
/**
 * Reports
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
$pageTitle = 'Reports';
$activeNav = 'reports';

$db = Database::getInstance();
$user = getCurrentUser();

$type = $_GET['type'] ?? 'summary';
$period = $_GET['period'] ?? 'month';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

// Summary stats
$stats = [
    'companies' => [
        'total' => $db->query("SELECT COUNT(*) FROM companies WHERE status != 'churned'")->fetchColumn(),
        'active' => $db->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn(),
        'new' => $db->query("SELECT COUNT(*) FROM companies WHERE created_at >= ?", [$from])->fetchColumn(),
    ],
    'registrations' => [
        'total' => $db->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
        'pending' => $db->query("SELECT COUNT(*) FROM registrations WHERE status IN ('pending', 'submitted', 'processing')")->fetchColumn(),
        'approved' => $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'approved'")->fetchColumn(),
        'this_period' => $db->query("SELECT COUNT(*) FROM registrations WHERE created_at BETWEEN ? AND ?", [$from . ' 00:00:00', $to . ' 23:59:59'])->fetchColumn(),
    ],
    'tasks' => [
        'total' => $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
        'completed' => $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn(),
        'this_period' => $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND completed_at BETWEEN ? AND ?", [$from . ' 00:00:00', $to . ' 23:59:59'])->fetchColumn(),
    ],
];

ob_start();
include 'views/reports/main.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

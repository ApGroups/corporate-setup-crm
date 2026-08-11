<?php
/**
 * Reports
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/helpers/validators.php';
require_once __DIR__ . '/helpers/formatters.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
requirePermission('view_reports');

$pageTitle = 'Reports';
$activeNav = 'reports';

$db = Database::getInstance();
$user = getCurrentUser();

$type = $_GET['type'] ?? 'summary';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

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

$registrationRows = [];
$taskRows = [];
$activityRows = [];

if ($type === 'registrations') {
    $registrationRows = $db->fetchAll(
        "SELECT r.id, c.company_name, rt.name as registration_type, r.status, r.created_at, r.expiry_date
         FROM registrations r
         JOIN companies c ON r.company_id = c.id
         JOIN registration_types rt ON r.registration_type_id = rt.id
         WHERE r.created_at BETWEEN ? AND ?
         ORDER BY r.created_at DESC
         LIMIT 100",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

if ($type === 'tasks') {
    $taskRows = $db->fetchAll(
        "SELECT t.id, t.title, t.status, t.priority, t.due_date, t.completed_at,
                (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
                 FROM task_assignees ta2
                 JOIN users u2 ON u2.id = ta2.user_id
                 WHERE ta2.task_id = t.id) as assigned_to_names
         FROM tasks t
         WHERE t.created_at BETWEEN ? AND ?
         ORDER BY t.created_at DESC
         LIMIT 100",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

if ($type === 'activity') {
    $activityRows = $db->fetchAll(
        "SELECT al.*, u.name as user_name
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         WHERE al.created_at BETWEEN ? AND ?
         ORDER BY al.created_at DESC
         LIMIT 100",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

ob_start();
include 'views/reports/main.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

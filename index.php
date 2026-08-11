<?php

session_start();

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

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$db = Database::getInstance();
$user = getCurrentUser();

// Dashboard Statistics
// Dashboard Statistics with Error Handling
try {
    $totalCompanies = $db->query("SELECT COUNT(*) FROM companies WHERE status != 'churned'")->fetchColumn();
    $activeClients = $db->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn();
    $prospectClients = $db->query("SELECT COUNT(*) FROM companies WHERE status = 'prospect'")->fetchColumn();
} catch (Exception $e) {
    error_log("Dashboard company stats error: " . $e->getMessage());
    $totalCompanies = 0;
    $activeClients = 0;
    $prospectClients = 0;
}

try {
    $pendingRegistrations = $db->query(
        "SELECT COUNT(*) FROM registrations WHERE status IN ('pending', 'awaiting_documents', 'submitted', 'processing')"
    )->fetchColumn();

    $completedRegistrations = $db->query(
        "SELECT COUNT(*) FROM registrations WHERE status = 'approved'"
    )->fetchColumn();

    $expiringRegistrations = $db->query(
        "SELECT COUNT(*) FROM registrations WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND status = 'approved'"
    )->fetchColumn();

    $expiredRegistrations = $db->query(
        "SELECT COUNT(*) FROM registrations WHERE expiry_date < CURDATE() AND status = 'approved'"
    )->fetchColumn();
} catch (Exception $e) {
    error_log("Dashboard registration stats error: " . $e->getMessage());
    $pendingRegistrations = 0;
    $completedRegistrations = 0;
    $expiringRegistrations = 0;
    $expiredRegistrations = 0;
}

// Task Statistics
try {
    $pendingTasks = $db->query(
        "SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')"
    )->fetchColumn();

    $myPendingTasks = $db->query(
        "SELECT COUNT(DISTINCT t.id) FROM tasks t
         INNER JOIN task_assignees ta ON ta.task_id = t.id
         WHERE ta.user_id = ? AND t.status IN ('pending', 'in_progress')",
        [$user['id']]
    )->fetchColumn();

    $completedTasks = $db->query(
        "SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
    )->fetchColumn();
} catch (Exception $e) {
    error_log("Dashboard task stats error: " . $e->getMessage());
    $pendingTasks = 0;
    $myPendingTasks = 0;
    $completedTasks = 0;
}

// Upcoming meetings
try {
    $upcomingMeetings = $db->fetchAll(
        "SELECT m.*, c.company_name, DATE_FORMAT(m.meeting_date, '%D %b %Y') as formatted_date
         FROM meetings m
         LEFT JOIN companies c ON m.company_id = c.id
         INNER JOIN meeting_participants mp ON mp.meeting_id = m.id
         WHERE mp.user_id = ? AND m.meeting_date >= CURDATE() AND m.status = 'scheduled'
         ORDER BY m.meeting_date ASC, m.start_time ASC
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    error_log("Dashboard meetings error: " . $e->getMessage());
    $upcomingMeetings = [];
}

// Recent activities
try {
    $recentActivities = $db->fetchAll(
        "SELECT al.*, u.name as user_name
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT 10"
    );
} catch (Exception $e) {
    error_log("Dashboard activities error: " . $e->getMessage());
    $recentActivities = [];
}

// Expiring soon registrations
try {
    $expiringSoon = $db->fetchAll(
        "SELECT r.id, r.expiry_date, c.company_name, rt.name as registration_type,
                DATEDIFF(r.expiry_date, CURDATE()) as days_remaining
         FROM registrations r
         JOIN companies c ON r.company_id = c.id
         JOIN registration_types rt ON r.registration_type_id = rt.id
         WHERE r.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         AND r.status = 'approved'
         ORDER BY r.expiry_date ASC
         LIMIT 5"
    );
} catch (Exception $e) {
    error_log("Dashboard expiring registrations error: " . $e->getMessage());
    $expiringSoon = [];
}

// New this month
try {
    $newThisMonth = [
        'companies' => $db->query("SELECT COUNT(*) FROM companies WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn(),
        'registrations' => $db->query("SELECT COUNT(*) FROM registrations WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn(),
        'tasks_completed' => $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND updated_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn(),
    ];
} catch (Exception $e) {
    error_log("Dashboard new this month error: " . $e->getMessage());
    $newThisMonth = ['companies' => 0, 'registrations' => 0, 'tasks_completed' => 0];
}

// My tasks
try {
    $myTasks = $db->fetchAll(
        "SELECT t.*, c.company_name
         FROM tasks t
         LEFT JOIN companies c ON t.related_company_id = c.id
         INNER JOIN task_assignees ta ON ta.task_id = t.id
         WHERE ta.user_id = ? AND t.status IN ('pending', 'in_progress')
         ORDER BY t.due_date ASC, FIELD(t.priority, 'urgent', 'high', 'medium', 'low')
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    error_log("Dashboard my tasks error: " . $e->getMessage());
    $myTasks = [];
}

// Registration Status Counts for Chart
$registrationStatuses = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM registrations GROUP BY status"
);

// Monthly registrations chart data
$monthlyRegistrations = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count
     FROM registrations
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY created_at ASC"
);

// Load layout
ob_start();
include 'views/dashboard/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

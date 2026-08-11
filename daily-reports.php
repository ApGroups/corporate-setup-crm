<?php
/**
 * Daily Reports
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

$pageTitle = 'Daily Reports';
$activeNav = 'daily-reports';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($action) {
        case 'save':
            $reportData = [
                'user_id' => $user['id'],
                'report_date' => $_POST['report_date'],
                'time_started' => $_POST['time_started'] ?: null,
                'time_finished' => $_POST['time_finished'] ?: null,
                'activities' => sanitize($_POST['activities']),
                'projects_worked' => sanitize($_POST['projects_worked'] ?? ''),
                'clients_contacted' => sanitize($_POST['clients_contacted'] ?? ''),
                'issues_encountered' => sanitize($_POST['issues_encountered'] ?? ''),
                'tomorrow_plan' => sanitize($_POST['tomorrow_plan'] ?? ''),
                'status' => sanitize($_POST['status'] ?? 'draft'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            try {
                // Check if report exists for this date
                $existing = $db->fetch(
                    "SELECT id, status FROM daily_reports WHERE user_id = ? AND report_date = ?",
                    [$user['id'], $reportData['report_date']]
                );

                if ($existing && $existing['status'] !== 'draft') {
                    echo json_encode(['success' => false, 'error' => 'Report already submitted for this date']);
                    exit;
                }

                if ($existing) {
                    $db->update('daily_reports', $reportData, 'id = :id', ['id' => $existing['id']]);
                    logActivity($user['id'], 'update', 'Updated daily report', 'daily_reports', $existing['id']);
                    echo json_encode(['success' => true, 'message' => 'Report updated successfully']);
                } else {
                    $newId = $db->insert('daily_reports', $reportData);
                    logActivity($user['id'], 'create', 'Created daily report', 'daily_reports', $newId);
                    echo json_encode(['success' => true, 'message' => 'Report saved successfully', 'id' => $newId]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to save report']);
            }
            exit;

        case 'review':
            if ($id > 0) {
                $status = sanitize($_POST['status']);
                $comments = sanitize($_POST['reviewer_comments'] ?? '');

                try {
                    $db->update('daily_reports', [
                        'status' => $status,
                        'reviewed_by' => $user['id'],
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'reviewer_comments' => $comments
                    ], 'id = :id', ['id' => $id]);

                    logActivity($user['id'], $status, 'Reviewed daily report', 'daily_reports', $id);
                    echo json_encode(['success' => true, 'message' => 'Report reviewed successfully']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Failed to review report']);
                }
            }
            exit;
    }
}

// Filter parameters
$userId = intval($_GET['user'] ?? ($user['role'] === 'Administrator' || $user['role'] === 'Management' ? 0 : $user['id']));
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-t');
$page = intval($_GET['page'] ?? 1);

if (!$userId) $userId = $user['id'];

// Build query
$where = ['1=1'];
$params = [];

if ($userId) {
    $where[] = 'dr.user_id = :user_id';
    $params['user_id'] = $userId;
}

if ($status) {
    $where[] = 'dr.status = :status';
    $params['status'] = $status;
}

if ($dateFrom) {
    $where[] = 'dr.report_date >= :date_from';
    $params['date_from'] = $dateFrom;
}

if ($dateTo) {
    $where[] = 'dr.report_date <= :date_to';
    $params['date_to'] = $dateTo;
}

$whereClause = implode(' AND ', $where);
$totalCount = $db->query("SELECT COUNT(*) FROM daily_reports dr WHERE $whereClause", $params)->fetchColumn();

$perPage = 30;
$pagination = paginate($totalCount, $page, $perPage);

// Get reports
$reports = $db->fetchAll(
    "SELECT dr.*, u.name as user_name, d.name as department_name,
            rv.name as reviewer_name
     FROM daily_reports dr
     JOIN users u ON dr.user_id = u.id
     LEFT JOIN departments d ON u.department_id = d.id
     LEFT JOIN users rv ON dr.reviewed_by = rv.id
     WHERE $whereClause
     ORDER BY dr.report_date DESC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

// Get staff for filter
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

// Single report
$report = null;

if ($id > 0) {
    $report = $db->fetch(
        "SELECT dr.*, u.name as user_name, u.role_id, d.name as department_name,
                rv.name as reviewer_name, r.display_name as role_name
         FROM daily_reports dr
         JOIN users u ON dr.user_id = u.id
         LEFT JOIN departments d ON u.department_id = d.id
         LEFT JOIN users rv ON dr.reviewed_by = rv.id
         LEFT JOIN roles r ON u.role_id = r.id
         WHERE dr.id = ?",
        [$id]
    );
}

// Check today's report
$todayReport = $db->fetch(
    "SELECT * FROM daily_reports WHERE user_id = ? AND report_date = CURDATE()",
    [$user['id']]
);

ob_start();
include 'views/reports/daily.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

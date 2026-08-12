<?php
/**
 * Daily Reports
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

$canViewAllReports = checkPermission('view_reports') || checkPermission('approve_reports');
$canSubmitReports = checkPermission('submit_reports') || checkPermission('view_reports') || checkPermission('approve_reports');
$canApproveReports = checkPermission('approve_reports');

if (!$canSubmitReports && !$canViewAllReports) {
    requirePermission('submit_reports');
}

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
            if (!$canSubmitReports) {
                echo json_encode(['success' => false, 'error' => 'You are not authorized to submit reports']);
                exit;
            }

            $reportDate = $_POST['report_date'] ?? '';
            $activities = trim((string)($_POST['activities'] ?? ''));
            $status = sanitize($_POST['status'] ?? 'draft');

            if ($reportDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
                echo json_encode(['success' => false, 'error' => 'Valid report date is required']);
                exit;
            }

            if ($activities === '') {
                echo json_encode(['success' => false, 'error' => 'Activities are required']);
                exit;
            }

            if (!in_array($status, ['draft', 'submitted'], true)) {
                $status = 'draft';
            }

            $reportData = [
                'user_id' => $user['id'],
                'report_date' => $reportDate,
                'time_started' => $_POST['time_started'] ?: null,
                'time_finished' => $_POST['time_finished'] ?: null,
                'activities' => sanitize($activities),
                'projects_worked' => sanitize($_POST['projects_worked'] ?? ''),
                'clients_contacted' => sanitize($_POST['clients_contacted'] ?? ''),
                'issues_encountered' => sanitize($_POST['issues_encountered'] ?? ''),
                'status' => $status,
            ];

            try {
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
                    createOperationNotification((int) $user['id'], 'Daily report updated', 'Daily report updated successfully.', 'success', 'daily-reports.php?action=view&id=' . $existing['id'], [
                        'related_type' => 'daily_reports',
                        'related_id' => $existing['id']
                    ]);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Report updated successfully',
                        'redirect' => 'daily-reports.php?action=view&id=' . $existing['id']
                    ]);
                } else {
                    $reportData['created_at'] = date('Y-m-d H:i:s');
                    $newId = $db->insert('daily_reports', $reportData);
                    logActivity($user['id'], 'create', 'Created daily report', 'daily_reports', $newId);
                    createOperationNotification((int) $user['id'], 'Daily report submitted', 'Daily report saved successfully.', 'success', 'daily-reports.php?action=view&id=' . $newId, [
                        'related_type' => 'daily_reports',
                        'related_id' => $newId
                    ]);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Report saved successfully',
                        'id' => $newId,
                        'redirect' => 'daily-reports.php?action=view&id=' . $newId
                    ]);
                }
            } catch (Exception $e) {
                error_log('Daily report save error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save report']);
            }
            exit;

        case 'review':
            if (!$canApproveReports) {
                echo json_encode(['success' => false, 'error' => 'You are not authorized to review reports']);
                exit;
            }

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
                exit;
            }

            $status = sanitize($_POST['status'] ?? '');
            $comments = sanitize($_POST['reviewer_comments'] ?? '');

            if (!in_array($status, ['reviewed', 'approved'], true)) {
                echo json_encode(['success' => false, 'error' => 'Invalid review status']);
                exit;
            }

            try {
                $target = $db->fetch("SELECT id, status FROM daily_reports WHERE id = ?", [$id]);
                if (!$target) {
                    echo json_encode(['success' => false, 'error' => 'Report not found']);
                    exit;
                }

                $db->update('daily_reports', [
                    'status' => $status,
                    'reviewed_by' => $user['id'],
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewer_comments' => $comments
                ], 'id = :id', ['id' => $id]);

                logActivity($user['id'], $status, 'Reviewed daily report', 'daily_reports', $id);
                createOperationNotification((int) $user['id'], 'Daily report reviewed', 'Daily report reviewed successfully.', 'success', 'daily-reports.php?action=view&id=' . $id, [
                    'related_type' => 'daily_reports',
                    'related_id' => $id
                ]);
                $reportOwner = $db->fetch("SELECT u.id, u.name, u.email FROM daily_reports dr JOIN users u ON dr.user_id = u.id WHERE dr.id = ?", [$id]);
                if ($reportOwner) {
                    notify((int) $reportOwner['id'], 'Your daily report was reviewed', 'Your daily report was marked as ' . $status . '.', 'info', 'daily-reports.php?action=view&id=' . $id, [
                        'related_type' => 'daily_reports',
                        'related_id' => $id,
                        'email' => [
                            'to_email' => $reportOwner['email'],
                            'to_name' => $reportOwner['name'],
                            'subject' => 'Corporate Setup CRM - Daily Report Reviewed',
                            'html' => buildStaffAccountEmailHtml('Your Daily Report Was Reviewed', [
                                'Your daily report was marked as ' . ucfirst($status) . '.',
                                $comments !== '' ? ('Reviewer comments: ' . $comments) : 'Open the CRM to review the status and any comments.'
                            ], 'daily-reports.php?action=view&id=' . $id, 'View Daily Report'),
                            'text' => 'Your daily report was marked as ' . $status . '.'
                        ]
                    ]);
                }
                echo json_encode([
                    'success' => true,
                    'message' => 'Report reviewed successfully',
                    'redirect' => 'daily-reports.php?action=view&id=' . $id
                ]);
            } catch (Exception $e) {
                error_log('Daily report review error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to review report']);
            }
            exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Filter parameters
$filterUserParam = array_key_exists('user', $_GET) ? $_GET['user'] : null;
if ($canViewAllReports) {
    $userId = ($filterUserParam === null || $filterUserParam === '') ? 0 : intval($filterUserParam);
} else {
    $userId = $user['id'];
}

$status = $_GET['status'] ?? '';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-t');
$page = intval($_GET['page'] ?? 1);

// Build query
$where = ['1=1'];
$params = [];

if ($userId > 0) {
    $where[] = 'dr.user_id = :user_id';
    $params['user_id'] = $userId;
} elseif (!$canViewAllReports) {
    $where[] = 'dr.user_id = :user_id';
    $params['user_id'] = $user['id'];
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

$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

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

    // IDOR: staff without view_reports may only open their own reports
    if ($report && !$canViewAllReports && intval($report['user_id']) !== intval($user['id'])) {
        http_response_code(403);
        require 'views/errors/403.php';
        exit;
    }
}

$todayReport = $db->fetch(
    "SELECT * FROM daily_reports WHERE user_id = ? AND report_date = CURDATE()",
    [$user['id']]
);

// Prefill submit/edit form with today's draft when no explicit id
if (in_array($action, ['submit', 'new'], true) && !$report && $todayReport) {
    $report = $todayReport;
    if ($todayReport['status'] === 'draft') {
        $action = 'edit';
        $id = intval($todayReport['id']);
    }
}

if ($action === 'edit' && $report && (intval($report['user_id']) !== intval($user['id']) || $report['status'] !== 'draft')) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

ob_start();
include 'views/reports/daily.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

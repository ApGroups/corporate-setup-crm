<?php
/**
 * Monthly Reports (aggregated from daily reports)
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
require_once __DIR__ . '/includes/monthly_reports.php';

requireLogin();
requirePermission('view_reports');

$pageTitle = 'Monthly Reports';
$activeNav = 'monthly-reports';

$db = Database::getInstance();
$user = getCurrentUser();

$canGenerate = checkPermission('approve_reports') || checkPermission('manage_settings');
$canViewAll = checkPermission('approve_reports') || checkPermission('view_users');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if ($action === 'generate') {
        if (!$canGenerate) {
            echo json_encode(['success' => false, 'error' => 'Not authorized to generate monthly reports']);
            exit;
        }

        $year = intval($_POST['year'] ?? date('Y'));
        $month = intval($_POST['month'] ?? date('n'));
        $onlyUserId = intval($_POST['user_id'] ?? 0) ?: null;

        if ($month < 1 || $month > 12) {
            echo json_encode(['success' => false, 'error' => 'Invalid month']);
            exit;
        }

        try {
            $stats = generateMonthlyReportsForPeriod($db, $year, $month, (int)$user['id'], $onlyUserId);
            logActivity(
                $user['id'],
                'generate',
                sprintf('Generated monthly reports for %04d-%02d', $year, $month),
                'monthly_reports',
                null
            );
            echo json_encode([
                'success' => true,
                'message' => sprintf(
                    'Monthly reports ready: %d created, %d updated',
                    $stats['generated'],
                    $stats['updated']
                ),
                'reload' => true
            ]);
        } catch (Exception $e) {
            error_log('Monthly report generate error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to generate monthly reports']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

$filterUser = array_key_exists('user', $_GET) ? intval($_GET['user'] ?: 0) : ($canViewAll ? 0 : (int)$user['id']);
$filterYear = intval($_GET['year'] ?? date('Y'));
$filterMonth = intval($_GET['month'] ?? 0); // 0 = all months
$page = intval($_GET['page'] ?? 1);

if (!$canViewAll) {
    $filterUser = (int)$user['id'];
}

$where = ['1=1'];
$params = [];

if ($filterUser > 0) {
    $where[] = 'mr.user_id = :user_id';
    $params['user_id'] = $filterUser;
}
if ($filterYear > 0) {
    $where[] = 'mr.report_year = :year';
    $params['year'] = $filterYear;
}
if ($filterMonth > 0) {
    $where[] = 'mr.report_month = :month';
    $params['month'] = $filterMonth;
}

$whereClause = implode(' AND ', $where);
$totalCount = (int)$db->query("SELECT COUNT(*) FROM monthly_reports mr WHERE $whereClause", $params)->fetchColumn();
$perPage = 30;
$pagination = paginate($totalCount, $page, $perPage);

$reports = $db->fetchAll(
    "SELECT mr.*, u.name as user_name, d.name as department_name, g.name as generated_by_name
     FROM monthly_reports mr
     JOIN users u ON mr.user_id = u.id
     LEFT JOIN departments d ON mr.department_id = d.id
     LEFT JOIN users g ON mr.generated_by = g.id
     WHERE $whereClause
     ORDER BY mr.report_year DESC, mr.report_month DESC, u.name ASC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

$report = null;
if ($id > 0 && $action === 'view') {
    $report = $db->fetch(
        "SELECT mr.*, u.name as user_name, d.name as department_name, g.name as generated_by_name
         FROM monthly_reports mr
         JOIN users u ON mr.user_id = u.id
         LEFT JOIN departments d ON mr.department_id = d.id
         LEFT JOIN users g ON mr.generated_by = g.id
         WHERE mr.id = ?",
        [$id]
    );

    if ($report && !$canViewAll && intval($report['user_id']) !== intval($user['id'])) {
        http_response_code(403);
        require 'views/errors/403.php';
        exit;
    }
}

$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

ob_start();
include 'views/reports/monthly.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

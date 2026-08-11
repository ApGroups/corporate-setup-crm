<?php
/**
 * Activity Log
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
requirePermission('view_logs');

$pageTitle = 'Activity Log';
$activeNav = 'activity-log';

$db = Database::getInstance();
$user = getCurrentUser();

$filterUser = intval($_GET['user'] ?? 0);
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');
$actionFilter = sanitize($_GET['action_type'] ?? '');
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($filterUser > 0) {
    $where[] = 'al.user_id = :user_id';
    $params['user_id'] = $filterUser;
}
if ($dateFrom) {
    $where[] = 'DATE(al.created_at) >= :date_from';
    $params['date_from'] = $dateFrom;
}
if ($dateTo) {
    $where[] = 'DATE(al.created_at) <= :date_to';
    $params['date_to'] = $dateTo;
}
if ($actionFilter !== '') {
    $where[] = 'al.action = :action_type';
    $params['action_type'] = $actionFilter;
}

$whereClause = implode(' AND ', $where);
$totalCount = (int)$db->query("SELECT COUNT(*) FROM activity_logs al WHERE $whereClause", $params)->fetchColumn();
$perPage = 50;
$pagination = paginate($totalCount, $page, $perPage);

$activities = $db->fetchAll(
    "SELECT al.*, u.name as user_name
     FROM activity_logs al
     LEFT JOIN users u ON al.user_id = u.id
     WHERE $whereClause
     ORDER BY al.created_at DESC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
$actionTypes = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");

ob_start();
include 'views/activity/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

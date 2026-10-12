<?php
/**
 * Client Reports
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
require_once __DIR__ . '/includes/client_reports.php';

requireLogin();
requirePermission('view_companies');

$pageTitle = 'Client Reports';
$activeNav = 'client-reports';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_GET['action'] ?? 'list';
$companyId = (int) ($_GET['company_id'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$search = trim((string) ($_GET['q'] ?? ''));

if ($action === 'download') {
    $format = $_GET['format'] ?? 'html';
    $report = buildClientReport($db, $companyId, $from, $to, $user['id'] ?? null, true);
    if (empty($report['ok'])) {
        flashMessage('warning', $report['error'] ?? 'Unable to build the client report.');
        redirect('client-reports.php');
    }

    if ($format === 'csv') {
        exportToCSV(clientReportCsvRows($report), $report['reference']);
    }

    $filename = $report['reference'] . '.html';
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo renderClientReportHtml($report);
    exit;
}

$report = null;
$reportError = '';
if ($companyId > 0 && isset($_GET['from'], $_GET['to'])) {
    $built = buildClientReport($db, $companyId, $from, $to, $user['id'] ?? null, true);
    if (!empty($built['ok'])) {
        $report = $built;
    } else {
        $reportError = $built['error'] ?? 'Unable to build the client report.';
    }
}

$matches = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $matches = $db->fetchAll(
        "SELECT id, company_name, rc_number, status
         FROM companies
         WHERE company_name LIKE ? OR rc_number LIKE ? OR email LIKE ?
         ORDER BY company_name
         LIMIT 20",
        [$like, $like, $like]
    );
} elseif ($companyId > 0) {
    $matches = $db->fetchAll(
        "SELECT id, company_name, rc_number, status FROM companies WHERE id = ?",
        [$companyId]
    );
}

$history = $db->fetchAll(
    "SELECT cr.*, c.company_name, u.name AS generated_by_name
     FROM client_reports cr
     JOIN companies c ON c.id = cr.company_id
     LEFT JOIN users u ON u.id = cr.generated_by
     ORDER BY cr.generated_at DESC
     LIMIT 30"
);

ob_start();
include 'views/reports/client.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

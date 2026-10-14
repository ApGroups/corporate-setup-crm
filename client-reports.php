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

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$companyId = (int) ($_POST['company_id'] ?? $_GET['company_id'] ?? 0);
$from = $_POST['from'] ?? $_GET['from'] ?? date('Y-m-01');
$to = $_POST['to'] ?? $_GET['to'] ?? date('Y-m-d');
$search = trim((string) ($_GET['q'] ?? ''));

$messageInput = [
    'lines' => is_array($_POST['lines'] ?? null) ? $_POST['lines'] : [],
    'note' => trim((string) ($_POST['note'] ?? '')),
    'invoice_document_id' => (int) ($_POST['invoice_document_id'] ?? 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['save_message', 'download'], true)) {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('warning', 'Invalid security token. Please try again.');
        redirect('client-reports.php');
    }

    $report = buildClientReport($db, $companyId, $from, $to, $user['id'] ?? null, true, $messageInput);
    if (empty($report['ok'])) {
        flashMessage('warning', $report['error'] ?? 'Unable to build the client report.');
        redirect('client-reports.php');
    }

    if ($action === 'save_message') {
        logActivity($user['id'], 'create', 'Saved client update for ' . $report['company']['company_name'], 'client_reports', 0);
        flashMessage('success', 'Client update saved. Registration records were not changed.');
        redirect('client-reports.php?company_id=' . (int) $report['company']['id'] . '&from=' . urlencode($report['from']) . '&to=' . urlencode($report['to']) . '&q=' . urlencode($report['company']['company_name']));
    }

    $format = $_POST['format'] ?? 'html';
    if ($format === 'csv') {
        exportToCSV(clientReportCsvRows($report), $report['reference'] ?: 'elite-message');
    }
    if ($format === 'txt') {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . ($report['reference'] ?: 'client-report') . '.txt"');
        echo renderEliteMessagesText([$report]);
        exit;
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . ($report['reference'] ?: 'client-report') . '.html"');
    echo renderEliteMessagesHtml([$report]);
    exit;
}

if ($action === 'download' || $action === 'extract') {
    $format = $_GET['format'] ?? ($action === 'extract' ? 'txt' : 'html');
    $reports = [];

    if ($action === 'extract') {
        $eliteCompanies = $db->fetchAll(
            "SELECT DISTINCT em.company_id
             FROM elite_members em
             WHERE em.status = 'active'
             ORDER BY em.company_id"
        );
        foreach ($eliteCompanies as $eliteCompany) {
            $built = buildClientReport($db, $eliteCompany['company_id'], $from, $to, $user['id'] ?? null, true);
            if (!empty($built['ok'])) {
                $reports[] = $built;
            }
        }
        if (!$reports) {
            flashMessage('warning', 'No active elite memberships to extract.');
            redirect('client-reports.php');
        }
    } else {
        $report = buildClientReport($db, $companyId, $from, $to, $user['id'] ?? null, true);
        if (empty($report['ok'])) {
            flashMessage('warning', $report['error'] ?? 'Unable to build the client report.');
            redirect('client-reports.php');
        }
        $reports[] = $report;
    }

    if ($format === 'csv') {
        exportToCSV(clientReportCsvRows($reports[0]), $reports[0]['reference'] ?: 'elite-messages');
    }

    if ($format === 'txt') {
        $filename = ($action === 'extract' ? 'Elite-Monthly-Messages-' . date('Ymd') : ($reports[0]['reference'] ?: 'client-report')) . '.txt';
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo renderEliteMessagesText($reports);
        exit;
    }

    $filename = ($action === 'extract' ? 'Elite-Monthly-Messages-' . date('Ymd') : ($reports[0]['reference'] ?: 'client-report')) . '.html';
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo renderEliteMessagesHtml($reports);
    exit;
}

$report = null;
$reportError = '';
if ($companyId > 0 && isset($_GET['from'], $_GET['to']) && $action !== 'download' && $action !== 'extract') {
        $built = buildClientReport($db, $companyId, $from, $to, $user['id'] ?? null, false);
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

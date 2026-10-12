<?php
/**
 * Export Handler
 * Corporate Setup Limited - Internal CRM
 */



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
require_once __DIR__ . '/includes/crm_exports.php';

// Authentication
require_once __DIR__ . '/includes/auth.php';

// User must be logged in
requireLogin();
requirePermission('export_data');

$db = Database::getInstance();
$user = getCurrentUser();

$type = $_GET['type'] ?? 'summary';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'csv';

function outputCSV($filename, $data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit();
}

function outputExcel($filename, $data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';

    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th style="background:#0f2942;color:#fff;">' . sanitize($header) . '</th>';
        }
        echo '</tr>';

        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . sanitize($cell) . '</td>';
            }
            echo '</tr>';
        }
    }

    echo '</table></body></html>';
    exit();
}

$filename = 'crm_' . $type . '_' . $from . '_to_' . $to;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

switch ($type) {
    case 'companies':
        $data = exportCompanies($db, $from, $to);
        break;

    case 'registrations':
        $data = exportRegistrations($db, $from, $to);
        break;

    case 'tasks':
        $data = exportTasks($db, $from, $to);
        break;

    case 'compliance':
        $data = exportCompliance($db);
        break;

    case 'elites':
        $data = exportElites($db, $search, $status);
        break;

    case 'documents':
        $data = exportDocuments($db, $from, $to);
        break;

    default:
        $data = exportSummary($db);
        break;
}

if (empty($data)) {
    flashMessage('warning', 'No data to export for the selected criteria.');
    redirect('reports.php');
}

if ($format === 'excel') {
    outputExcel($filename, $data);
} else {
    outputCSV($filename, $data);
}

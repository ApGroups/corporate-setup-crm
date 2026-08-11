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

// Authentication
require_once __DIR__ . '/includes/auth.php';

// User must be logged in
requireLogin();

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

switch ($type) {
    case 'companies':
        $data = $db->fetchAll(
            "SELECT c.company_name, c.rc_number, c.industry_name, c.email, c.phone, c.status,
                    c.subscription_status, c.client_since, c.assigned_staff
             FROM vw_companies_export c
             ORDER BY c.company_name"
        );
        break;

    case 'registrations':
        $data = $db->fetchAll(
            "SELECT c.company_name, r.registration_type, r.status, r.priority,
                    r.submission_date, r.expiry_date, r.assigned_staff, r.remarks
             FROM registrations r
             JOIN companies c ON r.company_id = c.id
             JOIN registration_types rt ON r.registration_type_id = rt.id
             LEFT JOIN users u ON r.assigned_staff_id = u.id
             WHERE r.created_at BETWEEN ? AND ?
             ORDER BY r.created_at DESC",
            [$from . ' 00:00:00', $to . ' 23:59:59']
        );
        break;

    case 'tasks':
        $data = $db->fetchAll(
            "SELECT t.title, t.task_type, t.status, t.priority, t.due_date,
                    t.assigned_to_name as assigned_to, t.created_at, t.completed_at
             FROM tasks t
             WHERE t.created_at BETWEEN ? AND ?
             ORDER BY t.created_at DESC",
            [$from . ' 00:00:00', $to . ' 23:59:59']
        );
        break;

    case 'compliance':
        $data = $db->fetchAll(
            "SELECT c.company_name, rt.name as registration_type,
                    r.status, r.expiry_date, DATEDIFF(r.expiry_date, CURDATE()) as days_remaining,
                    r.assigned_staff
             FROM registrations r
             JOIN companies c ON r.company_id = c.id
             JOIN registration_types rt ON r.registration_type_id = rt.id
             LEFT JOIN users u ON r.assigned_staff_id = u.id
             WHERE r.status = 'approved' AND r.expiry_date IS NOT NULL
             ORDER BY r.expiry_date ASC"
        );
        break;

    default:
        $data = $db->fetchAll(
            "SELECT
                (SELECT COUNT(*) FROM companies WHERE status != 'churned') as total_companies,
                (SELECT COUNT(*) FROM companies WHERE status = 'active') as active_clients,
                (SELECT COUNT(*) FROM registrations WHERE status IN ('pending', 'submitted', 'processing')) as pending_registrations,
                (SELECT COUNT(*) FROM registrations WHERE status = 'approved') as approved_registrations,
                (SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')) as pending_tasks,
                (SELECT COUNT(*) FROM tasks WHERE status = 'completed') as completed_tasks"
        );
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

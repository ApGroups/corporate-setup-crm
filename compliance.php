<?php
/**
 * Compliance Tracker
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

$pageTitle = 'Compliance Tracker';
$activeNav = 'compliance';

$db = Database::getInstance();
$user = getCurrentUser();

$filter = $_GET['filter'] ?? 'all';
$companyId = intval($_GET['company'] ?? 0);

// Build query based on filter
$where = ['1=1'];
$params = [];

if ($companyId) {
    $where[] = 'r.company_id = :company_id';
    $params['company_id'] = $companyId;
}

switch ($filter) {
    case 'expired':
        $where[] = 'r.expiry_date < CURDATE() AND r.status = "approved"';
        break;
    case 'expiring':
        $where[] = 'r.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND r.status = "approved"';
        break;
    case 'pending':
        $where[] = 'r.status IN ("pending", "awaiting_documents", "submitted", "processing")';
        break;
    case 'approved':
        $where[] = 'r.status = "approved"';
        break;
    case 'rejected':
        $where[] = 'r.status = "rejected"';
        break;
}

$whereClause = implode(' AND ', $where);

$registrations = $db->fetchAll(
    "SELECT r.*, c.company_name, rt.name as registration_type, u.name as assigned_staff
     FROM registrations r
     JOIN companies c ON r.company_id = c.id
     JOIN registration_types rt ON r.registration_type_id = rt.id
     LEFT JOIN users u ON r.assigned_staff_id = u.id
     WHERE $whereClause
     ORDER BY r.expiry_date ASC, r.updated_at DESC",
    $params
);

// Get companies for filter
$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");

// Stats
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM registrations r WHERE r.expiry_date IS NOT NULL OR r.status IN ('pending', 'submitted', 'processing')")->fetchColumn(),
    'expired' => $db->query("SELECT COUNT(*) FROM registrations WHERE expiry_date < CURDATE() AND status = 'approved'")->fetchColumn(),
    'expiring' => $db->query("SELECT COUNT(*) FROM registrations WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND status = 'approved'")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM registrations WHERE status IN ('pending', 'awaiting_documents', 'submitted', 'processing')")->fetchColumn(),
    'approved' => $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'approved'")->fetchColumn(),
];

ob_start();
include 'views/compliance/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

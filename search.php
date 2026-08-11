<?php
/**
 * Global Search
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

$pageTitle = 'Search Results';
$activeNav = 'search';

$db = Database::getInstance();
$user = getCurrentUser();

$query = sanitize($_GET['q'] ?? '');
$results = [];

if (strlen($query) >= 2) {
    $searchTerm = "%$query%";

    if (checkPermission('view_companies')) {
        $results['companies'] = $db->fetchAll(
            "SELECT id, company_name, email, phone, status FROM companies
             WHERE company_name LIKE ? OR rc_number LIKE ? OR email LIKE ? OR contact_person LIKE ?
             LIMIT 10",
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
    }

    if (checkPermission('view_registrations')) {
        $results['registrations'] = $db->fetchAll(
            "SELECT r.id, c.company_name, rt.name as registration_type, r.status, r.expiry_date
             FROM registrations r
             JOIN companies c ON r.company_id = c.id
             JOIN registration_types rt ON r.registration_type_id = rt.id
             WHERE c.company_name LIKE ? OR rt.name LIKE ?
             LIMIT 10",
            [$searchTerm, $searchTerm]
        );
    }

    if (checkPermission('view_tasks')) {
        $results['tasks'] = $db->fetchAll(
            "SELECT t.id, t.title, t.status, t.due_date,
                    (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
                     FROM task_assignees ta2
                     JOIN users u2 ON u2.id = ta2.user_id
                     WHERE ta2.task_id = t.id) as assigned_to
             FROM tasks t
             WHERE t.title LIKE ? OR t.description LIKE ?
             LIMIT 10",
            [$searchTerm, $searchTerm]
        );
    }

    if (checkPermission('view_documents')) {
        $results['documents'] = $db->fetchAll(
            "SELECT d.id, d.document_name, d.document_type, c.company_name
             FROM documents d
             LEFT JOIN companies c ON d.company_id = c.id
             WHERE d.document_name LIKE ? AND d.status = 'active'
             LIMIT 10",
            [$searchTerm]
        );
    }

    if (checkPermission('view_users')) {
        $results['staff'] = $db->fetchAll(
            "SELECT id, name, email, department_id FROM users
             WHERE name LIKE ? OR email LIKE ?
             LIMIT 10",
            [$searchTerm, $searchTerm]
        );
    }
}

$totalResults = array_sum(array_map('count', $results));

ob_start();
include 'views/search/results.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

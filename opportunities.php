<?php
/**
 * Opportunities
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

// Permission check
requirePermission('view_opportunities');

$pageTitle = 'Opportunities';
$activeNav = 'opportunities';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = intval($_GET['page'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add', 'edit'], true) && !checkPermission('manage_opportunities')) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if (!checkPermission('manage_opportunities')) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to manage opportunities']);
        exit;
    }

    if ($action === 'save') {
        $opportunityData = [
            'opportunity_title' => sanitize($_POST['opportunity_title'] ?? ''),
            'opportunity_type' => sanitize($_POST['opportunity_type'] ?? 'other'),
            'source' => sanitize($_POST['source'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'company_id' => intval($_POST['company_id'] ?? 0) ?: null,
            'assigned_staff_id' => intval($_POST['assigned_staff_id'] ?? 0) ?: null,
            'estimated_value' => floatval($_POST['estimated_value'] ?? 0) ?: null,
            'deadline' => $_POST['deadline'] ?: null,
            'requirements' => sanitize($_POST['requirements'] ?? ''),
            'status' => sanitize($_POST['status'] ?? 'new'),
            'probability' => max(0, min(100, intval($_POST['probability'] ?? 50))),
            'result_notes' => sanitize($_POST['result_notes'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($opportunityData['opportunity_title'] === '' || $opportunityData['opportunity_type'] === '') {
            echo json_encode(['success' => false, 'error' => 'Title and type are required']);
            exit;
        }

        try {
            if ($id > 0) {
                $db->update('opportunities', $opportunityData, 'id = :id', ['id' => $id]);
                logActivity($user['id'], 'update', 'Updated opportunity: ' . $opportunityData['opportunity_title'], 'opportunities', $id);
                echo json_encode(['success' => true, 'message' => 'Opportunity updated successfully', 'redirect' => 'opportunities.php?action=view&id=' . $id]);
            } else {
                $opportunityData['created_by'] = $user['id'];
                $opportunityData['created_at'] = date('Y-m-d H:i:s');
                $newId = $db->insert('opportunities', $opportunityData);
                logActivity($user['id'], 'create', 'Created opportunity: ' . $opportunityData['opportunity_title'], 'opportunities', $newId);
                echo json_encode(['success' => true, 'message' => 'Opportunity created successfully', 'redirect' => 'opportunities.php?action=view&id=' . $newId]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to save opportunity: ' . $e->getMessage()]);
        }
        exit;
    }
}

$where = ['1=1'];
$params = [];

if ($type) {
    $where[] = 'o.opportunity_type = :type';
    $params['type'] = $type;
}
if ($status && $status !== 'all') {
    $where[] = 'o.status = :status';
    $params['status'] = $status;
}

$whereClause = implode(' AND ', $where);
$total = $db->query("SELECT COUNT(*) FROM opportunities o WHERE $whereClause", $params)->fetchColumn();
$pagination = paginate($total, $page, 20);

$opportunities = $db->fetchAll(
    "SELECT o.*, c.company_name, u.name as assigned_staff
     FROM opportunities o
     LEFT JOIN companies c ON o.company_id = c.id
     LEFT JOIN users u ON o.assigned_staff_id = u.id
     WHERE $whereClause
     ORDER BY o.deadline ASC, o.created_at DESC
     LIMIT {$pagination['offset']}, 20",
    $params
);

$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

$opportunity = null;
if ($id > 0) {
    $opportunity = $db->fetch(
        "SELECT o.*, c.company_name, u.name as assigned_staff
         FROM opportunities o
         LEFT JOIN companies c ON o.company_id = c.id
         LEFT JOIN users u ON o.assigned_staff_id = u.id
         WHERE o.id = ?",
        [$id]
    );
}

ob_start();
include 'views/opportunities/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

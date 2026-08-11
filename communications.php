<?php
/**
 * Communications Log
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
requirePermission('view_communications');
$pageTitle = 'Communications';
$activeNav = 'communications';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$companyId = intval($_GET['company'] ?? 0);
$type = $_GET['type'] ?? '';
$page = intval($_GET['page'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add', 'edit'], true) && !checkPermission('manage_communications')) {
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

    if (!checkPermission('manage_communications')) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to manage communications']);
        exit;
    }

    if ($action === 'save') {
        $communicationData = [
            'company_id' => intval($_POST['company_id'] ?? 0),
            'contact_id' => intval($_POST['contact_id'] ?? 0) ?: null,
            'user_id' => $user['id'],
            'communication_type' => sanitize($_POST['communication_type'] ?? ''),
            'subject' => sanitize($_POST['subject'] ?? ''),
            'summary' => sanitize($_POST['summary'] ?? ''),
            'outcome' => sanitize($_POST['outcome'] ?? ''),
            'next_action' => sanitize($_POST['next_action'] ?? ''),
            'next_action_date' => $_POST['next_action_date'] ?: null,
            'duration_minutes' => intval($_POST['duration_minutes'] ?? 0) ?: null,
            'communication_date' => $_POST['communication_date'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($communicationData['company_id'] <= 0 || $communicationData['communication_type'] === '' || $communicationData['summary'] === '' || $communicationData['communication_date'] === null) {
            echo json_encode(['success' => false, 'error' => 'Company, type, summary and date are required']);
            exit;
        }

        try {
            $communicationData['created_at'] = date('Y-m-d H:i:s');
            $newId = $db->insert('communications', $communicationData);
            logActivity($user['id'], 'create', 'Logged communication', 'communications', $newId);
            echo json_encode(['success' => true, 'message' => 'Communication logged successfully', 'redirect' => 'communications.php?action=view&id=' . $newId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to log communication: ' . $e->getMessage()]);
        }
        exit;
    }
}

$where = ['1=1'];
$params = [];

if ($companyId) {
    $where[] = 'comm.company_id = :company_id';
    $params['company_id'] = $companyId;
}
if ($type) {
    $where[] = 'comm.communication_type = :type';
    $params['type'] = $type;
}

$whereClause = implode(' AND ', $where);
$total = $db->query("SELECT COUNT(*) FROM communications comm WHERE $whereClause", $params)->fetchColumn();
$perPage = 20;
$pagination = paginate($total, $page, $perPage);

$communications = $db->fetchAll(
    "SELECT comm.*, c.company_name, u.name as staff_name, cc.name as contact_name
     FROM communications comm
     JOIN companies c ON comm.company_id = c.id
     LEFT JOIN users u ON comm.user_id = u.id
     LEFT JOIN company_contacts cc ON comm.contact_id = cc.id
     WHERE $whereClause
     ORDER BY comm.communication_date DESC
     LIMIT {$pagination['offset']}, $perPage",
    $params
);

$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");

$communication = null;
if ($id > 0) {
    $communication = $db->fetch(
        "SELECT comm.*, c.company_name, u.name as staff_name, cc.name as contact_name
         FROM communications comm
         JOIN companies c ON comm.company_id = c.id
         LEFT JOIN users u ON comm.user_id = u.id
         LEFT JOIN company_contacts cc ON comm.contact_id = cc.id
         WHERE comm.id = ?",
        [$id]
    );
}

ob_start();
include 'views/communications/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

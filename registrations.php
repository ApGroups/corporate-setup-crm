<?php

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
requirePermission('view_registrations');

$pageTitle = 'Registrations';
$activeNav = 'registrations';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$companyId = intval($_GET['company'] ?? 0);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($action) {
        case 'save':
            $regData = [
                'company_id' => intval($_POST['company_id']),
                'registration_type_id' => intval($_POST['registration_type_id']),
                'status' => sanitize($_POST['status'] ?? 'pending'),
                'priority' => sanitize($_POST['priority'] ?? 'medium'),
                'assigned_staff_id' => intval($_POST['assigned_staff_id'] ?? 0) ?: null,
                'requirements_checklist' => $_POST['requirements_checklist'] ?? null,
                'documents_received' => sanitize($_POST['documents_received'] ?? ''),
                'documents_missing' => sanitize($_POST['documents_missing'] ?? ''),
                'submission_date' => $_POST['submission_date'] ?: null,
                'expected_completion_date' => $_POST['expected_completion_date'] ?: null,
                'expiry_date' => $_POST['expiry_date'] ?: null,
                'remarks' => sanitize($_POST['remarks'] ?? ''),
                'next_follow_up_date' => $_POST['next_follow_up_date'] ?: null,
                'regulator_reference' => sanitize($_POST['regulator_reference'] ?? ''),
                'cost' => floatval($_POST['cost'] ?? 0),
                'payment_status' => sanitize($_POST['payment_status'] ?? 'pending'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            try {
                if ($id > 0) {
                    $db->update('registrations', $regData, 'id = :id', ['id' => $id]);
                    logActivity($user['id'], 'update', 'Updated registration', 'registrations', $id);
                    echo json_encode(['success' => true, 'message' => 'Registration updated successfully']);
                } else {
                    $regData['created_by'] = $user['id'];
                    $regData['created_at'] = date('Y-m-d H:i:s');
                    $newId = $db->insert('registrations', $regData);
                    logActivity($user['id'], 'create', 'Created registration', 'registrations', $newId);
                    echo json_encode(['success' => true, 'message' => 'Registration created successfully', 'id' => $newId]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to save registration: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
            if ($id > 0) {
                try {
                    $reg = $db->fetch("SELECT * FROM registrations WHERE id = ?", [$id]);
                    if ($reg) {
                        $db->delete('registrations', 'id = :id', ['id' => $id]);
                        logActivity($user['id'], 'delete', 'Deleted registration', 'registrations', $id);
                        echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Registration not found']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete registration']);
                }
            }
            exit;

        case 'update_status':
            if ($id > 0) {
                $newStatus = sanitize($_POST['status']);
                try {
                    $db->update('registrations', [
                        'status' => $newStatus,
                        'completion_date' => $newStatus === 'approved' ? date('Y-m-d') : null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', ['id' => $id]);

                    // Get current status before update for history
                    $currentReg = $db->fetch("SELECT status FROM registrations WHERE id = ?", [$id]);
                    $oldStatus = ($currentReg && isset($currentReg['status'])) ? $currentReg['status'] : '';

                    $db->insert('registration_history', [
                        'registration_id' => $id,
                        'user_id' => $user['id'],
                        'action' => 'Status Update',
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'remarks' => sanitize($_POST['remarks'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Status updated']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
                }
            }
            exit;
    }
}

// Filter parameters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned = intval($_GET['assigned'] ?? 0);
$filter = $_GET['filter'] ?? '';
$search = sanitize($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);

// Build query
$where = ['1=1'];
$params = [];

if ($companyId) {
    $where[] = 'r.company_id = :company_id';
    $params['company_id'] = $companyId;
}

if ($status) {
    $where[] = 'r.status = :status';
    $params['status'] = $status;
}

if ($priority) {
    $where[] = 'r.priority = :priority';
    $params['priority'] = $priority;
}

if ($assigned) {
    $where[] = 'r.assigned_staff_id = :assigned';
    $params['assigned'] = $assigned;
}

if ($filter === 'expiring') {
    $where[] = 'r.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
    $where[] = 'r.status = "approved"';
}

if ($search) {
    $where[] = '(c.company_name LIKE :search OR rt.name LIKE :search)';
    $params['search'] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$totalCount = $db->query("SELECT COUNT(*) FROM registrations r JOIN companies c ON r.company_id = c.id WHERE $whereClause", $params)->fetchColumn();

$perPage = 20;
$pagination = paginate($totalCount, $page, $perPage);

// Get registrations
$registrations = $db->fetchAll(
    "SELECT r.*, c.company_name, rt.name as registration_type, u.name as assigned_staff
     FROM registrations r
     JOIN companies c ON r.company_id = c.id
     JOIN registration_types rt ON r.registration_type_id = rt.id
     LEFT JOIN users u ON r.assigned_staff_id = u.id
     WHERE $whereClause
     ORDER BY r.updated_at DESC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

// Get lookups
$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$regTypes = $db->fetchAll("SELECT id, name, category FROM registration_types WHERE status = 'active' ORDER BY name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

// Single registration
$registration = null;
$history = [];
$relatedDocs = [];

if ($id > 0) {
    $registration = $db->fetch(
        "SELECT r.*, c.company_name, c.rc_number, c.phone, c.email, rt.name as registration_type,
                rt.description as reg_description, rt.validity_period, u.name as assigned_staff
         FROM registrations r
         JOIN companies c ON r.company_id = c.id
         JOIN registration_types rt ON r.registration_type_id = rt.id
         LEFT JOIN users u ON r.assigned_staff_id = u.id
         WHERE r.id = ?",
        [$id]
    );

    if ($registration) {
        $history = $db->fetchAll(
            "SELECT rh.*, u.name as user_name FROM registration_history rh LEFT JOIN users u ON rh.user_id = u.id WHERE rh.registration_id = ? ORDER BY rh.created_at DESC",
            [$id]
        );
        $relatedDocs = $db->fetchAll("SELECT * FROM documents WHERE registration_id = ? ORDER BY created_at DESC", [$id]);
    }
}

ob_start();
include 'views/registrations/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

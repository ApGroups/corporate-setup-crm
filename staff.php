<?php
/**
 * Staff Management
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
requirePermission('view_users');

$pageTitle = 'Staff Management';
$activeNav = 'staff';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$tab = $_GET['tab'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add', 'edit'], true) && !checkPermission('manage_users')) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if (!checkPermission('manage_users')) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to manage staff']);
        exit;
    }

    switch ($action) {
        case 'save':
            $name = sanitize($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $employeeId = sanitize($_POST['employee_id'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $position = sanitize($_POST['position'] ?? '');
            $roleId = intval($_POST['role_id'] ?? 0);
            $departmentId = intval($_POST['department_id'] ?? 0) ?: null;
            $status = sanitize($_POST['status'] ?? 'active');
            $password = (string)($_POST['password'] ?? '');
            $mustChangePassword = isset($_POST['must_change_password']) ? 1 : 0;

            if ($name === '' || $email === '' || $roleId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Name, email and role are required']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email address']);
                exit;
            }

            if ($id <= 0 && strlen($password) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
                exit;
            }

            if ($id > 0 && $password !== '' && strlen($password) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
                exit;
            }

            if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
                $status = 'active';
            }

            $existing = $db->fetch('SELECT id FROM users WHERE email = :email', ['email' => $email]);
            if ($existing) {
                if ($id <= 0 || intval($existing['id']) !== $id) {
                    echo json_encode(['success' => false, 'error' => 'Email already exists']);
                    exit;
                }
            }

            if ($employeeId !== '') {
                $existingEmployee = $db->fetch('SELECT id FROM users WHERE employee_id = :employee_id', ['employee_id' => $employeeId]);
                if ($existingEmployee) {
                    if ($id <= 0 || intval($existingEmployee['id']) !== $id) {
                        echo json_encode(['success' => false, 'error' => 'Employee ID already exists']);
                        exit;
                    }
                }
            }

            try {
                $staffData = [
                    'employee_id' => $employeeId !== '' ? $employeeId : null,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role_id' => $roleId,
                    'department_id' => $departmentId,
                    'position' => $position,
                    'status' => $status,
                    'must_change_password' => $mustChangePassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($id > 0) {
                    if ($password !== '') {
                        $staffData['password'] = password_hash($password, HASH_ALGO);
                    }

                    $db->update('users', $staffData, 'id = :id', ['id' => $id]);
                    logActivity($user['id'], 'update', 'Updated staff account: ' . $name, 'users', $id);
                    echo json_encode(['success' => true, 'message' => 'Staff updated successfully', 'redirect' => 'staff.php?action=view&id=' . $id]);
                } else {
                    $staffData['password'] = password_hash($password, HASH_ALGO);
                    $staffData['created_at'] = date('Y-m-d H:i:s');

                    $newId = $db->insert('users', $staffData);
                    logActivity($user['id'], 'create', 'Created staff account: ' . $name, 'users', $newId);
                    echo json_encode(['success' => true, 'message' => 'Staff added successfully', 'redirect' => 'staff.php?action=view&id=' . $newId]);
                }

            } catch (Exception $e) {
                error_log('Staff create error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save staff: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid staff ID']);
                exit;
            }

            if ($id === intval($user['id'])) {
                echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
                exit;
            }

            try {
                $staffToDelete = $db->fetch('SELECT id, name, status FROM users WHERE id = ?', [$id]);
                if (!$staffToDelete) {
                    echo json_encode(['success' => false, 'error' => 'Staff record not found']);
                    exit;
                }

                if ($staffToDelete['status'] === 'inactive') {
                    echo json_encode(['success' => true, 'message' => 'Staff already inactive', 'reload' => true]);
                    exit;
                }

                // Safe delete strategy: deactivate account instead of hard delete,
                // because users are referenced by many records with FK NO ACTION.
                $db->update('users', [
                    'status' => 'inactive',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);

                logActivity($user['id'], 'delete', 'Deactivated staff account: ' . $staffToDelete['name'], 'users', $id);
                echo json_encode(['success' => true, 'message' => 'Staff deactivated successfully', 'reload' => true]);
            } catch (Exception $e) {
                error_log('Staff deactivate error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to deactivate staff: ' . $e->getMessage()]);
            }
            exit;
    }
}

$staffMember = null;
if ($id > 0) {
    $staffMember = $db->fetch(
        "SELECT u.*, r.display_name as role_name, d.name as department_name
         FROM users u
         LEFT JOIN roles r ON u.role_id = r.id
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE u.id = ?",
        [$id]
    );
}

// Get staff
$staff = $db->fetchAll(
    "SELECT u.*, r.display_name as role_name, d.name as department_name
     FROM users u
     LEFT JOIN roles r ON u.role_id = r.id
     LEFT JOIN departments d ON u.department_id = d.id
     ORDER BY u.name"
);

// Get lookup data
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY display_name");
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");

ob_start();
include 'views/staff/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

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
$auth = new Auth();

function staffOptionalText($value) {
    $value = trim((string) $value);
    return $value === '' ? null : sanitize($value);
}

function staffContactEmail($value) {
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return null;
    }
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : false;
}

function staffContactRowsValid(array $source, $prefix) {
    $emails = $source[$prefix . '_email'] ?? [];
    if (!is_array($emails)) {
        return null;
    }
    foreach ($emails as $email) {
        if (staffContactEmail($email) === false) {
            return 'Enter a valid email or leave it blank.';
        }
    }
    return null;
}

function saveStaffContactRows(Database $db, $userId, $prefix, $table) {
    $ids = $_POST[$prefix . '_id'] ?? [];
    $names = $_POST[$prefix . '_name'] ?? [];
    $relationships = $_POST[$prefix . '_relationship'] ?? [];
    $phones = $_POST[$prefix . '_phone'] ?? [];
    $altPhones = $_POST[$prefix . '_alt_phone'] ?? [];
    $emails = $_POST[$prefix . '_email'] ?? [];
    $addresses = $_POST[$prefix . '_address'] ?? [];
    $cities = $_POST[$prefix . '_city'] ?? [];
    $states = $_POST[$prefix . '_state'] ?? [];
    $countries = $_POST[$prefix . '_country'] ?? [];
    $notes = $_POST[$prefix . '_notes'] ?? [];
    $methods = $_POST[$prefix . '_method'] ?? [];
    $orders = $_POST[$prefix . '_order'] ?? [];
    $removals = $_POST[$prefix . '_remove'] ?? [];
    if (!is_array($ids)) {
        return;
    }

    foreach ($ids as $index => $rawId) {
        $rowId = (int) $rawId;
        $removed = is_array($removals) && !empty($removals[$index]);
        if ($removed && $rowId > 0) {
            $db->delete($table, 'id = :id AND user_id = :user_id', ['id' => $rowId, 'user_id' => $userId]);
            continue;
        }

        $name = staffOptionalText($names[$index] ?? '');
        if ($name === null) {
            continue;
        }

        $data = [
            'full_name' => $name,
            'relationship' => staffOptionalText($relationships[$index] ?? ''),
            'phone' => staffOptionalText($phones[$index] ?? ''),
            'alt_phone' => staffOptionalText($altPhones[$index] ?? ''),
            'email' => staffContactEmail($emails[$index] ?? ''),
            'address' => staffOptionalText($addresses[$index] ?? ''),
        ];
        if ($prefix === 'kin') {
            $data['city'] = staffOptionalText($cities[$index] ?? '');
            $data['state'] = staffOptionalText($states[$index] ?? '');
            $data['country'] = staffOptionalText($countries[$index] ?? '');
            $data['notes'] = staffOptionalText($notes[$index] ?? '');
        } else {
            $data['preferred_method'] = staffOptionalText($methods[$index] ?? '');
            $data['sort_order'] = max(1, (int) ($orders[$index] ?? 1));
        }

        if ($rowId > 0) {
            $db->update($table, $data, 'id = :id AND user_id = :user_id', ['id' => $rowId, 'user_id' => $userId]);
        } else {
            $data['user_id'] = $userId;
            $db->insert($table, $data);
        }
    }
}

function staffProfileSections(array $staffMember, $kinCount, $emergencyCount) {
    $sections = [
        'Personal details' => trim((string) ($staffMember['name'] ?? '')) !== '' && trim((string) ($staffMember['email'] ?? '')) !== '',
        'Address' => trim((string) ($staffMember['residential_address'] ?? '')) !== '' || trim((string) ($staffMember['address_city'] ?? '')) !== '',
        'Next of kin' => $kinCount > 0,
        'Emergency contact' => $emergencyCount > 0,
    ];
    $done = count(array_filter($sections));
    return ['sections' => $sections, 'done' => $done, 'total' => count($sections)];
}

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

    $isDeleteAction = $action === 'delete';
    if (!$isDeleteAction && !checkPermission('manage_users')) {
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

            $contactError = staffContactRowsValid($_POST, 'kin') ?: staffContactRowsValid($_POST, 'emergency');
            if ($contactError) {
                echo json_encode(['success' => false, 'error' => $contactError]);
                exit;
            }

            $mailingSame = isset($_POST['mailing_same']) ? 1 : 0;
            $residentialAddress = staffOptionalText($_POST['residential_address'] ?? '');
            try {
                $staffData = [
                    'employee_id' => $employeeId !== '' ? $employeeId : null,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'residential_address' => $residentialAddress,
                    'street' => staffOptionalText($_POST['street'] ?? ''),
                    'address_city' => staffOptionalText($_POST['address_city'] ?? ''),
                    'address_state' => staffOptionalText($_POST['address_state'] ?? ''),
                    'postal_code' => staffOptionalText($_POST['postal_code'] ?? ''),
                    'address_country' => staffOptionalText($_POST['address_country'] ?? ''),
                    'mailing_address' => $mailingSame ? $residentialAddress : staffOptionalText($_POST['mailing_address'] ?? ''),
                    'mailing_same' => $mailingSame,
                    'alternative_address' => staffOptionalText($_POST['alternative_address'] ?? ''),
                    'role_id' => $roleId,
                    'department_id' => $departmentId,
                    'position' => $position,
                    'status' => $status,
                    'must_change_password' => $mustChangePassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($id > 0) {
                    $existingStaff = $db->fetch(
                        "SELECT u.*, r.display_name as role_name
                         FROM users u
                         LEFT JOIN roles r ON u.role_id = r.id
                         WHERE u.id = ?",
                        [$id]
                    );
                    if ($password !== '') {
                        $staffData['password'] = password_hash($password, HASH_ALGO);
                    }

                    $db->update('users', $staffData, 'id = :id', ['id' => $id]);
                    saveStaffContactRows($db, $id, 'kin', 'staff_next_of_kin');
                    saveStaffContactRows($db, $id, 'emergency', 'staff_emergency_contacts');
                    logActivity($user['id'], 'update', 'Updated staff account: ' . $name, 'users', $id);

                    $newRole = $db->fetch("SELECT display_name FROM roles WHERE id = ?", [$roleId]);
                    $changedFields = [];
                    if ($existingStaff) {
                        foreach (['email', 'phone', 'position', 'status', 'department_id', 'role_id'] as $field) {
                            if ((string) ($existingStaff[$field] ?? '') !== (string) ($staffData[$field] ?? '')) {
                                $changedFields[] = $field;
                            }
                        }
                    }

                    createOperationNotification(
                        (int) $user['id'],
                        'Staff member updated',
                        'Staff member updated successfully.',
                        'success',
                        'staff.php?action=view&id=' . $id,
                        [
                            'related_type' => 'users',
                            'related_id' => $id
                        ]
                    );

                    if (!empty($changedFields)) {
                        $emailLines = [
                            'Your Corporate Setup CRM account details were updated.',
                            'Name: ' . $name,
                            'Email: ' . $email,
                            'Role: ' . ($newRole['display_name'] ?? 'Unassigned'),
                            'Status: ' . ucfirst($status),
                        ];
                        if ($password !== '') {
                            $emailLines[] = 'Your password was changed. Use the login page and choose “Forgot password” if you did not request this.';
                        }

                        notify(
                            $id,
                            'Your account was updated',
                            'Your staff account details were updated successfully.',
                            'success',
                            'profile.php',
                            [
                                'related_type' => 'users',
                                'related_id' => $id,
                                'email' => [
                                    'to_email' => $email,
                                    'to_name' => $name,
                                    'subject' => 'Corporate Setup CRM - Account Updated',
                                    'html' => buildStaffAccountEmailHtml('Your CRM Account Was Updated', $emailLines, 'profile.php', 'View Profile'),
                                    'text' => implode("\n", $emailLines),
                                ]
                            ]
                        );
                    }
                    echo json_encode(['success' => true, 'message' => 'Staff updated successfully', 'redirect' => 'staff.php?action=view&id=' . $id]);
                } else {
                    $staffData['password'] = password_hash($password, HASH_ALGO);
                    $staffData['created_at'] = date('Y-m-d H:i:s');

                    $newId = $db->insert('users', $staffData);
                    saveStaffContactRows($db, (int) $newId, 'kin', 'staff_next_of_kin');
                    saveStaffContactRows($db, (int) $newId, 'emergency', 'staff_emergency_contacts');
                    logActivity($user['id'], 'create', 'Created staff account: ' . $name, 'users', $newId);

                    $newRole = $db->fetch("SELECT display_name FROM roles WHERE id = ?", [$roleId]);
                    createOperationNotification(
                        (int) $user['id'],
                        'Staff member created',
                        'Staff member created successfully.',
                        'success',
                        'staff.php?action=view&id=' . $newId,
                        [
                            'related_type' => 'users',
                            'related_id' => $newId
                        ]
                    );

                    $reset = $auth->createPasswordReset($email);
                    $resetPath = $reset['success'] ? 'forgot-password.php?token=' . urlencode($reset['token']) : 'login.php';
                    $emailLines = [
                        'Your Corporate Setup CRM account has been created.',
                        'Name: ' . $name,
                        'Email: ' . $email,
                        'Role: ' . ($newRole['display_name'] ?? 'Unassigned'),
                        'Account status: ' . ucfirst($status),
                        'For security, use the button below to set or reset your password before your first sign-in.',
                    ];

                    notify(
                        $newId,
                        'Your CRM account was created',
                        'Your Corporate Setup CRM account has been created.',
                        'success',
                        'profile.php',
                        [
                            'related_type' => 'users',
                            'related_id' => $newId,
                            'email' => [
                                'to_email' => $email,
                                'to_name' => $name,
                                'subject' => 'Corporate Setup CRM - Account Created',
                                'html' => buildStaffAccountEmailHtml('Your CRM Account Has Been Created', $emailLines, $resetPath, 'Set Password'),
                                'text' => implode("\n", $emailLines) . ($reset['success'] ? "\nReset link: " . appUrl($resetPath) : ''),
                            ]
                        ]
                    );
                    echo json_encode(['success' => true, 'message' => 'Staff added successfully', 'redirect' => 'staff.php?action=view&id=' . $newId]);
                }

            } catch (Exception $e) {
                error_log('Staff create error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save staff: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
            if (!$auth->hasRole('Administrator')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'You do not have permission to delete staff members.']);
                exit;
            }

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid staff ID']);
                exit;
            }

            if ($id === intval($user['id'])) {
                echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
                exit;
            }

            try {
                $staffToDelete = $db->fetch('SELECT id, name, email, status FROM users WHERE id = ?', [$id]);
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
                createOperationNotification(
                    (int) $user['id'],
                    'Staff member deleted',
                    'Staff member deleted successfully.',
                    'success',
                    'staff.php?tab=list',
                    [
                        'related_type' => 'users',
                        'related_id' => $id
                    ]
                );

                notify(
                    $id,
                    'Your CRM access was deactivated',
                    'Your Corporate Setup CRM access has been deactivated.',
                    'warning',
                    'login.php',
                    [
                        'related_type' => 'users',
                        'related_id' => $id,
                        'email' => [
                            'to_email' => $staffToDelete['email'] ?? '',
                            'to_name' => $staffToDelete['name'],
                            'subject' => 'Corporate Setup CRM - Account Deactivated',
                            'html' => buildStaffAccountEmailHtml(
                                'Your CRM Access Was Deactivated',
                                [
                                    'Hello ' . $staffToDelete['name'] . ',',
                                    'Your Corporate Setup CRM access has been deactivated.',
                                    'If you believe this was done in error, please contact an administrator.'
                                ],
                                'login.php',
                                'Open Login Page'
                            ),
                            'text' => 'Your Corporate Setup CRM access has been deactivated.'
                        ]
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Staff deactivated successfully', 'reload' => true]);
            } catch (Exception $e) {
                error_log('Staff deactivate error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to deactivate staff: ' . $e->getMessage()]);
            }
            exit;

        case 'save_role_permissions':
            $roleId = intval($_POST['role_id'] ?? 0);
            if ($roleId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid role']);
                exit;
            }

            $role = $db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
            if (!$role) {
                echo json_encode(['success' => false, 'error' => 'Role not found']);
                exit;
            }

            // Administrator always retains full access
            if ($role['name'] === 'Administrator') {
                $db->update('roles', [
                    'permissions' => json_encode(['*']),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $roleId]);
                refreshSessionPermissions();
                createOperationNotification((int) $user['id'], 'Role permissions updated', 'Administrator retains full access.', 'success', 'staff.php?tab=roles', [
                    'related_type' => 'roles',
                    'related_id' => $roleId
                ]);
                echo json_encode(['success' => true, 'message' => 'Administrator retains full access (*)', 'reload' => true]);
                exit;
            }

            $posted = $_POST['permissions'] ?? [];
            if (!is_array($posted)) {
                $posted = [];
            }

            $allowed = [];
            foreach (getAvailablePermissions() as $group => $slugs) {
                foreach ($slugs as $slug) {
                    $allowed[$slug] = true;
                }
            }

            $clean = [];
            foreach ($posted as $slug) {
                $slug = sanitize((string)$slug);
                if (isset($allowed[$slug])) {
                    $clean[] = $slug;
                }
            }
            $clean = array_values(array_unique($clean));

            try {
                $db->update('roles', [
                    'permissions' => json_encode($clean),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $roleId]);

                logActivity($user['id'], 'update', 'Updated permissions for role: ' . $role['display_name'], 'roles', $roleId);
                createOperationNotification((int) $user['id'], 'Role permissions updated', 'Role permissions saved successfully.', 'success', 'staff.php?tab=roles&role_id=' . $roleId, [
                    'related_type' => 'roles',
                    'related_id' => $roleId
                ]);

                $affectedUsers = $db->fetchAll(
                    "SELECT u.id, u.name, u.email FROM users u WHERE u.role_id = ? AND u.status = 'active'",
                    [$roleId]
                );
                $emailMap = [];
                foreach ($affectedUsers as $affectedUser) {
                    $emailMap[(int) $affectedUser['id']] = [
                        'to_email' => $affectedUser['email'],
                        'to_name' => $affectedUser['name'],
                        'subject' => 'Corporate Setup CRM - Permissions Updated',
                        'html' => buildStaffAccountEmailHtml(
                            'Your CRM Permissions Were Updated',
                            [
                                'Your role permissions were updated by an administrator.',
                                'Role: ' . $role['display_name'],
                                'Please sign out and back in if you do not immediately see the expected access.'
                            ],
                            'profile.php',
                            'Open CRM'
                        ),
                        'text' => 'Your role permissions were updated by an administrator.'
                    ];
                }
                notifyMultiple(
                    array_map('intval', array_column($affectedUsers, 'id')),
                    'Your permissions were updated',
                    'Your role permissions were updated by an administrator.',
                    'info',
                    'profile.php',
                    [
                        'related_type' => 'roles',
                        'related_id' => $roleId,
                        'email_map' => $emailMap
                    ]
                );
                refreshSessionPermissions();
                echo json_encode(['success' => true, 'message' => 'Role permissions saved', 'reload' => true]);
            } catch (Exception $e) {
                error_log('Role permission save error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save role permissions']);
            }
            exit;
    }
}

$staffMember = null;
$nextOfKin = [];
$emergencyContacts = [];
$profileCompletion = null;
if ($id > 0) {
    $staffMember = $db->fetch(
        "SELECT u.*, r.display_name as role_name, d.name as department_name
         FROM users u
         LEFT JOIN roles r ON u.role_id = r.id
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE u.id = ?",
        [$id]
    );
    if ($staffMember) {
        $nextOfKin = $db->fetchAll("SELECT * FROM staff_next_of_kin WHERE user_id = ? ORDER BY id", [$staffMember['id']]);
        $emergencyContacts = $db->fetchAll("SELECT * FROM staff_emergency_contacts WHERE user_id = ? ORDER BY sort_order, id", [$staffMember['id']]);
        $profileCompletion = staffProfileSections($staffMember, count($nextOfKin), count($emergencyContacts));
    }
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
$departments = $db->fetchAll(
    "SELECT d.*, u.name as head_name
     FROM departments d
     LEFT JOIN users u ON d.head_id = u.id
     ORDER BY d.name"
);
$availablePermissions = getAvailablePermissions();
$editRoleId = intval($_GET['role_id'] ?? 0);
$editRole = null;
$editRolePerms = [];
if ($tab === 'roles' && $editRoleId > 0) {
    $editRole = $db->fetch("SELECT * FROM roles WHERE id = ?", [$editRoleId]);
    if ($editRole) {
        $decoded = $editRole['permissions'] ? json_decode($editRole['permissions'], true) : [];
        $editRolePerms = is_array($decoded) ? $decoded : [];
    }
}

ob_start();
include 'views/staff/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

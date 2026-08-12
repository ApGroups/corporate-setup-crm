<?php
/**
 * Simulate POST delete for tasks.php or registrations.php in CLI.
 * Usage:
 *   php scripts/delete-endpoint-test.php tasks Administrator 12
 *   php scripts/delete-endpoint-test.php registrations IT 5
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$module = $argv[1] ?? 'tasks';
$role = $argv[2] ?? 'Administrator';
$recordId = intval($argv[3] ?? 0);
$permissionsArg = $argv[4] ?? '';

$defaults = [
    'Administrator' => ['id' => 1, 'name' => 'System Administrator', 'email' => 'admin@corporatesetup.com', 'department' => 'Administration', 'permissions' => ['*']],
    'IT' => ['id' => 6, 'name' => 'Emeka Okafor', 'email' => 'emeka.okafor@corporatesetup.com', 'department' => 'IT', 'permissions' => ['view_users', 'manage_users', 'view_settings']],
    'Management' => ['id' => 8, 'name' => 'David Mohammed', 'email' => 'david.mohammed@corporatesetup.com', 'department' => 'Administration', 'permissions' => ['view_tasks', 'view_registrations', 'view_reports']],
];

$sessionUser = $defaults[$role] ?? $defaults['Administrator'];
$permissions = $permissionsArg !== '' ? (json_decode($permissionsArg, true) ?: []) : $sessionUser['permissions'];

$script = $module === 'registrations' ? 'registrations.php' : 'tasks.php';

session_id('delete-' . $module . '-' . preg_replace('/[^a-z0-9]/i', '-', $role) . '-' . $recordId);
session_start();

$_SESSION['user_id'] = $sessionUser['id'];
$_SESSION['user_name'] = $sessionUser['name'];
$_SESSION['user_email'] = $sessionUser['email'];
$_SESSION['user_role'] = $role;
$_SESSION['user_department'] = $sessionUser['department'];
$_SESSION['permissions'] = $permissions;
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$_SESSION['last_activity'] = time();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUEST_ID'] = 'e2e-delete-' . $module . '-' . strtolower($role);
$_POST = [
    'action' => 'delete',
    'id' => $recordId,
    'csrf_token' => $_SESSION['csrf_token'],
];

include __DIR__ . '/../' . $script;

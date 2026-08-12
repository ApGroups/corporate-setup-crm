<?php
/**
 * Simulate POST /staff.php?action=delete in CLI for authorization testing.
 * Usage: php scripts/staff-delete-endpoint-test.php Administrator
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$role = $argv[1] ?? 'Administrator';
$permissions = $argv[2] ?? ($role === 'Administrator' ? '["*"]' : '["view_users","manage_users"]');
$userMap = [
    'Administrator' => ['id' => 1, 'name' => 'System Administrator', 'email' => 'admin@corporatesetup.com', 'department' => 'Administration'],
    'IT' => ['id' => 6, 'name' => 'Emeka Okafor', 'email' => 'emeka.okafor@corporatesetup.com', 'department' => 'IT'],
];
$sessionUser = $userMap[$role] ?? $userMap['Administrator'];

session_id('staff-delete-' . preg_replace('/[^a-z0-9]/i', '-', $role));
session_start();

$_SESSION['user_id'] = $sessionUser['id'];
$_SESSION['user_name'] = $sessionUser['name'];
$_SESSION['user_email'] = $sessionUser['email'];
$_SESSION['user_role'] = $role;
$_SESSION['user_department'] = $sessionUser['department'];
$_SESSION['permissions'] = json_decode($permissions, true) ?: [];
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$_SESSION['last_activity'] = time();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUEST_ID'] = 'e2e-delete-' . strtolower($role);
$_POST = [
    'action' => 'delete',
    'id' => 4,
    'csrf_token' => $_SESSION['csrf_token'],
];

include __DIR__ . '/../staff.php';

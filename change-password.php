<?php
/**
 * Change Password
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

$pageTitle = 'Change Password';
$activeNav = 'settings';

$auth = new Auth();
$db = Database::getInstance();
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $result = $auth->changePassword($user['id'], $currentPassword, $newPassword);

            if ($result['success']) {
                $db->update('users', ['must_change_password' => 0], 'id = :id', ['id' => $user['id']]);
                logActivity($user['id'], 'password_change', 'Changed password');
                createOperationNotification((int) $user['id'], 'Password changed', 'Password changed successfully.', 'success', 'profile.php', [
                    'related_type' => 'users',
                    'related_id' => (int) $user['id']
                ]);
                notify((int) $user['id'], 'Your password was changed', 'Your Corporate Setup CRM password was changed successfully.', 'info', 'profile.php', [
                    'related_type' => 'users',
                    'related_id' => (int) $user['id'],
                    'email' => [
                        'to_email' => $user['email'],
                        'to_name' => $user['name'],
                        'subject' => 'Corporate Setup CRM - Password Changed',
                        'html' => buildStaffAccountEmailHtml('Your CRM Password Was Changed', [
                            'Your Corporate Setup CRM password was changed successfully.',
                            'If you did not perform this action, use the password reset link immediately and contact an administrator.'
                        ], 'profile.php', 'Open Profile'),
                        'text' => 'Your Corporate Setup CRM password was changed successfully.'
                    ]
                ]);
                $success = 'Password changed successfully.';
            } else {
                $error = $result['error'];
            }
        }
    }
}

ob_start();
include 'views/profile/change-password.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

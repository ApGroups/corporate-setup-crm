<?php
/**
 * Logout Handler
 * Corporate Setup Limited - Internal CRM
 */

require_once 'config/config.php';

$auth = new Auth();
$auth->logout();
flashMessage('success', 'You have been logged out successfully.');

redirect('login.php');

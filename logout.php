<?php
/**
 * Logout Handler
 * Corporate Setup Limited - Internal CRM
 */

require_once 'config/config.php';

$auth = new Auth();
$auth->logout();

redirect('login.php');

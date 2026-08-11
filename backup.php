<?php
/**
 * Database Backup Download
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/helpers/validators.php';
require_once __DIR__ . '/helpers/formatters.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
requirePermission('backup_system');

$action = $_GET['action'] ?? 'download';

if ($action !== 'download') {
    http_response_code(404);
    require 'views/errors/404.php';
    exit;
}

$dbHost = DB_HOST;
$dbPort = DB_PORT;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!is_file($mysqldump)) {
    $mysqldump = 'mysqldump';
}

$filename = 'crm_backup_' . date('Y-m-d_His') . '.sql';
$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

$cmd = sprintf(
    '"%s" --host=%s --port=%s --user=%s %s %s > %s',
    $mysqldump,
    escapeshellarg($dbHost),
    escapeshellarg((string)$dbPort),
    escapeshellarg($dbUser),
    $dbPass !== '' ? ('--password=' . escapeshellarg($dbPass)) : '',
    escapeshellarg($dbName),
    escapeshellarg($tmpFile)
);

// Prefer proc_open without shell redirection on Windows
$descriptors = [
    1 => ['file', $tmpFile, 'w'],
    2 => ['pipe', 'w'],
];
$args = [
    $mysqldump,
    '--host=' . $dbHost,
    '--port=' . $dbPort,
    '--user=' . $dbUser,
];
if ($dbPass !== '') {
    $args[] = '--password=' . $dbPass;
}
$args[] = $dbName;

$process = proc_open($args, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
$stderr = '';
$exitCode = 1;
if (is_resource($process)) {
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
}

$user = getCurrentUser();

if ($exitCode !== 0 || !is_file($tmpFile) || filesize($tmpFile) < 10) {
    error_log('Backup failed: ' . $stderr);
    if (is_file($tmpFile)) {
        @unlink($tmpFile);
    }
    flashMessage('danger', 'Backup failed. Ensure mysqldump is available on the server.');
    redirect('settings.php?tab=backup');
}

try {
    $db = Database::getInstance();
    $db->insert('backup_audit', [
        'backup_type' => 'full',
        'file_name' => $filename,
        'file_size' => filesize($tmpFile),
        'status' => 'completed',
        'started_at' => date('Y-m-d H:i:s'),
        'completed_at' => date('Y-m-d H:i:s'),
        'notes' => 'Manual download by user #' . $user['id'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log('backup_audit insert skipped: ' . $e->getMessage());
}

logActivity($user['id'], 'backup', 'Downloaded database backup: ' . $filename);

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: no-cache');
readfile($tmpFile);
@unlink($tmpFile);
exit;

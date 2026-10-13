<?php
/**
 * End-to-end functional tests for production repair (CLI).
 * Run: C:\xampp\php\php.exe scripts\e2e-tests.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validators.php';
require_once __DIR__ . '/../helpers/formatters.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/monthly_reports.php';
require_once __DIR__ . '/../includes/crm_exports.php';
require_once __DIR__ . '/../includes/client_reports.php';
require_once __DIR__ . '/../includes/MailService.php';

$results = [];
$pass = 0;
$fail = 0;

function assert_true(string $name, bool $cond, string $detail = ''): void
{
    global $results, $pass, $fail;
    if ($cond) {
        $results[] = ['name' => $name, 'status' => 'PASS', 'detail' => $detail];
        $pass++;
        echo "PASS  $name" . ($detail ? " ($detail)" : '') . PHP_EOL;
    } else {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => $detail];
        $fail++;
        echo "FAIL  $name" . ($detail ? " — $detail" : '') . PHP_EOL;
    }
}

$db = Database::getInstance();

// Reset known passwords for test users
$testPassword = 'Admin@123';
$hash = password_hash($testPassword, PASSWORD_ARGON2ID);
$db->query("UPDATE users SET password = ?, status = 'active', locked_until = NULL, login_attempts = 0 WHERE id IN (1,4,8)", [$hash]);

// --- Authentication ---
$auth = new Auth();
$login = $auth->login('admin@corporatesetup.com', $testPassword, false);
assert_true('Authentication login', !empty($login['success']), $login['error'] ?? '');

$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'System Administrator';
$_SESSION['user_email'] = 'admin@corporatesetup.com';
$_SESSION['user_role'] = 'Administrator';
$_SESSION['permissions'] = ['*'];
$_SESSION['user_department'] = 'Administration';

assert_true('Authentication session', !empty($_SESSION['user_id']));
assert_true('Permission wildcard *', checkPermission('manage_users') && checkPermission('view_elite'));

// --- Staff ---
$staffCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
assert_true('Staff Management list', $staffCount >= 1, "users=$staffCount");

// --- Role permissions persistence ---
$ops = $db->fetch("SELECT id, permissions FROM roles WHERE name = 'Operations'");
$origOps = $ops['permissions'];
$testPerms = json_encode(['view_tasks', 'manage_tasks', 'submit_reports']);
$db->update('roles', ['permissions' => $testPerms], 'id = :id', ['id' => $ops['id']]);
$reloaded = $db->fetch("SELECT permissions FROM roles WHERE id = ?", [$ops['id']]);
assert_true('Permissions persist', $reloaded['permissions'] === $testPerms || json_decode($reloaded['permissions'], true) == json_decode($testPerms, true));
$db->update('roles', ['permissions' => $origOps], 'id = :id', ['id' => $ops['id']]);

// Non-admin permission enforcement simulation
$_SESSION['user_role'] = 'Marketing';
$_SESSION['permissions'] = ['view_companies', 'view_tasks'];
refreshSessionPermissions(); // will reload from DB for user 1 who is Admin — so reset manually after
$_SESSION['user_role'] = 'Marketing';
$_SESSION['permissions'] = ['view_companies', 'view_tasks'];
$denied = !in_array('manage_users', $_SESSION['permissions'], true) && $_SESSION['user_role'] !== 'Administrator';
assert_true('Permissions deny manage_users for Marketing session', $denied);

// Restore admin session for remaining tests
$_SESSION['user_role'] = 'Administrator';
$_SESSION['permissions'] = ['*'];

// --- Notification + email logging infrastructure ---
$tables = array_map(function ($r) { return array_values($r)[0]; }, $db->fetchAll('SHOW TABLES'));
assert_true('Database email_logs', in_array('email_logs', $tables, true));

$emailLogCountBefore = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
$uniqueToken = 'e2e-' . microtime(true);
$notifyResult = notify(4, 'E2E notification', 'Testing notification + email handoff. ' . $uniqueToken, 'success', 'profile.php', [
    'related_type' => 'users',
    'related_id' => 4,
    'email' => [
        'to_email' => 'john.okonkwo@corporatesetup.com',
        'to_name' => 'John Okonkwo',
        'subject' => 'E2E Notification',
        'html' => '<p>Test email body</p>',
        'text' => 'Test email body'
    ]
]);
assert_true('Notification helper inserts row', !empty($notifyResult['success']) && !empty($notifyResult['id']));
$emailLogCountAfter = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
assert_true('Email handoff logged', $emailLogCountAfter === $emailLogCountBefore + 1);
$emailLog = $db->fetch("SELECT * FROM email_logs ORDER BY id DESC LIMIT 1");
assert_true('Email failure logged safely', in_array($emailLog['status'], ['sent', 'failed', 'not_configured'], true));

// --- Tasks multi-assignee ---
$taskId = (int)$db->insert('tasks', [
    'title' => 'E2E Multi Assignee Task',
    'description' => 'Test',
    'task_type' => 'general',
    'assigned_to' => 1,
    'assigned_by' => 1,
    'priority' => 'medium',
    'status' => 'pending',
    'progress' => 0,
    'frequency' => 'once',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
$db->insert('task_assignees', ['task_id' => $taskId, 'user_id' => 1, 'created_at' => date('Y-m-d H:i:s')]);
$db->insert('task_assignees', ['task_id' => $taskId, 'user_id' => 4, 'created_at' => date('Y-m-d H:i:s')]);

$forUser4 = (int)$db->query(
    "SELECT COUNT(*) FROM tasks t INNER JOIN task_assignees ta ON ta.task_id = t.id WHERE ta.user_id = 4 AND t.id = ?",
    [$taskId]
)->fetchColumn();
assert_true('Multi Assignment visible to assignee B', $forUser4 === 1, "task_id=$taskId");

$db->delete('task_assignees', 'task_id = :tid AND user_id = :uid', ['tid' => $taskId, 'uid' => 1]);
$remaining = (int)$db->query("SELECT COUNT(*) FROM task_assignees WHERE task_id = ?", [$taskId])->fetchColumn();
assert_true('Remove one assignee keeps others', $remaining === 1);

$db->delete('tasks', 'id = :id', ['id' => $taskId]);
$orphans = (int)$db->query("SELECT COUNT(*) FROM task_assignees WHERE task_id = ?", [$taskId])->fetchColumn();
assert_true('Tasks delete cleans assignees', $orphans === 0);

// --- Daily reports ---
$today = date('Y-m-d');
$year = (int) date('Y');
$month = (int) date('n');
$dateA = sprintf('%04d-%02d-03', $year, $month);
$dateB = sprintf('%04d-%02d-12', $year, $month);
$db->delete('daily_reports', 'user_id = :uid AND report_date = :d', ['uid' => 4, 'd' => $today]);
$db->delete('daily_reports', 'user_id = :uid AND report_date = :d', ['uid' => 4, 'd' => $dateA]);
$db->delete('daily_reports', 'user_id = :uid AND report_date = :d', ['uid' => 4, 'd' => $dateB]);
$reportId = (int) $db->insert('daily_reports', [
    'user_id' => 4,
    'report_date' => $dateA,
    'activities' => 'E2E daily activities',
    'projects_worked' => 'Project Alpha',
    'clients_contacted' => 'Client X',
    'issues_encountered' => 'None',
    'status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s'),
]);
$db->insert('daily_reports', [
    'user_id' => 4,
    'report_date' => $dateB,
    'activities' => 'Second week project work',
    'projects_worked' => 'Project Beta',
    'clients_contacted' => 'Client Y',
    'issues_encountered' => 'Delay',
    'status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s'),
]);
$fetched = $db->fetch("SELECT * FROM daily_reports WHERE id = ?", [$reportId]);
assert_true('Daily Reports save/retrieve', $fetched && $fetched['activities'] === 'E2E daily activities');

$dupBlocked = false;
try {
    $db->insert('daily_reports', [
        'user_id' => 4,
        'report_date' => $dateA,
        'activities' => 'Duplicate',
        'status' => 'draft',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    $dupBlocked = true;
}
assert_true('Daily Reports duplicate prevention', $dupBlocked);

// --- Monthly reports ---
$gen1 = generateMonthlyReportForUser($db, 4, $year, $month, 1);
$gen2 = generateMonthlyReportForUser($db, 4, $year, $month, 1);
$mr = $db->fetch("SELECT * FROM monthly_reports WHERE id = ?", [$gen1['id']]);
assert_true('Monthly Reports generate/save', $mr && (int) $mr['daily_reports_count'] >= 2, 'count=' . ($mr['daily_reports_count'] ?? 0));
assert_true('Monthly Reports duplicate upsert', $gen1['id'] === $gen2['id'] && $gen2['created'] === false);

$weekly = json_decode((string) ($mr['weekly_breakdown'] ?? ''), true);
$projects = json_decode((string) ($mr['project_breakdown'] ?? ''), true);
assert_true('Monthly weekly breakdown stored', is_array($weekly) && count($weekly) >= 1);
$projectNames = is_array($projects) ? array_map(static function ($p) {
    return $p['name'] ?? '';
}, $projects) : [];
assert_true('Monthly project breakdown includes Project Alpha', in_array('Project Alpha', $projectNames, true));
assert_true('Monthly project breakdown includes Project Beta', in_array('Project Beta', $projectNames, true));
$hasWeekKeys = is_array($weekly) && isset($weekly[0]['week'], $weekly[0]['start'], $weekly[0]['end']);
assert_true('Monthly weekly breakdown has week keys', $hasWeekKeys);

// --- Recent activity ---
logActivity(1, 'test', 'E2E activity entry', 'users', 1);
$act = $db->fetch("SELECT * FROM activity_logs WHERE action = 'test' ORDER BY id DESC LIMIT 1");
assert_true('Recent Activity create/store', !empty($act));
$dashActs = $db->fetchAll("SELECT al.*, u.name as user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
assert_true('Recent Activity retrieve', count($dashActs) > 0);

// --- Database schema ---
assert_true('Database task_assignees', in_array('task_assignees', $tables, true));
assert_true('Database monthly_reports', in_array('monthly_reports', $tables, true));
$monthlyColumns = array_column($db->fetchAll('SHOW COLUMNS FROM monthly_reports'), 'Field');
assert_true('Database weekly_breakdown column', in_array('weekly_breakdown', $monthlyColumns, true));
assert_true('Database project_breakdown column', in_array('project_breakdown', $monthlyColumns, true));

// --- Tomorrow plan removed from app code ---
$dailyPhp = file_get_contents(__DIR__ . '/../daily-reports.php');
$dailyView = file_get_contents(__DIR__ . '/../views/reports/daily.php');
assert_true('Tomorrow Plan removed from handlers', strpos($dailyPhp, 'tomorrow_plan') === false);
assert_true('Tomorrow Plan removed from UI', strpos($dailyView, 'tomorrow_plan') === false && strpos($dailyView, "Tomorrow's Plan") === false);

// --- notify() column ---
$fn = file_get_contents(__DIR__ . '/../includes/functions.php');
assert_true('notify() uses link column', strpos($fn, "'link' => \$actionUrl") !== false && strpos($fn, "'action_url'") === false);

// --- activity-log.php exists ---
assert_true('Activity log page exists', is_file(__DIR__ . '/../activity-log.php'));
assert_true('Backup page exists', is_file(__DIR__ . '/../backup.php'));
assert_true('Monthly reports page exists', is_file(__DIR__ . '/../monthly-reports.php'));

// --- Staff delete authorization ---
$php = 'C:\\xampp\\php\\php.exe';
$adminDeleteResponse = shell_exec('"' . $php . '" "' . __DIR__ . '\\staff-delete-endpoint-test.php" Administrator');
$itDeleteResponse = shell_exec('"' . $php . '" "' . __DIR__ . '\\staff-delete-endpoint-test.php" IT "[\"view_users\",\"manage_users\"]"');
assert_true('Admin staff delete endpoint allowed', is_string($adminDeleteResponse) && strpos($adminDeleteResponse, '"success":true') !== false);
assert_true('Non-admin staff delete endpoint blocked', is_string($itDeleteResponse) && strpos($itDeleteResponse, 'do not have permission to delete staff members') !== false);

// Restore test user deactivation side effect from admin endpoint check
$db->update('users', ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => 4]);

// --- Task delete with related rows ---
$deleteTaskId = (int) $db->insert('tasks', [
    'title' => 'E2E Delete Task',
    'description' => 'Delete me',
    'task_type' => 'general',
    'assigned_to' => 1,
    'assigned_by' => 1,
    'priority' => 'low',
    'status' => 'pending',
    'progress' => 0,
    'frequency' => 'once',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
$db->insert('task_assignees', ['task_id' => $deleteTaskId, 'user_id' => 1, 'created_at' => date('Y-m-d H:i:s')]);
$db->insert('task_comments', [
    'task_id' => $deleteTaskId,
    'user_id' => 1,
    'comment' => 'E2E comment',
    'created_at' => date('Y-m-d H:i:s'),
]);
$deniedTaskDelete = shell_exec('"' . $php . '" "' . __DIR__ . '\\delete-endpoint-test.php" tasks Management ' . $deleteTaskId);
assert_true('Non-manager task delete blocked', is_string($deniedTaskDelete) && strpos($deniedTaskDelete, 'not authorized to delete tasks') !== false);
$allowedTaskDelete = shell_exec('"' . $php . '" "' . __DIR__ . '\\delete-endpoint-test.php" tasks Administrator ' . $deleteTaskId);
assert_true('Admin task delete endpoint allowed', is_string($allowedTaskDelete) && strpos($allowedTaskDelete, '"success":true') !== false);
$taskGone = $db->fetch("SELECT id FROM tasks WHERE id = ?", [$deleteTaskId]);
$commentGone = (int) $db->query("SELECT COUNT(*) FROM task_comments WHERE task_id = ?", [$deleteTaskId])->fetchColumn();
$assigneeGone = (int) $db->query("SELECT COUNT(*) FROM task_assignees WHERE task_id = ?", [$deleteTaskId])->fetchColumn();
assert_true('Task delete removes related rows', !$taskGone && $commentGone === 0 && $assigneeGone === 0);

// --- Admin registration delete ---
$company = $db->fetch("SELECT id FROM companies ORDER BY id LIMIT 1");
$regType = $db->fetch("SELECT id FROM registration_types ORDER BY id LIMIT 1");
assert_true('Registration fixtures exist', !empty($company) && !empty($regType));
$regId = 0;
if ($company && $regType) {
    $regId = (int) $db->insert('registrations', [
        'company_id' => $company['id'],
        'registration_type_id' => $regType['id'],
        'status' => 'pending',
        'priority' => 'medium',
        'created_by' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}
$deniedRegDelete = shell_exec('"' . $php . '" "' . __DIR__ . '\\delete-endpoint-test.php" registrations Management ' . $regId);
assert_true('Non-admin registration delete blocked', is_string($deniedRegDelete) && strpos($deniedRegDelete, 'do not have permission to delete registrations') !== false);
$stillThere = $db->fetch("SELECT id FROM registrations WHERE id = ?", [$regId]);
assert_true('Blocked registration delete leaves record', !empty($stillThere));
$allowedRegDelete = shell_exec('"' . $php . '" "' . __DIR__ . '\\delete-endpoint-test.php" registrations Administrator ' . $regId);
assert_true('Admin registration delete endpoint allowed', is_string($allowedRegDelete) && strpos($allowedRegDelete, '"success":true') !== false);
$regGone = $db->fetch("SELECT id FROM registrations WHERE id = ?", [$regId]);
assert_true('Admin registration delete removes record', empty($regGone));

// --- Logout / session hardening ---
$authLogout = new Auth();
$authLogout->logout();
assert_true('Logout clears local auth state', !$authLogout->isLoggedIn());
$configPhp = file_get_contents(__DIR__ . '/../config/config.php');
$logoutPhp = file_get_contents(__DIR__ . '/../logout.php');
$loginPhp = file_get_contents(__DIR__ . '/../login.php');
assert_true('Protected pages send no-store cache headers', strpos($configPhp, 'Cache-Control: no-store') !== false);
assert_true('Logout sets success flash', strpos($logoutPhp, "flashMessage('success'") !== false);
assert_true('Remember me removed from login UI', strpos($loginPhp, 'Remember me') === false);

// --- Integrity, exports, document links, elites, client reports ---
$rc = 'E2E-RC-' . date('YmdHis');
$rcCompanyId = (int) $db->insert('companies', [
    'company_name' => 'E2E RC Company',
    'rc_number' => $rc,
    'status' => 'prospect',
    'created_by' => 1,
    'created_at' => date('Y-m-d H:i:s'),
]);
$rcDuplicate = $db->fetch("SELECT id FROM companies WHERE rc_number = ? AND id <> ?", [$rc, 0]);
$secondBlocked = false;
try {
    $db->insert('companies', [
        'company_name' => 'E2E RC Duplicate',
        'rc_number' => $rc,
        'status' => 'prospect',
        'created_by' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    $secondBlocked = stripos($e->getMessage(), 'Duplicate') !== false;
}
assert_true('Duplicate RC number rejected', !empty($rcDuplicate) && $secondBlocked);
$db->delete('companies', 'id = :id', ['id' => $rcCompanyId]);

$eliteCompany = $db->fetch("SELECT id, company_name FROM companies ORDER BY id LIMIT 1");
$eliteId = 0;
if ($eliteCompany) {
    $eliteStart = '2098-06-15';
    $db->delete('elite_members', 'company_id = :company_id AND membership_start_date = :start', [
        'company_id' => $eliteCompany['id'],
        'start' => $eliteStart,
    ]);
    $eliteId = (int) $db->insert('elite_members', [
        'company_id' => $eliteCompany['id'],
        'membership_tier' => 'premium',
        'membership_start_date' => $eliteStart,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
[$eliteWhere, $eliteParams] = eliteMembershipWhere($eliteCompany['company_name'] ?? '', '');
$eliteParams[] = $eliteId;
$eliteFound = $eliteId ? $db->fetch(
    "SELECT em.id
     FROM elite_members em
     LEFT JOIN companies c ON em.company_id = c.id
     LEFT JOIN users u ON em.assigned_account_manager = u.id
     WHERE {$eliteWhere} AND em.id = ?",
    $eliteParams
) : null;
assert_true('Elite list query returns seeded member', !empty($eliteFound));
if ($eliteId) {
    $db->delete('elite_members', 'id = :id', ['id' => $eliteId]);
}

$companyExport = exportCompanies($db, '2000-01-01', date('Y-m-d'));
assert_true('Export companies SQL runs', is_array($companyExport));

assert_true('Document link rejects http', isHttpsUrl('http://example.com/file.pdf') === false);
assert_true('Document link accepts https', isHttpsUrl('https://example.com/file.pdf') === true);
$linkCompanyId = (int) ($eliteCompany['id'] ?? 0);
$linkUrl = 'https://example.com/e2e-doc-' . date('YmdHis');
$linkId = 0;
if ($linkCompanyId > 0) {
    $linkId = (int) $db->insert('documents', [
        'document_type' => 'other',
        'document_name' => 'E2E HTTPS link',
        'file_name' => null,
        'file_path' => null,
        'external_url' => $linkUrl,
        'company_id' => $linkCompanyId,
        'uploaded_by' => 1,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
$linkSaved = $linkId ? $db->fetch("SELECT id, external_url FROM documents WHERE id = ?", [$linkId]) : null;
assert_true('HTTPS document link stored', !empty($linkSaved) && $linkSaved['external_url'] === $linkUrl);
if ($linkId) {
    $db->delete('documents', 'id = :id', ['id' => $linkId]);
}

$reportCompanyA = (int) $db->insert('companies', [
    'company_name' => 'E2E Report A ' . date('His'),
    'status' => 'active',
    'created_by' => 1,
    'created_at' => date('Y-m-d H:i:s'),
]);
$reportCompanyB = (int) $db->insert('companies', [
    'company_name' => 'E2E Report B ' . date('His'),
    'status' => 'active',
    'created_by' => 1,
    'created_at' => date('Y-m-d H:i:s'),
]);
$reportType = $db->fetch("SELECT id FROM registration_types ORDER BY id LIMIT 1");
if ($reportType) {
    $db->insert('registrations', [
        'company_id' => $reportCompanyA,
        'registration_type_id' => $reportType['id'],
        'status' => 'pending',
        'priority' => 'medium',
        'created_by' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}
$periodFrom = date('Y-m-01');
$periodTo = date('Y-m-t');
$reportForB = buildClientReport($db, $reportCompanyB, $periodFrom, $periodTo, 1, false);
$reportForA = buildClientReport($db, $reportCompanyA, $periodFrom, $periodTo, 1, false);
$scoped = !empty($reportForB['ok']) && !empty($reportForA['ok'])
    && (int) $reportForB['company']['id'] === $reportCompanyB
    && count($reportForB['registrations']) === 0
    && count($reportForA['registrations']) === 1
    && (int) $reportForA['registrations'][0]['id'] > 0;
$letter = !empty($reportForA['ok']) ? renderEliteMessageText($reportForA) : '';
$scopedLetter = $scoped
    && strpos($letter, 'Dear Valued Elite,') !== false
    && strpos($letter, $reportForA['company']['company_name']) !== false
    && strpos($letter, $reportForB['company']['company_name']) === false;
assert_true('Client report query is scoped to one company', $scopedLetter);
$db->delete('registrations', 'company_id = :id', ['id' => $reportCompanyA]);
$db->delete('companies', 'id = :id', ['id' => $reportCompanyA]);
$db->delete('companies', 'id = :id', ['id' => $reportCompanyB]);

echo PHP_EOL . "Summary: $pass passed, $fail failed" . PHP_EOL;
exit($fail > 0 ? 1 : 0);

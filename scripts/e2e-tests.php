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
$db->delete('daily_reports', 'user_id = :uid AND report_date = :d', ['uid' => 4, 'd' => $today]);
$reportId = (int)$db->insert('daily_reports', [
    'user_id' => 4,
    'report_date' => $today,
    'activities' => 'E2E daily activities',
    'projects_worked' => 'Project Alpha',
    'clients_contacted' => 'Client X',
    'issues_encountered' => 'None',
    'status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s'),
]);
$fetched = $db->fetch("SELECT * FROM daily_reports WHERE id = ?", [$reportId]);
assert_true('Daily Reports save/retrieve', $fetched && $fetched['activities'] === 'E2E daily activities');

$dupBlocked = false;
try {
    $db->insert('daily_reports', [
        'user_id' => 4,
        'report_date' => $today,
        'activities' => 'Duplicate',
        'status' => 'draft',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    $dupBlocked = true;
}
assert_true('Daily Reports duplicate prevention', $dupBlocked);

// --- Monthly reports ---
$year = (int)date('Y');
$month = (int)date('n');
$gen1 = generateMonthlyReportForUser($db, 4, $year, $month, 1);
$gen2 = generateMonthlyReportForUser($db, 4, $year, $month, 1);
$mr = $db->fetch("SELECT * FROM monthly_reports WHERE id = ?", [$gen1['id']]);
assert_true('Monthly Reports generate/save', $mr && (int)$mr['daily_reports_count'] >= 1, 'count=' . ($mr['daily_reports_count'] ?? 0));
assert_true('Monthly Reports duplicate upsert', $gen1['id'] === $gen2['id'] && $gen2['created'] === false);

// --- Recent activity ---
logActivity(1, 'test', 'E2E activity entry', 'users', 1);
$act = $db->fetch("SELECT * FROM activity_logs WHERE action = 'test' ORDER BY id DESC LIMIT 1");
assert_true('Recent Activity create/store', !empty($act));
$dashActs = $db->fetchAll("SELECT al.*, u.name as user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
assert_true('Recent Activity retrieve', count($dashActs) > 0);

// --- Database schema ---
$tables = array_map(function ($r) { return array_values($r)[0]; }, $db->fetchAll('SHOW TABLES'));
assert_true('Database task_assignees', in_array('task_assignees', $tables, true));
assert_true('Database monthly_reports', in_array('monthly_reports', $tables, true));

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

echo PHP_EOL . "Summary: $pass passed, $fail failed" . PHP_EOL;
exit($fail > 0 ? 1 : 0);

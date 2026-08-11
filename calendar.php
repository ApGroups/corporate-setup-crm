<?php
/**
 * Calendar
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

$pageTitle = 'Calendar';
$activeNav = 'calendar';

$db = Database::getInstance();
$user = getCurrentUser();

$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));
$view = $_GET['view'] ?? 'month';

// Get events for the month
$startDate = date('Y-m-01', strtotime("$year-$month-01"));
$endDate = date('Y-m-t', strtotime($startDate));

// Get registrations expiring
$expiringRegs = $db->fetchAll(
    "SELECT r.id, r.expiry_date, c.company_name, rt.name as reg_type, 'registration' as event_type
     FROM registrations r
     JOIN companies c ON r.company_id = c.id
     JOIN registration_types rt ON r.registration_type_id = rt.id
     WHERE r.expiry_date BETWEEN ? AND ? AND r.status = 'approved'
     ORDER BY r.expiry_date",
    [$startDate, $endDate]
);

// Get meetings
$meetings = $db->fetchAll(
    "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time, m.status, 'meeting' as event_type
     FROM meetings m
     WHERE m.meeting_date BETWEEN ? AND ?
     ORDER BY m.meeting_date, m.start_time",
    [$startDate, $endDate]
);

// Get tasks due
$tasksDue = $db->fetchAll(
    "SELECT t.id, t.title, t.due_date, 'task' as event_type
     FROM tasks t
     WHERE t.due_date BETWEEN ? AND ? AND t.status IN ('pending', 'in_progress')
     ORDER BY t.due_date",
    [$startDate, $endDate]
);

// Merge all events
$events = [];
foreach ($expiringRegs as $r) {
    $events[$r['expiry_date']][] = [
        'type' => 'registration',
        'title' => $r['company_name'] . ' - ' . $r['reg_type'],
        'time' => '',
        'color' => 'warning',
        'link' => 'registrations.php?action=view&id=' . $r['id']
    ];
}
foreach ($meetings as $m) {
    $events[$m['meeting_date']][] = [
        'type' => 'meeting',
        'title' => $m['title'],
        'time' => substr($m['start_time'], 0, 5) . ' - ' . substr($m['end_time'], 0, 5),
        'color' => $m['status'] === 'completed' ? 'secondary' : 'primary',
        'link' => 'meetings.php?action=view&id=' . $m['id']
    ];
}
foreach ($tasksDue as $t) {
    $events[$t['due_date']][] = [
        'type' => 'task',
        'title' => $t['title'],
        'time' => 'Due',
        'color' => 'info',
        'link' => 'tasks.php?action=view&id=' . $t['id']
    ];
}

// Generate calendar
$firstDay = date('w', strtotime($startDate));
$daysInMonth = date('t', strtotime($startDate));

$monthNames = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

$prevMonth = $month - 1 < 1 ? 12 : $month - 1;
$prevYear = $month - 1 < 1 ? $year - 1 : $year;
$nextMonth = $month + 1 > 12 ? 1 : $month + 1;
$nextYear = $month + 1 > 12 ? $year + 1 : $year;

ob_start();
include 'views/calendar/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

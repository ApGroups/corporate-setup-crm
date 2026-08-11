<?php
/**
 * CLI: Generate monthly reports from daily_reports
 *
 * Usage:
 *   php scripts/generate-monthly-reports.php
 *   php scripts/generate-monthly-reports.php --year=2026 --month=7
 *   php scripts/generate-monthly-reports.php --year=2026 --month=7 --user=4
 *
 * Cron example (last days of month, 23:00 Africa/Lagos):
 *   0 23 28-31 * * C:\xampp\php\php.exe C:\xampp\htdocs\corporate-setup-crm\scripts\generate-monthly-reports.php
 *
 * Safe to re-run: upserts by (user_id, year, month).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/monthly_reports.php';

$opts = getopt('', ['year::', 'month::', 'user::']);

$default = strtotime('first day of last month');
$year = isset($opts['year']) ? (int)$opts['year'] : (int)date('Y', $default);
$month = isset($opts['month']) ? (int)$opts['month'] : (int)date('n', $default);
$userId = isset($opts['user']) ? (int)$opts['user'] : null;

if ($month < 1 || $month > 12) {
    fwrite(STDERR, "Invalid --month\n");
    exit(1);
}

try {
    $db = Database::getInstance();
    $stats = generateMonthlyReportsForPeriod($db, $year, $month, null, $userId);
    echo sprintf(
        "Monthly reports for %04d-%02d: created=%d updated=%d\n",
        $year,
        $month,
        $stats['generated'],
        $stats['updated']
    );
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

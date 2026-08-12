<?php
/**
 * Monthly report aggregation helpers
 * Corporate Setup Limited - Internal CRM
 */

/**
 * Split a free-text projects_worked field into project names.
 *
 * @return string[]
 */
function parseProjectNames(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $names = [];
    foreach ($parts as $part) {
        $name = trim($part);
        $name = trim($name, " \t.-•*");
        if ($name === '') {
            continue;
        }
        $key = mb_strtolower($name);
        if (!isset($names[$key])) {
            $names[$key] = $name;
        }
    }

    return array_values($names);
}

/**
 * Join non-empty text values with a separator.
 */
function joinNonEmptyTexts(array $values, string $separator = "\n\n"): string
{
    $parts = [];
    foreach ($values as $val) {
        $val = trim((string) $val);
        if ($val !== '') {
            $parts[] = $val;
        }
    }
    return implode($separator, $parts);
}

/**
 * Format a clipped Monday–Sunday week label, e.g. "3–9 Aug".
 */
function formatWeekRangeLabel(string $start, string $end): string
{
    $startTs = strtotime($start);
    $endTs = strtotime($end);
    $startDay = date('j', $startTs);
    $endDay = date('j', $endTs);
    $startMonth = date('M', $startTs);
    $endMonth = date('M', $endTs);

    if ($startMonth === $endMonth) {
        return $startDay . '–' . $endDay . ' ' . $startMonth;
    }

    return $startDay . ' ' . $startMonth . ' – ' . $endDay . ' ' . $endMonth;
}

/**
 * Build Monday–Sunday week buckets clipped to the selected month.
 *
 * @return array<int, array<string, mixed>>
 */
function buildWeeklyBreakdown(array $dailyRows, string $monthStart, string $monthEnd): array
{
    $weeks = [];

    foreach ($dailyRows as $row) {
        $date = (string) ($row['report_date'] ?? '');
        if ($date === '') {
            continue;
        }

        $ts = strtotime($date);
        $dow = (int) date('N', $ts); // 1 = Monday
        $weekStartTs = strtotime('-' . ($dow - 1) . ' days', $ts);
        $weekEndTs = strtotime('+' . (7 - $dow) . ' days', $ts);
        $weekStart = date('Y-m-d', $weekStartTs);
        $weekEnd = date('Y-m-d', $weekEndTs);

        $clippedStart = max($weekStart, $monthStart);
        $clippedEnd = min($weekEnd, $monthEnd);
        $key = $clippedStart . '_' . $clippedEnd;

        if (!isset($weeks[$key])) {
            $weeks[$key] = [
                'start' => $clippedStart,
                'end' => $clippedEnd,
                'days' => 0,
                'activity_parts' => [],
                'project_names' => [],
                'client_parts' => [],
                'issue_parts' => [],
            ];
        }

        $weeks[$key]['days']++;
        $weeks[$key]['activity_parts'][] = $row['activities'] ?? '';
        $weeks[$key]['client_parts'][] = $row['clients_contacted'] ?? '';
        $weeks[$key]['issue_parts'][] = $row['issues_encountered'] ?? '';
        foreach (parseProjectNames((string) ($row['projects_worked'] ?? '')) as $name) {
            $weeks[$key]['project_names'][mb_strtolower($name)] = $name;
        }
    }

    ksort($weeks);

    $result = [];
    $weekNumber = 1;
    foreach ($weeks as $week) {
        $result[] = [
            'week' => $weekNumber,
            'start' => $week['start'],
            'end' => $week['end'],
            'label' => formatWeekRangeLabel($week['start'], $week['end']),
            'days' => $week['days'],
            'activities' => joinNonEmptyTexts($week['activity_parts']),
            'projects' => array_values($week['project_names']),
            'clients' => joinNonEmptyTexts($week['client_parts']),
            'issues' => joinNonEmptyTexts($week['issue_parts']),
        ];
        $weekNumber++;
    }

    return $result;
}

/**
 * Build project-by-project buckets from daily report project names.
 *
 * @return array<int, array<string, mixed>>
 */
function buildProjectBreakdown(array $dailyRows, string $monthStart, string $monthEnd): array
{
    $projects = [];

    foreach ($dailyRows as $row) {
        $date = (string) ($row['report_date'] ?? '');
        $names = parseProjectNames((string) ($row['projects_worked'] ?? ''));
        if (empty($names)) {
            $names = ['Unspecified'];
        }

        $ts = strtotime($date);
        $dow = $date !== '' ? (int) date('N', $ts) : 1;
        $weekStart = $date !== '' ? date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts)) : $monthStart;
        $weekEnd = $date !== '' ? date('Y-m-d', strtotime('+' . (7 - $dow) . ' days', $ts)) : $monthEnd;
        $clippedStart = max($weekStart, $monthStart);
        $clippedEnd = min($weekEnd, $monthEnd);
        $weekLabel = formatWeekRangeLabel($clippedStart, $clippedEnd);

        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if (!isset($projects[$key])) {
                $projects[$key] = [
                    'name' => $name,
                    'days' => 0,
                    'week_labels' => [],
                    'activity_parts' => [],
                    'client_parts' => [],
                    'issue_parts' => [],
                ];
            }
            $projects[$key]['days']++;
            $projects[$key]['week_labels'][$weekLabel] = $weekLabel;
            $projects[$key]['activity_parts'][] = $row['activities'] ?? '';
            $projects[$key]['client_parts'][] = $row['clients_contacted'] ?? '';
            $projects[$key]['issue_parts'][] = $row['issues_encountered'] ?? '';
        }
    }

    $result = [];
    foreach ($projects as $project) {
        $result[] = [
            'name' => $project['name'],
            'days' => $project['days'],
            'weeks' => array_values($project['week_labels']),
            'activities' => joinNonEmptyTexts($project['activity_parts']),
            'clients' => joinNonEmptyTexts($project['client_parts']),
            'issues' => joinNonEmptyTexts($project['issue_parts']),
        ];
    }

    usort($result, static function ($a, $b) {
        if ($a['name'] === 'Unspecified') {
            return 1;
        }
        if ($b['name'] === 'Unspecified') {
            return -1;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $result;
}

/**
 * Aggregate one staff member's daily reports (+ task counts) into a monthly_reports row.
 * Idempotent upsert on (user_id, report_year, report_month).
 *
 * @return array{id:int, created:bool}
 */
function generateMonthlyReportForUser(Database $db, int $userId, int $year, int $month, ?int $generatedBy = null): array
{
    if ($month < 1 || $month > 12 || $year < 2000) {
        throw new InvalidArgumentException('Invalid year/month');
    }

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    $userRow = $db->fetch(
        "SELECT id, department_id FROM users WHERE id = ?",
        [$userId]
    );
    if (!$userRow) {
        throw new RuntimeException('User not found: ' . $userId);
    }

    $dailyRows = $db->fetchAll(
        "SELECT report_date, activities, projects_worked, clients_contacted, issues_encountered, status
         FROM daily_reports
         WHERE user_id = ? AND report_date BETWEEN ? AND ?
           AND status IN ('submitted', 'reviewed', 'approved')
         ORDER BY report_date ASC",
        [$userId, $startDate, $endDate]
    );

    $joinTexts = function (array $rows, string $field): string {
        $parts = [];
        foreach ($rows as $row) {
            $val = trim((string) ($row[$field] ?? ''));
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        return implode("\n\n---\n\n", $parts);
    };

    $weeklyBreakdown = buildWeeklyBreakdown($dailyRows, $startDate, $endDate);
    $projectBreakdown = buildProjectBreakdown($dailyRows, $startDate, $endDate);

    // Prefer junction table when present; fall back to assigned_to
    $hasAssignees = false;
    try {
        $db->query("SELECT 1 FROM task_assignees LIMIT 1");
        $hasAssignees = true;
    } catch (Exception $e) {
        $hasAssignees = false;
    }

    if ($hasAssignees) {
        $tasksCompleted = (int) $db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t
             INNER JOIN task_assignees ta ON ta.task_id = t.id
             WHERE ta.user_id = ?
               AND t.status = 'completed'
               AND (
                    (t.completed_at IS NOT NULL AND t.completed_at BETWEEN ? AND ?)
                    OR (t.completed_at IS NULL AND t.updated_at BETWEEN ? AND ?)
               )",
            [$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59', $startDate . ' 00:00:00', $endDate . ' 23:59:59']
        )->fetchColumn();

        $tasksOutstanding = (int) $db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t
             INNER JOIN task_assignees ta ON ta.task_id = t.id
             WHERE ta.user_id = ?
               AND t.status IN ('pending', 'in_progress', 'on_hold')",
            [$userId]
        )->fetchColumn();
    } else {
        $tasksCompleted = (int) $db->query(
            "SELECT COUNT(*) FROM tasks
             WHERE assigned_to = ?
               AND status = 'completed'
               AND (
                    (completed_at IS NOT NULL AND completed_at BETWEEN ? AND ?)
                    OR (completed_at IS NULL AND updated_at BETWEEN ? AND ?)
               )",
            [$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59', $startDate . ' 00:00:00', $endDate . ' 23:59:59']
        )->fetchColumn();

        $tasksOutstanding = (int) $db->query(
            "SELECT COUNT(*) FROM tasks
             WHERE assigned_to = ?
               AND status IN ('pending', 'in_progress', 'on_hold')",
            [$userId]
        )->fetchColumn();
    }

    $payload = [
        'user_id' => $userId,
        'department_id' => $userRow['department_id'] ?: null,
        'report_year' => $year,
        'report_month' => $month,
        'daily_reports_count' => count($dailyRows),
        'activities_summary' => $joinTexts($dailyRows, 'activities'),
        'projects_summary' => $joinTexts($dailyRows, 'projects_worked'),
        'clients_summary' => $joinTexts($dailyRows, 'clients_contacted'),
        'issues_summary' => $joinTexts($dailyRows, 'issues_encountered'),
        'tasks_completed_count' => $tasksCompleted,
        'tasks_outstanding_count' => $tasksOutstanding,
        'generated_at' => date('Y-m-d H:i:s'),
        'generated_by' => $generatedBy,
    ];

    $columnNames = array_map(static function ($col) {
        return $col['Field'];
    }, $db->fetchAll('SHOW COLUMNS FROM monthly_reports'));
    if (in_array('weekly_breakdown', $columnNames, true)) {
        $payload['weekly_breakdown'] = json_encode($weeklyBreakdown, JSON_UNESCAPED_UNICODE);
    }
    if (in_array('project_breakdown', $columnNames, true)) {
        $payload['project_breakdown'] = json_encode($projectBreakdown, JSON_UNESCAPED_UNICODE);
    }

    $existing = $db->fetch(
        "SELECT id FROM monthly_reports WHERE user_id = ? AND report_year = ? AND report_month = ?",
        [$userId, $year, $month]
    );

    if ($existing) {
        $db->update('monthly_reports', $payload, 'id = :id', ['id' => $existing['id']]);
        return ['id' => (int) $existing['id'], 'created' => false];
    }

    $payload['created_at'] = date('Y-m-d H:i:s');
    $newId = (int) $db->insert('monthly_reports', $payload);
    return ['id' => $newId, 'created' => true];
}

/**
 * Generate monthly reports for all active users (or one user).
 *
 * @return array{generated:int, updated:int, skipped:int}
 */
function generateMonthlyReportsForPeriod(Database $db, int $year, int $month, ?int $generatedBy = null, ?int $onlyUserId = null): array
{
    if ($onlyUserId) {
        $users = [['id' => $onlyUserId]];
    } else {
        $users = $db->fetchAll("SELECT id FROM users WHERE status = 'active' ORDER BY id");
    }

    $generated = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($users as $u) {
        $result = generateMonthlyReportForUser($db, (int) $u['id'], $year, $month, $generatedBy);
        if ($result['created']) {
            $generated++;
        } else {
            $updated++;
        }
    }

    return compact('generated', 'updated', 'skipped');
}

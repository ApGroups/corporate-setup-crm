<?php
/**
 * Monthly report aggregation helpers
 * Corporate Setup Limited - Internal CRM
 */

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
        "SELECT activities, projects_worked, clients_contacted, issues_encountered, status
         FROM daily_reports
         WHERE user_id = ? AND report_date BETWEEN ? AND ?
           AND status IN ('submitted', 'reviewed', 'approved')
         ORDER BY report_date ASC",
        [$userId, $startDate, $endDate]
    );

    $joinTexts = function (array $rows, string $field): string {
        $parts = [];
        foreach ($rows as $row) {
            $val = trim((string)($row[$field] ?? ''));
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        return implode("\n\n---\n\n", $parts);
    };

    // Prefer junction table when present; fall back to assigned_to
    $hasAssignees = false;
    try {
        $db->query("SELECT 1 FROM task_assignees LIMIT 1");
        $hasAssignees = true;
    } catch (Exception $e) {
        $hasAssignees = false;
    }

    if ($hasAssignees) {
        $tasksCompleted = (int)$db->query(
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

        $tasksOutstanding = (int)$db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t
             INNER JOIN task_assignees ta ON ta.task_id = t.id
             WHERE ta.user_id = ?
               AND t.status IN ('pending', 'in_progress', 'on_hold')",
            [$userId]
        )->fetchColumn();
    } else {
        $tasksCompleted = (int)$db->query(
            "SELECT COUNT(*) FROM tasks
             WHERE assigned_to = ?
               AND status = 'completed'
               AND (
                    (completed_at IS NOT NULL AND completed_at BETWEEN ? AND ?)
                    OR (completed_at IS NULL AND updated_at BETWEEN ? AND ?)
               )",
            [$userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59', $startDate . ' 00:00:00', $endDate . ' 23:59:59']
        )->fetchColumn();

        $tasksOutstanding = (int)$db->query(
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

    $existing = $db->fetch(
        "SELECT id FROM monthly_reports WHERE user_id = ? AND report_year = ? AND report_month = ?",
        [$userId, $year, $month]
    );

    if ($existing) {
        $db->update('monthly_reports', $payload, 'id = :id', ['id' => $existing['id']]);
        return ['id' => (int)$existing['id'], 'created' => false];
    }

    $payload['created_at'] = date('Y-m-d H:i:s');
    $newId = (int)$db->insert('monthly_reports', $payload);
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
        $result = generateMonthlyReportForUser($db, (int)$u['id'], $year, $month, $generatedBy);
        if ($result['created']) {
            $generated++;
        } else {
            $updated++;
        }
    }

    return compact('generated', 'updated', 'skipped');
}

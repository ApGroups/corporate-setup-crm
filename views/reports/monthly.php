<?php defined('APP_NAME') or die('Direct access not allowed');

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<?php if ($action === 'view' && $report): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Monthly Report — <?= sanitize($monthNames[(int)$report['report_month']] ?? '') ?> <?= (int)$report['report_year'] ?></h1>
            <p class="page-subtitle"><a href="monthly-reports.php">Monthly Reports</a> &rsaquo; <?= sanitize($report['user_name']) ?></p>
        </div>
        <div class="btn-group">
            <a href="monthly-reports.php" class="btn btn-outline">Back</a>
        </div>
    </div>

    <div class="grid grid-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-user"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="font-size: 1rem;"><?= sanitize($report['user_name']) ?></div>
                <div class="stat-label"><?= sanitize($report['department_name'] ?? 'No department') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$report['daily_reports_count'] ?></div>
                <div class="stat-label">Daily Reports Submitted</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$report['tasks_completed_count'] ?></div>
                <div class="stat-label">Tasks Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$report['tasks_outstanding_count'] ?></div>
                <div class="stat-label">Outstanding Tasks</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Activity Summary</h3></div>
        <div class="card-body">
            <p style="white-space: pre-wrap;"><?= sanitize($report['activities_summary']) ?: 'No submitted daily activities for this month.' ?></p>
        </div>
    </div>

    <div class="grid grid-2 gap-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Projects</h3></div>
            <div class="card-body">
                <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['projects_summary']) ?: 'None' ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Clients Contacted</h3></div>
            <div class="card-body">
                <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['clients_summary']) ?: 'None' ?></p>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Issues Encountered</h3></div>
        <div class="card-body">
            <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['issues_summary']) ?: 'None reported' ?></p>
        </div>
    </div>

    <p class="text-muted mt-4" style="font-size: 0.875rem;">
        Generated <?= formatDateTime($report['generated_at']) ?>
        <?= $report['generated_by_name'] ? ' by ' . sanitize($report['generated_by_name']) : '' ?>
    </p>

<?php else: ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Monthly Reports</h1>
            <p class="page-subtitle">Aggregated staff reports from daily submissions</p>
        </div>
    </div>

    <?php if (!empty($canGenerate)): ?>
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Generate Monthly Reports</h3></div>
        <div class="card-body">
            <form id="generateMonthlyForm" method="POST" action="monthly-reports.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="generate">
                <div class="flex gap-4 items-center flex-wrap">
                    <select name="year" class="form-control" style="max-width: 120px;">
                        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="month" class="form-control" style="max-width: 160px;">
                        <?php
                        $defaultMonth = $filterMonth > 0 ? $filterMonth : (int)date('n', strtotime('first day of last month'));
                        foreach ($monthNames as $num => $label):
                        ?>
                            <option value="<?= $num ?>" <?= $num == $defaultMonth ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($canViewAll)): ?>
                    <select name="user_id" class="form-control" style="max-width: 220px;">
                        <option value="">All Active Staff</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sync"></i> Generate</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <?php if (!empty($canViewAll)): ?>
                <select name="user" class="form-control">
                    <option value="">All Staff</option>
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filterUser == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <select name="year" class="form-control">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="month" class="form-control">
                    <option value="0">All Months</option>
                    <?php foreach ($monthNames as $num => $label): ?>
                        <option value="<?= $num ?>" <?= $filterMonth == $num ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Daily Reports</th>
                            <th>Tasks Done</th>
                            <th>Outstanding</th>
                            <th>Generated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h3>No monthly reports found</h3><p>Generate reports for a month to populate this list.</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><?= sanitize($monthNames[(int)$r['report_month']] ?? '') ?> <?= (int)$r['report_year'] ?></td>
                                    <td><?= sanitize($r['user_name']) ?></td>
                                    <td><?= sanitize($r['department_name'] ?? '-') ?></td>
                                    <td><?= (int)$r['daily_reports_count'] ?></td>
                                    <td><?= (int)$r['tasks_completed_count'] ?></td>
                                    <td><?= (int)$r['tasks_outstanding_count'] ?></td>
                                    <td><?= formatDateTime($r['generated_at']) ?></td>
                                    <td><a href="monthly-reports.php?action=view&id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer">
                <div class="pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="pagination-item"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="pagination-item"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

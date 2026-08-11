<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Generate business reports and analytics</p>
    </div>
</div>

<div class="tabs mb-4">
    <a href="?type=summary&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="tab <?= $type === 'summary' ? 'active' : '' ?>">Summary</a>
    <a href="?type=registrations&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="tab <?= $type === 'registrations' ? 'active' : '' ?>">Registrations</a>
    <a href="?type=tasks&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="tab <?= $type === 'tasks' ? 'active' : '' ?>">Tasks</a>
    <a href="?type=activity&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="tab <?= $type === 'activity' ? 'active' : '' ?>">Activity</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center flex-wrap">
            <input type="hidden" name="type" value="<?= sanitize($type) ?>">
            <label class="form-label" style="margin: 0;">Period:</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
            <button type="submit" class="btn btn-primary">Generate</button>
            <?php if (checkPermission('export_data')): ?>
            <button type="button" onclick="exportReport()" class="btn btn-outline"><i class="fas fa-download"></i> Export</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($type === 'summary'): ?>
    <div class="grid grid-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-building"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $stats['companies']['total'] ?></div><div class="stat-label">Total Companies</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $stats['companies']['active'] ?></div><div class="stat-label">Active Clients</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $stats['registrations']['pending'] ?></div><div class="stat-label">Pending Registrations</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $stats['registrations']['approved'] ?></div><div class="stat-label">Approved</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Period Summary (<?= formatDate($from) ?> - <?= formatDate($to) ?>)</h3></div>
        <div class="card-body">
            <div class="grid grid-3 gap-4">
                <div class="text-center p-4" style="background: var(--light-grey); border-radius: var(--border-radius);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary);"><?= $stats['companies']['new'] ?></div>
                    <div class="text-muted">New Companies</div>
                </div>
                <div class="text-center p-4" style="background: var(--light-grey); border-radius: var(--border-radius);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary);"><?= $stats['registrations']['this_period'] ?></div>
                    <div class="text-muted">New Registrations</div>
                </div>
                <div class="text-center p-4" style="background: var(--light-grey); border-radius: var(--border-radius);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary);"><?= $stats['tasks']['this_period'] ?></div>
                    <div class="text-muted">Tasks Completed</div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($type === 'registrations'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Registrations (<?= formatDate($from) ?> - <?= formatDate($to) ?>)</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Company</th><th>Type</th><th>Status</th><th>Created</th><th>Expiry</th></tr></thead>
                    <tbody>
                        <?php if (empty($registrationRows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No registrations in this period</td></tr>
                        <?php else: ?>
                            <?php foreach ($registrationRows as $row): ?>
                                <tr>
                                    <td><?= sanitize($row['company_name']) ?></td>
                                    <td><?= sanitize($row['registration_type']) ?></td>
                                    <td><?= getStatusBadge($row['status']) ?></td>
                                    <td><?= formatDate($row['created_at']) ?></td>
                                    <td><?= $row['expiry_date'] ? formatDate($row['expiry_date']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($type === 'tasks'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tasks (<?= formatDate($from) ?> - <?= formatDate($to) ?>)</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Title</th><th>Assignees</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead>
                    <tbody>
                        <?php if (empty($taskRows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No tasks in this period</td></tr>
                        <?php else: ?>
                            <?php foreach ($taskRows as $row): ?>
                                <tr>
                                    <td><a href="tasks.php?action=view&id=<?= (int)$row['id'] ?>"><?= sanitize($row['title']) ?></a></td>
                                    <td><?= sanitize($row['assigned_to_names'] ?? '-') ?></td>
                                    <td><?= getPriorityBadge($row['priority']) ?></td>
                                    <td><?= getStatusBadge($row['status']) ?></td>
                                    <td><?= $row['due_date'] ? formatDate($row['due_date']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($type === 'activity'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Activity (<?= formatDate($from) ?> - <?= formatDate($to) ?>)</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php if (empty($activityRows)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No activity in this period</td></tr>
                        <?php else: ?>
                            <?php foreach ($activityRows as $row): ?>
                                <tr>
                                    <td><?= formatDateTime($row['created_at']) ?></td>
                                    <td><?= sanitize($row['user_name'] ?? 'System') ?></td>
                                    <td><?= sanitize($row['action']) ?></td>
                                    <td><?= sanitize($row['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function exportReport() {
    window.location.href = 'export.php?type=<?= sanitize($type) ?>&from=<?= $from ?>&to=<?= $to ?>';
}
</script>

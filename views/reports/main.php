<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Generate business reports and analytics</p>
    </div>
</div>

<div class="tabs mb-4">
    <a href="?type=summary" class="tab <?= $type === 'summary' ? 'active' : '' ?>">Summary</a>
    <a href="?type=registrations" class="tab <?= $type === 'registrations' ? 'active' : '' ?>">Registrations</a>
    <a href="?type=tasks" class="tab <?= $type === 'tasks' ? 'active' : '' ?>">Tasks</a>
    <a href="?type=activity" class="tab <?= $type === 'activity' ? 'active' : '' ?>">Activity</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center flex-wrap">
            <input type="hidden" name="type" value="<?= sanitize($type) ?>">
            <label class="form-label" style="margin: 0;">Period:</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
            <button type="submit" class="btn btn-primary">Generate</button>
            <button type="button" onclick="exportReport()" class="btn btn-outline"><i class="fas fa-download"></i> Export</button>
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

<?php else: ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted">Select a report type from the tabs above to view detailed analytics.</p>
        </div>
    </div>
<?php endif; ?>

<script>
function exportReport() {
    window.location.href = 'export.php?type=<?= $type ?>&from=<?= $from ?>&to=<?= $to ?>';
}
</script>

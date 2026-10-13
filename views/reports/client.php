<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Client Reports</h1>
        <p class="page-subtitle">Elite compliance update for a company</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-end flex-wrap">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="q">Find company</label>
                <input type="text" id="q" name="q" class="form-control" placeholder="Name, RC number, or email" value="<?= sanitize($search) ?>" style="width: 260px;">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="company_id">Company</label>
                <select id="company_id" name="company_id" class="form-control" style="min-width: 240px;" required>
                    <option value="">Select a company</option>
                    <?php foreach ($matches as $match): ?>
                        <option value="<?= (int) $match['id'] ?>" <?= $companyId === (int) $match['id'] ? 'selected' : '' ?>>
                            <?= sanitize($match['company_name']) ?><?= !empty($match['rc_number']) ? ' (' . sanitize($match['rc_number']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="from">From</label>
                <input type="date" id="from" name="from" class="form-control" value="<?= sanitize($from) ?>" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="to">To</label>
                <input type="date" id="to" name="to" class="form-control" value="<?= sanitize($to) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Preview</button>
            <a class="btn btn-outline" href="client-reports.php?action=extract&amp;format=txt&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>">Extract elite messages</a>
        </form>
        <?php if ($search !== '' && empty($matches)): ?>
            <p class="text-muted mt-2">No companies matched that search.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($reportError !== ''): ?>
    <div class="alert alert-warning"><?= sanitize($reportError) ?></div>
<?php endif; ?>

<?php if ($report): ?>
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title"><?= sanitize($report['company']['company_name']) ?></h3>
            <div class="btn-group">
                <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a class="btn btn-outline" href="client-reports.php?action=download&amp;format=txt&amp;company_id=<?= (int) $report['company']['id'] ?>&amp;from=<?= urlencode($report['from']) ?>&amp;to=<?= urlencode($report['to']) ?>">Extract text</a>
                <a class="btn btn-outline" href="client-reports.php?action=download&amp;format=html&amp;company_id=<?= (int) $report['company']['id'] ?>&amp;from=<?= urlencode($report['from']) ?>&amp;to=<?= urlencode($report['to']) ?>">Download HTML</a>
                <a class="btn btn-outline" href="client-reports.php?action=download&amp;format=csv&amp;company_id=<?= (int) $report['company']['id'] ?>&amp;from=<?= urlencode($report['from']) ?>&amp;to=<?= urlencode($report['to']) ?>">Download CSV</a>
            </div>
        </div>
        <div class="card-body elite-message">
            <p class="text-muted">Reference <?= sanitize($report['reference']) ?>.</p>
            <h2><?= sanitize($report['company']['company_name']) ?></h2>
            <p>Dear Valued Elite,</p>
            <p>This is an official compliance update from Corporate-setup.app, your digital compliance management platform powered by Corporate Setup.</p>
            <p>Kindly review your company status below:</p>
            <p><strong>Company:</strong> <?= sanitize($report['company']['company_name']) ?></p>
            <?php if (empty($report['registrations'])): ?>
                <p>No registrations to report.</p>
            <?php else: ?>
                <?php foreach ($report['registrations'] as $row): ?>
                    <p><strong><?= sanitize($row['registration_type']) ?>:</strong> <?= sanitize(clientReportStatusPhrase($row)) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <p><strong>Login Details:</strong><br><strong>Link:</strong> <a href="https://www.corporate-setup.app/login">https://www.corporate-setup.app/login</a></p>
            <p>For more information about our services, please visit: corporate-setup.app</p>
            <p>Contact us at: <a href="mailto:compliance@corporate-setup.com">compliance@corporate-setup.com</a> or +234 702 560 0065</p>
            <p>Kind regards,<br><strong>Corporate Setup Team</strong></p>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title">History</h3></div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <p class="p-4 text-muted">No client reports have been generated yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Reference</th><th>Company</th><th>Period</th><th>Generated</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?= sanitize($row['report_reference']) ?></td>
                        <td><?= sanitize($row['company_name']) ?></td>
                        <td><?= formatDate($row['period_start']) ?> – <?= formatDate($row['period_end']) ?></td>
                        <td><?= sanitize($row['generated_by_name'] ?: '-') ?><br><small class="text-muted"><?= formatDateTime($row['generated_at']) ?></small></td>
                        <td><?= sanitize($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

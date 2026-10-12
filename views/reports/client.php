<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Client Reports</h1>
        <p class="page-subtitle">Company activity for a chosen period</p>
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
                <a class="btn btn-outline" href="client-reports.php?action=download&amp;format=html&amp;company_id=<?= (int) $report['company']['id'] ?>&amp;from=<?= urlencode($report['from']) ?>&amp;to=<?= urlencode($report['to']) ?>">Download HTML</a>
                <a class="btn btn-outline" href="client-reports.php?action=download&amp;format=csv&amp;company_id=<?= (int) $report['company']['id'] ?>&amp;from=<?= urlencode($report['from']) ?>&amp;to=<?= urlencode($report['to']) ?>">Download CSV</a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Period <?= sanitize($report['from']) ?> to <?= sanitize($report['to']) ?>. Reference <?= sanitize($report['reference']) ?>.</p>
            <div class="grid grid-3 gap-4">
                <div><strong>Status</strong><br><?= sanitize($report['company']['status']) ?></div>
                <div><strong>RC number</strong><br><?= sanitize($report['company']['rc_number'] ?: '-') ?></div>
                <div><strong>Subscription</strong><br><?= sanitize($report['company']['subscription_status']) ?></div>
                <div><strong>Email</strong><br><?= sanitize($report['company']['email'] ?: '-') ?></div>
                <div><strong>Phone</strong><br><?= sanitize($report['company']['phone'] ?: '-') ?></div>
                <div><strong>Assigned staff</strong><br><?= sanitize($report['company']['assigned_staff'] ?: '-') ?></div>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Registrations</h3></div>
        <div class="card-body p-0">
            <?php if (empty($report['registrations'])): ?>
                <p class="p-4 text-muted">No registrations in this period.</p>
            <?php else: ?>
                <table class="table"><thead><tr><th>Type</th><th>Status</th><th>Submitted</th><th>Expiry</th></tr></thead><tbody>
                <?php foreach ($report['registrations'] as $row): ?>
                    <tr>
                        <td><?= sanitize($row['registration_type']) ?></td>
                        <td><?= sanitize($row['status']) ?></td>
                        <td><?= formatDate($row['submission_date']) ?></td>
                        <td><?= formatDate($row['expiry_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Communications</h3></div>
        <div class="card-body p-0">
            <?php if (empty($report['communications'])): ?>
                <p class="p-4 text-muted">No communications in this period.</p>
            <?php else: ?>
                <table class="table"><thead><tr><th>Date</th><th>Type</th><th>Subject</th><th>Summary</th></tr></thead><tbody>
                <?php foreach ($report['communications'] as $row): ?>
                    <tr>
                        <td><?= formatDateTime($row['communication_date']) ?></td>
                        <td><?= sanitize($row['communication_type']) ?></td>
                        <td><?= sanitize($row['subject'] ?: '-') ?></td>
                        <td><?= sanitize($row['summary']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Tasks</h3></div>
        <div class="card-body p-0">
            <?php if (empty($report['tasks'])): ?>
                <p class="p-4 text-muted">No tasks in this period.</p>
            <?php else: ?>
                <table class="table"><thead><tr><th>Task</th><th>Status</th><th>Priority</th><th>Due</th></tr></thead><tbody>
                <?php foreach ($report['tasks'] as $row): ?>
                    <tr>
                        <td><?= sanitize($row['title']) ?></td>
                        <td><?= sanitize($row['status']) ?></td>
                        <td><?= sanitize($row['priority']) ?></td>
                        <td><?= formatDate($row['due_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Active documents</h3></div>
        <div class="card-body p-0">
            <?php if (empty($report['documents'])): ?>
                <p class="p-4 text-muted">No active documents.</p>
            <?php else: ?>
                <table class="table"><thead><tr><th>Name</th><th>Type</th><th>File or link</th><th>Expiry</th></tr></thead><tbody>
                <?php foreach ($report['documents'] as $row): ?>
                    <tr>
                        <td><?= sanitize($row['document_name']) ?></td>
                        <td><?= sanitize($row['document_type']) ?></td>
                        <td>
                            <?php if (!empty($row['external_url'])): ?>
                                <a href="<?= sanitize($row['external_url']) ?>" target="_blank" rel="noopener noreferrer">Open link</a>
                            <?php else: ?>
                                <?= sanitize($row['file_name'] ?: '-') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= formatDate($row['expiry_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Elite membership</h3></div>
        <div class="card-body">
            <?php if (empty($report['elite'])): ?>
                <p class="text-muted">No elite membership.</p>
            <?php else: ?>
                <p><?= sanitize(ucfirst($report['elite']['membership_tier'])) ?> · <?= sanitize($report['elite']['status']) ?> · started <?= formatDate($report['elite']['membership_start_date']) ?></p>
            <?php endif; ?>
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

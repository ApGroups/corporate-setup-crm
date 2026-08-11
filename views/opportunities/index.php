<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php $canManageOpportunities = checkPermission('manage_opportunities'); ?>

<?php if ($action === 'add' || ($action === 'edit' && $opportunity)): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'Add Opportunity' : 'Edit Opportunity' ?></h1>
            <p class="page-subtitle"><a href="opportunities.php">Opportunities</a> &rsaquo; <?= $action === 'add' ? 'New' : sanitize($opportunity['opportunity_title']) ?></p>
        </div>
        <div class="btn-group">
            <a href="opportunities.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="opportunityForm" class="btn btn-primary"><i class="fas fa-save"></i> Save Opportunity</button>
        </div>
    </div>

    <form id="opportunityForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if (!empty($opportunity['id'])): ?>
            <input type="hidden" name="id" value="<?= $opportunity['id'] ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required" for="opportunity_title">Title</label>
                    <input type="text" id="opportunity_title" name="opportunity_title" class="form-control" value="<?= sanitize($opportunity['opportunity_title'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="opportunity_type">Type</label>
                        <select id="opportunity_type" name="opportunity_type" class="form-control" required>
                            <option value="vendor" <?= ($opportunity['opportunity_type'] ?? '') === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                            <option value="government_tender" <?= ($opportunity['opportunity_type'] ?? '') === 'government_tender' ? 'selected' : '' ?>>Government Tender</option>
                            <option value="oil_gas" <?= ($opportunity['opportunity_type'] ?? '') === 'oil_gas' ? 'selected' : '' ?>>Oil & Gas</option>
                            <option value="training" <?= ($opportunity['opportunity_type'] ?? '') === 'training' ? 'selected' : '' ?>>Training</option>
                            <option value="consulting" <?= ($opportunity['opportunity_type'] ?? '') === 'consulting' ? 'selected' : '' ?>>Consulting</option>
                            <option value="partnership" <?= ($opportunity['opportunity_type'] ?? '') === 'partnership' ? 'selected' : '' ?>>Partnership</option>
                            <option value="other" <?= ($opportunity['opportunity_type'] ?? 'other') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="form-control">
                            <option value="">No Company</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($opportunity['company_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="assigned_staff_id">Assigned Staff</label>
                        <select id="assigned_staff_id" name="assigned_staff_id" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($opportunity['assigned_staff_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estimated_value">Estimated Value</label>
                        <input type="number" id="estimated_value" step="0.01" name="estimated_value" class="form-control" value="<?= sanitize($opportunity['estimated_value'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= $opportunity['deadline'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="new" <?= ($opportunity['status'] ?? 'new') === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="pursuing" <?= ($opportunity['status'] ?? '') === 'pursuing' ? 'selected' : '' ?>>Pursuing</option>
                            <option value="submitted" <?= ($opportunity['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option value="won" <?= ($opportunity['status'] ?? '') === 'won' ? 'selected' : '' ?>>Won</option>
                            <option value="lost" <?= ($opportunity['status'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                            <option value="cancelled" <?= ($opportunity['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="probability">Probability (%)</label>
                        <input type="number" id="probability" name="probability" min="0" max="100" class="form-control" value="<?= intval($opportunity['probability'] ?? 50) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= sanitize($opportunity['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $opportunity): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($opportunity['opportunity_title']) ?></h1>
            <p class="page-subtitle"><a href="opportunities.php">Opportunities</a> &rsaquo; Details</p>
        </div>
        <div class="btn-group">
            <?php if ($canManageOpportunities): ?>
                <a href="opportunities.php?action=edit&id=<?= $opportunity['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr><td style="width: 180px; font-weight: 500;">Type</td><td><?= sanitize(ucwords(str_replace('_', ' ', $opportunity['opportunity_type']))) ?></td></tr>
                <tr><td style="font-weight: 500;">Company</td><td><?= $opportunity['company_name'] ? sanitize($opportunity['company_name']) : '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Assigned Staff</td><td><?= sanitize($opportunity['assigned_staff']) ?: 'Unassigned' ?></td></tr>
                <tr><td style="font-weight: 500;">Estimated Value</td><td><?= $opportunity['estimated_value'] ? formatCurrency($opportunity['estimated_value']) : '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Deadline</td><td><?= $opportunity['deadline'] ? formatDate($opportunity['deadline']) : '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Status</td><td><?= getStatusBadge($opportunity['status']) ?></td></tr>
                <tr><td style="font-weight: 500;">Probability</td><td><?= intval($opportunity['probability']) ?>%</td></tr>
                <tr><td style="font-weight: 500;">Description</td><td><?= sanitize($opportunity['description']) ?: '-' ?></td></tr>
            </table>
        </div>
    </div>

<?php else: ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Opportunities</h1>
            <p class="page-subtitle">Track business opportunities and tenders</p>
        </div>
        <?php if ($canManageOpportunities): ?>
            <a href="opportunities.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Opportunity</a>
        <?php endif; ?>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="vendor" <?= $type === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                    <option value="oil_gas" <?= $type === 'oil_gas' ? 'selected' : '' ?>>Oil & Gas</option>
                    <option value="training" <?= $type === 'training' ? 'selected' : '' ?>>Training</option>
                    <option value="consulting" <?= $type === 'consulting' ? 'selected' : '' ?>>Consulting</option>
                    <option value="government_tender" <?= $type === 'government_tender' ? 'selected' : '' ?>>Gov Tender</option>
                </select>
                <select name="status" class="form-control">
                    <option value="all">All Statuses</option>
                    <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="pursuing" <?= $status === 'pursuing' ? 'selected' : '' ?>>Pursuing</option>
                    <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="won" <?= $status === 'won' ? 'selected' : '' ?>>Won</option>
                    <option value="lost" <?= $status === 'lost' ? 'selected' : '' ?>>Lost</option>
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
                        <tr><th>Opportunity</th><th>Type</th><th>Company</th><th>Value</th><th>Deadline</th><th>Status</th><th>Prob.</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($opportunities)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-lightbulb"></i><h3>No opportunities</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($opportunities as $o): ?>
                                <tr>
                                    <td><a href="opportunities.php?action=view&id=<?= $o['id'] ?>"><?= sanitize($o['opportunity_title']) ?></a></td>
                                    <td><span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $o['opportunity_type'])) ?></span></td>
                                    <td><?= $o['company_name'] ? '<a href="companies.php?action=view&id=' . $o['company_id'] . '">' . sanitize($o['company_name']) . '</a>' : '-' ?></td>
                                    <td><?= $o['estimated_value'] ? formatCurrency($o['estimated_value']) : '-' ?></td>
                                    <td><?= $o['deadline'] ? formatDate($o['deadline']) : '-' ?></td>
                                    <td><?= getStatusBadge($o['status']) ?></td>
                                    <td><?= $o['probability'] ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

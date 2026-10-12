<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Elite Members</h1>
        <p class="page-subtitle">Manage premium client memberships</p>
    </div>
    <?php if (checkPermission('export_data')): ?>
    <div class="btn-group">
        <a class="btn btn-outline" href="export.php?type=elites&amp;format=csv&amp;search=<?= urlencode($search) ?>&amp;status=<?= urlencode($status) ?>&amp;from=<?= urlencode(date('Y-m-01')) ?>&amp;to=<?= urlencode(date('Y-m-t')) ?>">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center flex-wrap">
            <input type="text" name="search" class="form-control" placeholder="Search company, tier, or manager" value="<?= sanitize($search) ?>" style="width: 280px;">
            <select name="status" class="form-control">
                <option value="">All statuses</option>
                <?php foreach (['active', 'expired', 'suspended', 'cancelled'] as $option): ?>
                    <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="elite.php" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="card-title">Add membership</h3></div>
    <div class="card-body">
        <form method="POST" class="flex gap-4 items-center flex-wrap">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <select name="company_id" class="form-control" required>
                <option value="">Company</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= (int) $company['id'] ?>"><?= sanitize($company['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="membership_tier" class="form-control">
                <?php foreach (['basic', 'premium', 'elite', 'platinum'] as $tier): ?>
                    <option value="<?= $tier ?>" <?= $tier === 'elite' ? 'selected' : '' ?>><?= ucfirst($tier) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="membership_start_date" class="form-control" required>
            <input type="date" name="membership_end_date" class="form-control">
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Company</th><th>Tier</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Account Manager</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-crown"></i><h3>No Elite Members</h3></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($m['company_name'])): ?>
                                        <a href="companies.php?action=view&id=<?= (int) $m['company_id'] ?>"><?= sanitize($m['company_name']) ?></a>
                                    <?php else: ?>
                                        Unknown company
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-warning"><i class="fas fa-crown"></i> <?= ucwords(str_replace('_', ' ', (string) $m['membership_tier'])) ?></span></td>
                                <td><?= formatDate($m['membership_start_date']) ?></td>
                                <td><?= formatDate($m['membership_end_date']) ?></td>
                                <td><?= getStatusBadge($m['status']) ?></td>
                                <td><?= !empty($m['account_manager']) ? sanitize($m['account_manager']) : '-' ?></td>
                                <td>
                                    <?php if (!empty($m['company_id']) && !empty($m['company_name'])): ?>
                                        <a href="companies.php?action=view&id=<?= (int) $m['company_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                    <?php endif; ?>
                                </td>
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

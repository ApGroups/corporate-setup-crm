<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Elite Members</h1>
        <p class="page-subtitle">Manage premium client memberships</p>
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
                                <td><a href="companies.php?action=view&id=<?= $m['company_id'] ?>"><?= sanitize($m['company_name']) ?></a></td>
                                <td><span class="badge badge-warning"><i class="fas fa-crown"></i> <?= ucwords($m['membership_tier']) ?></span></td>
                                <td><?= formatDate($m['membership_start_date']) ?></td>
                                <td><?= formatDate($m['membership_end_date']) ?></td>
                                <td><?= getStatusBadge($m['status']) ?></td>
                                <td><?= sanitize($m['account_manager']) ?: '-' ?></td>
                                <td><a href="companies.php?action=view&id=<?= $m['company_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

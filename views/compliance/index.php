<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Compliance Tracker</h1>
        <p class="page-subtitle">Monitor registration status and expiry dates</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-5 gap-4 mb-6">
    <a href="?filter=all" class="stat-card <?= $filter === 'all' ? 'border-primary' : '' ?>">
        <div class="stat-icon primary"><i class="fas fa-list"></i></div>
        <div class="stat-content"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total</div></div>
    </a>
    <a href="?filter=expired" class="stat-card <?= $filter === 'expired' ? 'border-danger' : '' ?>">
        <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-content"><div class="stat-value"><?= $stats['expired'] ?></div><div class="stat-label">Expired</div></div>
    </a>
    <a href="?filter=expiring" class="stat-card <?= $filter === 'expiring' ? 'border-warning' : '' ?>">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="stat-content"><div class="stat-value"><?= $stats['expiring'] ?></div><div class="stat-label">Expiring</div></div>
    </a>
    <a href="?filter=pending" class="stat-card <?= $filter === 'pending' ? 'border-info' : '' ?>">
        <div class="stat-icon info"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-content"><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div>
    </a>
    <a href="?filter=approved" class="stat-card <?= $filter === 'approved' ? 'border-success' : '' ?>">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content"><div class="stat-value"><?= $stats['approved'] ?></div><div class="stat-label">Approved</div></div>
    </a>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center flex-wrap">
            <select name="filter" class="form-control">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="expired" <?= $filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="expiring" <?= $filter === 'expiring' ? 'selected' : '' ?>>Expiring Soon</option>
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <select name="company" class="form-control">
                <option value="">All Companies</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $companyId == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="compliance.php" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Registration</th>
                        <th>Status</th>
                        <th>Expiry Date</th>
                        <th>Days</th>
                        <th>Assigned</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-check-circle"></i><h3>No records found</h3></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $r): ?>
                            <?php $days = $r['expiry_date'] ? getDaysRemaining($r['expiry_date']) : null; ?>
                            <tr>
                                <td><a href="companies.php?action=view&id=<?= $r['company_id'] ?>"><?= sanitize($r['company_name']) ?></a></td>
                                <td><a href="registrations.php?action=view&id=<?= $r['id'] ?>"><?= sanitize($r['registration_type']) ?></a></td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                                <td><?= $r['expiry_date'] ? formatDate($r['expiry_date']) : '-' ?></td>
                                <td>
                                    <?php if ($days !== null): ?>
                                        <?php if ($days < 0): ?>
                                            <span class="badge badge-danger"><?= abs($days) ?> days overdue</span>
                                        <?php elseif ($days <= 30): ?>
                                            <span class="badge badge-warning"><?= $days ?> days left</span>
                                        <?php elseif ($days <= 90): ?>
                                            <span class="badge badge-info"><?= $days ?> days left</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= $days ?> days</span>
                                        <?php endif; ?>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td><?= sanitize($r['assigned_staff']) ?: '-' ?></td>
                                <td><a href="registrations.php?action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

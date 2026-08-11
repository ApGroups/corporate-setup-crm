<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <p class="page-subtitle">Recent system activity across the CRM</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center flex-wrap">
            <select name="user" class="form-control">
                <option value="">All Staff</option>
                <?php foreach ($staff as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= $filterUser == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="action_type" class="form-control">
                <option value="">All Actions</option>
                <?php foreach ($actionTypes as $a): ?>
                    <option value="<?= sanitize($a['action']) ?>" <?= $actionFilter === $a['action'] ? 'selected' : '' ?>><?= sanitize($a['action']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" class="form-control" value="<?= sanitize($dateFrom) ?>">
            <input type="date" name="to" class="form-control" value="<?= sanitize($dateTo) ?>">
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
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Entity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-history"></i><h3>No activity found</h3></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($activities as $a): ?>
                            <tr>
                                <td><?= formatDateTime($a['created_at']) ?><br><small class="text-muted"><?= timeAgo($a['created_at']) ?></small></td>
                                <td><?= sanitize($a['user_name'] ?? 'System') ?></td>
                                <td><span class="badge badge-info"><?= sanitize($a['action']) ?></span></td>
                                <td><?= sanitize($a['description']) ?></td>
                                <td>
                                    <?php if ($a['table_name']): ?>
                                        <?= sanitize($a['table_name']) ?><?= $a['record_id'] ? ' #' . (int)$a['record_id'] : '' ?>
                                    <?php else: ?>
                                        -
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

<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Stay updated with your activities</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <button onclick="markAllRead()" class="btn btn-outline">
            <i class="fas fa-check-double"></i> Mark All as Read
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications</h3>
                <p>You're all caught up!</p>
            </div>
        <?php else: ?>
            <div class="list">
                <?php foreach ($notifications as $n): ?>
                    <div class="flex items-start gap-3 p-4 border-b" style="background: <?= $n['is_read'] ? 'transparent' : 'var(--info-light)' ?>; border-bottom: 1px solid var(--grey);">
                        <div class="stat-icon <?= $n['is_read'] ? 'secondary' : 'primary' ?>" style="width: 40px; height: 40px;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <strong style="color: var(--primary);"><?= sanitize($n['title']) ?></strong>
                                <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                            </div>
                            <p class="text-muted mt-1"><?= sanitize($n['message']) ?></p>
                            <?php if (isset($n['sent_email']) && (int)$n['sent_email'] === 1): ?>
                                <small class="text-muted" style="display:block; margin-top: 0.5rem;">Email delivered to mailbox provider.</small>
                            <?php endif; ?>
                            <?php if ($n['link']): ?>
                                <a href="<?= sanitize($n['link']) ?>" class="btn btn-sm btn-outline mt-2">View Details</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <button onclick="markRead(<?= $n['id'] ?>)" class="btn btn-sm btn-outline">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function markRead(id) {
        fetch('notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mark_read=' + id + '&csrf_token=<?= Security::generateCSRFToken() ?>'
        }).then(() => location.reload());
    }

    function markAllRead() {
        fetch('notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mark_all_read=1&csrf_token=<?= Security::generateCSRFToken() ?>'
        }).then(() => location.reload());
    }
</script>

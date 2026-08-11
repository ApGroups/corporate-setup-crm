<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">View and manage your account</p>
    </div>
</div>

<div class="grid grid-2 gap-6">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Profile Information</h3></div>
        <div class="card-body text-center" style="padding: 2rem;">
            <?= generateAvatar($profile['name'], 100) ?>
            <h2 style="margin: 1rem 0 0.25rem;"><?= sanitize($profile['name']) ?></h2>
            <p class="text-muted"><?= sanitize($profile['role_name']) ?></p>
            <p class="text-muted" style="font-size: 0.875rem;"><?= sanitize($profile['department_name']) ?></p>
        </div>
        <div class="card-body">
            <table class="table">
                <tr><td style="width: 140px; font-weight: 500;">Employee ID</td><td><?= sanitize($profile['employee_id']) ?></td></tr>
                <tr><td style="font-weight: 500;">Email</td><td><?= sanitize($profile['email']) ?></td></tr>
                <tr><td style="font-weight: 500;">Phone</td><td><?= sanitize($profile['phone']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Position</td><td><?= sanitize($profile['position']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Status</td><td><?= getStatusBadge($profile['status']) ?></td></tr>
                <tr><td style="font-weight: 500;">Last Login</td><td><?= $profile['last_login'] ? formatDateTime($profile['last_login']) : 'Never' ?></td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Account Settings</h3></div>
        <div class="card-body">
            <div class="list" style="border: 1px solid var(--grey); border-radius: var(--border-radius);">
                <a href="change-password.php" class="flex items-center gap-3 p-4" style="border-bottom: 1px solid var(--grey);">
                    <div class="stat-icon primary" style="width: 40px; height: 40px;"><i class="fas fa-key"></i></div>
                    <div class="flex-1">
                        <strong>Change Password</strong>
                        <br><small class="text-muted">Update your account password</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
                <a href="notifications.php" class="flex items-center gap-3 p-4">
                    <div class="stat-icon secondary" style="width: 40px; height: 40px;"><i class="fas fa-bell"></i></div>
                    <div class="flex-1">
                        <strong>Notifications</strong>
                        <br><small class="text-muted">View your notifications</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

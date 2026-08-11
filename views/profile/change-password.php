<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Change Password</h1>
        <p class="page-subtitle"><a href="profile.php">Profile</a> &rsaquo; Change Password</p>
    </div>
</div>

<div class="card" style="max-width: 500px;">
    <div class="card-header"><h3 class="card-title">Update Your Password</h3></div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><div class="alert-content"><?= sanitize($error) ?></div></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i><div class="alert-content"><?= sanitize($success) ?></div></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label required" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label required" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
                <small class="form-hint">Minimum 8 characters</small>
            </div>

            <div class="form-group">
                <label class="form-label required" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Change Password</button>
        </form>
    </div>
</div>

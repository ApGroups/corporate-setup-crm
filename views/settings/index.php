<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Manage system configuration</p>
    </div>
</div>

<div class="tabs mb-4">
    <a href="?tab=general" class="tab <?= $tab === 'general' ? 'active' : '' ?>">General</a>
    <a href="?tab=company" class="tab <?= $tab === 'company' ? 'active' : '' ?>">Company</a>
    <a href="?tab=notifications" class="tab <?= $tab === 'notifications' ? 'active' : '' ?>">Notifications</a>
    <a href="?tab=backup" class="tab <?= $tab === 'backup' ? 'active' : '' ?>">Backup</a>
</div>

<?php if ($tab === 'general'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">General Settings</h3></div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="grid grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" value="<?= sanitize($config['company_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_email">Company Email</label>
                        <input type="email" id="company_email" name="company_email" class="form-control" value="<?= sanitize($config['company_email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_phone">Company Phone</label>
                        <input type="tel" id="company_phone" name="company_phone" class="form-control" value="<?= sanitize($config['company_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="timezone">Timezone</label>
                        <select id="timezone" name="timezone" class="form-control">
                            <option value="Africa/Lagos" <?= ($config['timezone'] ?? '') === 'Africa/Lagos' ? 'selected' : '' ?>>Africa/Lagos (WAT)</option>
                            <option value="UTC" <?= ($config['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date_format">Date Format</label>
                        <select id="date_format" name="date_format" class="form-control">
                            <option value="d-m-Y" <?= ($config['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY</option>
                            <option value="m-d-Y" <?= ($config['date_format'] ?? '') === 'm-d-Y' ? 'selected' : '' ?>>MM-DD-YYYY</option>
                            <option value="Y-m-d" <?= ($config['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="items_per_page">Items per Page</label>
                        <select id="items_per_page" name="items_per_page" class="form-control">
                            <option value="10" <?= ($config['items_per_page'] ?? '20') === '10' ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ($config['items_per_page'] ?? '20') === '20' ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ($config['items_per_page'] ?? '20') === '50' ? 'selected' : '' ?>>50</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label" for="company_address">Company Address</label>
                    <textarea id="company_address" name="company_address" class="form-control" rows="2"><?= sanitize($config['company_address'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="submit" class="btn btn-primary mt-4"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'company'): ?>
    <div class="grid grid-2 gap-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Departments</h3></div>
            <div class="card-body">
                <p>Manage company departments.</p>
                <a href="staff.php?tab=departments" class="btn btn-outline mt-2">Manage Departments</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">User Roles</h3></div>
            <div class="card-body">
                <p>Manage user roles and permissions.</p>
                <a href="staff.php?tab=roles" class="btn btn-outline mt-2">Manage Roles</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Registration Types</h3></div>
            <div class="card-body">
                <p>Configure available registration types.</p>
                <a href="settings.php?tab=registrations" class="btn btn-outline mt-2">View Types</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Industries</h3></div>
            <div class="card-body">
                <p>Manage industry categories.</p>
                <a href="settings.php?tab=industries" class="btn btn-outline mt-2">View Industries</a>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'registrations'): ?>
    <?php
    $registrationTypes = $db->fetchAll("SELECT * FROM registration_types ORDER BY name");
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Registration Types</h3></div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>Name</th><th>Description</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($registrationTypes as $rt): ?>
                        <tr>
                            <td><?= sanitize($rt['name']) ?></td>
                            <td><?= sanitize($rt['description'] ?? '-') ?></td>
                            <td><?= getStatusBadge($rt['status'] ?? 'active') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'industries'): ?>
    <?php
    $industries = $db->fetchAll("SELECT * FROM industries ORDER BY name");
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Industries</h3></div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>Name</th><th>Description</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($industries as $ind): ?>
                        <tr>
                            <td><?= sanitize($ind['name']) ?></td>
                            <td><?= sanitize($ind['description'] ?? '-') ?></td>
                            <td><?= getStatusBadge($ind['status'] ?? 'active') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'notifications'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Notification Settings</h3></div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="enable_notifications" value="1" <?= ($config['enable_notifications'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Enable Email Notifications</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="enable_daily_reports" value="1" <?= ($config['enable_daily_reports'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Enable Daily Reports</span>
                    </label>
                </div>
                <button type="submit" name="submit" class="btn btn-primary mt-4"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'backup'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Database Backup</h3></div>
        <div class="card-body">
            <p>Download a backup of your database. It is recommended to backup regularly.</p>
            <a href="backup.php?action=download" class="btn btn-primary mt-2">
                <i class="fas fa-download"></i> Download Backup
            </a>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Backup History</h3></div>
        <div class="card-body p-0">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Status</th><th>Size</th></tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="text-center text-muted">No backups recorded</td></tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

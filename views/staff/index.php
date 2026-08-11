<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php $canManageUsers = checkPermission('manage_users'); ?>

<?php if ($action === 'add' || ($action === 'edit' && $staffMember)): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'Add Staff' : 'Edit Staff' ?></h1>
            <p class="page-subtitle"><a href="staff.php?tab=list">Staff Management</a> &rsaquo; <?= $action === 'add' ? 'New Staff' : sanitize($staffMember['name'] ?? '') ?></p>
        </div>
        <div class="btn-group">
            <a href="staff.php<?= $action === 'edit' && !empty($staffMember['id']) ? '?action=view&id=' . intval($staffMember['id']) : '?tab=list' ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" form="staffForm" class="btn btn-primary"><i class="fas fa-save"></i> Save Staff</button>
        </div>
    </div>

    <form id="staffForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if (!empty($staffMember['id'])): ?>
            <input type="hidden" name="id" value="<?= intval($staffMember['id']) ?>">
        <?php endif; ?>

        <div class="grid grid-2 gap-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">User Details</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="employee_id">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= sanitize($staffMember['employee_id'] ?? '') ?>" placeholder="e.g. CSL-100">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($staffMember['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($staffMember['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= sanitize($staffMember['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="position">Position</label>
                        <input type="text" id="position" name="position" class="form-control" value="<?= sanitize($staffMember['position'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Access Settings</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="role_id">Role</label>
                        <select id="role_id" name="role_id" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($staffMember['role_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="department_id">Department</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($staffMember['department_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label <?= $action === 'add' ? 'required' : '' ?>" for="password"><?= $action === 'add' ? 'Temporary Password' : 'New Password (optional)' ?></label>
                        <input type="password" id="password" name="password" class="form-control" minlength="8" <?= $action === 'add' ? 'required' : '' ?>>
                        <small class="text-muted">Minimum 8 characters<?= $action === 'edit' ? '. Leave blank to keep current password.' : '' ?></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?= ($staffMember['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($staffMember['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="suspended" <?= ($staffMember['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="must_change_password" <?= ($staffMember['must_change_password'] ?? 1) ? 'checked' : '' ?>>
                            <span>Require password change on first login</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $staffMember): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($staffMember['name']) ?></h1>
            <p class="page-subtitle"><a href="staff.php?tab=list">Staff Management</a> &rsaquo; Profile</p>
        </div>
        <div class="btn-group">
            <?php if ($canManageUsers): ?>
                <a href="staff.php?action=edit&id=<?= intval($staffMember['id']) ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <button type="button" data-delete-staff-id="<?= intval($staffMember['id']) ?>" class="btn btn-danger"><i class="fas fa-user-slash"></i> Deactivate</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr><td style="width: 180px; font-weight: 500;">Employee ID</td><td><?= sanitize($staffMember['employee_id']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Full Name</td><td><?= sanitize($staffMember['name']) ?></td></tr>
                <tr><td style="font-weight: 500;">Email</td><td><?= sanitize($staffMember['email']) ?></td></tr>
                <tr><td style="font-weight: 500;">Phone</td><td><?= sanitize($staffMember['phone']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Position</td><td><?= sanitize($staffMember['position']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Role</td><td><?= sanitize($staffMember['role_name']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Department</td><td><?= sanitize($staffMember['department_name']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Status</td><td><?= getStatusBadge($staffMember['status']) ?></td></tr>
                <tr><td style="font-weight: 500;">Last Login</td><td><?= $staffMember['last_login'] ? formatDateTime($staffMember['last_login']) : 'Never' ?></td></tr>
            </table>
        </div>
    </div>

<?php else: ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Staff Management</h1>
        <p class="page-subtitle">Manage employees and permissions</p>
    </div>
    <div class="btn-group">
        <?php if ($canManageUsers): ?>
            <a href="staff.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Staff</a>
        <?php endif; ?>
    </div>
</div>

<div class="tabs mb-4">
    <a href="?tab=list" class="tab <?= $tab === 'list' ? 'active' : '' ?>">All Staff</a>
    <a href="?tab=departments" class="tab <?= $tab === 'departments' ? 'active' : '' ?>">Departments</a>
    <a href="?tab=roles" class="tab <?= $tab === 'roles' ? 'active' : '' ?>">Roles</a>
</div>

<?php if ($tab === 'list'): ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <?= generateAvatar($s['name'], 36) ?>
                                        <div>
                                            <strong><?= sanitize($s['name']) ?></strong>
                                            <br><small class="text-muted"><?= sanitize($s['employee_id']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= sanitize($s['email']) ?></td>
                                <td><?= sanitize($s['role_name']) ?></td>
                                <td><?= sanitize($s['department_name']) ?: '-' ?></td>
                                <td><?= getStatusBadge($s['status']) ?></td>
                                <td><?= $s['last_login'] ? timeAgo($s['last_login']) : 'Never' ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="staff.php?action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                        <?php if ($canManageUsers): ?>
                                            <a href="staff.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                            <button type="button" data-delete-staff-id="<?= $s['id'] ?>" class="btn btn-sm btn-danger" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'departments'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Departments</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>Department</th><th>Description</th><th>Head</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><strong><?= sanitize($d['name']) ?></strong></td>
                                <td><?= sanitize($d['description']) ?: '-' ?></td>
                                <td>-</td>
                                <td><?= getStatusBadge($d['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'roles'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">User Roles</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>Role</th><th>Description</th><th>System</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td><strong><?= sanitize($r['display_name']) ?></strong></td>
                                <td><?= sanitize($r['description']) ?: '-' ?></td>
                                <td><?= $r['is_system'] ? '<span class="badge badge-info">System</span>' : '<span class="badge badge-secondary">Custom</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php endif; ?>

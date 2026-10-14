<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php $canManageUsers = checkPermission('manage_users'); ?>
<?php $canDeleteUsers = isset($auth) ? $auth->hasRole('Administrator') : checkPermission('manage_users'); ?>

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

        <?php if ($profileCompletion): ?>
            <p class="profile-meter">Profile sections complete: <?= (int) $profileCompletion['done'] ?> of <?= (int) $profileCompletion['total'] ?>. Optional sections do not block saving.</p>
        <?php endif; ?>

        <details class="card staff-panel" open>
            <summary class="card-header">Address</summary>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="residential_address">Residential address</label>
                    <textarea id="residential_address" name="residential_address" class="form-control" rows="2"><?= sanitize($staffMember['residential_address'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="street">Street or house number</label>
                        <input type="text" id="street" name="street" class="form-control" value="<?= sanitize($staffMember['street'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="address_city">City or town</label>
                        <input type="text" id="address_city" name="address_city" class="form-control" value="<?= sanitize($staffMember['address_city'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="address_state">State</label>
                        <input type="text" id="address_state" name="address_state" class="form-control" value="<?= sanitize($staffMember['address_state'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="postal_code">Postal code</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= sanitize($staffMember['postal_code'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="address_country">Country</label>
                        <input type="text" id="address_country" name="address_country" class="form-control" value="<?= sanitize($staffMember['address_country'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="mailing_same" <?= !empty($staffMember['mailing_same']) ? 'checked' : '' ?>>
                        <span>Mailing address is the same as the residential address</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mailing_address">Mailing address</label>
                    <textarea id="mailing_address" name="mailing_address" class="form-control" rows="2"><?= sanitize($staffMember['mailing_address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="alternative_address">Alternative contact address</label>
                    <textarea id="alternative_address" name="alternative_address" class="form-control" rows="2"><?= sanitize($staffMember['alternative_address'] ?? '') ?></textarea>
                </div>
            </div>
        </details>

        <details class="card staff-panel">
            <summary class="card-header">Next of kin</summary>
            <div class="card-body">
                <p class="text-muted">Next of kin is separate from an emergency contact. Leave a new row blank if you are not adding one.</p>
                <?php
                $kinRows = $nextOfKin;
                $kinRows[] = ['id' => 0, 'full_name' => '', 'relationship' => '', 'phone' => '', 'alt_phone' => '', 'email' => '', 'address' => '', 'city' => '', 'state' => '', 'country' => '', 'notes' => ''];
                foreach ($kinRows as $kinIndex => $kin):
                ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <input type="hidden" name="kin_id[<?= $kinIndex ?>]" value="<?= (int) ($kin['id'] ?? 0) ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Full name</label>
                                    <input type="text" name="kin_name[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['full_name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Relationship</label>
                                    <input type="text" name="kin_relationship[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['relationship'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Primary phone</label>
                                    <input type="text" name="kin_phone[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alternative phone</label>
                                    <input type="text" name="kin_alt_phone[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['alt_phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="kin_email[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Residential address</label>
                                <textarea name="kin_address[<?= $kinIndex ?>]" class="form-control" rows="2"><?= sanitize($kin['address'] ?? '') ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">City or town</label>
                                    <input type="text" name="kin_city[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['city'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">State</label>
                                    <input type="text" name="kin_state[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['state'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="kin_country[<?= $kinIndex ?>]" class="form-control" value="<?= sanitize($kin['country'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="kin_notes[<?= $kinIndex ?>]" class="form-control" rows="2"><?= sanitize($kin['notes'] ?? '') ?></textarea>
                            </div>
                            <?php if (!empty($kin['id'])): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="kin_remove[<?= $kinIndex ?>]" value="1">
                                    <span>Remove this next of kin</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="card staff-panel">
            <summary class="card-header">Emergency contact</summary>
            <div class="card-body">
                <p class="text-muted">An emergency contact can be a different person from the next of kin.</p>
                <?php
                $emergencyRows = $emergencyContacts;
                $emergencyRows[] = ['id' => 0, 'full_name' => '', 'relationship' => '', 'phone' => '', 'alt_phone' => '', 'email' => '', 'address' => '', 'preferred_method' => '', 'sort_order' => count($emergencyContacts) + 1];
                foreach ($emergencyRows as $emergencyIndex => $emergency):
                ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <input type="hidden" name="emergency_id[<?= $emergencyIndex ?>]" value="<?= (int) ($emergency['id'] ?? 0) ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Full name</label>
                                    <input type="text" name="emergency_name[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['full_name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Relationship</label>
                                    <input type="text" name="emergency_relationship[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['relationship'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact order</label>
                                    <input type="number" min="1" name="emergency_order[<?= $emergencyIndex ?>]" class="form-control" value="<?= (int) ($emergency['sort_order'] ?? 1) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Primary phone</label>
                                    <input type="text" name="emergency_phone[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alternative phone</label>
                                    <input type="text" name="emergency_alt_phone[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['alt_phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="emergency_email[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Residential address</label>
                                <textarea name="emergency_address[<?= $emergencyIndex ?>]" class="form-control" rows="2"><?= sanitize($emergency['address'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preferred contact method</label>
                                <input type="text" name="emergency_method[<?= $emergencyIndex ?>]" class="form-control" value="<?= sanitize($emergency['preferred_method'] ?? '') ?>" placeholder="Phone, email, or either">
                            </div>
                            <?php if (!empty($emergency['id'])): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="emergency_remove[<?= $emergencyIndex ?>]" value="1">
                                    <span>Remove this emergency contact</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
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
                <?php if ($canDeleteUsers && intval($staffMember['id']) !== intval($user['id'])): ?>
                    <button type="button" data-delete-staff-id="<?= intval($staffMember['id']) ?>" class="btn btn-danger"><i class="fas fa-user-slash"></i> Delete</button>
                <?php endif; ?>
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
                <tr><td style="font-weight: 500;">Profile</td><td><?= (int) $profileCompletion['done'] ?> of <?= (int) $profileCompletion['total'] ?> sections complete</td></tr>
                <tr><td style="font-weight: 500;">Residential address</td><td><?= sanitize($staffMember['residential_address'] ?? '') ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">City</td><td><?= sanitize($staffMember['address_city'] ?? '') ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Mailing address</td><td><?= sanitize($staffMember['mailing_address'] ?? '') ?: '-' ?></td></tr>
            </table>
            <h3 class="card-title mt-4">Next of kin</h3>
            <?php if (empty($nextOfKin)): ?>
                <p class="text-muted">No next of kin recorded.</p>
            <?php else: ?>
                <?php foreach ($nextOfKin as $kin): ?>
                    <p><strong><?= sanitize($kin['full_name']) ?></strong> <?= sanitize($kin['relationship'] ?? '') ?><br><?= sanitize($kin['phone'] ?? '') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <h3 class="card-title">Emergency contacts</h3>
            <?php if (empty($emergencyContacts)): ?>
                <p class="text-muted">No emergency contact recorded.</p>
            <?php else: ?>
                <?php foreach ($emergencyContacts as $emergency): ?>
                    <p><strong><?= sanitize($emergency['full_name']) ?></strong> <?= sanitize($emergency['relationship'] ?? '') ?><br><?= sanitize($emergency['phone'] ?? '') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
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
                                            <?php if ($canDeleteUsers && intval($s['id']) !== intval($user['id'])): ?>
                                                <button type="button" data-delete-staff-id="<?= $s['id'] ?>" class="btn btn-sm btn-danger" title="Delete staff member"><i class="fas fa-user-slash"></i></button>
                                            <?php endif; ?>
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
                                <td><?= sanitize($d['head_name'] ?? '-') ?></td>
                                <td><?= getStatusBadge($d['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'roles'): ?>
    <?php if ($editRole && checkPermission('manage_users')): ?>
        <div class="page-header" style="margin-top: 0;">
            <div>
                <h2 class="page-title" style="font-size: 1.25rem;">Edit Permissions — <?= sanitize($editRole['display_name']) ?></h2>
                <p class="page-subtitle"><a href="staff.php?tab=roles">Roles</a> &rsaquo; Permissions</p>
            </div>
            <div class="btn-group">
                <a href="staff.php?tab=roles" class="btn btn-outline">Back</a>
            </div>
        </div>

        <form id="rolePermissionsForm" method="POST" action="staff.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_role_permissions">
            <input type="hidden" name="role_id" value="<?= intval($editRole['id']) ?>">

            <?php if ($editRole['name'] === 'Administrator'): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <div class="alert-content">Administrator always has full access (<code>*</code>). Saving will keep full access.</div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php foreach ($availablePermissions as $group => $slugs): ?>
                        <div class="mb-4">
                            <h4 style="margin-bottom: 0.75rem;"><?= sanitize($group) ?></h4>
                            <div class="grid grid-2 gap-2">
                                <?php foreach ($slugs as $slug): ?>
                                    <label class="flex items-center gap-2" style="font-weight: normal;">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="<?= sanitize($slug) ?>"
                                               <?= in_array($slug, $editRolePerms, true) || in_array('*', $editRolePerms, true) ? 'checked' : '' ?>
                                               <?= $editRole['name'] === 'Administrator' ? 'disabled' : '' ?>>
                                        <span><?= sanitize($slug) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer flex justify-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Permissions</button>
                </div>
            </div>
        </form>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">User Roles</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>Role</th><th>Description</th><th>Permissions</th><th>System</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <?php
                            $perms = $r['permissions'] ? json_decode($r['permissions'], true) : [];
                            $permCount = is_array($perms) ? (in_array('*', $perms, true) ? 'All (*)' : count($perms)) : 0;
                            ?>
                            <tr>
                                <td><strong><?= sanitize($r['display_name']) ?></strong></td>
                                <td><?= sanitize($r['description']) ?: '-' ?></td>
                                <td><?= is_string($permCount) ? sanitize($permCount) : ((int)$permCount . ' permissions') ?></td>
                                <td><?= $r['is_system'] ? '<span class="badge badge-info">System</span>' : '<span class="badge badge-secondary">Custom</span>' ?></td>
                                <td>
                                    <?php if (checkPermission('manage_users')): ?>
                                        <a href="staff.php?tab=roles&role_id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-key"></i> Permissions</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php endif; ?>

<div class="modal-overlay" id="deleteStaffModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Delete Staff Member?</h3>
            <button class="modal-close" type="button" data-close-modal="deleteStaffModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="deleteStaffForm" method="POST" action="staff.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteStaffId" value="">
            <div class="modal-body">
                <p>You are about to remove <strong id="deleteStaffName">this staff member</strong> from the CRM.</p>
                <p class="text-muted">This action deactivates access while preserving historical records and related business data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="deleteStaffModal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="confirmDeleteStaffBtn">Delete Staff</button>
            </div>
        </form>
    </div>
</div>

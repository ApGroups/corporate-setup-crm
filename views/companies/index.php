<?php
/**
 * Companies View
 * Corporate Setup Limited - Internal CRM
 */

defined('APP_NAME') or die('Direct access not allowed');

$canManageCompanies = checkPermission('manage_companies');
$canManageContacts = checkPermission('manage_contacts') || $canManageCompanies;
?>

<?php if ($action === 'add' || ($action === 'edit' && $company)): ?>
    <!-- Company Form -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'Add New Company' : 'Edit Company' ?></h1>
            <p class="page-subtitle">
                <a href="companies.php">Companies</a> &rsaquo; <?= $action === 'add' ? 'New' : sanitize($company['company_name'] ?? '') ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="companies.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="companyForm" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Company
            </button>
        </div>
    </div>

    <form id="companyForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($action === 'edit' && !empty($company['id'])): ?>
            <input type="hidden" name="id" value="<?= (int) $company['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-2 gap-4">
            <!-- Company Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Company Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" class="form-control"
                               value="<?= sanitize($company['company_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="rc_number">RC Number</label>
                            <input type="text" id="rc_number" name="rc_number" class="form-control"
                                   value="<?= sanitize($company['rc_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="industry_id">Industry</label>
                            <select id="industry_id" name="industry_id" class="form-control">
                                <option value="">Select Industry</option>
                                <?php foreach ($industries as $ind): ?>
                                    <option value="<?= $ind['id'] ?>" <?= ($company['industry_id'] ?? '') == $ind['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($ind['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="2"><?= sanitize($company['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="city">City</label>
                            <input type="text" id="city" name="city" class="form-control"
                                   value="<?= sanitize($company['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="state">State</label>
                            <input type="text" id="state" name="state" class="form-control"
                                   value="<?= sanitize($company['state'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="country_id">Country</label>
                        <select id="country_id" name="country_id" class="form-control">
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?= $country['id'] ?>" <?= ($company['country_id'] ?? '') == $country['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($country['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?= sanitize($company['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?= sanitize($company['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control"
                               value="<?= sanitize($company['website'] ?? '') ?>" placeholder="https://">
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contact Person</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="contact_person">Contact Name</label>
                        <input type="text" id="contact_person" name="contact_person" class="form-control"
                               value="<?= sanitize($company['contact_person'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact_position">Position</label>
                        <input type="text" id="contact_position" name="contact_position" class="form-control"
                               value="<?= sanitize($company['contact_position'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="contact_phone">Contact Phone</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                   value="<?= sanitize($company['contact_phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_email">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control"
                                   value="<?= sanitize($company['contact_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="card-header" style="border-top: 1px solid var(--grey); margin-top: 0;">
                    <h3 class="card-title">Client Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="prospect" <?= ($company['status'] ?? '') === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                                <option value="active" <?= ($company['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($company['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="churned" <?= ($company['status'] ?? '') === 'churned' ? 'selected' : '' ?>>Churned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="subscription_status">Subscription</label>
                            <select id="subscription_status" name="subscription_status" class="form-control">
                                <option value="free" <?= ($company['subscription_status'] ?? '') === 'free' ? 'selected' : '' ?>>Free</option>
                                <option value="basic" <?= ($company['subscription_status'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic</option>
                                <option value="premium" <?= ($company['subscription_status'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
                                <option value="elite" <?= ($company['subscription_status'] ?? '') === 'elite' ? 'selected' : '' ?>>Elite</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="assigned_staff_id">Assigned Staff</label>
                            <select id="assigned_staff_id" name="assigned_staff_id" class="form-control">
                                <option value="">Unassigned</option>
                                <?php foreach ($staff as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($company['assigned_staff_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="client_since">Client Since</label>
                            <input type="date" id="client_since" name="client_since" class="form-control"
                                   value="<?= $company['client_since'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="is_elite" <?= ($company['is_elite'] ?? 0) ? 'checked' : '' ?>>
                            <span>Elite Member</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"><?= sanitize($company['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $company): ?>
    <!-- Company Detail View -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($company['company_name']) ?></h1>
            <p class="page-subtitle">
                <a href="companies.php">Companies</a> &rsaquo; <?= sanitize($company['company_name']) ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="registrations.php?action=add&company=<?= $company['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Add Registration
            </a>
            <?php if ($canManageCompanies): ?>
                <a href="companies.php?action=edit&id=<?= $company['id'] ?>" class="btn btn-outline">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" data-delete-company-id="<?= $company['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Company Overview -->
    <div class="grid grid-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($registrations) ?></div>
                <div class="stat-label">Registrations</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-folder"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($documents) ?></div>
                <div class="stat-label">Documents</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-comments"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($communications) ?></div>
                <div class="stat-label">Communications</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($contacts) ?></div>
                <div class="stat-label">Contacts</div>
            </div>
        </div>
    </div>

    <!-- Company Details Grid -->
    <div class="grid grid-2 gap-4">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building"></i> Company Information</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td style="width: 150px; font-weight: 500;">Company Name</td>
                        <td><?= sanitize($company['company_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">RC Number</td>
                        <td><?= sanitize($company['rc_number']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Industry</td>
                        <td><?= sanitize($company['industry_name']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Address</td>
                        <td><?= sanitize($company['address']) ?: '-' ?>
                            <?php if ($company['city'] || $company['state']): ?>
                                <br><?= sanitize($company['city']) ?><?= $company['city'] && $company['state'] ? ', ' : '' ?><?= sanitize($company['state']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Country</td>
                        <td><?= sanitize($company['country_name']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Phone</td>
                        <td><?= sanitize($company['phone']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Email</td>
                        <td><?= sanitize($company['email']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Website</td>
                        <td><?= $company['website'] ? '<a href="' . sanitize($company['website']) . '" target="_blank">' . sanitize($company['website']) . '</a>' : '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Client Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Client Details</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td style="width: 150px; font-weight: 500;">Status</td>
                        <td><?= getStatusBadge($company['status']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Subscription</td>
                        <td><?= getStatusBadge($company['subscription_status']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Elite Member</td>
                        <td><?= $company['is_elite'] ? '<span class="badge badge-warning"><i class="fas fa-crown"></i> Elite</span>' : 'No' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Assigned Staff</td>
                        <td><?= sanitize($company['assigned_staff']) ?: 'Unassigned' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Client Since</td>
                        <td><?= formatDate($company['client_since']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Created</td>
                        <td><?= formatDateTime($company['created_at']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">Last Updated</td>
                        <td><?= formatDateTime($company['updated_at']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Contacts -->
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users"></i> Contact Persons</h3>
            <?php if ($canManageContacts): ?>
                <button type="button" data-open-modal="addContactModal" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Contact
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($contacts)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No contacts added yet</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Primary</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?= sanitize($contact['name']) ?></td>
                                <td><?= sanitize($contact['position']) ?: '-' ?></td>
                                <td><?= sanitize($contact['phone']) ?: '-' ?></td>
                                <td><?= sanitize($contact['email']) ?: '-' ?></td>
                                <td><?= $contact['is_primary'] ? '<span class="badge badge-success">Primary</span>' : '' ?></td>
                                <td>
                                    <?php if ($canManageContacts): ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-delete-contact-id="<?= $contact['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Registrations -->
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-alt"></i> Registrations</h3>
            <a href="registrations.php?company=<?= $company['id'] ?>" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($registrations)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>No registrations yet</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Registration Type</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><a href="registrations.php?action=view&id=<?= $reg['id'] ?>"><?= sanitize($reg['registration_type']) ?></a></td>
                                <td><?= getStatusBadge($reg['status']) ?></td>
                                <td>
                                    <?php if ($reg['expiry_date']): ?>
                                        <?= formatDate($reg['expiry_date']) ?>
                                        <?php if (isOverdue($reg['expiry_date'])): ?>
                                            <span class="badge badge-danger">Expired</span>
                                        <?php elseif (isExpiringSoon($reg['expiry_date'])): ?>
                                            <span class="badge badge-warning">Expiring</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><a href="registrations.php?action=view&id=<?= $reg['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Contact Modal -->
    <?php if ($canManageContacts): ?>
        <div class="modal-overlay" id="addContactModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Add Contact Person</h3>
                    <button type="button" class="modal-close" data-close-modal="addContactModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="companies.php?action=add_contact">
                    <?= csrf_field() ?>
                    <input type="hidden" name="company_id" value="<?= $company['id'] ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label required" for="contact_name">Name</label>
                            <input type="text" id="contact_name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_position">Position</label>
                            <input type="text" id="contact_position" name="position" class="form-control">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="contact_phone">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contact_email">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-check">
                                <input type="checkbox" name="is_primary">
                                <span>Set as Primary Contact</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_notes">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-close-modal="addContactModal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Contact</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Companies List View -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Companies</h1>
            <p class="page-subtitle">Manage your client companies</p>
        </div>
        <div class="btn-group">
            <?php if ($canManageCompanies): ?>
                <a href="companies.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Company
                </a>
            <?php endif; ?>
            <button type="button" data-export-table-id="companiesTable" data-export-filename="companies" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" name="search" class="form-control" placeholder="Search companies..."
                           value="<?= sanitize($search) ?>" style="width: 250px;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="prospect" <?= $status === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="churned" <?= $status === 'churned' ? 'selected' : '' ?>>Churned</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="subscription" class="form-control">
                        <option value="">All Subscriptions</option>
                        <option value="free" <?= $subscription === 'free' ? 'selected' : '' ?>>Free</option>
                        <option value="basic" <?= $subscription === 'basic' ? 'selected' : '' ?>>Basic</option>
                        <option value="premium" <?= $subscription === 'premium' ? 'selected' : '' ?>>Premium</option>
                        <option value="elite" <?= $subscription === 'elite' ? 'selected' : '' ?>>Elite</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="assigned" class="form-control">
                        <option value="">All Staff</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $assigned == $s['id'] ? 'selected' : '' ?>>
                                <?= sanitize($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="companies.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <!-- Companies Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="companiesTable">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Industry</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Subscription</th>
                            <th>Assigned</th>
                            <th>Registrations</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-building"></i>
                                        <h3>No companies found</h3>
                                        <p>Add your first company or adjust your filters</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td>
                                        <a href="companies.php?action=view&id=<?= $company['id'] ?>" class="table-link">
                                            <?= sanitize($company['company_name']) ?>
                                        </a>
                                        <?php if ($company['rc_number']): ?>
                                            <br><small class="text-muted"><?= sanitize($company['rc_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= sanitize($company['industry_name']) ?: '-' ?></td>
                                    <td>
                                        <?= sanitize($company['contact_person']) ?: '-' ?>
                                        <?php if ($company['contact_phone']): ?>
                                            <br><small><?= sanitize($company['contact_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getStatusBadge($company['status']) ?></td>
                                    <td><?= getStatusBadge($company['subscription_status']) ?></td>
                                    <td>
                                        <?php if ($company['assigned_staff']): ?>
                                            <div class="flex items-center gap-2">
                                                <?= generateAvatar($company['assigned_staff'], 24) ?>
                                                <span><?= sanitize($company['assigned_staff']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $company['registration_count'] ?>
                                        <?php if ($company['pending_tasks'] > 0): ?>
                                            <br><small class="text-warning"><?= $company['pending_tasks'] ?> pending tasks</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="companies.php?action=view&id=<?= $company['id'] ?>" class="btn btn-sm btn-outline" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($canManageCompanies): ?>
                                                <a href="companies.php?action=edit&id=<?= $company['id'] ?>" class="btn btn-sm btn-outline" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" data-delete-company-id="<?= $company['id'] ?>" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="pagination-item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="pagination-item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="text-center text-muted mt-2">
                    Showing <?= $pagination['offset'] + 1 ?> - <?= min($pagination['offset'] + $perPage, $pagination['total_records']) ?> of <?= $pagination['total_records'] ?> companies
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

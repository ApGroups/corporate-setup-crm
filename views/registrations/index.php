<?php
defined('APP_NAME') or die('Direct access not allowed');

$statusOptions = ['pending', 'awaiting_documents', 'submitted', 'processing', 'approved', 'rejected', 'expired', 'renewal_required'];
?>

<?php if ($action === 'add' || ($action === 'edit' && $registration)): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'New Registration' : 'Edit Registration' ?></h1>
            <p class="page-subtitle">
                <a href="registrations.php">Registrations</a> &rsaquo; <?= $action === 'add' ? 'New' : sanitize($registration['registration_type'] ?? '') ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="registrations.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="regForm" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>

    <form id="regForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="grid grid-2 gap-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Registration Details</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="form-control" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($registration['company_id'] ?? $companyId) == $c['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($c['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="registration_type_id">Registration Type</label>
                        <select id="registration_type_id" name="registration_type_id" class="form-control" required onchange="updateExpiry()">
                            <option value="">Select Type</option>
                            <?php foreach ($regTypes as $rt): ?>
                                <option value="<?= $rt['id'] ?>" <?= ($registration['registration_type_id'] ?? '') == $rt['id'] ? 'selected' : '' ?>
                                        data-validity="<?= $rt['validity_period'] ?>">
                                    <?= sanitize($rt['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <?php foreach ($statusOptions as $s): ?>
                                    <option value="<?= $s ?>" <?= ($registration['status'] ?? 'pending') === $s ? 'selected' : '' ?>>
                                        <?= ucwords(str_replace('_', ' ', $s)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="priority">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="low" <?= ($registration['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($registration['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($registration['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= ($registration['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="assigned_staff_id">Assigned Staff</label>
                        <select id="assigned_staff_id" name="assigned_staff_id" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($registration['assigned_staff_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Timeline & Dates</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="submission_date">Submission Date</label>
                            <input type="date" id="submission_date" name="submission_date" class="form-control"
                                   value="<?= $registration['submission_date'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="expected_completion_date">Expected Completion</label>
                            <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-control"
                                   value="<?= $registration['expected_completion_date'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" class="form-control"
                                   value="<?= $registration['expiry_date'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="next_follow_up_date">Next Follow-up</label>
                            <input type="date" id="next_follow_up_date" name="next_follow_up_date" class="form-control"
                                   value="<?= $registration['next_follow_up_date'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="cost">Cost (NGN)</label>
                            <input type="number" id="cost" name="cost" class="form-control" step="0.01"
                                   value="<?= $registration['cost'] ?? 0 ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_status">Payment Status</label>
                            <select id="payment_status" name="payment_status" class="form-control">
                                <option value="pending" <?= ($registration['payment_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= ($registration['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="waived" <?= ($registration['payment_status'] ?? '') === 'waived' ? 'selected' : '' ?>>Waived</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regulator_reference">Regulator Reference</label>
                        <input type="text" id="regulator_reference" name="regulator_reference" class="form-control"
                               value="<?= sanitize($registration['regulator_reference'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">Document Tracking</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="documents_received">Documents Received</label>
                        <textarea id="documents_received" name="documents_received" class="form-control" rows="3"><?= sanitize($registration['documents_received'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="documents_missing">Documents Missing</label>
                        <textarea id="documents_missing" name="documents_missing" class="form-control" rows="3"><?= sanitize($registration['documents_missing'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="3"><?= sanitize($registration['remarks'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </form>

    <script>
        function updateExpiry() {
            const select = document.getElementById('registration_type_id');
            const option = select.options[select.selectedIndex];
            const validity = option ? option.dataset.validity : 0;
            if (validity && validity > 0) {
                const submissionDate = document.getElementById('submission_date').value || new Date().toISOString().split('T')[0];
                const expiryDate = new Date(submissionDate);
                expiryDate.setDate(expiryDate.getDate() + parseInt(validity));
                document.getElementById('expiry_date').value = expiryDate.toISOString().split('T')[0];
            }
        }
    </script>

<?php elseif ($action === 'view' && $registration): ?>
    <!-- Registration Detail View -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($registration['registration_type']) ?></h1>
            <p class="page-subtitle">
                <a href="registrations.php">Registrations</a> &rsaquo;
                <a href="companies.php?action=view&id=<?= $registration['company_id'] ?>"><?= sanitize($registration['company_name']) ?></a>
            </p>
        </div>
        <div class="btn-group">
            <a href="documents.php?action=upload&registration=<?= $registration['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Upload Document
            </a>
            <a href="registrations.php?action=edit&id=<?= $registration['id'] ?>" class="btn btn-outline">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="card mb-6 <?= $registration['status'] === 'approved' ? 'border-success' : '' ?>">
        <div class="card-body flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="stat-icon <?= $registration['status'] === 'approved' ? 'success' : ($registration['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                    <i class="fas fa-<?= $registration['status'] === 'approved' ? 'check' : 'clock' ?>"></i>
                </div>
                <div>
                    <h3 style="margin: 0;"><?= getStatusBadge($registration['status']) ?></h3>
                    <p class="text-muted" style="margin: 0; font-size: 0.875rem;">
                        Last updated: <?= timeAgo($registration['updated_at']) ?>
                    </p>
                </div>
            </div>
            <div>
                <?= getPriorityBadge($registration['priority']) ?>
            </div>
        </div>
    </div>

    <div class="grid grid-3 gap-4">
        <!-- Details -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Registration Details</h3></div>
            <div class="card-body">
                <table class="table">
                    <tr><td style="width: 140px;">Company</td><td><a href="companies.php?action=view&id=<?= $registration['company_id'] ?>"><?= sanitize($registration['company_name']) ?></a></td></tr>
                    <tr><td>RC Number</td><td><?= sanitize($registration['rc_number']) ?: '-' ?></td></tr>
                    <tr><td>Assigned To</td><td><?= sanitize($registration['assigned_staff']) ?: 'Unassigned' ?></td></tr>
                    <tr><td>Regulator Ref</td><td><?= sanitize($registration['regulator_reference']) ?: '-' ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Timeline</h3></div>
            <div class="card-body">
                <table class="table">
                    <tr><td style="width: 140px;">Submission</td><td><?= formatDate($registration['submission_date']) ?: '-' ?></td></tr>
                    <tr><td>Expected</td><td><?= formatDate($registration['expected_completion_date']) ?: '-' ?></td></tr>
                    <tr><td>Expiry</td><td>
                        <?php if ($registration['expiry_date']): ?>
                            <?= formatDate($registration['expiry_date']) ?>
                            <?php if (isOverdue($registration['expiry_date'])): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif (isExpiringSoon($registration['expiry_date'])): ?>
                                <span class="badge badge-warning">Expiring Soon</span>
                            <?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td></tr>
                    <tr><td>Next Follow-up</td><td><?= formatDate($registration['next_follow_up_date']) ?: '-' ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Financial</h3></div>
            <div class="card-body">
                <table class="table">
                    <tr><td style="width: 140px;">Cost</td><td><?= formatCurrency($registration['cost']) ?></td></tr>
                    <tr><td>Payment</td><td><?= getStatusBadge($registration['payment_status']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-2 gap-4 mt-6">
        <!-- Document Tracking -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Documents</h3></div>
            <div class="card-body">
                <div class="mb-4">
                    <strong>Received:</strong>
                    <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($registration['documents_received']) ?: 'None listed' ?></p>
                </div>
                <div class="mb-4">
                    <strong>Missing:</strong>
                    <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($registration['documents_missing']) ?: 'None listed' ?></p>
                </div>
                <div>
                    <strong>Remarks:</strong>
                    <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($registration['remarks']) ?: 'No remarks' ?></p>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">Activity History</h3></div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted">No history recorded</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?= formatDateTime($h['created_at']) ?></div>
                                    <strong><?= sanitize($h['user_name']) ?></strong>: <?= sanitize($h['action']) ?>
                                    <?php if ($h['remarks']): ?><br><small class="text-muted"><?= sanitize($h['remarks']) ?></small><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- List View -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Registrations</h1>
            <p class="page-subtitle">Manage company registrations and renewals</p>
        </div>
        <div class="btn-group">
            <a href="registrations.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> New Registration</a>
            <button onclick="exportTableToCSV('registrationsTable', 'registrations')" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= sanitize($search) ?>" style="width: 200px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="priority" class="form-control">
                    <option value="">All Priorities</option>
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
                <select name="assigned" class="form-control">
                    <option value="">All Staff</option>
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $assigned == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="registrations.php" class="btn btn-outline">Clear</a>
                <a href="?filter=expiring" class="btn btn-warning btn-sm <?= $filter === 'expiring' ? '' : 'btn-outline' ?>">
                    <i class="fas fa-exclamation-triangle"></i> Expiring Soon
                </a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="registrationsTable">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Registration</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned</th>
                            <th>Expiry</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registrations)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-alt"></i><h3>No registrations found</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
                                    <td><a href="companies.php?action=view&id=<?= $r['company_id'] ?>"><?= sanitize($r['company_name']) ?></a></td>
                                    <td><a href="registrations.php?action=view&id=<?= $r['id'] ?>"><?= sanitize($r['registration_type']) ?></a></td>
                                    <td><?= getStatusBadge($r['status']) ?></td>
                                    <td><?= getPriorityBadge($r['priority']) ?></td>
                                    <td><?= sanitize($r['assigned_staff']) ?: '<span class="text-muted">-</span>' ?></td>
                                    <td>
                                        <?php if ($r['expiry_date']): ?>
                                            <?= formatDate($r['expiry_date']) ?>
                                            <?php if (isOverdue($r['expiry_date'])): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php elseif (isExpiringSoon($r['expiry_date'])): ?>
                                                <span class="badge badge-warning">Expiring</span>
                                            <?php endif; ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="registrations.php?action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                            <a href="registrations.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
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
<?php endif; ?>

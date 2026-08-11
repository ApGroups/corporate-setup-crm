<?php defined('APP_NAME') or die('Direct access not allowed');

$statusOptions = ['draft', 'submitted', 'reviewed', 'approved'];
?>

<?php if ($action === 'submit' || ($action === 'edit' && $report) || ($action === 'new')): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Daily Activity Report</h1>
            <p class="page-subtitle"><a href="daily-reports.php">Daily Reports</a> &rsaquo; <?= isset($report) && $report['id'] ? 'Edit' : 'New' ?></p>
        </div>
        <div class="btn-group">
            <a href="daily-reports.php" class="btn btn-outline">Cancel</a>
        </div>
    </div>

    <form id="reportForm" method="POST" action="daily-reports.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if (!empty($report['id'])): ?>
            <input type="hidden" name="id" value="<?= intval($report['id']) ?>">
        <?php endif; ?>

        <div class="grid grid-2 gap-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Today's Information</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="report_date">Report Date</label>
                            <input type="date" id="report_date" name="report_date" class="form-control" value="<?= $report['report_date'] ?? date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="time_started">Time Started</label>
                            <input type="time" id="time_started" name="time_started" class="form-control" value="<?= $report['time_started'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_finished">Time Finished</label>
                            <input type="time" id="time_finished" name="time_finished" class="form-control" value="<?= $report['time_finished'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Submit Status</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="draft" <?= ($report['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Save as Draft</option>
                            <option value="submitted" <?= ($report['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Submit for Review</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">Activities</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required" for="activities">Today's Activities</label>
                    <textarea id="activities" name="activities" class="form-control" rows="6" required placeholder="Describe the activities you performed today..."><?= sanitize($report['activities'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="grid grid-2 gap-4 mt-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Additional Information</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="projects_worked">Projects Worked On</label>
                        <textarea id="projects_worked" name="projects_worked" class="form-control" rows="3"><?= sanitize($report['projects_worked'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="clients_contacted">Clients Contacted</label>
                        <textarea id="clients_contacted" name="clients_contacted" class="form-control" rows="3"><?= sanitize($report['clients_contacted'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Issues</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="issues_encountered">Issues Encountered</label>
                        <textarea id="issues_encountered" name="issues_encountered" class="form-control" rows="6"><?= sanitize($report['issues_encountered'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Report</button>
        </div>
    </form>

<?php elseif ($action === 'view' && $report): ?>
    <!-- View Report -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Daily Report - <?= formatDate($report['report_date']) ?></h1>
            <p class="page-subtitle"><a href="daily-reports.php">Daily Reports</a> &rsaquo; <?= sanitize($report['user_name']) ?></p>
        </div>
        <div class="btn-group">
            <?php if ($report['user_id'] == $user['id'] && $report['status'] === 'draft'): ?>
                <a href="daily-reports.php?action=edit&id=<?= $report['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
            <?php endif; ?>
            <?php if (!empty($canApproveReports) && $report['status'] === 'submitted'): ?>
                <button onclick="openModal('reviewModal')" class="btn btn-primary"><i class="fas fa-check"></i> Review</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-3 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-user"></i></div>
            <div class="stat-content"><div class="stat-value" style="font-size: 1rem;"><?= sanitize($report['user_name']) ?></div><div class="stat-label"><?= sanitize($report['department_name']) ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-calendar"></i></div>
            <div class="stat-content"><div class="stat-value"><?= formatDate($report['report_date']) ?></div><div class="stat-label"><?= $report['time_started'] ? substr($report['time_started'], 0, 5) : '' ?> - <?= $report['time_finished'] ? substr($report['time_finished'], 0, 5) : '' ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?= $report['status'] === 'approved' ? 'success' : 'warning' ?>"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content"><div class="stat-value"><?= getStatusBadge($report['status']) ?></div><div class="stat-label"><?= $report['reviewer_name'] ? 'Reviewed by ' . $report['reviewer_name'] : '' ?></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Today's Activities</h3></div>
        <div class="card-body">
            <p style="white-space: pre-wrap;"><?= sanitize($report['activities']) ?></p>
        </div>
    </div>

    <div class="grid grid-2 gap-4 mt-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Projects & Clients</h3></div>
            <div class="card-body">
                <div class="mb-4">
                    <strong>Projects Worked On:</strong>
                    <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['projects_worked']) ?: 'None specified' ?></p>
                </div>
                <div>
                    <strong>Clients Contacted:</strong>
                    <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['clients_contacted']) ?: 'None specified' ?></p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Issues Encountered</h3></div>
            <div class="card-body">
                <p class="text-muted" style="white-space: pre-wrap;"><?= sanitize($report['issues_encountered']) ?: 'None reported' ?></p>
            </div>
        </div>
    </div>

    <?php if (in_array($report['status'], ['reviewed', 'approved'], true) && $report['reviewer_comments']): ?>
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Reviewer Comments</h3></div>
        <div class="card-body">
            <p style="white-space: pre-wrap;"><?= sanitize($report['reviewer_comments']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Review Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Review Report</h3>
                <button class="modal-close" onclick="closeModal('reviewModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="daily-reports.php?action=review&id=<?= $report['id'] ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="status">Action</label>
                        <select id="status" name="status" class="form-control">
                            <option value="reviewed">Mark as Reviewed</option>
                            <option value="approved">Approve</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reviewer_comments">Comments</label>
                        <textarea id="reviewer_comments" name="reviewer_comments" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- List View -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Daily Reports</h1>
            <p class="page-subtitle">View and manage daily activity reports</p>
        </div>
        <div class="btn-group">
            <a href="daily-reports.php?action=submit" class="btn btn-primary"><i class="fas fa-plus"></i> Submit Today's Report</a>
        </div>
    </div>

    <!-- Quick Notice -->
    <?php if (!$todayReport): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-circle"></i>
            <div class="alert-content">
                <strong>Reminder:</strong> You haven't submitted your daily report for today.
                <a href="daily-reports.php?action=submit" style="margin-left: 0.5rem;">Submit Now</a>
            </div>
        </div>
    <?php elseif ($todayReport['status'] === 'draft'): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-circle"></i>
            <div class="alert-content">
                <strong>Draft saved:</strong> Today's report is still a draft.
                <a href="daily-reports.php?action=edit&id=<?= intval($todayReport['id']) ?>" style="margin-left: 0.5rem;">Continue Editing</a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle"></i>
            <div class="alert-content">Today's report has been submitted.</div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <?php if (!empty($canViewAllReports)): ?>
                    <select name="user" class="form-control">
                        <option value="">All Staff</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $userId == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucwords($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="from" class="form-control" value="<?= $dateFrom ?>">
                <input type="date" name="to" class="form-control" value="<?= $dateTo ?>">
                <button type="submit" class="btn btn-primary">Filter</button>
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
                            <th>Date</th>
                            <th>Staff</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Activities Summary</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-file-alt"></i><h3>No reports found</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><a href="daily-reports.php?action=view&id=<?= $r['id'] ?>"><?= formatDate($r['report_date']) ?></a></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <?= generateAvatar($r['user_name'], 28) ?>
                                            <?= sanitize($r['user_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= sanitize($r['department_name']) ?></td>
                                    <td><?= getStatusBadge($r['status']) ?></td>
                                    <td class="truncate" style="max-width: 250px;"><?= sanitize(truncateText($r['activities'], 100)) ?></td>
                                    <td>
                                        <a href="daily-reports.php?action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
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

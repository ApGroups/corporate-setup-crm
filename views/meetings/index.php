<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php $canManageMeetings = checkPermission('manage_meetings'); ?>

<?php if ($action === 'add' || ($action === 'edit' && $meeting)): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'Schedule Meeting' : 'Edit Meeting' ?></h1>
            <p class="page-subtitle"><a href="meetings.php">Meetings</a> &rsaquo; <?= $action === 'add' ? 'New' : sanitize($meeting['title']) ?></p>
        </div>
        <div class="btn-group">
            <a href="meetings.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="meetingForm" class="btn btn-primary"><i class="fas fa-save"></i> Save Meeting</button>
        </div>
    </div>

    <form id="meetingForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if (!empty($meeting['id'])): ?>
            <input type="hidden" name="id" value="<?= $meeting['id'] ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required" for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($meeting['title'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="meeting_type">Type</label>
                        <select id="meeting_type" name="meeting_type" class="form-control">
                            <option value="internal" <?= ($meeting['meeting_type'] ?? 'internal') === 'internal' ? 'selected' : '' ?>>Internal</option>
                            <option value="client" <?= ($meeting['meeting_type'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                            <option value="regulator" <?= ($meeting['meeting_type'] ?? '') === 'regulator' ? 'selected' : '' ?>>Regulator</option>
                            <option value="vendor" <?= ($meeting['meeting_type'] ?? '') === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                            <option value="other" <?= ($meeting['meeting_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="form-control">
                            <option value="">Internal / No Company</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($meeting['company_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="meeting_date">Date</label>
                        <input type="date" id="meeting_date" name="meeting_date" class="form-control" value="<?= $meeting['meeting_date'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" value="<?= isset($meeting['start_time']) ? substr($meeting['start_time'], 0, 5) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" value="<?= isset($meeting['end_time']) ? substr($meeting['end_time'], 0, 5) : '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= sanitize($meeting['location'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="scheduled" <?= ($meeting['status'] ?? 'scheduled') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="completed" <?= ($meeting['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= ($meeting['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="rescheduled" <?= ($meeting['status'] ?? '') === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="agenda">Agenda</label>
                    <textarea id="agenda" name="agenda" class="form-control" rows="3"><?= sanitize($meeting['agenda'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"><?= sanitize($meeting['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $meeting): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($meeting['title']) ?></h1>
            <p class="page-subtitle"><a href="meetings.php">Meetings</a> &rsaquo; Details</p>
        </div>
        <div class="btn-group">
            <?php if ($canManageMeetings): ?>
                <a href="meetings.php?action=edit&id=<?= $meeting['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <button type="button" data-delete-meeting-id="<?= $meeting['id'] ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr><td style="width: 180px; font-weight: 500;">Type</td><td><?= sanitize(ucwords(str_replace('_', ' ', $meeting['meeting_type']))) ?></td></tr>
                <tr><td style="font-weight: 500;">Company</td><td><?= $meeting['company_name'] ? sanitize($meeting['company_name']) : 'Internal' ?></td></tr>
                <tr><td style="font-weight: 500;">Organizer</td><td><?= sanitize($meeting['organizer_name']) ?></td></tr>
                <tr><td style="font-weight: 500;">Date</td><td><?= formatDate($meeting['meeting_date']) ?></td></tr>
                <tr><td style="font-weight: 500;">Time</td><td><?= substr($meeting['start_time'], 0, 5) ?><?= $meeting['end_time'] ? ' - ' . substr($meeting['end_time'], 0, 5) : '' ?></td></tr>
                <tr><td style="font-weight: 500;">Status</td><td><?= getStatusBadge($meeting['status']) ?></td></tr>
                <tr><td style="font-weight: 500;">Location</td><td><?= sanitize($meeting['location']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Agenda</td><td><?= sanitize($meeting['agenda']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Notes</td><td><?= sanitize($meeting['notes']) ?: '-' ?></td></tr>
            </table>
        </div>
    </div>

<?php else: ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Meetings</h1>
            <p class="page-subtitle">Schedule and track meetings</p>
        </div>
        <?php if ($canManageMeetings): ?>
            <a href="meetings.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Schedule Meeting</a>
        <?php endif; ?>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="meetings.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>Meeting</th><th>Company</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetings)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h3>No meetings</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($meetings as $m): ?>
                                <tr>
                                    <td><a href="meetings.php?action=view&id=<?= $m['id'] ?>"><?= sanitize($m['title']) ?></a></td>
                                    <td><?= $m['company_name'] ? '<a href="companies.php?action=view&id=' . $m['company_id'] . '">' . sanitize($m['company_name']) . '</a>' : 'Internal' ?></td>
                                    <td><?= formatDate($m['meeting_date']) ?></td>
                                    <td><?= substr($m['start_time'], 0, 5) ?><?= $m['end_time'] ? ' - ' . substr($m['end_time'], 0, 5) : '' ?></td>
                                    <td><?= sanitize($m['location']) ?: '-' ?></td>
                                    <td><?= getStatusBadge($m['status']) ?></td>
                                    <td><a href="meetings.php?action=view&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php $canManageCommunications = checkPermission('manage_communications'); ?>

<?php if ($action === 'add'): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Log Communication</h1>
            <p class="page-subtitle"><a href="communications.php">Communications</a> &rsaquo; New</p>
        </div>
        <div class="btn-group">
            <a href="communications.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="communicationForm" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>

    <form id="communicationForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="form-control" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= sanitize($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="communication_type">Type</label>
                        <select id="communication_type" name="communication_type" class="form-control" required>
                            <option value="email">Email</option>
                            <option value="phone_call">Phone Call</option>
                            <option value="meeting">Meeting</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="letter">Letter</option>
                            <option value="site_visit">Site Visit</option>
                            <option value="video_call">Video Call</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="communication_date">Date/Time</label>
                        <input type="datetime-local" id="communication_date" name="communication_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label required" for="summary">Summary</label>
                    <textarea id="summary" name="summary" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="next_action">Next Action</label>
                    <textarea id="next_action" name="next_action" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="next_action_date">Next Action Date</label>
                        <input type="date" id="next_action_date" name="next_action_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="duration_minutes">Duration (minutes)</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" min="0">
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $communication): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Communication Details</h1>
            <p class="page-subtitle"><a href="communications.php">Communications</a> &rsaquo; View</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr><td style="width: 180px; font-weight: 500;">Company</td><td><a href="companies.php?action=view&id=<?= $communication['company_id'] ?>"><?= sanitize($communication['company_name']) ?></a></td></tr>
                <tr><td style="font-weight: 500;">Type</td><td><?= sanitize(ucwords(str_replace('_', ' ', $communication['communication_type']))) ?></td></tr>
                <tr><td style="font-weight: 500;">Date</td><td><?= formatDateTime($communication['communication_date']) ?></td></tr>
                <tr><td style="font-weight: 500;">Subject</td><td><?= sanitize($communication['subject']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Summary</td><td><?= sanitize($communication['summary']) ?></td></tr>
                <tr><td style="font-weight: 500;">Outcome</td><td><?= sanitize($communication['outcome']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Next Action</td><td><?= sanitize($communication['next_action']) ?: '-' ?></td></tr>
                <tr><td style="font-weight: 500;">Staff</td><td><?= sanitize($communication['staff_name']) ?></td></tr>
            </table>
        </div>
    </div>

<?php else: ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Communications</h1>
            <p class="page-subtitle">Track all client communications</p>
        </div>
        <?php if ($canManageCommunications): ?>
            <a href="communications.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Log Communication</a>
        <?php endif; ?>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <select name="company" class="form-control">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $companyId == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="email" <?= $type === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="phone_call" <?= $type === 'phone_call' ? 'selected' : '' ?>>Phone Call</option>
                    <option value="meeting" <?= $type === 'meeting' ? 'selected' : '' ?>>Meeting</option>
                    <option value="whatsapp" <?= $type === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="site_visit" <?= $type === 'site_visit' ? 'selected' : '' ?>>Site Visit</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Company</th><th>Type</th><th>Summary</th><th>Staff</th><th>Next Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($communications)): ?>
                            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-comments"></i><h3>No communications logged</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($communications as $c): ?>
                                <tr>
                                    <td><a href="communications.php?action=view&id=<?= $c['id'] ?>"><?= formatDateTime($c['communication_date']) ?></a></td>
                                    <td><a href="companies.php?action=view&id=<?= $c['company_id'] ?>"><?= sanitize($c['company_name']) ?></a></td>
                                    <td><span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $c['communication_type'])) ?></span></td>
                                    <td><?= sanitize(truncateText($c['summary'], 50)) ?></td>
                                    <td><?= generateAvatar($c['staff_name'], 24) ?> <?= sanitize($c['staff_name']) ?></td>
                                    <td><?= $c['next_action'] ? truncateText($c['next_action'], 30) . '<br><small>' . formatDate($c['next_action_date']) . '</small>' : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

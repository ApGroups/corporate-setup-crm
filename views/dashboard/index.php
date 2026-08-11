<?php
/**
 * Dashboard View
 * Corporate Setup Limited - Internal CRM
 */

defined('APP_NAME') or die('Direct access not allowed');
?>

<?php if ($user['must_change_password'] ?? false): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="alert-content">
            <strong>Security Notice:</strong> Please change your password immediately.
            <a href="change-password.php" style="margin-left: 0.5rem;">Change Password</a>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card mb-6">
    <div class="card-body" style="padding: 1rem;">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="text-muted">Quick Actions:</span>
                <a href="companies.php?action=add" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Company
                </a>
                <a href="registrations.php?action=add" class="btn btn-secondary btn-sm">
                    <i class="fas fa-plus"></i> New Registration
                </a>
                <a href="tasks.php?action=add" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Create Task
                </a>
                <a href="daily-reports.php?action=submit" class="btn btn-outline btn-sm">
                    <i class="fas fa-file-lines"></i> Submit Report
                </a>
            </div>
            <div class="flex gap-2">
                <a href="reports.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-4 grid-3-md gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalCompanies) ?></div>
            <div class="stat-label">Total Companies</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <?= $newThisMonth['companies'] ?> new this month
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($activeClients) ?></div>
            <div class="stat-label">Active Clients</div>
            <?php if ($prospectClients > 0): ?>
                <div class="stat-change">
                    <i class="fas fa-user-plus"></i>
                    <?= $prospectClients ?> prospects
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($pendingRegistrations) ?></div>
            <div class="stat-label">Pending Registrations</div>
            <div class="stat-change negative">
                <i class="fas fa-exclamation-circle"></i>
                <?= $expiringRegistrations ?> expiring soon
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($completedRegistrations) ?></div>
            <div class="stat-label">Completed</div>
            <div class="stat-change positive">
                <i class="fas fa-check"></i>
                <?= $newThisMonth['registrations'] ?> this month
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="grid grid-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon secondary">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($pendingTasks) ?></div>
            <div class="stat-label">Pending Tasks</div>
            <div class="stat-change">
                <?= $myPendingTasks ?> assigned to you
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-calendar-times"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($expiredRegistrations) ?></div>
            <div class="stat-label">Expired</div>
            <div class="stat-change negative">
                <i class="fas fa-exclamation-triangle"></i>
                Requires attention
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($completedTasks) ?></div>
            <div class="stat-label">Tasks Completed (30 days)</div>
            <div class="stat-change positive">
                <i class="fas fa-trophy"></i>
                Great progress!
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= count($upcomingMeetings) ?></div>
            <div class="stat-label">Upcoming Meetings</div>
            <div class="stat-change">
                This week
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-3 gap-4">
    <!-- My Tasks -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tasks"></i> My Tasks</h3>
            <a href="tasks.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($myTasks)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No pending tasks. Great job!</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <tbody>
                            <?php foreach ($myTasks as $task): ?>
                                <tr>
                                    <td style="width: 40px;">
                                        <?= getPriorityBadge($task['priority']) ?>
                                    </td>
                                    <td>
                                        <a href="tasks.php?action=view&id=<?= $task['id'] ?>" class="table-link">
                                            <?= sanitize($task['title']) ?>
                                        </a>
                                        <?php if ($task['company_name']): ?>
                                            <br><small class="text-muted"><?= sanitize($task['company_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width: 100px; text-align: right;">
                                        <small class="text-muted">
                                            <?php if ($task['due_date']): ?>
                                                <?php if (isOverdue($task['due_date'])): ?>
                                                    <span class="text-danger">Overdue</span>
                                                <?php elseif (isExpiringSoon($task['due_date'], 3)): ?>
                                                    <span class="text-warning">Soon</span>
                                                <?php else: ?>
                                                    <?= formatDate($task['due_date'], 'd M') ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Meetings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Meetings</h3>
            <a href="meetings.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($upcomingMeetings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p>No upcoming meetings</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <tbody>
                            <?php foreach ($upcomingMeetings as $meeting): ?>
                                <tr>
                                    <td>
                                        <a href="meetings.php?action=view&id=<?= $meeting['id'] ?>" class="table-link">
                                            <?= sanitize($meeting['title']) ?>
                                        </a>
                                        <?php if ($meeting['company_name']): ?>
                                            <br><small class="text-muted"><?= sanitize($meeting['company_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?= $meeting['formatted_date'] ?><br>
                                        <small><?= substr($meeting['start_time'], 0, 5) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expiring Registrations -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</h3>
            <a href="registrations.php?filter=expiring" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($expiringSoon)): ?>
                <div class="empty-state">
                    <i class="fas fa-check"></i>
                    <p>No registrations expiring soon</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <tbody>
                            <?php foreach ($expiringSoon as $reg): ?>
                                <tr>
                                    <td>
                                        <a href="registrations.php?action=view&id=<?= $reg['id'] ?>" class="table-link">
                                            <?= sanitize($reg['registration_type']) ?>
                                        </a>
                                        <br><small class="text-muted"><?= sanitize($reg['company_name']) ?></small>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="badge badge-<?= $reg['days_remaining'] <= 7 ? 'danger' : 'warning' ?>">
                                            <?= $reg['days_remaining'] ?> days
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts and Activity -->
<div class="grid grid-2 gap-4 mt-6">
    <!-- Registration Status Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Registration Status</h3>
        </div>
        <div class="card-body">
            <div style="height: 250px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i> Recent Activity</h3>
            <a href="activity-log.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body recent-activity">
            <?php if (empty($recentActivities)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-date"><?= timeAgo($activity['created_at']) ?></div>
                                <strong><?= sanitize($activity['user_name']) ?></strong>
                                <?= sanitize($activity['description']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Monthly Registrations Chart -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Registrations Overview</h3>
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Registration Status Chart
    const statusData = {
        labels: <?= json_encode(array_column($registrationStatuses, 'status')) ?>,
        counts: <?= json_encode(array_column($registrationStatuses, 'count')) ?>
    };

    createDoughnutChart('statusChart', statusData.counts, statusData.labels);

    // Monthly Registrations Chart
    const monthlyData = {
        labels: <?= json_encode(array_column($monthlyRegistrations, 'month')) ?>,
        counts: <?= json_encode(array_column($monthlyRegistrations, 'count')) ?>
    };

    createBarChart('monthlyChart', monthlyData.counts, monthlyData.labels);
</script>

<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<?php if ($action === 'add' || ($action === 'edit' && $task)): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'add' ? 'New Task' : 'Edit Task' ?></h1>
            <p class="page-subtitle"><a href="tasks.php">Tasks</a> &rsaquo; <?= $action === 'add' ? 'New' : sanitize($task['title'] ?? '') ?></p>
        </div>
        <div class="btn-group">
            <a href="tasks.php" class="btn btn-outline">Cancel</a>
            <button type="submit" form="taskForm" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>

    <form id="taskForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($action === 'edit' && !empty($task['id'])): ?>
            <input type="hidden" name="id" value="<?= intval($task['id']) ?>">
        <?php endif; ?>

        <div class="grid grid-2 gap-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Task Details</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($task['title'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?= sanitize($task['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="task_type">Type</label>
                            <select id="task_type" name="task_type" class="form-control">
                                <?php $types = ['general', 'registration', 'compliance', 'meeting', 'client', 'admin', 'other']; ?>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t ?>" <?= ($task['task_type'] ?? 'general') === $t ? 'selected' : '' ?>><?= ucwords($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="priority">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($task['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= ($task['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="assigned_to">Assigned To</label>
                        <?php
                        $selectedAssignees = $taskAssigneeIds ?? [];
                        if (empty($selectedAssignees)) {
                            $selectedAssignees = [!empty($task['assigned_to']) ? (int)$task['assigned_to'] : (int)$user['id']];
                        }
                        ?>
                        <select id="assigned_to" name="assigned_to[]" class="form-control" multiple required size="6">
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= in_array((int)$s['id'], $selectedAssignees, true) ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple staff.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tags">Tags</label>
                        <input type="text" id="tags" name="tags" class="form-control" value="<?= sanitize($task['tags'] ?? '') ?>" placeholder="Comma separated tags">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Scheduling</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="pending" <?= ($task['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= ($task['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($task['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="on_hold" <?= ($task['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="progress">Progress</label>
                            <select id="progress" name="progress" class="form-control">
                                <?php for ($i = 0; $i <= 100; $i += 10): ?>
                                    <option value="<?= $i ?>" <?= ($task['progress'] ?? 0) == $i ? 'selected' : '' ?>><?= $i ?>%</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" class="form-control" value="<?= $task['due_date'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="due_time">Due Time</label>
                            <input type="time" id="due_time" name="due_time" class="form-control" value="<?= $task['due_time'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="frequency">Frequency</label>
                        <select id="frequency" name="frequency" class="form-control">
                            <option value="once" <?= ($task['frequency'] ?? 'once') === 'once' ? 'selected' : '' ?>>Once</option>
                            <option value="daily" <?= ($task['frequency'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($task['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($task['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarterly" <?= ($task['frequency'] ?? '') === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                            <option value="yearly" <?= ($task['frequency'] ?? '') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="related_company_id">Related Company</label>
                        <select id="related_company_id" name="related_company_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($task['related_company_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php elseif ($action === 'view' && $task): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= sanitize($task['title']) ?></h1>
            <p class="page-subtitle"><a href="tasks.php">Tasks</a> &rsaquo; Details</p>
        </div>
        <div class="btn-group">
            <a href="tasks.php?action=edit&id=<?= $task['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
            <button type="button" data-delete-task-id="<?= $task['id'] ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>

    <!-- Progress Card -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex justify-between items-center mb-2">
                <span class="font-bold"><?= $task['progress'] ?>% Complete</span>
                <span><?= getStatusBadge($task['status']) ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar <?= $task['progress'] >= 100 ? 'success' : 'primary' ?>" style="width: <?= $task['progress'] ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-2 gap-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body">
                <table class="table">
                    <tr><td style="width: 140px;">Type</td><td><?= ucwords($task['task_type']) ?></td></tr>
                    <tr><td>Priority</td><td><?= getPriorityBadge($task['priority']) ?></td></tr>
                    <tr><td>Assigned To</td><td><?= sanitize($task['assigned_to_names'] ?? $task['assigned_to_name'] ?? '-') ?></td></tr>
                    <tr><td>Created By</td><td><?= sanitize($task['created_by_name']) ?: 'N/A' ?></td></tr>
                    <tr><td>Company</td><td><?= $task['company_name'] ? '<a href="companies.php?action=view&id=' . $task['related_company_id'] . '">' . sanitize($task['company_name']) . '</a>' : '-' ?></td></tr>
                    <tr><td>Frequency</td><td><?= ucwords($task['frequency']) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Timeline</h3></div>
            <div class="card-body">
                <table class="table">
                    <tr><td style="width: 140px;">Due Date</td><td>
                        <?php if ($task['due_date']): ?>
                            <?= formatDate($task['due_date']) ?> <?= $task['due_time'] ? substr($task['due_time'], 0, 5) : '' ?>
                            <?php if (isOverdue($task['due_date']) && $task['status'] !== 'completed'): ?>
                                <span class="badge badge-danger">Overdue</span>
                            <?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td></tr>
                    <tr><td>Created</td><td><?= formatDateTime($task['created_at']) ?></td></tr>
                    <tr><td>Updated</td><td><?= formatDateTime($task['updated_at']) ?></td></tr>
                    <tr><td>Completed</td><td><?= $task['completed_at'] ? formatDateTime($task['completed_at']) : '-' ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <?php if ($task['description']): ?>
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Description</h3></div>
        <div class="card-body">
            <p style="white-space: pre-wrap;"><?= sanitize($task['description']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comments -->
    <div class="card mt-4">
        <div class="card-header"><h3 class="card-title">Comments</h3></div>
        <div class="card-body">
            <?php if (empty($comments)): ?>
                <p class="text-muted">No comments yet</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($comments as $c): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="flex justify-between items-center">
                                    <strong><?= sanitize($c['user_name']) ?></strong>
                                    <small class="text-muted"><?= timeAgo($c['created_at']) ?></small>
                                </div>
                                <p style="white-space: pre-wrap; margin-top: 0.5rem;"><?= sanitize($c['comment']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="tasks.php?action=add_comment&id=<?= $task['id'] ?>" class="mt-4">
                <?= csrf_field() ?>
                <div class="form-group">
                    <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-comment"></i> Add Comment</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- List View -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Tasks</h1>
            <p class="page-subtitle">Manage tasks and assignments</p>
        </div>
        <div class="btn-group">
            <a href="tasks.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> New Task</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-4 gap-4 mb-6">
        <?php
        $myPending = $db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_assignees ta ON ta.task_id = t.id WHERE ta.user_id = ? AND t.status IN ('pending', 'in_progress')",
            [$user['id']]
        )->fetchColumn();
        $overdue = $db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_assignees ta ON ta.task_id = t.id WHERE ta.user_id = ? AND t.due_date < CURDATE() AND t.status NOT IN ('completed', 'cancelled')",
            [$user['id']]
        )->fetchColumn();
        $completedToday = $db->query(
            "SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_assignees ta ON ta.task_id = t.id WHERE ta.user_id = ? AND t.status = 'completed' AND DATE(t.completed_at) = CURDATE()",
            [$user['id']]
        )->fetchColumn();
        $teamPending = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')")->fetchColumn();
        ?>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-list"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $myPending ?></div><div class="stat-label">My Pending</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $overdue ?></div><div class="stat-label">Overdue</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $completedToday ?></div><div class="stat-label">Completed Today</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-users"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $teamPending ?></div><div class="stat-label">Team Total</div></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="<?= sanitize($search) ?>" style="width: 200px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <select name="priority" class="form-control">
                    <option value="">All Priorities</option>
                    <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
                <select name="assigned" class="form-control">
                    <option value="">All Staff</option>
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $assigned == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="tasks.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="tasksTable">
                    <thead>
                        <tr><th>Task</th><th>Company</th><th>Priority</th><th>Status</th><th>Due Date</th><th>Progress</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-check-circle"></i><h3>No tasks found</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr>
                                    <td>
                                        <a href="tasks.php?action=view&id=<?= $t['id'] ?>" class="table-link"><?= sanitize($t['title']) ?></a>
                                        <br><small class="text-muted">Assigned to: <?= sanitize($t['assigned_to_names'] ?? $t['assigned_to_name'] ?? '-') ?></small>
                                    </td>
                                    <td><?= $t['company_name'] ? sanitize($t['company_name']) : '-' ?></td>
                                    <td><?= getPriorityBadge($t['priority']) ?></td>
                                    <td><?= getStatusBadge($t['status']) ?></td>
                                    <td>
                                        <?php if ($t['due_date']): ?>
                                            <?= formatDate($t['due_date']) ?>
                                            <?php if (isOverdue($t['due_date']) && !in_array($t['status'], ['completed', 'cancelled'])): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php endif; ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 80px; height: 6px;">
                                            <div class="progress-bar <?= $t['progress'] >= 100 ? 'success' : 'primary' ?>" style="width: <?= $t['progress'] ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="tasks.php?action=view&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                            <a href="tasks.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
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

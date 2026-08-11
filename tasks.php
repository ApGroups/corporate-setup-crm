<?php
/**
 * Tasks Management
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/helpers/validators.php';
require_once __DIR__ . '/helpers/formatters.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
requirePermission('view_tasks');

$pageTitle = 'Tasks';
$activeNav = 'tasks';

$db = Database::getInstance();
$user = getCurrentUser();
$canManageTasks = checkPermission('manage_tasks');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_POST['task_id'] ?? $_GET['id'] ?? 0);

/**
 * Normalize assignee IDs from POST (array or single value).
 * @return int[]
 */
function parseAssigneeIds($raw): array
{
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    $ids = [];
    foreach ($raw as $val) {
        $uid = intval($val);
        if ($uid > 0) {
            $ids[$uid] = $uid;
        }
    }
    return array_values($ids);
}

/**
 * Replace task_assignees rows and sync tasks.assigned_to to first assignee.
 */
function syncTaskAssignees(Database $db, int $taskId, array $assigneeIds): void
{
    $db->delete('task_assignees', 'task_id = :task_id', ['task_id' => $taskId]);
    foreach ($assigneeIds as $uid) {
        $db->insert('task_assignees', [
            'task_id' => $taskId,
            'user_id' => $uid,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    $primary = $assigneeIds[0] ?? null;
    if ($primary) {
        $db->update('tasks', ['assigned_to' => $primary], 'id = :id', ['id' => $taskId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($action) {
        case 'save':
            if (!$canManageTasks) {
                echo json_encode(['success' => false, 'error' => 'You are not authorized to manage tasks']);
                exit;
            }

            $assigneeIds = parseAssigneeIds($_POST['assigned_to'] ?? []);
            if (empty($assigneeIds)) {
                echo json_encode(['success' => false, 'error' => 'At least one assignee is required']);
                exit;
            }

            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') {
                echo json_encode(['success' => false, 'error' => 'Title is required']);
                exit;
            }

            $taskData = [
                'title' => sanitize($title),
                'description' => sanitize($_POST['description'] ?? ''),
                'task_type' => sanitize($_POST['task_type'] ?? 'general'),
                'related_company_id' => intval($_POST['related_company_id'] ?? 0) ?: null,
                'related_registration_id' => intval($_POST['related_registration_id'] ?? 0) ?: null,
                'assigned_to' => $assigneeIds[0],
                'priority' => sanitize($_POST['priority'] ?? 'medium'),
                'status' => sanitize($_POST['status'] ?? 'pending'),
                'due_date' => $_POST['due_date'] ?: null,
                'due_time' => $_POST['due_time'] ?: null,
                'frequency' => sanitize($_POST['frequency'] ?? 'once'),
                'tags' => sanitize($_POST['tags'] ?? ''),
                'progress' => intval($_POST['progress'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($taskData['status'] === 'completed') {
                $taskData['completed_at'] = date('Y-m-d H:i:s');
            } else {
                $taskData['completed_at'] = null;
            }

            try {
                $db->beginTransaction();

                $previousAssignees = [];
                if ($id > 0) {
                    $previousAssignees = array_map('intval', array_column(
                        $db->fetchAll("SELECT user_id FROM task_assignees WHERE task_id = ?", [$id]),
                        'user_id'
                    ));
                    $db->update('tasks', $taskData, 'id = :id', ['id' => $id]);
                    syncTaskAssignees($db, $id, $assigneeIds);
                    logActivity($user['id'], 'update', 'Updated task: ' . $taskData['title'], 'tasks', $id);
                    $taskId = $id;
                    $message = 'Task updated successfully';
                } else {
                    $taskData['assigned_by'] = $user['id'];
                    $taskData['created_at'] = date('Y-m-d H:i:s');
                    $taskId = (int)$db->insert('tasks', $taskData);
                    syncTaskAssignees($db, $taskId, $assigneeIds);
                    logActivity($user['id'], 'create', 'Created task: ' . $taskData['title'], 'tasks', $taskId);
                    $message = 'Task created successfully';
                }

                $db->commit();

                $notifyTargets = array_diff($assigneeIds, [$user['id']]);
                if ($id > 0) {
                    $notifyTargets = array_diff($notifyTargets, $previousAssignees);
                }
                foreach ($notifyTargets as $assigneeId) {
                    $db->insert('notifications', [
                        'user_id' => $assigneeId,
                        'type' => 'task_assigned',
                        'title' => 'New Task Assigned',
                        'message' => 'You have been assigned a task: ' . $taskData['title'],
                        'link' => 'tasks.php?action=view&id=' . $taskId,
                        'related_type' => 'tasks',
                        'related_id' => $taskId,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'id' => $taskId,
                    'redirect' => 'tasks.php?action=view&id=' . $taskId
                ]);
            } catch (Exception $e) {
                if ($db->getConnection()->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Task save error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save task: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
            if (!$canManageTasks) {
                echo json_encode(['success' => false, 'error' => 'You are not authorized to delete tasks']);
                exit;
            }

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
                exit;
            }

            try {
                $task = $db->fetch("SELECT title FROM tasks WHERE id = ?", [$id]);
                if (!$task) {
                    echo json_encode(['success' => false, 'error' => 'Task not found']);
                    exit;
                }

                $db->delete('tasks', 'id = :id', ['id' => $id]);
                logActivity($user['id'], 'delete', 'Deleted task: ' . $task['title'], 'tasks', $id);
                echo json_encode(['success' => true, 'message' => 'Task deleted', 'reload' => true]);
            } catch (Exception $e) {
                error_log('Task delete error: ' . $e->getMessage());
                $errorMessage = APP_ENV !== 'production'
                    ? ('Failed to delete task: ' . $e->getMessage())
                    : 'Failed to delete task';
                echo json_encode(['success' => false, 'error' => $errorMessage]);
            }
            exit;

        case 'add_comment':
            $comment = sanitize($_POST['comment'] ?? '');
            if ($id <= 0 || $comment === '') {
                echo json_encode(['success' => false, 'error' => 'Comment is required']);
                exit;
            }
            try {
                $db->insert('task_comments', [
                    'task_id' => $id,
                    'user_id' => $user['id'],
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                logActivity($user['id'], 'comment', 'Commented on task', 'tasks', $id);
                echo json_encode(['success' => true, 'message' => 'Comment added', 'reload' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
            }
            exit;

        case 'update_progress':
            $progress = intval($_POST['progress']);
            try {
                $db->update('tasks', ['progress' => $progress, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
                echo json_encode(['success' => true, 'progress' => $progress]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to update progress']);
            }
            exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

if (in_array($action, ['add', 'edit'], true) && !$canManageTasks) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

// Filter parameters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned = array_key_exists('assigned', $_GET) ? intval($_GET['assigned'] ?: 0) : (int)$user['id'];
$type = $_GET['type'] ?? '';
$search = sanitize($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 't.status = :status';
    $params['status'] = $status;
}

if ($priority) {
    $where[] = 't.priority = :priority';
    $params['priority'] = $priority;
}

if ($assigned > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM task_assignees ta_f WHERE ta_f.task_id = t.id AND ta_f.user_id = :assigned)';
    $params['assigned'] = $assigned;
}

if ($type) {
    $where[] = 't.task_type = :type';
    $params['type'] = $type;
}

if ($search) {
    $where[] = '(t.title LIKE :search OR c.company_name LIKE :search)';
    $params['search'] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$totalCount = $db->query(
    "SELECT COUNT(DISTINCT t.id) FROM tasks t LEFT JOIN companies c ON t.related_company_id = c.id WHERE $whereClause",
    $params
)->fetchColumn();

$perPage = 20;
$pagination = paginate($totalCount, $page, $perPage);

$tasks = $db->fetchAll(
    "SELECT t.*, c.company_name, uc.name as created_by_name,
            (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
             FROM task_assignees ta2
             JOIN users u2 ON u2.id = ta2.user_id
             WHERE ta2.task_id = t.id) as assigned_to_names
     FROM tasks t
     LEFT JOIN companies c ON t.related_company_id = c.id
     LEFT JOIN users uc ON t.assigned_by = uc.id
     WHERE $whereClause
     ORDER BY FIELD(t.status, 'pending', 'in_progress', 'completed', 'cancelled'),
              FIELD(t.priority, 'urgent', 'high', 'medium', 'low'),
              t.due_date ASC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
$registrations = $db->fetchAll("SELECT r.id, rt.name, c.company_name FROM registrations r JOIN registration_types rt ON r.registration_type_id = rt.id JOIN companies c ON r.company_id = c.id WHERE r.status IN ('pending', 'submitted', 'processing')");

$task = null;
$comments = [];
$attachments = [];
$taskAssigneeIds = [];

if ($id > 0) {
    $task = $db->fetch(
        "SELECT t.*, c.company_name, uc.name as created_by_name,
                (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
                 FROM task_assignees ta2
                 JOIN users u2 ON u2.id = ta2.user_id
                 WHERE ta2.task_id = t.id) as assigned_to_names
         FROM tasks t
         LEFT JOIN companies c ON t.related_company_id = c.id
         LEFT JOIN users uc ON t.assigned_by = uc.id
         WHERE t.id = ?",
        [$id]
    );

    if ($task) {
        $taskAssigneeIds = array_map('intval', array_column(
            $db->fetchAll("SELECT user_id FROM task_assignees WHERE task_id = ?", [$id]),
            'user_id'
        ));
        if (empty($taskAssigneeIds) && !empty($task['assigned_to'])) {
            $taskAssigneeIds = [(int)$task['assigned_to']];
        }
        $comments = $db->fetchAll(
            "SELECT tc.*, u.name as user_name FROM task_comments tc LEFT JOIN users u ON tc.user_id = u.id WHERE tc.task_id = ? ORDER BY tc.created_at DESC",
            [$id]
        );
        $attachments = $db->fetchAll("SELECT * FROM task_attachments WHERE task_id = ? ORDER BY created_at DESC", [$id]);
    }
}

ob_start();
include 'views/tasks/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

<?php
/**
 * Tasks Management
 * Corporate Setup Limited - Internal CRM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Helper Functions
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/helpers/validators.php';
require_once __DIR__ . '/helpers/formatters.php';

// General Functions
require_once __DIR__ . '/includes/functions.php';

// Authentication
require_once __DIR__ . '/includes/auth.php';

// User must be logged in
requireLogin();

$pageTitle = 'Tasks';
$activeNav = 'tasks';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_POST['task_id'] ?? $_GET['id'] ?? 0);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($action) {
        case 'save':
            $taskData = [
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description'] ?? ''),
                'task_type' => sanitize($_POST['task_type'] ?? 'general'),
                'related_company_id' => intval($_POST['related_company_id'] ?? 0) ?: null,
                'related_registration_id' => intval($_POST['related_registration_id'] ?? 0) ?: null,
                'assigned_to' => intval($_POST['assigned_to']),
                'priority' => sanitize($_POST['priority'] ?? 'medium'),
                'status' => sanitize($_POST['status'] ?? 'pending'),
                'due_date' => $_POST['due_date'] ?: null,
                'due_time' => $_POST['due_time'] ?: null,
                'frequency' => sanitize($_POST['frequency'] ?? 'once'),
                'tags' => sanitize($_POST['tags'] ?? ''),
                'progress' => intval($_POST['progress'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($taskData['status'] === 'completed' && empty($taskData['completed_at'])) {
                $taskData['completed_at'] = date('Y-m-d H:i:s');
            }

            try {
                if ($id > 0) {
                    $db->update('tasks', $taskData, 'id = :id', ['id' => $id]);
                    logActivity($user['id'], 'update', 'Updated task: ' . $taskData['title'], 'tasks', $id);
                    echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
                } else {
                    $taskData['assigned_by'] = $user['id'];
                    $taskData['created_at'] = date('Y-m-d H:i:s');
                    $newId = $db->insert('tasks', $taskData);
                    logActivity($user['id'], 'create', 'Created task: ' . $taskData['title'], 'tasks', $newId);

                    // Send notification to assigned user
                    if ($taskData['assigned_to'] != $user['id']) {
                        $db->insert('notifications', [
                            'user_id' => $taskData['assigned_to'],
                            'type' => 'task_assigned',
                            'title' => 'New Task Assigned',
                            'message' => 'You have been assigned a new task: ' . $taskData['title'],
                            'link' => '/tasks.php?action=view&id=' . $newId,
                            'related_type' => 'tasks',
                            'related_id' => $newId,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }

                    echo json_encode(['success' => true, 'message' => 'Task created successfully', 'id' => $newId]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to save task: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
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
                echo json_encode(['success' => true, 'message' => 'Task deleted']);
            } catch (Exception $e) {
                error_log('Task delete error: ' . $e->getMessage());
                $errorMessage = APP_ENV !== 'production'
                    ? ('Failed to delete task: ' . $e->getMessage())
                    : 'Failed to delete task';
                echo json_encode(['success' => false, 'error' => $errorMessage]);
            }
            exit;

        case 'add_comment':
            $comment = sanitize($_POST['comment']);
            try {
                $db->insert('task_comments', [
                    'task_id' => $id,
                    'user_id' => $user['id'],
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                echo json_encode(['success' => true, 'message' => 'Comment added']);
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
}

// Filter parameters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned = intval($_GET['assigned'] ?? $user['id']);
$type = $_GET['type'] ?? '';
$search = sanitize($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);

// Build query
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

if ($assigned) {
    $where[] = 't.assigned_to = :assigned';
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
$totalCount = $db->query("SELECT COUNT(*) FROM tasks t LEFT JOIN companies c ON t.related_company_id = c.id WHERE $whereClause", $params)->fetchColumn();

$perPage = 20;
$pagination = paginate($totalCount, $page, $perPage);

// Get tasks
$tasks = $db->fetchAll(
    "SELECT t.*, c.company_name, u.name as assigned_to_name, uc.name as created_by_name
     FROM tasks t
     LEFT JOIN companies c ON t.related_company_id = c.id
     LEFT JOIN users u ON t.assigned_to = u.id
     LEFT JOIN users uc ON t.assigned_by = uc.id
     WHERE $whereClause
     ORDER BY FIELD(t.status, 'pending', 'in_progress', 'completed', 'cancelled'),
              FIELD(t.priority, 'urgent', 'high', 'medium', 'low'),
              t.due_date ASC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

// Get lookups
$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
$registrations = $db->fetchAll("SELECT r.id, rt.name, c.company_name FROM registrations r JOIN registration_types rt ON r.registration_type_id = rt.id JOIN companies c ON r.company_id = c.id WHERE r.status IN ('pending', 'submitted', 'processing')");

// Single task
$task = null;
$comments = [];
$attachments = [];

if ($id > 0) {
    $task = $db->fetch(
        "SELECT t.*, c.company_name, u.name as assigned_to_name, uc.name as created_by_name
         FROM tasks t
         LEFT JOIN companies c ON t.related_company_id = c.id
         LEFT JOIN users u ON t.assigned_to = u.id
         LEFT JOIN users uc ON t.assigned_by = uc.id
         WHERE t.id = ?",
        [$id]
    );

    if ($task) {
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

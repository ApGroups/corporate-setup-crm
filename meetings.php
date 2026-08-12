<?php
/**
 * Meetings
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
requirePermission('view_meetings');
$pageTitle = 'Meetings';
$activeNav = 'meetings';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add', 'edit'], true) && !checkPermission('manage_meetings')) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if (!checkPermission('manage_meetings')) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to manage meetings']);
        exit;
    }

    switch ($action) {
        case 'save':
            $meetingData = [
                'title' => sanitize($_POST['title'] ?? ''),
                'meeting_type' => sanitize($_POST['meeting_type'] ?? 'internal'),
                'company_id' => intval($_POST['company_id'] ?? 0) ?: null,
                'organizer_id' => $user['id'],
                'location' => sanitize($_POST['location'] ?? ''),
                'meeting_date' => $_POST['meeting_date'] ?? null,
                'start_time' => $_POST['start_time'] ?? null,
                'end_time' => $_POST['end_time'] ?: null,
                'agenda' => sanitize($_POST['agenda'] ?? ''),
                'minutes' => sanitize($_POST['minutes'] ?? ''),
                'action_items' => sanitize($_POST['action_items'] ?? ''),
                'status' => sanitize($_POST['status'] ?? 'scheduled'),
                'meeting_link' => sanitize($_POST['meeting_link'] ?? ''),
                'notes' => sanitize($_POST['notes'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (empty($meetingData['title']) || empty($meetingData['meeting_date']) || empty($meetingData['start_time'])) {
                echo json_encode(['success' => false, 'error' => 'Title, meeting date and start time are required']);
                exit;
            }

            try {
                if ($id > 0) {
                    $db->update('meetings', $meetingData, 'id = :id', ['id' => $id]);
                    logActivity($user['id'], 'update', 'Updated meeting: ' . $meetingData['title'], 'meetings', $id);
                    createOperationNotification((int) $user['id'], 'Meeting updated', 'Meeting updated successfully.', 'success', 'meetings.php?action=view&id=' . $id, [
                        'related_type' => 'meetings',
                        'related_id' => $id
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Meeting updated successfully', 'redirect' => 'meetings.php?action=view&id=' . $id]);
                } else {
                    $meetingData['created_at'] = date('Y-m-d H:i:s');
                    $newId = $db->insert('meetings', $meetingData);
                    logActivity($user['id'], 'create', 'Created meeting: ' . $meetingData['title'], 'meetings', $newId);
                    createOperationNotification((int) $user['id'], 'Meeting created', 'Meeting created successfully.', 'success', 'meetings.php?action=view&id=' . $newId, [
                        'related_type' => 'meetings',
                        'related_id' => $newId
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Meeting created successfully', 'redirect' => 'meetings.php?action=view&id=' . $newId]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to save meeting: ' . $e->getMessage()]);
            }
            exit;

        case 'delete':
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid meeting ID']);
                exit;
            }

            try {
                $meeting = $db->fetch('SELECT title FROM meetings WHERE id = ?', [$id]);
                if (!$meeting) {
                    echo json_encode(['success' => false, 'error' => 'Meeting not found']);
                    exit;
                }

                $db->delete('meetings', 'id = :id', ['id' => $id]);
                logActivity($user['id'], 'delete', 'Deleted meeting: ' . $meeting['title'], 'meetings', $id);
                createOperationNotification((int) $user['id'], 'Meeting deleted', 'Meeting deleted successfully.', 'success', 'meetings.php', [
                    'related_type' => 'meetings',
                    'related_id' => $id
                ]);
                echo json_encode(['success' => true, 'message' => 'Meeting deleted successfully', 'reload' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to delete meeting: ' . $e->getMessage()]);
            }
            exit;
    }
}

$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 'm.status = :status';
    $params['status'] = $status;
}

$whereClause = implode(' AND ', $where);
$total = $db->query("SELECT COUNT(*) FROM meetings m WHERE $whereClause", $params)->fetchColumn();
$pagination = paginate($total, $page, 20);

$meetings = $db->fetchAll(
    "SELECT m.*, c.company_name, u.name as organizer_name
     FROM meetings m
     LEFT JOIN companies c ON m.company_id = c.id
     LEFT JOIN users u ON m.organizer_id = u.id
     WHERE $whereClause
     ORDER BY m.meeting_date DESC, m.start_time DESC
     LIMIT {$pagination['offset']}, 20",
    $params
);

$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

$meeting = null;
if ($id > 0) {
    $meeting = $db->fetch(
        "SELECT m.*, c.company_name, u.name as organizer_name
         FROM meetings m
         LEFT JOIN companies c ON m.company_id = c.id
         LEFT JOIN users u ON m.organizer_id = u.id
         WHERE m.id = ?",
        [$id]
    );
}

ob_start();
include 'views/meetings/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

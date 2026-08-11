<?php
/**
 * Global Helper Functions
 * Corporate Setup Limited - Internal CRM
 */

function redirect($url) {
    header("Location: {$url}");
    exit();
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function flashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function paginate($totalRecords, $currentPage, $perPage = 20) {
    $totalPages = ceil($totalRecords / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') return '-';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = DATETIME_FORMAT) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($datetime));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}

function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge badge-success">Active</span>',
        'inactive' => '<span class="badge badge-secondary">Inactive</span>',
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
        'expired' => '<span class="badge badge-danger">Expired</span>',
        'submitted' => '<span class="badge badge-info">Submitted</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'awaiting_client' => '<span class="badge badge-warning">Awaiting Client</span>',
        'awaiting_regulator' => '<span class="badge badge-info">Awaiting Regulator</span>',
        'new' => '<span class="badge badge-primary">New</span>',
        'in_progress' => '<span class="badge badge-info">In Progress</span>',
        'overdue' => '<span class="badge badge-danger">Overdue</span>',
    ];

    $status = strtolower(str_replace(' ', '_', $status));
    return $badges[$status] ?? '<span class="badge badge-secondary">' . sanitize($status) . '</span>';
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge badge-info">Low</span>',
        'medium' => '<span class="badge badge-warning">Medium</span>',
        'high' => '<span class="badge badge-danger">High</span>',
        'urgent' => '<span class="badge badge-danger pulse">Urgent</span>',
    ];

    $priority = strtolower($priority);
    return $badges[$priority] ?? '<span class="badge badge-secondary">' . sanitize($priority) . '</span>';
}

function uploadFile($file, $category = 'documents') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileError = $file['error'];

    if ($fileError !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $fileError];
    }

    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed'];
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    $uploadPath = UPLOAD_PATH . $category . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $newFileName = generateUUID() . '.' . $ext;
    $destination = $uploadPath . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        return [
            'success' => true,
            'filename' => $newFileName,
            'original_name' => $fileName,
            'path' => $category . '/' . $newFileName,
            'size' => $fileSize,
            'extension' => $ext
        ];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function logActivity($userId, $action, $description, $table = null, $recordId = null) {
    try {
        $db = Database::getInstance();

        $db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'table_name' => $table,
            'record_id' => $recordId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log('Error logging activity: ' . $e->getMessage());
    }
}

function notify($userId, $title, $message, $type = 'info', $actionUrl = null) {
    try {
        $db = Database::getInstance();

        $db->insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    } catch (Exception $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
}

function notifyMultiple($userIds, $title, $message, $type = 'info', $actionUrl = null) {
    try {
        $db = Database::getInstance();

        foreach ($userIds as $userId) {
            $db->insert('notifications', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return true;
    } catch (Exception $e) {
        error_log('Error creating notifications: ' . $e->getMessage());
        return false;
    }
}

function checkPermission($permission) {
    if (!isset($_SESSION['user_role']) || !isset($_SESSION['permissions'])) {
        return false;
    }

    $role = $_SESSION['user_role'];
    $permissions = $_SESSION['permissions'];

    if ($role === 'Administrator') {
        return true;
    }

    return in_array($permission, $permissions);
}

function requirePermission($permission) {
    if (!checkPermission($permission)) {
        http_response_code(403);
        require 'views/errors/403.php';
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'department' => $_SESSION['user_department'],
        'avatar' => $_SESSION['user_avatar'] ?? null
    ];
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit();
}

function exportToExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';

    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . sanitize($header) . '</th>';
        }
        echo '</tr>';

        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . sanitize($cell) . '</td>';
            }
            echo '</tr>';
        }
    }

    echo '</table></body></html>';
    exit();
}

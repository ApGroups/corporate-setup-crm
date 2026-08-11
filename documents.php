<?php
/**
 * Documents Management
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

$pageTitle = 'Documents';
$activeNav = 'documents';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$companyId = intval($_GET['company'] ?? 0);
$regId = intval($_GET['registration'] ?? 0);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($action) {
        case 'upload':
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                exit;
            }

            $validation = Security::validateFileUpload($_FILES['document']);
            if (!$validation['valid']) {
                echo json_encode(['success' => false, 'error' => implode(', ', $validation['errors'])]);
                exit;
            }

            $uploadResult = uploadFile($_FILES['document'], 'documents');
            if (!$uploadResult['success']) {
                echo json_encode(['success' => false, 'error' => $uploadResult['error']]);
                exit;
            }

            $docData = [
                'document_type' => sanitize($_POST['document_type']),
                'document_name' => sanitize($_POST['document_name'] ?: $uploadResult['original_name']),
                'file_name' => $uploadResult['original_name'],
                'file_path' => $uploadResult['path'],
                'file_size' => $uploadResult['size'],
                'file_type' => $uploadResult['extension'],
                'company_id' => intval($_POST['company_id'] ?? 0) ?: null,
                'registration_id' => intval($_POST['registration_id'] ?? 0) ?: null,
                'uploaded_by' => $user['id'],
                'description' => sanitize($_POST['description'] ?? ''),
                'expiry_date' => $_POST['expiry_date'] ?: null,
                'reminder_days' => intval($_POST['reminder_days'] ?? 30),
                'created_at' => date('Y-m-d H:i:s')
            ];

            try {
                $newId = $db->insert('documents', $docData);
                logActivity($user['id'], 'upload', 'Uploaded document: ' . $docData['document_name'], 'documents', $newId);
                echo json_encode(['success' => true, 'message' => 'Document uploaded successfully', 'id' => $newId]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to upload document']);
            }
            exit;

        case 'delete':
            if ($id > 0) {
                try {
                    $doc = $db->fetch("SELECT * FROM documents WHERE id = ?", [$id]);
                    if ($doc) {
                        $filePath = UPLOAD_PATH . $doc['file_path'];
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                        $db->update('documents', ['status' => 'deleted'], 'id = :id', ['id' => $id]);
                        logActivity($user['id'], 'delete', 'Deleted document: ' . $doc['document_name'], 'documents', $id);
                        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Document not found']);
                    }
                } catch (Exception $e) {
                    error_log("Document delete error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'Failed to delete document']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid document ID']);
            }
            exit;
    }
}

// Filter parameters
$type = $_GET['type'] ?? '';
$search = sanitize($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);

// Build query
$where = ['d.status = "active"'];
$params = [];

if ($companyId) {
    $where[] = 'd.company_id = :company_id';
    $params['company_id'] = $companyId;
}

if ($regId) {
    $where[] = 'd.registration_id = :registration_id';
    $params['registration_id'] = $regId;
}

if ($type) {
    $where[] = 'd.document_type = :type';
    $params['type'] = $type;
}

if ($search) {
    $where[] = '(d.document_name LIKE :search OR c.company_name LIKE :search)';
    $params['search'] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$totalCount = $db->query("SELECT COUNT(*) FROM documents d LEFT JOIN companies c ON d.company_id = c.id WHERE $whereClause", $params)->fetchColumn();

$perPage = 20;
$pagination = paginate($totalCount, $page, $perPage);

// Get documents
$documents = $db->fetchAll(
    "SELECT d.*, c.company_name, u.name as uploaded_by_name
     FROM documents d
     LEFT JOIN companies c ON d.company_id = c.id
     LEFT JOIN users u ON d.uploaded_by = u.id
     WHERE $whereClause
     ORDER BY d.created_at DESC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

// Get lookups
$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status = 'active' ORDER BY company_name");
$registrations = $db->fetchAll("SELECT r.id, c.company_name, rt.name as reg_type FROM registrations r JOIN companies c ON r.company_id = c.id JOIN registration_types rt ON r.registration_type_id = rt.id WHERE r.status NOT IN ('rejected', 'expired')");

ob_start();
include 'views/documents/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

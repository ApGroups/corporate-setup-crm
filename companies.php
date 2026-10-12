<?php
/**
 * Companies Management
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
requirePermission('view_companies');

$pageTitle = 'Companies';
$activeNav = 'companies';

$db = Database::getInstance();
$user = getCurrentUser();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($action, ['add', 'edit'], true) && !checkPermission('manage_companies')) {
    http_response_code(403);
    require 'views/errors/403.php';
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? ('srv-' . uniqid());
    $requestStartedAt = microtime(true);

    $respond = static function (bool $success, string $message, string $code, int $httpStatus = 200, array $extra = []) use ($requestId): void {
        http_response_code($httpStatus);
        echo json_encode(array_merge([
            'success' => $success,
            $success ? 'message' : 'error' => $message,
            'code' => $code,
            'traceId' => $requestId
        ], $extra));
        exit;
    };

    $canManageCompanies = checkPermission('manage_companies');
    $canManageContacts = checkPermission('manage_contacts') || $canManageCompanies;

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        error_log(sprintf(
            '[companies][%s] CSRF validation failed action=%s id=%s has_post_token=%s has_header_token=%s has_session_token=%s',
            $requestId,
            $action,
            (string)$id,
            isset($_POST['csrf_token']) ? '1' : '0',
            isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? '1' : '0',
            isset($_SESSION['csrf_token']) ? '1' : '0'
        ));
        $respond(false, 'Invalid security token', 'SECURITY_CSRF_INVALID', 403);
    }

    switch ($action) {
        case 'save':
            if (!$canManageCompanies) {
                $respond(false, 'You are not authorized to modify companies', 'AUTH_FORBIDDEN_COMPANY_WRITE', 403);
            }

            $rcNumber = trim((string) ($_POST['rc_number'] ?? ''));
            $companyData = [
                'company_name' => sanitize($_POST['company_name']),
                'rc_number' => $rcNumber === '' ? null : sanitize($rcNumber),
                'industry_id' => intval($_POST['industry_id'] ?? 0) ?: null,
                'address' => sanitize($_POST['address'] ?? ''),
                'city' => sanitize($_POST['city'] ?? ''),
                'state' => sanitize($_POST['state'] ?? ''),
                'country_id' => intval($_POST['country_id'] ?? 0) ?: null,
                'website' => sanitize($_POST['website'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'contact_person' => sanitize($_POST['contact_person'] ?? ''),
                'contact_position' => sanitize($_POST['contact_position'] ?? ''),
                'contact_phone' => sanitize($_POST['contact_phone'] ?? ''),
                'contact_email' => sanitize($_POST['contact_email'] ?? ''),
                'status' => sanitize($_POST['status'] ?? 'prospect'),
                'subscription_status' => sanitize($_POST['subscription_status'] ?? 'free'),
                'assigned_staff_id' => intval($_POST['assigned_staff_id'] ?? 0) ?: null,
                'client_since' => $_POST['client_since'] ?? null,
                'notes' => sanitize($_POST['notes'] ?? ''),
                'is_elite' => isset($_POST['is_elite']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($companyData['rc_number'] !== null) {
                $duplicate = $db->fetch(
                    "SELECT id, company_name FROM companies WHERE rc_number = ? AND id <> ?",
                    [$companyData['rc_number'], $id > 0 ? $id : 0]
                );
                if ($duplicate) {
                    $respond(false, 'A company with this RC number already exists: ' . $duplicate['company_name'], 'COMPANY_DUPLICATE_RC', 409);
                }
            }

            try {
                if ($id > 0) {
                    $db->update('companies', $companyData, 'id = :id', ['id' => $id]);
                    logActivity($user['id'], 'update', 'Updated company: ' . $companyData['company_name'], 'companies', $id);
                    createOperationNotification((int) $user['id'], 'Company updated', 'Company updated successfully.', 'success', 'companies.php?action=view&id=' . $id, [
                        'related_type' => 'companies',
                        'related_id' => $id
                    ]);

                    if (!empty($companyData['assigned_staff_id']) && (int) $companyData['assigned_staff_id'] !== (int) $user['id']) {
                        $assignedStaff = $db->fetch("SELECT name, email FROM users WHERE id = ?", [$companyData['assigned_staff_id']]);
                        if ($assignedStaff) {
                            notify((int) $companyData['assigned_staff_id'], 'Company updated', 'A company assigned to you was updated: ' . $companyData['company_name'], 'info', 'companies.php?action=view&id=' . $id, [
                                'related_type' => 'companies',
                                'related_id' => $id,
                                'email' => [
                                    'to_email' => $assignedStaff['email'],
                                    'to_name' => $assignedStaff['name'],
                                    'subject' => 'Corporate Setup CRM - Company Updated',
                                    'html' => buildStaffAccountEmailHtml('A Company Assigned To You Was Updated', [
                                        'Company: ' . $companyData['company_name'],
                                        'Please open the CRM to review the latest company details.'
                                    ], 'companies.php?action=view&id=' . $id, 'View Company'),
                                    'text' => 'A company assigned to you was updated: ' . $companyData['company_name']
                                ]
                            ]);
                        }
                    }
                    $respond(true, 'Company updated successfully', 'COMPANY_UPDATED');
                } else {
                    $companyData['created_by'] = $user['id'];
                    $companyData['created_at'] = date('Y-m-d H:i:s');
                    $newId = $db->insert('companies', $companyData);
                    logActivity($user['id'], 'create', 'Created company: ' . $companyData['company_name'], 'companies', $newId);
                    createOperationNotification((int) $user['id'], 'Company created', 'Company created successfully.', 'success', 'companies.php?action=view&id=' . $newId, [
                        'related_type' => 'companies',
                        'related_id' => $newId
                    ]);
                    $respond(true, 'Company created successfully', 'COMPANY_CREATED', 200, ['id' => $newId]);
                }
            } catch (Exception $e) {
                $sqlStateCode = ($e instanceof PDOException && isset($e->errorInfo[1])) ? (int) $e->errorInfo[1] : 0;
                $duplicateKey = $sqlStateCode === 1062 || stripos($e->getMessage(), 'Duplicate') !== false;
                error_log(sprintf('[companies][%s] Save failed action=%s id=%s error=%s', $requestId, $action, (string)$id, $e->getMessage()));
                if ($duplicateKey) {
                    $respond(false, 'A company with this RC number already exists.', 'COMPANY_DUPLICATE_RC', 409);
                }
                $respond(false, 'Failed to save company: ' . $e->getMessage(), 'COMPANY_SAVE_FAILED');
            }

        case 'delete':
            if (!$canManageCompanies) {
                $respond(false, 'You are not authorized to delete companies', 'AUTH_FORBIDDEN_COMPANY_DELETE', 403);
            }

            $activeDatabase = (string)$db->query('SELECT DATABASE()')->fetchColumn();
            error_log(sprintf('[companies][%s] Delete requested id=%s user_id=%s db=%s', $requestId, (string)$id, (string)($user['id'] ?? 0), $activeDatabase));

            if ($id <= 0) {
                $respond(false, 'Invalid company ID', 'COMPANY_INVALID_ID', 422);
            }

            $company = $db->fetch("SELECT id, company_name FROM companies WHERE id = ?", [$id]);
            if (!$company) {
                $respond(false, 'Company not found', 'COMPANY_NOT_FOUND', 404, ['affectedRows' => 0]);
            }

            try {
                $db->beginTransaction();

                // Detach nullable references first to avoid FK failures on installations
                // where ON DELETE SET NULL may not be configured consistently.
                $detachedTasks = $db->query(
                    "UPDATE tasks SET related_company_id = NULL WHERE related_company_id = ?",
                    [$id]
                )->rowCount();
                $detachedMeetings = $db->query(
                    "UPDATE meetings SET company_id = NULL WHERE company_id = ?",
                    [$id]
                )->rowCount();
                $detachedOpportunities = $db->query(
                    "UPDATE opportunities SET company_id = NULL WHERE company_id = ?",
                    [$id]
                )->rowCount();

                $deletedRows = $db->delete('companies', 'id = :id', ['id' => $id])->rowCount();
                if ($deletedRows < 1) {
                    $db->rollBack();
                    error_log(sprintf('[companies][%s] Delete no-op id=%s db=%s', $requestId, (string)$id, $activeDatabase));
                    $respond(false, 'Company not deleted (record may no longer exist)', 'COMPANY_DELETE_NOOP', 409, ['affectedRows' => 0]);
                }

                $db->commit();

                $durationMs = (int)round((microtime(true) - $requestStartedAt) * 1000);

                logActivity($user['id'], 'delete', 'Deleted company: ' . $company['company_name'], 'companies', $id);
                createOperationNotification((int) $user['id'], 'Company deleted', 'Company deleted successfully.', 'success', 'companies.php', [
                    'related_type' => 'companies',
                    'related_id' => $id
                ]);
                error_log(sprintf('[companies][%s] Delete successful id=%s company=%s rows=%s duration_ms=%s', $requestId, (string)$id, $company['company_name'], (string)$deletedRows, (string)$durationMs));
                $respond(true, 'Company deleted successfully', 'COMPANY_DELETED', 200, [
                    'affectedRows' => $deletedRows,
                    'meta' => [
                        'active_database' => $activeDatabase,
                        'detached_tasks' => $detachedTasks,
                        'detached_meetings' => $detachedMeetings,
                        'detached_opportunities' => $detachedOpportunities,
                        'duration_ms' => $durationMs
                    ]
                ]);
            } catch (Exception $e) {
                if ($db->getConnection()->inTransaction()) {
                    $db->rollBack();
                }

                error_log(sprintf('[companies][%s] Delete failed id=%s error=%s', $requestId, (string)$id, $e->getMessage()));
                $respond(false, 'Failed to delete company: ' . $e->getMessage(), 'COMPANY_DELETE_FAILED', 409, ['affectedRows' => 0]);
            }

        case 'delete_contact':
            if (!$canManageContacts) {
                $respond(false, 'You are not authorized to delete contacts', 'AUTH_FORBIDDEN_CONTACT_DELETE', 403);
            }

            if ($id > 0) {
                try {
                    $contact = $db->fetch("SELECT name FROM company_contacts WHERE id = ?", [$id]);
                    if ($contact) {
                        $db->delete('company_contacts', 'id = :id', ['id' => $id]);
                        logActivity($user['id'], 'delete', 'Deleted contact: ' . $contact['name'], 'company_contacts', $id);
                        createOperationNotification((int) $user['id'], 'Contact deleted', 'Contact deleted successfully.', 'success', 'companies.php', [
                            'related_type' => 'company_contacts',
                            'related_id' => $id
                        ]);
                        $respond(true, 'Contact deleted successfully', 'CONTACT_DELETED');
                    } else {
                        $respond(false, 'Contact not found', 'CONTACT_NOT_FOUND', 404);
                    }
                } catch (Exception $e) {
                    error_log("Contact delete error: " . $e->getMessage());
                    $respond(false, 'Failed to delete contact: ' . $e->getMessage(), 'CONTACT_DELETE_FAILED', 409);
                }
            } else {
                $respond(false, 'Invalid contact ID', 'CONTACT_INVALID_ID', 422);
            }

        case 'add_contact':
            if (!$canManageContacts) {
                $respond(false, 'You are not authorized to add contacts', 'AUTH_FORBIDDEN_CONTACT_CREATE', 403);
            }

            $contactData = [
                'company_id' => intval($_POST['company_id']),
                'name' => sanitize($_POST['name']),
                'position' => sanitize($_POST['position'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'is_primary' => isset($_POST['is_primary']) ? 1 : 0,
                'notes' => sanitize($_POST['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s')
            ];

            try {
                $newId = $db->insert('company_contacts', $contactData);
                logActivity($user['id'], 'create', 'Added contact: ' . $contactData['name'], 'company_contacts', $newId);
                createOperationNotification((int) $user['id'], 'Contact added', 'Contact added successfully.', 'success', 'companies.php?action=view&id=' . $contactData['company_id'], [
                    'related_type' => 'company_contacts',
                    'related_id' => $newId
                ]);
                $respond(true, 'Contact added successfully', 'CONTACT_CREATED', 200, ['id' => $newId]);
            } catch (Exception $e) {
                $respond(false, 'Failed to add contact: ' . $e->getMessage(), 'CONTACT_CREATE_FAILED');
            }

        default:
            $respond(false, 'Unsupported action', 'ACTION_UNSUPPORTED', 400);
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$subscription = $_GET['subscription'] ?? '';
$search = sanitize($_GET['search'] ?? '');
$assigned = intval($_GET['assigned'] ?? 0);
$page = intval($_GET['page'] ?? 1);
$perPage = 20;

// Build query
$whereClauses = ['1=1'];
$params = [];

if ($status) {
    $whereClauses[] = 'c.status = :status';
    $params['status'] = $status;
}

if ($subscription) {
    $whereClauses[] = 'c.subscription_status = :subscription';
    $params['subscription'] = $subscription;
}

if ($search) {
    $whereClauses[] = '(c.company_name LIKE :search OR c.rc_number LIKE :search OR c.email LIKE :search OR c.contact_person LIKE :search)';
    $params['search'] = "%$search%";
}

if ($assigned) {
    $whereClauses[] = 'c.assigned_staff_id = :assigned';
    $params['assigned'] = $assigned;
}

$whereClause = implode(' AND ', $whereClauses);

// Get total count
$totalCount = $db->query(
    "SELECT COUNT(*) FROM companies c WHERE $whereClause",
    $params
)->fetchColumn();

$pagination = paginate($totalCount, $page, $perPage);

// Get companies
$companies = $db->fetchAll(
    "SELECT c.*, i.name as industry_name, u.name as assigned_staff, country.name as country_name,
            (SELECT COUNT(*) FROM registrations r WHERE r.company_id = c.id) as registration_count,
            (SELECT COUNT(*) FROM tasks t WHERE t.related_company_id = c.id AND t.status IN ('pending', 'in_progress')) as pending_tasks
     FROM companies c
     LEFT JOIN industries i ON c.industry_id = i.id
     LEFT JOIN users u ON c.assigned_staff_id = u.id
     LEFT JOIN countries country ON c.country_id = country.id
     WHERE $whereClause
     ORDER BY c.updated_at DESC
     LIMIT {$pagination['offset']}, {$perPage}",
    $params
);

// Get lookups
$industries = $db->fetchAll("SELECT id, name FROM industries WHERE status = 'active' ORDER BY name");
$countries = $db->fetchAll("SELECT id, name FROM countries ORDER BY name");
$staff = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

// Get single company for view/edit
$company = null;
$contacts = [];
$registrations = [];
$documents = [];
$communications = [];

if ($id > 0) {
    $company = $db->fetch(
        "SELECT c.*, i.name as industry_name, u.name as assigned_staff, country.name as country_name
         FROM companies c
         LEFT JOIN industries i ON c.industry_id = i.id
         LEFT JOIN users u ON c.assigned_staff_id = u.id
         LEFT JOIN countries country ON c.country_id = country.id
         WHERE c.id = ?",
        [$id]
    );

    if ($company) {
        $contacts = $db->fetchAll("SELECT * FROM company_contacts WHERE company_id = ? ORDER BY is_primary DESC, name", [$id]);
        $registrations = $db->query(
            "SELECT r.id, r.status, r.expiry_date, rt.name as registration_type
             FROM registrations r
             JOIN registration_types rt ON r.registration_type_id = rt.id
             WHERE r.company_id = ?
             ORDER BY r.created_at DESC LIMIT 10",
            [$id]
        )->fetchAll();
        $documents = $db->fetchAll(
            "SELECT * FROM documents WHERE company_id = ? ORDER BY created_at DESC LIMIT 10",
            [$id]
        );
        $communications = $db->fetchAll(
            "SELECT comm.*, u.name as staff_name
             FROM communications comm
             LEFT JOIN users u ON comm.user_id = u.id
             WHERE comm.company_id = ?
             ORDER BY comm.communication_date DESC LIMIT 5",
            [$id]
        );
    }
}

// Load layout
ob_start();
include 'views/companies/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

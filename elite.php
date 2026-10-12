<?php
/**
 * Elite Members
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
requirePermission('view_elite');

$pageTitle = 'Elite Members';
$activeNav = 'elite';

$db = Database::getInstance();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!$csrfToken || !Security::validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    $companyId = (int) ($_POST['company_id'] ?? 0);
    $startDate = trim((string) ($_POST['membership_start_date'] ?? ''));
    $start = DateTime::createFromFormat('Y-m-d', $startDate);
    $tier = trim((string) ($_POST['membership_tier'] ?? 'elite'));
    $allowedTiers = ['basic', 'premium', 'elite', 'platinum'];
    if (!in_array($tier, $allowedTiers, true)) {
        $tier = 'elite';
    }

    if ($companyId <= 0 || !$start || $start->format('Y-m-d') !== $startDate) {
        echo json_encode(['success' => false, 'error' => 'Company and a valid start date are required']);
        exit;
    }

    $duplicate = $db->fetch(
        "SELECT id FROM elite_members WHERE company_id = ? AND membership_start_date = ?",
        [$companyId, $startDate]
    );
    if ($duplicate) {
        echo json_encode(['success' => false, 'error' => 'This company already has a membership starting on that date']);
        exit;
    }

    try {
        $newId = $db->insert('elite_members', [
            'company_id' => $companyId,
            'membership_tier' => $tier,
            'membership_start_date' => $startDate,
            'membership_end_date' => ($_POST['membership_end_date'] ?? '') !== '' ? $_POST['membership_end_date'] : null,
            'status' => 'active',
            'assigned_account_manager' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        logActivity($user['id'], 'create', 'Added elite membership', 'elite_members', $newId);
        echo json_encode(['success' => true, 'message' => 'Elite membership saved', 'reload' => true]);
    } catch (Exception $e) {
        $sqlStateCode = ($e instanceof PDOException && isset($e->errorInfo[1])) ? (int) $e->errorInfo[1] : 0;
        $duplicateKey = $sqlStateCode === 1062 || stripos($e->getMessage(), 'Duplicate') !== false;
        echo json_encode(['success' => false, 'error' => $duplicateKey ? 'This company already has a membership starting on that date' : 'Failed to save elite membership']);
    }
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

[$where, $params] = eliteMembershipWhere($search, $status);
$countRow = $db->fetch(
    "SELECT COUNT(*) AS total
     FROM elite_members em
     LEFT JOIN companies c ON em.company_id = c.id
     LEFT JOIN users u ON em.assigned_account_manager = u.id
     WHERE {$where}",
    $params
);
$pagination = paginate((int) ($countRow['total'] ?? 0), $page, 20);
$members = $db->fetchAll(
    "SELECT em.*, c.company_name, c.email, c.phone, u.name AS account_manager
     FROM elite_members em
     LEFT JOIN companies c ON em.company_id = c.id
     LEFT JOIN users u ON em.assigned_account_manager = u.id
     WHERE {$where}
     ORDER BY em.membership_start_date DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);
$companies = $db->fetchAll("SELECT id, company_name FROM companies WHERE status != 'churned' ORDER BY company_name");

ob_start();
include 'views/elite/index.php';
$content = ob_get_clean();
include 'views/layouts/app.php';

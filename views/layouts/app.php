<?php
/**
 * Main Application Layout
 * Corporate Setup Limited - Internal CRM
 */

if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($activeNav)) $activeNav = 'dashboard';

$user = getCurrentUser();
$db = Database::getInstance();

// Get notification count
$notificationCount = $db->query(
    "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
    [$user['id']]
)->fetchColumn();

// Get pending tasks count (multi-assignee aware)
$pendingTasks = $db->query(
    "SELECT COUNT(DISTINCT t.id) FROM tasks t
     INNER JOIN task_assignees ta ON ta.task_id = t.id
     WHERE ta.user_id = ? AND t.status IN ('pending', 'in_progress')",
    [$user['id']]
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= sanitize(Security::generateCSRFToken()) ?>">
    <title><?= sanitize($pageTitle) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-building"></i>
                    <span>Corporate</span>Setup
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="index.php" class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <?php if (checkPermission('view_companies') || checkPermission('view_registrations') || checkPermission('view_communications') || checkPermission('view_documents')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Client Management</div>
                    <?php if (checkPermission('view_companies')): ?>
                    <a href="companies.php" class="nav-item <?= $activeNav === 'companies' ? 'active' : '' ?>">
                        <i class="fas fa-building"></i>
                        <span>Companies</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_registrations')): ?>
                    <a href="registrations.php" class="nav-item <?= $activeNav === 'registrations' ? 'active' : '' ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Registrations</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_communications')): ?>
                    <a href="communications.php" class="nav-item <?= $activeNav === 'communications' ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <span>Communications</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_documents')): ?>
                    <a href="documents.php" class="nav-item <?= $activeNav === 'documents' ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i>
                        <span>Documents</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (checkPermission('view_compliance') || checkPermission('view_elite')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Compliance</div>
                    <?php if (checkPermission('view_compliance')): ?>
                    <a href="compliance.php" class="nav-item <?= $activeNav === 'compliance' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Compliance Tracker</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_elite')): ?>
                    <a href="elite.php" class="nav-item <?= $activeNav === 'elite' ? 'active' : '' ?>">
                        <i class="fas fa-crown"></i>
                        <span>Elite Members</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (checkPermission('view_tasks') || checkPermission('view_meetings') || checkPermission('view_calendar') || checkPermission('view_opportunities')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Operations</div>
                    <?php if (checkPermission('view_tasks')): ?>
                    <a href="tasks.php" class="nav-item <?= $activeNav === 'tasks' ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Tasks</span>
                        <?php if ($pendingTasks > 0): ?>
                            <span class="nav-badge"><?= $pendingTasks ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_meetings')): ?>
                    <a href="meetings.php" class="nav-item <?= $activeNav === 'meetings' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Meetings</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_calendar')): ?>
                    <a href="calendar.php" class="nav-item <?= $activeNav === 'calendar' ? 'active' : '' ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_opportunities')): ?>
                    <a href="opportunities.php" class="nav-item <?= $activeNav === 'opportunities' ? 'active' : '' ?>">
                        <i class="fas fa-lightbulb"></i>
                        <span>Opportunities</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (checkPermission('view_reports') || checkPermission('submit_reports') || checkPermission('approve_reports')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Reports</div>
                    <?php if (checkPermission('submit_reports') || checkPermission('view_reports') || checkPermission('approve_reports')): ?>
                    <a href="daily-reports.php" class="nav-item <?= $activeNav === 'daily-reports' ? 'active' : '' ?>">
                        <i class="fas fa-file-lines"></i>
                        <span>Daily Reports</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_reports')): ?>
                    <a href="monthly-reports.php" class="nav-item <?= $activeNav === 'monthly-reports' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Monthly Reports</span>
                    </a>
                    <a href="reports.php" class="nav-item <?= $activeNav === 'reports' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>
                    <?php if (checkPermission('view_users')): ?>
                    <a href="staff.php" class="nav-item <?= $activeNav === 'staff' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Staff</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_logs')): ?>
                    <a href="activity-log.php" class="nav-item <?= $activeNav === 'activity-log' ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>Activity Log</span>
                    </a>
                    <?php endif; ?>
                    <?php if (checkPermission('view_settings')): ?>
                    <a href="settings.php" class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <?= $pageTitle ? '<h1 class="page-title">' . sanitize($pageTitle) . '</h1>' : '' ?>
                </div>

                <div class="header-right">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                           <input type="text" id="globalSearch" placeholder="Search companies, tasks, registrations...">
                    </div>

                    <a href="notifications.php" class="header-icon" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="badge"><?= $notificationCount > 9 ? '9+' : $notificationCount ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <div class="user-menu" id="userMenuTrigger">
                            <?= generateAvatar($user['name'], 36) ?>
                            <div class="user-info">
                                <div class="user-name"><?= sanitize($user['name']) ?></div>
                                <div class="user-role"><?= sanitize($user['role']) ?></div>
                            </div>
                            <i class="fas fa-chevron-down" style="color: var(--dark-grey);"></i>
                        </div>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="change-password.php" class="dropdown-item">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="assets/js/main.js"></script>

    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>
</html>

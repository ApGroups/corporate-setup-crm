<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Search Results</h1>
        <p class="page-subtitle">
            <?php if ($query): ?>
                Found <?= $totalResults ?> results for "<?= sanitize($query) ?>"
            <?php else: ?>
                Enter a search term to find companies, registrations, tasks, and more.
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-center">
            <input type="text" name="q" class="form-control" placeholder="Search companies, registrations, tasks, staff..."
                   value="<?= sanitize($query) ?>" style="max-width: 500px;" autofocus>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
</div>

<?php if (strlen($query) < 2 && $query): ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <div class="alert-content">Please enter at least 2 characters to search.</div>
    </div>
<?php endif; ?>

<?php if ($totalResults > 0): ?>
    <!-- Companies -->
    <?php if (!empty($results['companies'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building"></i> Companies (<?= count($results['companies']) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Company</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['companies'] as $c): ?>
                            <tr>
                                <td><a href="companies.php?action=view&id=<?= $c['id'] ?>"><?= highlightSearch(sanitize($c['company_name']), $query) ?></a></td>
                                <td><?= sanitize($c['email']) ?></td>
                                <td><?= sanitize($c['phone']) ?></td>
                                <td><?= getStatusBadge($c['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Registrations -->
    <?php if (!empty($results['registrations'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt"></i> Registrations (<?= count($results['registrations']) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Company</th><th>Type</th><th>Status</th><th>Expiry</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['registrations'] as $r): ?>
                            <tr>
                                <td><a href="registrations.php?action=view&id=<?= $r['id'] ?>"><?= sanitize($r['company_name']) ?></a></td>
                                <td><?= sanitize($r['registration_type']) ?></td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                                <td><?= $r['expiry_date'] ? formatDate($r['expiry_date']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tasks -->
    <?php if (!empty($results['tasks'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks"></i> Tasks (<?= count($results['tasks']) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Task</th><th>Assigned To</th><th>Status</th><th>Due</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['tasks'] as $t): ?>
                            <tr>
                                <td><a href="tasks.php?action=view&id=<?= $t['id'] ?>"><?= highlightSearch(sanitize($t['title']), $query) ?></a></td>
                                <td><?= sanitize($t['assigned_to']) ?></td>
                                <td><?= getStatusBadge($t['status']) ?></td>
                                <td><?= $t['due_date'] ? formatDate($t['due_date']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Documents -->
    <?php if (!empty($results['documents'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-folder"></i> Documents (<?= count($results['documents']) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Document</th><th>Type</th><th>Company</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['documents'] as $d): ?>
                            <tr>
                                <td><a href="documents.php?action=view&id=<?= $d['id'] ?>"><?= highlightSearch(sanitize($d['document_name']), $query) ?></a></td>
                                <td><?= ucwords(str_replace('_', ' ', $d['document_type'])) ?></td>
                                <td><?= sanitize($d['company_name']) ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Staff -->
    <?php if (!empty($results['staff'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users"></i> Staff (<?= count($results['staff']) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['staff'] as $s): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <?= generateAvatar($s['name'], 28) ?>
                                        <a href="staff.php?action=view&id=<?= $s['id'] ?>"><?= highlightSearch(sanitize($s['name']), $query) ?></a>
                                    </div>
                                </td>
                                <td><?= sanitize($s['email']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($query && strlen($query) >= 2): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>No results found</h3>
        <p>Try different keywords or check your spelling</p>
    </div>
<?php endif; ?>

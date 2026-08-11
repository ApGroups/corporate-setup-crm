<?php defined('APP_NAME') or die('Direct access not allowed');

$docTypes = ['certificate', 'license', 'permit', 'insurance', 'receipt', 'invoice', 'contract', 'letter', 'report', 'certificate_of_incorporation', 'tax_clearance', 'financial_statement', 'other'];
?>

<?php if ($action === 'upload'): ?>
    <div class="page-header">
        <div>
            <h1 class="page-title">Upload Document</h1>
            <p class="page-subtitle"><a href="documents.php">Documents</a> &rsaquo; Upload</p>
        </div>
        <div class="btn-group">
            <a href="documents.php" class="btn btn-outline">Cancel</a>
        </div>
    </div>

    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">

        <div class="grid grid-2 gap-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Document Information</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="document_name">Document Name</label>
                        <input type="text" id="document_name" name="document_name" class="form-control" placeholder="e.g., NUPRC Registration Certificate" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="document_type">Document Type</label>
                        <select id="document_type" name="document_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <?php foreach ($docTypes as $t): ?>
                                <option value="<?= $t ?>"><?= ucwords(str_replace('_', ' ', $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_id">Related Company</label>
                        <select id="company_id" name="company_id" class="form-control">
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $companyId == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="registration_id">Related Registration</label>
                        <select id="registration_id" name="registration_id" class="form-control">
                            <option value="">Select Registration</option>
                            <?php foreach ($registrations as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $regId == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['company_name']) ?> - <?= sanitize($r['reg_type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">File & Expiry</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required">Upload File</label>
                        <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                            <input type="file" id="fileInput" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" style="display: none;" required onchange="updateFileName(this)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload or drag and drop</p>
                            <p class="text-muted" style="font-size: 0.75rem;">PDF, Word, Excel, Images (max 10MB)</p>
                        </div>
                        <div id="filePreview" class="mt-2"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reminder_days">Reminder (days before)</label>
                            <input type="number" id="reminder_days" name="reminder_days" class="form-control" value="30">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-upload"></i> Upload Document</button>
        </div>
    </form>

    <script>
        function updateFileName(input) {
            const file = input.files[0];
            if (file) {
                document.getElementById('filePreview').innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file"></i> <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} KB)
                    </div>
                `;
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('documents.php?action=upload', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Document uploaded successfully', 'success');
                    setTimeout(() => window.location.href = 'documents.php', 1000);
                } else {
                    showToast(data.error, 'danger');
                }
            });
        });
    </script>

<?php else: ?>
    <!-- List View -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Documents</h1>
            <p class="page-subtitle">Manage company documents and certificates</p>
        </div>
        <div class="btn-group">
            <a href="documents.php?action=upload" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Document</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search documents..." value="<?= sanitize($search) ?>" style="width: 250px;">
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($docTypes as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $t)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="documents.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Type</th>
                            <th>Company</th>
                            <th>Expiry</th>
                            <th>Uploaded</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-folder-open"></i><h3>No documents found</h3></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <i class="fas <?= getFileIcon($doc['file_type']) ?>"></i>
                                        <a href="uploads/<?= $doc['file_path'] ?>" target="_blank" class="table-link"><?= sanitize($doc['document_name']) ?></a>
                                        <br><small class="text-muted"><?= sanitize($doc['file_name']) ?></small>
                                    </td>
                                    <td><?= ucwords(str_replace('_', ' ', $doc['document_type'])) ?></td>
                                    <td><?= $doc['company_name'] ? '<a href="companies.php?action=view&id=' . $doc['company_id'] . '">' . sanitize($doc['company_name']) . '</a>' : '-' ?></td>
                                    <td>
                                        <?php if ($doc['expiry_date']): ?>
                                            <?= formatDate($doc['expiry_date']) ?>
                                            <?php if (isOverdue($doc['expiry_date'])): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php elseif (isExpiringSoon($doc['expiry_date'])): ?>
                                                <span class="badge badge-warning">Expiring</span>
                                            <?php endif; ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <?= sanitize($doc['uploaded_by_name']) ?><br>
                                        <small class="text-muted"><?= timeAgo($doc['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="uploads/<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-download"></i></a>
                                            <button onclick="deleteDoc(<?= $doc['id'] ?>)" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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

    <script>
        function deleteDoc(id) {
            if (confirm('Are you sure you want to delete this document?')) {
                fetch('documents.php?action=delete&id=' + id, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=<?= Security::generateCSRFToken() ?>'
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error);
                });
            }
        }
    </script>
<?php endif; ?>

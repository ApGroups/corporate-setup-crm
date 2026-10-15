<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">Client Reports</h1>
        <p class="page-subtitle">Elite compliance update for a company</p>
    </div>
</div>

<div class="card mb-6 no-print">
    <div class="card-body">
        <form method="GET" class="flex gap-4 items-end flex-wrap">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="q">Find company</label>
                <input type="text" id="q" name="q" class="form-control" placeholder="Name, RC number, or email" value="<?= sanitize($search) ?>" style="width: 260px;">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="company_id">Company</label>
                <select id="company_id" name="company_id" class="form-control" style="min-width: 240px;" <?= $matches ? 'required' : '' ?>>
                    <option value="">Select a company</option>
                    <?php foreach ($matches as $match): ?>
                        <option value="<?= (int) $match['id'] ?>" <?= $companyId === (int) $match['id'] ? 'selected' : '' ?>>
                            <?= sanitize(clientReportCompanyLabel($match)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="from">From</label>
                <input type="date" id="from" name="from" class="form-control" value="<?= sanitize($from) ?>" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="to">To</label>
                <input type="date" id="to" name="to" class="form-control" value="<?= sanitize($to) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Preview</button>
            <a class="btn btn-outline" href="client-reports.php?action=extract&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>" target="_blank" rel="noopener">Print elite messages</a>
        </form>
        <?php if ($companyListError !== ''): ?>
            <p class="text-muted mt-2"><?= sanitize($companyListError) ?></p>
        <?php elseif ($search !== '' && empty($matches)): ?>
            <p class="text-muted mt-2">No companies matched that search.</p>
        <?php elseif (empty($matches)): ?>
            <p class="text-muted mt-2">No companies are available.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($reportError !== ''): ?>
    <div class="alert alert-warning no-print"><?= sanitize($reportError) ?></div>
<?php endif; ?>

<?php if ($report): ?>
    <form id="messageForm" method="POST" data-no-ajax class="mb-6">
        <?= csrf_field() ?>
        <input type="hidden" name="company_id" value="<?= (int) $report['company']['id'] ?>">
        <input type="hidden" name="from" value="<?= sanitize($report['from']) ?>">
        <input type="hidden" name="to" value="<?= sanitize($report['to']) ?>">

        <div class="grid grid-2 gap-4 message-layout">
            <div class="card no-print">
                <div class="card-header"><h3 class="card-title">Message wording</h3></div>
                <div class="card-body">
                    <p class="text-muted">These edits apply to this letter only. Saved registration statuses stay unchanged.</p>
                    <?php if (empty($report['registrations'])): ?>
                        <p>No registrations to report.</p>
                    <?php else: ?>
                        <?php foreach ($report['registrations'] as $row): ?>
                            <div class="form-group">
                                <label class="form-label" for="line-<?= (int) $row['id'] ?>"><?= sanitize($row['registration_type']) ?></label>
                                <input type="text" class="form-control letter-input" id="line-<?= (int) $row['id'] ?>" name="lines[<?= (int) $row['id'] ?>]" value="<?= sanitize($row['generated_phrase']) ?>" data-generated="<?= sanitize($row['generated_phrase']) ?>" data-line-id="<?= (int) $row['id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label" for="note">Optional note</label>
                        <textarea id="note" name="note" class="form-control" rows="3" placeholder="Extra wording for this message only"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="invoice_document_id">Invoice picture</label>
                        <select id="invoice_document_id" name="invoice_document_id" class="form-control">
                            <option value="">No invoice</option>
                            <?php foreach ($report['invoices'] as $invoice): ?>
                                <option value="<?= (int) $invoice['id'] ?>" data-image="<?= isInvoiceImage($invoice) ? 'uploads/' . sanitize($invoice['file_path']) : '' ?>" data-link="<?= sanitize($invoice['external_url'] ?: ($invoice['file_path'] ? 'uploads/' . $invoice['file_path'] : '')) ?>" data-name="<?= sanitize($invoice['document_name']) ?>">
                                    <?= sanitize($invoice['document_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($report['invoices'])): ?>
                            <small class="text-muted">This company has no saved invoice document.</small>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <button type="submit" name="action" value="save_message" class="btn btn-primary">Save message</button>
                        <button type="button" id="copyLetter" class="btn btn-outline">Copy</button>
                        <button type="button" id="restoreLetter" class="btn btn-outline">Restore generated wording</button>
                        <button type="submit" name="action" value="download" class="btn btn-outline" onclick="document.getElementById('downloadFormat').value='csv'">Download CSV</button>
                        <button type="button" class="btn btn-outline" onclick="window.print()">Print / Save as PDF</button>
                    </div>
                    <input type="hidden" name="format" id="downloadFormat" value="csv">
                </div>
            </div>

            <div class="card message-sheet" id="letterPreview">
                <div class="message-sheet-header">
                    <img src="assets/images/cslogo.png" alt="Corporate Setup">
                    <div>
                        <h3>Elite Monthly Messages</h3>
                        <p>Corporate Setup</p>
                    </div>
                </div>
                <div class="card-body">
                    <h2><?= sanitize($report['company']['company_name']) ?></h2>
                    <p>Dear Valued Elite,</p>
                    <p>This is an official compliance update from Corporate-setup.app, your digital compliance management platform powered by Corporate Setup.</p>
                    <p>Kindly review your company status below:</p>
                    <p><strong>Company:</strong> <?= sanitize($report['company']['company_name']) ?></p>
                    <?php if (empty($report['registrations'])): ?>
                        <p class="letter-empty">No registrations to report.</p>
                    <?php else: ?>
                        <table class="letter-table">
                            <thead><tr><th>Service</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($report['registrations'] as $row): ?>
                                <tr>
                                    <td class="service"><?= sanitize($row['registration_type']) ?></td>
                                    <td class="status"><span class="preview-line" data-line-id="<?= (int) $row['id'] ?>"><?= sanitize($row['generated_phrase']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <p id="previewNote" hidden><strong>Note:</strong> <span></span></p>
                    <p id="previewInvoice" hidden></p>
                    <p><strong>Login Details:</strong><br><strong>Link:</strong> <a href="https://www.corporate-setup.app/login">https://www.corporate-setup.app/login</a></p>
                    <p>For more information about our services, please visit: corporate-setup.app</p>
                    <p>Contact us at: <a href="mailto:compliance@corporate-setup.com">compliance@corporate-setup.com</a> or +234 702 560 0065</p>
                    <p>Kind regards,<br><strong>Corporate Setup Team</strong></p>
                </div>
            </div>
        </div>
    </form>
    <script>
        (function () {
            var preview = document.getElementById('letterPreview');
            var note = document.getElementById('note');
            var noteRow = document.getElementById('previewNote');
            var invoiceSelect = document.getElementById('invoice_document_id');
            var invoiceRow = document.getElementById('previewInvoice');

            function syncLines() {
                document.querySelectorAll('.letter-input').forEach(function (input) {
                    var target = preview.querySelector('.preview-line[data-line-id="' + input.getAttribute('data-line-id') + '"]');
                    if (target) {
                        target.textContent = input.value;
                    }
                });
                var noteText = note.value.trim();
                noteRow.hidden = noteText === '';
                noteRow.querySelector('span').textContent = noteText;
                var option = invoiceSelect.options[invoiceSelect.selectedIndex];
                var image = option ? option.getAttribute('data-image') : '';
                var link = option ? option.getAttribute('data-link') : '';
                var name = option ? option.getAttribute('data-name') : '';
                invoiceRow.innerHTML = '';
                invoiceRow.hidden = !invoiceSelect.value;
                if (image) {
                    var img = document.createElement('img');
                    img.src = image;
                    img.alt = name || 'Invoice';
                    invoiceRow.appendChild(img);
                } else if (link) {
                    var anchor = document.createElement('a');
                    anchor.href = link;
                    anchor.target = '_blank';
                    anchor.rel = 'noopener noreferrer';
                    anchor.textContent = name || 'Invoice';
                    invoiceRow.appendChild(anchor);
                }
            }

            document.getElementById('messageForm').addEventListener('input', syncLines);
            invoiceSelect.addEventListener('change', syncLines);
            document.getElementById('restoreLetter').addEventListener('click', function () {
                document.querySelectorAll('.letter-input').forEach(function (input) {
                    input.value = input.getAttribute('data-generated') || '';
                });
                note.value = '';
                invoiceSelect.value = '';
                syncLines();
            });
            document.getElementById('copyLetter').addEventListener('click', function () {
                var text = preview.innerText;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text);
                }
            });
        })();
    </script>
<?php endif; ?>

<div class="card no-print">
    <div class="card-header"><h3 class="card-title">History</h3></div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <p class="p-4 text-muted">No client reports have been generated yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Reference</th><th>Company</th><th>Period</th><th>Generated</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?= sanitize($row['report_reference']) ?></td>
                        <td><?= sanitize($row['company_name']) ?></td>
                        <td><?= formatDate($row['period_start']) ?> – <?= formatDate($row['period_end']) ?></td>
                        <td><?= sanitize($row['generated_by_name'] ?: '-') ?><br><small class="text-muted"><?= formatDateTime($row['generated_at']) ?></small></td>
                        <td><?= sanitize($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

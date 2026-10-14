<?php
/**
 * Company-scoped client report data. Every query is bound to one company id.
 */

function validReportDate($value) {
    $value = trim((string) $value);
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function buildClientReport(Database $db, $companyId, $from, $to, $userId = null, $recordHistory = false, array $message = []) {
    $companyId = (int) $companyId;
    $from = validReportDate($from);
    $to = validReportDate($to);

    if ($companyId <= 0 || $from === null || $to === null || $from > $to) {
        return ['ok' => false, 'error' => 'Choose a company and a valid date range.'];
    }

    $company = $db->fetch(
        "SELECT c.id, c.company_name, c.rc_number, c.email, c.phone, c.status, c.subscription_status,
                c.address, c.city, c.state, c.client_since, i.name AS industry_name, u.name AS assigned_staff
         FROM companies c
         LEFT JOIN industries i ON c.industry_id = i.id
         LEFT JOIN users u ON c.assigned_staff_id = u.id
         WHERE c.id = ?",
        [$companyId]
    );

    if (!$company) {
        return ['ok' => false, 'error' => 'Company not found.'];
    }

    $rangeStart = $from . ' 00:00:00';
    $rangeEnd = $to . ' 23:59:59';

    $registrations = $db->fetchAll(
        "SELECT r.id, rt.name AS registration_type, r.status, r.remarks, r.documents_missing,
                r.submission_date, r.expiry_date, r.created_at
         FROM registrations r
         JOIN registration_types rt ON r.registration_type_id = rt.id
         WHERE r.company_id = ? AND r.status <> 'rejected'
         ORDER BY rt.name, r.id",
        [$companyId]
    );

    $communications = $db->fetchAll(
        "SELECT communication_type, subject, summary, communication_date
         FROM communications
         WHERE company_id = ? AND communication_date BETWEEN ? AND ?
         ORDER BY communication_date DESC",
        [$companyId, $rangeStart, $rangeEnd]
    );

    $tasks = $db->fetchAll(
        "SELECT title, status, priority, due_date, created_at
         FROM tasks
         WHERE related_company_id = ? AND created_at BETWEEN ? AND ?
         ORDER BY created_at DESC",
        [$companyId, $rangeStart, $rangeEnd]
    );

    $documents = $db->fetchAll(
        "SELECT id, document_name, document_type, file_name, file_path, file_type, external_url, expiry_date
         FROM documents
         WHERE company_id = ? AND status = 'active'
         ORDER BY created_at DESC",
        [$companyId]
    );

    $invoices = [];
    foreach ($documents as $document) {
        if (($document['document_type'] ?? '') === 'invoice') {
            $invoices[] = $document;
        }
    }

    $elite = $db->fetch(
        "SELECT membership_tier, membership_start_date, membership_end_date, status
         FROM elite_members
         WHERE company_id = ?
         ORDER BY FIELD(status, 'active', 'expired', 'suspended', 'cancelled'), membership_start_date DESC
         LIMIT 1",
        [$companyId]
    );

    foreach ($registrations as &$registration) {
        $registration['generated_phrase'] = clientReportStatusPhrase($registration);
        $registration['display_phrase'] = $registration['generated_phrase'];
    }
    unset($registration);

    $note = trim((string) ($message['note'] ?? ''));
    $lineOverrides = is_array($message['lines'] ?? null) ? $message['lines'] : [];
    foreach ($registrations as &$registration) {
        $override = trim((string) ($lineOverrides[$registration['id']] ?? $lineOverrides[(string) $registration['id']] ?? ''));
        if ($override !== '') {
            $registration['display_phrase'] = $override;
        }
    }
    unset($registration);

    $invoice = null;
    $invoiceId = (int) ($message['invoice_document_id'] ?? 0);
    if ($invoiceId > 0) {
        foreach ($invoices as $candidate) {
            if ((int) $candidate['id'] === $invoiceId) {
                $invoice = $candidate;
                break;
            }
        }
    }

    $report = [
        'ok' => true,
        'company' => $company,
        'from' => $from,
        'to' => $to,
        'reference' => '',
        'registrations' => $registrations,
        'communications' => $communications,
        'tasks' => $tasks,
        'documents' => $documents,
        'invoices' => $invoices,
        'invoice' => $invoice,
        'note' => $note,
        'elite' => $elite ?: null,
    ];

    if ($recordHistory) {
        $historyId = (int) $db->insert('client_reports', [
            'company_id' => $companyId,
            'report_reference' => 'pending-' . bin2hex(random_bytes(6)),
            'period_start' => $from,
            'period_end' => $to,
            'status' => 'generated',
            'message_body' => renderEliteMessageText($report),
            'invoice_document_id' => $invoice ? (int) $invoice['id'] : null,
            'generated_by' => $userId ? (int) $userId : null,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
        $report['reference'] = 'CR-' . date('Ymd') . '-' . $historyId;
        $db->update('client_reports', ['report_reference' => $report['reference']], 'id = :id', ['id' => $historyId]);
    }

    return $report;
}

function clientReportStatusPhrase(array $row) {
    $remarks = trim((string) ($row['remarks'] ?? ''));
    if ($remarks !== '') {
        return $remarks;
    }

    $missing = trim((string) ($row['documents_missing'] ?? ''));
    $status = (string) ($row['status'] ?? '');
    if ($missing !== '') {
        return 'Awaiting ' . $missing;
    }

    $phrases = [
        'pending' => 'Pending',
        'awaiting_documents' => 'Ongoing – Awaiting outstanding client documentation',
        'submitted' => 'Submitted',
        'processing' => 'Ongoing – Processing',
        'approved' => 'Completed',
        'expired' => 'Expired',
        'renewal_required' => 'Renewal required',
    ];

    return $phrases[$status] ?? ucwords(str_replace('_', ' ', $status));
}

function clientReportLineText(array $row) {
    $phrase = trim((string) ($row['display_phrase'] ?? ''));
    return $phrase !== '' ? $phrase : clientReportStatusPhrase($row);
}

function isInvoiceImage(array $document) {
    $extension = strtolower((string) ($document['file_type'] ?? pathinfo((string) ($document['file_name'] ?? ''), PATHINFO_EXTENSION)));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && !empty($document['file_path']);
}

function clientReportCsvRows(array $report) {
    $company = $report['company']['company_name'];
    $rows = [
        ['section' => 'Message', 'item' => 'Company', 'detail' => $company],
    ];

    if (empty($report['registrations'])) {
        $rows[] = ['section' => 'Registration', 'item' => '', 'detail' => 'No registrations to report.'];
    }
    foreach ($report['registrations'] as $row) {
        $rows[] = [
            'section' => 'Registration',
            'item' => $row['registration_type'],
            'detail' => clientReportLineText($row),
        ];
    }

    $rows[] = ['section' => 'Login', 'item' => 'Link', 'detail' => 'https://www.corporate-setup.app/login'];
    $rows[] = ['section' => 'Contact', 'item' => 'Email', 'detail' => 'compliance@corporate-setup.com'];
    $rows[] = ['section' => 'Contact', 'item' => 'Phone', 'detail' => '+234 702 560 0065'];

    return $rows;
}

function renderEliteMessageText(array $report) {
    $name = (string) $report['company']['company_name'];
    $lines = [
        '## ' . $name,
        '',
        'Dear Valued Elite,',
        '',
        'This is an official compliance update from Corporate-setup.app, your digital compliance management platform powered by Corporate Setup.',
        '',
        'Kindly review your company status below:',
        '',
        'Company: ' . $name,
        '',
    ];

    if (empty($report['registrations'])) {
        $lines[] = 'No registrations to report.';
    } else {
        foreach ($report['registrations'] as $row) {
            $lines[] = $row['registration_type'] . ': ' . clientReportLineText($row);
        }
    }

    if (!empty($report['note'])) {
        $lines[] = '';
        $lines[] = 'Note: ' . $report['note'];
    }

    if (!empty($report['invoice'])) {
        $lines[] = '';
        $lines[] = 'Invoice: ' . $report['invoice']['document_name'];
    }

    $lines[] = '';
    $lines[] = 'Login Details:';
    $lines[] = 'Link: https://www.corporate-setup.app/login';
    $lines[] = '';
    $lines[] = 'For more information about our services, please visit: corporate-setup.app';
    $lines[] = '';
    $lines[] = 'Contact us at: compliance@corporate-setup.com or +234 702 560 0065';
    $lines[] = '';
    $lines[] = 'Kind regards,';
    $lines[] = 'Corporate Setup Team';

    return implode("\n", $lines);
}

function renderEliteMessagesText(array $reports) {
    $chunks = ['# Elite Monthly Messages', ''];
    foreach ($reports as $index => $report) {
        if ($index > 0) {
            $chunks[] = '';
            $chunks[] = '──────────────────────────────';
            $chunks[] = '';
        }
        $chunks[] = renderEliteMessageText($report);
    }

    return implode("\n", $chunks) . "\n";
}

function renderEliteMessageHtml(array $report) {
    $esc = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    $name = (string) $report['company']['company_name'];
    $html = '<article class="elite-message">';
    $html .= '<h2>' . $esc($name) . '</h2>';
    $html .= '<p>Dear Valued Elite,</p>';
    $html .= '<p>This is an official compliance update from Corporate-setup.app, your digital compliance management platform powered by Corporate Setup.</p>';
    $html .= '<p>Kindly review your company status below:</p>';
    $html .= '<p><strong>Company:</strong> ' . $esc($name) . '</p>';

    if (empty($report['registrations'])) {
        $html .= '<p class="letter-empty">No registrations to report.</p>';
    } else {
        $html .= '<table class="letter-table"><thead><tr><th>Service</th><th>Status</th></tr></thead><tbody>';
        foreach ($report['registrations'] as $row) {
            $html .= '<tr><td class="service">' . $esc($row['registration_type']) . '</td><td class="status"><span class="line-text">' . $esc(clientReportLineText($row)) . '</span></td></tr>';
        }
        $html .= '</tbody></table>';
    }

    if (!empty($report['note'])) {
        $html .= '<p class="letter-note"><strong>Note:</strong> <span class="note-text">' . $esc($report['note']) . '</span></p>';
    } else {
        $html .= '<p class="letter-note" hidden><strong>Note:</strong> <span class="note-text"></span></p>';
    }

    if (!empty($report['invoice'])) {
        $invoice = $report['invoice'];
        if (isInvoiceImage($invoice)) {
            $html .= '<p class="letter-invoice"><img src="uploads/' . $esc($invoice['file_path']) . '" alt="' . $esc($invoice['document_name']) . '" style="max-width:100%;height:auto;"></p>';
        } elseif (!empty($invoice['external_url'])) {
            $html .= '<p class="letter-invoice"><strong>Invoice:</strong> <a href="' . $esc($invoice['external_url']) . '" target="_blank" rel="noopener noreferrer">' . $esc($invoice['document_name']) . '</a></p>';
        } elseif (!empty($invoice['file_path'])) {
            $html .= '<p class="letter-invoice"><strong>Invoice:</strong> <a href="uploads/' . $esc($invoice['file_path']) . '" target="_blank" rel="noopener noreferrer">' . $esc($invoice['document_name']) . '</a></p>';
        }
    }

    $html .= '<p><strong>Login Details:</strong><br><strong>Link:</strong> <a href="https://www.corporate-setup.app/login">https://www.corporate-setup.app/login</a></p>';
    $html .= '<p>For more information about our services, please visit: corporate-setup.app</p>';
    $html .= '<p>Contact us at: <a href="mailto:compliance@corporate-setup.com">compliance@corporate-setup.com</a> or +234 702 560 0065</p>';
    $html .= '<p>Kind regards,<br><strong>Corporate Setup Team</strong></p>';
    $html .= '</article>';

    return $html;
}

function renderEliteMessagesHtml(array $reports) {
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Elite Monthly Messages</title>';
    $logoUrl = function_exists('appUrl') ? appUrl('assets/images/cslogo.png') : 'assets/images/cslogo.png';
    $html .= '<style>
        body { font-family: Georgia, serif; color: #1a1a1a; margin: 0; line-height: 1.45; background: #f4f7f5; font-size: 16px; }
        h1 { font-size: 26px; margin: 0; font-weight: 700; line-height: 1.2; }
        .sheet { max-width: 820px; margin: 24px auto; background: #fff; }
        .sheet-header { background: linear-gradient(135deg, #0e7a45 0%, #14915a 100%); color: #fff; padding: 22px 32px; display: flex; align-items: center; gap: 16px; }
        .sheet-header img { width: 64px; height: 64px; object-fit: contain; background: #fff; border-radius: 8px; padding: 4px; }
        .sheet-header p { margin: 4px 0 0; font-size: 15px; opacity: 0.95; }
        .sheet-body { padding: 28px 32px 36px; }
        h2 { font-size: 22px; margin: 0 0 12px; font-weight: 700; line-height: 1.3; }
        p { font-size: 16px; margin: 0 0 12px; }
        article { page-break-inside: avoid; }
        .letter-table { width: 100%; border-collapse: collapse; margin: 8px 0 18px; font-size: 15px; }
        .letter-table th { background: #0e7a45; color: #fff; text-align: left; font-weight: 700; padding: 10px 12px; }
        .letter-table td { padding: 10px 12px; vertical-align: top; border-bottom: 1px solid #e3efe8; }
        .letter-table tbody tr:nth-child(odd) { background: #fff; }
        .letter-table tbody tr:nth-child(even) { background: #f3faf6; }
        .letter-table td.service { font-weight: 700; width: 42%; }
        .letter-table td.status { font-weight: 400; }
        .letter-empty { font-size: 16px; }
        .divider { border: 0; border-top: 1px solid #d5e5db; margin: 28px 0; }
        a { color: #0e7a45; }
        .letter-invoice img { max-width: 100%; height: auto; border-radius: 8px; }
        @media print { body { background: #fff; } .sheet { margin: 0; } }
    </style></head><body>';
    $html .= '<div class="sheet"><header class="sheet-header"><img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Corporate Setup"><div><h1>Elite Monthly Messages</h1><p>Corporate Setup</p></div></header><div class="sheet-body">';
    foreach ($reports as $index => $report) {
        if ($index > 0) {
            $html .= '<hr class="divider">';
        }
        $html .= renderEliteMessageHtml($report);
    }
    $html .= '</div></div></body></html>';

    return $html;
}

function renderClientReportHtml(array $report) {
    return renderEliteMessagesHtml([$report]);
}

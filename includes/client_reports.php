<?php
/**
 * Company-scoped client report data. Every query is bound to one company id.
 */

function validReportDate($value) {
    $value = trim((string) $value);
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function buildClientReport(Database $db, $companyId, $from, $to, $userId = null, $recordHistory = false) {
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
        "SELECT document_name, document_type, file_name, external_url, expiry_date
         FROM documents
         WHERE company_id = ? AND status = 'active'
         ORDER BY created_at DESC",
        [$companyId]
    );

    $elite = $db->fetch(
        "SELECT membership_tier, membership_start_date, membership_end_date, status
         FROM elite_members
         WHERE company_id = ?
         ORDER BY FIELD(status, 'active', 'expired', 'suspended', 'cancelled'), membership_start_date DESC
         LIMIT 1",
        [$companyId]
    );

    $reference = '';
    if ($recordHistory) {
        $historyId = (int) $db->insert('client_reports', [
            'company_id' => $companyId,
            'report_reference' => 'pending-' . bin2hex(random_bytes(6)),
            'period_start' => $from,
            'period_end' => $to,
            'status' => 'generated',
            'generated_by' => $userId ? (int) $userId : null,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
        $reference = 'CR-' . date('Ymd') . '-' . $historyId;
        $db->update('client_reports', ['report_reference' => $reference], 'id = :id', ['id' => $historyId]);
    }

    return [
        'ok' => true,
        'company' => $company,
        'from' => $from,
        'to' => $to,
        'reference' => $reference,
        'registrations' => $registrations,
        'communications' => $communications,
        'tasks' => $tasks,
        'documents' => $documents,
        'elite' => $elite ?: null,
    ];
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
            'detail' => clientReportStatusPhrase($row),
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
            $lines[] = $row['registration_type'] . ': ' . clientReportStatusPhrase($row);
        }
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
        $html .= '<p>No registrations to report.</p>';
    } else {
        foreach ($report['registrations'] as $row) {
            $html .= '<p><strong>' . $esc($row['registration_type']) . ':</strong> ' . $esc(clientReportStatusPhrase($row)) . '</p>';
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
    $html .= '<style>
        body { font-family: Georgia, serif; color: #1a1a1a; margin: 36px; line-height: 1.5; }
        h1 { font-size: 28px; margin-bottom: 28px; }
        h2 { font-size: 22px; margin-bottom: 8px; }
        article { page-break-inside: avoid; margin-bottom: 12px; }
        .divider { border: 0; border-top: 1px solid #333; margin: 28px 0; }
        a { color: #0f2942; }
        @media print { body { margin: 12mm; } }
    </style></head><body>';
    $html .= '<h1>Elite Monthly Messages</h1>';
    foreach ($reports as $index => $report) {
        if ($index > 0) {
            $html .= '<hr class="divider">';
        }
        $html .= renderEliteMessageHtml($report);
    }
    $html .= '</body></html>';

    return $html;
}

function renderClientReportHtml(array $report) {
    return renderEliteMessagesHtml([$report]);
}

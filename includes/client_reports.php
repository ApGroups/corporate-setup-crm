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
        "SELECT r.id, rt.name AS registration_type, r.status, r.submission_date, r.expiry_date, r.created_at
         FROM registrations r
         JOIN registration_types rt ON r.registration_type_id = rt.id
         WHERE r.company_id = ? AND r.created_at BETWEEN ? AND ?
         ORDER BY r.created_at DESC",
        [$companyId, $rangeStart, $rangeEnd]
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

function clientReportCsvRows(array $report) {
    $company = $report['company'];
    $rows = [
        ['section' => 'Company', 'item' => 'Name', 'detail' => $company['company_name'], 'extra' => $company['status']],
        ['section' => 'Company', 'item' => 'RC number', 'detail' => $company['rc_number'] ?? '', 'extra' => $company['subscription_status']],
        ['section' => 'Company', 'item' => 'Contact', 'detail' => trim(($company['email'] ?? '') . ' ' . ($company['phone'] ?? '')), 'extra' => $company['assigned_staff'] ?? ''],
        ['section' => 'Period', 'item' => 'Range', 'detail' => $report['from'] . ' to ' . $report['to'], 'extra' => $report['reference']],
    ];

    if (empty($report['registrations'])) {
        $rows[] = ['section' => 'Registrations', 'item' => '', 'detail' => 'No registrations in this period.', 'extra' => ''];
    }
    foreach ($report['registrations'] as $row) {
        $rows[] = ['section' => 'Registrations', 'item' => $row['registration_type'], 'detail' => $row['status'], 'extra' => $row['created_at']];
    }

    if (empty($report['communications'])) {
        $rows[] = ['section' => 'Communications', 'item' => '', 'detail' => 'No communications in this period.', 'extra' => ''];
    }
    foreach ($report['communications'] as $row) {
        $rows[] = ['section' => 'Communications', 'item' => $row['subject'] ?? $row['communication_type'], 'detail' => $row['summary'], 'extra' => $row['communication_date']];
    }

    if (empty($report['tasks'])) {
        $rows[] = ['section' => 'Tasks', 'item' => '', 'detail' => 'No tasks in this period.', 'extra' => ''];
    }
    foreach ($report['tasks'] as $row) {
        $rows[] = ['section' => 'Tasks', 'item' => $row['title'], 'detail' => $row['status'], 'extra' => $row['due_date']];
    }

    if (empty($report['documents'])) {
        $rows[] = ['section' => 'Documents', 'item' => '', 'detail' => 'No active documents.', 'extra' => ''];
    }
    foreach ($report['documents'] as $row) {
        $rows[] = ['section' => 'Documents', 'item' => $row['document_name'], 'detail' => $row['external_url'] ?: ($row['file_name'] ?? ''), 'extra' => $row['document_type']];
    }

    if (empty($report['elite'])) {
        $rows[] = ['section' => 'Elite', 'item' => '', 'detail' => 'No elite membership.', 'extra' => ''];
    } else {
        $elite = $report['elite'];
        $rows[] = ['section' => 'Elite', 'item' => $elite['membership_tier'], 'detail' => $elite['status'], 'extra' => $elite['membership_start_date']];
    }

    return $rows;
}

function renderClientReportHtml(array $report) {
    $company = $report['company'];
    $esc = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    $section = function ($title, $emptyText, $headers, $bodyRows) use ($esc) {
        $html = '<section class="section"><h2>' . $esc($title) . '</h2>';
        if (empty($bodyRows)) {
            return $html . '<p class="empty">' . $esc($emptyText) . '</p></section>';
        }
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $esc($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($bodyRows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $esc($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></section>';
    };

    $registrationRows = [];
    foreach ($report['registrations'] as $row) {
        $registrationRows[] = [$row['registration_type'], $row['status'], $row['submission_date'] ?: '-', $row['expiry_date'] ?: '-'];
    }
    $communicationRows = [];
    foreach ($report['communications'] as $row) {
        $communicationRows[] = [$row['communication_date'], $row['communication_type'], $row['subject'] ?: '-', $row['summary']];
    }
    $taskRows = [];
    foreach ($report['tasks'] as $row) {
        $taskRows[] = [$row['title'], $row['status'], $row['priority'], $row['due_date'] ?: '-'];
    }
    $documentRows = [];
    foreach ($report['documents'] as $row) {
        $documentRows[] = [$row['document_name'], $row['document_type'], $row['external_url'] ?: ($row['file_name'] ?: 'File'), $row['expiry_date'] ?: '-'];
    }
    $eliteRows = [];
    if (!empty($report['elite'])) {
        $elite = $report['elite'];
        $eliteRows[] = [$elite['membership_tier'], $elite['status'], $elite['membership_start_date'], $elite['membership_end_date'] ?: '-'];
    }

    $address = trim(implode(', ', array_filter([$company['address'] ?? '', $company['city'] ?? '', $company['state'] ?? ''])));

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . $esc($report['reference'] ?: 'Client Report') . '</title>';
    $html .= '<style>
        body { font-family: Arial, sans-serif; color: #0f2942; margin: 32px; }
        h1 { margin: 0 0 4px; font-size: 24px; }
        h2 { font-size: 16px; border-bottom: 2px solid #0f2942; padding-bottom: 4px; }
        .meta { color: #445; margin-bottom: 8px; }
        .section { margin-top: 28px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d5dde6; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #0f2942; color: #fff; }
        .empty { color: #667; }
        @media print { body { margin: 12mm; } }
    </style></head><body>';
    $html .= '<header><h1>Corporate Setup Limited</h1><p class="meta">Client Report</p>';
    $html .= '<p class="meta"><strong>' . $esc($company['company_name']) . '</strong><br>';
    $html .= 'Period: ' . $esc($report['from']) . ' to ' . $esc($report['to']) . '<br>';
    $html .= 'Reference: ' . $esc($report['reference'] ?: 'Preview') . '</p></header>';
    $html .= '<section class="section"><h2>Company profile</h2><table><tbody>';
    $profile = [
        'Status' => $company['status'],
        'RC number' => $company['rc_number'] ?: '-',
        'Industry' => $company['industry_name'] ?: '-',
        'Subscription' => $company['subscription_status'],
        'Email' => $company['email'] ?: '-',
        'Phone' => $company['phone'] ?: '-',
        'Address' => $address !== '' ? $address : '-',
        'Client since' => $company['client_since'] ?: '-',
        'Assigned staff' => $company['assigned_staff'] ?: '-',
    ];
    foreach ($profile as $label => $value) {
        $html .= '<tr><th>' . $esc($label) . '</th><td>' . $esc($value) . '</td></tr>';
    }
    $html .= '</tbody></table></section>';
    $html .= $section('Registrations', 'No registrations in this period.', ['Type', 'Status', 'Submitted', 'Expiry'], $registrationRows);
    $html .= $section('Communications', 'No communications in this period.', ['Date', 'Type', 'Subject', 'Summary'], $communicationRows);
    $html .= $section('Tasks', 'No tasks in this period.', ['Task', 'Status', 'Priority', 'Due'], $taskRows);
    $html .= $section('Active documents', 'No active documents.', ['Name', 'Type', 'File or link', 'Expiry'], $documentRows);
    $html .= $section('Elite membership', 'No elite membership.', ['Tier', 'Status', 'Start', 'End'], $eliteRows);
    $html .= '</body></html>';
    return $html;
}

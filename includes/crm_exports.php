<?php
/**
 * Shared export queries. Dates filter companies, registrations, tasks, and documents by created_at.
 */

function exportCompanies(Database $db, $from, $to) {
    return $db->fetchAll(
        "SELECT c.company_name, c.rc_number, i.name AS industry_name, c.email, c.phone, c.status,
                c.subscription_status, c.client_since, u.name AS assigned_staff
         FROM companies c
         LEFT JOIN industries i ON c.industry_id = i.id
         LEFT JOIN users u ON c.assigned_staff_id = u.id
         WHERE c.created_at BETWEEN ? AND ?
         ORDER BY c.company_name",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function exportRegistrations(Database $db, $from, $to) {
    return $db->fetchAll(
        "SELECT c.company_name, rt.name AS registration_type, r.status, r.priority,
                r.submission_date, r.expiry_date, u.name AS assigned_staff, r.remarks
         FROM registrations r
         JOIN companies c ON r.company_id = c.id
         JOIN registration_types rt ON r.registration_type_id = rt.id
         LEFT JOIN users u ON r.assigned_staff_id = u.id
         WHERE r.created_at BETWEEN ? AND ?
         ORDER BY r.created_at DESC",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function exportTasks(Database $db, $from, $to) {
    return $db->fetchAll(
        "SELECT t.title, t.task_type, t.status, t.priority, t.due_date,
                (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
                 FROM task_assignees ta2
                 JOIN users u2 ON u2.id = ta2.user_id
                 WHERE ta2.task_id = t.id) AS assigned_to,
                t.created_at, t.completed_at
         FROM tasks t
         WHERE t.created_at BETWEEN ? AND ?
         ORDER BY t.created_at DESC",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function exportCompliance(Database $db) {
    return $db->fetchAll(
        "SELECT c.company_name, rt.name AS registration_type,
                r.status, r.expiry_date, DATEDIFF(r.expiry_date, CURDATE()) AS days_remaining,
                u.name AS assigned_staff
         FROM registrations r
         JOIN companies c ON r.company_id = c.id
         JOIN registration_types rt ON r.registration_type_id = rt.id
         LEFT JOIN users u ON r.assigned_staff_id = u.id
         WHERE r.status = 'approved' AND r.expiry_date IS NOT NULL
         ORDER BY r.expiry_date ASC"
    );
}

function exportElites(Database $db, $search, $status) {
    [$where, $params] = eliteMembershipWhere($search, $status);
    return $db->fetchAll(
        "SELECT COALESCE(c.company_name, '') AS company_name, em.membership_tier,
                em.membership_start_date, em.membership_end_date, em.status,
                u.name AS account_manager
         FROM elite_members em
         LEFT JOIN companies c ON em.company_id = c.id
         LEFT JOIN users u ON em.assigned_account_manager = u.id
         WHERE {$where}
         ORDER BY em.membership_start_date DESC",
        $params
    );
}

function exportDocuments(Database $db, $from, $to) {
    return $db->fetchAll(
        "SELECT d.document_name, d.document_type, c.company_name, d.file_name,
                d.external_url, d.expiry_date, d.status, d.created_at
         FROM documents d
         LEFT JOIN companies c ON d.company_id = c.id
         WHERE d.created_at BETWEEN ? AND ?
         ORDER BY d.created_at DESC",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function exportSummary(Database $db) {
    return $db->fetchAll(
        "SELECT
            (SELECT COUNT(*) FROM companies WHERE status != 'churned') AS total_companies,
            (SELECT COUNT(*) FROM companies WHERE status = 'active') AS active_clients,
            (SELECT COUNT(*) FROM registrations WHERE status IN ('pending', 'submitted', 'processing')) AS pending_registrations,
            (SELECT COUNT(*) FROM registrations WHERE status = 'approved') AS approved_registrations,
            (SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')) AS pending_tasks,
            (SELECT COUNT(*) FROM tasks WHERE status = 'completed') AS completed_tasks"
    );
}

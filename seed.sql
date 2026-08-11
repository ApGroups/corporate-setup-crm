-- Corporate Setup Limited - CRM Seed Data
-- Run this after crm.sql

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Default Roles
-- --------------------------------------------------------
INSERT INTO `roles` (`name`, `display_name`, `description`, `permissions`, `is_system`) VALUES
('Administrator', 'Administrator', 'Full system access', '["*"]', 1),
('Compliance', 'Compliance Officer', 'Manages compliance and regulatory matters', '["view_companies","view_registrations","manage_registrations","view_documents","manage_documents","view_compliance","manage_compliance","view_tasks","manage_tasks","view_reports","export_data","view_elite"]', 1),
('Business Development', 'Business Development', 'Manages client relationships and opportunities', '["view_companies","manage_companies","view_contacts","manage_contacts","view_communications","manage_communications","view_opportunities","manage_opportunities","view_tasks","manage_tasks","view_meetings","manage_meetings","submit_reports"]', 1),
('Operations', 'Operations', 'Handles daily operations', '["view_companies","view_registrations","view_tasks","manage_tasks","view_documents","upload_documents","submit_reports","view_calendar","view_reports"]', 1),
('Legal', 'Legal Officer', 'Manages legal matters and contracts', '["view_companies","view_documents","manage_documents","view_tasks","manage_tasks","view_compliance","manage_compliance","view_contracts"]', 1),
('IT', 'IT Support', 'Technical support and system management', '["view_settings","manage_settings","view_users","manage_users","view_logs","export_data","backup_system"]', 1),
('Marketing', 'Marketing', 'Marketing and communications', '["view_companies","view_contacts","manage_contacts","view_tasks","manage_tasks","view_meetings","manage_meetings","submit_reports"]', 1),
('Management', 'Management', 'Executive oversight', '["view_companies","view_registrations","view_tasks","view_reports","view_users","view_compliance","view_opportunities","approve_reports","export_data","view_elite","submit_reports","view_logs","view_calendar","view_settings"]', 1);

-- --------------------------------------------------------
-- Default Departments
-- --------------------------------------------------------
INSERT INTO `departments` (`name`, `description`, `status`) VALUES
('Administration', 'Corporate administration and management', 'active'),
('Compliance', 'Regulatory compliance and registration', 'active'),
('Business Development', 'Client acquisition and relationship management', 'active'),
('Operations', 'Daily business operations', 'active'),
('Legal', 'Legal affairs and contracts', 'active'),
('IT', 'Information Technology', 'active'),
('Marketing', 'Marketing and brand management', 'active'),
('Finance', 'Financial management', 'active');

-- --------------------------------------------------------
-- Default Admin User
-- Password: Admin@123 (change immediately after first login)
-- --------------------------------------------------------
INSERT INTO `users` (`employee_id`, `name`, `email`, `password`, `phone`, `role_id`, `department_id`, `position`, `status`, `must_change_password`) VALUES
('CSL-001', 'System Administrator', 'admin@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-801-234-5678', 1, 1, 'System Administrator', 'active', 1),
('CSL-002', 'John Okonkwo', 'john.okonkwo@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-802-345-6789', 2, 2, 'Head of Compliance', 'active', 0),
('CSL-003', 'Mary Ibrahim', 'mary.ibrahim@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-803-456-7890', 3, 3, 'Business Development Manager', 'active', 0),
('CSL-004', 'Peter Adeyemi', 'peter.adeyemi@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-804-567-8901', 4, 4, 'Operations Manager', 'active', 0),
('CSL-005', 'Grace Nwankwo', 'grace.nwankwo@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-805-678-9012', 5, 5, 'Legal Counsel', 'active', 0),
('CSL-006', 'Emeka Okafor', 'emeka.okafor@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-806-789-0123', 6, 6, 'IT Manager', 'active', 0),
('CSL-007', 'Chioma Eze', 'chioma.eze@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-807-890-1234', 7, 7, 'Marketing Manager', 'active', 0),
('CSL-008', 'David Mohammed', 'david.mohammed@corporatesetup.com', '$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA', '+234-808-901-2345', 8, 1, 'Managing Director', 'active', 0);

-- Update department heads
UPDATE `departments` SET `head_id` = 1 WHERE `name` = 'Administration';
UPDATE `departments` SET `head_id` = 2 WHERE `name` = 'Compliance';
UPDATE `departments` SET `head_id` = 3 WHERE `name` = 'Business Development';
UPDATE `departments` SET `head_id` = 4 WHERE `name` = 'Operations';
UPDATE `departments` SET `head_id` = 5 WHERE `name` = 'Legal';
UPDATE `departments` SET `head_id` = 6 WHERE `name` = 'IT';
UPDATE `departments` SET `head_id` = 7 WHERE `name` = 'Marketing';

-- --------------------------------------------------------
-- Default Industries
-- --------------------------------------------------------
INSERT INTO `industries` (`name`, `description`, `status`) VALUES
('Oil & Gas', 'Petroleum exploration, production, and services', 'active'),
('Banking & Finance', 'Financial institutions and services', 'active'),
('Telecommunications', 'Telecom services and infrastructure', 'active'),
('Manufacturing', 'Industrial manufacturing', 'active'),
('Construction', 'Building and construction', 'active'),
('Information Technology', 'IT services and software', 'active'),
('Healthcare', 'Medical services and pharmaceuticals', 'active'),
('Education', 'Educational institutions', 'active'),
('Agriculture', 'Agricultural production and services', 'active'),
('Maritime', 'Marine and shipping services', 'active'),
('Power & Energy', 'Power generation and distribution', 'active'),
('Mining', 'Mineral exploration and extraction', 'active'),
('Logistics', 'Transportation and logistics', 'active'),
('Consulting', 'Business consulting services', 'active'),
('Real Estate', 'Property development and management', 'active');

-- --------------------------------------------------------
-- Sample Countries
-- --------------------------------------------------------
INSERT INTO `countries` (`name`, `code`, `phone_code`, `currency`) VALUES
('Nigeria', 'NG', '+234', 'NGN'),
('Ghana', 'GH', '+233', 'GHS'),
('South Africa', 'ZA', '+27', 'ZAR'),
('Kenya', 'KE', '+254', 'KES'),
('United Kingdom', 'GB', '+44', 'GBP'),
('United States', 'US', '+1', 'USD'),
('United Arab Emirates', 'AE', '+971', 'AED'),
('China', 'CN', '+86', 'CNY'),
('India', 'IN', '+91', 'INR'),
('Netherlands', 'NL', '+31', 'EUR');

-- --------------------------------------------------------
-- Default Registration Types
-- --------------------------------------------------------
INSERT INTO `registration_types` (`name`, `category`, `description`, `processing_time`, `validity_period`, `cost`, `renewable`, `status`) VALUES
('NUPRC Registration', 'regulatory', 'Nigerian Upstream Petroleum Regulatory Commission registration for oil and gas companies', 90, 365, 500000, 1, 'active'),
('NCDMB Registration', 'regulatory', 'Nigerian Content Development and Monitoring Board registration', 60, 365, 300000, 1, 'active'),
('NIPEX Registration', 'regulatory', 'Nigerian Petroleum Exchange registration for oil and gas operators', 45, 365, 250000, 1, 'active'),
('Seplat JV Registration', 'corporate', 'Seplat Joint Venture vendor registration', 30, 730, 100000, 1, 'active'),
('NLNG Registration', 'corporate', 'Nigeria LNG Limited vendor registration', 45, 730, 150000, 1, 'active'),
('Aradel Registration', 'corporate', 'Aradel Energy vendor registration', 30, 730, 100000, 1, 'active'),
('Chevron Registration', 'corporate', 'Chevron Nigeria Limited vendor registration', 60, 730, 200000, 1, 'active'),
('Shell Registration', 'corporate', 'Shell Nigeria vendor registration', 60, 730, 200000, 1, 'active'),
('NNPC Registration', 'regulatory', 'Nigerian National Petroleum Company registration', 90, 365, 500000, 1, 'active'),
('NSITF Registration', 'compliance', 'Nigerian Social Insurance Trust Fund registration', 14, 365, 50000, 1, 'active'),
('PENCOM Registration', 'compliance', 'Pension Commission registration for employers', 30, 365, 75000, 1, 'active'),
('ITF Registration', 'compliance', 'Industrial Training Fund registration', 14, 365, 50000, 1, 'active'),
('FIRS Registration', 'compliance', 'Federal Inland Revenue Service tax registration', 7, 365, 25000, 1, 'active'),
('DUNS Number', 'certification', 'Dun & Bradstreet D-U-N-S Number registration', 30, 365, 100000, 1, 'active'),
('ISO 9001 Certification', 'certification', 'Quality Management System certification', 180, 1095, 1500000, 1, 'active'),
('ISO 14001 Certification', 'certification', 'Environmental Management System certification', 180, 1095, 1500000, 1, 'active'),
('ISO 45001 Certification', 'certification', 'Occupational Health and Safety Management System certification', 180, 1095, 1500000, 1, 'active'),
('Marine Vessel Registration', 'regulatory', 'Nigerian Maritime Administration and Safety Agency vessel registration', 90, 365, 500000, 1, 'active'),
('Nigerian Navy Permit', 'regulatory', 'Nigerian Navy operational permit for marine vessels', 60, 365, 300000, 1, 'active'),
('Customs License', 'regulatory', 'Nigeria Customs Service license', 30, 365, 200000, 1, 'active');

-- --------------------------------------------------------
-- Sample Companies
-- --------------------------------------------------------
INSERT INTO `companies` (`company_name`, `rc_number`, `industry_id`, `address`, `city`, `state`, `country_id`, `website`, `phone`, `email`, `contact_person`, `contact_position`, `contact_phone`, `status`, `subscription_status`, `assigned_staff_id`, `client_since`, `is_elite`, `created_by`) VALUES
('PetroTech Nigeria Limited', 'RC 123456', 1, '15 Admiralty Way, Lekki', 'Lagos', 'Lagos', 1, 'www.pettechnig.com', '+234-901-234-5678', 'info@pettechnig.com', 'Ahmed Musa', 'CEO', '+234-901-234-5679', 'active', 'elite', 3, '2022-01-15', 1, 1),
('Ocean Drilling Services Ltd', 'RC 234567', 1, '27 Trans Amadi Road', 'Port Harcourt', 'Rivers', 1, 'www.oceandrillingserv.com', '+234-902-345-6789', 'contact@oceandrillingserv.com', 'Blessing Ojo', 'Operations Director', '+234-902-345-6790', 'active', 'premium', 3, '2022-03-20', 0, 1),
('Greenfield Energy Solutions', 'RC 345678', 1, 'Plot 42, Victoria Island', 'Lagos', 'Lagos', 1, 'www.greenfieldenergy.com.ng', '+234-903-456-7890', 'info@greenfieldenergy.com.ng', 'Chukwudi Eze', 'Managing Director', '+234-903-456-7891', 'active', 'elite', 2, '2021-06-10', 1, 1),
('Atlantic Marine Services', 'RC 456789', 10, '16 Dockyard Road, Apapa', 'Lagos', 'Lagos', 1, 'www.atlanticmarineserv.com', '+234-904-567-8901', 'admin@atlanticmarineserv.com', 'Tunde Bakare', 'General Manager', '+234-904-567-8902', 'active', 'basic', 4, '2023-01-05', 0, 1),
('Niger Delta Engineering Co.', 'RC 567890', 1, '8 Aba Road, GRA', 'Port Harcourt', 'Rivers', 1, 'www.nigerdeltaeng.com', '+234-905-678-9012', 'contact@nigerdeltaeng.com', 'Timi Alaibe', 'Technical Director', '+234-905-678-9013', 'active', 'premium', 3, '2022-08-15', 0, 1),
('TechBridge Solutions Ltd', 'RC 678901', 6, '100 Adeniran Ogunsanya', 'Lagos', 'Lagos', 1, 'www.techbridgesolutions.com', '+234-906-789-0123', 'info@techbridgesolutions.com', 'Ngozi Okonkwo', 'CEO', '+234-906-789-0124', 'active', 'basic', 4, '2023-05-20', 0, 1),
('Delta Steel Construction', 'RC 789012', 5, '5 Ekpan Road, Warri', 'Warri', 'Delta', 1, 'www.deltasteelcon.com', '+234-907-890-1234', 'contact@deltasteelcon.com', 'Emeka Obi', 'Project Manager', '+234-907-890-1235', 'prospect', 'free', NULL, NULL, 0, 1),
('Agro Nigeria Ventures', 'RC 890123', 9, '30 Aminu Kano Crescent', 'Abuja', 'FCT', 1, 'www.agroventng.com', '+234-908-901-2345', 'info@agroventng.com', 'Fatima Abubakar', 'Director', '+234-908-901-2346', 'active', 'basic', 4, '2023-02-10', 0, 1),
('LogiTrans Nigeria Ltd', 'RC 901234', 13, '45 Ladipo Road, Mushin', 'Lagos', 'Lagos', 1, 'www.logitransng.com', '+234-909-012-3456', 'admin@logitransng.com', 'Adebowale Adesanya', 'Logistics Manager', '+234-909-012-3457', 'active', 'premium', 3, '2022-11-01', 0, 1),
('Safety First Consultancy', 'RC 012345', 14, '20 Awolowo Road, Ikoyi', 'Lagos', 'Lagos', 1, 'www.safetyfirstcon.com', '+234-910-123-4567', 'info@safetyfirstcon.com', 'Olumide Dansu', 'Lead Consultant', '+234-910-123-4568', 'active', 'elite', 2, '2021-03-15', 1, 1);

-- --------------------------------------------------------
-- Sample Registrations
-- --------------------------------------------------------
INSERT INTO `registrations` (`company_id`, `registration_type_id`, `status`, `priority`, `assigned_staff_id`, `submission_date`, `expected_completion_date`, `expiry_date`, `created_by`) VALUES
(1, 1, 'approved', 'high', 2, '2022-02-01', '2022-04-15', '2026-07-02', 1),
(1, 2, 'approved', 'high', 2, '2022-02-15', '2022-04-01', '2026-07-02', 1),
(1, 3, 'approved', 'medium', 2, '2022-03-01', '2022-04-15', '2026-07-02', 1),
(2, 1, 'approved', 'high', 2, '2022-04-01', '2022-06-15', '2025-07-02', 1),
(2, 4, 'submitted', 'medium', 3, '2024-05-01', '2024-06-15', NULL, 1),
(3, 5, 'approved', 'high', 2, '2021-07-01', '2021-09-01', '2025-07-02', 1),
(3, 15, 'processing', 'high', 2, '2024-01-15', '2024-07-15', NULL, 1),
(4, 18, 'approved', 'high', 2, '2023-02-01', '2023-05-01', '2024-02-01', 1),
(4, 19, 'pending', 'medium', 2, NULL, NULL, NULL, 1),
(5, 1, 'approved', 'high', 2, '2022-09-01', '2022-11-15', '2025-09-01', 1),
(5, 6, 'approved', 'medium', 3, '2022-09-15', '2022-10-30', '2024-09-15', 1),
(6, 11, 'pending', 'medium', 2, NULL, NULL, NULL, 2),
(6, 12, 'pending', 'medium', 2, NULL, NULL, NULL, 2),
(7, 1, 'pending', 'low', 3, NULL, NULL, NULL, 1),
(8, 11, 'approved', 'low', 2, '2023-03-01', '2023-03-15', '2024-03-01', 1),
(9, 13, 'approved', 'low', 2, '2022-11-15', '2022-11-30', '2023-11-15', 1),
(10, 15, 'approved', 'high', 2, '2021-04-01', '2021-10-01', '2024-04-01', 1),
(10, 16, 'processing', 'high', 2, '2023-06-01', '2023-12-01', NULL, 1);

-- --------------------------------------------------------
-- Sample Tasks
-- --------------------------------------------------------
INSERT INTO `tasks` (`title`, `description`, `task_type`, `related_company_id`, `related_registration_id`, `assigned_to`, `assigned_by`, `priority`, `status`, `due_date`, `progress`) VALUES
('Follow up on NUPRC registration', 'Send required documents to NUPRC for PetroTech registration renewal', 'registration', 1, 1, 2, 1, 'high', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 60),
('Client meeting - Ocean Drilling', 'Quarterly review meeting with Ocean Drilling Services', 'client', 2, NULL, 3, 1, 'medium', 'pending', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0),
('Submit NSITF documents', 'Collect and submit NSITF compliance documents for TechBridge', 'compliance', 6, 12, 2, 8, 'medium', 'pending', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 30),
('ISO certification audit preparation', 'Prepare all documentation for upcoming ISO 9001 audit', 'compliance', 10, 17, 2, 1, 'high', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 75),
('Marine vessel registration renewal', 'Process renewal for Atlantic Marine vessel permit', 'registration', 4, 9, 2, 1, 'high', 'pending', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 20),
('New client onboarding', 'Complete onboarding process for Delta Steel Construction', 'client', 7, NULL, 3, 8, 'medium', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 40),
('Weekly compliance report', 'Submit weekly compliance status report', 'admin', NULL, NULL, 2, 1, 'medium', 'in_progress', CURDATE(), 80),
('Elite member monthly check-in', 'Monthly status check with Greenfield Energy', 'client', 3, NULL, 3, 1, 'medium', 'pending', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 0);

-- --------------------------------------------------------
-- Sample Meetings
-- --------------------------------------------------------
INSERT INTO `meetings` (`title`, `meeting_type`, `company_id`, `organizer_id`, `location`, `meeting_date`, `start_time`, `end_time`, `agenda`, `status`) VALUES
('Q2 Business Review', 'client', 1, 3, 'PetroTech Head Office, Lekki', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', '12:00:00', 'Review Q2 performance and discuss upcoming registrations', 'scheduled'),
('Compliance Team Meeting', 'internal', NULL, 2, 'Conference Room A', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:00:00', 'Weekly compliance team sync', 'scheduled'),
('NUPRC Submission', 'regulator', 4, 2, 'NUPRC Office, Abuja', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', '16:00:00', 'Submit vessel registration documents', 'scheduled'),
('Elite Member Review', 'client', 3, 3, 'Virtual - Zoom', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '11:00:00', '12:00:00', 'Quarterly elite member status review', 'scheduled');

-- Meeting participants
INSERT INTO `meeting_participants` (`meeting_id`, `user_id`, `attendance_status`) VALUES
(1, 3, 'accepted'),
(1, 2, 'accepted'),
(2, 2, 'accepted'),
(2, 4, 'accepted'),
(3, 2, 'accepted'),
(4, 3, 'accepted'),
(4, 2, 'accepted');

-- --------------------------------------------------------
-- Sample Elite Members
-- --------------------------------------------------------
INSERT INTO `elite_members` (`company_id`, `membership_tier`, `membership_start_date`, `membership_end_date`, `status`, `assigned_account_manager`) VALUES
(1, 'elite', '2024-01-01', '2024-12-31', 'active', 3),
(3, 'platinum', '2024-01-01', '2024-12-31', 'active', 2),
(10, 'elite', '2024-03-01', '2025-02-28', 'active', 3);

-- --------------------------------------------------------
-- Sample Opportunities
-- --------------------------------------------------------
INSERT INTO `opportunities` (`opportunity_title`, `opportunity_type`, `source`, `description`, `company_id`, `assigned_staff_id`, `estimated_value`, `deadline`, `status`, `probability`, `created_by`) VALUES
('Shell Vendor Opportunity 2024', 'oil_gas', 'Shell Nigeria', 'Shell Nigeria vendor registration for marine services', 4, 3, 5000000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pursuing', 60, 1),
('NLNG Logistics Contract', 'oil_gas', 'NLNG Limited', 'NLNG is seeking logistics partners for 2024-2025', NULL, 3, 10000000, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'new', 40, 1),
('Government Power Project', 'government_tender', 'Federal Ministry of Power', 'Tender for power infrastructure consulting', NULL, 4, 2500000, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'new', 30, 1);

-- --------------------------------------------------------
-- Sample Settings
-- --------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'Corporate Setup Limited', 'string', 'Company name displayed in the application'),
('company_email', 'info@corporatesetup.com', 'string', 'Company email address'),
('company_phone', '+234-800-CORPSETUP', 'string', 'Company phone number'),
('company_address', '15 Admiralty Way, Lekki, Lagos', 'string', 'Company address'),
('currency', 'NGN', 'string', 'Default currency'),
('date_format', 'd-m-Y', 'string', 'Default date format'),
('timezone', 'Africa/Lagos', 'string', 'Application timezone'),
('items_per_page', '20', 'integer', 'Default pagination items per page'),
('session_timeout', '1800', 'integer', 'Session timeout in seconds'),
('max_file_upload', '10485760', 'integer', 'Maximum file upload size in bytes'),
('enable_notifications', '1', 'boolean', 'Enable email notifications'),
('enable_daily_reports', '1', 'boolean', 'Enable daily activity reports'),
('logo_url', '/assets/images/logo.png', 'string', 'Company logo URL');

-- --------------------------------------------------------
-- Sample Notification Templates
-- --------------------------------------------------------
INSERT INTO `notification_templates` (`name`, `subject`, `body`, `type`, `variables`, `is_active`) VALUES
('task_assigned', 'New Task Assigned', 'You have been assigned a new task: {task_title}. Due: {due_date}', 'in_app', '["task_title","due_date"]', 1),
('registration_due', 'Registration Due Soon', 'Registration for {company_name} - {registration_type} is due on {due_date}', 'in_app', '["company_name","registration_type","due_date"]', 1),
('document_expiring', 'Document Expiring', 'Document {document_name} for {company_name} expires on {expiry_date}', 'in_app', '["document_name","company_name","expiry_date"]', 1),
('meeting_reminder', 'Meeting Reminder', 'You have a meeting tomorrow: {meeting_title} at {meeting_time}', 'in_app', '["meeting_title","meeting_time"]', 1),
('weekly_report', 'Weekly Report Available', 'Your weekly report for {week_start} to {week_end} is ready for review', 'in_app', '["week_start","week_end"]', 1);

-- --------------------------------------------------------
-- Update user IDs for created_by fields in sample data
-- --------------------------------------------------------
UPDATE `companies` SET `created_by` = 1 WHERE `id` > 0;
UPDATE `registrations` SET `created_by` = 1 WHERE `id` > 0;
UPDATE `tasks` SET `assigned_by` = 1 WHERE `id` > 0;

-- --------------------------------------------------------
-- Create Views for Common Queries
-- --------------------------------------------------------
CREATE OR REPLACE VIEW `vw_active_registrations` AS
SELECT
    r.id,
    r.status,
    r.priority,
    r.expiry_date,
    c.company_name,
    rt.name AS registration_type,
    u.name AS assigned_staff,
    DATEDIFF(r.expiry_date, CURDATE()) AS days_to_expiry
FROM registrations r
JOIN companies c ON r.company_id = c.id
JOIN registration_types rt ON r.registration_type_id = rt.id
LEFT JOIN users u ON r.assigned_staff_id = u.id
WHERE r.status NOT IN ('rejected', 'expired');

CREATE OR REPLACE VIEW `vw_expiring_soon` AS
SELECT
    r.id,
    c.company_name,
    rt.name AS registration_type,
    r.expiry_date,
    DATEDIFF(r.expiry_date, CURDATE()) AS days_remaining
FROM registrations r
JOIN companies c ON r.company_id = c.id
JOIN registration_types rt ON r.registration_type_id = rt.id
WHERE r.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
AND r.status = 'approved'
ORDER BY r.expiry_date ASC;

CREATE OR REPLACE VIEW `vw_staff_workload` AS
SELECT
    u.id,
    u.name,
    u.department_id,
    COUNT(DISTINCT c.id) AS assigned_companies,
    COUNT(DISTINCT CASE WHEN r.status IN ('pending', 'submitted', 'processing') THEN r.id END) AS active_registrations,
    COUNT(DISTINCT CASE WHEN t.status IN ('pending', 'in_progress') THEN t.id END) AS pending_tasks
FROM users u
LEFT JOIN companies c ON c.assigned_staff_id = u.id
LEFT JOIN registrations r ON r.assigned_staff_id = u.id
LEFT JOIN tasks t ON t.assigned_to = u.id
WHERE u.status = 'active'
GROUP BY u.id, u.name, u.department_id;

-- --------------------------------------------------------
-- Create Sample Notifications
-- --------------------------------------------------------
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `created_at`) VALUES
(2, 'task', 'New Task Assigned', 'You have been assigned: Follow up on NUPRC registration', '/tasks/view?id=1', NOW()),
(3, 'meeting', 'Meeting Tomorrow', 'Reminder: Q2 Business Review with PetroTech', '/meetings/view?id=1', NOW()),
(2, 'registration', 'Expiring Registration', 'PetroTech NUPRC registration expires in 30 days', '/registrations/view?id=1', NOW());

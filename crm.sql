-- Corporate Setup Limited - Internal CRM Database Schema
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `permissions` JSON,
    `is_system` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: departments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `head_id` INT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(20) UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `residential_address` TEXT NULL,
    `street` VARCHAR(120) NULL,
    `address_city` VARCHAR(100) NULL,
    `address_state` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `address_country` VARCHAR(100) NULL,
    `mailing_address` TEXT NULL,
    `mailing_same` TINYINT(1) NOT NULL DEFAULT 0,
    `alternative_address` TEXT NULL,
    `avatar` VARCHAR(255),
    `role_id` INT NOT NULL,
    `department_id` INT,
    `position` VARCHAR(100),
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `last_login` DATETIME,
    `email_verified` TINYINT(1) DEFAULT 0,
    `must_change_password` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE NO ACTION,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update departments to add head_id foreign key
ALTER TABLE `departments` ADD FOREIGN KEY (`head_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- Table: password_resets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: login_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `success` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: countries
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `countries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(3) NOT NULL UNIQUE,
    `phone_code` VARCHAR(10),
    `currency` VARCHAR(10),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: industries
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `industries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: companies
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(200) NOT NULL,
    `rc_number` VARCHAR(50),
    `industry_id` INT,
    `address` TEXT,
    `city` VARCHAR(100),
    `state` VARCHAR(100),
    `country_id` INT,
    `website` VARCHAR(255),
    `phone` VARCHAR(50),
    `email` VARCHAR(100),
    `contact_person` VARCHAR(100),
    `contact_position` VARCHAR(100),
    `contact_phone` VARCHAR(20),
    `contact_email` VARCHAR(100),
    `status` ENUM('prospect', 'active', 'inactive', 'churned') DEFAULT 'prospect',
    `subscription_status` ENUM('free', 'basic', 'premium', 'elite') DEFAULT 'free',
    `subscription_start_date` DATE,
    `subscription_end_date` DATE,
    `assigned_staff_id` INT,
    `client_since` DATE,
    `notes` TEXT,
    `is_elite` TINYINT(1) DEFAULT 0,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`industry_id`) REFERENCES `industries`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_company_status` (`status`),
    INDEX `idx_company_subscription` (`subscription_status`),
    INDEX `idx_company_assigned` (`assigned_staff_id`),
    UNIQUE KEY `uq_company_rc_number` (`rc_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: company_contacts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `company_contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `position` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `is_primary` TINYINT(1) DEFAULT 0,
    `notes` TEXT,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    INDEX `idx_company_contact` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: registration_types
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `category` ENUM('regulatory', 'corporate', 'compliance', 'certification', 'other') DEFAULT 'regulatory',
    `description` TEXT,
    `requirements` TEXT,
    `processing_time` INT DEFAULT 0,
    `validity_period` INT DEFAULT 365,
    `cost` DECIMAL(15, 2) DEFAULT 0,
    `renewable` TINYINT(1) DEFAULT 1,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: registrations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `registration_type_id` INT NOT NULL,
    `registration_number` VARCHAR(100),
    `status` ENUM('pending', 'awaiting_documents', 'submitted', 'processing', 'approved', 'rejected', 'expired', 'renewal_required') DEFAULT 'pending',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `assigned_staff_id` INT,
    `requirements_checklist` JSON,
    `documents_received` TEXT,
    `documents_missing` TEXT,
    `submission_date` DATE,
    `expected_completion_date` DATE,
    `completion_date` DATE,
    `expiry_date` DATE,
    `remarks` TEXT,
    `next_follow_up_date` DATE,
    `regulator_reference` VARCHAR(100),
    `cost` DECIMAL(15, 2) DEFAULT 0,
    `payment_status` ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`registration_type_id`) REFERENCES `registration_types`(`id`) ON DELETE NO ACTION,
    FOREIGN KEY (`assigned_staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_registration_status` (`status`),
    INDEX `idx_registration_company` (`company_id`),
    INDEX `idx_registration_expiry` (`expiry_date`),
    INDEX `idx_registration_assigned` (`assigned_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: registration_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `old_status` VARCHAR(50),
    `new_status` VARCHAR(50),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    INDEX `idx_registration_history` (`registration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_type` ENUM(
        'certificate', 'license', 'permit', 'insurance', 'receipt',
        'invoice', 'contract', 'letter', 'report', 'certificate_of_incorporation',
        'tax_clearance', 'financial_statement', 'other'
    ) NOT NULL,
    `document_name` VARCHAR(200) NOT NULL,
    `file_name` VARCHAR(255) NULL,
    `file_path` VARCHAR(255) NULL,
    `file_size` INT,
    `file_type` VARCHAR(50),
    `external_url` VARCHAR(500) NULL,
    `company_id` INT,
    `registration_id` INT,
    `uploaded_by` INT NOT NULL,
    `description` TEXT,
    `expiry_date` DATE,
    `reminder_days` INT DEFAULT 30,
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `status` ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    INDEX `idx_document_company` (`company_id`),
    INDEX `idx_document_type` (`document_type`),
    INDEX `idx_document_expiry` (`expiry_date`),
    UNIQUE KEY `uq_company_doc_url` (`company_id`, `external_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tasks
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `task_type` ENUM('general', 'registration', 'compliance', 'meeting', 'client', 'admin', 'other') DEFAULT 'general',
    `related_company_id` INT,
    `related_registration_id` INT,
    `assigned_to` INT NOT NULL,
    `assigned_by` INT,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'pending',
    `due_date` DATE,
    `due_time` TIME,
    `completed_at` DATETIME,
    `progress` INT DEFAULT 0,
    `frequency` ENUM('once', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'once',
    `tags` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`related_company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`related_registration_id`) REFERENCES `registrations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_task_assigned` (`assigned_to`),
    INDEX `idx_task_status` (`status`),
    INDEX `idx_task_due` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: task_comments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    INDEX `idx_task_comments` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: task_attachments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `task_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `uploaded_by` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT,
    `file_type` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: daily_reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `report_date` DATE NOT NULL,
    `time_started` TIME,
    `time_finished` TIME,
    `activities` TEXT NOT NULL,
    `projects_worked` TEXT,
    `clients_contacted` TEXT,
    `issues_encountered` TEXT,
    `tomorrow_plan` TEXT,
    `status` ENUM('draft', 'submitted', 'reviewed', 'approved') DEFAULT 'draft',
    `reviewed_by` INT,
    `reviewed_at` DATETIME,
    `reviewer_comments` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `idx_user_date` (`user_id`, `report_date`),
    INDEX `idx_report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: communications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `communications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `contact_id` INT,
    `user_id` INT NOT NULL,
    `communication_type` ENUM('email', 'phone_call', 'meeting', 'whatsapp', 'letter', 'site_visit', 'video_call', 'other') NOT NULL,
    `subject` VARCHAR(255),
    `summary` TEXT NOT NULL,
    `outcome` TEXT,
    `next_action` TEXT,
    `next_action_date` DATE,
    `duration_minutes` INT,
    `communication_date` DATETIME NOT NULL,
    `attachments` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contact_id`) REFERENCES `company_contacts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    INDEX `idx_communication_company` (`company_id`),
    INDEX `idx_communication_date` (`communication_date`),
    INDEX `idx_communication_type` (`communication_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: meetings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `meeting_type` ENUM('internal', 'client', 'regulator', 'vendor', 'other') DEFAULT 'internal',
    `company_id` INT,
    `organizer_id` INT NOT NULL,
    `location` VARCHAR(255),
    `meeting_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME,
    `agenda` TEXT,
    `minutes` TEXT,
    `action_items` TEXT,
    `status` ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    `meeting_link` VARCHAR(255),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION,
    INDEX `idx_meeting_date` (`meeting_date`),
    INDEX `idx_meeting_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: meeting_participants
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meeting_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `user_id` INT,
    `external_name` VARCHAR(100),
    `external_email` VARCHAR(100),
    `is_external` TINYINT(1) DEFAULT 0,
    `attendance_status` ENUM('invited', 'accepted', 'declined', 'attended', 'absent') DEFAULT 'invited',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_meeting_participants` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: meeting_attachments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meeting_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL,
    `uploaded_by` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT,
    `file_type` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: opportunities
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `opportunities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `opportunity_title` VARCHAR(200) NOT NULL,
    `opportunity_type` ENUM('vendor', 'government_tender', 'oil_gas', 'training', 'consulting', 'partnership', 'other') NOT NULL,
    `source` VARCHAR(100),
    `description` TEXT,
    `company_id` INT,
    `assigned_staff_id` INT,
    `estimated_value` DECIMAL(15, 2),
    `deadline` DATE,
    `requirements` TEXT,
    `status` ENUM('new', 'pursuing', 'submitted', 'won', 'lost', 'cancelled') DEFAULT 'new',
    `probability` INT DEFAULT 50,
    `result_notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_opportunity_status` (`status`),
    INDEX `idx_opportunity_type` (`opportunity_type`),
    INDEX `idx_opportunity_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: elite_members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `elite_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `membership_tier` ENUM('basic', 'premium', 'elite', 'platinum') DEFAULT 'elite',
    `membership_start_date` DATE NOT NULL,
    `membership_end_date` DATE,
    `status` ENUM('active', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    `benefits` JSON,
    `assigned_account_manager` INT,
    `notes` TEXT,
    `last_login` DATETIME,
    `login_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_account_manager`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_elite_status` (`status`),
    INDEX `idx_elite_company` (`company_id`),
    UNIQUE KEY `uq_elite_company_start` (`company_id`, `membership_start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: elite_support_requests
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `elite_support_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `elite_member_id` INT NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    `assigned_to` INT,
    `resolution` TEXT,
    `resolved_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`elite_member_id`) REFERENCES `elite_members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_support_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255),
    `related_type` VARCHAR(50),
    `related_id` INT,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME,
    `sent_email` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notification_user` (`user_id`),
    INDEX `idx_notification_read` (`user_id`, `is_read`),
    INDEX `idx_notification_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notification_templates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `subject` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `type` ENUM('email', 'in_app') DEFAULT 'in_app',
    `variables` JSON,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: email_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `notification_id` INT NULL,
    `recipient_email` VARCHAR(190) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('sent', 'failed', 'not_configured') DEFAULT 'failed',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE SET NULL,
    INDEX `idx_email_logs_recipient` (`recipient_email`),
    INDEX `idx_email_logs_status` (`status`),
    INDEX `idx_email_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `table_name` VARCHAR(50),
    `record_id` INT,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_activity_user` (`user_id`),
    INDEX `idx_activity_action` (`action`),
    INDEX `idx_activity_table` (`table_name`, `record_id`),
    INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: events (Calendar events)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `event_type` ENUM('meeting', 'deadline', 'reminder', 'renewal', 'task', 'other') DEFAULT 'other',
    `event_date` DATE NOT NULL,
    `start_time` TIME,
    `end_time` TIME,
    `all_day` TINYINT(1) DEFAULT 0,
    `location` VARCHAR(255),
    `related_type` VARCHAR(50),
    `related_id` INT,
    `created_by` INT,
    `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    `color` VARCHAR(7) DEFAULT '#2563eb',
    `is_recurring` TINYINT(1) DEFAULT 0,
    `recurrence_pattern` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: event_participants
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_event_user` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: leave_requests
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_type` ENUM('annual', 'sick', 'maternity', 'paternity', 'bereavement', 'other') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `days` INT NOT NULL,
    `reason` TEXT,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    `approved_by` INT,
    `approved_at` DATETIME,
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_leave_user` (`user_id`),
    INDEX `idx_leave_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: attendance
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `check_in` TIME,
    `check_out` TIME,
    `status` ENUM('present', 'absent', 'late', 'half_day', 'on_leave') DEFAULT 'present',
    `notes` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_user_date` (`user_id`, `attendance_date`),
    INDEX `idx_attendance_date` (`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `report_type` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `query` TEXT,
    `columns` JSON,
    `filters` JSON,
    `created_by` INT,
    `is_scheduled` TINYINT(1) DEFAULT 0,
    `schedule_frequency` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NULL,
    `last_run` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: backup_audit
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_audit` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `backup_type` ENUM('full', 'partial', 'incremental') NOT NULL,
    `file_name` VARCHAR(255),
    `file_size` BIGINT,
    `status` ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
    `started_at` DATETIME,
    `completed_at` DATETIME,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: task_assignees (multi-assignee support)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `task_assignees` (
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`, `user_id`),
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_task_assignees_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: monthly_reports (aggregated from daily_reports)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monthly_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `department_id` INT NULL,
    `report_year` SMALLINT NOT NULL,
    `report_month` TINYINT NOT NULL,
    `daily_reports_count` INT NOT NULL DEFAULT 0,
    `activities_summary` TEXT,
    `projects_summary` TEXT,
    `clients_summary` TEXT,
    `issues_summary` TEXT,
    `weekly_breakdown` JSON NULL,
    `project_breakdown` JSON NULL,
    `tasks_completed_count` INT NOT NULL DEFAULT 0,
    `tasks_outstanding_count` INT NOT NULL DEFAULT 0,
    `generated_at` DATETIME NOT NULL,
    `generated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_month` (`user_id`, `report_year`, `report_month`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_monthly_period` (`report_year`, `report_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: client_reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `report_reference` VARCHAR(40) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `status` ENUM('generated', 'failed') DEFAULT 'generated',
    `message_body` TEXT NULL,
    `invoice_document_id` INT NULL,
    `generated_by` INT NULL,
    `generated_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_client_report_ref` (`report_reference`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`invoice_document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL,
    INDEX `idx_client_reports_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff_next_of_kin
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_next_of_kin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(80) NULL,
    `phone` VARCHAR(40) NULL,
    `alt_phone` VARCHAR(40) NULL,
    `email` VARCHAR(150) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_kin_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff_emergency_contacts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_emergency_contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(80) NULL,
    `phone` VARCHAR(40) NULL,
    `alt_phone` VARCHAR(40) NULL,
    `email` VARCHAR(150) NULL,
    `address` TEXT NULL,
    `preferred_method` VARCHAR(40) NULL,
    `sort_order` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_emergency_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

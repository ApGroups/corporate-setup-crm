-- Corporate Setup CRM — Production Repair Migration (additive, non-destructive)
-- Apply: mysql -u USER -p DB_NAME < sql/migrations/001_production_repair.sql

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Multi-assignee tasks: junction table
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

-- Backfill from existing single assignee (ignore duplicates if re-run)
INSERT IGNORE INTO `task_assignees` (`task_id`, `user_id`, `created_at`)
SELECT `id`, `assigned_to`, NOW() FROM `tasks` WHERE `assigned_to` IS NOT NULL;

-- --------------------------------------------------------
-- Monthly staff reports (aggregated from daily_reports)
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

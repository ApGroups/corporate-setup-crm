-- Advanced CRM notifications, email logging, and access-control updates

SET NAMES utf8mb4;

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

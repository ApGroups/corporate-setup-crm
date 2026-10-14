-- Staff address, next of kin, emergency contacts, and saved client-message text.
-- Additive and nullable. Safe to re-run. Does not drop or rewrite existing rows.

SET NAMES utf8mb4;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'residential_address'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `users`
        ADD COLUMN `residential_address` TEXT NULL AFTER `phone`,
        ADD COLUMN `street` VARCHAR(120) NULL AFTER `residential_address`,
        ADD COLUMN `address_city` VARCHAR(100) NULL AFTER `street`,
        ADD COLUMN `address_state` VARCHAR(100) NULL AFTER `address_city`,
        ADD COLUMN `postal_code` VARCHAR(20) NULL AFTER `address_state`,
        ADD COLUMN `address_country` VARCHAR(100) NULL AFTER `postal_code`,
        ADD COLUMN `mailing_address` TEXT NULL AFTER `address_country`,
        ADD COLUMN `mailing_same` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mailing_address`,
        ADD COLUMN `alternative_address` TEXT NULL AFTER `mailing_same`',
    'SELECT ''users address columns already exist'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_reports' AND COLUMN_NAME = 'message_body'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `client_reports` ADD COLUMN `message_body` TEXT NULL AFTER `status`',
    'SELECT ''message_body already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_reports' AND COLUMN_NAME = 'invoice_document_id'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `client_reports` ADD COLUMN `invoice_document_id` INT NULL AFTER `message_body`',
    'SELECT ''invoice_document_id already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_reports' AND CONSTRAINT_NAME = 'fk_client_reports_invoice'
);
SET @sql := IF(
    @fk_exists = 0,
    'ALTER TABLE `client_reports` ADD CONSTRAINT `fk_client_reports_invoice` FOREIGN KEY (`invoice_document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL',
    'SELECT ''fk_client_reports_invoice already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

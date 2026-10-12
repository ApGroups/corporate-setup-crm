-- Integrity constraints, document links, elite tier fix, and client report history
-- Additive. Stops if a unique index cannot be created because duplicates already exist.

SET NAMES utf8mb4;

UPDATE `companies` SET `rc_number` = NULL WHERE `rc_number` IS NOT NULL AND TRIM(`rc_number`) = '';

ALTER TABLE `companies`
    ADD UNIQUE KEY `uq_company_rc_number` (`rc_number`);

ALTER TABLE `elite_members`
    MODIFY `membership_tier` ENUM('basic', ' premium', 'premium', 'elite', 'platinum') DEFAULT 'elite';

UPDATE `elite_members` SET `membership_tier` = 'premium' WHERE `membership_tier` = ' premium';

ALTER TABLE `elite_members`
    MODIFY `membership_tier` ENUM('basic', 'premium', 'elite', 'platinum') DEFAULT 'elite';

ALTER TABLE `elite_members`
    ADD UNIQUE KEY `uq_elite_company_start` (`company_id`, `membership_start_date`);

ALTER TABLE `documents`
    MODIFY `file_name` VARCHAR(255) NULL,
    MODIFY `file_path` VARCHAR(255) NULL,
    ADD COLUMN `external_url` VARCHAR(500) NULL AFTER `file_type`;

ALTER TABLE `documents`
    ADD UNIQUE KEY `uq_company_doc_url` (`company_id`, `external_url`);

CREATE TABLE IF NOT EXISTS `client_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `report_reference` VARCHAR(40) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `status` ENUM('generated', 'failed') DEFAULT 'generated',
    `generated_by` INT NULL,
    `generated_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_client_report_ref` (`report_reference`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_client_reports_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integrity constraints, document links, elite tier fix, and client report history.
-- Safe to re-run. A unique index is added only when that column has no duplicate values.
-- Duplicate rows are listed and left in place.

SET NAMES utf8mb4;

UPDATE `companies`
SET `rc_number` = NULL
WHERE `rc_number` IS NOT NULL AND TRIM(`rc_number`) = '';

SELECT id, company_name, rc_number, status, created_at
FROM `companies`
WHERE `rc_number` IN (
    SELECT `rc_number` FROM (
        SELECT `rc_number`
        FROM `companies`
        WHERE `rc_number` IS NOT NULL AND TRIM(`rc_number`) <> ''
        GROUP BY `rc_number`
        HAVING COUNT(*) > 1
    ) `rc_dupes`
)
ORDER BY `rc_number`, `id`;

SET @rc_dupes := (
    SELECT COUNT(*) FROM (
        SELECT `rc_number`
        FROM `companies`
        WHERE `rc_number` IS NOT NULL AND TRIM(`rc_number`) <> ''
        GROUP BY `rc_number`
        HAVING COUNT(*) > 1
    ) `d`
);
SET @rc_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND INDEX_NAME = 'uq_company_rc_number'
);
SET @sql := IF(
    @rc_index > 0,
    'SELECT ''uq_company_rc_number already exists'' AS migration_note',
    IF(
        @rc_dupes > 0,
        'SELECT ''Skipped uq_company_rc_number because duplicate RC numbers still exist'' AS migration_note',
        'ALTER TABLE `companies` ADD UNIQUE KEY `uq_company_rc_number` (`rc_number`)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `elite_members`
    MODIFY `membership_tier` ENUM('basic', ' premium', 'premium', 'elite', 'platinum') DEFAULT 'elite';

UPDATE `elite_members` SET `membership_tier` = 'premium' WHERE `membership_tier` = ' premium';

ALTER TABLE `elite_members`
    MODIFY `membership_tier` ENUM('basic', 'premium', 'elite', 'platinum') DEFAULT 'elite';

SELECT company_id, membership_start_date, COUNT(*) AS copies, GROUP_CONCAT(id ORDER BY id) AS member_ids
FROM `elite_members`
GROUP BY company_id, membership_start_date
HAVING COUNT(*) > 1;

SET @elite_dupes := (
    SELECT COUNT(*) FROM (
        SELECT company_id, membership_start_date
        FROM `elite_members`
        GROUP BY company_id, membership_start_date
        HAVING COUNT(*) > 1
    ) `d`
);
SET @elite_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'elite_members'
      AND INDEX_NAME = 'uq_elite_company_start'
);
SET @sql := IF(
    @elite_index > 0,
    'SELECT ''uq_elite_company_start already exists'' AS migration_note',
    IF(
        @elite_dupes > 0,
        'SELECT ''Skipped uq_elite_company_start because duplicate start dates still exist'' AS migration_note',
        'ALTER TABLE `elite_members` ADD UNIQUE KEY `uq_elite_company_start` (`company_id`, `membership_start_date`)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `documents`
    MODIFY `file_name` VARCHAR(255) NULL,
    MODIFY `file_path` VARCHAR(255) NULL;

SET @doc_url_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documents'
      AND COLUMN_NAME = 'external_url'
);
SET @sql := IF(
    @doc_url_exists = 0,
    'ALTER TABLE `documents` ADD COLUMN `external_url` VARCHAR(500) NULL AFTER `file_type`',
    'SELECT ''external_url already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @doc_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documents'
      AND INDEX_NAME = 'uq_company_doc_url'
);
SET @sql := IF(
    @doc_index > 0,
    'SELECT ''uq_company_doc_url already exists'' AS migration_note',
    'ALTER TABLE `documents` ADD UNIQUE KEY `uq_company_doc_url` (`company_id`, `external_url`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

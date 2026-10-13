-- Monthly report week-by-week and project-by-project breakdowns
-- MySQL does not accept ADD COLUMN IF NOT EXISTS. These statements add each column only when it is missing.

SET NAMES utf8mb4;

SET @weekly_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'monthly_reports'
      AND COLUMN_NAME = 'weekly_breakdown'
);
SET @sql := IF(
    @weekly_exists = 0,
    'ALTER TABLE `monthly_reports` ADD COLUMN `weekly_breakdown` JSON NULL AFTER `issues_summary`',
    'SELECT ''weekly_breakdown already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'monthly_reports'
      AND COLUMN_NAME = 'project_breakdown'
);
SET @sql := IF(
    @project_exists = 0,
    'ALTER TABLE `monthly_reports` ADD COLUMN `project_breakdown` JSON NULL AFTER `weekly_breakdown`',
    'SELECT ''project_breakdown already exists'' AS migration_note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

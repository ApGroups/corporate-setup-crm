-- Monthly report week-by-week and project-by-project breakdowns
-- Additive, non-destructive

SET NAMES utf8mb4;

ALTER TABLE `monthly_reports`
    ADD COLUMN IF NOT EXISTS `weekly_breakdown` JSON NULL AFTER `issues_summary`,
    ADD COLUMN IF NOT EXISTS `project_breakdown` JSON NULL AFTER `weekly_breakdown`;

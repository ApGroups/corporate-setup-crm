# Database Migrations

Apply additive migrations manually (this CRM has no migration runner).

## Before applying

Take a backup of the live database.

Example (XAMPP Windows):

```bat
C:\xampp\mysql\bin\mysqldump.exe -u DB_USER -p DB_NAME > backups\pre_repair_YYYYMMDD.sql
```

## Apply 001_production_repair.sql

```bat
C:\xampp\mysql\bin\mysql.exe -u DB_USER -p DB_NAME < sql\migrations\001_production_repair.sql
```

Or via phpMyAdmin: Import → select `001_production_repair.sql`.

## Apply 002_notifications_email_access.sql

```bat
C:\xampp\mysql\bin\mysql.exe -u DB_USER -p DB_NAME < sql\migrations\002_notifications_email_access.sql
```

Or via phpMyAdmin: Import → select `002_notifications_email_access.sql`.

## Apply 003_monthly_report_breakdowns.sql

```bat
C:\xampp\mysql\bin\mysql.exe -u DB_USER -p DB_NAME < sql\migrations\003_monthly_report_breakdowns.sql
```

Or via phpMyAdmin: Import → select `003_monthly_report_breakdowns.sql`.

`003` does not use `ADD COLUMN IF NOT EXISTS`. That syntax is rejected by MySQL. Re-importing it is safe when the columns are already present.

## Apply 004_integrity_reports_documents.sql

```bat
C:\xampp\mysql\bin\mysql.exe -u DB_USER -p DB_NAME < sql\migrations\004_integrity_reports_documents.sql
```

If the result grid lists companies that share an RC number, the unique index is skipped and those rows are left unchanged. Clear or correct the extra RC number, then import this file again. `php scripts/dedupe-report.php` lists the same collisions. Do not drop rows to force the index.

Or via phpMyAdmin: Import → select `004_integrity_reports_documents.sql`.

## What it adds

- `task_assignees` (multi-assignee tasks) + backfill from `tasks.assigned_to`
- `monthly_reports` (persisted monthly aggregation from daily reports)
- `email_logs` (mail delivery handoff/failure visibility for CRM notifications)
- `monthly_reports.weekly_breakdown` and `monthly_reports.project_breakdown` (JSON week/project aggregation)
- Unique `companies.rc_number`, unique elite start date per company, document `external_url`, and `client_reports` history
- Staff address columns, `staff_next_of_kin`, `staff_emergency_contacts`, and saved client-message text

## Apply 005_staff_profile_client_message.sql

```bat
C:\xampp\mysql\bin\mysql.exe -u DB_USER -p DB_NAME < sql\migrations\005_staff_profile_client_message.sql
```

Or via phpMyAdmin: Import → select `005_staff_profile_client_message.sql`. Re-importing it is safe.

Does **not** drop `daily_reports.tomorrow_plan` (column left for historical data; app no longer uses it).

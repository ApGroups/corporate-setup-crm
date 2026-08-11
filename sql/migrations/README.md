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

## What it adds

- `task_assignees` (multi-assignee tasks) + backfill from `tasks.assigned_to`
- `monthly_reports` (persisted monthly aggregation from daily reports)

Does **not** drop `daily_reports.tomorrow_plan` (column left for historical data; app no longer uses it).

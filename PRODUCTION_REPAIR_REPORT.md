# Corporate Setup CRM — Production Repair Delivery Report

Date: 2026-08-11  
Scope: Existing plain PHP CRM only (no React migration, no UI redesign).

## Completed

- Full codebase audit against live schema; local DB `corpor43_crm` created/seeded for verification
- **Tomorrow's Plan** removed from daily report form, view, and save handler (DB column retained)
- **Daily reports** repaired: All Staff filter, draft prefill, draft vs submitted banner, no `created_at` overwrite on update, server-side review auth, redirect after save, IDOR protection, permission gates
- **Monthly reports** added: `monthly_reports` table, `monthly-reports.php` UI, aggregation from saved daily reports, idempotent upsert, CLI cron script
- **Multi-assignee tasks** via `task_assignees` junction; multi-select UI; dashboard/nav/export/search updated; `assigned_to` kept as primary assignee sync
- **Editable role permissions** on Staff → Roles; JSON `roles.permissions` updated and enforced immediately via DB reload in `checkPermission()`
- Server-side `requirePermission` on previously open modules + sidebar gating
- **Recent Activity**: `activity-log.php` page; dashboard “View All” works
- Security/helpers: `notify()` column `link`, CSRF on notifications, login open-redirect allowlist, `EnvLoader` empty password fix, export tasks query, settings registration/industry tabs, `backup.php`
- Business report tabs (registrations/tasks/activity) implemented with real queries

## Database Changes

| Change | Type |
| --- | --- |
| `sql/migrations/001_production_repair.sql` | Additive migration |
| `task_assignees` | New table + backfill from `tasks.assigned_to` |
| `monthly_reports` | New table |
| `crm.sql` | Updated for greenfield installs |
| `daily_reports.tomorrow_plan` | **Not dropped** (historical safety) |

Backup taken: `backups/pre_repair_baseline.sql`

## Permission Changes

- `checkPermission()` honors `*` wildcard and reloads permissions from DB each request
- Role editor: `staff.php` action `save_role_permissions`
- Page gates added for documents, registrations, compliance, calendar, reports, export, settings, tasks, monthly reports, activity log, backup
- Seed/role updates: Management/Compliance/Operations (+ Marketing/BD submit_reports in seed) include needed report/elite/log permissions
- Nav items hidden when permission missing (still 403 if URL forced)

## Task Changes

- Junction table `task_assignees (task_id, user_id)`
- Create/edit accept `assigned_to[]`
- Transactional replace of assignees; notifications for newly added assignees
- List/detail show comma-separated assignee names
- Delete cascades assignees via FK

## Report Changes

- Daily: create/save/retrieve/edit draft/review with auth
- Monthly: generated from submitted/reviewed/approved daily reports + task counts; persisted; list/view; re-generate updates same row
- Cron:  
  `C:\xampp\php\php.exe C:\xampp\htdocs\corporate-setup-crm\scripts\generate-monthly-reports.php`  
  Optional: `--year=YYYY --month=M`  
  Example crontab: `0 23 28-31 * *` with that command

## Recent Activity

- Logging via `logActivity()` unchanged in core path
- Full list page with filters/pagination
- Task comments now also log activity

## Security

- Permission enforcement on restricted modules and endpoints
- Daily report IDOR (own reports unless `view_reports`/`approve_reports`)
- Notifications CSRF
- Login redirect allowlist
- `notify()` schema alignment
- Document mutations require upload/manage permission
- Settings mutations require `manage_settings`
- Backup download requires `backup_system`

## Testing

Ran `scripts/e2e-tests.php` and monthly CLI:

```
Authentication ........ PASS
Staff Management ...... PASS
Permissions ........... PASS
Tasks / Multi Assignment PASS
Daily Reports ......... PASS
Monthly Reports ....... PASS
Recent Activity ....... PASS
Database .............. PASS
Tomorrow Plan removed . PASS
Activity/Backup pages . PASS
```

CLI monthly generation: `created=7 updated=1` for 2026-08.

## Local setup notes

- Created `.env` (gitignored) with local root MySQL for XAMPP
- Default seed password reset during E2E to `Admin@123` for admin/test users — **change in production**
- Apply migration on other environments: see `sql/migrations/README.md`

## UI preservation

No layout/theme redesign. Changes limited to functional controls (multi-select assignees, role permission checkboxes, new Monthly Reports nav item matching existing Reports styling).

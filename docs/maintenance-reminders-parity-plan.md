---
description: Phased plan to implement Maintenance Reminders in the SaaS with 100% parity to the RepairBuddy/Computer Repair Shop WordPress plugin.
---

# Maintenance Reminders — Parity Implementation Plan

## Goal
Implement Maintenance Reminders in the SaaS to reach **100% functional parity** with the WordPress plugin implementation.

## Source of truth (plugin behavior)
The plugin’s Maintenance Reminders feature provides:

- **Rule definition** stored in a dedicated DB table (`wc_cr_maint_reminders`)
- **Reminder logs** stored in a dedicated DB table (`wc_cr_reminder_logs`)
- **Execution** that:
  - Runs only when reminders are enabled and the reminder itself is active
  - Enforces a **minimum 24h cooldown** between executions per reminder
  - Selects jobs by **delivery date** and **interval-days** logic
  - Applies **device-type and device-brand targeting** (including “All”)
  - Respects **opt-out/unsubscribe** behavior
  - Performs **per-job rate limiting** (`_last_reminder_sent`)
  - Sends **email and/or SMS** with template variables
  - Logs each send
- **Admin UX** including add/edit, send test, and view logs

## Definitions
- **Reminder rule**: configuration that determines who should receive reminders and what is sent.
- **Execution**: the process that evaluates rules and dispatches reminders.
- **Rate limiting**:
  - Per-reminder: do not run reminder evaluation more than once per 24h
  - Per-job: do not remind the same job again until the interval has elapsed

---

# Phase 0 — Requirements lock + design (Parity contract)
## Outcomes
- Written spec that exactly matches plugin behavior and clarifies SaaS-specific decisions.

## Decisions to finalize
- **Opt-out scope**
  - Plugin uses job-level meta `_email_optout` (and uses it for SMS too).
  - SaaS must decide whether opt-out is:
    - Job-level only (closest parity)
    - Customer-level
    - Device-level
- **Multi-device jobs**
  - Plugin loops devices; it can send per matching device.
  - SaaS must decide:
    - Per-device sends (closest parity)
    - Consolidated single message per job
- **Branch scoping**
  - Taxes in the plugin are branch-aware; reminders are logically branch-aware too.
  - SaaS should scope reminders to `tenant_id + branch_id`.

## Deliverables
- Spec doc section added to this plan (or linked) that includes:
  - Job selection formula
  - Targeting rules
  - Template variables
  - Logging requirements
  - Error handling expectations

---

# Phase 1 — Data model + migrations (Rules, Logs, State)
## Outcomes
- Durable storage for reminder rules and logs (tenant+branch scoped).

## Backend schema
### 1) Reminder rules table
Create a table (name suggestion: `rb_maintenance_reminders`) with:

- **Identity / scope**
  - `id`
  - `tenant_id`
  - `branch_id`

- **Rule fields (plugin parity)**
  - `name`
  - `description`
  - `interval_days`
  - `device_type_id` (nullable; `NULL` means “All”)
  - `device_brand_id` (nullable; `NULL` means “All”)
  - `email_enabled` (bool)
  - `sms_enabled` (bool)
  - `reminder_enabled` (bool)
  - `email_body` (longtext)
  - `sms_body` (longtext)

- **Execution metadata**
  - `last_executed_at` (nullable)

- **Audit**
  - `created_by_user_id` (nullable)
  - `updated_by_user_id` (nullable)
  - `created_at`, `updated_at`

### 2) Reminder logs table
Create `rb_maintenance_reminder_logs`:

- `id`, `created_at`
- `tenant_id`, `branch_id`
- `reminder_id`
- `job_id`
- `customer_id`
- `channel` (`email|sms`)
- `to_address` (email or phone)
- `status` (`sent|failed|skipped`)
- `error_message` (nullable)

### 3) Per-job per-reminder send state (rate limiting)
Create `rb_job_maintenance_reminder_state` (recommended for parity):

- `tenant_id`, `branch_id`
- `job_id`
- `reminder_id`
- `last_sent_at`

Unique index on (`tenant_id`,`branch_id`,`job_id`,`reminder_id`).

### 4) Opt-out storage
Implement **at least** a job-level flag compatible with plugin behavior:

- Option A: column on jobs: `maintenance_reminders_opted_out_at`
- Option B: separate table keyed by (`job_id`) if jobs table shouldn’t change

---

# Phase 2 — Backend APIs (CRUD, Logs, Test)
## Outcomes
- Full admin API parity for managing reminders.

## Endpoints
### Reminder rules
- `GET /api/{tenant}/app/repairbuddy/maintenance-reminders`
- `POST /api/{tenant}/app/repairbuddy/maintenance-reminders`
- `PATCH /api/{tenant}/app/repairbuddy/maintenance-reminders/{id}`
- `DELETE /api/{tenant}/app/repairbuddy/maintenance-reminders/{id}`

### Logs
- `GET /api/{tenant}/app/repairbuddy/maintenance-reminder-logs`
  - Supports filters:
    - `reminder_id`
    - `job_id`
    - date range
    - pagination

### Test sending
- `POST /api/{tenant}/app/repairbuddy/maintenance-reminders/{id}/test`
  - Input:
    - `email` (optional)
    - `phone` (optional)
  - Behavior:
    - Uses rule templates
    - Uses sample placeholders for `{{customer_name}}`, `{{device_name}}`
    - Includes valid unsubscribe URL placeholder
    - Writes a log entry

## Permissions + audit
- Require a permission similar to `settings.manage` or a dedicated `maintenance_reminders.manage`.
- Write platform audit events for:
  - created/updated/deleted rule
  - test sent

---

# Phase 3 — Execution engine (Scheduler + Queue)
## Outcomes
- Reminders are actually delivered automatically with the same selection logic as the plugin.

## Scheduler design
- Create a Laravel scheduled command (e.g. `repairbuddy:run-maintenance-reminders`)
  - Runs hourly (or daily), but enforces:
    - **Per-reminder 24h cooldown** (plugin parity)

## Execution pipeline
For each reminder rule where `reminder_enabled=true`:

1. **Cooldown check**
   - If `last_executed_at` is < 24 hours ago: skip

2. **Job selection**
   - Select jobs with **delivery date** present
   - Delivery date older than: `today - interval_days`

3. **Per-job rate limiting**
   - If state table has `last_sent_at` and `days_since_last_sent <= interval_days`: skip

4. **Device targeting**
   - Evaluate job’s device(s) against rule:
     - `(type=All && brand=All)` OR
     - `(type=All && brand matches)` OR
     - `(brand=All && type matches)` OR
     - `(both match)`

5. **Opt-out**
   - If opted out: skip (for both email and sms, matching plugin)

6. **Send email**
   - If `email_enabled=true` and job/customer has valid email

7. **Send SMS**
   - If `sms_enabled=true` and job/customer has valid phone and SMS is configured

8. **Log**
   - Write a log row per channel attempt
   - Update per-job per-reminder state (`last_sent_at = now()`)

9. **Update rule last execution**
   - `last_executed_at = now()`

## Templating parity
Support replacements:
- `{{customer_name}}`
- `{{device_name}}`
- `{{unsubscribe_device}}`

## Unsubscribe endpoint
- Implement a signed URL endpoint (public) for unsubscribe:
  - Example: `GET /t/{tenant}/unsubscribe?job_id=...&token=...`
- Token should be HMAC signed and expire.
- On success:
  - Mark job/device/customer opted out
  - Return confirmation screen

---

# Phase 4 — Frontend UI parity
## Outcomes
- UI matches plugin capabilities: add/edit, test, show statuses, and navigate to logs.

## Replace mock model
Current frontend draft model only supports:
- `name`, `intervalDays`, `status`

Update UI to support full rule fields:
- Name
- Description
- Interval (days)
- Device Type (including All)
- Brand (including All)
- Email enabled + email body
- SMS enabled + sms body
- Reminder enabled
- Last run

## Screens and behaviors
- **Maintenance Reminders list**
  - Columns: ID (optional), Name, Interval, Device Type, Brand, Email, SMS, Reminder Status, Last Run
  - Actions: Edit, Send test, View logs
- **Add/Edit modal**
  - Validations matching backend
- **Test modal**
  - Enter email/phone
  - Show success/failure state
- **Logs page**
  - Connect existing `/app/[tenant]/reminder-logs` to new backend logs
  - Filters by reminder/job/date

---

# Phase 5 — Parity QA + Monitoring
## Outcomes
- Verified behavior matches plugin across edge cases.

## Test matrix
- Jobs without delivery date never selected
- Delivery date boundary conditions (exactly interval days)
- 24h cooldown per reminder
- Per-job cooldown using state table
- Device targeting permutations (All/All, All/Brand, Type/All, Type/Brand)
- Multi-device jobs
- Opt-out blocks sends (email and sms)
- Template variables render correctly
- Logs captured correctly for sent/skipped/failed

## Operational concerns
- Idempotency (avoid duplicates if command reruns)
- Failure handling with retries (queue)
- Basic alerting/visibility:
  - last run time
  - error counts

---

# Phase 6 — Migration / Backfill (Optional)
## Outcomes
- Existing mock UI data can be migrated (if needed).

- If any tenants already have reminder data in `repairbuddy_settings` draft, define a one-time migration:
  - Create rule rows
  - Seed default templates and toggles
  - Leave logs empty

---

# Implementation notes / recommended sequencing
- Implement **Phase 1 + Phase 2** first so the UI has real APIs.
- Implement **Phase 3** after CRUD + logs exist, so the system can be observed.
- Implement **Phase 4** once backend contracts are stable.

# Open questions (must be answered for exact parity)
- Opt-out scope: job vs customer vs device
- Multi-device behavior: per-device vs consolidated
- Branch behavior: rule per branch vs tenant-wide

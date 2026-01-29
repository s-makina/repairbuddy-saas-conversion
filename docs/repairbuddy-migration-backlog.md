# RepairBuddy SaaS Migration Backlog (AI-Implementable Tickets)

This backlog decomposes the RepairBuddy WordPress plugin into small, dependency-ordered tickets suitable for AI-assisted implementation.

## Conventions

- **Tenant + branch scoping**: Every RepairBuddy domain table must include `tenant_id` and `branch_id`. Models should default to `BelongsToTenantAndBranch`.
- **Auth**: Staff routes live under `app/[tenant]/...`. Public/portal routes live under `t/[tenant]/...`.
- **Acceptance criteria (AC)**: Each ticket includes deterministic AC.
- **Non-goals**: Do not implement “nice-to-have” refactors inside feature tickets.

---

## EPIC 0 — Platform Rails (required for everything)

### RB-0001 — Domain tenant scoping standard
- **Dependencies**: none
- **Scope**:
  - Establish a standard for tenant + branch scoped domain models.
- **Backend**:
  - Add `tenant_id` and `branch_id` to all new RepairBuddy domain tables.
  - Add base query scoping helper (global scope / model concern) for tenant + branch filtering.
- **API**:
  - All domain endpoints must derive `tenant_id` from route param and enforce it.
  - All staff/app endpoints must enforce an active branch context (`branch.active`) and use branch-scoped models.
  - Public endpoints must resolve a branch (initially use tenant `default_branch_id`; later can accept explicit branch code or tokenized links).
- **AC**:
  - Attempting to access any resource from a different tenant returns 404/403.
  - Attempting to access branch-scoped resources without an active branch returns an error (or empty result) and does not leak data.

### RB-0002 — RBAC mapping for plugin roles
- **Dependencies**: RB-0001
- **Scope**:
  - Map plugin roles/capabilities into SaaS.
- **Backend**:
  - Roles: `customer`, `technician`, `store_manager` (or `manager`), `admin`.
  - Policies:
    - Job access: customer can only access own jobs; technician can access assigned jobs; manager/admin can access all.
- **Frontend**:
  - Gate navigation items by role.
- **AC**:
  - Technician cannot open an unassigned job detail page.

### RB-0003 — Audit/event log plumbing (generic)
- **Dependencies**: RB-0001
- **Scope**:
  - Provide a reusable “job history / event feed” mechanism.
- **Backend**:
  - Create `rb_events` (or similar) table:
    - `tenant_id`, `branch_id`, `actor_user_id`, `entity_type`, `entity_id`, `visibility` (public/private), `event_type`, `payload_json`, timestamps.
- **AC**:
  - Creating a Repair Job emits an event record.

---

## EPIC 1 — Jobs Core (minimal vertical slice)

### RB-0101 — Job statuses + payment statuses
- **Dependencies**: RB-0001
- **Scope**:
  - Replicate `wc_cr_job_status` and `wc_cr_payment_status`.
- **Backend (DB)**:
  - `rb_job_statuses`: `tenant_id`, `branch_id`, `slug`, `label`, `email_enabled`, `email_template`, `sms_enabled`, `invoice_label`, `is_active`, timestamps.
  - `rb_payment_statuses`: `tenant_id`, `branch_id`, `slug`, `label`, `email_template`, `is_active`, timestamps.
- **Backend (Seed)**:
  - Seed default statuses matching the plugin defaults.
- **UI**:
  - Settings screens to list/edit statuses can be deferred.
- **AC**:
  - A tenant has seeded statuses available via API.

### RB-0102 — Repair Jobs table + model
- **Dependencies**: RB-0101
- **Scope**:
  - Create first-class Repair Job entity (not queue jobs).
- **Backend (DB)**:
  - `rb_jobs`: `tenant_id`, `branch_id`, `id`, `case_number`, `status_slug`, `payment_status_slug`, `priority`, `customer_id`, `created_by`, `opened_at`, `closed_at`, timestamps.
  - Unique index: (`tenant_id`, `branch_id`, `case_number`).
- **Backend (Model)**:
  - Relationships: customer, assigned technicians (later), devices (later), payments (later), items (later).
- **API**:
  - `GET /api/app/{tenant}/jobs` list
  - `POST /api/app/{tenant}/jobs` create
  - `GET /api/app/{tenant}/jobs/{job}` detail
  - `PATCH /api/app/{tenant}/jobs/{job}` update
- **AC**:
  - Staff can create a job and retrieve it via list/detail.

### RB-0103 — Case number generator
- **Dependencies**: RB-0102
- **Scope**:
  - Generate formatted case numbers (prefix + digits).
- **Backend**:
  - Tenant setting: `case_prefix`, `case_digits`.
  - Ensure uniqueness even under concurrency.
- **AC**:
  - Creating 50 jobs results in 50 unique case numbers.

### RB-0104 — Staff UI: Jobs list + Job detail skeleton
- **Dependencies**: RB-0102
- **Frontend**:
  - `frontend/src/app/app/[tenant]/jobs/page.tsx` list
  - `frontend/src/app/app/[tenant]/jobs/[jobId]/page.tsx` detail
- **AC**:
  - Job detail page shows case number, status, customer, timestamps.

---

## EPIC 2 — Status Check (no login) + Portal read

### RB-0201 — Public status lookup endpoint (case number)
- **Dependencies**: RB-0102, RB-0002
- **Scope**:
  - Public lookup by case number.
- **Backend**:
  - Token strategy (choose 1):
    - A) Case number only (plugin-like)
    - B) Case number + last name/serial partial
    - C) Case number + time-limited signed token
  - Start with **A** for parity.
  - Branch resolution (Option A): initially resolve branch via tenant `default_branch_id` and only search within that branch.
- **API**:
  - `POST /api/t/{tenant}/status/lookup` { caseNumber }
- **AC**:
  - Valid case number returns job status and minimal summary.
  - Invalid case number returns 404 with generic message.

### RB-0202 — Public status page UI
- **Dependencies**: RB-0201
- **Frontend**:
  - `frontend/src/app/t/[tenant]/status/page.tsx`
  - Display: status, case number, basic timeline (if exists), customer message form (later).
- **AC**:
  - User can enter case number and see job status.

### RB-0203 — Portal tickets list (authenticated customer)
- **Dependencies**: RB-0102, RB-0002
- **API**:
  - `GET /api/t/{tenant}/portal/tickets`
- **UI**:
  - `frontend/src/app/t/[tenant]/portal/tickets/page.tsx`
- **AC**:
  - Customer sees only their own jobs.

---

## EPIC 3 — Job history + customer messaging

### RB-0301 — Job events feed
- **Dependencies**: RB-0003, RB-0102
- **API**:
  - `GET /api/app/{tenant}/jobs/{job}/events`
  - `POST /api/app/{tenant}/jobs/{job}/events` (internal note)
- **UI**:
  - Job detail shows event timeline.
- **AC**:
  - Adding an internal note appears immediately in timeline.

### RB-0302 — Public customer message posting from status page
- **Dependencies**: RB-0201, RB-0003
- **API**:
  - `POST /api/t/{tenant}/status/{caseNumber}/message` { message }
- **Behavior**:
  - Creates a **public** event with actor “guest”.
  - Optional: notify admin email.
- **AC**:
  - Message shows up in staff job timeline.

---

## EPIC 4 — Clients

### RB-0401 — Customer profile fields
- **Dependencies**: RB-0001
- **Backend**:
  - Add profile fields used by plugin: phone, company, tax_id, address lines, city, state, zip, country.
- **AC**:
  - Staff can view customer profile with these fields populated.

### RB-0402 — Staff UI: Clients list/search + jobs
- **Dependencies**: RB-0401, RB-0102
- **API**:
  - `GET /api/app/{tenant}/clients?query=...`
  - `GET /api/app/{tenant}/clients/{client}/jobs`
- **UI**:
  - `frontend/src/app/app/[tenant]/clients/page.tsx`
- **AC**:
  - Search by name/phone/company returns expected clients.

---

## EPIC 5 — Devices + customer devices + attach to job

### RB-0501 — Device catalog (types/brands/devices)
- **Dependencies**: RB-0001
- **Backend (DB)**:
  - `rb_device_types`, `rb_device_brands` (brand image), `rb_devices` (supports variations), all with `tenant_id` + `branch_id`.
- **AC**:
  - Admin can create device type/brand/device via API (UI can come later).

### RB-0502 — Customer devices
- **Dependencies**: RB-0501, RB-0401
- **Backend (DB)**:
  - `rb_customer_devices`: `tenant_id`, `branch_id`, `customer_id`, `device_id?`, label, serial, pin, notes.
- **UI**:
  - Portal page listing customer devices.
- **AC**:
  - Customer can see their device list.

### RB-0503 — Attach devices to job
- **Dependencies**: RB-0502, RB-0102
- **Backend (DB)**:
  - `rb_job_devices`: `tenant_id`, `branch_id`, job_id, customer_device_id, snapshot fields (serial/pin/label) to preserve history.
- **UI**:
  - Job detail includes “Devices” panel.
- **AC**:
  - Attached devices show in job and portal.

---

## EPIC 6 — Services + Parts catalogs

### RB-0601 — Services catalog
- **Dependencies**: RB-0001
- **Backend (DB)**:
  - `rb_service_types`, `rb_services`, both with `tenant_id` + `branch_id`.
- **AC**:
  - Services can be listed via API.

### RB-0602 — Parts catalog
- **Dependencies**: RB-0001
- **Backend (DB)**:
  - `rb_part_types`, `rb_part_brands`, `rb_parts`, all with `tenant_id` + `branch_id`.
- **Note**:
  - Decide later whether to integrate WooCommerce-equivalent; start native.
- **AC**:
  - Parts can be listed via API.

---

## EPIC 7 — Job line items + totals + taxes

### RB-0701 — Taxes
- **Dependencies**: RB-0001
- **Backend (DB)**:
  - `rb_taxes`: `tenant_id`, `branch_id`, name, rate, is_default.
- **AC**:
  - Tenant can have multiple taxes; one default.

### RB-0702 — Job line items
- **Dependencies**: RB-0102, RB-0601, RB-0602
- **Backend (DB)**:
  - `rb_job_items`: `tenant_id`, `branch_id`, job_id, item_type (service/part/fee/discount), ref_id, name_snapshot, qty, unit_price, tax_id, meta_json.
- **API**:
  - `POST /api/app/{tenant}/jobs/{job}/items`
  - `DELETE /api/app/{tenant}/jobs/{job}/items/{item}`
- **AC**:
  - Add/remove items updates job totals.

### RB-0703 — Totals calculator (server-side)
- **Dependencies**: RB-0702, RB-0701
- **Behavior**:
  - Supports inclusive/exclusive tax mode.
- **AC**:
  - Given known items and tax, totals match expected.

---

## EPIC 8 — Payments

### RB-0801 — Payment methods + payments
- **Dependencies**: RB-0102
- **Backend (DB)**:
  - `rb_payment_methods` (configurable, with `tenant_id` + `branch_id`)
  - `rb_payments`: `tenant_id`, `branch_id`, job_id, method, transaction_id, amount, currency, note, status.
- **UI**:
  - Job detail “Payments” panel.
- **AC**:
  - Adding a payment reduces balance.

---

## EPIC 9 — PDF/Print

### RB-0901 — Repair invoice PDF
- **Dependencies**: RB-0703, RB-0801
- **Backend**:
  - Server-side PDF generation (Dompdf or equivalent).
  - Template parity: invoice label, totals, QR link.
- **API**:
  - `GET /api/app/{tenant}/jobs/{job}/invoice.pdf`
- **AC**:
  - PDF downloads and renders correctly for a sample job.

### RB-0902 — Work order / ticket / label PDFs
- **Dependencies**: RB-0901
- **AC**:
  - Each document type is downloadable.

---

## EPIC 10 — Booking + Quote (public)

### RB-1001 — Public booking form creates job
- **Dependencies**: RB-0501, RB-0601, RB-0103
- **API**:
  - `POST /api/t/{tenant}/bookings` (device + service selection + customer info)
- **AC**:
  - Booking creates job in initial status and sends confirmation email.

### RB-1002 — Quote request form
- **Dependencies**: RB-1001
- **AC**:
  - Quote request creates an estimate or job stub.

---

## EPIC 11 — Estimates

### RB-1101 — Estimates model + items
- **Dependencies**: RB-0702
- **Backend (DB)**:
  - `rb_estimates`, `rb_estimate_items`, both with `tenant_id` + `branch_id`.
- **AC**:
  - Estimate can be created and viewed.

### RB-1102 — Approve/reject estimate (public)
- **Dependencies**: RB-1101
- **API**:
  - `POST /api/t/{tenant}/estimates/{estimate}/approve`
  - `POST /api/t/{tenant}/estimates/{estimate}/reject`
- **AC**:
  - Status changes tracked in events.

---

## EPIC 12 — Signature Workflow

### RB-1201 — Signature settings + trigger rules
- **Dependencies**: RB-0101
- **Backend**:
  - Tenant settings: enable pickup/delivery signatures, trigger status, after-signature status.
- **AC**:
  - Settings can be read/updated via API.

### RB-1202 — Public signature request + submit
- **Dependencies**: RB-1201, RB-0102, RB-0003
- **Backend (DB)**:
  - `rb_signatures`: `tenant_id`, `branch_id`, job_id, type (pickup/delivery), storage_key, signed_at, signer_name.
- **API**:
  - `POST /api/t/{tenant}/jobs/{caseNumber}/signature` (upload)
- **AC**:
  - Submitting signature stores it and emits event; job status updates if configured.

---

## EPIC 13 — Time Logs

### RB-1301 — Time log entries + activities
- **Dependencies**: RB-0102
- **Backend (DB)**:
  - `rb_time_logs`: `tenant_id`, `branch_id`, job_id, technician_id, activity, start_at, end_at, minutes, notes, hourly_rate, approvals.
- **AC**:
  - Technician can create a time log on an assigned job.

---

## EPIC 14 — Expenses

### RB-1401 — Expense categories + expenses
- **Dependencies**: RB-0102
- **Backend (DB)**:
  - `rb_expense_categories`, `rb_expenses`, both with `tenant_id` + `branch_id`.
- **AC**:
  - Manager can create an expense linked to a job.

---

## EPIC 15 — Maintenance reminders + SMS

### RB-1501 — Maintenance reminders definitions + logs
- **Dependencies**: RB-0501
- **Backend (DB)**:
  - `rb_maintenance_reminders`, `rb_reminder_logs`, both with `tenant_id` + `branch_id`.
- **AC**:
  - Reminder can be created and a “test send” produces a log.

### RB-1502 — Reminder scheduler job
- **Dependencies**: RB-1501
- **Backend**:
  - Scheduled job runs daily/hourly and evaluates reminders against jobs/devices.
- **AC**:
  - Running scheduler creates logs for due reminders.

### RB-1503 — SMS gateways abstraction
- **Dependencies**: RB-1501
- **Backend**:
  - Config storage per tenant; send/test endpoints.
- **AC**:
  - Test SMS endpoint returns success/failure with provider response.

---

## EPIC 16 — Reviews/Feedback + Reports

### RB-1601 — Feedback requests + review capture
- **Dependencies**: RB-0102
- **Backend**:
  - `rb_feedback_log`, `rb_reviews`, both with `tenant_id` + `branch_id`.
- **AC**:
  - Customer can submit a review via tokenized link.

### RB-1602 — Core reports (minimal)
- **Dependencies**: RB-0801, RB-0703
- **Scope**:
  - Revenue by date, jobs by status, technician time summary.
  - Reports page loads and matches computed totals for seed data.

# 99smartx SaaS Conversion Research (Work In Progress)

This repository contains research and analysis for converting the **RepairBuddy** WordPress plugin (WordPress.org slug: `computer-repair-shop`) into **99smartx**, a **standalone multi-tenant SaaS**.

- **Plugin source (local):** `docs/computer-repair-shop-plugin`
- **Objective:** achieve **100% conversion of the entire plugin** feature set, then later split into SaaS modules.
- **Customer access model:** **case-number based, no login** (matching the plugin’s status check behavior).

## Scope decisions

- **In scope:** everything present in the plugin codebase (CPTs, custom tables, admin pages, shortcodes, AJAX endpoints, notifications, reports, printing/PDF, reminders/cron, time logs, expenses, signatures, etc.).
- **Customer portal:** customers can interact using a **case number** (and sometimes a device serial/IMEI) without requiring authentication.

---

## Plugin version reference

- The local plugin folder indicates version around **`4.1121`** (also some classes show `4.1111+` headers).

---

# 1) Core data model (authoritative from code)

## 1.1 Custom Post Types (CPTs)

- **Jobs:** `rep_jobs`
  - The core “ticket/work order” entity.
- **Estimates:** `rep_estimates`
  - Can be approved/rejected by customer and converted into a job.
- **Devices:** `rep_devices`
- **Other Devices:** `rep_devices_other`
- **Services:** `rep_services`
- **Parts:** `rep_products`
- **Reviews:** `rep_reviews`

## 1.2 Custom taxonomies

- `device_brand`
- `device_type`
- `service_type`
- `part_type`
- `brand_type`

These drive booking filters, catalog organization, and device/service/part categorization.

## 1.3 Custom database tables (created on activation)

From `activate.php`, the plugin uses custom tables (prefixed with `wp_` / `$wpdb->prefix` at runtime):

- **Statuses**
  - `wc_cr_job_status`
  - `wc_cr_payment_status`
- **Job financials & line items**
  - `wc_cr_payments`
  - `wc_cr_order_items`
  - `wc_cr_order_itemmeta`
  - `wc_cr_taxes`
- **Job lifecycle / logs**
  - `wc_cr_job_history`
  - `wc_cr_feedback_log`
- **Scheduling / reminders**
  - `wc_cr_maint_reminders`
  - `wc_cr_reminder_logs`
- **Devices owned by customers**
  - `wc_cr_customer_devices`
- **Time tracking**
  - `wc_cr_time_logs`
- **Expenses**
  - `wc_cr_expense_categories`
  - `wc_cr_expenses`
- **Job numbering / mapping**
  - `wc_cr_jobs` (maps `post_id` to a numeric job id used for formatted job numbers)

---

# 2) Roles / permissions model

Created/ensured during activation:

- `customer`
- `technician`
- `store_manager`
- `administrator` (WordPress native; plugin capabilities extend access)

SaaS conversion will need equivalent RBAC, even if customer-facing access is case-number based.

---

# 3) Primary UI entry points

## 3.1 Frontend shortcodes (customer-facing)

Shortcodes are the plugin’s primary public UX entry points:

- **Booking (job intake):**
  - `[wc_book_my_service]`
  - `[wc_book_type_grouped_service]`
  - other booking-related shortcodes loaded via `lib/shortcodes/shortcodes.php`

- **Status check (case-number portal):**
  - `[wc_order_status_form]`
  - Allows:
    - lookup by **case number**
    - (optionally) lookup by **serial/IMEI** in device data
    - customer messages + attachments

- **Warranty intake:**
  - `[wc_book_my_warranty]`

- **Quote request intake:**
  - `[wc_request_quote_form]`
  - Creates a job with status `quote` and a generated case number.

- **Catalog display:**
  - `[wc_list_services]`
  - `[wc_list_products]`

- **Reviews:**
  - `[wc_get_order_feedback]` (customer review form page)
  - `[wcrb_display_reviews]` (display reviews)

## 3.2 Admin/backoffice

The plugin exposes many admin pages and CPT menus (see `admin_menu.php`) including:

- Appointments / Jobs & Estimates calendar
- Jobs, Estimates
- Devices (and brands/types), Services, Parts
- Payments
- Reports
- Reviews
- Reminder logs
- Clients + Customer Devices
- Technicians/Managers
- Time logs + hourly rates
- Expenses

---

# 4) Core workflows (how modules connect)

## 4.1 Booking → Job OR Estimate

Entry: booking shortcodes.

Typical flow:

- Customer chooses device type/brand/device (CPT + taxonomy)
- Select services (service CPT + pricing logic)
- Submit creates:
  - a **job** (`rep_jobs`) OR
  - an **estimate** (`rep_estimates`) (depending on settings)
- System generates/stores a **case number** (`_case_number`) and associates:
  - customer (`_customer`)
  - technician(s) (`_technician`, can be multiple)
  - device data (`_wc_device_data`)
  - job details (`_case_detail`)

Notifications:
- booking emails to customer/admin
- optional SMS (if enabled)

## 4.2 Estimate → approve/reject → convert to job

Entry: estimate email links.

- Customer receives a link to approve/reject
- On approval, estimate is converted to a job

## 4.3 Job execution lifecycle (staff)

Entry: job admin screen (`rep_jobs`).

Operations include:

- status lifecycle (`_wc_order_status` / label)
- technician assignment
- line items (parts/services/extras/products)
- taxes and totals
- payments (on-site and online)
- job history logs (public and private)
- printing invoices/tickets/work orders/labels
- signature requests and capture

Status changes trigger:

- email updates (optionally with PDF attachment)
- SMS updates (status-based)

## 4.4 Customer status check + messaging (no login)

Entry: `[wc_order_status_form]`

- Customer enters case number (or serial, if enabled)
- System displays:
  - status
  - selected invoice fields
  - public job history
- Customer can post a message and attach files
  - stored as public history/extra items
  - can notify staff

## 4.5 Payments

- **On-site payments:** recorded into `wc_cr_payments` and reflected in payment status/meta.
- **Online payments:** integrated with WooCommerce (generates an order/payment link; invoice embeds payment link).

---

# 5) Notifications & templating

## 5.1 Email

- Primary email system class: `lib/includes/classes/class-emails.php`
- Supports templating via keyword replacement.
- Can attach PDFs (invoice/work order) generated via Dompdf.

## 5.2 SMS

- Primary SMS system class: `lib/includes/classes/class-sms_system.php`
- Supports multiple providers (Twilio, BulkGate, etc.)
- SMS can be triggered based on job status.

---

# 6) Printing / PDF / Documents

## 6.1 Print templates

Under `lib/includes/reports/`:

- `large_invoice.php` (invoice rendering)
- `repair_order.php` (repair order print)
- `repair_label.php` (label print)
- `report_functions.php` (report generator UI + job filtering)

## 6.2 PDF generation

`lib/includes/classes/class-pdf_maker.php` uses **Dompdf**.

- Generates invoice/work order PDFs.
- Can stream PDFs to browser or save to uploads for email attachments.

---

# 7) Digital Signature Workflow

Implementation: `lib/includes/classes/class-wcrb_signature.php`

- Configurable pickup and delivery signature workflows.
- Signature requests are sent via email/SMS with a **tokenized verification code** stored in job meta.
- Signature submission endpoint verifies:
  - nonce
  - verification code
  - **case number matches job**
  - optional expiry/timestamp
- Signature is stored as a file upload and logged into job history/extra items.
- Can automatically change job status after signature submission and trigger notifications.

---

# 8) Maintenance Reminders

Implementation: `lib/includes/classes/class-maintenance_reminder.php`

- Reminder definitions stored in `wc_cr_maint_reminders`.
- Sends reminders to customers after a job delivery date + configured interval.
- Enforces:
  - reminder-level last execution spacing
  - job-level `_last_reminder_sent` spacing
- Sends email/SMS and logs to reminder logs.

---

# 9) Reviews / Feedback

Implementation: `lib/includes/classes/class-reviews.php`

- Review CPT: `rep_reviews`
- Review request flow:
  - Staff can request feedback
  - System can auto-request feedback (pro gated)

Automation:
- Schedules `wcrb_review_daily_event` via WP-Cron (`wp_schedule_event` daily).
- Auto request logic checks job status and meta gates, then sends email/SMS with feedback link.

---

# 10) Appointments / Calendar

Implementation: `lib/includes/classes/class-appointments.php`

- Admin “Jobs & Estimates Calendar” based on FullCalendar.
- AJAX endpoint `wcrb_get_calendar_events` returns events filtered by:
  - jobs/estimates
  - date field (pickup/delivery/next service/creation)
  - assignment filters

---

# 11) Time Logs + Expenses

## 11.1 Time logs

Implementation: `lib/includes/classes/class-wcrb_time_management.php`

- Stores entries in `wc_cr_time_logs`.
- Supports activities, billable flags, rates/cost, and technician-level stats.

## 11.2 Expenses

Implementation: `lib/includes/classes/class-expense-manager.php`

- Stores expenses in `wc_cr_expenses` with categories in `wc_cr_expense_categories`.
- Supports:
  - expense numbering
  - tax calculation per category
  - filtering/pagination
  - job expense totals
  - linking labor expenses to time logs

---

# 12) Reporting

Reporting is primarily job filtering + grouping.

Key files:

- `lib/includes/reports/report_functions.php` (core filter UI + job selection)
- `lib/includes/classes/class-reports_technicians.php`
- `lib/includes/classes/class-reports_customers.php`

Reports include:

- daily sales summary
- jobs by technician / technicians summary
- jobs by customer / customers summary

---

# 13) Notes for SaaS conversion

## 13.1 “No login” customer experience

To match the plugin behavior, customer interactions must support:

- status check by **case number**
- optional lookup by serial/IMEI
- messaging + file uploads
- signature requests and submission
- feedback/review links

## 13.2 Job identity

The plugin uses both:

- **Case number** (`_case_number`) used publicly.
- **Formatted job number** derived from `wc_cr_jobs.job_id` (zero-padded) for internal display/printing.

SaaS should preserve both concepts for full parity.

---

# 14) Parts (rep_products) + Part Variations + Per-Device Pricing

Parts are modeled as CPT **`rep_products`** and used as billable line items on jobs.

Key implementation:

- `lib/includes/classes/class-device_parts.php` (class `WCRB_DEVICE_PARTS`)

Capabilities/permissions:

- Store managers and technicians are granted capabilities for `rep_products` during activation (`activate.php` -> `wc_capability_store_manager()`).

Part “variations”:

- A part post can contain multiple “sub-part” variants tracked in post meta: `'_sub_parts_arr'`.
- UI allows adding a new sub-part variation (`wcrb_append_new_part` AJAX).
- Variant-specific fields are stored using an ID prefix (e.g. `{variant}_part_title`, `{variant}_price`, etc.).

Per-device pricing model (override precedence):

`WCRB_DEVICE_PARTS::get_price_by_device_for_part($device_id, $part_id, $_partID)` resolves the effective price/stock/manufacturing code using this precedence:

- default part fields (on the part post)
- device type overrides (`device_type` taxonomy)
- device brand overrides (`device_brand` taxonomy)
- exact device override (by device post ID)

Each override level also has an enable/disable flag (`active`/`inactive`).

Meta keys used for overrides (examples):

- Type level:
  - `type_price_{partVariant}_{typeId}`
  - `type_stock_code_{partVariant}_{typeId}`
  - `type_manufacturing_code_{partVariant}_{typeId}`
  - `type_status_{partVariant}_{typeId}`
- Brand level:
  - `brand_price_{partVariant}_{brandId}`
  - `brand_stock_code_{partVariant}_{brandId}`
  - `brand_manufacturing_code_{partVariant}_{brandId}`
  - `brand_status_{partVariant}_{brandId}`
- Device level:
  - `device_price_{partVariant}_{deviceId}`
  - `device_stock_code_{partVariant}_{deviceId}`
  - `device_manufacturing_code_{partVariant}_{deviceId}`
  - `device_status_{partVariant}_{deviceId}`

Notes:

- The part pricing UI includes “select all” checkbox behaviors for activating/deactivating many overrides.

---

# 15) My Account Dashboard (logged-in portal)

This is a **logged-in** portal (distinct from the **case-number/no-login** status check).

Key implementation:

- `lib/includes/classes/class-wcrb_myaccount_dashboard.php` (shortcode + template routing + assets)
- `lib/templates/my_account/myaccount_dashboard.php` (template; selects content partials by `?screen=...`)
- `lib/includes/classes/class-wcrb_myaccount_jobs.php` (job/estimate listing/filtering logic)

Entry point:

- Shortcode: `[wc_cr_my_account]`
- Activation creates a default “My Account” WP page pointing at this shortcode (`activate.php` -> `wc_rb_create_default_pages()`).

Routing model:

- Page uses `?screen=<screenName>` to switch content.
- Example screens in template map include:
  - `dashboard`, `jobs`, `jobs_card`, `estimates`, `estimates_card`, `calendar`, `timelog`, `expenses`, `expense_categories`, `reviews`, `profile`, `settings`, `support`, `edit-job`, `print-screen`.

Role-gated navigation and access:

- The dashboard builds nav entries with explicit allowed roles (e.g. `calendar` and `timelog` are limited to `administrator`, `store_manager`, `technician`).
- Job access checks are enforced in code via `WCRB_MYACCOUNT_DASHBOARD::have_job_access($job_id)`:
  - administrators/store managers: full access
  - technicians: access if assigned via `_technician` meta (supports both scalar and serialized array patterns)
  - customers: access only if `_customer` equals current user

Implications for SaaS conversion:

- There are **two customer experiences** to preserve:
  - **No-login** case-number portal (status check)
  - **Logged-in dashboard** portal (my account)

---

# 16) Multi-store support (partial)

The plugin supports associating jobs/estimates to a store using post meta:

- Job/estimate store link: `'_store_id'`

How `_store_id` is set:

- Booking flow: `lib/shortcodes/book_my_service.php` saves `_store_id` when `wc_rb_ms_select_store` is provided.
- Store selection UI is injected into booking forms via:
  - `apply_filters('rb_ms_store_booking_options', '')`
  - (implementation not located yet in the codebase; likely provided by a Pro module/addon or another file)

How `_store_id` is used:

- Print templates and payment outputs read store-specific meta from the referenced store record:
  - `'_store_address'`
  - `'_store_email'`
  - These override the global business address/email on invoices/repair orders when present.
- SMS configuration can be overridden per-store (example for SMS Chef gateway):
  - `'_smschef_secret'`, `'_smschef_mode'`, `'_smschef_device_id'`, `'_smschef_sim'`

Store entity status:

- Code references a `store` post type in at least one admin menu entry, but it is commented out (`admin_menu.php`).
- No `register_post_type('store', ...)` was found in the current codebase, which suggests store records may be implemented in a separate/pro package or under a different post type.

---

# Current progress

- Feature inventory is **in progress**.
- Module mapping is **in progress**.
- Next step after completing the inventory: produce a final “module catalog” for implementation planning.

---

# Platform Admin Tenant Detail Page Plan (`/admin/tenants/{id}`)

This section defines the target structure for the **landlord/platform admin** tenant detail page (internal ops screen) at:

- `/admin/tenants/{id}`

Goal: provide a single operational control panel for a tenant: identity + health + plan/entitlements + billing + diagnostics + auditability + safe actions.

## Proposed tabs

### 1) Overview

- Tenant identity: ID, name, slug, status, timestamps (created/activated/suspended/closed)
- Plan + effective entitlements summary
- Operational snapshot KPIs (real data): users, jobs, payments, last login
- Quick actions: impersonate, suspend/unsuspend, close, add internal note, open diagnostics

### 2) Users & Access

- Owner card: name/email, MFA enabled, status, last login
- Users snapshot/table: status, role, last login, created
- Actions: disable/enable user, send reset link, force logout (user or tenant-wide)

### 3) Plan & Entitlements

- Plan picker (set/clear) with required reason
- Effective entitlements view (plan defaults + overrides)
- Overrides editor (form preferred; JSON fallback)
- Change history (from audit log)

### 4) Billing

Replace current mock UI with a provider-agnostic, DB-backed view:

- Subscription status, current period, next renewal
- Last payment, failed payments count
- Invoices list (and downloads) when supported
- Actions: open billing portal (only if configured), optional manual overrides if policy allows

### 5) Diagnostics

- Tenant health checks (queue/email/SMS/webhooks/storage)
- Recent errors (tenant-scoped, sanitized)
- Optional performance summaries (latency, slow endpoints) if instrumentation exists

### 6) Audit Log

- Tenant-scoped audit timeline: action, actor, timestamp, reason, reference_id
- Before/after diffs for plan/entitlements/status changes
- Export (CSV)

### 7) Support & Internal Notes

- Internal notes (staff-only), tagged/pinned
- Support tickets list + create ticket
- Optional attachments (screenshots/log exports)

### 8) Danger Zone

- Suspend/unsuspend/close tenant
- Typed confirmations for destructive actions
- Password reset for owner (high-risk): reason required, “copy once”, always audited

## API mapping

### Existing APIs (already in backend)

- `GET /api/admin/tenants/{tenant}`: tenant + owner
- `PATCH /api/admin/tenants/{tenant}/suspend`
- `PATCH /api/admin/tenants/{tenant}/unsuspend`
- `PATCH /api/admin/tenants/{tenant}/close`
- `POST /api/admin/tenants/{tenant}/owner/reset-password`
- `PUT /api/admin/tenants/{tenant}/plan`
- `PUT /api/admin/tenants/{tenant}/entitlements`
- `POST /api/admin/impersonation`
- `GET /api/admin/tenants/{tenant}/diagnostics`

### Missing APIs (recommended to make the page fully operational)

- Tenant summary payload (KPIs + plan + effective entitlements)
  - Option A: expand `GET /api/admin/tenants/{tenant}` to include `plan`, `kpis`, `effective_entitlements`
  - Option B: add `GET /api/admin/tenants/{tenant}/summary`
- Entitlements read endpoints
  - `GET /api/admin/tenants/{tenant}/entitlements` (effective + overrides + defaults)
- Audit log read endpoints
  - `GET /api/admin/tenants/{tenant}/audit` (filterable timeline)
  - `GET /api/admin/audit/{event}` (full details/diff)
- Landlord user management endpoints (if not requiring impersonation)
  - `GET /api/admin/tenants/{tenant}/users`
  - `PATCH /api/admin/tenants/{tenant}/users/{user}/status`
  - `POST /api/admin/tenants/{tenant}/users/{user}/reset-password-link`
  - `POST /api/admin/tenants/{tenant}/sessions/revoke` (tenant-wide)
 - Billing read endpoints (provider-agnostic)
  - `GET /api/admin/tenants/{tenant}/billing`
  - `GET /api/admin/tenants/{tenant}/billing/invoices`
  - Optional: `POST /api/admin/tenants/{tenant}/billing/sync`

---

# Configurable subscription packages (architecture plan)

Goal: implement configurable subscription packages (plans, entitlements, pricing) for 99smartx SaaS, while keeping billing **provider-agnostic** so a provider (Stripe/Paddle/etc.) can be integrated later without rewriting business logic.

## Scope

- **Subscriptions are tenant-only** (one subscription per `tenant_id`).
- **Admin-configurable**:
  - Plan names/marketing content
  - Entitlements (feature flags + limits)
  - Full pricing (monthly/annual, trial, currency)
- **Required in scope**: taxes + invoicing.
- **Grandfathering**: configurable per catalog change (can be yes/no depending on the change).

## Design principles

- **App is the source of truth for entitlements**: billing proves payment; the app determines what is allowed.
- **Immutable versions** for anything that impacts billing/entitlements:
  - Editing a plan that impacts customers creates a **new version**.
  - Existing subscriptions keep pointing at their purchased version unless migrated.
- **Strict tenant scoping**: all subscription, invoice, and entitlement checks must filter by `tenant_id`.
- **Auditability**: all plan changes, entitlement changes, subscription state changes, and invoice actions produce events.

## Domain model (recommended)

### Catalog (global)

- `billing_plans`
  - Stable identity: `code`, `name`, `description`, `is_public`
- `billing_plan_versions` (immutable snapshots)
  - `plan_id`, `version`, `status` (`draft|active|retired`), `effective_from`
- `billing_prices` (immutable prices)
  - `plan_version_id`, `currency`, `amount_cents`, `interval_unit` (`month|year`), `trial_days`, `is_default`, `effective_from`

Currency decision:

- Currency is **per-tenant**.
- Operational implication: on subscription assignment/change, validate that a matching `billing_prices.currency` exists for the tenant’s currency.

### Entitlements

- `entitlement_definitions`
  - Global catalog of entitlements with types: `boolean|integer|decimal|string|json`
  - Examples:
    - `max_users` (integer)
    - `customer_portal_enabled` (boolean)
- `plan_entitlements`
  - `plan_version_id`, `entitlement_key`, `value_json`

### Tenant subscription (tenant-scoped)

- `tenant_subscriptions`
  - `tenant_id`, `status`, `plan_version_id`, `price_id`, period dates, cancel flags
- Provider placeholders for later integration:
  - `billing_provider_accounts` (global)
  - `tenant_billing_customers` (tenant+provider mapping)
  - `tenant_billing_subscriptions` (subscription+provider mapping)

### Invoicing (tenant-scoped)

- `invoices` + `invoice_lines`
  - Store tax snapshots and amounts as-of invoice issuance.
- `invoice_sequences`
  - Controls invoice numbering.

### Audit trail

- `subscription_events`
  - Append-only, tenant-scoped timeline for subscription and invoice events.

## Subscription lifecycle (state machine)

Recommended subscription states:

- `trialing`
- `active`
- `past_due`
- `paused` (optional)
- `canceled`

Recommended transitions:

- **Create**: `trialing` (if trial) else `active`
- **Renew**: extend `current_period_end`, issue invoice
- **Upgrade**: immediate entitlements change (policy-driven)
- **Downgrade**: schedule at period end (recommended)
- **Cancel**: cancel at period end (default) or immediate (admin)
- **Payment failure**: move to `past_due` + grace policy

## Provider-agnostic billing abstraction

Introduce a billing interface with a pluggable adapter layer:

- Catalog remains in local DB.
- Provider adapter (later) offers capabilities like:
  - `createCheckoutSession(tenant_id, price_id, addons[])`
  - `syncSubscription(provider_subscription_id)`
  - `cancelSubscription(...)`
  - `changeSubscriptionPrice(...)`

For now (no provider), support **manual/ops flows**:

- Admin can activate a tenant subscription internally.
- Invoices can be issued and marked paid manually (with full audit log).

## Taxes and invoicing approach

Minimum viable model (works now, extensible later):

- Maintain a tax catalog:
  - `tax_profiles` + `tax_rates` with effective dates
- On invoice issuance:
  - snapshot tax rates into invoice lines
  - compute totals and lock the invoice

Tax decision:

- Use **VAT rules** (customer country + VAT number), including reverse charge / 0% VAT scenarios when applicable.
- Store VAT evidence as invoice snapshots (example):
  - customer country
  - VAT number (if provided)
  - whether VAT was applied and why (reason code)

## Admin UI / configuration rules

- Editing plan marketing fields is safe anytime.
- Changes to entitlements or pricing create a new plan version.
- Support scheduling with `effective_from`.
- On activation, choose grandfathering policy:
  - keep existing subscriptions on their current version
  - migrate subscriptions at renewal or immediately

## Milestones (implementation backlog)

1) **Schema + seed catalog**
2) **Subscription + invoice core**
3) **Entitlement enforcement layer**
4) **Admin UI for plan builder**
5) **Provider integration later (adapter + webhooks + reconciliation)**

## Phased implementation plan (assignable to AI agents)

This plan assumes no external billing provider initially, but keeps clear seams for adding one later. Currency is **per-tenant** and taxes use **VAT rules**.

### Phase 0: Alignment & invariants

- Agent: **Architect Agent**
- Deliverables:
  - Confirm invariants: tenant-only subscription, strict `tenant_id` scoping, immutable plan versioning.
  - Finalize VAT scenarios supported in v1 (same-country VAT, reverse charge, non-VAT).
  - Decide seller country source (env/config) and invoice numbering format.
- Acceptance checks:
  - Written decisions captured in this README section.

### Phase 1: Database schema + migrations

- Agent: **DB/Migrations Agent**
- Deliverables:
  - Migrations for:
    - `billing_plans`, `billing_plan_versions`, `billing_prices`
    - `entitlement_definitions`, `plan_entitlements`
    - `tenant_subscriptions`, `subscription_events`
    - `invoice_sequences`, `invoices`, `invoice_lines`
    - VAT/tax catalog tables (e.g., `tax_profiles`, `tax_rates`) and invoice tax snapshot fields
  - Add tenant billing fields:
    - `tenants.currency`
    - `tenants.billing_country`, `tenants.billing_vat_number` (nullable)
    - `tenants.billing_address_json` (or structured fields)
  - Seed data:
    - entitlement definitions (start with `max_users` + 3-5 feature flags)
    - initial plan(s) + version(s) + price(s) for at least 1 currency
- Acceptance checks:
  - Migrations run cleanly on empty DB.
  - Unique constraints enforce “1 default price per plan-version/currency/interval”.

### Phase 2: Core domain services (backend)

- Agent: **Backend Domain Agent**
- Deliverables:
  - `EntitlementsService`:
    - resolve effective entitlements from subscription’s `plan_version_id`
    - typed getters and caching strategy
  - `SubscriptionService`:
    - create/change/cancel subscription
    - enforce per-tenant currency compatibility with `billing_prices.currency`
    - emit `subscription_events`
- Acceptance checks:
  - Unit tests for entitlement resolution and currency mismatch failures.

### Phase 3: VAT + invoicing engine

- Agent: **Billing/Tax Agent**
- Deliverables:
  - `InvoicingService`:
    - invoice number generation via `invoice_sequences`
    - build invoice lines from subscription price
    - VAT computation (country + VAT number + seller country)
    - snapshot VAT evidence into `invoices.tax_details_json` and line fields
  - Invoice status transitions:
    - `draft -> issued -> paid` (+ optional `void`)
  - PDF generation integration (reuse existing PDF approach where applicable)
- Acceptance checks:
  - Test cases for VAT scenarios:
    - same-country VAT applied
    - reverse charge / 0% VAT when VAT number is provided and rules allow
    - non-VAT region handling (if applicable to your target markets)

### Phase 4: Admin APIs

- Agent: **Backend API Agent**
- Deliverables:
  - Catalog APIs (admin):
    - plan CRUD (marketing fields)
    - version create-from-current, activate/retire
    - prices CRUD per version
    - entitlements CRUD per version
  - Tenant billing APIs (admin):
    - assign/change/cancel subscription
    - issue invoice, mark paid, list invoices, download invoice PDF
  - Authorization: platform admin only
- Acceptance checks:
  - API requests are tenant-safe and audited.
  - Attempts to mutate an active plan version are rejected.

### Phase 5: Admin UI (catalog + tenant billing)

- Agent: **Frontend Admin UI Agent**
- Deliverables:
  - Plan builder UI:
    - plan list/detail
    - version editor (draft), activation wizard (grandfathering policy)
    - entitlements editor (typed)
    - price editor filtered by currency
  - Tenant billing UI:
    - show current subscription + currency + VAT info
    - assign/change plan with currency validation
    - invoices list + issue + mark paid + download
- Acceptance checks:
  - UI prevents selecting prices not matching tenant currency.
  - UI displays VAT evidence snapshot on invoice view.

### Phase 6: Enforcement + guards

- Agent: **Product Enforcement Agent**
- Deliverables:
  - Enforce `max_users` on user creation/invite.
  - Enforce 1-2 feature flags end-to-end (UI + API guard).
  - Add clear error messaging for over-limit operations.
- Acceptance checks:
  - Over-limit attempts fail predictably.
  - Feature flags actually disable relevant endpoints and UI actions.

### Phase 7: QA, hardening, and observability

- Agent: **QA/Testing Agent**
- Deliverables:
  - Integration tests for:
    - plan versioning immutability
    - subscription lifecycle
    - invoice issuance and VAT snapshots
  - Regression checks for tenant scoping.
  - Minimal logging for subscription/invoice events.
- Acceptance checks:
  - Test suite passes and critical flows are covered.

### Phase 8 (later): Provider integration

- Agent: **Provider Integration Agent**
- Deliverables:
  - Implement billing provider adapter(s)
  - Webhook ingestion + reconciliation jobs
  - Replace manual payment marking with provider events
- Acceptance checks:
  - Provider subscription state reconciles with `tenant_subscriptions` without breaking entitlements.

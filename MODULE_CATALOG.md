# Module Catalog (Implementation Planning)

This document converts the current feature inventory in `README.md` into an implementable **SaaS module map**.

Guiding constraints:

- **Feature parity goal:** 100% conversion of the WordPress plugin feature set.
- **Customer access model:** **case-number based, no login** (status-check portal) must be supported.
- **Multi-tenant SaaS:** every module must be tenant-aware (data isolation + tenant-scoped configuration).

---

## Conventions used in this catalog

- **Module type**
  - **Domain**: core business capability (jobs, estimates, payments, etc.)
  - **Platform**: cross-cutting capability (auth, files, notifications, etc.)
  - **UI**: frontend/backoffice surfaces
- **Key plugin references**
  - CPTs/taxonomies/tables/classes referenced are the source-of-truth for parity.

---

# A) Core Domain Modules

## A1) Job Management (Work Orders)

- **Type**
  - Domain
- **Purpose**
  - Manage repair tickets end-to-end: intake, assignment, lifecycle status changes, history, totals, printing, and customer communications.
- **Primary entities (plugin parity)**
  - CPT: `rep_jobs`
  - Tables: `wc_cr_jobs`, `wc_cr_job_history`, `wc_cr_job_status`
  - Meta concepts: `_case_number`, `_wc_order_status`, `_technician`, `_customer`, `_wc_device_data`, `_case_detail`, `_store_id`
- **Key user journeys**
  - Staff creates/updates a job, assigns technician(s), updates status, adds line items, captures signatures, prints documents.
  - Customer views job status by case number and posts messages/attachments.
- **Key UI surfaces**
  - Backoffice: job list, job detail screen, job status and payment status controls
  - Customer portal: status view and messaging (see module A4)
- **Dependencies**
  - A2 Catalog (services/parts)
  - A3 Payments
  - A5 Documents (printing/PDF)
  - A6 Notifications (email/SMS)
  - B3 File/Attachment Storage
  - B1 RBAC (staff access)

---

## A2) Catalog (Devices, Services, Parts)

- **Type**
  - Domain
- **Purpose**
  - Manage device catalog, service catalog, and part catalog used for booking filters and billable line items.
- **Primary entities (plugin parity)**
  - CPTs:
    - Devices: `rep_devices`
    - Other Devices: `rep_devices_other`
    - Services: `rep_services`
    - Parts/Products: `rep_products`
  - Taxonomies:
    - `device_brand`, `device_type`, `service_type`, `part_type`, `brand_type`
- **Key capabilities**
  - Catalog CRUD
  - Frontend list display: `[wc_list_services]`, `[wc_list_products]`
  - Part variations and per-device pricing overrides
    - Stored in post meta (`_sub_parts_arr`, and device/type/brand override meta)
    - Pricing precedence: default -> type -> brand -> exact device
- **Key UI surfaces**
  - Backoffice: devices/services/parts management
  - Frontend: services/parts listing pages
- **Dependencies**
  - B1 RBAC
  - B2 Tenant Settings (pricing/tax settings)

---

## A3) Payments & Invoicing

- **Type**
  - Domain
- **Purpose**
  - Record on-site payments, reflect payment status, and support online payments (WooCommerce in plugin; SaaS equivalent TBD).
- **Primary entities (plugin parity)**
  - Tables: `wc_cr_payments`, `wc_cr_payment_status`
  - Job linkage: job totals, payment status meta
- **Key capabilities**
  - Record payments (cash/card/etc.)
  - Track payment status
  - Produce invoice/payment outputs in print/PDF
  - Online payment link generation (plugin ties into WooCommerce; SaaS should provide an equivalent integration/module)
- **Key UI surfaces**
  - Backoffice: payments list, job payment panels
  - Customer portal: invoice display and payment link (where applicable)
- **Dependencies**
  - A1 Job Management
  - A5 Documents
  - B2 Tenant Settings (tax, currency, payment methods)
  - B4 Integrations (payment gateway)

---

## A4) Customer Case-Number Portal (No Login)

- **Type**
  - UI + Domain
- **Purpose**
  - Provide a customer-facing portal accessible via **case number** (optionally serial/IMEI) without requiring authentication.
- **Primary entry point (plugin parity)**
  - Shortcode: `[wc_order_status_form]`
- **Key capabilities**
  - Lookup by case number (and optional serial/IMEI)
  - Display status, selected invoice fields, public job history
  - Customer messaging + file attachments
- **Security considerations (SaaS)**
  - Case number should be treated like a shared secret.
  - Optional additional verification (e.g., serial/IMEI match, SMS/email one-time code) may be needed depending on tenant settings.
- **Dependencies**
  - A1 Job Management
  - B3 File/Attachment Storage
  - A6 Notifications (notify staff)

---

## A5) Documents: Printing / PDF / Templates

- **Type**
  - Domain
- **Purpose**
  - Generate invoices, repair orders, labels, and reports as print views and PDFs.
- **Primary entities (plugin parity)**
  - Templates: `lib/includes/reports/large_invoice.php`, `repair_order.php`, `repair_label.php`
  - PDF: `class-pdf_maker.php` (Dompdf)
- **Key capabilities**
  - Print views
  - PDF generation + streaming
  - PDF attachment support for emails
  - Store-specific address/email overrides when `_store_id` is present
- **Dependencies**
  - A1 Job Management
  - A3 Payments
  - A6 Notifications
  - B2 Tenant Settings (branding, templates)

---

## A6) Notifications: Email + SMS

- **Type**
  - Platform
- **Purpose**
  - Send transactional notifications for bookings, status updates, signatures, reminders, and reviews.
- **Primary entities (plugin parity)**
  - Email: `class-emails.php` (keyword templating, PDF attachments)
  - SMS: `class-sms_system.php` (multiple providers, status-triggered)
- **Key capabilities**
  - Template management (per-tenant)
  - Trigger rules (status-based)
  - Provider configuration (per-tenant, optionally per-store)
  - Logging/auditing of sends
- **Dependencies**
  - B2 Tenant Settings
  - B4 Integrations (SMS providers)
  - A5 Documents (PDF attachments)

---

## A7) Digital Signatures

- **Type**
  - Domain
- **Purpose**
  - Request and capture pickup/delivery signatures with tokenized verification.
- **Primary entities (plugin parity)**
  - `class-wcrb_signature.php`
  - Job meta: verification code/token, expiry gates
  - Upload storage for signature image/file
  - History log entry on submission
- **Key capabilities**
  - Signature request delivery (email/SMS)
  - Verification (case number match + token + expiry)
  - Store signature artifact and link to job
  - Optional automatic status change and notifications
- **Dependencies**
  - A1 Job Management
  - A6 Notifications
  - B3 File/Attachment Storage

---

## A8) Estimates (Quotes) + Approval Flow

- **Type**
  - Domain
- **Purpose**
  - Provide estimate creation, send approval/rejection links, and convert approved estimates into jobs.
- **Primary entities (plugin parity)**
  - CPT: `rep_estimates`
  - Estimate approval links via email
- **Key capabilities**
  - Create estimate from booking / staff
  - Customer approve/reject
  - Convert to job on approval
- **Dependencies**
  - A1 Job Management
  - A6 Notifications
  - A5 Documents

---

## A9) Booking / Intake (Jobs, Estimates, Warranty, Quote Request)

- **Type**
  - UI + Domain
- **Purpose**
  - Public-facing intake forms to create jobs or estimates.
- **Primary entry points (plugin parity)**
  - Booking: `[wc_book_my_service]`, `[wc_book_type_grouped_service]`
  - Warranty intake: `[wc_book_my_warranty]`
  - Quote request: `[wc_request_quote_form]`
- **Key capabilities**
  - Device/service selection using catalog + taxonomies
  - Case number generation
  - Create job/estimate depending on settings
  - Optional store selection injection (`rb_ms_store_booking_options` filter in plugin)
- **Dependencies**
  - A1 Job Management
  - A2 Catalog
  - A6 Notifications
  - B2 Tenant Settings (booking rules)

---

## A10) Appointments / Calendar

- **Type**
  - UI + Domain
- **Purpose**
  - Staff calendar for jobs/estimates (pickup/delivery/next service/creation date views).
- **Primary entities (plugin parity)**
  - `class-appointments.php`
  - AJAX endpoint: `wcrb_get_calendar_events`
- **Key capabilities**
  - Calendar view + filters (jobs vs estimates, date field selection, technician assignment)
- **Dependencies**
  - A1 Job Management
  - A8 Estimates
  - B1 RBAC

---

## A11) Time Logs

- **Type**
  - Domain
- **Purpose**
  - Track technician time entries, billable flags, rates/cost, and stats.
- **Primary entities (plugin parity)**
  - Table: `wc_cr_time_logs`
  - Class: `class-wcrb_time_management.php`
- **Key capabilities**
  - CRUD time logs
  - Associate with job/technician
  - Reporting rollups
- **Dependencies**
  - A1 Job Management
  - B1 RBAC

---

## A12) Expenses

- **Type**
  - Domain
- **Purpose**
  - Track business expenses and categories, including job-linked totals and optional linkage to time logs.
- **Primary entities (plugin parity)**
  - Tables: `wc_cr_expenses`, `wc_cr_expense_categories`
  - Class: `class-expense-manager.php`
- **Key capabilities**
  - Expense CRUD + numbering
  - Category tax rules
  - Filters/pagination
  - Link labor expenses to time logs
- **Dependencies**
  - B1 RBAC
  - A11 Time Logs (optional linking)

---

## A13) Maintenance Reminders

- **Type**
  - Domain
- **Purpose**
  - Send follow-up reminders after delivery date + configured intervals.
- **Primary entities (plugin parity)**
  - Tables: `wc_cr_maint_reminders`, `wc_cr_reminder_logs`
  - Class: `class-maintenance_reminder.php`
- **Key capabilities**
  - Define reminder schedules
  - Send email/SMS
  - Enforce spacing gates at reminder-level and job-level
  - Log reminder sends
- **Dependencies**
  - A6 Notifications
  - B5 Background Jobs / Scheduler

---

## A14) Reviews / Feedback

- **Type**
  - Domain
- **Purpose**
  - Request feedback, collect reviews, and display reviews.
- **Primary entities (plugin parity)**
  - CPT: `rep_reviews`
  - Class: `class-reviews.php`
  - Cron: `wcrb_review_daily_event`
  - Shortcodes:
    - `[wc_get_order_feedback]` (review form)
    - `[wcrb_display_reviews]` (display)
- **Key capabilities**
  - Manual request feedback
  - Automated feedback requests based on status/meta gates
  - Customer submission flow via tokenized link
- **Dependencies**
  - A6 Notifications
  - B5 Background Jobs / Scheduler

---

## A15) Reporting

- **Type**
  - Domain
- **Purpose**
  - Sales summaries, jobs by technician/customer, and other operational reporting.
- **Primary entities (plugin parity)**
  - `report_functions.php`
  - `class-reports_technicians.php`
  - `class-reports_customers.php`
- **Key capabilities**
  - Filter by date/status/technician/customer
  - Grouping and totals
  - Export/print/PDF as needed
- **Dependencies**
  - A1 Job Management
  - A3 Payments
  - A11 Time Logs
  - A12 Expenses

---

# B) Platform Modules (Cross-cutting)

## B1) Users, Roles, and RBAC

- **Type**
  - Platform
- **Purpose**
  - Replace WP roles/capabilities with SaaS RBAC while preserving access rules.
- **Roles (plugin parity)**
  - `administrator`, `store_manager`, `technician`, `customer`
- **Key access rules to preserve**
  - Staff has backoffice access per role
  - Technician access restricted to assigned jobs (plugin supports scalar or serialized array patterns)
  - Customer logged-in portal access restricted to own jobs (separate from no-login case-number portal)
- **Dependencies**
  - All modules

---

## B2) Tenant Settings & Configuration

- **Type**
  - Platform
- **Purpose**
  - Store tenant-scoped configuration currently represented by WP options + per-store overrides.
- **Key configuration domains**
  - Business identity, branding, email templates, SMS templates
  - Booking settings (job vs estimate, required fields)
  - Status definitions, payment statuses
  - Tax rules
  - Security gates for no-login portal (optional serial/IMEI requirement)
- **Dependencies**
  - A3 Payments
  - A6 Notifications
  - A9 Booking

---

## B3) File & Attachment Storage

- **Type**
  - Platform
- **Purpose**
  - Centralize storage for uploads from customer messages, signature files, and generated PDFs.
- **Key storage items**
  - Customer attachments (A4)
  - Signature images/files (A7)
  - PDF artifacts (A5)
- **Dependencies**
  - A4 Customer Portal
  - A5 Documents
  - A7 Signatures

---

## B4) External Integrations

- **Type**
  - Platform
- **Purpose**
  - Abstraction layer for providers used by Notifications and Payments.
- **Integrations (plugin parity)**
  - SMS providers: Twilio, BulkGate, etc.
  - Online payments: WooCommerce integration (SaaS equivalent integration TBD)
- **Dependencies**
  - A3 Payments
  - A6 Notifications

---

## B5) Background Jobs / Scheduler

- **Type**
  - Platform
- **Purpose**
  - Replace WP-Cron jobs with a reliable SaaS scheduler.
- **Recurring jobs (plugin parity)**
  - Review auto-requests: `wcrb_review_daily_event`
  - Maintenance reminder processing
- **Dependencies**
  - A13 Maintenance Reminders
  - A14 Reviews

---

# C) UI Applications (SaaS Surfaces)

## C1) Backoffice Admin App

- **Purpose**
  - Staff UI for jobs, estimates, calendar, catalog, payments, customers, reports, time logs, expenses.
- **Role visibility**
  - Admin/store manager: full
  - Technician: assignment-scoped

---

## C2) Public Website Widgets / Pages

- **Purpose**
  - Booking, quote request, warranty, services/products catalog, reviews display.

---

## C3) Logged-in Portal (My Account)

- **Purpose**
  - Preserve the plugin’s logged-in dashboard experience (`[wc_cr_my_account]`) as a separate surface from the case-number portal.
- **Key screens (plugin parity list)**
  - `dashboard`, `jobs`, `jobs_card`, `estimates`, `estimates_card`, `calendar`, `timelog`, `expenses`, `expense_categories`, `reviews`, `profile`, `settings`, `support`, `edit-job`, `print-screen`

---

# D) Multi-store (Partial) / Locations

## D1) Stores / Locations

- **Type**
  - Domain (optional for parity depending on what exists outside the current plugin snapshot)
- **Purpose**
  - Allow jobs/estimates to be associated with a store/location, with store-level overrides for contact details and SMS configuration.
- **Plugin parity notes**
  - `_store_id` exists and is used.
  - A `store` post type is referenced but not registered in the current code snapshot.
  - Booking store-selection injection exists via filter `rb_ms_store_booking_options` but implementation wasn’t found.

---

# E) Dependency Graph (High level)

- **A1 Job Management** depends on
  - A2 Catalog
  - A3 Payments
  - A5 Documents
  - A6 Notifications
  - B1 RBAC
  - B3 File Storage
- **A4 Case-Number Portal** depends on
  - A1 Job Management
  - B3 File Storage
  - A6 Notifications
- **A13 Reminders** and **A14 Reviews** depend on
  - A6 Notifications
  - B5 Scheduler

---

# Next implementation step

Decide the first implementation slice and freeze the MVP boundary for that slice:

- **Slice 1 candidate:** A1 (Jobs) + A4 (Case-number portal) + B3 (Files) + A6 (Notifications basics)
- **Slice 2 candidate:** A9 (Booking) + A2 (Catalog)
- **Slice 3 candidate:** A5 (Documents/PDF) + A3 (Payments)

If you tell me which slice you want to start with, I’ll convert that slice into a deliverable checklist: routes/pages, tables, API contracts, and acceptance criteria.

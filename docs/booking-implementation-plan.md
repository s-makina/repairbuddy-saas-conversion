# Booking implementation plan (100% plugin parity)

This plan defines how to implement the **customer-side Booking form** in the SaaS with **100% functionality parity** with the WordPress plugin’s booking flows.

Plugin source of truth (customer-side booking):
- Shortcode (ungrouped): `lib/shortcodes/book_my_service.php` → `[wc_book_my_service]`
- Shortcode (type-grouped): `lib/shortcodes/type_grouped_service.php` → `[wc_book_type_grouped_service]`
- Shortcode (warranty): `lib/shortcodes/wc_book_my_warranty.php` → `[wc_book_my_warranty]`
- Frontend JS driver: `assets/js/ajax_scripts.js`
- Booking settings: `lib/includes/classes/class-booking-settings.php`

---

## Phase 0: Lock the parity contract (non-negotiables)

- Agent: **Product/Architect Agent**
- Deliverables:
  - Write down the authoritative **Booking Contract** for SaaS matching the plugin.
  - Confirm which booking mode(s) the SaaS will support:
    - `ungrouped` (brand → device)
    - `grouped` (type → brand → device)
    - `warranty` (grouped + extra field `dateOfPurchase` and customer section shown earlier)
  - Confirm whether SaaS booking creates:
    - an **Estimate** by default, OR
    - a **Job** by default, controlled by setting parity: `Send booking forms & quote forms to jobs instead of estimates`.

### 0.0 SaaS authoritative settings keys (must be used)

The SaaS already stores RepairBuddy settings under `tenant.setup_state.repairbuddy_settings` and validates them in:
- Backend: `backend/app/Http/Controllers/Api/App/RepairBuddySettingsController.php`
- Frontend draft types: `frontend/src/app/app/[tenant]/settings/_components/repairbuddy/types.ts`

Booking-related keys used by the SaaS settings UI:
- `settings.booking.customerEmailSubject`
- `settings.booking.customerEmailBody`
- `settings.booking.adminEmailSubject`
- `settings.booking.adminEmailBody`
- `settings.booking.publicBookingMode` (`ungrouped|grouped|warranty`)
- `settings.booking.sendBookingQuoteToJobs`
- `settings.booking.turnOffOtherDeviceBrand`
- `settings.booking.turnOffOtherService`
- `settings.booking.turnOffServicePrice`
- `settings.booking.turnOffIdImeiInBooking`
- `settings.booking.defaultType`
- `settings.booking.defaultBrand`
- `settings.booking.defaultDevice`

Note: there is also an existing estimates key used in the UI:
- `settings.estimates.bookingQuoteSendToJobs`

For robustness and to prevent misconfiguration, the backend must keep these two toggles consistent:
- `settings.booking.sendBookingQuoteToJobs`
- `settings.estimates.bookingQuoteSendToJobs`

### 0.1 Booking Contract (what must match plugin)

#### Customer-visible steps
- **Ungrouped booking**:
  1) Select Brand/Manufacture
  2) Select Device
  3) Select Service (per device)
  4) Customer Information + Job Details
  5) Attachments + GDPR + captcha
  6) Submit → receive case number + confirmation

- **Grouped booking**:
  1) Select Device Type
  2) Select Brand/Manufacture (filtered by type)
  3) Select Device
  4) Select Service (grouped accordion by `service_type` taxonomy)
  5) Customer Information + Job Details
  6) Attachments + GDPR + captcha
  7) Submit

- **Warranty booking**:
  - Same as grouped, but:
    - customer section becomes available earlier
    - includes field: `Date of Purchase` (`dateOfPurchase`)

#### Inputs (must match plugin)
- **Required**:
  - `firstName`
  - `lastName`
  - `userEmail`
  - `jobDetails`
  - at least one selected device
  - service selection per device (or `other_service` free text)
  - captcha success
  - GDPR acceptance (if enabled)
- **Optional**:
  - phone (international input; plugin uses `intl-tel-input` producing hidden `phoneNumber`)
  - city, postal code, address, company, tax id
  - device ID/IMEI (toggleable)
  - pin code/password (toggleable)
  - device note
  - extra device fields (configurable)
  - attachments
  - warranty-only: `dateOfPurchase`

#### Outcomes / side-effects (must match plugin)
- Create or reuse customer:
  - If email exists, associate booking to that customer.
  - If email does not exist, create a user and send an email containing a **one-time password** (OTP) for initial access.
- Create booking record:
  - Create a draft post of type `wcrb_return_booking_post_type()` (job vs estimate depending setting).
  - Generate a **case number** (tenant+branch format).
    - Uniqueness scope: **unique per `tenant_id` + `branch_id`** (matches existing DB constraints on `rb_jobs.case_number` and `rb_estimates.case_number`).
    - Format: include tenant + branch identifiers (e.g. `RB-{TENANT_SLUG}-{BRANCH_SLUG}-{NNNNNN}`) with a per-branch sequence padded to the configured length.
  - Persist booking meta:
    - devices array (`_wc_device_data` equivalent)
    - customer fields (billing/shipping-like)
    - job details (`_case_detail` equivalent)
    - pickup date (plugin sets `_pickup_date` to current date)
    - status label fields
  - Persist service line items (plugin uses `wc_cr_order_items` + meta) with price logic:
    - base service price
    - overrides by device/brand/type
    - optional tax computation
    - `other_service` creates an “extras” line with name but 0 price
- Send emails:
  - Admin booking email
  - Customer booking email (includes case number and **status-check link**)

#### Confirmation UX (must match plugin)
- Success message includes:
  - “We have received your quote request…”
  - displays: `{ case_number }`
  - encourages refreshing to submit another request

---

## Phase 1: SaaS data model + migrations (parity-first)

- Agent: **DB/Migrations Agent**
- Deliverables:
  - Add/confirm schema to support the booking flow and all plugin options.
  - Ensure **strict `tenant_id` scoping** on all created records.

### 1.1 Catalog tables (device/service)
You must represent plugin concepts:
- Device types (taxonomy `device_type`)
- Brands/manufactures (taxonomy `device_brand`)
- Devices (`rep_devices` posts)
- Service types (taxonomy `service_type`)
- Services (`rep_services` posts)

SaaS tables (already implemented):
- `rb_device_types`
- `rb_device_brands`
- `rb_devices`
- `rb_service_types`
- `rb_services`

### 1.2 Service pricing + availability parity
Plugin computes service eligibility and price per device selection:
- Price priority:
  - device override (`device_price_{deviceId}`)
  - else brand override (`brand_price_{brandId}`)
  - else type override (`type_price_{typeId}`)
  - else base `_cost`
- Availability priority:
  - can be set `inactive` by device, brand, or type

SaaS schema (already implemented for pricing):
- `rb_service_price_overrides`
  - fields include: `service_id`, `scope_type` (`device|brand|type`), `scope_ref_id`, `price_amount_cents`, `price_currency`, `tax_id`, `is_active`
  - price precedence is already implemented in `RepairBuddyServicePricingController`:
    - device override → brand override → type override → base price

SaaS schema (added for availability parity):
- `rb_service_availability_overrides`
  - fields: `service_id`, `scope_type` (`device|brand|type`), `scope_ref_id`, `status` (`inactive|active`)
  - this is required because the plugin supports disabling services by device/brand/type independently of pricing.

### 1.3 Booking submission record
You need a durable record for:
- booking request (the “draft job/estimate”)
- per-device details and per-device service selection
- attachments
- case number

SaaS schema (already supports Option A):
- Job tables:
  - `rb_jobs`
  - `rb_job_devices` (device snapshots + extra fields snapshot)
  - `rb_job_items` (services/extras snapshots)
  - `rb_job_attachments`
- Estimate tables:
  - `rb_estimates`
  - `rb_estimate_devices` (device snapshots + extra fields snapshot)
  - `rb_estimate_items` (services/extras snapshots)
  - `rb_estimate_tokens`
  - `rb_estimate_attachments`

Parity requirement:
- Booking submission must be able to land into either `rb_jobs` or `rb_estimates` based on:
  - `settings.booking.sendBookingQuoteToJobs` (kept consistent with `settings.estimates.bookingQuoteSendToJobs`)

Parity requirement: regardless of modeling choice, you must be able to reproduce:
- per-device service line items
- per-device input fields
- attachments with correct visibility and metadata

### 1.4 Settings parity tables
You need tenant-scoped settings for all booking options:
- booking form type (ungrouped vs grouped vs warranty)
- defaults: default type/brand/device
- toggles:
  - disable “Other” brand/device
  - disable “Other Service”
  - disable service prices display
  - disable ID/IMEI input
  - enable pin code input
  - send booking/quote to jobs instead of estimates
- email templates:
  - booking email subject/body to customer
  - booking email subject/body to admin

Implementation approach:
- Use existing SaaS settings mechanism (whatever backs `docs/repairbuddy-settings-ui-coverage-checklist.md`).
- Ensure all booking settings are **tenant-scoped**.

Tenant/branch scoping robustness:
- All RepairBuddy catalog and booking records use the `BelongsToTenantAndBranch` trait (global scopes + enforced context on create).

---

## Phase 2: Backend public APIs (customer booking)

- Agent: **Backend API Agent**
- Deliverables:
  - Tenant-scoped, unauthenticated (“public”) endpoints for catalog lookup and booking submission.
  - Rate limiting + abuse protection.

### 2.1 Public catalog endpoints
These replace plugin AJAX endpoints:
- Plugin: `wcrb_get_brands_by_type`
- Plugin: `wc_rb_mb_update_devices`
- Plugin: `wcrb_return_services_section` / `wc_rb_update_services_list_grouped`

SaaS endpoints (suggested):
- `GET /api/public/t/{tenantSlug}/booking/config`
  - returns booking mode, defaults, toggles, labels, pricing display config
- `GET /api/public/t/{tenantSlug}/device-types`
- `GET /api/public/t/{tenantSlug}/brands?typeId=...`
- `GET /api/public/t/{tenantSlug}/devices?typeId=...&brandId=...`
- `GET /api/public/t/{tenantSlug}/services?deviceId=...&mode=grouped|ungrouped`
  - returns:
    - service list with computed `is_active` and computed `display_price`
    - grouped: services grouped by service_type

### 2.2 Attachments upload parity
Plugin uses a separate AJAX upload then includes file references in submit.

SaaS endpoints (suggested):
- `POST /api/public/t/{tenantSlug}/uploads`
  - multipart
  - returns file IDs + URLs
- Booking submit includes `attachment_ids[]`

Acceptance checks:
- Upload supports multiple files
- Customer can remove an attachment before final submit

### 2.3 Captcha / anti-spam endpoints
Plugin uses captcha actions like `repairbuddy_get_captcha` and verification in submit.

SaaS should implement either:
- Turnstile/reCAPTCHA (preferred), OR
- a first-party captcha generator with server verification.

Endpoints:
- `GET /api/public/t/{tenantSlug}/captcha` (if first-party)
- `POST /api/public/t/{tenantSlug}/captcha/verify` (optional)

Booking submit must fail if captcha invalid.

---

## Phase 3: Booking domain service (authoritative business logic)

- Agent: **Backend Domain Agent**
- Deliverables:
  - A single application service that executes booking end-to-end with parity.

### 3.1 Customer resolution & creation
Parity rules:
- If customer exists (by email): reuse
- Else create customer
  - Plugin behavior: generate password and email login credentials

SaaS parity decision to implement:
- Provide a tenant setting for “customer creation email behavior” with default parity mode:
  - `send_login_credentials` (closest to plugin)
  - alternatives: `send_invite_link`, `do_not_email`

### 3.2 Case number generation
Must match plugin behavior used in booking flows.
- Implement the same generator you use for parity job creation (see `docs/job_handling.md`).
- Ensure uniqueness per tenant.

### 3.3 Create job/estimate draft (depending on setting)
When booking submitted:
- Create job or estimate record with:
  - status = plugin-equivalent default for this flow (plugin uses `quote` internally in booking submit)
  - customer linkage
  - `_pickup_date` equivalent = current date
  - job details

### 3.4 Persist devices + per-device service selections
Must support:
- multiple devices in a single booking
- “Other device” path:
  - allow free text device name
  - preserve brand/type selection if provided
- optional fields:
  - ID/IMEI (unless disabled)
  - pin code/password (if enabled)
  - note
  - extra device fields

### 3.5 Create line items (services/extras)
Parity rules:
- For each device identifier:
  - exactly one selected service OR `other_service` with free text
- Service line item records must store:
  - service name
  - computed price
  - device linkage + device serial/IMEI linkage
- If `other_service`:
  - create “extras” line with 0 price and the provided name

### 3.6 Taxes (if enabled)
Plugin supports taxes toggles and default tax.
- If your SaaS already has taxes, implement booking line item tax snapshots similarly.
- If taxes are not yet complete, still model the seams:
  - computed tax rate per line
  - computed tax amount per line
  - invoice amount mode inclusive/exclusive

---

## Phase 4: Emails & status-check link parity

- Agent: **Backend Notifications Agent**
- Deliverables:
  - Booking emails (admin + customer) with tenant-editable templates.

### 4.1 Templates & keywords
Plugin booking settings include templates with keywords like:
- `{{customer_full_name}}`
- `{{customer_device_label}}`
- `{{status_check_link}}`
- `{{job_id}}`
- `{{case_number}}`
- `{{order_invoice_details}}`

SaaS requirement:
- Implement the same keywords.
- Persist a “booking snapshot” so that `order_invoice_details` renders deterministically.

### 4.2 Status-check link
Customer email must include a link to check status without login.
- Use case-number-based access parity (already a scope decision).
- Booking success page should also show:
  - case number
  - “Check Status” button

---

## Phase 5: Settings UI parity (tenant admin)

- Agent: **Frontend Admin UI Agent**
- Deliverables:
  - Implement Booking settings in SaaS admin (matching `docs/repairbuddy-settings-ui-coverage-checklist.md` “Booking” section).

### 5.1 Booking settings screens
Add/complete:
- Booking email templates:
  - subject/body to customer
  - subject/body to admin
- Toggles:
  - send booking/quote to jobs
  - turn off other device/brand
  - turn off other service
  - turn off service price
  - turn off ID/IMEI input
- Defaults:
  - default type/brand/device

Acceptance checks:
- Settings changes reflect on public booking page immediately (or after cache TTL).

---

## Phase 6: Frontend customer booking experience (Next.js)

- Agent: **Frontend Public UX Agent**
- Deliverables:
  - Public Booking page(s)
  - Welcome page “Book a Repair” button
  - 100% parity behavior and states

### 6.1 Routes
Suggested public routes:
- Booking page:
  - `/t/[tenantSlug]/book`
- Status check (existing or planned):
  - `/t/[tenantSlug]/status`

### 6.2 Welcome page button
- Add CTA button: `Book a Repair`
- Click → navigate to booking page

### 6.3 UI flow parity
Implement wizard-like UI but maintain parity:
- Must support all plugin modes:
  - ungrouped
  - grouped
  - warranty
- Must support:
  - multi-device add/remove
  - “Other” brand/device (if enabled)
  - per-device service selection
  - “Other service” with text
  - attachments upload
  - GDPR acceptance
  - captcha

### 6.4 Defaults and deep-link preselect
Match plugin default selection logic:
- tenant defaults set via settings
- optional query params to preselect:
  - `?wcrb_selected_type=...`
  - `?wcrb_selected_brand=...`
  - `?wcrb_selected_device=...`

### 6.5 UX states parity
- Loading spinners (for brands/devices/services)
- Error messages for missing required fields
- Success message includes case number and next steps

---

## Phase 7: Security, tenant isolation, observability

- Agent: **Security/Platform Agent**
- Deliverables:
  - Public endpoints are safe and tenant-scoped.

Requirements:
- Rate limit booking submissions and uploads (per IP + per tenant)
- CAPTCHA required (as in plugin)
- Strict tenant scoping:
  - every catalog query and booking create is constrained by `tenant_id`
- Audit logging for:
  - booking created
  - customer created
  - emails sent

---

## Phase 8: Parity checklist + acceptance criteria + test plan

- Agent: **QA/Testing Agent**
- Deliverables:
  - Automated tests + a manual parity checklist runbook.

### 8.1 Automated backend tests (feature tests)
Cover:
- Booking submit fails when:
  - captcha invalid
  - missing required fields
  - no devices
  - device has no selected service and no other_service
- Booking submit succeeds when:
  - existing customer by email
  - new customer created
  - multiple devices with different services
  - other device + other service paths
- Price calculation tests:
  - device override wins
  - brand override wins
  - type override wins
  - fallback to base
- Availability tests:
  - service excluded when inactive by device/brand/type

### 8.2 Automated frontend tests (E2E)
- Grouped mode path:
  - type → brand → device → service → submit
- Ungrouped mode path:
  - brand → device → service → submit
- Warranty path:
  - includes dateOfPurchase
- Attachments path:
  - upload → appears in UI → submit

### 8.3 Manual side-by-side parity checklist
For identical catalog/setup, confirm plugin == SaaS:
- Fields present (including toggled fields)
- Defaults selected correctly
- “Other” options behave same
- Services list and prices match
- Case number generated and shown
- Emails sent and contain the same template keyword substitutions
- Status-check link works without login

---

## Phase 9: Rollout & migration

- Agent: **Release/Operations Agent**
- Deliverables:
  - Rollout strategy with feature flags.

Steps:
- Hide booking behind a tenant flag until configuration ready
- Enable per tenant, verify emails and status-check link
- Add monitoring:
  - booking submission success rate
  - upload failure rate
  - captcha fail rate

---

## Definition of Done (100% parity)

All of the following must be true:
- SaaS supports all three plugin booking modes (ungrouped, grouped, warranty) OR you explicitly decide to exclude one and document it as a non-parity decision.
- All plugin booking settings listed in `docs/repairbuddy-settings-ui-coverage-checklist.md` → **Booking** are implemented and affect runtime behavior.
- Multi-device bookings are supported.
- “Other device/brand” and “Other service” behaviors match toggles and match plugin.
- Service pricing and activation logic matches plugin precedence and outputs.
- Attachments work and are persisted and referenced in booking output.
- Captcha is enforced.
- Booking creates job/estimate as configured and emits emails with correct templates.
- Status check without login works using case number.

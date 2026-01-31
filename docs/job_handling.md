# 100% Plugin Parity Plan — Job Creation (SaaS)

## Objective
Make the SaaS **job creation page and behavior match the WordPress plugin 1:1**, specifically the plugin flow behind:
- Shortcode UI: `[wc_start_job_with_device]`
- Handler: `wp_ajax_wc_cr_create_new_job`

“100% parity” means matching:
- **Fields**
- **Validation rules**
- **Defaults**
- **Identifiers (case number format)**
- **Side effects** (e.g., inline customer creation + notification behavior)

---

## 1) Define the Plugin Job-Creation Contract (Source of Truth)

### 1.1 Required Inputs (must match plugin behavior)
From the plugin handler, the job creation must enforce:
- **Device**: `device_post_id` (required)
- **Delivery date**: `delivery_date` (required)
- **Job details**: `jobDetails` (required)
- **Customer**: either
  - Select **existing customer**, OR
  - **Create new customer inline** (first name/last name/email + other fields the plugin collects)

### 1.2 Outputs / Persistence Expectations (must match plugin)
On success, the system must:
- Generate a **plugin-style case number**
- Create the job record and persist plugin-equivalent fields
- Default status to plugin’s **`new`**
- Store device linkage (`device_post_id`) and optional free text device identifier (`devideID`)

### 1.3 Parity Decisions That Must Be Locked
To ensure true parity, decide and implement:
- **Case number strategy**: plugin-style random+timestamp must be the default
- **Status vocabulary**: default to `new` (plugin default in this flow)
- **Device model mapping**: plugin uses “device catalog id”; SaaS must support the same concept

---

## 2) Backend Changes (Do First)

### 2.1 Add Plugin-Parity Fields to Jobs
Add columns/fields on the SaaS job table (e.g. `rb_jobs`) to store plugin-specific fields:
- `plugin_device_post_id` (nullable integer) — plugin `device_post_id`
- `plugin_device_id_text` (nullable string) — plugin `devideID` (IMEI/ID text)
- Ensure existing fields map correctly:
  - `delivery_date` ↔ plugin `delivery_date`
  - `case_detail` ↔ plugin `jobDetails`

> Keep existing SaaS device attachment model if needed, but parity requires the plugin fields be stored exactly.

### 2.2 Implement Plugin Case Number Generation (Default)
Add tenant/branch config to support:
- `case_number_strategy`: `plugin_random_timestamp` | `sequential`
  - Default for parity: **`plugin_random_timestamp`**
- `case_number_prefix` default: **`WC_`**
- `case_number_length` default: **`6`**

Implement generator to match plugin:
- random alphanumeric string length = `case_number_length`
- prefix = `case_number_prefix` (or store override if you support it)
- append current timestamp (plugin uses `time()`)

### 2.3 Make Default Job Status Match Plugin
Update job creation logic to default:
- `status_slug = 'new'` (instead of SaaS `new_quote`)

Also ensure `new` status exists per branch:
- confirm the plugin-status seeder populates `new`
- if not, add/adjust seeding

### 2.4 Extend/Create a Plugin-Parity Create Job API Contract
Keep the existing endpoint but extend it to accept plugin parity:

`POST /api/{tenant}/app/repairbuddy/jobs`

Payload must support:
- Existing customer path:
  - `customer_id`
- Inline customer creation path:
  - `customer_create: { first_name, last_name, email, phone, company, address... }`

Validation must match plugin:
- require `device_post_id`
- require `delivery_date`
- require `case_detail` (from `jobDetails`)
- require either `customer_id` or `customer_create`

Side effects:
- If `customer_create` is provided:
  - create the customer user
  - (optionally) trigger the SaaS equivalent of plugin “send login email” (see Questions section)

Response should include:
- `id`
- `case_number`
- `status` (`new`)
- customer summary

---

## 3) Frontend Changes — Rebuild Job Creation Page to Match Plugin Flow

Target:
- `frontend/src/app/app/[tenant]/jobs/new/page.tsx`

### 3.1 Implement Plugin-Equivalent UX Flow (Device-first wizard)
Rebuild the page into a step flow matching plugin’s modal sequence:

1) **Device selection**
   - searchable device list (plugin-style)
   - yields `device_post_id`

2) **Customer**
   - toggle:
     - “Select existing customer” (search/select)
     - “Add new customer” (inline form)

3) **Job details**
   - `delivery_date` (required)
   - `device_id` (free text IMEI/ID)
   - `jobDetails` (required textarea)

4) **Submit**
   - show field-level validation messages consistent with plugin
   - after success, redirect to job view/edit with new `case_number`

### 3.2 Frontend → Backend Field Mapping
Submit:
- `device_post_id`
- `device_id` → `plugin_device_id_text`
- `delivery_date`
- `jobDetails` → `case_detail`
- either:
  - `customer_id`
  - OR `customer_create`

### 3.3 UX Parity Details
- Required fields must block submission the same way as plugin
- Provide the same “single flow” convenience: customer creation + job creation in one submit

---

## 4) Verification — What Makes This Truly “100%”

### 4.1 Automated Backend Tests (Feature Tests)
Add tests covering:
- missing `device_post_id` → 422
- missing `delivery_date` → 422
- missing `case_detail` → 422
- existing customer path creates job with:
  - `status_slug = new`
  - plugin-style `case_number`
- customer_create path:
  - creates customer
  - creates job linked to that customer
  - expected notification behavior (depending on chosen approach)

### 4.2 Manual Parity Checklist (Side-by-Side)
For the same inputs in plugin vs SaaS, confirm:
- case number format and uniqueness
- default status equals plugin (`new`)
- required fields behavior matches
- customer creation behavior matches
- device linkage stored matches (`device_post_id`, `device_id` text)
- resulting job view includes the same “core facts”

---

## Open Decisions (Need Your Confirmation for True Parity)
1) **Device source**: In SaaS, should `device_post_id` refer to:
   - a global “device catalog” (plugin-like), OR
   - a customer-owned device record?

2) **Customer creation notification**: When plugin creates a customer it emails login info. In SaaS should we:
   - send an invite email,
   - send auto-generated password email,
   - or send no email?
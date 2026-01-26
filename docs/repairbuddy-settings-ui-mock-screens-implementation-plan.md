---
description: Implement UI-only RepairBuddy settings screens (tenant settings tab)
---

# RepairBuddy Settings (UI-only) — Implementation Plan

## Goal
Add **UI-only mock settings screens** for the legacy WordPress “Repair Buddy / computer-repair-shop” plugin inside the existing SaaS app **Tenant Settings** page:

- Location: `frontend/src/app/app/[tenant]/settings/page.tsx`
- UX requirement: **Option B (integrated)**
  - Add a new `TabsTrigger`: **RepairBuddy**
  - The `TabsContent` for RepairBuddy shows a **sub-nav** + **nested navigation links** to sub-sections
- Buttons requirement:
  - All “Save” / “Add” / “Edit” actions inside RepairBuddy settings should be **disabled** (UI-only)
  - Implementation must still be **ready for saving later** (clean state shape + update helpers + stubbed save function)
- Scope requirement:
  - **Tenant settings only** (no `/admin/...` pages)

## Non-goals (for this phase)
- No backend/API for RepairBuddy settings
- No database migrations
- No real saving/persistence (no localStorage unless explicitly decided later)
- No feature flag unless explicitly requested

## Current app context (verified)
- App is **Next.js App Router** under `frontend/src/app/...`
- Tenant settings page exists and uses:
  - `Tabs`, `TabsList`, `TabsTrigger`, `TabsContent`
  - `Card`, `Input`, `Button`, `Alert`, `PageHeader`
- Tenant settings page currently has tabs:
  - `company`, `billing`, `branding`, `operations`, `tax`, `team`

---

# Implementation Strategy

## A) Keep RepairBuddy screens inside the existing settings route
To keep “Option B integrated” without introducing complex nested layouts, implement RepairBuddy sections **inside** `frontend/src/app/app/[tenant]/settings/page.tsx` as a new tab.

### Deep-linking (“nested router links”)
Implement RepairBuddy sub-section navigation using a **query parameter**, so it behaves like nested navigation while staying on the same route:

- Base route: `/app/[tenant]/settings`
- RepairBuddy tab active: `activeTab === "repairbuddy"`
- RepairBuddy section selection via query param:
  - `?rb=general`
  - `?rb=sms`
  - etc.

This enables:
- Copy/paste-able links to a specific settings subsection
- No additional Next.js route files
- The page remains integrated under the existing Settings Tabs

---

# File/Folder Plan

## 1) Modify existing tenant settings page
- **File:** `frontend/src/app/app/[tenant]/settings/page.tsx`
- **Changes:**
  - Add `TabsTrigger value="repairbuddy"`
  - Add `TabsContent value="repairbuddy"` containing a new component: `RepairBuddySettingsTab`
  - Ensure the bottom `Save changes` button for tenant settings does not confuse users when in RepairBuddy tab:
    - Recommended: hide or disable that main submit button when `activeTab === "repairbuddy"`

## 2) Add RepairBuddy tab UI components (new)
Create new components so the main settings page remains readable.

- **Folder (recommended):**
  - `frontend/src/app/app/[tenant]/settings/_components/repairbuddy/`

- **New files (minimum):**
  - `RepairBuddySettingsTab.tsx`
  - `repairBuddyNav.ts` (subnav config)
  - `types.ts` (typed settings model)
  - `defaults.ts` (default/mock values)
  - `useRepairBuddyDraft.ts` (state + update helpers + stub save)

- **New section components (one file per section):**
  - `sections/GeneralSection.tsx`
  - `sections/CurrencySection.tsx`
  - `sections/InvoicesReportsSection.tsx`
  - `sections/JobStatusesSection.tsx`
  - `sections/PaymentsSection.tsx`
  - `sections/ReviewsSection.tsx`
  - `sections/EstimatesSection.tsx`
  - `sections/MyAccountSection.tsx`
  - `sections/StylingLabelsSection.tsx`
  - `sections/SignatureWorkflowSection.tsx`
  - `sections/TimeLogsSection.tsx`
  - `sections/MaintenanceRemindersSection.tsx`
  - `sections/BookingSection.tsx`
  - `sections/DevicesBrandsSection.tsx`
  - `sections/PagesSetupSection.tsx`
  - `sections/SmsSection.tsx`
  - `sections/TaxesSection.tsx`
  - `sections/ServiceSettingsSection.tsx`

> Note: You can start with 3–5 sections and add the rest incrementally; the nav should include all sections from day 1.

---

# Step-by-step Execution Checklist (AI-agent ready)

## Phase 1 — Add the RepairBuddy tab entry point
1. **Edit** `frontend/src/app/app/[tenant]/settings/page.tsx`
2. Add:
   - `TabsTrigger value="repairbuddy">RepairBuddy</TabsTrigger>`
3. Add:
   - `TabsContent value="repairbuddy">` rendering `<RepairBuddySettingsTab tenantSlug={String(tenantSlug)} />`
4. Ensure `activeTab` can be set to `repairbuddy`.
5. Update the main bottom submit area:
   - If `activeTab === "repairbuddy"`, hide or disable the existing `<Button type="submit">Save changes</Button>`.

**Acceptance criteria**
- Settings page loads
- A new “RepairBuddy” tab appears
- Clicking it shows the RepairBuddy container UI

---

## Phase 2 — Implement RepairBuddy sub-nav + deep links
1. Create `repairBuddyNav.ts`:
   - Export a list of sections `{ key, label, description? }`.
   - Keys must match query param values (e.g., `general`, `sms`, `taxes`).
2. In `RepairBuddySettingsTab.tsx`:
   - Read current section from `useSearchParams()`:
     - `const rb = searchParams.get("rb") ?? "general"`
   - Render a left sub-nav of `Link` components pointing to:
     - `/app/${tenantSlug}/settings?tab=repairbuddy&rb=${key}` OR
     - `/app/${tenantSlug}/settings?rb=${key}` (if tab state is not encoded)
3. Ensure the UI stays on the same page while switching sub-sections.

**Important detail**
- If you want the RepairBuddy tab to open automatically when a `rb` param exists, add a small effect in `page.tsx`:
  - If `searchParams.get("rb")` is present, set `activeTab` to `repairbuddy`.

**Acceptance criteria**
- Sub-nav renders in RepairBuddy tab
- Clicking sub-nav items changes the content area
- URL updates so sections are linkable

---

## Phase 3 — Create a typed settings draft model (ready for backend later)
1. Create `types.ts` to define a single top-level model:
   - `export type RepairBuddySettingsDraft = { ... }`
2. Create `defaults.ts`:
   - `export const defaultRepairBuddyDraft: RepairBuddySettingsDraft = { ... }`
3. Create `useRepairBuddyDraft.ts`:
   - `const [draft, setDraft] = useState(defaultRepairBuddyDraft)`
   - Provide helpers:
     - `setField(path, value)` (implementation can be explicit per field; do not over-engineer)
     - `reset()`
     - `save()` stub that is not implemented yet
   - Export `savingDisabledReason = "Backend not implemented"`

**Acceptance criteria**
- Section components read/write from a shared `draft`
- No backend calls exist yet

---

## Phase 4 — Implement section screens (UI-only)

### Shared rules for ALL sections
- Use existing UI kit components (`Card`, `Input`, `Button`, `Alert`, `Tabs` etc.)
- All actionable buttons inside RepairBuddy must be:
  - `disabled={true}` (or `disabled={isMock}`)
  - show helper text like: “Saving will be available in a later phase.”
- All form inputs should remain editable (so UX can be tested), but do not persist.

### Required sections + field inventory
Implement these sections to match the extracted plugin settings.

#### 1) General
- Menu name
- Business name
- Business phone
- Business address
- Logo URL
- Email
- Case number prefix
- Case number length
- Email customer (checkbox)
- Attach PDF (checkbox)
- Next service date toggle
- GDPR acceptance text + link label + link URL
- Default country
- Disable parts and use Woo products
- Disable status check by serial

#### 2) Currency
- Currency
- Currency position
- Thousand separator
- Decimal separator
- Number of decimals

#### 3) Invoices & Reports
- Add QR code to invoice
- Invoice footer message
- Invoice print type
- Display dates toggles (pickup/delivery/next service)
- Invoice disclaimer / terms textarea
- Repair order type
- Terms URL
- Repair order print size
- Display business address details
- Display customer email/address details
- Repair order footer message

#### 4) Job Statuses (CRUD-style UI)
- Table: ID, Name, Slug, Description, Invoice Label, Manage Woo Stock, Status
- “Add new status” button (disabled) opens modal UI (modal can still open, but the modal submit is disabled)
- Settings:
  - status considered completed
  - status considered cancelled

#### 5) Payments
- Payment statuses table (CRUD-style)
- Payment methods multi-checkbox list

#### 6) Reviews
- Request feedback by SMS
- Request feedback by Email
- Feedback page selection (mock select)
- Send review request if job status is
- Auto feedback request interval
- Email subject + message template
- SMS message template

#### 7) Estimates
- Email subject/body to customer
- Disable estimates
- Booking & quote forms: send to jobs
- Approve/reject email subject/body to admin

#### 8) My Account
- Disable booking
- Disable estimates
- Disable reviews
- Booking form type

#### 9) Devices & Brands
- Enable pin code field
- Show pin code in invoices/emails/status check
- Use Woo products as devices
- Labels (note/pin/device/device brand/device type/imei)
- Additional device fields repeater UI
- Pickup/delivery toggle + charges
- Rental toggle + per-day/per-week

#### 10) Pages Setup
- Dashboard page
- Status check page
- Feedback page
- Booking page
- Services page
- Parts page
- Redirect after login
- Enable registration

#### 11) SMS
- Activate SMS for selective statuses
- Gateway selection
- Gateway credential fields (conditional)
- “Send when status changed to” multi-select
- Test SMS (number + message)

#### 12) Taxes
- Taxes table (CRUD-style)
- Enable taxes
- Default tax
- Invoice amounts inclusive/exclusive

#### 13) Service Settings
- Sidebar description
- Disable booking on service page
- Booking form type

#### 14) Time Logs
- Disable time log
- Default tax for hours
- Enable time log for statuses
- Activities textarea

#### 15) Maintenance Reminders (CRUD-style)
- Reminders table
- Add reminder modal UI
- Test reminder modal UI

#### 16) Styling & Labels
- Labels (delivery/pickup/next-service/case-number)
- Colors (primary/secondary)

#### 17) Signature Workflow
Pickup signature:
- enable
- trigger status
- email subject
- email template
- sms text
- status after submission

Delivery signature:
- same fields

#### 18) Booking
- Booking email templates (to customer/admin)
- Send booking/quote to jobs
- Turn off “other device brand”
- Turn off “other service”
- Turn off service price
- Turn off ID/IMEI in booking
- Default type/brand/device

**Acceptance criteria**
- Every section exists and renders without crashing
- All fields are present (even if some are placeholder selects)
- All “Save/Add/Edit” actions are disabled

---

## Phase 5 — Coverage checklist (don’t miss anything)
Create a checklist file (or section in this plan) mapping each plugin setting to a screen.

- **File (recommended):** `docs/repairbuddy-settings-ui-coverage-checklist.md`
- Format:
  - `Setting key/label -> Screen -> Section -> Field type`

**Acceptance criteria**
- A reviewer can verify nothing was forgotten without reading code

---

# UX Notes (important)
- Keep the RepairBuddy UI visually consistent with the current Settings UI (Cards, spacing, typography).
- Prefer a two-column layout inside RepairBuddy tab:
  - Left: section list
  - Right: section content
- Always show a small warning banner at top of RepairBuddy tab:
  - “Mock screens — saving not available yet.”

---

# Backend-readiness (future phase hooks)
Do NOT implement now, but ensure UI structure makes this easy later:

- Centralize state in `useRepairBuddyDraft()` so future backend can:
  - load initial values from `GET /api/{tenant}/app/settings/repairbuddy`
  - save via `PATCH /api/{tenant}/app/settings/repairbuddy`
- Keep a stable type `RepairBuddySettingsDraft` to align frontend+backend
- Keep all section components “controlled” via props/state (no DOM reads)

---

# Definition of Done
- Tenant Settings page has a new **RepairBuddy** tab
- RepairBuddy tab shows sub-nav and all sections listed above
- Fields are editable but **no persistence**
- All save/add/edit buttons inside RepairBuddy are **disabled**, with clear messaging
- Code is organized into components/types/defaults so backend wiring is straightforward later

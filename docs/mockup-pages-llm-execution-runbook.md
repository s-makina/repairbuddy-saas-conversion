# Mockup Pages — LLM Execution Runbook (Plugin Parity)

This document is a **phased execution plan** intended for an LLM/agent to implement **100% menu parity** with the legacy WordPress RepairBuddy plugin (`docs/computer-repair-shop-plugin`) using **frontend-only mock pages**, with a strong emphasis on **accuracy**, **determinism**, and being **free from avoidable errors**.

---

## Scope

### Staff app (authenticated tenant app)
- All sidebar menu items must land on a **real page** (not the generic placeholder page).
- Pages should be mock implementations (UI + fake data) with consistent list/detail layouts.

### Customer-facing (public + portal)
- Public pages to match plugin shortcodes:
  - **Status check (no login)**
  - **Booking**
  - **Request quote**
  - **Services list**
  - **Parts list**
- Customer portal pages to match plugin “My Account”:
  - Dashboard
  - Tickets/Jobs
  - Estimates
  - Reviews
  - My Devices
  - Booking
  - Profile

---

## Phase 0 — Preconditions and invariants

### Required decision (blocking)
Choose the route base for customer/public pages:
- **Option A (recommended):** `/t/[tenant]/...`
- **Option B:** public pages under `/app/[tenant]/...` (or similar)

**Decision:** Option A selected — all customer/public mock pages must live under `/t/[tenant]/...`.

### Invariants
- Do not break existing `/app/[tenant]/*` and `/admin/*` routes.
- Frontend-only implementation; avoid backend/database changes.
- Prefer additive changes; replace placeholder routes only after the new pages exist.
- No automatic vulnerability fixes (avoid `npm audit fix --force`) unless explicitly requested.

### Done criteria
- Route base decision is made (A or B).
- A single authoritative route map exists (see Phase 1 output).

---

## Phase 1 — Route map + navigation blueprint (design-first)

### Tasks
1. Produce a complete route inventory for:
   - Staff app routes for each sidebar item.
   - Customer/public routes.
   - Customer portal routes.
2. Define which staff menus get:
   - **List-only** mock pages (fast)
   - **List + detail** mock pages (deep workflows)
3. Define ID patterns:
   - `jobs/[jobId]`, `clients/[clientId]`, `estimates/[estimateId]`.

### Guardrails
- Do not define routes you are not implementing in subsequent phases.
- Sidebar links must point to real pages after completion.

### Done criteria
- A table exists:
  - Menu item → Route → Page type (list/detail) → Mock dependencies
- No unresolved TBD routes.

### Output — Authoritative route map (Phase 1 deliverable)

This route map is authoritative for subsequent phases. It is derived from the current staff sidebar configuration (`frontend/src/components/DashboardShell.tsx`) plus the required customer/public + portal pages.

#### ID patterns (deterministic)

- **Tenant slug**
  - Route param: `[tenant]`
  - Example: `acme-repairs`

- **Job ID**
  - Route param: `[jobId]`
  - Pattern: `job_\d{3}`
  - Examples:
    - `job_001`
    - `job_014`

- **Client ID**
  - Route param: `[clientId]`
  - Pattern: `client_\d{3}`
  - Examples:
    - `client_001`

- **Estimate ID**
  - Route param: `[estimateId]`
  - Pattern: `estimate_\d{3}`
  - Examples:
    - `estimate_001`

- **Case number (customer-facing lookup key)**
  - Used on status check page input (not a route param)
  - Pattern: `RB-\d{5}`
  - Example: `RB-10421`

#### Staff app + admin routes (must match sidebar menu)

Notes:
- The sidebar currently links many modules to `/app/[tenant]/placeholder/...`. In subsequent phases, these will be replaced with the dedicated routes below.
- Permissions shown here correspond to the sidebar `auth.can(...)` checks.

| Menu section | Menu item | Route | Page type | Mock dependencies | Notes |
|---|---|---|---|---|---|
| (Admin quick) | Businesses | `/admin/businesses` | list-only | (n/a – existing) | Admin-only: `admin.tenants.read` |
| Overview | Dashboard (Admin) | `/admin` | list-only | (n/a – existing) | Admin-only: `admin.access` |
| Overview | Business Dashboard | `/app/[tenant]` | list-only | (n/a – existing) | `dashboard.view` |
| Overview | Calendar | `/app/[tenant]/calendar` | list-only | `Job`, `Estimate`, `Appointment` | Already implemented as mock calendar |
| Overview | Business Settings | `/app/[tenant]/business-settings` | list-only | (n/a – existing UI) | Already implemented; Phase 6 adds preview cross-links |
| Overview | Users | `/app/[tenant]/users` | list-only | (n/a – existing) | Already implemented; real API-backed |
| Overview | Roles | `/app/[tenant]/roles` | list-only | (n/a – existing) | Already implemented; real API-backed |
| Overview | Branches | `/app/[tenant]/branches` | list-only | (n/a – existing) | Already implemented; real API-backed |
| Overview | Settings | `/app/[tenant]/settings` | list-only | (n/a – existing) | Existing tenant settings page (API-backed) |
| Overview | Security | `/app/[tenant]/security` | list-only | (n/a – existing) | Existing security page |
| Billing (Admin) | Plans | `/admin/billing/plans` | list-only | (n/a – existing) | Admin-only: `admin.billing.read` |
| Billing (Admin) | Plan Builder | `/admin/billing/builder` | list-only | (n/a – existing) | Admin-only: `admin.billing.read` |
| Billing (Admin) | Intervals | `/admin/billing/intervals` | list-only | (n/a – existing) | Admin-only: `admin.billing.read` |
| Billing (Admin) | Entitlements | `/admin/billing/entitlements` | list-only | (n/a – existing) | Admin-only: `admin.billing.read` |
| Billing (Admin) | Currencies | `/admin/billing/currencies` | list-only | (n/a – existing) | Admin-only: `admin.billing.read` |
| Billing (Admin) | Business Billing | `/admin/billing/businesses` | list-only | (n/a – existing) | Admin-only |
| Operations | Appointments | `/app/[tenant]/appointments` | list-only | `Appointment` | Must replace placeholder link |
| Operations | Jobs | `/app/[tenant]/jobs` | list + detail | `Job`, `Client`, `CustomerDevice`, `Estimate`, `Payment` | Detail route below |
| Operations | Jobs (detail) | `/app/[tenant]/jobs/[jobId]` | detail | `Job`, `JobStatus`, `JobMessage`, `JobAttachment`, `Client`, `CustomerDevice`, `Estimate`, `Payment`, `Expense`, `TimeLog` | Tabs per Phase 3 |
| Operations | Estimates | `/app/[tenant]/estimates` | list + detail | `Estimate`, `Client`, `Job` | Detail route below |
| Operations | Estimates (detail) | `/app/[tenant]/estimates/[estimateId]` | detail | `Estimate`, `Client`, `Job`, `Payment` | Approve/reject UI (mock) |
| Operations | Services | `/app/[tenant]/services` | list-only | `Service` (mock type) | List-only acceptable initially |
| Inventory | Devices | `/app/[tenant]/devices` | list-only | `Device`, `DeviceBrand`, `DeviceType` | List-only |
| Inventory | Device Brands | `/app/[tenant]/device-brands` | list-only | `DeviceBrand` | List-only |
| Inventory | Device Types | `/app/[tenant]/device-types` | list-only | `DeviceType` | List-only |
| Inventory | Parts | `/app/[tenant]/parts` | list-only | `Part` (mock type) | List-only |
| Finance | Payments | `/app/[tenant]/payments` | list-only | `Payment` | List-only |
| Finance | Reports | `/app/[tenant]/reports` | list-only | `Job`, `Payment`, `Expense`, `TimeLog` | List-only |
| Finance | Expenses | `/app/[tenant]/expenses` | list-only | `Expense` | List-only |
| Finance | Expense Categories | `/app/[tenant]/expense-categories` | list-only | `ExpenseCategory` (mock type) | List-only |
| People | Clients | `/app/[tenant]/clients` | list + detail | `Client`, `CustomerDevice`, `Job`, `Estimate` | Detail route below |
| People | Clients (detail) | `/app/[tenant]/clients/[clientId]` | detail | `Client`, `CustomerDevice`, `Job`, `Estimate`, `Review`, `Payment` | Client profile detail |
| People | Customer Devices | `/app/[tenant]/customer-devices` | list-only | `CustomerDevice`, `Client`, `Device` | List-only |
| People | Technicians | `/app/[tenant]/technicians` | list-only | `User` (mock subset) | List-only |
| People | Managers | `/app/[tenant]/managers` | list-only | `User` (mock subset) | List-only |
| Quality | Job Reviews | `/app/[tenant]/job-reviews` | list-only | `Review`, `Job`, `Client` | List-only |
| Tools | Time Logs | `/app/[tenant]/time-logs` | list-only | `TimeLog`, `Job`, `User` | List-only |
| Tools | Manage Hourly Rates | `/app/[tenant]/hourly-rates` | list-only | `HourlyRate` (mock type), `User` | List-only |
| Tools | Reminder Logs | `/app/[tenant]/reminder-logs` | list-only | `ReminderLog` (mock type), `Job`, `Client` | List-only |
| Tools | Print Screen | `/app/[tenant]/print-screen` | list-only | `Job`, `Estimate`, `Invoice` (mock types ok) | List-only |
| Account | Profile | `/app/[tenant]/profile` | list-only | (n/a – existing) | Existing page |
| Account | Settings | `/app/[tenant]/settings` | list-only | (n/a – existing) | Existing page |
| Account | Business Settings | `/app/[tenant]/business-settings` | list-only | (n/a – existing) | Existing page |

#### Customer/public + portal routes (no staff auth)

All customer/public pages must live under `/t/[tenant]/...`.

| Area | Route | Page type | Mock dependencies | Notes |
|---|---|---|---|---|
| Public | `/t/[tenant]/status` | detail | `Job`, `JobStatus`, `JobMessage`, `JobAttachment`, `Estimate`, `Payment` | Case-number-based lookup; message + attachment UI; estimate approve/reject updates local state |
| Public | `/t/[tenant]/book` | detail | `Appointment` | Booking flow produces confirmation linking to status + portal |
| Public | `/t/[tenant]/quote` | detail | `Estimate`, `Client`, `Device` | Request quote form (mock) |
| Public catalog | `/t/[tenant]/services` | list-only | `Service` (mock type) | Public services list |
| Public catalog | `/t/[tenant]/parts` | list-only | `Part` (mock type) | Public parts list |
| Portal shell | `/t/[tenant]/portal` | list-only | `Job`, `Estimate`, `Review`, `CustomerDevice` | Portal home/dashboard; uses `PortalShell` |
| Portal | `/t/[tenant]/portal/tickets` | list-only | `Job` | Jobs/tickets list |
| Portal | `/t/[tenant]/portal/tickets/[jobId]` | detail | `Job`, `JobMessage`, `JobAttachment`, `Estimate`, `Payment` | Ticket/job detail (portal view) |
| Portal | `/t/[tenant]/portal/estimates` | list-only | `Estimate`, `Job` | Estimates list |
| Portal | `/t/[tenant]/portal/reviews` | list-only | `Review`, `Job` | Reviews list + create (mock) |
| Portal | `/t/[tenant]/portal/devices` | list-only | `CustomerDevice`, `Device` | “My Devices” |
| Portal | `/t/[tenant]/portal/booking` | list-only | `Appointment` | Customer booking history (mock) |
| Portal | `/t/[tenant]/portal/profile` | detail | `Client` | Profile page (mock) |

---

## Phase 2 — Shared mock foundation (types + fixtures + mock API)

### Tasks
1. Create a single mock data layer folder in `frontend/src` (one location).
2. Add:
   - `types.ts` (Job, Client, Estimate, etc.)
   - `fixtures.ts` (deterministic arrays)
   - `mockApi.ts` (query/update functions)
3. Required entities (minimum):
   - `Job`, `JobStatus`, `JobMessage`, `JobAttachment`
   - `Client`, `CustomerDevice`, `Device`, `DeviceBrand`, `DeviceType`
   - `Estimate` (approve/reject)
   - `Appointment`
   - `Payment`, `Expense`, `TimeLog`, `Review`
4. Deterministic IDs:
   - `job_001`, `client_001`, `estimate_001`, etc.
5. Add latency simulation helper:
   - `sleep(ms)` and wrappers like `withLatency`.

### Guardrails
- No additional libraries are required for mock data.
- Avoid randomness unless seeded.

### Done criteria
- A job can render with:
  - linked client, device(s), estimate, messages, and payments
- Lint/typecheck passes for mock layer.

---

## Phase 3 — Shared UI page shells (consistency + low error risk)

### Tasks
Create reusable “page shells” so the UI is consistent across modules:

- **ListPageShell**
  - `PageHeader`
  - filter/search row
  - table/cards area
  - empty/loading/error states

- **DetailPageShell**
  - breadcrumbs
  - primary actions
  - tabs: Overview / Timeline / Messages / Financial / Print

- **PortalShell**
  - left nav mirroring plugin “My Account”
  - portal layout with consistent header

### Guardrails
- Use existing UI components/styles.
- Avoid refactors of existing components unless necessary.

### Done criteria
- At least:
  - 1 staff list page uses `ListPageShell`
  - 1 staff detail page uses `DetailPageShell`
  - portal home uses `PortalShell`

---

## Phase 4 — Customer/public pages (highest parity value)

### Pages (build in this order)
1. **Status Check (no login)**
   - Route: `/t/[tenant]/status`
   - Case number input
   - Job summary + current status
   - Timeline/history
   - Message post + attachment UI (mock)
   - Estimate approve/reject UI (mock)

2. **Portal shell + portal home**
   - `/t/[tenant]/portal`

3. **Portal tickets**
   - `/t/[tenant]/portal/tickets`
   - `/t/[tenant]/portal/tickets/[jobId]`

4. **Portal estimates**
   - `/t/[tenant]/portal/estimates`

5. **Public booking**
   - `/t/[tenant]/book`

6. **Public request quote**
   - `/t/[tenant]/quote`

7. **Public catalogs**
   - `/t/[tenant]/services`
   - `/t/[tenant]/parts`

### Guardrails
- Public pages must not require staff auth.
- Approve/reject and message posting must update UI state locally (in-session or localStorage).

### Done criteria
- Booking confirmation links to:
  - Status check page
  - Portal pages
- No console/runtime errors on these routes.

---

## Phase 5 — Staff app pages (replace placeholder-based navigation)

### Implementation order
1. **Jobs**
   - `/app/[tenant]/jobs`
   - `/app/[tenant]/jobs/[jobId]`
2. **Clients**
   - `/app/[tenant]/clients`
   - `/app/[tenant]/clients/[clientId]`
3. **Estimates**
   - `/app/[tenant]/estimates`
   - `/app/[tenant]/estimates/[estimateId]`
4. Remaining modules (list-only acceptable initially):
   - Appointments, Services, Devices, Device Brands, Device Types, Parts
   - Payments, Reports, Expenses, Expense Categories
   - Technicians, Managers, Job Reviews
   - Time Logs, Hourly Rates, Reminder Logs
   - Print Screen

### Guardrails
- Keep existing sidebar structure in `DashboardShell.tsx`.
- After completion, each sidebar link must land on a dedicated page (not the generic placeholder catch-all).

### Done criteria
- Clicking every sidebar item:
  - renders a meaningful page
  - produces no 404
  - does not show the generic “Phase 1 placeholder” message

---

## Phase 6 — Business Settings cross-linking (settings → portal/public)

### Tasks
- Add preview links in Business Settings sections (where relevant):
  - Preview customer portal
  - Preview status check
  - Preview booking
  - Preview services/parts catalogs

### Guardrails
- Avoid implementing backend persistence.

### Done criteria
- Settings area can be used as a “control center” to navigate to customer flows.

---

## Phase 7 — QA hardening (error-free delivery)

### Automated checks (run and fix)
- `npm run lint`
- `npm run typecheck` (or equivalent)
- `npm run build` (preferred)

### Manual QA script (must pass)
- No dead links:
  - all sidebar links
  - all portal nav links
- Critical flows:
  - status check search returns job
  - message post appears in UI
  - estimate approve/reject changes state
  - booking produces confirmation
- Proper empty/loading/error states exist
- Responsive sanity check (mobile widths)

### Done criteria
- All checks pass.
- A final “routes to verify” list is produced.

---

## Phase 8 — Final parity gate (100% plugin menu + shortcode coverage)

### Tasks
- Create a parity checklist:
  - Plugin admin menus ✅
  - Plugin settings tabs ✅
  - Plugin shortcodes/customer flows ✅
- For each item include:
  - route
  - implemented page
  - primary action(s)
  - cross-links

### Done criteria
- Checklist shows **no missing** menu/page/flow relative to the plugin.

---

## Notes

- This runbook is intentionally frontend-only and deterministic to reduce error rate.
- Avoid unrelated commands (e.g., Maven) in the frontend project.

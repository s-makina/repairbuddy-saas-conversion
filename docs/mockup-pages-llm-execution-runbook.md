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

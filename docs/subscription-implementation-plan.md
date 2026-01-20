# Subscription implementation plan (AI agents)

This plan assumes no external billing provider initially, but keeps clear seams for adding one later. Currency is **per-tenant** and taxes use **VAT rules**.

## Phase 0: Alignment & invariants

- Agent: **Architect Agent**
- Deliverables:
  - Confirm invariants: tenant-only subscription, strict `tenant_id` scoping, immutable plan versioning.
  - Finalize VAT scenarios supported in v1 (same-country VAT, reverse charge, non-VAT).
  - Decide seller country source (env/config) and invoice numbering format.
- Acceptance checks:
  - Written decisions captured in this plan.

### Phase 0 decisions (captured)

- Invariants
  - Subscriptions are **tenant-owned** (no per-user subscriptions).
  - All tenant-owned records must have a non-null `tenant_id` and must be queried through strict tenant scoping (DB foreign keys + app-level tenant scoping).
  - Plan versioning is **immutable** once a version is active/retired. Changes require creating a new `billing_plan_versions` row.
- VAT scenarios in v1
  - Same-country VAT: apply VAT based on `tax_rates` for `seller_country == buyer_country`.
  - Reverse charge: when `buyer_vat_number` is present and `buyer_country != seller_country`, invoice VAT is 0% and the invoice tax snapshot must record the reverse-charge reason.
  - Non-VAT: if no applicable VAT rate is configured for the buyer country, treat VAT as 0% and record “no rate configured” in the tax snapshot.
- Seller country source
  - Seller country comes from env/config: `BILLING_SELLER_COUNTRY` (ISO 3166-1 alpha-2).
- Invoice numbering format
  - Invoice numbers are per-tenant sequential per calendar year via `invoice_sequences`.
  - Format: `RB-{TENANT_SLUG}-{YYYY}-{NNNNNN}` (6-digit zero-padded sequence).

## Phase 1: Database schema + migrations

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

## Phase 2: Core domain services (backend)

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

## Phase 3: VAT + invoicing engine

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

## Phase 4: Admin APIs

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

## Phase 5: Admin UI (catalog + tenant billing)

- Agent: **Frontend Admin UI Agent**
- Deliverables:
  - Information architecture + navigation:
    - Admin nav group: **Billing**
      - **Catalog**
        - Plans
        - Entitlements (definitions)
        - Tax rates (optional if Phase 4 exposes it)
      - **Tenants**
        - Tenant Billing (embedded inside tenant detail)
  - Routes (suggested Next.js app routes):
    - `/admin/billing/plans`
    - `/admin/billing/plans/[planId]`
    - `/admin/billing/plans/[planId]/versions/[versionId]`
    - `/admin/billing/entitlements`
    - `/admin/tenants/[tenantId]/billing`
    - `/admin/tenants/[tenantId]/billing/invoices`
    - `/admin/tenants/[tenantId]/billing/invoices/[invoiceId]`
  - Plan builder UI (catalog management):
    - Plans list
      - Data table columns:
        - name, status (active/inactive), current version, currencies available, updated
      - Primary actions:
        - Create plan
        - Search by plan name
      - Row actions:
        - View details
        - Duplicate plan (optional)
        - Archive/deactivate (if supported)
    - Plan detail page
      - Header summary:
        - plan name, marketing label, short description, visibility (public/internal)
      - Tabs:
        - Overview
        - Versions
        - Prices (read-only aggregate across versions)
        - Audit/events (if Phase 4 exposes)
      - Versions tab
        - Show version timeline: draft, active, retired
        - Actions:
          - Create draft from active
          - Open version editor
          - Retire active version (if supported)
    - Version editor (draft-only editable)
      - UX invariant:
        - Active/retired versions are **read-only** (hard-disable inputs + show lock banner)
      - Sections:
        - Marketing fields (name override, highlights)
        - Entitlements editor (typed)
        - Prices editor
      - Draft actions:
        - Save draft
        - Validate draft (runs client validation + server validation endpoint if present)
        - Activate (opens activation wizard)
    - Activation wizard (grandfathering policy)
      - Step 1: Review changes
        - Diff view from active -> draft:
          - entitlement changes
          - price changes by currency/interval
      - Step 2: Grandfathering selection
        - Option A: Keep existing tenants on old version (recommended default)
        - Option B: Move all tenants to new version on next renewal/invoice (if supported)
        - Option C: Move selected tenants (requires tenant multi-select UI + search)
      - Step 3: Confirm
        - Confirm dialog summarizing impact (count tenants affected)
        - Require explicit typed confirmation (e.g., type `ACTIVATE`) for irreversible action
  - Entitlements editor (typed)
    - Use a constrained UI per entitlement type:
      - numeric (e.g., `max_users`) => number input with min/max, step, helper text
      - boolean => toggle
      - enum => dropdown
    - Display computed meaning:
      - example: `max_users = 5` => “Up to 5 active users”
    - Validation:
      - disallow negatives
      - enforce sensible upper bounds (front-end) but rely on API for authoritative validation
  - Price editor
    - Grid by currency and interval (monthly/yearly)
    - Constraints:
      - only one `default_for_currency_interval` per plan version + currency + interval
      - amount must be >= 0
      - currency must be ISO code and must be supported by tenant currency model
    - UX:
      - highlight default price using `Badge`
      - show formatted price (e.g., `€29.00 / month`)
      - require confirmation when changing defaults
  - Tenant billing UI (tenant-scoped admin):
    - Tenant billing overview (`/admin/tenants/[tenantId]/billing`)
      - Summary cards:
        - Current subscription (plan + version + status + started/renews)
        - Currency (locked) + note: “Currency is tenant-owned; prices must match”
        - VAT profile (billing country + VAT number + reverse-charge eligible indicator)
      - Tabs:
        - Subscription
        - Invoices
        - Events (subscription_events + invoice events, if API exposes)
      - Subscription tab actions:
        - Assign plan (if no subscription)
        - Change plan/version
        - Cancel subscription
    - Assign/change plan flow
      - Step 1: Choose plan
        - Filter plans by availability of tenant currency
        - If plan has no price for tenant currency, disable selection and explain why
      - Step 2: Choose price (currency locked)
        - Show monthly/yearly options available for the tenant currency
        - Show clear “effective on” date (immediate vs next period) based on backend capability
      - Step 3: Review
        - Summary:
          - selected plan/version
          - price
          - VAT handling preview (same-country vs reverse charge vs 0% rate) as informational only
        - Confirm with `ConfirmDialog`
      - Post-action UI:
        - toast + refresh summary
        - if backend returns validation error (currency mismatch, version immutable), show `Alert` with actionable message
    - Invoices list (`/admin/tenants/[tenantId]/billing/invoices`)
      - Data table columns:
        - invoice number, status, issued date, due date (optional), total, currency
      - Filters:
        - status
        - date range
      - Actions:
        - Issue invoice (if drafts supported)
        - Mark paid (with payment date + method note)
        - Download PDF
        - View details
    - Invoice detail view (`/admin/tenants/[tenantId]/billing/invoices/[invoiceId]`)
      - Summary:
        - invoice number, status, amounts, dates
        - buyer info snapshot (billing address / VAT number)
      - Line items:
        - description, quantity, unit price, tax rate, tax amount, line total
      - Tax evidence snapshot (read-only)
        - seller country
        - buyer country
        - VAT number present?
        - reverse charge applied reason / “no rate configured” reason
      - Actions:
        - Download PDF
        - Mark paid (if issued)
        - Void (optional if supported)
  - UI building blocks (use existing components where possible):
    - Tables: `DataTable` with server pagination/sorting
    - Page structure: `DashboardShell`, `PageHeader`, `Card`
    - Forms: `Input`, `DropdownMenu`, `Tabs`, `Modal`
    - Confirmation: `ConfirmDialog`
    - Messaging: `Alert`, `Badge`
  - Required UI states (robustness):
    - Loading:
      - skeletons for summary cards and tables
      - disable destructive actions until data loaded
    - Empty:
      - “No plans yet” CTA
      - “No invoices yet” CTA
    - Error:
      - show inline `Alert` for recoverable errors
      - global error boundary message for route-level failures
      - map API validation errors to specific fields where possible
    - Permission denied:
      - show explicit “Admin access required” page (do not silently hide routes)
    - Concurrency/immutability:
      - if draft was activated/retired by another admin, the editor must switch to read-only and prompt refresh
  - Accessibility + UX quality bar:
    - Keyboard navigation:
      - all actions reachable via keyboard; focus is trapped in `Modal`/`ConfirmDialog`
    - Forms:
      - visible labels (not placeholder-only)
      - clear error text tied to fields
    - Color/contrast:
      - status badges must not rely on color alone; include text
    - Responsive:
      - tables collapse to stacked rows/cards on small screens
      - critical actions remain reachable without horizontal scrolling
 Acceptance checks:
  - UI prevents selecting prices not matching tenant currency (filter + disabled states + server-side error handling).
  - UI clearly indicates plan version immutability (active/retired read-only; activation is irreversible).
  - Activation wizard includes change review and a safe confirmation pattern.
  - Tenant billing page shows subscription, currency, VAT profile, and invoice actions with robust empty/loading/error states.
  - Invoice detail displays VAT evidence snapshot (as recorded on invoice) and supports PDF download.

## Phase 6: Enforcement + guards

- Agent: **Product Enforcement Agent**
- Deliverables:
  - Enforce `max_users` on user creation/invite.
  - Enforce 1-2 feature flags end-to-end (UI + API guard).
  - Add clear error messaging for over-limit operations.
- Acceptance checks:
  - Over-limit attempts fail predictably.
  - Feature flags actually disable relevant endpoints and UI actions.

## Phase 7: QA, hardening, and observability

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

## Phase 8 (later): Provider integration

- Agent: **Provider Integration Agent**
- Deliverables:
  - Implement billing provider adapter(s)
  - Webhook ingestion + reconciliation jobs
  - Replace manual payment marking with provider events
- Acceptance checks:
  - Provider subscription state reconciles with `tenant_subscriptions` without breaking entitlements.

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

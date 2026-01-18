# Milestone 1 Checklist — SaaS Foundation & Multi-Tenant Setup

Source: `docs/proposal_contents.txt` (Milestone 1: Weeks 1–2)

## Milestone 1 scope (from proposal)

- Analysis and extraction of core logic from the existing WordPress plugin
- Standalone Laravel project setup
- Secure authentication (admin and tenant users)
- Multi-tenant database architecture with strict tenant data isolation
- Base dashboard structure

## Acceptance criteria (from proposal)

- Users can register, authenticate, and access tenant-specific dashboards
- Tenant data is securely isolated
- Development environment is deployed and stable

---

# 1) Architecture decisions (must be finalized early)

- [ ] Decide multi-tenancy model
  - [ ] **Tenant identification strategy** (subdomain / custom domain / path prefix)
  - [ ] **Data isolation strategy**
    - [ ] Single database with `tenant_id` scoping
    - [ ] Schema-per-tenant
    - [ ] Database-per-tenant
  - [ ] Decide where tenant configuration lives (DB tables vs env-driven)
- [ ] Define core user model for Phase 1
  - [ ] Global `admin` users (platform-level)
  - [ ] Tenant users (scoped to a tenant)
  - [ ] Minimal role set for Milestone 1 (admin vs tenant owner/member)
- [ ] Decide API auth mechanism
  - [ ] Session cookie + CSRF (SPA same-domain)
  - [ ] Token-based (Sanctum tokens)
- [ ] Define baseline environments
  - [ ] Local dev
  - [ ] Staging/dev deployment (for milestone acceptance)

---

# 2) Repository & project setup (Laravel 11)

- [ ] Create standalone Laravel 11 backend app
  - [ ] Standard folder structure and PSR-12 formatting
  - [ ] Base environment config (`.env.example`) for local dev
  - [ ] App key generation workflow documented
- [ ] Setup database connection for MySQL
  - [ ] Connection config and migrations runnable end-to-end
- [ ] Setup basic health checks
  - [ ] Health endpoint (or equivalent) for deployment verification
  - [ ] App boots without errors on fresh install
- [ ] Seed data strategy
  - [ ] Minimal seeders for initial admin user and demo tenant

---

# 3) Multi-tenancy foundation (strict isolation)

## 3.1 Tenant model + lifecycle

- [ ] Create tenant entity
  - [ ] Tenant unique key (e.g., `slug` or `uuid`)
  - [ ] Tenant status (active/suspended)
  - [ ] Tenant metadata (name, contact email)
- [ ] Tenant provisioning flow
  - [ ] Tenant creation endpoint/UI action
  - [ ] Auto-create tenant owner user
- [ ] Tenant resolution middleware
  - [ ] Identify tenant per request (based on chosen strategy)
  - [ ] Fail-safe behavior when tenant is missing/invalid

## 3.2 Data isolation enforcement

- [ ] Enforce tenant scoping at query level
  - [ ] Global scope pattern for tenant-owned tables/models
  - [ ] Prevent cross-tenant reads/writes by default
- [ ] Ensure platform-level tables remain unscoped
  - [ ] Admin users (if platform-level)
  - [ ] Tenants table
- [ ] Add safety tests
  - [ ] Attempt to access tenant A data while authenticated in tenant B must fail

---

# 4) Authentication & authorization (admin + tenant users)

## 4.1 Authentication flows

- [ ] Implement user registration flow (as required by proposal)
  - [ ] Define whether registration creates a tenant automatically or joins an existing one
  - [ ] Email verification required before first login/token issuance
    - [ ] Verification email sending configured (dev: log/mailtrap)
    - [ ] Verify link flow returns user to frontend confirmation screen
    - [ ] Resend verification email endpoint/UI
- [ ] Implement login/logout
  - [ ] Secure password hashing
  - [ ] Optional per-user OTP (TOTP) support
    - [ ] Enable/setup OTP for a user (secret + provisioning URI)
    - [ ] Confirm OTP setup with code
    - [ ] Disable OTP (password + code)
    - [ ] OTP-required login challenge when enabled
  - [ ] Rate limiting on auth endpoints
  - [ ] Session invalidation on logout
- [ ] Implement "current user" endpoint
  - [ ] Returns authenticated user and resolved tenant context

## 4.2 Authorization baseline

- [ ] Guard tenant dashboards by authentication + tenant membership
- [ ] Guard admin dashboard by admin role
- [ ] Define “tenant owner” vs “tenant member” baseline permissions (minimal for Milestone 1)

---

# 5) Base dashboard structure (backend + frontend contract)

## 5.1 Backend

- [ ] Create dashboard route group(s)
  - [ ] `/admin/*` (platform admin)
  - [ ] `/app/*` (tenant app)
- [ ] Create minimal dashboard controllers/endpoints
  - [ ] Admin dashboard: show list of tenants + basic metrics placeholder
  - [ ] Tenant dashboard: show tenant info + placeholder widgets

## 5.2 Frontend (Next.js) — Milestone 1 minimum

- [ ] Create Next.js app scaffold
- [ ] Auth pages
  - [ ] Register
  - [ ] Login
- [ ] Dashboard shell
  - [ ] Layout (nav + header)
  - [ ] Tenant dashboard landing screen
  - [ ] Admin dashboard landing screen
- [ ] API integration
  - [ ] Central API client
  - [ ] Auth state management
  - [ ] Route guarding (protected routes)

---

# 6) Plugin logic extraction (analysis deliverables)

Milestone 1 requires analysis/extraction, not full implementation.

- [ ] Identify the “core logic” that will be migrated first
  - [ ] Jobs / case numbers / statuses (for later milestones)
  - [ ] Customer no-login portal requirements (for later milestones)
- [ ] Create an extraction report (deliverable)
  - [ ] List plugin CPTs/tables that map to future SaaS tables
  - [ ] List shortcodes/AJAX endpoints that map to future SaaS routes
  - [ ] Identify highest-risk areas (payments, PDF, SMS providers)
- [ ] Produce initial domain model mapping
  - [ ] Tenant -> Users -> (future) Jobs/Estimates/etc.

---

# 7) Dev environment deployed and stable (Milestone 1 acceptance)

- [ ] Local development setup works end-to-end
  - [ ] Fresh clone -> install -> migrate -> seed -> login -> dashboard
- [ ] Deployed development environment (staging/dev)
  - [ ] Backend deployed and reachable
  - [ ] Frontend deployed and reachable
  - [ ] Database provisioned
  - [ ] Environment secrets configured securely
- [ ] Operational basics
  - [ ] Application logs accessible
  - [ ] Error reporting baseline (at least server logs)

---

# 8) Quality gates (minimum for Milestone 1)

- [ ] Automated tests
  - [ ] Auth tests (register/login/logout)
  - [ ] Tenant isolation tests (cross-tenant access blocked)
  - [ ] Smoke test for dashboard endpoints
- [ ] Security checklist
  - [ ] CSRF handled (if cookie-based)
  - [ ] CORS configured correctly
  - [ ] Password policy decided
  - [ ] Rate limiting enabled for auth endpoints

---

# Milestone 1 “Definition of Done” (DoD)

Milestone 1 is complete when all are true:

- [ ] A new Laravel 11 backend exists and runs with MySQL
- [ ] A Next.js frontend exists with login/register and protected dashboard routes
- [ ] Multi-tenancy is implemented with **provable isolation** (tests + enforced scoping)
- [ ] Admin and tenant users can authenticate and see tenant-specific dashboards
- [ ] A stable dev/staging deployment is available for review
- [ ] A written extraction/mapping deliverable exists for the next milestones

---

# Landlord (Platform Admin) Module — Phased Checklist

Assumptions:

- [ ] **Single database** with strict `tenant_id` scoping for tenant-owned data
- [ ] Billing provider integration will be done later, but **billing infrastructure** must be in place now (provider-agnostic)
- [ ] **Impersonation mode** is supported with a **complete audit trail**

---

## Phase L1 — Platform admin access, tenant directory, and lifecycle

- [ ] Define platform admin roles (landlord RBAC)
  - [ ] `platform_admin` (full)
  - [ ] `support_agent` (impersonation + diagnostics)
  - [ ] `billing_admin` (billing-only actions)
  - [ ] `read_only_auditor` (audit + tenant read-only)
- [ ] Create platform admin navigation + route group
  - [ ] `/admin/tenants`
  - [ ] `/admin/tenants/{tenant}`
- [ ] Tenant directory (admin UI + API)
  - [ ] List/search/filter tenants by status/plan/date
  - [ ] Tenant detail page (metadata + health summary)
- [ ] Tenant model enhancements (beyond Milestone 1 minimum)
  - [ ] Tenant `status` supports at least: `trial`, `active`, `past_due`, `suspended`, `closed`
  - [ ] Record lifecycle timestamps (created/activated/suspended/closed)
  - [ ] Store tenant contact + billing contact fields (even before billing integration)
- [ ] Tenant provisioning (admin-driven)
  - [ ] Create tenant with owner user
  - [ ] Reset tenant owner password flow (admin-triggered, secure)
- [ ] Tenant suspension/unsuspension
  - [ ] Suspension reason + actor recorded
  - [ ] Enforce restrictions when suspended (define read/write behavior)
- [ ] Tenant offboarding
  - [ ] Close tenant (soft close)
  - [ ] Retention window field(s) (for future deletion policies)

---

## Phase L2 — Plans, entitlements, feature flags, and limits

- [ ] Plan catalog (data model + admin UI)
  - [ ] Create/edit plans (name, code)
  - [ ] Plan metadata fields prepared for billing (price display, billing interval)
- [ ] Entitlements engine
  - [ ] Feature flags by plan (boolean flags)
  - [ ] Limits/quotas by plan (numeric limits)
  - [ ] Per-tenant overrides (override plan defaults)
- [ ] Enforcement points
  - [ ] Backend policies/middleware enforce entitlements for protected modules
  - [ ] Frontend uses entitlements to hide/disable UI (but backend remains authoritative)
- [ ] Tenant configuration surface
  - [ ] Tenant settings page in admin: plan assignment + overrides
  - [ ] Audit all entitlement changes (who/when/what)

---

## Phase L3 — Support tooling + audited impersonation

- [ ] Impersonation design decisions
  - [ ] Define who can impersonate (RBAC)
  - [ ] Define session duration + expiration behavior
  - [ ] Define what actions are allowed during impersonation (recommended: allow, but audit everything)
- [ ] Impersonation workflow
  - [ ] Require reason + ticket/reference ID
  - [ ] Start impersonation (platform user -> tenant user)
  - [ ] Visible banner in UI while impersonating
  - [ ] Stop impersonation (explicit + automatic timeout)
- [ ] Audit trail requirements
  - [ ] Log: impersonator user, target tenant, target user, reason, start/end timestamps, IP/user agent
  - [ ] Log sensitive actions taken during impersonation (at minimum: writes and permission changes)
- [ ] Tenant diagnostics (per-tenant)
  - [ ] Recent auth events
  - [ ] Recent failed jobs/background tasks (if applicable)
  - [ ] Recent outbound communications (email/SMS logs if enabled later)

---

## Phase L4 — Billing infrastructure (provider-agnostic, no integration yet)

- [ ] Billing domain model (tables + relationships)
  - [ ] Billing customer (maps to a tenant)
  - [ ] Subscription (plan, status, renewal dates)
  - [ ] Invoices (amounts, currency, state)
  - [ ] Payments (attempts, outcomes)
  - [ ] Credits/adjustments (optional)
- [ ] Billing status state machine (drives product access)
  - [ ] Define transitions: `trial -> active -> past_due -> suspended -> closed`
  - [ ] Define grace periods and restriction levels
- [ ] Provider abstraction
  - [ ] Define a billing provider interface (create customer, sync subscription, handle webhook events)
  - [ ] Store provider identifiers (e.g., `provider_customer_id`, `provider_subscription_id`)
- [ ] Webhook ingestion infrastructure (even before provider selection)
  - [ ] Signed webhook endpoint framework with verification hooks
  - [ ] Event log table: store raw payload + processing status
  - [ ] Idempotency keys to prevent double-processing
- [ ] Product access enforcement
  - [ ] Gate entitlements by billing status (e.g., `past_due` restricts certain features)
  - [ ] Admin override for billing status (highly restricted + audited)
- [ ] Billing admin screens (minimal)
  - [ ] View tenant billing state and history
  - [ ] Manual set status (with reason) for testing/support

---

## Phase L5 — Security, governance, and operational robustness

- [ ] Platform audit logging (beyond impersonation)
  - [ ] Log tenant lifecycle changes
  - [ ] Log plan/entitlement changes
  - [ ] Log role/permission changes
- [ ] Data export and retention
  - [ ] Export tenant data (admin-triggered)
  - [ ] Retention policy fields + scheduled enforcement hooks
- [ ] Abuse prevention
  - [ ] Per-tenant rate limiting strategy (API + auth)
  - [ ] Lockdown/maintenance mode per tenant
- [ ] Monitoring and alerting (baseline)
  - [ ] Per-tenant error rate tracking (at least logs filtered by `tenant_id`)
  - [ ] Alert on repeated auth failures and elevated 5xx rates

---

## Phase L6 — RepairBuddy-specific landlord controls

- [ ] Customer portal access controls (per tenant)
  - [ ] Enable/disable case-number-based access without login
  - [ ] Optional extra verification mode (email/phone OTP)
  - [ ] Rate limits and abuse controls for status check endpoint
- [ ] Communications enablement (per tenant)
  - [ ] Toggle email/SMS channels (implementation later, but flags now)
  - [ ] Store sender identity metadata fields (for later deliverability compliance)

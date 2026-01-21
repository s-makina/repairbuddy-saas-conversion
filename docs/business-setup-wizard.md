---
description: Business registration and setup wizard specification
---

# Business Setup Wizard (Tenant Onboarding)

## Objective
After a business (tenant) creates an account, they must be redirected into a setup wizard to capture the minimum business and operational details needed to use the app confidently.

This wizard should:
- Reduce time-to-value (tenant can create their first job quickly)
- Collect correct legal/identity details for customer-facing documents and messaging
- Be resumable, auto-saved, and safe to skip where appropriate

## Entry / Routing Rules
- On successful tenant signup (or first login), if the tenant is not fully set up, redirect to the wizard.
- If setup is complete, send the user to the normal dashboard.

## Onboarding & Billing Flow (Execution Plan)

### Goal
Align onboarding with common SaaS best practices while supporting plan-dependent trials.

### High-level user journey
1. **Signup** (creates `user` + `tenant`)
2. **Plan selection** (inside authenticated app)
3. **Checkout** (or trial activation depending on selected plan)
4. **Business setup wizard** (`/[business]/setup`)
5. **Full app access** (`/app/[tenant]`)

### Required gating states (tenant-scoped)
These are the minimum states to route users correctly.

- **Billing / subscription state** (one of)
  - `subscription_status = none` (no plan selected)
  - `subscription_status = pending_checkout` (plan selected, payment not completed)
  - `subscription_status = trialing` (trial active; depends on plan)
  - `subscription_status = active` (paid and active)
  - `subscription_status = past_due` (grace-limited access)
  - `subscription_status = suspended` (no access)

- **Setup state**
  - `setup_completed_at` (nullable)
  - `setup_step`
  - `setup_state`

### Redirect / route gating rules (authoritative order)
Apply this logic after login and on protected routes (server + client where appropriate).

1. **Suspended tenant**
   - If tenant is suspended, route to a dedicated suspension screen.
   - Do not allow setup or checkout screens unless explicitly intended.

2. **No plan selected**
   - If `subscription_status = none`, route to plan selection.

3. **Plan selected but payment required and not completed**
   - If `subscription_status = pending_checkout`, route to checkout.

4. **Subscription/trial OK but setup incomplete**
   - If `subscription_status in (trialing, active, past_due)` and `setup_completed_at is null`, route to `/[business]/setup`.

5. **Subscription/trial OK and setup complete**
   - Route to the normal app dashboard `/app/[tenant]`.

### Trial behavior
- Trial eligibility is **plan-dependent**.
- When a user selects a plan:
  - If plan has trial: set `subscription_status = trialing` (with end date) and skip payment.
  - If plan requires payment: set `subscription_status = pending_checkout` and start checkout.

### Billing details timing
- For best UX, collect **minimal billing basics** as part of checkout (or just before it):
  - Billing country
  - Currency
  - VAT number (optional)
- Full business setup (logo, branding, operational defaults) happens after subscription/trial is confirmed.

### Implementation checklist

#### Backend
- Add/confirm fields needed to represent `subscription_status` and trial end date.
- Implement endpoints:
  - Plan list (public or authenticated)
  - Start checkout / create subscription intent
  - Confirm webhook/return handler to mark subscription active
- Ensure tenancy middleware and route rules respect `subscription_status`:
  - `suspended` should return a clear error code/message.

#### Frontend
- Add an authenticated onboarding route, e.g. `/app/[tenant]/onboarding`:
  - Step: plan selection
  - Step: checkout
- Add routing guards so logged-in users land in the correct step (based on tenant + subscription + setup state).
- Keep `/[business]/setup` as the setup wizard, but only reachable once subscription/trial is OK.

#### UX
- Users should always be able to resume:
  - If checkout is pending, show “Resume checkout”
  - If setup is incomplete, resume at `setup_step`

### Notes / decisions
- Tenant is created at signup.
- Language is locked to English for now.

### Proposed state flags (tenant-scoped)
- `setup_completed_at` (nullable)
- `setup_step` (string/enum, last saved step)
- `setup_state` (JSON; step data + completion markers)

### Redirect behavior
- If `setup_completed_at` is null:
  - Redirect to `/setup` (wizard)
  - Allow limited navigation but show persistent "Finish setup" banner

## UX Principles
- Auto-save after each step (and optionally on field blur)
- Clear progress indicator (stepper + percentage)
- Explicit required vs optional fields
- Ability to skip non-critical steps (with a short explanation)
- Resume from last saved step
- Mobile-friendly layout

## Wizard Steps (Recommended Order)

### Step 0: Welcome
**Purpose**: Set expectations and reduce friction.

**UI elements**
- Title: “Set up your business”
- Subtext: “This takes about 3–5 minutes”
- Step list preview
- CTA: “Start setup”

**Data captured**: none

---

### Step 1: Business identity (Required)
**Purpose**: Establish tenant business identity for customer-facing pages and documents.

**Fields**
- Business/Shop name (required)
- Display name (optional; defaults to business name)
- Primary contact person name (required)
- Primary contact email (required)
- Primary contact phone (required)
- Business registration number (optional)

**Validation**
- Email format validation
- Phone format validation (lenient; country-aware later)

---

### Step 2: Address & locale (Required)
**Purpose**: Correct timezone, formatting, tax defaults, and templates.

**Fields**
- Country (required)
- Address line 1 (required)
- Address line 2 (optional)
- City (required)
- State/Region (optional; required in some countries)
- Postal code (required)
- Timezone (required; default from browser)
- Default language (required; default from browser)
- Default currency (required; default from country)

**Validation**
- Country required
- Timezone must be a valid TZ ID

---

### Step 3: Branding & customer-facing details (High impact)
**Purpose**: Make the tenant experience feel "ready" and professional.

**Fields**
- Logo upload (optional but recommended)
- Brand color (optional)
- Public support email (optional; default to primary contact email)
- Public support phone (optional; default to primary contact phone)
- Website (optional)
- Document footer text (optional)

**Validation**
- Logo: file type + max size
- Website: URL format (optional)

---

### Step 4: Operations defaults (Recommended)
**Purpose**: Enable sensible defaults for job creation and customer updates.

**Fields**
- Working hours (optional; default Mon–Fri 09:00–17:00)
- Default labor rate (optional)
- Default warranty terms (optional)
- Customer notifications (toggle defaults)
  - Email on status change (recommended on)
  - Email on invoice created (optional)

---

### Step 5: Tax & invoicing (Optional for MVP; required to issue invoices)
**Purpose**: Ensure invoices and tax calculations behave correctly.

**Fields**
- Tax/VAT registered? (yes/no)
- VAT number (required if registered)
- Invoice numbering settings (optional; default system)
  - Prefix
  - Format preview

**Notes**
- If billing/tax features are not part of current MVP, this step can be shown as “Set up later” and moved into settings.

---

### Step 6: Team & permissions (Optional)
**Purpose**: Invite staff and assign roles early.

**Fields**
- Invite team members (email list)
- Role per invite
  - Owner/Admin
  - Technician
  - Front Desk

---

### Step 7: Finish & next actions
**Purpose**: Confirm readiness and drive the first key action.

**UI elements**
- Summary of configured sections
- Readiness checklist (what’s done / missing)
- CTAs:
  - Create first job
  - Import customers (CSV)
  - Go to dashboard

**Completion action**
- Set `setup_completed_at = now()`
- Clear or finalize `setup_step`

## Data Model (High-level)
Tenant profile should store:
- Identity: business name, registration number
- Contacts: owner/admin contact details
- Locale: address, country, timezone, currency, language
- Branding: logo, color
- Operations: working hours, default labor rate, warranty terms
- Optional: tax identifiers, invoice numbering prefs

All persisted data must be strictly `tenant_id` scoped.

## Persistence & Resume Behavior
- Save step data on "Next".
- Optionally auto-save on field blur for smoother UX.
- If user exits and returns, resume at `setup_step`.

## Error Handling
- Inline field validation
- Disable “Next” until required fields are valid
- On server error, keep entered data and show actionable message

## Acceptance Criteria
- After signup, user is redirected to `/setup` until completion.
- Wizard supports resume (refresh + logout/login returns to last step).
- Required fields are enforced per step.
- Completion marks the tenant as set up and routes to dashboard.
- Wizard is usable on mobile (single-column layout).

## Open Questions
- Should tenant slug/subdomain be chosen during signup or in the wizard?
- Is invoicing/tax required for MVP, or should it be deferred into settings?
- Which notification channels are supported in MVP (email only vs email + SMS/WhatsApp later)?

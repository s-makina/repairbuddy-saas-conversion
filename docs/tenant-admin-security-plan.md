# Tenant Admin Security (Enforced) — Implementation Plan

## Scope
This plan covers **tenant-admin managed security policies** that are **enforced across all users in a tenant**.

Non-scope (for later):
- User preference settings (per-user UX beyond required enforcement)
- IP allowlist enforcement (v2)

## Key decisions (confirmed)
- **MFA enforcement is role-targeted**: tenant admin can select **multiple roles** for which MFA is required.
- **Grace period included now**: enforcement can be delayed for a defined period.
- **IP allowlist**: defer to v2; use mock data for UI iteration later.
- **Audit Log UI**: include now (tenant-scoped).

## Milestones

### Milestone 1 — Tenant security policy model (source of truth)
**Outcome**: A tenant has stored, versioned/trackable security policy settings with strict tenant scoping.

Deliverables:
- Data model for `tenant_security_settings` (or equivalent) including:
  - `mfa_required_roles` (multi-select by role)
  - `mfa_grace_period_days`
  - `mfa_enforce_after` (timestamp computed when enabling/enforcing)
  - `session_idle_timeout_minutes`
  - `session_max_lifetime_days`
  - `lockout_max_attempts`
  - `lockout_duration_minutes`
- Validation rules + defaults for new and existing tenants
- API endpoints (tenant-scoped, admin-only):
  - `GET /api/tenant/security-settings`
  - `PUT /api/tenant/security-settings`
- Permissions/guards:
  - Require `security.manage` (or tenant-admin role) and strict tenant access
- Audit events emitted for policy changes

Acceptance criteria:
- Tenant admin can fetch/update settings for *their* tenant only.
- Settings have safe defaults when absent.

---

### Milestone 2 — MFA requirement enforcement (roles + grace period)
**Outcome**: Users in selected roles must enable/confirm TOTP after grace period; enforcement is consistent across app.

Enforcement rules:
- Tenant admin selects one or more roles in `mfa_required_roles`.
- If user has any role in that set and **current time >= mfa_enforce_after**, then:
  - User must have `otp_enabled = true` and `otp_confirmed_at != null`.
  - If not compliant, block access to protected app routes and force completion.
- Grace period:
  - When policy is changed from “not requiring” to “requiring”, set `mfa_enforce_after = now + gracePeriod`.
  - If grace period is updated while requirement is active, keep behavior deterministic (define update policy during implementation).

Deliverables:
- Backend enforcement point(s):
  - Middleware / guard that checks tenant policy + user roles + OTP status
  - Exemptions for tenant owner if you want a break-glass option (explicitly decided during implementation)
- Frontend routing:
  - If blocked for MFA, route to a dedicated “MFA required” step that links to OTP setup
- Compliance status computation:
  - Counts for compliant/non-compliant users

Acceptance criteria:
- A user in a required role cannot access tenant app after grace period unless OTP is confirmed.
- A user not in the selected roles is not forced.

---

### Milestone 3 — Session + login protections (tenant-enforced)
**Outcome**: Tenant admin controls baseline session hardening; brute force is mitigated.

Deliverables:
- Session controls:
  - Idle timeout (server-enforced)
  - Absolute lifetime (server-enforced)
  - “Force logout all tenant users” action
- Login/OTP protections:
  - Lockout after N failed logins
  - Rate limiting for login + OTP endpoints

Acceptance criteria:
- Configurable timeouts take effect for tenant users.
- Force logout invalidates active sessions.
- Failed login attempts trigger lockout according to policy.

---

### Milestone 4 — Tenant Security Audit Log (API + UI)
**Outcome**: Tenant admin can review security-relevant events with filters.

Events (minimum viable set):
- Auth:
  - login success
  - login failure
  - lockout triggered
- MFA:
  - otp_setup_started
  - otp_confirmed
  - otp_disabled
  - otp_confirm_failed
- Admin actions:
  - tenant security settings updated
  - force logout executed
- Impersonation:
  - impersonation started/ended (actor + target)

Deliverables:
- Persistent `audit_events` (or equivalent) with tenant scoping
- API:
  - `GET /api/tenant/audit-events?type=&user_id=&from=&to=`
- UI:
  - Audit log table with filters + pagination

Acceptance criteria:
- Tenant admin only sees their tenant’s events.
- Impersonation events are visible and attributable.

---

### Milestone 5 — Tenant Admin Security UI (`/app/[tenant]/security`)
**Outcome**: A tenant admin can configure policies, view compliance, and review audit logs in one place.

Page sections:
- Policies
  - MFA required roles (multi-select)
  - Grace period configuration
  - Session timeouts
  - Lockout policy
  - Force logout button
- Compliance
  - MFA compliance counts + list of non-compliant users
- Audit log
  - Filterable event list

Acceptance criteria:
- Settings save and are enforced.
- Compliance and audit log are tenant-scoped and accurate.

## v2 backlog (explicitly deferred)
- IP allowlist (UI first with mock data, then enforcement)
- Email alerts / notifications for incidents
- SSO (SAML/OIDC)
- User-level preferences UI (beyond required enforcement)

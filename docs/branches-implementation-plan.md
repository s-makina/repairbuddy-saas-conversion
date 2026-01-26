# Branches (Multi-Shop) Implementation Plan

## Goals

- Support multiple physical locations (“branches/shops”) per tenant.
- Tenant admin can access all branches by default.
- Users can be assigned to multiple branches.
- Users operate in exactly one **active branch** at a time (select at login; switch later).
- Future modules (appointments, inventory, repairs, etc.) must be branch-aware by including `branch_id`.
- Invoicing is branch-specific, including branch-specific invoice numbering/sequence.

## Non-goals (for now)

- Multi-tenant across different databases.
- Complex per-branch pricing plans/subscriptions.
- Inter-branch stock transfer workflows (inventory will arrive later).

## Terminology

- **Tenant**: a business account in the system.
- **Branch**: a physical location/shop under a tenant.
- **Active branch**: the branch context the user is currently working in.
- **Branch role**: a role a user holds within a specific branch (e.g. branch manager).

## Design Principles

- **Strict tenant isolation remains the primary boundary**: everything remains scoped by `tenant_id`.
- **Branch is a secondary scope** inside a tenant.
- **Default branch** per tenant ensures backward compatibility.
- Branch scoping should be **hard to forget** (framework guardrails for all branch-owned models).
- Tenant admin should be able to view/manage **all branches**, but still operate within an active branch context.

## Data Model

### 1) `branches` table

Purpose: represent locations/shops.

Recommended columns:

- `id`
- `tenant_id` (FK, indexed)
- `name`
- `code` (short code, used for invoice numbering; unique per tenant)
- `phone`, `email` (optional)
- address fields (optional initially; needed later for invoice footer)
- `is_active` (soft-disable a branch without deleting it)
- `created_at`, `updated_at`

Constraints:

- `unique(tenant_id, code)`

Default branch strategy (choose one):

- **Option A (preferred)**: `tenants.default_branch_id` (FK to branches)
- Option B: `branches.is_default` with application-level enforcement

### 2) `branch_user` table (pivot)

Purpose: user can work in multiple branches.

Recommended columns:

- `id`
- `tenant_id` (indexed)
- `branch_id` (indexed)
- `user_id` (indexed)
- `created_at`, `updated_at`

Constraints:

- `unique(branch_id, user_id)`

Notes:

- Tenant admin is not required to have explicit `branch_user` rows. They implicitly have access to all tenant branches.
- Non-admin staff should require explicit assignment.

### 3) `branch_user_roles` (branch roles)

Purpose: assign roles within a branch (e.g. branch manager).

Recommended columns:

- `id`
- `tenant_id` (indexed)
- `branch_id` (indexed)
- `user_id` (indexed)
- `role` (string or enum: `branch_manager`, `advisor`, `technician`, etc.)
- `created_at`, `updated_at`

Constraints:

- `unique(branch_id, user_id, role)`

Notes:

- Branch roles are additive.
- Tenant-wide roles (like `tenant_admin`) should remain tenant-level.

## Active Branch (Session Context)

### Where active branch is stored

- Backend session is authoritative: `active_branch_id`.
- Frontend may store a cached value for UX, but backend must validate.

### Login behavior

After authentication + tenant resolution:

- If user is tenant admin: allow choosing any branch (default to tenant default branch).
- If user is not tenant admin:
  - If assigned to exactly 1 branch: auto-select it.
  - If assigned to >1: force selection.
  - If assigned to 0: block (or show instructions to contact admin).

### Switching branch

- Provide a branch switcher that updates `active_branch_id`.
- When switched, lists and creation flows operate under the new branch.

## Authorization & Scoping

### Access rules

- Tenant admin:
  - Can access all branches.
  - Can manage branches and assignments.
  - Can operate in any branch by setting active branch.

- Branch manager:
  - Can manage operational data within assigned branches.
  - Can manage staff assignments within those branches (optional; enforce via permission policy).

- Staff:
  - Can access data only within their assigned branches.
  - Can only operate within active branch.

### Enforcing branch scoping for future modules

Implement a standard, reusable approach for “branch-owned” models.

Recommended approach:

- An **ActiveBranchResolver** service that returns `branch_id` for the request.
- A model trait (example naming): `BelongsToBranch` that:
  - automatically sets `branch_id` on create if missing
  - adds a global scope that filters by `branch_id` (unless explicitly bypassed)
- For admin “all branches” pages, do not bypass tenant scoping; use an explicit query with allowed branch IDs.

Notes:

- All branch-owned tables should still include `tenant_id`.
- Controllers/services should treat `(tenant_id, branch_id)` as required inputs for creation.

## Invoice Numbering (Branch-specific)

### Format

Recommended format:

`RB-{TENANT_SLUG}-{BRANCH_CODE}-{YYYY}-{NNNN}`

Example:

`RB-ACME-CPT-2026-0007`

### Sequencing

Add a counter table:

#### `branch_invoice_counters`

- `id`
- `tenant_id` (indexed)
- `branch_id` (indexed)
- `year` (int)
- `next_number` (int)
- timestamps

Constraints:

- `unique(tenant_id, branch_id, year)`

Generation must be done in a transaction with row lock to avoid collisions.

## Step-by-step Execution Plan

### Phase 1 — Schema foundation

1) Create migration: `branches`.
2) Create migration: add `default_branch_id` to `tenants` (or implement your chosen default strategy).
3) Create migration: `branch_user`.
4) Create migration: `branch_user_roles`.
5) Seeder/backfill:
   - For every existing tenant, create a default branch: `Main Branch`.
   - Set `tenants.default_branch_id`.
   - (Optional) Assign existing users to the default branch.

Acceptance criteria:

- Tenants have at least one branch.
- Tenant admin can create/edit/deactivate branches.

### Phase 2 — Active branch selection

1) Add backend endpoint to list accessible branches for current user.
2) Add backend endpoint to set active branch.
3) Store `active_branch_id` in session.
4) Update frontend auth flow:
   - After login, if multiple branches available, redirect to “Choose branch”.
   - Otherwise auto-select.
5) Add a UI branch switcher.

Acceptance criteria:

- User always has a valid active branch before accessing branch-owned modules.
- Tenant admin can switch to any branch.

### Phase 3 — Authorization policies for branch roles

1) Define branch roles and permissions mapping:
   - `tenant_admin` (tenant-level)
   - `branch_manager` (branch-level)
   - optionally `technician`, `advisor`, etc.
2) Implement policies/guards:
   - tenant admin bypasses branch assignment checks but still respects tenant boundary.
   - branch manager can manage branch operations and (optionally) assignments.
3) Add UI to assign roles per branch.

Acceptance criteria:

- Branch managers can manage within assigned branches.
- Staff cannot access other branches.

### Phase 4 — Branch enforcement for future modules

1) Create a shared “branch-owned model” pattern (trait + resolver).
2) Document a checklist for new modules:
   - table includes `tenant_id`, `branch_id`
   - model uses the branch trait
   - creation sets `branch_id` from active branch
   - queries are scoped by active branch (except explicit admin reports)
3) Add a basic test (or automated check) ensuring certain tables include `branch_id` once modules exist.

Acceptance criteria:

- Developers have a standard pattern; no module can be implemented without branch scope.

### Phase 5 — Branch-specific invoicing

1) Add `branch_invoice_counters` migration.
2) Implement invoice number generator using:
   - tenant slug
   - branch code
   - year
   - counter sequence
3) Ensure invoice creation always uses active branch.

Acceptance criteria:

- Invoice numbers are unique per (branch, year).
- Two invoices created concurrently do not collide.

## Open Decisions

- Do we require non-admin users to have at least one `branch_user` assignment before login is allowed?
- Should branch managers be allowed to assign users to their branch (or tenant admin only)?
- Do we want “All branches” reporting views early, or defer until reporting modules exist?

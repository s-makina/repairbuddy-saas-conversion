# RepairBuddy SaaS — UI/UX Foundation Plan (Next.js)

## Goal
Create a **solid UI foundation** for the RepairBuddy SaaS in **React/Next.js** that:

- Preserves the **RepairBuddy visual brand** (colors, layout feel, familiar patterns).
- Modernizes the experience into a **clean, responsive SaaS UI**.
- Establishes a scalable system so future screens are consistent (dashboard, CRUD, reports, appointments/calendar, portal screens).

## Scope (Admin + Customer Portal)
This foundation applies to **both**:

- **Admin app** (internal staff: technicians/managers/admins)
- **Customer Portal** (end customers)

For now, both experiences should share:

- The same **design tokens** (colors, spacing, radii, typography)
- The same **component library** (forms, tables, modals, alerts)
- The same **interaction rules** (loading states, validation, feedback)

Later, we can introduce a portal-specific “variant” (lighter layout density, fewer admin-style tables, simplified navigation) without forking the design system.

This document is **planning only** (no implementation).

---

## Source UI Reference (WordPress Plugin)
The plugin UI behaves like an embedded mini-app in WP Admin.

### Key visual DNA to carry forward
- **Primary color (brand blue):** `#063e70`
- **Accent color (brand orange):** `#fd6742`
- **Surfaces:** `#ffffff` and light grey sections around `#f7f7f7`
- **Borders:** `#ededed`
- **Common radii:** ~`10px`, `15px`, `20px`
- **Card shadow style:** `0 1px 15px 1px rgba(52, 40, 104, 0.08)`

### Key layout + pattern references
- **Left sidebar** with logo header block and nav items.
- **Right content area** with generous padding and simple sectioning.
- **Dashboard** with:
  - Shortcut icon tiles
  - Widget cards
- **Admin behaviors** (to modernize):
  - Modals/overlays
  - AJAX form submission patterns
  - Tables and row actions

---

## Decisions (recommended)
These choices aim for “same brand, modern SaaS”, and keep future work fast.

### Shared system with optional variants (recommended)
- Keep **one** token system and component library.
- Allow small, controlled variants via tokens (example: `--density-compact` vs `--density-comfortable`) rather than duplicating components.
- Prefer “composition” (different page templates) over rewriting components for portal vs admin.

### UI stack recommendation
- **Next.js (App Router)**
- **Tailwind CSS** for consistent token-driven styling
- **shadcn/ui + Radix** for accessible primitives (Dialog, DropdownMenu, Tabs, Select, etc.)
- **Lucide** for general icon set, plus **RepairBuddy custom icons** for key modules
- **React Hook Form + Zod** for consistent forms/validation
- **TanStack Table** for robust data tables (sorting/filtering/pagination)

If you already committed to a different component stack, we can keep the plan but swap equivalents.

---

# Step-by-step Foundation Roadmap

## Phase 1 — Brand Tokens + Theming (lock the DNA)
**Objective:** Freeze the visual language before building screens.

### Deliverables
- **Design tokens**:
  - Color palette (brand + neutrals + semantic)
  - Spacing scale
  - Border radii scale
  - Shadow scale
  - Typography scale
- **Theme rules**:
  - Light theme first
  - Optional dark theme later (do not block MVP)
- **Token usage rules**:
  - No ad-hoc hex values in components
  - No one-off spacing values unless approved

### Acceptance criteria
- A single source of truth for tokens.
- All components reference tokens (not arbitrary styles).

---

## Phase 2 — App Shell + Navigation (prevent layout drift)
**Objective:** Create the structural skeleton all pages will use.

### Deliverables
- **App Shell**
  - Sidebar (~200px desktop baseline to match plugin feel)
  - Top area for page title + actions
  - Content container with consistent padding (plugin feels like ~30px)
- **Responsive navigation**
  - Desktop: persistent sidebar
  - Mobile: off-canvas drawer + overlay
- **Nav item behavior**
  - Active state clearly indicated
  - Hover matches brand (orange treatment)
  - Keyboard navigation + focus styles

### Acceptance criteria
- Every new route/page uses the same shell.
- Sidebar does not re-implement per page.

---

## Phase 3 — Core Component Library (primitives first)
**Objective:** Build reusable primitives before building feature pages.

### Component inventory (minimum set)
- **Typography**
  - `PageTitle`, `SectionTitle`, `Label`, `HelpText`
- **Buttons**
  - `Button` (primary/secondary/ghost)
  - `IconButton`
- **Surfaces**
  - `Card` (widget/panel style)
  - `Section` (light grey surface like plugin sections)
- **Feedback**
  - `Alert/Callout` (success/info/warn/error, closable)
  - `Toast` strategy (optional)
  - `Loading` (spinner + skeletons)
- **Forms**
  - `FormField` wrapper (label, required, help, error)
  - `Input`, `Textarea`, `Select`, `Combobox` (searchable)
  - `Checkbox`, `Switch`, `RadioGroup`
- **Overlays**
  - `Modal/Dialog`
  - `ConfirmDialog`
- **Data display**
  - `Table` wrapper (TanStack)
  - `Badge` (statuses)
  - `EmptyState`

### UX rules
- No `alert()` for errors.
- Every mutation has:
  - loading state
  - success feedback
  - error feedback

### Acceptance criteria
- Components are documented with usage examples.
- New pages cannot introduce new “button styles” or random card styles.

---

## Phase 4 — Page Templates (speed + consistency)
**Objective:** Create repeatable page archetypes so future modules don’t drift.

### Required templates
- **Dashboard template**
  - Shortcut tiles (RepairBuddy icon feel)
  - Widget cards (counts, quick actions)
- **CRUD list template**
  - Filters/search
  - Table
  - Bulk actions
  - Row actions
- **CRUD create/edit template**
  - Form sections
  - Sticky save action pattern (optional)
  - Read-only vs editable states
- **Reports landing template**
  - Panel cards with heading bar + link list (like plugin reports)
- **Calendar template** (appointments)
  - Month/week/day views
  - Status legend
  - Color rules for statuses

### Acceptance criteria
- For any new module, you choose a template rather than inventing a layout.

---

## Phase 5 — Icon & Illustration System (brand + modern)
**Objective:** Keep “RepairBuddy-ness” while being SaaS-clean.

### Strategy
- Use **Lucide** (or similar) for general UI icons (buttons, actions, UI chrome).
- Use **RepairBuddy custom module icons** (jobs/devices/clients/payments/etc.) for:
  - Sidebar primary nav
  - Dashboard shortcuts

### Deliverables
- Icon size rules
  - Sidebar: 18–20px
  - Toolbar actions: 16–18px
  - Dashboard tiles: 48–64px
- One place to manage icons (e.g. `public/icons/repairbuddy/`)

### Acceptance criteria
- Icons are consistent in size, stroke/weight, and alignment.

---

## Phase 6 — Accessibility + QA Baseline (professional standard)
**Objective:** Ensure the foundation is robust and future-proof.

### Requirements
- Keyboard navigable app shell + menus + dialogs
- Visible focus states
- Color contrast checks (brand orange usage especially)
- Proper labeling for form fields
- Error messages tied to fields (`aria-describedby`)

### Testing / tooling recommendations
- ESLint + accessibility rules
- Optional: automated checks (axe) in CI

### Acceptance criteria
- No blocking accessibility issues on core flows.

---

## Phase 7 — Documentation + Governance (keep it consistent long-term)
**Objective:** Prevent UI regression and one-off design decisions.

### Deliverables
- A simple **UI rules** doc:
  - when to use primary vs secondary button
  - spacing rules
  - layout rules
  - status color rules
- Component documentation
  - Prefer Storybook if the team likes it, otherwise a `/ui-kit` route in-app.
- A PR checklist:
  - “Did you use existing components?”
  - “Did you add new tokens?”
  - “Does it work on mobile?”

---

# Definition of Done (for the UI Foundation)
The UI foundation is considered complete when:

- App shell is implemented and used everywhere.
- Tokens exist and are used consistently.
- Core components exist for forms/tables/modals/alerts.
- At least one of each template type is built as a reference (dashboard + CRUD list + CRUD form + reports landing + calendar shell).
- A11y baseline checks pass for navigation/forms/dialogs.
- Documentation exists so future work stays consistent.

---

# Next Actions (non-implementation)
- Confirm navigation structure (module list + labeling) for the SaaS sidebar.
- Confirm which plugin icons should be preserved 1:1 vs replaced with modern equivalents.
- Confirm initial page priorities (which templates to build first as references).

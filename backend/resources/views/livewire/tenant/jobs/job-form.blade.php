@props([
    'tenant',
    'customers' => [],
    'technicians' => [],
    'customerDevices' => [],
    'jobStatuses' => [],
    'paymentStatuses' => [],
])

@push('page-styles')
<style>
    /* ═══════════════════════════════════════════════════════
       Design B — Single-Page, All-at-Once Layout
       No wizard/stepper. Everything visible with collapsible
       sections. Left: main form. Right: sticky sidebar.
       Dense, efficient, power-user friendly.
       ═══════════════════════════════════════════════════════ */

    :root {
        --rb-brand: #0ea5e9;
        --rb-brand-soft: #e0f2fe;
        --rb-brand-dark: #0284c7;
        --rb-success: #22c55e;
        --rb-success-soft: #dcfce7;
        --rb-danger: #ef4444;
        --rb-danger-soft: #fef2f2;
        --rb-warning: #f59e0b;
        --rb-warning-soft: #fef3c7;
        --rb-bg: #f8fafc;
        --rb-card: #ffffff;
        --rb-border: #e2e8f0;
        --rb-border-h: #cbd5e1;
        --rb-text: #0f172a;
        --rb-text-2: #475569;
        --rb-text-3: #94a3b8;
        --rb-radius: 12px;
        --rb-radius-sm: 8px;
        --rb-shadow: 0 1px 3px rgba(0,0,0,.06);
        --rb-shadow-md: 0 4px 12px rgba(0,0,0,.07);
    }

    [x-cloak] { display: none !important; }

    /* ── Page background override ── */
    .container-fluid.jf-page {
        background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
        min-height: 100vh;
        margin: -1rem -1rem 0 -1rem;
        padding: 0;
        width: calc(100% + 2rem);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        line-height: 1.5;
        color: var(--rb-text);
    }

    /* ── Sticky Top Bar ── */
    .jf-top-bar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--rb-border);
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 0 var(--rb-border), 0 2px 8px rgba(14,165,233,.04);
    }
    .jf-top-bar-inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: .65rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .jf-top-bar-inner .jf-left { display: flex; align-items: center; gap: 1rem; }

    /* Back button */
    .jf-back-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid var(--rb-border);
        background: #fff;
        color: var(--rb-text-2);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        flex-shrink: 0;
        font-size: .88rem;
        transition: all .15s;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .jf-back-btn:hover {
        background: var(--rb-bg);
        color: var(--rb-brand);
        border-color: var(--rb-brand);
    }

    /* Page title block */
    .jf-title-block { line-height: 1.2; }
    .jf-title-block .jf-mode-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .55rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .3rem;
    }
    .jf-title-block .jf-page-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: 1rem;
        font-weight: 800;
        color: var(--rb-text);
        margin: 0 0 .15rem 0;
    }
    .jf-title-block .jf-page-title i { color: var(--rb-brand); font-size: .9rem; }
    .jf-mode-badge.mode-create {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .jf-mode-badge.mode-edit {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    /* Breadcrumb */
    .jf-breadcrumb {
        display: flex;
        align-items: center;
        gap: .2rem;
        font-size: .72rem;
        color: var(--rb-text-3);
        margin: 0;
        list-style: none;
        padding: 0;
    }
    .jf-breadcrumb a {
        color: var(--rb-text-3);
        text-decoration: none;
        transition: color .12s;
    }
    .jf-breadcrumb a:hover { color: var(--rb-brand); }
    .jf-breadcrumb .jf-bc-sep {
        font-size: .6rem;
        opacity: .4;
        margin: 0 .05rem;
    }
    .jf-breadcrumb .jf-bc-current {
        color: var(--rb-text-2);
        font-weight: 600;
    }

    .jf-top-bar-inner .jf-right { display: flex; gap: .5rem; }

    .jf-btn {
        padding: .5rem 1.25rem;
        border-radius: var(--rb-radius-sm);
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        text-decoration: none;
    }
    .jf-btn-cancel {
        background: transparent;
        color: var(--rb-text-2);
        border: 1px solid var(--rb-border);
    }
    .jf-btn-cancel:hover { background: var(--rb-bg); color: var(--rb-text); }
    .jf-btn-save {
        background: var(--rb-brand);
        color: #fff;
    }
    .jf-btn-save:hover { background: var(--rb-brand-dark); }

    /* ── Page Layout ── */
    .jf-layout {
        display: flex;
        gap: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem 2rem;
        align-items: flex-start;
    }
    .jf-main { flex: 1; min-width: 0; }
    .jf-side { width: 320px; flex-shrink: 0; }
    .jf-side .jf-sticky {
        position: sticky;
        top: 5rem;
    }

    /* ── Collapsible Sections ── */
    .jf-section {
        background: var(--rb-card);
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: var(--rb-shadow);
    }
    .jf-section-head {
        display: flex;
        align-items: center;
        gap: .625rem;
        padding: .75rem 1rem;
        cursor: pointer;
        user-select: none;
        transition: background .12s;
    }
    .jf-section-head:hover { background: var(--rb-bg); }
    .jf-section-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .88rem;
        flex-shrink: 0;
    }
    .jf-section-head h3 {
        font-size: .92rem;
        font-weight: 700;
        flex: 1;
        margin: 0;
    }
    .jf-section-head .jf-tag {
        font-size: .68rem;
        font-weight: 700;
        padding: .15rem .5rem;
        border-radius: 999px;
    }
    .jf-section-head .jf-chevron {
        font-size: .75rem;
        color: var(--rb-text-3);
        transition: transform .25s;
    }
    .jf-section-body {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--rb-border);
    }

    /* ── Form Groups ── */
    .jf-fg { margin-bottom: .875rem; }
    .jf-fg:last-child { margin-bottom: 0; }
    .jf-fg > label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: var(--rb-text-2);
        margin-bottom: .3rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .jf-fg .form-control,
    .jf-fg .form-select {
        width: 100%;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: .5rem .75rem;
        font-size: .86rem;
        color: var(--rb-text);
        background: #fff;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        height: auto;
    }
    /* Don't force width:100% on inputs inside Bootstrap input-groups */
    .jf-fg .input-group .form-control,
    .jf-fg .input-group .form-select {
        width: auto;
        flex: 1 1 auto;
    }
    .jf-fg .form-control:focus,
    .jf-fg .form-select:focus {
        border-color: var(--rb-brand);
        box-shadow: 0 0 0 3px rgba(14,165,233,.12);
        background: #fff;
    }
    .jf-fg .form-text,
    .jf-fg .jf-hint {
        font-size: .72rem;
        color: var(--rb-text-3);
        margin-top: .2rem;
    }

    .jf-row { display: flex; gap: .75rem; }
    .jf-c2 { flex: 1; }
    .jf-c3 { flex: 1; }

    .jf-dates-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .75rem;
    }
    @media (max-width: 767.98px) {
        .jf-dates-grid { grid-template-columns: 1fr; }
    }

    /* ── Search Select / Dropdown ── */
    .jf-search-container { position: relative; width: 100%; }
    .jf-search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #fff;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        margin-top: 4px;
        box-shadow: 0 10px 25px rgba(0,0,0,.1);
        max-height: 280px;
        overflow-y: auto;
    }
    .jf-search-item {
        padding: .6rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background .12s;
        border-bottom: 1px solid #f8fafc;
    }
    .jf-search-item:last-child { border-bottom: none; }
    .jf-search-item:hover { background: #f8fafc; }
    .jf-search-item .jf-item-title { font-weight: 500; font-size: .875rem; color: var(--rb-text); }
    .jf-search-item .jf-item-meta { font-size: .75rem; color: var(--rb-text-3); }

    .jf-selected-box {
        background: var(--rb-brand-soft);
        border: 1px solid #bae6fd;
        border-radius: var(--rb-radius-sm);
        padding: .5rem .75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .jf-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .jf-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: .2rem .6rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 500;
    }
    .jf-chip .btn-remove-chip {
        background: none;
        border: none;
        padding: 0;
        color: #60a5fa;
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
    }
    .jf-chip .btn-remove-chip:hover { color: #1d4ed8; }

    .btn-gradient {
        background: linear-gradient(135deg, var(--rb-brand) 0%, var(--rb-brand-dark) 100%);
        border: none;
        color: #fff !important;
        font-weight: 600;
        transition: all .2s ease;
        box-shadow: 0 4px 12px rgba(14,165,233,.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(14,165,233,.3);
        filter: brightness(1.05);
        color: #fff !important;
    }

    /* ── Devices ── */
    .jf-dev-form-card {
        background: var(--rb-bg);
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .jf-dev-form-card h6 {
        font-size: .85rem;
        font-weight: 700;
        margin-bottom: .75rem;
    }

    .jf-dev-row {
        display: flex;
        align-items: center;
        gap: .625rem;
        padding: .5rem .625rem;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        background: var(--rb-bg);
        margin-bottom: .375rem;
        font-size: .84rem;
        transition: border-color .15s;
    }
    .jf-dev-row:hover { border-color: var(--rb-brand); }
    .jf-dev-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #dbeafe;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .9rem;
    }
    .jf-dev-info { flex: 1; min-width: 0; }
    .jf-dev-info strong { font-size: .84rem; display: block; }
    .jf-dev-info span { font-size: .72rem; color: var(--rb-text-3); display: block; }
    .jf-dev-rmv {
        width: 26px;
        height: 26px;
        border: none;
        background: none;
        color: var(--rb-text-3);
        cursor: pointer;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }
    .jf-dev-rmv:hover { background: var(--rb-danger-soft); color: var(--rb-danger); }

    .jf-btn-dashed {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .75rem;
        border: 2px dashed var(--rb-border);
        border-radius: var(--rb-radius-sm);
        background: none;
        color: var(--rb-text-3);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
    }
    .jf-btn-dashed:hover {
        border-color: var(--rb-brand);
        color: var(--rb-brand);
        background: var(--rb-brand-soft);
    }

    /* ── Category Strips & Item Lines ── */
    .jf-cat-strip {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem 0;
        margin-bottom: .5rem;
        border-bottom: 1px solid var(--rb-border);
    }
    .jf-cat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .jf-cat-dot.part { background: #a78bfa; }
    .jf-cat-dot.service { background: #fbbf24; }
    .jf-cat-dot.other { background: #94a3b8; }
    .jf-cat-strip h5 {
        font-size: .82rem;
        font-weight: 700;
        flex: 1;
        margin: 0;
    }
    .jf-cat-strip .jf-cat-total {
        font-size: .82rem;
        font-weight: 700;
        color: var(--rb-brand);
    }

    .jf-cat-search {
        display: flex;
        gap: .5rem;
        margin-bottom: .75rem;
        align-items: center;
    }
    .jf-cat-search .jf-inp-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: 0 .625rem;
        background: var(--rb-bg);
        position: relative;
    }
    .jf-cat-search .jf-inp-wrap:focus-within {
        border-color: var(--rb-brand);
        background: #fff;
    }
    .jf-cat-search .jf-inp-wrap > i {
        color: var(--rb-text-3);
        margin-right: .4rem;
        font-size: .85rem;
    }
    .jf-cat-search .jf-inp-wrap input {
        border: none;
        background: transparent;
        padding: .45rem 0;
        font-size: .82rem;
        flex: 1;
        outline: none;
        color: var(--rb-text);
    }
    .jf-cat-search .jf-device-link {
        flex: 0 0 175px;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: .45rem .65rem;
        font-size: .82rem;
        color: var(--rb-text);
        background: var(--rb-bg);
        outline: none;
        transition: border-color .15s;
        appearance: auto;
    }
    .jf-cat-search .jf-device-link:focus { border-color: var(--rb-brand); background: #fff; }
    .jf-cat-search .jf-add-btn {
        padding: .45rem .85rem;
        background: var(--rb-success);
        color: #fff;
        border: none;
        border-radius: var(--rb-radius-sm);
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .jf-cat-search .jf-add-btn:hover { background: #16a34a; }
    .jf-cat-search .jf-add-btn:disabled { opacity: .5; cursor: not-allowed; }
    .jf-cat-search .jf-create-btn {
        padding: .45rem .65rem;
        border: 1px solid var(--rb-brand);
        border-radius: var(--rb-radius-sm);
        font-size: .86rem;
        background: var(--rb-brand-soft);
        color: var(--rb-brand);
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
        display: inline-flex;
        align-items: center;
    }
    .jf-cat-search .jf-create-btn:hover { background: var(--rb-brand); color: #fff; border-color: var(--rb-brand); }

    .jf-il {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .5rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background .12s;
    }
    .jf-il:last-of-type { border-bottom: none; }
    .jf-il:hover { background: #fafbfd; }

    .jf-il-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .88rem;
    }
    .jf-il-icon.part { background: #eff6ff; color: #3b82f6; }
    .jf-il-icon.service { background: #f0fdf4; color: #22c55e; }
    .jf-il-icon.other { background: #fefce8; color: #eab308; }

    .jf-il-body { flex: 1; min-width: 0; }
    .jf-il-name { font-weight: 600; font-size: .88rem; color: var(--rb-text); }
    .jf-il-meta {
        font-size: .75rem;
        color: var(--rb-text-3);
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        margin-top: .1rem;
    }

    .jf-il-inline-input {
        display: block;
        max-width: 280px;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: .35rem .6rem;
        font-size: .84rem;
        color: var(--rb-text);
        background: var(--rb-bg);
        outline: none;
        transition: border-color .15s;
        margin-bottom: .25rem;
        width: 100%;
    }
    .jf-il-inline-input:focus { border-color: var(--rb-brand); background: #fff; }
    .jf-il-inline-input--sm {
        max-width: 155px;
        font-size: .75rem;
        padding: .2rem .5rem;
        margin-bottom: 0;
    }

    .jf-il-controls {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-shrink: 0;
    }
    .jf-il-controls .qty-input {
        width: 52px;
        text-align: center;
        padding: .3rem .2rem;
        border-radius: 6px;
        border: 1px solid var(--rb-border);
        font-weight: 600;
        font-size: .82rem;
        outline: none;
    }
    .jf-il-controls .qty-input:focus { border-color: var(--rb-brand); }
    .jf-il-controls .price-group {
        display: flex;
        align-items: center;
        border: 1px solid var(--rb-border);
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .jf-il-controls .price-group:focus-within { border-color: var(--rb-brand); }
    .jf-il-controls .price-group .cur {
        padding: .25rem .4rem;
        font-size: .72rem;
        color: var(--rb-text-3);
        background: var(--rb-bg);
        border-right: 1px solid var(--rb-border);
        font-weight: 600;
    }
    .jf-il-controls .price-group input {
        width: 72px;
        text-align: right;
        border: none;
        padding: .3rem .4rem;
        font-weight: 600;
        font-size: .82rem;
        outline: none;
    }

    /* ── Per-item tax override select ── */
    .jf-tax-sel {
        font-size: .72rem;
        border: 1px solid var(--rb-border);
        border-radius: 6px;
        padding: .28rem .5rem .28rem .4rem;
        color: var(--rb-text);
        background: var(--rb-bg);
        outline: none;
        cursor: pointer;
        flex-shrink: 0;
        max-width: 130px;
        min-width: 80px;
        transition: border-color .15s;
    }
    .jf-tax-sel:focus { border-color: var(--rb-brand); background: #fff; }
    .jf-tax-sel.tax-none { color: var(--rb-text-3); font-style: italic; }

    .jf-il-total {
        min-width: 70px;
        text-align: right;
        font-weight: 700;
        font-size: .88rem;
        color: var(--rb-text);
    }
    .jf-il-rm {
        width: 26px;
        height: 26px;
        border: none;
        background: none;
        color: var(--rb-text-3);
        cursor: pointer;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }
    .jf-il-rm:hover { background: var(--rb-danger-soft); color: var(--rb-danger); }

    /* Tax Mode bar — top of Line Items section */
    .jf-tax-mode-bar {
        display: flex;
        align-items: center;
        background: #fefce8;
        border: 1px solid #fde68a;
        border-radius: var(--rb-radius-sm);
        padding: .55rem .85rem;
        margin-bottom: .85rem;
    }
    .jf-tax-mode-inner {
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
        width: 100%;
    }
    .jf-tax-icon { color: #d97706; font-size: .9rem; flex-shrink: 0; }
    .jf-tax-label {
        font-size: .78rem;
        font-weight: 700;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }
    .jf-tax-select {
        border: 1px solid #fcd34d;
        border-radius: var(--rb-radius-sm);
        padding: .3rem .65rem;
        font-size: .82rem;
        color: var(--rb-text);
        background: #fff;
        outline: none;
        min-width: 220px;
        transition: border-color .15s;
        appearance: auto;
    }
    .jf-tax-select:focus { border-color: #d97706; }
    .jf-tax-rate-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .74rem;
        font-weight: 600;
        padding: .2rem .6rem;
        border-radius: 999px;
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
        white-space: nowrap;
    }
    .jf-tax-rate-pill--none {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fca5a5;
    }
    .jf-tax-error { font-size: .74rem; color: var(--rb-danger); }

    .jf-subtotal-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        padding: .5rem .75rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-top: 1px solid var(--rb-border);
        border-radius: 0 0 var(--rb-radius-sm) var(--rb-radius-sm);
        margin-top: .25rem;
    }
    .jf-subtotal-bar .jf-sub-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--rb-text-2);
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .jf-subtotal-bar .jf-sub-amount {
        font-size: .95rem;
        font-weight: 700;
        color: var(--rb-brand);
    }

    .jf-selected-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .82rem;
        font-weight: 600;
        padding: .3rem .75rem;
        border-radius: 999px;
        border: 1px solid;
    }

    /* ── Category separator ── */
    .jf-cat-sep { height: 1rem; }

    /* ── Empty State ── */
    .jf-empty {
        text-align: center;
        padding: 1.5rem 1rem;
        color: var(--rb-text-3);
    }
    .jf-empty i { font-size: 1.5rem; display: block; margin-bottom: .375rem; opacity: .4; }
    .jf-empty p { font-size: .78rem; margin: 0; }
    .jf-empty .jf-empty-sub { font-size: .72rem; opacity: .6; margin-top: .15rem; }

    /* ── Extras Table ── */
    .jf-extras-table { width: 100%; border-collapse: collapse; }
    .jf-extras-table th {
        background: var(--rb-bg);
        font-weight: 600;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--rb-text-2);
        padding: .6rem .75rem;
        border-bottom: 1px solid var(--rb-border);
        text-align: left;
    }
    .jf-extras-table td {
        padding: .6rem .75rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .84rem;
        vertical-align: middle;
    }
    .jf-extras-table tbody tr:hover { background: rgba(14,165,233,.02); }

    /* ── Grand Total Hero (mockup: no extra shadow on hero) ── */
    .jf-gt-hero {
        background: linear-gradient(135deg, var(--rb-brand) 0%, var(--rb-brand-dark) 100%);
        padding: 1.25rem;
        border-radius: var(--rb-radius);
        color: #fff;
        margin-bottom: 1rem;
    }
    .jf-gt-hero .jf-gt-label { font-size: .78rem; opacity: .7; font-weight: 600; }
    .jf-gt-hero .jf-gt-amount { font-size: 1.75rem; font-weight: 800; margin-top: .15rem; }
    .jf-gt-hero .jf-gt-items { font-size: .72rem; opacity: .65; margin-top: .25rem; }

    /* ── Sidebar Cards ── */
    .jf-sc {
        background: var(--rb-card);
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius);
        margin-bottom: .75rem;
        overflow: hidden;
        box-shadow: var(--rb-shadow);
    }
    .jf-sc-head {
        padding: .625rem .875rem;
        font-weight: 700;
        font-size: .85rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        border-bottom: 1px solid var(--rb-border);
    }
    .jf-sc-head i { color: var(--rb-brand); }
    .jf-sc-body { padding: .875rem; }
    .jf-sc-row {
        display: flex;
        justify-content: space-between;
        padding: .25rem 0;
        font-size: .82rem;
    }
    .jf-sc-row .jf-val { font-weight: 600; }

    /* ── Breakdown: category + tax grouping ── */
    .jf-cat-block {
        margin-bottom: .45rem;
    }
    .jf-cat-block:last-of-type { margin-bottom: 0; }
    .jf-cat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .3rem 0 .15rem;
        font-size: .84rem;
        font-weight: 600;
        color: var(--rb-text);
    }
    .jf-cat-row .jf-val { font-weight: 700; }
    .jf-cat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 5px;
        font-size: .68rem;
        margin-right: .35rem;
        flex-shrink: 0;
    }
    .jf-tax-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-left: .75rem;
        padding: .18rem .55rem .18rem .6rem;
        margin-bottom: .05rem;
        background: rgba(245,158,11,.07);
        border-left: 2px solid rgba(245,158,11,.4);
        border-radius: 0 4px 4px 0;
        font-size: .75rem;
        color: #92400e;
    }
    .jf-tax-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-weight: 600;
    }
    .jf-tax-badge i { font-size: .7rem; opacity: .8; }
    .jf-tax-row .jf-tax-amount {
        font-weight: 700;
        color: #b45309;
    }
    .jf-divider {
        border: none;
        border-top: 1px solid var(--rb-border);
        margin: .55rem 0;
    }
    .jf-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .28rem 0;
        font-size: .82rem;
    }
    .jf-summary-row .jf-val { font-weight: 600; }
    .jf-tax-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .28rem .6rem;
        margin: .2rem 0;
        border-radius: 6px;
        background: rgba(245,158,11,.08);
        border: 1px solid rgba(245,158,11,.2);
        font-size: .82rem;
        color: #92400e;
    }
    .jf-tax-total-row .jf-val { font-weight: 700; color: #b45309; }
    .jf-grand-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .4rem .5rem;
        border-radius: 6px;
        background: rgba(var(--rb-brand-rgb, 59,130,246),.07);
        font-size: .9rem;
        font-weight: 800;
    }
    .jf-grand-row .jf-val { color: var(--rb-brand); font-weight: 800; }

    /* ── Sidebar Settings ── */
    .jf-ss { margin-bottom: .625rem; }
    .jf-ss:last-child { margin-bottom: 0; }
    .jf-ss label {
        display: block;
        font-size: .7rem;
        font-weight: 600;
        color: var(--rb-text-2);
        margin-bottom: .2rem;
        text-transform: uppercase;
    }
    .jf-ss select,
    .jf-ss input {
        width: 100%;
        border: 1px solid var(--rb-border);
        border-radius: 6px;
        padding: .4rem .625rem;
        font-size: .82rem;
        outline: none;
        color: var(--rb-text);
    }
    .jf-ss select:focus,
    .jf-ss input:focus { border-color: var(--rb-brand); }

    /* ── Drop zone ── */
    .jf-dropzone {
        border: 2px dashed var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: 1.25rem;
        text-align: center;
        color: var(--rb-text-3);
        font-size: .82rem;
        cursor: pointer;
        transition: border-color .15s,background .15s;
    }
    .jf-dropzone:hover { border-color: var(--rb-brand); background: var(--rb-brand-soft); }
    .jf-dropzone i { font-size: 1.25rem; display: block; margin-bottom: .2rem; opacity: .45; }

    /* ── Modal ── */
    .rb-modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
        z-index: 2000; align-items: center; justify-content: center;
        padding: 1.5rem; animation: fadeInModal .2s ease;
    }
    .rb-modal-container {
        background: #fff; width: 100%; max-width: 550px;
        border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden; position: relative; animation: slideUpModal .3s ease;
    }
    .rb-modal-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: #f8fafc;
    }
    .rb-modal-body { padding: 1.5rem; max-height: 80vh; overflow-y: auto; }
    .rb-modal-footer {
        padding: 1.25rem 1.5rem; border-top: 1px solid #f1f5f9;
        background: #f8fafc; display: flex; justify-content: flex-end; gap: .75rem;
    }
    @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUpModal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .jf-layout {
            flex-direction: column;
            padding: 1rem;
        }
        .jf-side { width: 100%; }
        .jf-side .jf-sticky { position: static; }
        .jf-row { flex-direction: column; }
        .jf-c2, .jf-c3 { flex: 1 1 100%; }
        .jf-dates-grid { grid-template-columns: 1fr; }
        .jf-cat-search { flex-wrap: wrap; }
        .jf-cat-search .jf-device-link { width: 100%; }
        .jf-il { flex-wrap: wrap; }
        .jf-il-controls { width: 100%; justify-content: space-between; }
    }
    @media (max-width: 575.98px) {
        .jf-top-bar { padding: .75rem 1rem; flex-wrap: wrap; gap: .5rem; }
        .jf-top-bar .jf-left h1 { font-size: 1rem; }
    }
</style>
@endpush

@php
    $isEstimate = ($formMode ?? 'job') === 'estimate';
    $isEditing  = $isEstimate ? !empty($estimateId) : !empty($jobId);
    $backUrl    = $isEstimate
        ? route('tenant.estimates.index', ['business' => $tenant->slug])
        : route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=jobs';
    $entityLabel = $isEstimate ? __('Estimate') : __('Job');
@endphp

<div class="container-fluid px-0 jf-page">
    @livewire('tenant.operations.quick-customer-modal', ['tenant' => $tenant])
    @livewire('tenant.operations.quick-technician-modal', ['tenant' => $tenant])
    @livewire('tenant.operations.quick-part-modal', ['tenant' => $tenant])
    @livewire('tenant.operations.quick-service-modal', ['tenant' => $tenant])

    <form id="job-form" wire:submit.prevent="save" enctype="multipart/form-data">
    <div x-data="{
        sections: { details: true, devices: true, items: true, notes: false }
    }">

    {{-- ════════ STICKY TOP BAR ════════ --}}
    <header class="jf-top-bar">
        <div class="jf-top-bar-inner">
            <div class="jf-left">
                {{-- Back button --}}
                <a href="{{ $backUrl }}"
                   class="jf-back-btn" title="{{ __('Back to :entity', ['entity' => $isEstimate ? __('Estimates') : __('Jobs')]) }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                {{-- Title + breadcrumb --}}
                <div class="jf-title-block">
                    <span class="jf-mode-badge {{ $isEditing ? 'mode-edit' : 'mode-create' }}">
                        <i class="bi {{ $isEditing ? 'bi-pencil' : 'bi-plus-lg' }}"></i>
                        {{ $isEditing ? __('Edit') : __('New') }}
                    </span>
                    <h1 class="jf-page-title">
                        <i class="bi {{ $isEstimate ? 'bi-file-earmark-text' : 'bi-tools' }}"></i>
                        @if ($isEstimate)
                            {{ $isEditing ? __('Edit Estimate') : __('Create New Estimate') }}
                        @else
                            {{ $isEditing ? __('Edit Job') : __('Create New Job') }}
                        @endif
                    </h1>
                    <ol class="jf-breadcrumb">
                        <li><a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) }}">{{ __('Dashboard') }}</a></li>
                        <li><i class="bi bi-chevron-right jf-bc-sep"></i></li>
                        @if ($isEstimate)
                            <li><a href="{{ route('tenant.estimates.index', ['business' => $tenant->slug]) }}">{{ __('Estimates') }}</a></li>
                        @else
                            <li><a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=jobs' }}">{{ __('Jobs') }}</a></li>
                        @endif
                        <li><i class="bi bi-chevron-right jf-bc-sep"></i></li>
                        <li><span class="jf-bc-current">
                            @if ($isEstimate)
                                {{ $isEditing ? __('Edit') : __('New Estimate') }}
                            @else
                                {{ $isEditing ? __('Edit') : __('New Repair Job') }}
                            @endif
                        </span></li>
                    </ol>
                </div>
            </div>
            <div class="jf-right">
                <a href="{{ $backUrl }}" class="jf-btn jf-btn-cancel">
                    <i class="bi bi-x-lg"></i> {{ __('Cancel') }}
                </a>
                @if ($isEditing)
                    @if ($isEstimate)
                        <a href="{{ route('tenant.estimates.print', ['business' => $tenant->slug, 'estimateId' => $estimateId]) }}"
                           target="_blank"
                           class="jf-btn jf-btn-cancel"
                           title="{{ __('Print / Preview Estimate') }}">
                            <i class="bi bi-printer"></i> {{ __('Print') }}
                        </a>
                        <a href="{{ route('tenant.estimates.pdf', ['business' => $tenant->slug, 'estimateId' => $estimateId]) }}"
                           target="_blank"
                           class="jf-btn jf-btn-cancel"
                           title="{{ __('Download Estimate PDF') }}">
                            <i class="bi bi-file-earmark-pdf"></i> {{ __('PDF') }}
                        </a>
                    @else
                        <a href="{{ route('tenant.jobs.print', ['business' => $tenant->slug, 'jobId' => $jobId]) }}"
                           target="_blank"
                           class="jf-btn jf-btn-cancel"
                           title="{{ __('Print / Preview Work Order') }}">
                            <i class="bi bi-printer"></i> {{ __('Print') }}
                        </a>
                        <a href="{{ route('tenant.jobs.pdf', ['business' => $tenant->slug, 'jobId' => $jobId]) }}"
                           target="_blank"
                           class="jf-btn jf-btn-cancel"
                           title="{{ __('Download Work Order PDF') }}">
                            <i class="bi bi-file-earmark-pdf"></i> {{ __('PDF') }}
                        </a>
                    @endif
                @endif
                <button type="submit" class="jf-btn jf-btn-save" wire:loading.attr="disabled" wire:target="save">
                    <i class="bi bi-check-lg" wire:loading.remove wire:target="save"></i>
                    <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    <span wire:loading.remove wire:target="save">
                        @if ($isEstimate)
                            {{ $isEditing ? __('Update Estimate') : __('Create Estimate') }}
                        @else
                            {{ $isEditing ? __('Update Job') : __('Create Job') }}
                        @endif
                    </span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </button>
            </div>
        </div>
    </header>

    <div class="jf-layout">

        {{-- ════════════════════════════════════
             MAIN COLUMN
             ════════════════════════════════════ --}}
        <div class="jf-main">

            {{-- ── Section 1: Details ── --}}
            <div class="jf-section">
                <div class="jf-section-head" @click="sections.details = !sections.details">
                    <div class="jf-section-badge" style="background:var(--rb-brand-soft);color:var(--rb-brand)">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3>{{ $isEstimate ? __('Estimate Details') : __('Job Details') }}</h3>
                    <span class="jf-tag" style="background:var(--rb-success-soft);color:#16a34a;">{{ __('Required') }}</span>
                    <i class="bi bi-chevron-down jf-chevron" :style="sections.details ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="jf-section-body" x-show="sections.details" x-collapse>
                    {{-- Case Number & Title --}}
                    <div class="jf-row">
                        <div class="jf-c2">
                            <div class="jf-fg">
                                <label>{{ __('Case Number') }}</label>
                                <input type="text" class="form-control" wire:model.defer="case_number" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                <div class="jf-hint">{{ __('Auto-generated if left empty') }}</div>
                                @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="jf-c2">
                            <div class="jf-fg">
                                <label>{{ $isEstimate ? __('Estimate Title') : __('Job Title') }}</label>
                                <input type="text" class="form-control" wire:model.defer="title" placeholder="{{ __('e.g., iPhone 14 Screen Repair') }}" />
                                @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Customer Selection --}}
                    <div class="jf-fg">
                        <label><i class="bi bi-person"></i> {{ __('Customer') }} <span class="text-danger">*</span></label>
                        @if($this->selected_customer)
                            <div class="jf-selected-box">
                                <div>
                                    <div class="jf-item-title fw-semibold">{{ $this->selected_customer->name }}</div>
                                    <div class="jf-item-meta" style="font-size:.75rem;color:var(--rb-text-3);">{{ $this->selected_customer->email }} | {{ $this->selected_customer->phone }}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" wire:click="$set('customer_id', null)">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        @else
                            <div class="jf-search-container" x-data="{ open: false }" @click.away="open = false">
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           placeholder="{{ __('Search by name, email or phone...') }}"
                                           wire:model.live.debounce.300ms="customer_search"
                                           autocomplete="off"
                                           @focus="open = true"
                                           @input="open = true"
                                           @keydown.escape="open = false" />
                                    <div wire:loading wire:target="customer_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                    <button type="button" class="btn btn-gradient" title="{{ __('Quick Add Customer') }}" wire:click="$dispatch('openQuickCustomerModal')">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="jf-search-dropdown" x-show="open" x-cloak>
                                    <div wire:loading wire:target="customer_search" class="p-3 text-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span class="text-muted small">{{ __('Searching customers...') }}</span>
                                    </div>
                                    <div wire:loading.remove wire:target="customer_search">
                                        @forelse($this->filtered_customers as $c)
                                            <div class="jf-search-item" wire:key="cust-res-{{ $c->id }}" wire:click="selectCustomer({{ $c->id }})" @click="open = false">
                                                <div>
                                                    <div class="jf-item-title">{{ $c->name }}</div>
                                                    <div class="jf-item-meta">{{ $c->email }} | {{ $c->phone }}</div>
                                                </div>
                                                <i class="bi bi-plus text-primary"></i>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">{{ __('No customers found') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Technician Selection --}}
                    <div class="jf-fg">
                        <label><i class="bi bi-people"></i> {{ __('Assigned Technicians') }}</label>
                        <div class="jf-search-container" x-data="{ open: false }" @click.away="open = false">
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       placeholder="{{ __('Search technician...') }}"
                                       wire:model.live.debounce.300ms="technician_search"
                                       autocomplete="off"
                                       @focus="open = true"
                                       @input="open = true"
                                       @keydown.escape="open = false" />
                                <div wire:loading wire:target="technician_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                <button type="button" class="btn btn-gradient" title="{{ __('Quick Add Technician') }}" wire:click="$dispatch('openQuickTechnicianModal')">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="jf-search-dropdown" x-show="open" x-cloak>
                                <div wire:loading wire:target="technician_search" class="p-3 text-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span class="text-muted small">{{ __('Searching technicians...') }}</span>
                                </div>
                                <div wire:loading.remove wire:target="technician_search">
                                    @forelse($this->filtered_technicians as $t)
                                        <div class="jf-search-item" wire:key="tech-res-{{ $t->id }}" wire:click="selectTechnician({{ $t->id }})" @click="open = false">
                                            <div>
                                                <div class="jf-item-title">{{ $t->name }}</div>
                                                <div class="jf-item-meta">{{ $t->email }}</div>
                                            </div>
                                            <i class="bi bi-plus text-primary"></i>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted small">{{ __('No technicians found') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="jf-chips">
                            @foreach($this->selected_technicians as $st)
                                <span class="jf-chip" wire:key="selected-tech-{{ $st->id }}">
                                    {{ $st->name }}
                                    <button type="button" class="btn-remove-chip" wire:click="removeTechnician({{ $st->id }})">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        @error('technician_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Schedule Dates --}}
                    <div class="jf-fg">
                        <label><i class="bi bi-calendar3"></i> {{ __('Schedule Dates') }}</label>
                        <div class="jf-dates-grid" @if($isEstimate) style="grid-template-columns:repeat(2,1fr)" @endif>
                            <div>
                                <label class="form-label small text-muted mb-1">{{ __('Pickup Date') }}</label>
                                <input type="date" class="form-control" wire:model.defer="pickup_date" />
                                @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">{{ __('Delivery Date') }}</label>
                                <input type="date" class="form-control" wire:model.defer="delivery_date" />
                                @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            @if(! $isEstimate)
                            <div>
                                <label class="form-label small text-muted mb-1">{{ __('Next Service') }}</label>
                                <input type="date" class="form-control" wire:model.defer="next_service_date" />
                                @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="jf-fg">
                        <label>{{ __('Description') }}</label>
                        <textarea class="form-control" rows="3" wire:model.defer="case_detail" placeholder="{{ __('Describe the repair issue, customer notes, or any relevant details...') }}"></textarea>
                        @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Devices ── --}}
            <div class="jf-section">
                <div class="jf-section-head" @click="sections.devices = !sections.devices">
                    <div class="jf-section-badge" style="background:#dbeafe;color:#2563eb">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h3>{{ __('Devices') }}</h3>
                    <span class="jf-tag" style="background:#dbeafe;color:#1e40af;">{{ count($deviceRows) }}</span>
                    <i class="bi bi-chevron-down jf-chevron" :style="sections.devices ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="jf-section-body" x-show="sections.devices" x-collapse>
                    {{-- Add Device Form --}}
                    <div class="jf-dev-form-card">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.875rem;">
                            <div style="width:24px;height:24px;border-radius:6px;background:var(--rb-brand-soft);color:var(--rb-brand);display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;">
                                <i class="{{ $editingDeviceIndex !== null ? 'bi bi-pencil' : 'bi bi-plus-lg' }}"></i>
                            </div>
                            <span style="font-size:.82rem;font-weight:700;color:var(--rb-text);">
                                {{ $editingDeviceIndex !== null ? __('Edit Device') : __('Add Device') }}
                            </span>
                        </div>

                        {{-- Row 1: Device search + IMEI + PIN --}}
                        <div class="jf-dates-grid">
                            {{-- Device Search --}}
                            <div class="jf-fg" style="margin-bottom:0;">
                                <label><i class="bi bi-search"></i> {{ __('Device') }}</label>
                                <div class="position-relative" x-data="{ open: false, search: @entangle('device_search').live }" @click.away="open = false">
                                    <div class="input-group">
                                        <span class="input-group-text" style="background:#fff;border:1px solid var(--rb-border);border-right:none;border-radius:var(--rb-radius-sm) 0 0 var(--rb-radius-sm);color:var(--rb-text-3);font-size:.82rem;">
                                            <i class="bi bi-phone"></i>
                                        </span>
                                        <input type="text"
                                               class="form-control"
                                               style="border-left:none;border-radius:0 var(--rb-radius-sm) var(--rb-radius-sm) 0;"
                                               placeholder="{{ __('Search model...') }}"
                                               x-model="search"
                                               @focus="open = true"
                                               @input="open = true">
                                        @if($selected_device_id)
                                            <button type="button"
                                                    class="btn"
                                                    style="border:1px solid var(--rb-border);border-left:none;border-radius:0 var(--rb-radius-sm) var(--rb-radius-sm) 0;background:#fff;color:var(--rb-text-3);padding:.3rem .6rem;"
                                                    wire:click="$set('selected_device_id', null); $set('selected_device_name', '')">
                                                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($selected_device_name)
                                        <div class="mt-1">
                                            <span style="display:inline-flex;align-items:center;gap:.35rem;background:var(--rb-brand-soft);color:var(--rb-brand-dark);border:1px solid #bae6fd;padding:.2rem .65rem;border-radius:999px;font-size:.75rem;font-weight:600;">
                                                @if($selected_device_image)
                                                    <img src="{{ $selected_device_image }}" style="width:16px;height:16px;object-fit:cover;border-radius:50%;">
                                                @else
                                                    <i class="bi bi-check-circle-fill"></i>
                                                @endif
                                                {{ $selected_device_name }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="jf-search-dropdown" :class="{ 'd-block': open && search.length >= 2 }" x-show="open && search.length >= 2" x-cloak>
                                        <div wire:loading wire:target="device_search" class="p-3 text-center">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                            <span class="text-muted small">{{ __('Searching...') }}</span>
                                        </div>
                                        <div wire:loading.remove wire:target="device_search">
                                            @forelse($this->filteredDevices as $brandName => $groupDevices)
                                                <div style="padding:.4rem .75rem .2rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--rb-brand);background:var(--rb-bg);position:sticky;top:0;">{{ $brandName }}</div>
                                                @foreach($groupDevices as $device)
                                                    <div class="jf-search-item"
                                                         wire:click="selectDevice({{ $device->id }}, '{{ $brandName }} {{ $device->model }}')"
                                                         @click="open = false">
                                                        <div style="display:flex;align-items:center;gap:.5rem;">
                                                            <div style="width:28px;height:28px;border-radius:6px;border:1px solid var(--rb-border);overflow:hidden;background:var(--rb-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                                @if($device->image_url)
                                                                    <img src="{{ $device->image_url }}" style="object-fit:cover;width:100%;height:100%;">
                                                                @else
                                                                    <i class="bi bi-phone" style="font-size:.75rem;color:var(--rb-text-3);"></i>
                                                                @endif
                                                            </div>
                                                            <span class="jf-item-title">{{ $device->model }}</span>
                                                        </div>
                                                        <i class="bi bi-plus" style="color:var(--rb-brand);"></i>
                                                    </div>
                                                @endforeach
                                            @empty
                                                <div class="p-3 text-center text-muted small">{{ __('No matching devices found') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                @error('selected_device_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            {{-- IMEI / Serial --}}
                            <div class="jf-fg" style="margin-bottom:0;">
                                <label><i class="bi bi-upc-scan"></i> {{ __('ID / IMEI') }}</label>
                                <input type="text" class="form-control" wire:model.defer="device_serial" placeholder="{{ __('Enter Device ID/IMEI') }}">
                                @error('device_serial')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            {{-- PIN --}}
                            @if($enablePinCodeField)
                                <div class="jf-fg" style="margin-bottom:0;">
                                    <label><i class="bi bi-lock"></i> {{ __('Pincode / Password') }}</label>
                                    <input type="text" class="form-control" wire:model.defer="device_pin" placeholder="{{ __('e.g. 1234') }}">
                                </div>
                            @else
                                <div></div>{{-- keep grid symmetry --}}
                            @endif
                        </div>

                        {{-- Additional custom fields --}}
                        @if(count($fieldDefinitions) > 0)
                            <div class="jf-dates-grid" style="margin-top:.75rem;">
                                @foreach($fieldDefinitions as $def)
                                    <div class="jf-fg" style="margin-bottom:0;">
                                        <label>{{ __($def->label) }}</label>
                                        <input type="text" class="form-control" wire:model.defer="additional_fields.{{ $def->key }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Device Note --}}
                        <div class="jf-fg" style="margin-top:.75rem;margin-bottom:.875rem;">
                            <label><i class="bi bi-chat-text"></i> {{ __('Device Note') }}</label>
                            <textarea class="form-control" wire:model.defer="device_note" rows="2" placeholder="{{ __('Pre-existing damage, specific issues, etc.') }}"></textarea>
                        </div>

                        {{-- Action buttons --}}
                        <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                            @if($editingDeviceIndex !== null)
                                <button type="button" class="jf-btn jf-btn-cancel" wire:click="cancelEditDevice">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="button" class="jf-btn jf-btn-save" wire:click="addDeviceToTable">
                                    <i class="bi bi-check-lg"></i> {{ __('Update Device') }}
                                </button>
                            @else
                                <button type="button" class="jf-btn jf-btn-save" wire:click="addDeviceToTable">
                                    <i class="bi bi-plus-lg"></i> {{ $isEstimate ? __('Add to Estimate') : __('Add to Job') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Device List --}}
                    @forelse($deviceRows as $idx => $row)
                        <div class="jf-dev-row" wire:key="dev-row-{{ $idx }}">
                            <div class="jf-dev-icon">
                                @if(!empty($row['image_url']))
                                    <img src="{{ $row['image_url'] }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                                @else
                                    <i class="bi bi-phone"></i>
                                @endif
                            </div>
                            <div class="jf-dev-info">
                                <strong>{{ $row['brand_name'] }} {{ $row['device_model'] }}</strong>
                                <span>
                                    @if($row['serial'])IMEI: {{ $row['serial'] }}@endif
                                    @if($enablePinCodeField && $row['pin']) · PIN: {{ $row['pin'] }}@endif
                                    @if($row['notes']) · {{ Str::limit($row['notes'], 50) }}@endif
                                    @if(!empty($row['additional_fields']))
                                        @foreach($fieldDefinitions as $def)
                                            @if(!empty($row['additional_fields'][$def->key]))
                                                · {{ $def->label }}: {{ $row['additional_fields'][$def->key] }}
                                            @endif
                                        @endforeach
                                    @endif
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" wire:click="editDevice({{ $idx }})" title="{{ __('Edit') }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="jf-dev-rmv" wire:click="removeDevice({{ $idx }})" title="{{ __('Remove') }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    @empty
                        <div class="jf-empty">
                            <i class="bi bi-phone"></i>
                            <p>{{ __('No devices added yet') }}</p>
                            <div class="jf-empty-sub">{{ __('Use the form above to add at least one device.') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ── Section 3: Line Items (all categories visible) ── --}}
            <div class="jf-section">
                <div class="jf-section-head" @click="sections.items = !sections.items">
                    <div class="jf-section-badge" style="background:#ede9fe;color:#6d28d9">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h3>{{ __('Line Items') }}</h3>
                    @php
                        $partsItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'part');
                        $servicesItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'service');
                        $otherItems = array_filter($items, fn($r) => !in_array($r['type'] ?? '', ['part', 'service']));

                        $partsSubtotal = 0;
                        foreach ($partsItems as $r) { $partsSubtotal += (($r['unit_price_cents'] ?? 0) * ($r['qty'] ?? 1)); }
                        $servicesSubtotal = 0;
                        foreach ($servicesItems as $r) { $servicesSubtotal += (($r['unit_price_cents'] ?? 0) * ($r['qty'] ?? 1)); }
                        $otherSubtotal = 0;
                        foreach ($otherItems as $r) { $otherSubtotal += (($r['unit_price_cents'] ?? 0) * ($r['qty'] ?? 1)); }

                        $itemsCount = count($partsItems) + count($servicesItems) + count($otherItems);
                        $itemsTotal = $partsSubtotal + $servicesSubtotal + $otherSubtotal;
                    @endphp
                    <span class="jf-tag" style="background:#ede9fe;color:#6d28d9;">
                        {{ $itemsCount }} {{ __('items') }} · {{ Number::currency($itemsTotal, $currency_code) }}
                    </span>
                    <i class="bi bi-chevron-down jf-chevron" :style="sections.items ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="jf-section-body" x-show="sections.items" x-collapse>

                    {{-- ═══ Tax Mode (select before adding items) ═══ --}}
                    @if($tax_enabled)
                        <div class="jf-tax-mode-bar">
                            <div class="jf-tax-mode-inner">
                                <i class="bi bi-percent jf-tax-icon"></i>
                                <span class="jf-tax-label">{{ __('Tax Mode') }}</span>
                                <select class="jf-tax-select" wire:model.live="prices_inclu_exclu">
                                    <option value="">{{ __('Select tax mode...') }}</option>
                                    <option value="inclusive">{{ __('Inclusive — tax included in price') }}</option>
                                    <option value="exclusive">{{ __('Exclusive — tax added on top') }}</option>
                                </select>
                                @if($this->default_tax_info)
                                    <span class="jf-tax-rate-pill">
                                        <i class="bi bi-check-circle-fill"></i>
                                        {{ $this->default_tax_info['name'] }} &mdash; {{ number_format($this->default_tax_info['rate'], 2) }}%
                                    </span>
                                @else
                                    <span class="jf-tax-rate-pill jf-tax-rate-pill--none">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ __('No default tax rate configured') }}
                                    </span>
                                @endif
                                @error('prices_inclu_exclu')<span class="jf-tax-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endif

                    {{-- ═══ PARTS ═══ --}}
                    <div class="jf-cat-strip">
                        <div class="jf-cat-dot part"></div>
                        <h5>{{ __('Parts') }}</h5>
                        <span class="jf-cat-total">{{ Number::currency($partsSubtotal, $currency_code) }}</span>
                    </div>
                    <div class="jf-cat-search" x-data="{ open: false, search: @entangle('part_search').live }" @click.away="open = false">
                        <div class="jf-inp-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="{{ __('Search part by name or code...') }}"
                                   x-model="search"
                                   @focus="open = true"
                                   @input="open = true"
                                   @keydown.escape="open = false" />
                            {{-- Part Dropdown --}}
                            <div class="jf-search-dropdown" x-show="open && search.length >= 2" x-cloak style="z-index:1060;top:100%;left:0;right:0;position:absolute;">
                                <div wire:loading wire:target="part_search" class="p-3 text-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    <span class="text-muted small">{{ __('Searching...') }}</span>
                                </div>
                                <div wire:loading.remove wire:target="part_search">
                                    @forelse($this->filteredParts as $part)
                                        <div class="jf-search-item" wire:click="selectPart({{ $part->id }}, '{{ addslashes($part->name) }}')" @click="open = false">
                                            <div>
                                                <div class="jf-item-title">{{ $part->name }}</div>
                                                <div class="jf-item-meta">{{ $part->manufacturing_code ?: $part->sku ?: '--' }}</div>
                                            </div>
                                            <span class="fw-bold text-primary small">{{ Number::currency(($part->price_amount_cents ?? 0) / 100, $currency_code) }}</span>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted small">
                                            {{ __('No parts found.') }}
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1" wire:click="addCustomPart" @click="open = false">{{ __('Add custom') }}</button>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <select class="jf-device-link" wire:model.defer="selected_device_link_index">
                            <option value="">{{ __('Link to device...') }}</option>
                            @foreach($deviceRows as $idx => $row)
                                <option value="{{ $idx }}">{{ $row['brand_name'] }} {{ $row['device_model'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="jf-add-btn" wire:click="addPart" {{ !$selected_part_id ? 'disabled' : '' }}>
                            <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                        </button>
                        <button type="button" class="jf-create-btn" title="{{ __('Create New Part') }}" wire:click="$dispatch('openQuickPartModal')">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    @if($selected_part_id)
                        <div style="margin-bottom:.5rem;">
                            <span class="jf-selected-pill" style="background:#eff6ff;color:#3b82f6;border-color:#bfdbfe;">
                                <i class="bi bi-check-circle-fill"></i> {{ $selected_part_name }}
                                <button type="button" class="btn btn-sm p-0 ms-1 text-primary" wire:click="$set('selected_part_id', null)"><i class="bi bi-x-circle"></i></button>
                            </span>
                        </div>
                    @endif

                    @forelse ($partsItems as $i => $row)
                        <div class="jf-il" wire:key="item-line-{{ $i }}">
                            <div class="jf-il-icon part"><i class="bi bi-cpu"></i></div>
                            <div class="jf-il-body">
                                @if(empty($row['part_id']))
                                    <input type="text" class="form-control form-control-sm mb-1" style="max-width:280px;" wire:model.defer="items.{{ $i }}.name" placeholder="{{ __('Part Name') }}" />
                                    <input type="text" class="form-control form-control-sm" style="max-width:180px;" wire:model.defer="items.{{ $i }}.code" placeholder="{{ __('Code') }}" />
                                @else
                                    <div class="jf-il-name">{{ $row['name'] }}</div>
                                    <div class="jf-il-meta">
                                        @if(!empty($row['code']))<span><i class="bi bi-upc-scan me-1"></i>{{ $row['code'] }}</span>@endif
                                        @if(!empty($row['device_info']))<span><i class="bi bi-phone me-1"></i>{{ $row['device_info'] }}</span>@endif
                                    </div>
                                @endif
                            </div>
                            <div class="jf-il-controls">
                                <input type="number" min="1" class="qty-input" wire:model.live="items.{{ $i }}.qty" />
                                <div class="price-group">
                                    <span class="cur">{{ $currency_symbol }}</span>
                                    <input type="number" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                </div>
                                @if($tax_enabled)
                                <select class="jf-tax-sel{{ empty($row['tax_id']) ? ' tax-none' : '' }}" wire:model.defer="items.{{ $i }}.tax_id" title="{{ __('Tax override') }}">
                                    <option value="">{{ __('No tax') }}</option>
                                    @foreach($availableTaxes as $tax)
                                        <option value="{{ $tax['id'] }}">{{ $tax['label'] }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                            <div class="jf-il-total">{{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}</div>
                            <button type="button" class="jf-il-rm" wire:click="removeItem({{ $i }})" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
                        </div>
                    @empty
                        <div class="jf-empty" style="padding:.75rem;">
                            <i class="bi bi-cpu"></i>
                            <p>{{ __('No parts added yet') }}</p>
                        </div>
                    @endforelse

                    @if(count($partsItems) > 0)
                        <div class="jf-subtotal-bar">
                            <span class="jf-sub-label">{{ __('Parts Subtotal') }}</span>
                            <span class="jf-sub-amount">{{ Number::currency($partsSubtotal, $currency_code) }}</span>
                        </div>
                    @endif

                    <div class="jf-cat-sep"></div>

                    {{-- ═══ SERVICES ═══ --}}
                    <div class="jf-cat-strip">
                        <div class="jf-cat-dot service"></div>
                        <h5>{{ __('Services') }}</h5>
                        <span class="jf-cat-total">{{ Number::currency($servicesSubtotal, $currency_code) }}</span>
                    </div>
                    <div class="jf-cat-search" x-data="{ open: false, search: @entangle('service_search').live }" @click.away="open = false">
                        <div class="jf-inp-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="{{ __('Search service by name or code...') }}"
                                   x-model="search"
                                   @focus="open = true"
                                   @input="open = true"
                                   @keydown.escape="open = false" />
                            <div class="jf-search-dropdown" x-show="open && search.length >= 2" x-cloak style="z-index:1060;top:100%;left:0;right:0;position:absolute;">
                                <div wire:loading wire:target="service_search" class="p-3 text-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    <span class="text-muted small">{{ __('Searching...') }}</span>
                                </div>
                                <div wire:loading.remove wire:target="service_search">
                                    @forelse($this->filteredServices as $service)
                                        <div class="jf-search-item" wire:click="selectService({{ $service->id }}, '{{ addslashes($service->name) }}')" @click="open = false">
                                            <div>
                                                <div class="jf-item-title">{{ $service->name }}</div>
                                                <div class="jf-item-meta">{{ $service->service_code ?: '--' }}</div>
                                            </div>
                                            <span class="fw-bold text-primary small">{{ Number::currency(($service->base_price_amount_cents ?? 0) / 100, $currency_code) }}</span>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted small">
                                            {{ __('No services found.') }}
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1" wire:click="addService" @click="open = false">{{ __('Add custom') }}</button>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <select class="jf-device-link" wire:model.defer="selected_device_link_index">
                            <option value="">{{ __('Link to device...') }}</option>
                            @foreach($deviceRows as $idx => $device)
                                <option value="{{ $idx }}">{{ $device['brand_name'] }} {{ $device['device_model'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="jf-add-btn" wire:click="addService" {{ !$selected_service_id && strlen($service_search) < 2 ? 'disabled' : '' }}>
                            <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                        </button>
                        <button type="button" class="jf-create-btn" title="{{ __('Create New Service') }}" wire:click="$dispatch('openQuickServiceModal')">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    @if($selected_service_id)
                        <div style="margin-bottom:.5rem;">
                            <span class="jf-selected-pill" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0;">
                                <i class="bi bi-check-circle-fill"></i> {{ $selected_service_name }}
                                <button type="button" class="btn btn-sm p-0 ms-1 text-success" wire:click="$set('selected_service_id', null)"><i class="bi bi-x-circle"></i></button>
                            </span>
                        </div>
                    @endif

                    @forelse ($servicesItems as $i => $row)
                        <div class="jf-il" wire:key="item-line-{{ $i }}">
                            <div class="jf-il-icon service"><i class="bi bi-wrench-adjustable-circle"></i></div>
                            <div class="jf-il-body">
                                <div class="jf-il-name">{{ $row['name'] ?? '--' }}</div>
                                <div class="jf-il-meta">
                                    @if(!empty($row['code']))<span><i class="bi bi-hash me-1"></i>{{ $row['code'] }}</span>@endif
                                    @if(!empty($row['device_info']))<span><i class="bi bi-phone me-1"></i>{{ $row['device_info'] }}</span>@endif
                                </div>
                            </div>
                            <div class="jf-il-controls">
                                <input type="number" min="1" class="qty-input" wire:model.live="items.{{ $i }}.qty" />
                                <div class="price-group">
                                    <span class="cur">{{ $currency_symbol }}</span>
                                    <input type="number" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                </div>
                                @if($tax_enabled)
                                <select class="jf-tax-sel{{ empty($row['tax_id']) ? ' tax-none' : '' }}" wire:model.defer="items.{{ $i }}.tax_id" title="{{ __('Tax override') }}">
                                    <option value="">{{ __('No tax') }}</option>
                                    @foreach($availableTaxes as $tax)
                                        <option value="{{ $tax['id'] }}">{{ $tax['label'] }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                            <div class="jf-il-total">{{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}</div>
                            <button type="button" class="jf-il-rm" wire:click="removeItem({{ $i }})" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
                        </div>
                    @empty
                        <div class="jf-empty" style="padding:.75rem;">
                            <i class="bi bi-wrench-adjustable-circle"></i>
                            <p>{{ __('No services added yet') }}</p>
                        </div>
                    @endforelse

                    @if(count($servicesItems) > 0)
                        <div class="jf-subtotal-bar">
                            <span class="jf-sub-label">{{ __('Services Subtotal') }}</span>
                            <span class="jf-sub-amount">{{ Number::currency($servicesSubtotal, $currency_code) }}</span>
                        </div>
                    @endif

                    <div class="jf-cat-sep"></div>

                    {{-- ═══ OTHER / EXTRAS ═══ --}}
                    <div class="jf-cat-strip">
                        <div class="jf-cat-dot other"></div>
                        <h5>{{ __('Other / Extras') }}</h5>
                        <span class="jf-cat-total">{{ Number::currency($otherSubtotal, $currency_code) }}</span>
                    </div>
                    <div class="jf-cat-search">
                        <div class="jf-inp-wrap" style="opacity:.5;pointer-events:none;">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="{{ __('Custom items — click Add') }}" disabled />
                        </div>
                        <select class="jf-device-link" wire:model.defer="selected_device_link_index">
                            <option value="">{{ __('Link to device...') }}</option>
                            @foreach($deviceRows as $idx => $device)
                                <option value="{{ $idx }}">{{ $device['brand_name'] }} {{ $device['device_model'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="jf-add-btn" wire:click="addOtherItem">
                            <i class="bi bi-plus-lg"></i> {{ __('Add Item') }}
                        </button>
                    </div>

                    @forelse ($otherItems as $i => $row)
                        <div class="jf-il" wire:key="item-line-{{ $i }}">
                            <div class="jf-il-icon other"><i class="bi bi-receipt"></i></div>
                            <div class="jf-il-body">
                                <input type="text" class="jf-il-inline-input" wire:model.defer="items.{{ $i }}.name" placeholder="{{ __('Item name...') }}" />
                                <div class="jf-il-meta">
                                    <span>
                                        <input type="text" class="jf-il-inline-input jf-il-inline-input--sm" wire:model.defer="items.{{ $i }}.code" placeholder="{{ __('Code (optional)') }}" />
                                    </span>
                                    @if(!empty($row['device_info']))<span><i class="bi bi-phone me-1"></i>{{ $row['device_info'] }}</span>@endif
                                </div>
                            </div>
                            <div class="jf-il-controls">
                                <input type="number" min="1" class="qty-input" wire:model.live="items.{{ $i }}.qty" />
                                <div class="price-group">
                                    <span class="cur">{{ $currency_symbol }}</span>
                                    <input type="number" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                </div>
                                @if($tax_enabled)
                                <select class="jf-tax-sel{{ empty($row['tax_id']) ? ' tax-none' : '' }}" wire:model.defer="items.{{ $i }}.tax_id" title="{{ __('Tax override') }}">
                                    <option value="">{{ __('No tax') }}</option>
                                    @foreach($availableTaxes as $tax)
                                        <option value="{{ $tax['id'] }}">{{ $tax['label'] }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                            <div class="jf-il-total">{{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}</div>
                            <button type="button" class="jf-il-rm" wire:click="removeItem({{ $i }})" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
                        </div>
                    @empty
                        <div class="jf-empty" style="padding:.75rem;">
                            <i class="bi bi-receipt"></i>
                            <p>{{ __('No other items added yet') }}</p>
                        </div>
                    @endforelse

                    @if(count($otherItems) > 0)
                        <div class="jf-subtotal-bar">
                            <span class="jf-sub-label">{{ __('Other Subtotal') }}</span>
                            <span class="jf-sub-amount">{{ Number::currency($otherSubtotal, $currency_code) }}</span>
                        </div>
                    @endif

                </div>
            </div>

            {{-- ── Section 4: Notes & Attachments ── --}}
            @if(! $isEstimate)
            <div class="jf-section">
                <div class="jf-section-head" @click="sections.notes = !sections.notes">
                    <div class="jf-section-badge" style="background:#fef3c7;color:#92400e">
                        <i class="bi bi-paperclip"></i>
                    </div>
                    <h3>{{ __('Notes & Attachments') }}</h3>
                    <i class="bi bi-chevron-down jf-chevron" :style="sections.notes ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="jf-section-body" x-show="sections.notes" x-collapse>
                    {{-- Job Notes --}}
                    <div class="jf-fg">
                        <label>{{ __('Job Notes (Internal / Customer)') }}</label>
                        <textarea class="form-control" rows="3" wire:model.defer="wc_order_note" placeholder="{{ __('Special instructions or notes...') }}"></textarea>
                        @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- File Attachment --}}
                    <div class="jf-fg">
                        <label><i class="bi bi-upload"></i> {{ __('File Attachment') }}</label>
                        <input type="file" class="form-control" wire:model="job_file" />
                        <div wire:loading wire:target="job_file" class="mt-1 small text-primary">
                            <div class="spinner-border spinner-border-sm me-1"></div> {{ __('Uploading...') }}
                        </div>
                        @if($job_file)
                            <div class="mt-1 small text-success"><i class="bi bi-paperclip me-1"></i>{{ __('File ready to save') }}</div>
                        @endif
                        @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Job Extras --}}
                    <div style="margin-top:1rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
                            <label style="font-size:.85rem;font-weight:700;margin:0;"><i class="bi bi-paperclip text-primary me-1"></i>{{ __('Job Extras & Attachments') }}</label>
                            <button type="button" class="btn btn-outline-success btn-sm" wire:click="openExtraModal()">
                                <i class="bi bi-plus-circle me-1"></i>{{ __('Add Extra') }}
                            </button>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="jf-extras-table">
                                <thead>
                                    <tr>
                                        <th style="width:120px">{{ __('Date') }}</th>
                                        <th style="width:180px">{{ __('Label') }}</th>
                                        <th>{{ __('Data / Description') }}</th>
                                        <th style="width:100px" class="text-center">{{ __('Visibility') }}</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($extras as $i => $row)
                                        <tr>
                                            <td>{{ $row['occurred_at'] ?: '--' }}</td>
                                            <td class="fw-bold" style="color:var(--rb-brand);">{{ $row['label'] }}</td>
                                            <td>
                                                <div>{{ $row['data_text'] ?: '--' }}</div>
                                                @if(!empty($row['description']))
                                                    <div class="small text-muted">{{ $row['description'] }}</div>
                                                @endif
                                                @if(!empty($extra_item_files[$i]))
                                                    <i class="bi bi-file-earmark-check text-success" title="{{ __('File attached') }}"></i>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ ($row['visibility'] ?? 'public') === 'public' ? 'bg-light text-success border border-success' : 'bg-light text-muted border' }}">
                                                    {{ __(ucfirst($row['visibility'] ?? 'public')) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-primary btn-sm border-0 p-1" wire:click="openExtraModal({{ $i }})"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="btn btn-outline-danger btn-sm border-0 p-1" wire:click="removeExtra({{ $i }})"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted small">
                                                <i class="bi bi-paperclip" style="font-size:1.25rem;opacity:.3;display:block;margin-bottom:.2rem;"></i>
                                                {{ __('No extras added yet') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Estimate-mode: simpler Notes section --}}
            @if($isEstimate)
            <div class="jf-section">
                <div class="jf-section-head" @click="sections.notes = !sections.notes">
                    <div class="jf-section-badge" style="background:#fef3c7;color:#92400e">
                        <i class="bi bi-paperclip"></i>
                    </div>
                    <h3>{{ __('Notes') }}</h3>
                    <i class="bi bi-chevron-down jf-chevron" :style="sections.notes ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="jf-section-body" x-show="sections.notes" x-collapse>
                    <div class="jf-fg">
                        <label>{{ __('Internal Notes (Staff Only)') }}</label>
                        <textarea class="form-control" rows="3" wire:model.defer="wc_order_note" placeholder="{{ __('Special instructions or internal notes...') }}"></textarea>
                        @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ════════════════════════════════════
             SIDEBAR
             ════════════════════════════════════ --}}
        <div class="jf-side">
            <div class="jf-sticky">
                {{-- Grand Total Hero --}}
                <div class="jf-gt-hero">
                    <div class="jf-gt-label">{{ $isEstimate ? __('Estimate Total') : __('Estimated Total') }}</div>
                    <div class="jf-gt-amount">{{ Number::currency($this->grand_total_amount, $currency_code) }}</div>
                    <div class="jf-gt-items">{{ count($items) }} {{ __('items') }} · {{ count($deviceRows) }} {{ __('devices') }}</div>
                </div>

                {{-- Breakdown --}}
                @php
                    $taxInfo    = $this->default_tax_info;
                    $rateStr    = $taxInfo
                        ? rtrim(rtrim(number_format((float)($taxInfo['rate'] ?? $this->default_tax_rate), 2), '0'), '.')
                        : ($this->default_tax_rate > 0 ? rtrim(rtrim(number_format((float)$this->default_tax_rate, 2), '0'), '.') : null);
                    $taxName    = $taxInfo['name'] ?? null;
                    $taxModeTag = ($this->prices_inclu_exclu ?? 'exclusive') === 'inclusive' ? __('incl.') : __('excl.');
                    $isInclusive = ($this->prices_inclu_exclu ?? 'exclusive') === 'inclusive';
                    $totalTax   = $this->parts_tax + $this->services_tax + $this->extras_tax;
                @endphp
                <div class="jf-sc">
                    <div class="jf-sc-head"><i class="bi bi-receipt"></i> {{ __('Cost Breakdown') }}</div>
                    <div class="jf-sc-body">

                        {{-- Parts block --}}
                        @if($this->parts_total > 0)
                        <div class="jf-cat-block">
                            <div class="jf-cat-row">
                                <span>
                                    <span class="jf-cat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="bi bi-box-seam"></i></span>
                                    {{ __('Parts') }}
                                    <span style="font-weight:400;color:var(--rb-text-muted);font-size:.77rem;">({{ count($partsItems) }})</span>
                                </span>
                                <span class="jf-val">{{ Number::currency($this->parts_total, $currency_code) }}</span>
                            </div>
                            @if($this->tax_enabled && $this->parts_tax > 0)
                            <div class="jf-tax-row">
                                <span class="jf-tax-badge"><i class="bi bi-percent"></i> {{ __('Tax') }}@if($rateStr) <span style="opacity:.75;">{{ $rateStr }}%</span>@endif</span>
                                <span class="jf-tax-amount">{{ Number::currency($this->parts_tax, $currency_code) }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Services block --}}
                        @if($this->services_total > 0)
                        <div class="jf-cat-block">
                            <div class="jf-cat-row">
                                <span>
                                    <span class="jf-cat-icon" style="background:rgba(16,185,129,.1);color:#10b981;"><i class="bi bi-wrench-adjustable"></i></span>
                                    {{ __('Services') }}
                                    <span style="font-weight:400;color:var(--rb-text-muted);font-size:.77rem;">({{ count($servicesItems) }})</span>
                                </span>
                                <span class="jf-val">{{ Number::currency($this->services_total, $currency_code) }}</span>
                            </div>
                            @if($this->tax_enabled && $this->services_tax > 0)
                            <div class="jf-tax-row">
                                <span class="jf-tax-badge"><i class="bi bi-percent"></i> {{ __('Tax') }}@if($rateStr) <span style="opacity:.75;">{{ $rateStr }}%</span>@endif</span>
                                <span class="jf-tax-amount">{{ Number::currency($this->services_tax, $currency_code) }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Fees / Extras block --}}
                        @if($this->extras_total > 0)
                        <div class="jf-cat-block">
                            <div class="jf-cat-row">
                                <span>
                                    <span class="jf-cat-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6;"><i class="bi bi-tags"></i></span>
                                    {{ __('Fees / Extras') }}
                                    <span style="font-weight:400;color:var(--rb-text-muted);font-size:.77rem;">({{ count($otherItems) }})</span>
                                </span>
                                <span class="jf-val">{{ Number::currency($this->extras_total, $currency_code) }}</span>
                            </div>
                            @if($this->tax_enabled && $this->extras_tax > 0)
                            <div class="jf-tax-row">
                                <span class="jf-tax-badge"><i class="bi bi-percent"></i> {{ __('Tax') }}@if($rateStr) <span style="opacity:.75;">{{ $rateStr }}%</span>@endif</span>
                                <span class="jf-tax-amount">{{ Number::currency($this->extras_tax, $currency_code) }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        <hr class="jf-divider">

                        {{-- Subtotal --}}
                        <div class="jf-summary-row">
                            <span style="color:var(--rb-text-muted);">{{ __('Subtotal') }}</span>
                            <span class="jf-val">{{ Number::currency($this->parts_total + $this->services_total + $this->extras_total, $currency_code) }}</span>
                        </div>

                        {{-- Tax total summary row --}}
                        @if($this->tax_enabled && $totalTax > 0)
                        <div class="jf-tax-total-row">
                            <span>
                                <i class="bi bi-percent" style="margin-right:.3rem;"></i>
                                @if($taxName){{ $taxName }} &middot; @endif
                                @if($rateStr){{ $rateStr }}% &middot; @endif
                                <span style="font-size:.74rem;opacity:.8;">{{ $taxModeTag }}</span>
                            </span>
                            <span class="jf-val">{{ $isInclusive ? '' : '+' }} {{ Number::currency($totalTax, $currency_code) }}</span>
                        </div>
                        @endif

                        <hr class="jf-divider">

                        {{-- Grand Total --}}
                        <div class="jf-grand-row">
                            <span>{{ __('Grand Total') }}</span>
                            <span class="jf-val">{{ Number::currency($this->grand_total_amount, $currency_code) }}</span>
                        </div>

                        {{-- Balance --}}
                        @if($this->balance > 0)
                        <div class="jf-summary-row" style="margin-top:.3rem;">
                            <span style="color:var(--rb-text-muted);">{{ __('Balance Due') }}</span>
                            <span class="jf-val" style="color:var(--rb-danger);">{{ Number::currency($this->balance, $currency_code) }}</span>
                        </div>
                        @endif

                    </div>
                </div>

                {{-- Settings --}}
                @if($isEstimate)
                <div class="jf-sc">
                    <div class="jf-sc-head"><i class="bi bi-gear"></i> {{ __('Estimate Settings') }}</div>
                    <div class="jf-sc-body">
                        <div class="jf-ss">
                            <label>{{ __('Status') }}</label>
                            <select wire:model.defer="estimate_status">
                                <option value="draft">{{ __('Draft') }}</option>
                                <option value="sent">{{ __('Sent') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                                <option value="expired">{{ __('Expired') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                {{-- Job Settings --}}
                <div class="jf-sc">
                    <div class="jf-sc-head"><i class="bi bi-gear"></i> {{ __('Job Settings') }}</div>
                    <div class="jf-sc-body">
                        <div class="jf-ss">
                            <label>{{ __('Status') }}</label>
                            <select wire:model.defer="status_slug">
                                <option value="">{{ __('Select Status...') }}</option>
                                @foreach ($jobStatuses ?? [] as $st)
                                    <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                @endforeach
                            </select>
                            @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="jf-ss">
                            <label>{{ __('Payment Status') }}</label>
                            <select wire:model.defer="payment_status_slug">
                                <option value="">{{ __('Select Status...') }}</option>
                                @foreach ($paymentStatuses ?? [] as $st)
                                    <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                @endforeach
                            </select>
                            @error('payment_status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="jf-ss">
                            <label>{{ __('Priority') }}</label>
                            <select wire:model.defer="priority">
                                <option value="normal">{{ __('Normal') }}</option>
                                <option value="high">{{ __('High') }}</option>
                                <option value="urgent">{{ __('Urgent') }}</option>
                            </select>
                            @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                @endif
                @if($this->selected_customer)
                    <div class="jf-sc">
                        <div class="jf-sc-head"><i class="bi bi-person-circle"></i> {{ __('Customer') }}</div>
                        <div class="jf-sc-body" style="display:flex;align-items:center;gap:.75rem;">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--rb-brand-soft);color:var(--rb-brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                {{ strtoupper(substr($this->selected_customer->name ?? '', 0, 1)) }}{{ strtoupper(substr(explode(' ', $this->selected_customer->name ?? '')[1] ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.88rem;">{{ $this->selected_customer->name }}</div>
                                <div style="font-size:.72rem;color:var(--rb-text-3);">{{ $this->selected_customer->email }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Assigned Technicians --}}
                @if(count($this->selected_technicians) > 0)
                    <div class="jf-sc">
                        <div class="jf-sc-head"><i class="bi bi-people"></i> {{ __('Technicians') }}</div>
                        <div class="jf-sc-body">
                            <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
                                @foreach($this->selected_technicians as $tech)
                                    <span style="display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:.2rem .55rem;border-radius:6px;font-size:.75rem;font-weight:500;">
                                        <i class="bi bi-person" style="font-size:.7rem;"></i> {{ $tech->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Submit (mobile fallback) --}}
                <div class="d-grid" style="margin-top:.5rem;">
                    <button type="submit" class="jf-btn jf-btn-save" style="justify-content:center;padding:.75rem;">
                        <i class="bi bi-check-lg"></i>
                        @if ($isEstimate)
                            {{ $isEditing ? __('Update Estimate') : __('Create Estimate') }}
                        @else
                            {{ $isEditing ? __('Update Job') : __('Create Job') }}
                        @endif
                    </button>
                </div>
            </div>
        </div>

    </div>
    </form>

    {{-- ══════ Job Extra Modal (only for jobs) ══════ --}}
    @if(! $isEstimate)
    <div x-data="{ show: @entangle('showExtraModal').live }" 
         x-show="show" 
         x-bind:style="show ? 'display: flex !important' : ''"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak 
         class="rb-modal-backdrop" 
         @keydown.escape.window="show = false">
        <div class="rb-modal-container" @click.away="show = false">
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold">{{ $editingExtraIndex !== null ? __('Edit Job Extra') : __('Add Job Extra') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>
            <div class="rb-modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Field Label / Name') }}</label>
                        <input type="text" class="form-control" wire:model.defer="extra_label" placeholder="{{ __('e.g. Purchase Receipt, Box Status...') }}">
                        @error('extra_label')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">{{ __('Occurrence Date') }}</label>
                        <input type="date" class="form-control" wire:model.defer="extra_occurred_at">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">{{ __('Visibility') }}</label>
                        <select class="form-select" wire:model.defer="extra_visibility">
                            <option value="public">{{ __('Public (Customer can see)') }}</option>
                            <option value="private">{{ __('Private (Internal only)') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Data / Short Note') }}</label>
                        <input type="text" class="form-control" wire:model.defer="extra_data_text" placeholder="{{ __('Value or summary data') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Extended Description') }}</label>
                        <textarea class="form-control" rows="2" wire:model.defer="extra_description" placeholder="{{ __('Optional details...') }}"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Attachment / File') }}</label>
                        <div class="p-3 border rounded bg-light">
                            <input type="file" class="form-control form-control-sm" wire:model="extra_temp_file">
                            <div wire:loading wire:target="extra_temp_file" class="mt-2 small text-primary">
                                <div class="spinner-border spinner-border-sm me-1"></div> {{ __('Uploading...') }}
                            </div>
                            @if($extra_temp_file)
                                <div class="mt-2 small text-success"><i class="bi bi-file-earmark-check me-1"></i> {{ __('File ready to save') }}</div>
                            @endif
                            @error('extra_temp_file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="rb-modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4" @click="show = false">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary px-4" wire:click="saveExtra" wire:loading.attr="disabled">
                    <i class="bi bi-check-circle me-1"></i> {{ __('Save Extra') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    </div>
</div>

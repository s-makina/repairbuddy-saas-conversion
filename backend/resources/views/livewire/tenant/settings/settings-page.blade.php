{{-- Settings Page — Alpine + Livewire --}}
@push('page-styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Settings Page — Matches Job Form Design System
   Alpine.js for UI state, Livewire for data/persistence
   ═══════════════════════════════════════════════════════════ */

:root {
    --st-brand: #0ea5e9;
    --st-brand-soft: #e0f2fe;
    --st-brand-dark: #0284c7;
    --st-success: #22c55e;
    --st-success-soft: #dcfce7;
    --st-danger: #ef4444;
    --st-danger-soft: #fef2f2;
    --st-warning: #f59e0b;
    --st-warning-soft: #fef3c7;
    --st-bg: #f8fafc;
    --st-card: #ffffff;
    --st-border: #e2e8f0;
    --st-border-h: #cbd5e1;
    --st-text: #0f172a;
    --st-text-2: #475569;
    --st-text-3: #94a3b8;
    --st-radius: 12px;
    --st-radius-sm: 8px;
    --st-shadow: 0 1px 3px rgba(0,0,0,.06);
    --st-shadow-md: 0 4px 12px rgba(0,0,0,.07);
}

[x-cloak] { display: none !important; }

/* ── Page Container ── */
.st-page {
    background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
    min-height: 100vh;
    margin: -1rem -1rem 0 -1rem;
    padding: 0;
    width: calc(100% + 2rem);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    line-height: 1.5;
    color: var(--st-text);
}

/* ── Top Bar ── */
.st-top-bar {
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--st-border);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 var(--st-border), 0 2px 8px rgba(14,165,233,.04);
}
.st-top-bar-inner {
    max-width: 1440px;
    margin: 0 auto;
    padding: .65rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.st-top-bar-inner .st-left { display: flex; align-items: center; gap: 1rem; }
.st-back-btn {
    width: 34px; height: 34px;
    border-radius: 10px;
    border: 1px solid var(--st-border);
    background: #fff;
    color: var(--st-text-2);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; flex-shrink: 0; font-size: .88rem;
    transition: all .15s;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.st-back-btn:hover { background: var(--st-bg); color: var(--st-brand); border-color: var(--st-brand); }
.st-title-block { line-height: 1.2; }
.st-page-title { font-size: 1.05rem; font-weight: 700; color: var(--st-text); margin: 0; }
.st-page-subtitle { font-size: .78rem; color: var(--st-text-3); margin: 0; }

/* ── Layout: Sidebar + Content ── */
.st-layout {
    max-width: 1440px;
    margin: 0 auto;
    display: flex;
    gap: 0;
    min-height: calc(100vh - 60px);
}

/* ── Sidebar ── */
.st-sidebar {
    width: 240px;
    flex-shrink: 0;
    background: #063e70;
    border-right: none;
    padding: 0 0 1.5rem 0;
    overflow-y: auto;
    position: sticky;
    top: 53px;
    height: calc(100vh - 53px);
}
/* Sidebar logo / brand bar at top */
.st-sidebar-top {
    background: rgba(0,0,0,.2);
    padding: .85rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
    margin-bottom: .5rem;
}
.st-sidebar-brand {
    font-size: .78rem;
    font-weight: 700;
    color: rgba(255,255,255,.6);
    text-transform: uppercase;
    letter-spacing: .08em;
}
.st-sidebar-group { margin-bottom: .25rem; }
.st-sidebar-group-label {
    padding: .6rem 1.25rem .25rem;
    font-size: .6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.38);
    margin-bottom: 0;
}
.st-sidebar-items { list-style: none; margin: 0; padding: 0; }
.st-sidebar-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .48rem 1.25rem;
    font-size: .81rem;
    font-weight: 500;
    color: rgba(255,255,255,.72);
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    border-left: 3px solid transparent;
    user-select: none;
}
.st-sidebar-item:hover {
    background: rgba(255,255,255,.08);
    color: #fff;
}
.st-sidebar-item.active {
    background: rgba(255,255,255,.13);
    color: #fff;
    border-left-color: #fd6742;
    font-weight: 600;
}
.st-sidebar-item .st-nav-icon {
    width: 16px; height: 16px;
    flex-shrink: 0;
    color: rgba(255,255,255,.55);
}
.st-sidebar-item:hover .st-nav-icon { color: rgba(255,255,255,.85); }
.st-sidebar-item.active .st-nav-icon { color: #fd6742; }
.st-sidebar-item .st-nav-badge {
    margin-left: auto;
    font-size: .6rem;
    font-weight: 600;
    padding: .1rem .4rem;
    border-radius: 999px;
    background: rgba(253,103,66,.25);
    color: #fda98b;
}

/* ── Content Area ── */
.st-content {
    flex: 1;
    min-width: 0;
    padding: 1.5rem 2rem 3rem;
}

/* ── Flash / Toast ── */
.st-flash {
    display: flex; align-items: center; gap: .6rem;
    padding: .65rem 1rem;
    border-radius: var(--st-radius-sm);
    font-size: .82rem; font-weight: 500;
    margin-bottom: 1.25rem;
    animation: stFlashIn .3s ease;
}
@keyframes stFlashIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
.st-flash-success { background: var(--st-success-soft); color: #15803d; border: 1px solid #bbf7d0; }
.st-flash-error { background: var(--st-danger-soft); color: #dc2626; border: 1px solid #fecaca; }
.st-flash-dismiss {
    margin-left: auto; background: none; border: none; cursor: pointer;
    color: inherit; opacity: .6; font-size: 1rem; padding: 0;
}
.st-flash-dismiss:hover { opacity: 1; }

/* ── Section Card ── */
.st-section {
    background: var(--st-card);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius);
    box-shadow: var(--st-shadow);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.st-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.25rem;
    cursor: pointer; user-select: none;
    transition: background .15s;
}
.st-section-header:hover { background: rgba(14,165,233,.02); }
.st-section-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .88rem; font-weight: 700; color: var(--st-text);
    margin: 0;
}
.st-section-title .st-sec-icon { width: 18px; height: 18px; color: var(--st-brand); flex-shrink: 0; }
.st-section-chevron {
    width: 18px; height: 18px; color: var(--st-text-3);
    transition: transform .2s;
    flex-shrink: 0;
}
.st-section-chevron.open { transform: rotate(180deg); }
.st-section-body { padding: 0 1.25rem 1.25rem; }

/* ── Form Group (matches job form .jf-fg) ── */
.st-fg { margin-bottom: 1rem; }
.st-fg > label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--st-text-2);
    margin-bottom: .35rem;
}
.st-fg > .st-help {
    font-size: .7rem;
    color: var(--st-text-3);
    margin-top: .2rem;
}
.st-fg .form-control,
.st-fg input[type="text"],
.st-fg input[type="email"],
.st-fg input[type="url"],
.st-fg input[type="number"],
.st-fg input[type="password"],
.st-fg input[type="tel"],
.st-fg select,
.st-fg textarea {
    width: 100%;
    padding: .55rem .75rem;
    font-size: .84rem;
    color: var(--st-text);
    background: #fff;
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius-sm);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
    line-height: 1.5;
    box-sizing: border-box;
}
.st-fg input:focus,
.st-fg select:focus,
.st-fg textarea:focus {
    border-color: var(--st-brand);
    box-shadow: 0 0 0 3px rgba(14,165,233,.12);
}
.st-fg input::placeholder,
.st-fg textarea::placeholder {
    color: var(--st-text-3);
}
.st-fg textarea { resize: vertical; min-height: 80px; }
.st-fg select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' viewBox='0 0 12 12'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .75rem center;
    padding-right: 2rem;
}

/* ── Form Grid ── */
.st-grid { display: grid; gap: 1rem; }
.st-grid-2 { grid-template-columns: 1fr 1fr; }
.st-grid-3 { grid-template-columns: 1fr 1fr 1fr; }

/* ── Toggle Switch (matches job form style) ── */
.st-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .65rem;
    cursor: pointer;
    user-select: none;
}
.st-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.st-toggle-track {
    width: 40px; height: 22px;
    background: #cbd5e1;
    border-radius: 999px;
    position: relative;
    transition: background .2s;
    flex-shrink: 0;
}
.st-toggle input:checked + .st-toggle-track { background: var(--st-brand); }
.st-toggle-track::after {
    content: '';
    position: absolute;
    top: 2px; left: 2px;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    transition: transform .2s;
}
.st-toggle input:checked + .st-toggle-track::after { transform: translateX(18px); }
.st-toggle-label {
    font-size: .82rem; font-weight: 500; color: var(--st-text);
}
.st-toggle-description {
    font-size: .72rem; color: var(--st-text-3); margin-top: .15rem;
}

/* ── Toggle Card (option with toggle + label + description) ── */
.st-option-card {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .75rem 0;
    border-bottom: 1px solid var(--st-border);
}
.st-option-card:last-child { border-bottom: none; }
.st-option-card .st-option-control { flex-shrink: 0; padding-top: .1rem; }
.st-option-card .st-option-body { flex: 1; min-width: 0; }
.st-option-card .st-option-title {
    font-size: .84rem; font-weight: 600; color: var(--st-text);
    cursor: pointer;
}
.st-option-card .st-option-desc {
    font-size: .72rem; color: var(--st-text-3); margin-top: .15rem;
}

/* ── Save Button ── */
.st-save-bar {
    display: flex; align-items: center; gap: .75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--st-border);
    margin-top: 1rem;
}
.st-btn-save {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.25rem;
    font-size: .82rem; font-weight: 600;
    color: #fff;
    background: var(--st-brand);
    border: none;
    border-radius: var(--st-radius-sm);
    cursor: pointer;
    transition: all .15s;
    box-shadow: 0 1px 3px rgba(14,165,233,.2);
}
.st-btn-save:hover { background: var(--st-brand-dark); box-shadow: 0 2px 8px rgba(14,165,233,.3); }
.st-btn-save:disabled { opacity: .6; cursor: not-allowed; }
.st-btn-save .st-spinner {
    display: inline-block; width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: stSpin .6s linear infinite;
}
@keyframes stSpin { to { transform: rotate(360deg); } }

/* ── Placeholder Card ── */
.st-placeholder {
    text-align: center;
    padding: 3rem 1.5rem;
}
.st-placeholder-icon {
    width: 48px; height: 48px;
    color: var(--st-text-3);
    margin: 0 auto .75rem;
    opacity: .5;
}
.st-placeholder-title {
    font-size: 1rem; font-weight: 700;
    color: var(--st-text);
    margin-bottom: .35rem;
}
.st-placeholder-text {
    font-size: .82rem; color: var(--st-text-3);
    max-width: 400px;
    margin: 0 auto;
}

/* ── Dashboard Nav Cards ── */
.st-dash-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--st-text);
    margin: 0 0 1.25rem;
}
.st-dash-nav-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 2rem;
}
.st-dash-nav-card {
    width: 115px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid var(--st-border);
    overflow: hidden;
    text-decoration: none;
    display: block;
    transition: box-shadow .15s, transform .12s;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.st-dash-nav-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    transform: translateY(-2px);
    text-decoration: none;
}
.st-dash-nav-card-img {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px 10px 12px;
    background: #fafafa;
}
.st-dash-nav-card-img img {
    max-width: 52px;
    max-height: 52px;
    object-fit: contain;
}
.st-dash-nav-card-label {
    margin: 0;
    padding: 7px 6px;
    text-align: center;
    font-size: .72rem;
    font-weight: 600;
    background: #fd6742;
    color: #fff;
    line-height: 1.3;
    transition: background .15s;
}
.st-dash-nav-card:hover .st-dash-nav-card-label {
    background: #063e70;
}

/* ── Dashboard Stats ── */
.st-dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.st-dash-stat-card {
    background: #fff;
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius);
    padding: 1rem 1.25rem;
    box-shadow: var(--st-shadow);
}
.st-dash-stat-label {
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--st-text-3);
    margin-bottom: .3rem;
}
.st-dash-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #063e70;
    line-height: 1;
}
.st-dash-stat-sub {
    font-size: .7rem;
    color: var(--st-text-3);
    margin-top: .25rem;
}

/* ── Job / Estimate Summary Widgets (mirrors original wcrb_widget style) ── */
.st-widget-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    column-gap: 14px;
    row-gap: 0;
    margin-bottom: 1.1rem;
}
.st-widget {
    background: #fff;
    border-radius: 0;
    border: none;
    margin-top: 7px;
    margin-bottom: 7px;
    box-shadow: 0 1px 15px 1px rgb(52 40 104 / 8%);
}
.st-widget > a {
    text-decoration: none;
    display: block;
    color: inherit;
}
.st-widget > a:hover .st-widget-body {
    background-color: #ededed;
}
.st-widget-body {
    padding: 11px;
    position: relative;
    overflow: hidden;
}
.st-widget-media {
    display: flex;
    align-items: flex-start;
}
.st-widget-icon {
    flex-shrink: 0;
    margin-left: 1.1rem;
    margin-right: 1.1rem;
    align-self: center;
}
.st-widget-icon img {
    height: 4rem;
    width: auto;
}
.st-widget-info {
    flex: 1;
    min-width: 0;
    align-self: center;
}
.st-widget-title {
    color: #2c304d;
    font-size: 1.2rem;
    line-height: 1.5;
    font-weight: 600;
    margin: 0;
}
.st-widget-number {
    font-size: 1rem;
    line-height: 1.5;
    margin: 0;
    color: var(--st-text-2);
}

/* ── Error Display ── */
.st-field-error {
    font-size: .7rem;
    color: var(--st-danger);
    margin-top: .2rem;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
    .st-sidebar { width: 210px; }
    .st-content { padding: 1.25rem 1.25rem 2rem; }
}
@media (max-width: 768px) {
    .st-layout { flex-direction: column; }
    .st-sidebar {
        width: 100%; position: static; height: auto;
        border-right: none; border-bottom: 2px solid rgba(255,255,255,.12);
        padding: .5rem 0;
    }
    .st-sidebar-top { display: none; }
    .st-sidebar-group { display: flex; flex-wrap: wrap; gap: 0; margin-bottom: .5rem; }
    .st-sidebar-group-label { width: 100%; padding: .4rem 1rem .15rem; margin-bottom: 0; }
    .st-sidebar-items { display: flex; flex-wrap: wrap; gap: .25rem; padding: 0 .75rem; }
    .st-sidebar-item {
        border-left: none; border-bottom: 2px solid transparent;
        border-radius: var(--st-radius-sm);
        padding: .35rem .65rem; font-size: .75rem;
    }
    .st-sidebar-item.active {
        border-left-color: transparent;
        border-bottom-color: #fd6742;
        background: rgba(255,255,255,.15);
    }
    .st-grid-2, .st-grid-3 { grid-template-columns: 1fr; }
    .st-top-bar-inner { padding: .5rem 1rem; }
}
</style>
@endpush

<div class="st-page"
     x-data="{
        activeSection: @entangle('activeSection'),
        flash: @entangle('flashMessage'),
        flashType: @entangle('flashType'),
        mobileMenuOpen: false
     }"
     x-cloak>

    {{-- ═══ Top Bar ═══ --}}
    <div class="st-top-bar">
        <div class="st-top-bar-inner">
            <div class="st-left">
                <a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) }}"
                   class="st-back-btn" title="Back to Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div class="st-title-block">
                    <h1 class="st-page-title">Settings</h1>
                    <p class="st-page-subtitle">Manage your business configuration</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Layout ═══ --}}
    <div class="st-layout">

        {{-- ─── Sidebar ─── --}}
        <aside class="st-sidebar">
            <div class="st-sidebar-top">
                <div class="st-sidebar-brand">RepairBuddy</div>
            </div>
            @foreach ($this->sectionGroups as $groupKey => $group)
                <div class="st-sidebar-group">
                    @if ($group['label'])
                        <div class="st-sidebar-group-label">{{ $group['label'] }}</div>
                    @endif
                    <ul class="st-sidebar-items">
                        @foreach ($group['sections'] as $sectionKey => $section)
                            <li class="st-sidebar-item {{ $activeSection === $sectionKey ? 'active' : '' }}"
                                wire:click="switchSection('{{ $sectionKey }}')"
                                @click="mobileMenuOpen = false"
                                x-on:click="window.history.replaceState(null, '', '?section={{ $sectionKey }}')">
                                @include('livewire.tenant.settings.partials.nav-icon', ['icon' => $section['icon']])
                                <span>{{ $section['label'] }}</span>
                                @if ($section['component'] === null)
                                    <span class="st-nav-badge">Soon</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </aside>

        {{-- ─── Content ─── --}}
        <main class="st-content">

            {{-- Flash message --}}
            <template x-if="flash && flash.length > 0">
                <div class="st-flash"
                     :class="flashType === 'success' ? 'st-flash-success' : 'st-flash-error'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="flash"></span>
                    <button class="st-flash-dismiss" @click="flash = ''">&times;</button>
                </div>
            </template>

            {{-- Section content --}}
            @foreach ($sections as $sectionKey => $section)
                <div x-show="activeSection === '{{ $sectionKey }}'" x-cloak>
                    @if ($section['component'])
                        @livewire($section['component'], ['tenant' => $tenant], key('section-' . $sectionKey))
                    @else
                        @livewire('tenant.settings.section-placeholder', [
                            'tenant' => $tenant,
                            'sectionKey' => $sectionKey,
                            'sectionLabel' => $section['label'],
                        ], key('placeholder-' . $sectionKey))
                    @endif
                </div>
            @endforeach

        </main>
    </div>
</div>

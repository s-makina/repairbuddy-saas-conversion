@extends('tenant.layouts.public')

@section('title', 'My Portal — ' . ($tenant->name ?? 'RepairBuddy'))

@push('page-styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Customer Portal — Settings V2 Design System
   Sidebar + Content layout, Alpine.js for instant switching
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
    --st-info: #6366f1;
    --st-info-soft: #eef2ff;
    --st-accent: #fd6742;
    --st-accent-soft: #fff1ed;
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
.st-top-bar-inner .st-right { display: flex; align-items: center; gap: .75rem; }
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

/* Book Repair button */
.st-btn-book {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .38rem .85rem;
    font-size: .76rem; font-weight: 600;
    color: #fff;
    background: var(--st-accent);
    border: none; border-radius: var(--st-radius-sm);
    text-decoration: none;
    transition: all .15s;
    box-shadow: 0 1px 3px rgba(253,103,66,.2);
}
.st-btn-book:hover { background: #e55d3a; color: #fff; text-decoration: none; }

/* User avatar pill */
.st-user-pill {
    display: flex; align-items: center; gap: .4rem;
    padding: .25rem .5rem .25rem .25rem;
    background: none; border: 1px solid var(--st-border);
    border-radius: 999px; font-family: inherit; cursor: default;
    font-size: .78rem; font-weight: 500; color: var(--st-text-2);
}
.st-user-avatar {
    width: 26px; height: 26px;
    display: flex; align-items: center; justify-content: center;
    background: #063e70; color: #fff;
    border-radius: 50%; font-size: .65rem; font-weight: 700;
}

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
    font-size: .9rem;
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
    border-bottom: 1px solid var(--st-border);
}
.st-section-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .88rem; font-weight: 700; color: var(--st-text);
    margin: 0;
}
.st-section-title .st-sec-icon { color: var(--st-brand); flex-shrink: 0; }
.st-section-body { padding: 1.25rem; }

/* ── Dashboard Section Title ── */
.st-dash-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--st-text);
    margin: 0 0 1.25rem;
}

/* ── Stats Grid ── */
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
.st-dash-stat-value { font-size: 1.5rem; font-weight: 700; color: #063e70; line-height: 1; }
.st-dash-stat-sub { font-size: .7rem; color: var(--st-text-3); margin-top: .25rem; }

/* ── Dashboard Nav Cards ── */
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
    cursor: pointer;
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

/* ── Job / Estimate Summary Widgets ── */
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
.st-widget-inner {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}
.st-widget-inner:hover .st-widget-body {
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

/* ── Table ── */
.st-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.st-table thead th {
    padding: .55rem .7rem; font-size: .65rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--st-text-3); border-bottom: 2px solid var(--st-border);
    text-align: left; white-space: nowrap;
}
.st-table tbody td {
    padding: .6rem .7rem; border-bottom: 1px solid var(--st-border);
    color: var(--st-text); vertical-align: middle;
}
.st-table tbody tr:last-child td { border-bottom: none; }
.st-table tbody tr:hover { background: rgba(14,165,233,.02); }

/* ── Badges ── */
.st-badge {
    display: inline-flex; padding: .12rem .5rem;
    font-size: .65rem; font-weight: 600; border-radius: 999px; white-space: nowrap;
}
.st-badge-success { background: var(--st-success-soft); color: #15803d; }
.st-badge-warning { background: var(--st-warning-soft); color: #b45309; }
.st-badge-danger  { background: var(--st-danger-soft); color: #dc2626; }
.st-badge-info    { background: var(--st-info-soft); color: #4338ca; }
.st-badge-default { background: #f1f5f9; color: var(--st-text-2); }
.st-badge-primary { background: var(--st-brand-soft); color: var(--st-brand-dark); }

/* ── Empty State ── */
.st-placeholder { text-align: center; padding: 2.5rem 1.5rem; }
.st-placeholder-icon { font-size: 2.2rem; color: var(--st-text-3); opacity: .4; margin-bottom: .6rem; }
.st-placeholder-title { font-size: .95rem; font-weight: 700; color: var(--st-text); margin-bottom: .2rem; }
.st-placeholder-text { font-size: .82rem; color: var(--st-text-3); max-width: 340px; margin: 0 auto; }

/* ── Form ── */
.st-fg { margin-bottom: 1rem; }
.st-fg > label {
    display: block; font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--st-text-2); margin-bottom: .3rem;
}
.st-fg input[type="text"],
.st-fg input[type="email"],
.st-fg input[type="tel"],
.st-fg input[type="url"],
.st-fg input[type="number"],
.st-fg input[type="password"],
.st-fg select,
.st-fg textarea {
    width: 100%; padding: .5rem .7rem; font-size: .84rem;
    color: var(--st-text); background: #fff;
    border: 1px solid var(--st-border); border-radius: var(--st-radius-sm);
    outline: none; transition: border-color .15s, box-shadow .15s;
    font-family: inherit; line-height: 1.5; box-sizing: border-box;
}
.st-fg input:focus, .st-fg select:focus, .st-fg textarea:focus {
    border-color: var(--st-brand); box-shadow: 0 0 0 3px rgba(14,165,233,.12);
}
.st-fg input::placeholder { color: var(--st-text-3); }
.st-field-error { font-size: .7rem; color: var(--st-danger); margin-top: .15rem; }

.st-grid   { display: grid; gap: 1rem; }
.st-grid-2 { grid-template-columns: 1fr 1fr; }

/* ── Save Button ── */
.st-save-bar {
    display: flex; align-items: center; gap: .75rem;
    padding-top: .85rem; border-top: 1px solid var(--st-border); margin-top: .85rem;
}
.st-btn-save {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1.15rem; font-size: .82rem; font-weight: 600;
    color: #fff; background: var(--st-brand); border: none;
    border-radius: var(--st-radius-sm); cursor: pointer;
    transition: all .15s; box-shadow: 0 1px 3px rgba(14,165,233,.2);
    font-family: inherit;
}
.st-btn-save:hover { background: var(--st-brand-dark); box-shadow: 0 2px 8px rgba(14,165,233,.3); }

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
    .st-grid-2 { grid-template-columns: 1fr; }
    .st-top-bar-inner { padding: .5rem 1rem; }
    .st-dash-nav-grid { gap: 10px; }
    .st-dash-nav-card { width: 100px; }
    .st-widget-grid { grid-template-columns: repeat(2, 1fr); }
    .st-widget-icon { margin-left: .5rem; margin-right: .5rem; }
    .st-widget-icon img { height: 2.5rem; }
    .st-widget-title { font-size: 1rem; }
}
@media (max-width: 480px) {
    .st-widget-grid { grid-template-columns: 1fr; }
}
</style>
@endpush


@section('content')

<div class="st-page"
     x-data="{
         activeSection: '{{ $section ?? 'dashboard' }}',
         showSection(key) {
             this.activeSection = key;
             window.history.replaceState(null, '', '?section=' + key);
         }
     }"
     x-cloak>

    {{-- ═══ Top Bar ═══ --}}
    <div class="st-top-bar">
        <div class="st-top-bar-inner">
            <div class="st-left">
                <a href="{{ route('tenant.booking.show', ['business' => $business]) }}"
                   class="st-back-btn" title="Back to Site">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div class="st-title-block">
                    <h1 class="st-page-title">My Portal</h1>
                    <p class="st-page-subtitle">Manage your repairs, estimates &amp; account</p>
                </div>
            </div>
            <div class="st-right">
                <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="st-btn-book">
                    <i class="bi bi-plus-lg"></i> Book Repair
                </a>
                <div class="st-user-pill">
                    <span class="st-user-avatar">
                        {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="d-none d-md-inline">{{ $user->first_name ?? $user->name }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Layout ═══ --}}
    <div class="st-layout">

        {{-- ─── Sidebar ─── --}}
        <aside class="st-sidebar">
            <div class="st-sidebar-top">
                <div class="st-sidebar-brand">Customer Portal</div>
            </div>

            {{-- Overview --}}
            <div class="st-sidebar-group">
                <div class="st-sidebar-group-label">Overview</div>
                <ul class="st-sidebar-items">
                    <li class="st-sidebar-item"
                        :class="{ 'active': activeSection === 'dashboard' }"
                        @click="showSection('dashboard')">
                        <i class="bi bi-speedometer2 st-nav-icon"></i>
                        <span>Dashboard</span>
                    </li>
                </ul>
            </div>

            {{-- Repairs --}}
            <div class="st-sidebar-group">
                <div class="st-sidebar-group-label">Repairs</div>
                <ul class="st-sidebar-items">
                    <li class="st-sidebar-item"
                        :class="{ 'active': activeSection === 'jobs' }"
                        @click="showSection('jobs')">
                        <i class="bi bi-tools st-nav-icon"></i>
                        <span>My Jobs</span>
                        @if($openJobs > 0)
                            <span class="st-nav-badge">{{ $openJobs }}</span>
                        @endif
                    </li>
                    <li class="st-sidebar-item"
                        :class="{ 'active': activeSection === 'estimates' }"
                        @click="showSection('estimates')">
                        <i class="bi bi-file-earmark-text st-nav-icon"></i>
                        <span>Estimates</span>
                        @if($pendingEstimates > 0)
                            <span class="st-nav-badge">{{ $pendingEstimates }}</span>
                        @endif
                    </li>
                    <li class="st-sidebar-item"
                        :class="{ 'active': activeSection === 'devices' }"
                        @click="showSection('devices')">
                        <i class="bi bi-phone st-nav-icon"></i>
                        <span>My Devices</span>
                    </li>
                </ul>
            </div>

            {{-- Account --}}
            <div class="st-sidebar-group">
                <div class="st-sidebar-group-label">Account</div>
                <ul class="st-sidebar-items">
                    <li class="st-sidebar-item"
                        :class="{ 'active': activeSection === 'account' }"
                        @click="showSection('account')">
                        <i class="bi bi-person-circle st-nav-icon"></i>
                        <span>My Account</span>
                    </li>
                </ul>
            </div>
        </aside>

        {{-- ─── Content ─── --}}
        <main class="st-content">

            {{-- Flash --}}
            @if(session('success'))
                <div class="st-flash st-flash-success">
                    <i class="bi bi-check-circle"></i>
                    <span>{{ session('success') }}</span>
                    <button class="st-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="st-flash st-flash-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                    <button class="st-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif


            {{-- ══════════════════════════════════════════════════
                 Dashboard
                 ══════════════════════════════════════════════════ --}}
            <div x-show="activeSection === 'dashboard'" x-cloak>

                {{-- ── Page Heading ── --}}
                <h2 class="st-dash-section-title">Dashboard</h2>
                <p style="font-size:.82rem;color:var(--st-text-3);margin:-0.75rem 0 1.5rem;">
                    Quick access to your repair history, estimates and account.
                </p>

                {{-- ── Navigation Cards ── --}}
                <div class="st-dash-nav-grid">
                    <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="st-dash-nav-card">
                        <div class="st-dash-nav-card-img">
                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/services.png') }}" alt="Book Repair" loading="lazy">
                        </div>
                        <p class="st-dash-nav-card-label">Book Repair</p>
                    </a>
                    <div class="st-dash-nav-card" @click="showSection('jobs')">
                        <div class="st-dash-nav-card-img">
                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="My Jobs" loading="lazy">
                        </div>
                        <p class="st-dash-nav-card-label">My Jobs</p>
                    </div>
                    <div class="st-dash-nav-card" @click="showSection('estimates')">
                        <div class="st-dash-nav-card-img">
                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="Estimates" loading="lazy">
                        </div>
                        <p class="st-dash-nav-card-label">Estimates</p>
                    </div>
                    <div class="st-dash-nav-card" @click="showSection('devices')">
                        <div class="st-dash-nav-card-img">
                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/devices.png') }}" alt="My Devices" loading="lazy">
                        </div>
                        <p class="st-dash-nav-card-label">My Devices</p>
                    </div>
                    <div class="st-dash-nav-card" @click="showSection('account')">
                        <div class="st-dash-nav-card-img">
                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/clients.png') }}" alt="My Account" loading="lazy">
                        </div>
                        <p class="st-dash-nav-card-label">My Account</p>
                    </div>
                </div>

                {{-- ── Jobs Summary ── --}}
                <h3 style="font-size:.88rem;font-weight:700;color:var(--st-text);margin:0 0 .65rem;">My Jobs Summary</h3>
                <div class="st-widget-grid">
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('jobs')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Total Jobs</div>
                                        <div class="st-widget-number">{{ number_format($totalJobs) }} Jobs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('jobs')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">In Progress</div>
                                        <div class="st-widget-number">{{ number_format($openJobs) }} Jobs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('jobs')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Completed</div>
                                        <div class="st-widget-number">{{ number_format($completedJobs) }} Jobs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('devices')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/devices.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">My Devices</div>
                                        <div class="st-widget-number">{{ number_format($devices->count()) }} Devices</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Estimates Summary ── --}}
                <h3 style="font-size:.88rem;font-weight:700;color:var(--st-text);margin:1.25rem 0 .65rem;">Estimates Summary</h3>
                <div class="st-widget-grid">
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('estimates')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Total Estimates</div>
                                        <div class="st-widget-number">{{ number_format($totalEstimates) }} Estimates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('estimates')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Pending</div>
                                        <div class="st-widget-number">{{ number_format($pendingEstimates) }} Estimates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('estimates')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Approved</div>
                                        <div class="st-widget-number">{{ number_format($approvedEstimates ?? 0) }} Estimates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-widget">
                        <div class="st-widget-inner" @click="showSection('estimates')">
                            <div class="st-widget-body">
                                <div class="st-widget-media">
                                    <div class="st-widget-icon">
                                        <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="" loading="lazy">
                                    </div>
                                    <div class="st-widget-info">
                                        <div class="st-widget-title">Rejected</div>
                                        <div class="st-widget-number">{{ number_format($rejectedEstimates ?? 0) }} Estimates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            {{-- ══════════════════════════════════════════════════
                 My Jobs
                 ══════════════════════════════════════════════════ --}}
            <div x-show="activeSection === 'jobs'" x-cloak>
                <div class="st-section">
                    <div class="st-section-header">
                        <h2 class="st-section-title">
                            <i class="bi bi-tools st-sec-icon"></i> All Jobs
                        </h2>
                        <span style="font-size:.72rem;color:var(--st-text-3);">{{ $totalJobs }} total</span>
                    </div>
                    <div class="st-section-body" style="padding:0 0 .25rem;">
                        @if($jobs->isEmpty())
                            <div class="st-placeholder">
                                <div class="st-placeholder-icon"><i class="bi bi-inbox"></i></div>
                                <div class="st-placeholder-title">No jobs found</div>
                                <div class="st-placeholder-text">When you book a repair, your jobs will appear here.</div>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                            <table class="st-table">
                                <thead>
                                    <tr><th>Job #</th><th>Title</th><th>Status</th><th>Payment</th><th>Created</th><th>Pickup</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($jobs as $job)
                                        <tr>
                                            <td><strong style="color:var(--st-brand);">#{{ $job->job_number ?? $job->case_number ?? $job->id }}</strong></td>
                                            <td>
                                                <div style="font-weight:600;">{{ $job->title ?? '—' }}</div>
                                                @if($job->case_detail)
                                                    <div style="font-size:.68rem;color:var(--st-text-3);margin-top:.1rem;">{{ \Illuminate\Support\Str::limit($job->case_detail, 60) }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($job->closed_at)
                                                    <span class="st-badge st-badge-success">Completed</span>
                                                @else
                                                    <span class="st-badge st-badge-warning">{{ ucfirst(str_replace(['-','_'],' ',$job->status_slug ?? 'In Progress')) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php $ps = $job->payment_status_slug ?? 'pending'; @endphp
                                                @if($ps === 'paid')
                                                    <span class="st-badge st-badge-success">Paid</span>
                                                @elseif($ps === 'partial')
                                                    <span class="st-badge st-badge-info">Partial</span>
                                                @else
                                                    <span class="st-badge st-badge-default">{{ ucfirst(str_replace(['-','_'],' ',$ps)) }}</span>
                                                @endif
                                            </td>
                                            <td style="color:var(--st-text-3);font-size:.78rem;white-space:nowrap;">{{ $job->created_at?->format('M d, Y') ?? '—' }}</td>
                                            <td style="color:var(--st-text-3);font-size:.78rem;white-space:nowrap;">{{ $job->pickup_date?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- ══════════════════════════════════════════════════
                 Estimates
                 ══════════════════════════════════════════════════ --}}
            <div x-show="activeSection === 'estimates'" x-cloak>
                <div class="st-section">
                    <div class="st-section-header">
                        <h2 class="st-section-title">
                            <i class="bi bi-file-earmark-text st-sec-icon"></i> All Estimates
                        </h2>
                        <span style="font-size:.72rem;color:var(--st-text-3);">{{ $totalEstimates }} total</span>
                    </div>
                    <div class="st-section-body" style="padding:0 0 .25rem;">
                        @if($estimates->isEmpty())
                            <div class="st-placeholder">
                                <div class="st-placeholder-icon"><i class="bi bi-file-earmark-x"></i></div>
                                <div class="st-placeholder-title">No estimates yet</div>
                                <div class="st-placeholder-text">When the shop sends you an estimate, it will appear here.</div>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                            <table class="st-table">
                                <thead>
                                    <tr><th>Estimate #</th><th>Title</th><th>Status</th><th>Created</th><th>Sent</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($estimates as $estimate)
                                        <tr>
                                            <td><strong style="color:var(--st-brand);">#{{ $estimate->case_number ?? $estimate->id }}</strong></td>
                                            <td>
                                                <div style="font-weight:600;">{{ $estimate->title ?? '—' }}</div>
                                                @if($estimate->case_detail)
                                                    <div style="font-size:.68rem;color:var(--st-text-3);margin-top:.1rem;">{{ \Illuminate\Support\Str::limit($estimate->case_detail, 60) }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @php $st = $estimate->status ?? 'draft'; @endphp
                                                @if($st === 'approved')
                                                    <span class="st-badge st-badge-success">Approved</span>
                                                @elseif($st === 'rejected')
                                                    <span class="st-badge st-badge-danger">Rejected</span>
                                                @elseif($st === 'sent')
                                                    <span class="st-badge st-badge-primary">Sent</span>
                                                @elseif($st === 'pending')
                                                    <span class="st-badge st-badge-warning">Pending</span>
                                                @else
                                                    <span class="st-badge st-badge-default">{{ ucfirst($st) }}</span>
                                                @endif
                                            </td>
                                            <td style="color:var(--st-text-3);font-size:.78rem;white-space:nowrap;">{{ $estimate->created_at?->format('M d, Y') ?? '—' }}</td>
                                            <td style="color:var(--st-text-3);font-size:.78rem;white-space:nowrap;">{{ $estimate->sent_at?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- ══════════════════════════════════════════════════
                 My Devices
                 ══════════════════════════════════════════════════ --}}
            <div x-show="activeSection === 'devices'" x-cloak>
                <div class="st-section">
                    <div class="st-section-header">
                        <h2 class="st-section-title">
                            <i class="bi bi-phone st-sec-icon"></i> My Devices
                        </h2>
                        <span style="font-size:.72rem;color:var(--st-text-3);">{{ $devices->count() }} registered</span>
                    </div>
                    <div class="st-section-body" style="padding:0 0 .25rem;">
                        @if($devices->isEmpty())
                            <div class="st-placeholder">
                                <div class="st-placeholder-icon"><i class="bi bi-phone"></i></div>
                                <div class="st-placeholder-title">No devices registered</div>
                                <div class="st-placeholder-text">Devices will be added when you book a repair.</div>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                            <table class="st-table">
                                <thead>
                                    <tr><th>Device</th><th>Label</th><th>Serial</th><th>Notes</th><th>Added</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($devices as $d)
                                        <tr>
                                            <td style="font-weight:600;">{{ $d->device?->name ?? 'Unknown Device' }}</td>
                                            <td>{{ $d->label ?? '—' }}</td>
                                            <td>
                                                @if($d->serial)
                                                    <code style="font-size:.72rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:4px;">{{ $d->serial }}</code>
                                                @else
                                                    <span style="color:var(--st-text-3);">—</span>
                                                @endif
                                            </td>
                                            <td style="font-size:.78rem;color:var(--st-text-2);max-width:180px;">{{ \Illuminate\Support\Str::limit($d->notes, 50) ?? '—' }}</td>
                                            <td style="color:var(--st-text-3);font-size:.78rem;white-space:nowrap;">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- ══════════════════════════════════════════════════
                 My Account
                 ══════════════════════════════════════════════════ --}}
            <div x-show="activeSection === 'account'" x-cloak>

                {{-- Personal Info --}}
                <div class="st-section">
                    <div class="st-section-header">
                        <h2 class="st-section-title">
                            <i class="bi bi-person st-sec-icon"></i> Personal Information
                        </h2>
                    </div>
                    <div class="st-section-body">
                        <form method="POST" action="{{ route('tenant.customer.account.update', ['business' => $business]) }}">
                            @csrf

                            <div class="st-grid st-grid-2">
                                <div class="st-fg">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name"
                                           value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name') <div class="st-field-error">{{ $message }}</div> @enderror
                                </div>
                                <div class="st-fg">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name"
                                           value="{{ old('last_name', $user->last_name) }}">
                                    @error('last_name') <div class="st-field-error">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="st-grid st-grid-2">
                                <div class="st-fg">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email"
                                           value="{{ old('email', $user->email) }}" required>
                                    @error('email') <div class="st-field-error">{{ $message }}</div> @enderror
                                </div>
                                <div class="st-fg">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone"
                                           value="{{ old('phone', $user->phone) }}" placeholder="+1 555 123 4567">
                                    @error('phone') <div class="st-field-error">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="st-fg">
                                <label for="company">Company</label>
                                <input type="text" id="company" name="company"
                                       value="{{ old('company', $user->company) }}" placeholder="Optional">
                                @error('company') <div class="st-field-error">{{ $message }}</div> @enderror
                            </div>

                            <hr style="border:0;border-top:1px solid var(--st-border);margin:1.25rem 0;">

                            <h3 style="font-size:.78rem;font-weight:700;color:var(--st-text);margin-bottom:.85rem;">
                                <i class="bi bi-geo-alt" style="color:var(--st-brand);"></i> Address
                            </h3>

                            <div class="st-fg">
                                <label for="address">Street Address</label>
                                <input type="text" id="address" name="address"
                                       value="{{ old('address', $user->address) }}">
                                @error('address') <div class="st-field-error">{{ $message }}</div> @enderror
                            </div>

                            <div class="st-grid st-grid-2">
                                <div class="st-fg">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city"
                                           value="{{ old('city', $user->city) }}">
                                </div>
                                <div class="st-fg">
                                    <label for="state">State / Province</label>
                                    <input type="text" id="state" name="state"
                                           value="{{ old('state', $user->state) }}">
                                </div>
                            </div>

                            <div class="st-grid st-grid-2">
                                <div class="st-fg">
                                    <label for="zip">ZIP / Postal Code</label>
                                    <input type="text" id="zip" name="zip"
                                           value="{{ old('zip', $user->zip) }}">
                                </div>
                                <div class="st-fg">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country"
                                           value="{{ old('country', $user->country) }}">
                                </div>
                            </div>

                            <div class="st-save-bar">
                                <button type="submit" class="st-btn-save">
                                    <i class="bi bi-check-lg"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Account Info --}}
                <div class="st-section">
                    <div class="st-section-header">
                        <h2 class="st-section-title">
                            <i class="bi bi-shield-check st-sec-icon"></i> Account Information
                        </h2>
                    </div>
                    <div class="st-section-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--st-text-3);margin-bottom:.1rem;">Account Created</div>
                                <div style="font-size:.84rem;color:var(--st-text);">{{ $user->created_at?->format('F j, Y') ?? '—' }}</div>
                            </div>
                            <div>
                                <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--st-text-3);margin-bottom:.1rem;">Email Verified</div>
                                <div style="font-size:.84rem;">
                                    @if($user->email_verified_at)
                                        <span style="color:var(--st-success);"><i class="bi bi-check-circle-fill"></i> Verified</span>
                                    @else
                                        <span style="color:var(--st-warning);"><i class="bi bi-exclamation-circle"></i> Not verified</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

@endsection

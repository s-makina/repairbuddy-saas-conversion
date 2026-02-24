{{--
  ┌──────────────────────────────────────────────────────────────────┐
  │  MOCKUP 3 — Vertical Sidebar Navigation Layout                  │
  │  Matches job-create theming (Design B variables).               │
  │  Top bar → Left vertical pill-nav → Right content panel.        │
  │  The sidebar nav is sticky and highlights the active section.   │
  │  Reusable for Estimate Detail by swapping data/titles.          │
  └──────────────────────────────────────────────────────────────────┘
--}}
@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Job Details'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyJob|\App\Models\RepairBuddyEstimate|null $record */
    $record = $job ?? $estimate ?? null;

    $isEstimate  = isset($estimate);
    $entityLabel = $isEstimate ? 'Estimate' : 'Job';
    $entityIcon  = $isEstimate ? 'bi-file-earmark-text' : 'bi-briefcase-fill';

    $totals   = is_array($totals ?? null) ? $totals : [];
    $currency = is_string($totals['currency'] ?? null) ? (string) $totals['currency'] : 'USD';

    $customer    = $record?->customer;
    $technicians = $isEstimate ? collect([$record?->assignedTechnician])->filter() : ($record?->technicians ?? collect());

    $jobItems       = is_iterable($jobItems ?? null) ? $jobItems : [];
    $jobDevices     = is_iterable($jobDevices ?? null) ? $jobDevices : [];
    $jobEvents      = is_iterable($jobEvents ?? null) ? $jobEvents : [];
    $jobAttachments = is_iterable($jobAttachments ?? null) ? $jobAttachments : [];
    $jobTimelogs    = is_iterable($jobTimelogs ?? null) ? $jobTimelogs : [];
    $jobPayments    = is_iterable($jobPayments ?? null) ? $jobPayments : [];
    $jobExpenses    = is_iterable($jobExpenses ?? null) ? $jobExpenses : [];
    $jobFeedback    = is_iterable($jobFeedback ?? null) ? $jobFeedback : [];

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';
    $listUrl    = $isEstimate
        ? route('tenant.estimates.index', ['business' => $tenantSlug])
        : (route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs');
    $editUrl    = ($record && $tenantSlug)
        ? ($isEstimate
            ? route('tenant.estimates.edit', ['business' => $tenantSlug, 'estimateId' => $record->id])
            : route('tenant.jobs.edit', ['business' => $tenantSlug, 'jobId' => $record->id]))
        : '#';

    $formatMoney = function ($cents) {
        if ($cents === null) return '—';
        return '$' . number_format(((int) $cents) / 100, 2, '.', ',');
    };

    $statusSlug = $record?->status_slug ?? ($record?->status ?? 'open');
@endphp

@push('page-styles')
<style>
    /* ═══════════════════════════════════════════════════════
       Mockup 3 — Vertical Sidebar Nav Detail Layout
       Re-uses Design B tokens from job-form.
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

    /* ── Page shell ── */
    .jv-page {
        background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
        min-height: 100vh;
        margin: -1rem -1rem 0 -1rem;
        padding: 0;
        width: calc(100% + 2rem);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: var(--rb-text);
    }

    /* ── Sticky top bar ── */
    .jv-topbar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--rb-border);
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 1px 0 var(--rb-border), 0 2px 8px rgba(14,165,233,.04);
    }
    .jv-topbar-inner {
        max-width: 1440px; margin: 0 auto;
        padding: .65rem 2rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }
    .jv-topbar-left { display: flex; align-items: center; gap: 1rem; }
    .jv-topbar-right { display: flex; gap: .5rem; }

    .jv-back-btn {
        width: 34px; height: 34px; border-radius: 10px;
        border: 1px solid var(--rb-border); background: #fff; color: var(--rb-text-2);
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: .88rem; transition: all .15s;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .jv-back-btn:hover { background: var(--rb-bg); color: var(--rb-brand); border-color: var(--rb-brand); }

    .jv-title-block .jv-badge {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .65rem; font-weight: 700; padding: .15rem .55rem;
        border-radius: 999px; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem;
    }
    .jv-badge-view { background: var(--rb-brand-soft); color: var(--rb-brand-dark); border: 1px solid #bae6fd; }
    .jv-title-block h1 {
        display: flex; align-items: center; gap: .5rem;
        font-size: 1rem; font-weight: 800; margin: 0 0 .15rem;
    }
    .jv-title-block h1 i { color: var(--rb-brand); font-size: .9rem; }
    .jv-breadcrumb {
        display: flex; align-items: center; gap: .2rem;
        font-size: .72rem; color: var(--rb-text-3); margin: 0; list-style: none; padding: 0;
    }
    .jv-breadcrumb a { color: var(--rb-text-3); text-decoration: none; }
    .jv-breadcrumb a:hover { color: var(--rb-brand); }
    .jv-breadcrumb .sep { font-size: .6rem; opacity: .4; }
    .jv-breadcrumb .cur { color: var(--rb-text-2); font-weight: 600; }

    .jv-btn {
        padding: .5rem 1.1rem; border-radius: var(--rb-radius-sm);
        font-size: .84rem; font-weight: 600; cursor: pointer;
        border: none; transition: all .15s;
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
    }
    .jv-btn-outline { background: transparent; color: var(--rb-text-2); border: 1px solid var(--rb-border); }
    .jv-btn-outline:hover { background: var(--rb-bg); color: var(--rb-text); }
    .jv-btn-primary { background: var(--rb-brand); color: #fff; }
    .jv-btn-primary:hover { background: var(--rb-brand-dark); color: #fff; }

    /* ── STATUS PILL ── */
    .jv-sp {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .2rem .55rem; border-radius: 999px;
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
    }
    .jv-sp-open   { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }
    .jv-sp-done   { background: var(--rb-success-soft); color: #16a34a; }
    .jv-sp-danger { background: var(--rb-danger-soft); color: var(--rb-danger); }

    /* ── 3-column layout: nav + main + mini-sidebar ── */
    .jv-layout {
        display: flex; gap: 0;
        max-width: 1440px; margin: 0 auto;
        min-height: calc(100vh - 56px);
    }

    /* ── LEFT SIDEBAR NAV ── */
    .jv-nav {
        width: 220px; flex-shrink: 0;
        border-right: 1px solid var(--rb-border);
        background: rgba(255,255,255,.6);
        padding: 1.25rem .75rem;
    }
    .jv-nav-sticky { position: sticky; top: 4.5rem; }
    .jv-nav-label {
        font-size: .65rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: var(--rb-text-3); padding: 0 .5rem;
        margin-bottom: .5rem;
    }
    .jv-nav-item {
        display: flex; align-items: center; gap: .5rem;
        padding: .55rem .65rem; border-radius: var(--rb-radius-sm);
        font-size: .82rem; font-weight: 600; color: var(--rb-text-2);
        cursor: pointer; transition: all .12s;
        margin-bottom: .15rem; border: none; background: transparent; width: 100%;
        text-align: left;
    }
    .jv-nav-item:hover { background: var(--rb-bg); color: var(--rb-text); }
    .jv-nav-item.active {
        background: var(--rb-brand-soft); color: var(--rb-brand-dark);
        font-weight: 700;
    }
    .jv-nav-item i { font-size: .9rem; width: 18px; text-align: center; }
    .jv-nav-badge {
        margin-left: auto; font-size: .62rem; font-weight: 700;
        padding: .1rem .35rem; border-radius: 999px;
        background: var(--rb-bg); color: var(--rb-text-3);
        min-width: 18px; text-align: center;
    }
    .jv-nav-item.active .jv-nav-badge { background: white; color: var(--rb-brand-dark); }

    .jv-nav-sep { height: 1px; background: var(--rb-border); margin: .75rem .5rem; }

    /* ── MAIN CONTENT ── */
    .jv-main {
        flex: 1; min-width: 0;
        padding: 1.5rem 2rem;
    }

    /* ── RIGHT MINI SIDEBAR ── */
    .jv-mini {
        width: 280px; flex-shrink: 0;
        border-left: 1px solid var(--rb-border);
        background: rgba(255,255,255,.5);
        padding: 1.25rem 1rem;
    }
    .jv-mini-sticky { position: sticky; top: 4.5rem; }
    @media (max-width: 1199.98px) {
        .jv-mini { display: none; }
    }
    @media (max-width: 991.98px) {
        .jv-nav { display: none; }
        .jv-main { padding: 1rem; }
    }

    /* ── Card ── */
    .jv-card {
        background: var(--rb-card); border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius); overflow: hidden;
        box-shadow: var(--rb-shadow); margin-bottom: 1rem;
    }
    .jv-card-head {
        display: flex; align-items: center; gap: .6rem;
        padding: .85rem 1.25rem; border-bottom: 1px solid var(--rb-border);
    }
    .jv-card-head i { color: var(--rb-brand); font-size: 1.1rem; }
    .jv-card-head h4 { font-size: .92rem; font-weight: 700; margin: 0; flex: 1; }
    .jv-card-body { padding: 1.25rem; }

    /* Key-value */
    .jv-kv { display: flex; padding: .5rem 0; border-bottom: 1px solid #f1f5f9; font-size: .86rem; }
    .jv-kv:last-child { border-bottom: none; }
    .jv-kv-label { width: 140px; flex-shrink: 0; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--rb-text-3); padding-top: .15rem; }
    .jv-kv-value { flex: 1; color: var(--rb-text); font-weight: 500; }

    /* Table */
    .jv-table { width: 100%; border-collapse: collapse; }
    .jv-table th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--rb-text-3); padding: .65rem .75rem; border-bottom: 1px solid var(--rb-border); text-align: left; }
    .jv-table td { padding: .65rem .75rem; font-size: .85rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .jv-table tr:last-child td { border-bottom: none; }
    .jv-table .text-end { text-align: right; }
    .jv-table .fw-bold { font-weight: 700; }

    /* Person */
    .jv-person {
        display: flex; align-items: center; gap: .65rem;
        padding: .55rem; border-radius: 8px; background: var(--rb-bg);
        border: 1px solid var(--rb-border); margin-bottom: .35rem;
    }
    .jv-person-av {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .75rem; flex-shrink: 0; color: #fff;
    }
    .jv-person-name { font-weight: 700; font-size: .84rem; }
    .jv-person-meta { font-size: .72rem; color: var(--rb-text-3); }

    /* Device row */
    .jv-dev {
        display: flex; align-items: center; gap: .5rem;
        padding: .5rem; border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm); background: var(--rb-bg); margin-bottom: .35rem;
    }
    .jv-dev-icon {
        width: 30px; height: 30px; border-radius: 7px;
        background: #dbeafe; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: .85rem;
    }
    .jv-dev-info strong { font-size: .84rem; display: block; }
    .jv-dev-info span { font-size: .7rem; color: var(--rb-text-3); display: block; }

    /* Timeline */
    .jv-tl { display: flex; gap: .65rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; }
    .jv-tl:last-child { border-bottom: none; }
    .jv-tl-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--rb-brand); margin-top: 6px; flex-shrink: 0; }
    .jv-tl-title { font-size: .84rem; font-weight: 600; }
    .jv-tl-meta { font-size: .72rem; color: var(--rb-text-3); }

    /* Stars */
    .jv-stars { color: var(--rb-warning); font-size: 1rem; }

    /* Empty */
    .jv-empty { text-align: center; padding: 1.5rem 1rem; color: var(--rb-text-3); font-size: .84rem; }
    .jv-empty i { font-size: 1.5rem; display: block; margin-bottom: .4rem; opacity: .4; }

    /* Mini sidebar financials */
    .jv-fin-row { display: flex; justify-content: space-between; padding: .35rem 0; font-size: .82rem; }
    .jv-fin-row + .jv-fin-row { border-top: 1px solid #f1f5f9; }
    .jv-fin-label { color: var(--rb-text-3); font-size: .68rem; font-weight: 600; text-transform: uppercase; }
    .jv-fin-val { font-weight: 700; }
    .jv-fin-grand { border-top: 2px solid var(--rb-text); margin-top: .4rem; padding-top: .5rem; display: flex; justify-content: space-between; font-size: .95rem; font-weight: 800; }

    /* Mini sidebar actions */
    .jv-mini-btn {
        display: flex; align-items: center; gap: .4rem; width: 100%;
        padding: .45rem .7rem; border-radius: var(--rb-radius-sm);
        border: 1px solid var(--rb-border); background: #fff;
        font-size: .82rem; font-weight: 600; color: var(--rb-text-2);
        cursor: pointer; transition: all .12s; text-decoration: none;
        margin-bottom: .35rem;
    }
    .jv-mini-btn:hover { background: var(--rb-bg); color: var(--rb-text); border-color: var(--rb-brand); }
    .jv-mini-btn i { font-size: .88rem; width: 16px; text-align: center; }
    .jv-mini-btn.primary { background: var(--rb-brand); color: #fff; border-color: var(--rb-brand); }
    .jv-mini-btn.primary:hover { background: var(--rb-brand-dark); }
</style>
@endpush

<div class="jv-page">
    {{-- ═══════════════ STICKY TOP BAR ═══════════════ --}}
    <div class="jv-topbar">
        <div class="jv-topbar-inner">
            <div class="jv-topbar-left">
                <a href="{{ $listUrl }}" class="jv-back-btn" title="Back"><i class="bi bi-chevron-left"></i></a>
                <div class="jv-title-block">
                    <div class="jv-badge jv-badge-view"><i class="bi bi-eye"></i> {{ __('Detail View') }}</div>
                    <h1>
                        <i class="{{ $entityIcon }}"></i>
                        {{ $entityLabel }}: {{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}
                        @php
                            $spClass = match (true) {
                                in_array($statusSlug, ['completed','delivered','approved']) => 'jv-sp-done',
                                in_array($statusSlug, ['cancelled','rejected']) => 'jv-sp-danger',
                                default => 'jv-sp-open',
                            };
                        @endphp
                        <span class="jv-sp {{ $spClass }}">
                            <i class="bi bi-circle-fill" style="font-size:.4rem"></i>
                            {{ strtoupper(str_replace('_', ' ', $statusSlug)) }}
                        </span>
                    </h1>
                    <ul class="jv-breadcrumb">
                        <li><a href="{{ $listUrl }}">{{ $isEstimate ? __('Estimates') : __('Jobs') }}</a></li>
                        <li class="sep"><i class="bi bi-chevron-right"></i></li>
                        <li class="cur">{{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}</li>
                    </ul>
                </div>
            </div>
            <div class="jv-topbar-right">
                <a href="javascript:window.print()" class="jv-btn jv-btn-outline"><i class="bi bi-printer"></i>{{ __('Print') }}</a>
                <a href="{{ $editUrl }}" class="jv-btn jv-btn-outline"><i class="bi bi-pencil-square"></i>{{ __('Edit') }}</a>
                <a href="{{ $listUrl }}" class="jv-btn jv-btn-primary"><i class="bi bi-arrow-left"></i>{{ __('Back') }}</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════ 3-COLUMN LAYOUT ═══════════════ --}}
    <div class="jv-layout" x-data="{ section: 'overview' }">

        {{-- ──── LEFT SIDEBAR NAV ──── --}}
        <aside class="jv-nav">
            <div class="jv-nav-sticky">
                <div class="jv-nav-label">{{ __('Sections') }}</div>

                <button class="jv-nav-item" :class="section === 'overview' && 'active'" @click="section = 'overview'">
                    <i class="bi bi-grid"></i> {{ __('Overview') }}
                </button>
                <button class="jv-nav-item" :class="section === 'devices' && 'active'" @click="section = 'devices'">
                    <i class="bi bi-phone"></i> {{ __('Devices') }}
                    <span class="jv-nav-badge">{{ count($jobDevices) }}</span>
                </button>

                <div class="jv-nav-sep"></div>

                <button class="jv-nav-item" :class="section === 'technicians' && 'active'" @click="section = 'technicians'">
                    <i class="bi bi-people"></i> {{ __('Technicians') }}
                    <span class="jv-nav-badge">{{ $technicians->count() }}</span>
                </button>
                <button class="jv-nav-item" :class="section === 'timelogs' && 'active'" @click="section = 'timelogs'">
                    <i class="bi bi-stopwatch"></i> {{ __('Time Logs') }}
                    <span class="jv-nav-badge">{{ count($jobTimelogs) }}</span>
                </button>

                <div class="jv-nav-sep"></div>

                <button class="jv-nav-item" :class="section === 'payments' && 'active'" @click="section = 'payments'">
                    <i class="bi bi-credit-card-2-front"></i> {{ __('Payments') }}
                    <span class="jv-nav-badge">{{ count($jobPayments) }}</span>
                </button>
                <button class="jv-nav-item" :class="section === 'expenses' && 'active'" @click="section = 'expenses'">
                    <i class="bi bi-receipt-cutoff"></i> {{ __('Expenses') }}
                    <span class="jv-nav-badge">{{ count($jobExpenses) }}</span>
                </button>

                <div class="jv-nav-sep"></div>

                <button class="jv-nav-item" :class="section === 'history' && 'active'" @click="section = 'history'">
                    <i class="bi bi-clock-history"></i> {{ __('History') }}
                    <span class="jv-nav-badge">{{ count($jobEvents) }}</span>
                </button>
                <button class="jv-nav-item" :class="section === 'feedback' && 'active'" @click="section = 'feedback'">
                    <i class="bi bi-star"></i> {{ __('Feedback') }}
                    <span class="jv-nav-badge">{{ count($jobFeedback) }}</span>
                </button>
            </div>
        </aside>

        {{-- ──── MAIN CONTENT ──── --}}
        <main class="jv-main">

            {{-- ═══ OVERVIEW ═══ --}}
            <div x-show="section === 'overview'" x-cloak>
                {{-- Job Details --}}
                <div class="jv-card">
                    <div class="jv-card-head">
                        <i class="bi bi-clipboard-data"></i>
                        <h4>{{ $entityLabel }} {{ __('Details') }}</h4>
                    </div>
                    <div class="jv-card-body">
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Case #') }}</div><div class="jv-kv-value" style="font-weight:700;">{{ $record?->case_number ?? '—' }}</div></div>
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Title') }}</div><div class="jv-kv-value">{{ $record?->title ?? '—' }}</div></div>
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Status') }}</div><div class="jv-kv-value"><span class="jv-sp {{ $spClass }}"><i class="bi bi-circle-fill" style="font-size:.35rem"></i> {{ strtoupper(str_replace('_', ' ', $statusSlug)) }}</span></div></div>
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Pickup') }}</div><div class="jv-kv-value">{{ $record?->pickup_date ?? '—' }}</div></div>
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Delivery') }}</div><div class="jv-kv-value">{{ $record?->delivery_date ?? '—' }}</div></div>
                        <div class="jv-kv"><div class="jv-kv-label">{{ __('Payment') }}</div><div class="jv-kv-value">{{ strtoupper(str_replace('_', ' ', $record?->payment_status_slug ?? '—')) }}</div></div>
                    </div>
                </div>

                {{-- Customer --}}
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-person-badge"></i><h4>{{ __('Customer') }}</h4></div>
                    <div class="jv-card-body">
                        @if ($customer)
                            <div class="jv-person">
                                <div class="jv-person-av" style="background:linear-gradient(135deg, var(--rb-brand), var(--rb-brand-dark));">{{ strtoupper(mb_substr($customer->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="jv-person-name">{{ $customer->name }}</div>
                                    <div class="jv-person-meta">{{ $customer->email ?? '' }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</div>
                                    @if (!empty($customer->company ?? null))<div class="jv-person-meta">{{ $customer->company }}</div>@endif
                                </div>
                            </div>
                        @else
                            <div class="jv-empty" style="padding:.5rem;"><i class="bi bi-person" style="font-size:1rem;"></i>{{ __('No customer assigned') }}</div>
                        @endif
                    </div>
                </div>

                @if ($record?->case_detail)
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-journal-text"></i><h4>{{ __('Internal Notes') }}</h4></div>
                    <div class="jv-card-body">
                        <div style="white-space: pre-wrap; font-size:.86rem; line-height:1.5; color:var(--rb-text-2);">{{ $record->case_detail }}</div>
                    </div>
                </div>
                @endif

                {{-- Line Items --}}
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-list-check"></i><h4>{{ __('Line Items') }}</h4></div>
                    <div class="jv-card-body" style="padding:0;">
                        @if (count($jobItems) > 0)
                            <table class="jv-table">
                                <thead><tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="text-end">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Unit') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobItems as $item)
                                    @php
                                        $qty = max(1, (int)($item->qty ?? 1));
                                        $unit = (int)($item->unit_price_amount_cents ?? 0);
                                        $lt = $qty * $unit;
                                        if (($item->item_type ?? null) === 'discount') $lt = 0 - $lt;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $item->name_snapshot }}</td>
                                        <td><span style="font-size:.7rem; text-transform:uppercase; color:var(--rb-text-3); font-weight:600; letter-spacing:.03em;">{{ $item->item_type ?? '' }}</span></td>
                                        <td class="text-end">{{ $qty }}</td>
                                        <td class="text-end">{{ $formatMoney($unit) }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($lt) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jv-empty"><i class="bi bi-list-check"></i>{{ __('No line items') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ DEVICES ═══ --}}
            <div x-show="section === 'devices'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-phone"></i><h4>{{ __('Attached Devices') }} ({{ count($jobDevices) }})</h4></div>
                    <div class="jv-card-body">
                        @forelse ($jobDevices as $d)
                            <div class="jv-dev">
                                <div class="jv-dev-icon"><i class="bi bi-laptop"></i></div>
                                <div class="jv-dev-info" style="flex:1; min-width:0;">
                                    <strong>{{ $d->label_snapshot ?? __('Device') }}</strong>
                                    <span>{{ __('SN') }}: {{ $d->serial_snapshot ?? '—' }} · {{ __('PIN') }}: {{ $d->pin_snapshot ?? '—' }}</span>
                                    @if ($d->notes_snapshot)
                                        <span style="color:var(--rb-text-2); margin-top:.2rem;">{{ $d->notes_snapshot }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="jv-empty"><i class="bi bi-phone"></i>{{ __('No devices attached') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ═══ TECHNICIANS ═══ --}}
            <div x-show="section === 'technicians'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-people-fill"></i><h4>{{ __('Assigned Technicians') }}</h4></div>
                    <div class="jv-card-body">
                        @forelse ($technicians as $tech)
                            <div class="jv-person">
                                <div class="jv-person-av" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">{{ strtoupper(mb_substr($tech->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="jv-person-name">{{ $tech->name }}</div>
                                    <div class="jv-person-meta">{{ $tech->email ?? '' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="jv-empty"><i class="bi bi-people"></i>{{ __('No technicians assigned') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ═══ TIME LOGS ═══ --}}
            <div x-show="section === 'timelogs'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-stopwatch"></i><h4>{{ __('Time Logs') }}</h4></div>
                    <div class="jv-card-body" style="padding:0;">
                        @if (count($jobTimelogs) > 0)
                            <table class="jv-table">
                                <thead><tr>
                                    <th>{{ __('Technician') }}</th>
                                    <th>{{ __('Start') }}</th>
                                    <th>{{ __('End') }}</th>
                                    <th class="text-end">{{ __('Duration') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobTimelogs as $log)
                                    <tr>
                                        <td class="fw-bold">{{ $log->technician?->name ?? '—' }}</td>
                                        <td>{{ $log->start_at ?? '—' }}</td>
                                        <td>{{ $log->end_at ?? '—' }}</td>
                                        <td class="text-end">{{ $log->duration_label ?? '—' }}</td>
                                        <td style="font-size:.78rem; color:var(--rb-text-2);">{{ $log->notes ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jv-empty"><i class="bi bi-stopwatch"></i>{{ __('No time logs recorded') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ PAYMENTS ═══ --}}
            <div x-show="section === 'payments'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-credit-card-2-front"></i><h4>{{ __('Payment Records') }}</h4></div>
                    <div class="jv-card-body" style="padding:0;">
                        @if (count($jobPayments) > 0)
                            <table class="jv-table">
                                <thead><tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobPayments as $pmt)
                                    <tr>
                                        <td>{{ $pmt->paid_at ?? $pmt->created_at ?? '—' }}</td>
                                        <td><span class="jv-sp jv-sp-open">{{ strtoupper($pmt->method ?? 'N/A') }}</span></td>
                                        <td style="font-family:monospace; font-size:.8rem;">{{ $pmt->reference ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($pmt->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem; color:var(--rb-text-2);">{{ $pmt->notes ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jv-empty"><i class="bi bi-credit-card"></i>{{ __('No payments recorded yet') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ EXPENSES ═══ --}}
            <div x-show="section === 'expenses'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-receipt-cutoff"></i><h4>{{ __('Job Expenses') }}</h4></div>
                    <div class="jv-card-body" style="padding:0;">
                        @if (count($jobExpenses) > 0)
                            <table class="jv-table">
                                <thead><tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('By') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobExpenses as $exp)
                                    <tr>
                                        <td>{{ $exp->expense_date ?? $exp->created_at ?? '—' }}</td>
                                        <td><span class="jv-sp" style="background:var(--rb-warning-soft); color:#92400e;">{{ $exp->category?->name ?? $exp->category_name ?? '—' }}</span></td>
                                        <td>{{ $exp->description ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($exp->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem;">{{ $exp->creator?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jv-empty"><i class="bi bi-receipt"></i>{{ __('No expenses recorded') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ HISTORY ═══ --}}
            <div x-show="section === 'history'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-clock-history"></i><h4>{{ __('Activity Timeline') }}</h4></div>
                    <div class="jv-card-body">
                        @forelse ($jobEvents as $ev)
                            @php
                                $payload = is_array($ev->payload_json) ? $ev->payload_json : (is_string($ev->payload_json) ? json_decode($ev->payload_json, true) : []);
                                $evTitle = $payload['title'] ?? ($ev->event_type ?? 'Event');
                                $actorName = $ev->actor?->name ?? __('System');
                            @endphp
                            <div class="jv-tl">
                                <div class="jv-tl-dot"></div>
                                <div style="flex:1;">
                                    <div class="jv-tl-title">{{ $evTitle }}</div>
                                    <div class="jv-tl-meta">{{ $actorName }} &middot; {{ $ev->created_at?->format('M d, Y H:i') ?? '' }}</div>
                                    @if (!empty($payload['message']))
                                        <div style="font-size:.8rem; color:var(--rb-text-2); margin-top:.2rem; white-space:pre-wrap;">{{ $payload['message'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="jv-empty"><i class="bi bi-clock-history"></i>{{ __('No activity recorded') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ═══ FEEDBACK ═══ --}}
            <div x-show="section === 'feedback'" x-cloak>
                <div class="jv-card">
                    <div class="jv-card-head"><i class="bi bi-star-fill"></i><h4>{{ __('Customer Feedback') }}</h4></div>
                    <div class="jv-card-body">
                        @forelse ($jobFeedback as $fb)
                            <div style="border:1px solid var(--rb-border); border-radius:var(--rb-radius-sm); padding:.85rem; margin-bottom:.6rem; background:var(--rb-bg);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                                    <div class="jv-stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= ($fb->rating ?? 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                        <span style="font-size:.82rem; font-weight:700; color:var(--rb-text); margin-left:.4rem;">{{ $fb->rating ?? 0 }}/5</span>
                                    </div>
                                    <span style="font-size:.72rem; color:var(--rb-text-3);">{{ $fb->created_at?->format('M d, Y') ?? '' }}</span>
                                </div>
                                @if ($fb->comment)
                                    <div style="font-size:.84rem; color:var(--rb-text-2); white-space:pre-wrap; line-height:1.5;">{{ $fb->comment }}</div>
                                @endif
                                <div style="font-size:.72rem; color:var(--rb-text-3); margin-top:.35rem;">{{ __('By') }}: {{ $fb->customer?->name ?? $fb->author_name ?? __('Customer') }}</div>
                            </div>
                        @empty
                            <div class="jv-empty"><i class="bi bi-star"></i>{{ __('No feedback received') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </main>

        {{-- ──── RIGHT MINI SIDEBAR ──── --}}
        <aside class="jv-mini">
            <div class="jv-mini-sticky">

                {{-- Financial Summary --}}
                <div style="margin-bottom:1.25rem;">
                    <div style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--rb-text-3); margin-bottom:.5rem;">{{ __('Financial Summary') }}</div>
                    <div style="background:var(--rb-card); border:1px solid var(--rb-border); border-radius:var(--rb-radius); padding:.85rem; box-shadow:var(--rb-shadow);">
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Subtotal') }}</span><span class="jv-fin-val">{{ $formatMoney($totals['subtotal_cents'] ?? null) }}</span></div>
                        @if (($totals['tax_cents'] ?? 0) > 0)
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Tax') }}</span><span class="jv-fin-val">{{ $formatMoney($totals['tax_cents']) }}</span></div>
                        @endif
                        <div class="jv-fin-grand">
                            <span>{{ __('Total') }}</span>
                            <span>{{ $formatMoney($totals['total_cents'] ?? $totals['grand_total_cents'] ?? null) }} {{ $currency }}</span>
                        </div>
                        @if (isset($totals['balance_cents']))
                        <div class="jv-fin-row" style="margin-top:.35rem;">
                            <span class="jv-fin-label">{{ __('Balance') }}</span>
                            <span class="jv-fin-val" style="color:var(--rb-danger);">{{ $formatMoney($totals['balance_cents']) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Info --}}
                <div style="margin-bottom:1.25rem;">
                    <div style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--rb-text-3); margin-bottom:.5rem;">{{ __('Quick Info') }}</div>
                    <div style="background:var(--rb-card); border:1px solid var(--rb-border); border-radius:var(--rb-radius); padding:.85rem; box-shadow:var(--rb-shadow);">
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Devices') }}</span><span class="jv-fin-val">{{ count($jobDevices) }}</span></div>
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Techs') }}</span><span class="jv-fin-val">{{ $technicians->count() }}</span></div>
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Payments') }}</span><span class="jv-fin-val">{{ count($jobPayments) }}</span></div>
                        <div class="jv-fin-row"><span class="jv-fin-label">{{ __('Events') }}</span><span class="jv-fin-val">{{ count($jobEvents) }}</span></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div>
                    <div style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--rb-text-3); margin-bottom:.5rem;">{{ __('Actions') }}</div>
                    <a href="{{ $editUrl }}" class="jv-mini-btn"><i class="bi bi-pencil-square"></i>{{ __('Edit') }} {{ $entityLabel }}</a>
                    <a href="javascript:window.print()" class="jv-mini-btn"><i class="bi bi-printer"></i>{{ __('Print') }}</a>
                    <a href="{{ $listUrl }}" class="jv-mini-btn primary"><i class="bi bi-arrow-left"></i>{{ __('Back to List') }}</a>
                </div>
            </div>
        </aside>

    </div>
</div>
@endsection

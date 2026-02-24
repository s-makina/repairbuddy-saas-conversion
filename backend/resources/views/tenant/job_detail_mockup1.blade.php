{{--
  ┌──────────────────────────────────────────────────────────────────┐
  │  MOCKUP 1 — Horizontal Tabs Layout                              │
  │  Matches job-create theming (Design B variables).               │
  │  Top bar → Overview ribbon → Horizontal tab strip → Tab panels. │
  │  Also reusable for Estimate Detail by swapping data/titles.     │
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
@endphp

@push('page-styles')
<style>
    /* ═══════════════════════════════════════════════════════
       Mockup 1 — Horizontal Tabs Detail
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
    .jd-page {
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
    .jd-topbar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--rb-border);
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 1px 0 var(--rb-border), 0 2px 8px rgba(14,165,233,.04);
    }
    .jd-topbar-inner {
        max-width: 1440px; margin: 0 auto;
        padding: .65rem 2rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }
    .jd-topbar-left { display: flex; align-items: center; gap: 1rem; }
    .jd-topbar-right { display: flex; gap: .5rem; }

    .jd-back-btn {
        width: 34px; height: 34px; border-radius: 10px;
        border: 1px solid var(--rb-border); background: #fff; color: var(--rb-text-2);
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: .88rem; transition: all .15s;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .jd-back-btn:hover { background: var(--rb-bg); color: var(--rb-brand); border-color: var(--rb-brand); }

    .jd-title-block .jd-badge {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .65rem; font-weight: 700; padding: .15rem .55rem;
        border-radius: 999px; text-transform: uppercase; letter-spacing: .04em;
        margin-bottom: .15rem;
    }
    .jd-badge-view { background: var(--rb-brand-soft); color: var(--rb-brand-dark); border: 1px solid #bae6fd; }
    .jd-title-block .jd-page-title {
        display: flex; align-items: center; gap: .5rem;
        font-size: 1rem; font-weight: 800; margin: 0 0 .15rem;
    }
    .jd-title-block .jd-page-title i { color: var(--rb-brand); font-size: .9rem; }
    .jd-breadcrumb {
        display: flex; align-items: center; gap: .2rem;
        font-size: .72rem; color: var(--rb-text-3); margin: 0; list-style: none; padding: 0;
    }
    .jd-breadcrumb a { color: var(--rb-text-3); text-decoration: none; }
    .jd-breadcrumb a:hover { color: var(--rb-brand); }
    .jd-breadcrumb .sep { font-size: .6rem; opacity: .4; margin: 0 .05rem; }
    .jd-breadcrumb .current { color: var(--rb-text-2); font-weight: 600; }

    .jd-btn {
        padding: .5rem 1.1rem; border-radius: var(--rb-radius-sm);
        font-size: .84rem; font-weight: 600; cursor: pointer;
        border: none; transition: all .15s;
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
    }
    .jd-btn-outline { background: transparent; color: var(--rb-text-2); border: 1px solid var(--rb-border); }
    .jd-btn-outline:hover { background: var(--rb-bg); color: var(--rb-text); }
    .jd-btn-primary { background: var(--rb-brand); color: #fff; }
    .jd-btn-primary:hover { background: var(--rb-brand-dark); color: #fff; }

    /* ── Overview Ribbon ── */
    .jd-ribbon {
        max-width: 1440px; margin: 0 auto;
        padding: 1.25rem 2rem 0;
    }
    .jd-ribbon-cards {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: .75rem;
    }
    .jd-kpi-card {
        background: var(--rb-card); border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius); padding: 1rem 1.1rem;
        box-shadow: var(--rb-shadow);
    }
    .jd-kpi-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--rb-text-3); margin-bottom: .25rem;
    }
    .jd-kpi-value { font-size: 1.1rem; font-weight: 800; color: var(--rb-text); }
    .jd-kpi-sub { font-size: .72rem; color: var(--rb-text-3); margin-top: .15rem; }

    .jd-status-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .6rem; border-radius: 999px;
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
    }
    .jd-status-open { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }
    .jd-status-completed { background: var(--rb-success-soft); color: #16a34a; }
    .jd-status-cancelled { background: var(--rb-danger-soft); color: var(--rb-danger); }

    /* ── Tab Strip ── */
    .jd-tabs-wrapper {
        max-width: 1440px; margin: 0 auto; padding: 1rem 2rem 0;
    }
    .jd-tabs {
        display: flex; gap: 0; border-bottom: 2px solid var(--rb-border);
        overflow-x: auto; -webkit-overflow-scrolling: touch;
    }
    .jd-tab {
        padding: .7rem 1.25rem; cursor: pointer;
        font-size: .82rem; font-weight: 600; color: var(--rb-text-3);
        border-bottom: 2px solid transparent; margin-bottom: -2px;
        white-space: nowrap; transition: all .15s;
        display: flex; align-items: center; gap: .4rem;
        background: none; border-top: none; border-left: none; border-right: none;
    }
    .jd-tab:hover { color: var(--rb-text-2); }
    .jd-tab.active {
        color: var(--rb-brand-dark); border-bottom-color: var(--rb-brand);
    }
    .jd-tab i { font-size: .9rem; }
    .jd-tab .jd-tab-badge {
        font-size: .62rem; font-weight: 700; padding: .1rem .4rem;
        border-radius: 999px; background: var(--rb-bg); color: var(--rb-text-3);
        min-width: 18px; text-align: center;
    }
    .jd-tab.active .jd-tab-badge { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }

    /* ── Tab Content Area ── */
    .jd-content {
        max-width: 1440px; margin: 0 auto; padding: 1.25rem 2rem 2rem;
    }

    /* ── Section cards (used inside tabs) ── */
    .jd-card {
        background: var(--rb-card); border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius); overflow: hidden;
        box-shadow: var(--rb-shadow); margin-bottom: 1rem;
    }
    .jd-card-head {
        display: flex; align-items: center; gap: .6rem;
        padding: .85rem 1.25rem; border-bottom: 1px solid var(--rb-border);
    }
    .jd-card-head i { color: var(--rb-brand); font-size: 1.1rem; }
    .jd-card-head h4 { font-size: .92rem; font-weight: 700; margin: 0; flex: 1; }
    .jd-card-body { padding: 1.25rem; }

    /* Key-value rows */
    .jd-kv { display: flex; padding: .5rem 0; border-bottom: 1px solid #f1f5f9; font-size: .86rem; }
    .jd-kv:last-child { border-bottom: none; }
    .jd-kv-label { width: 150px; flex-shrink: 0; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--rb-text-3); padding-top: .15rem; }
    .jd-kv-value { flex: 1; color: var(--rb-text); font-weight: 500; }

    /* Grid helpers */
    .jd-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .jd-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    @media (max-width: 767.98px) {
        .jd-grid-2, .jd-grid-3 { grid-template-columns: 1fr; }
    }

    /* ── Table ── */
    .jd-table { width: 100%; border-collapse: collapse; }
    .jd-table th {
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--rb-text-3);
        padding: .65rem .75rem; border-bottom: 1px solid var(--rb-border);
        text-align: left;
    }
    .jd-table td { padding: .65rem .75rem; font-size: .85rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .jd-table tr:last-child td { border-bottom: none; }
    .jd-table .text-end { text-align: right; }
    .jd-table .fw-bold { font-weight: 700; }

    /* Timeline */
    .jd-timeline-item {
        display: flex; gap: .75rem; padding: .7rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .jd-timeline-item:last-child { border-bottom: none; }
    .jd-timeline-dot {
        width: 10px; height: 10px; border-radius: 50%;
        background: var(--rb-brand); margin-top: 5px; flex-shrink: 0;
    }
    .jd-timeline-body { flex: 1; }
    .jd-timeline-title { font-size: .85rem; font-weight: 600; }
    .jd-timeline-meta { font-size: .72rem; color: var(--rb-text-3); }

    /* Feedback Stars */
    .jd-stars { color: var(--rb-warning); font-size: 1rem; }

    /* Person card */
    .jd-person {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem; border-radius: 10px; background: var(--rb-bg);
        border: 1px solid var(--rb-border); margin-bottom: .5rem;
    }
    .jd-person-avatar {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, var(--rb-brand), var(--rb-brand-dark));
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .8rem; flex-shrink: 0;
    }
    .jd-person-name { font-weight: 700; font-size: .88rem; }
    .jd-person-meta { font-size: .75rem; color: var(--rb-text-3); }

    /* Empty state */
    .jd-empty {
        text-align: center; padding: 2rem 1rem; color: var(--rb-text-3); font-size: .85rem;
    }
    .jd-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .4; }

    /* Device card */
    .jd-device {
        border: 1px solid var(--rb-border); border-radius: var(--rb-radius-sm);
        padding: .85rem; background: var(--rb-bg); margin-bottom: .5rem;
    }
    .jd-device-title { font-weight: 700; font-size: .88rem; }
    .jd-device-sub { font-size: .78rem; color: var(--rb-text-3); }
</style>
@endpush

{{-- ═══════════════ STICKY TOP BAR ═══════════════ --}}
<div class="jd-page">
    <div class="jd-topbar">
        <div class="jd-topbar-inner">
            <div class="jd-topbar-left">
                <a href="{{ $listUrl }}" class="jd-back-btn" title="Back">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="jd-title-block">
                    <div class="jd-badge jd-badge-view"><i class="bi bi-eye"></i> {{ __('Viewing') }}</div>
                    <div class="jd-page-title">
                        <i class="{{ $entityIcon }}"></i>
                        {{ $entityLabel }}: {{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}
                    </div>
                    <ul class="jd-breadcrumb">
                        <li><a href="{{ $listUrl }}">{{ $isEstimate ? __('Estimates') : __('Jobs') }}</a></li>
                        <li class="sep"><i class="bi bi-chevron-right"></i></li>
                        <li class="current">{{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}</li>
                    </ul>
                </div>
            </div>
            <div class="jd-topbar-right">
                <a href="javascript:window.print()" class="jd-btn jd-btn-outline">
                    <i class="bi bi-printer"></i> {{ __('Print') }}
                </a>
                <a href="{{ $editUrl }}" class="jd-btn jd-btn-outline">
                    <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
                </a>
                <a href="{{ $listUrl }}" class="jd-btn jd-btn-primary">
                    <i class="bi bi-arrow-left"></i> {{ __('Back to List') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════ OVERVIEW RIBBON (KPI cards) ═══════════════ --}}
    <div class="jd-ribbon">
        <div class="jd-ribbon-cards">
            {{-- Status --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Status') }}</div>
                @php
                    $statusSlug = $record?->status_slug ?? ($record?->status ?? 'open');
                    $statusClass = match (true) {
                        in_array($statusSlug, ['completed','delivered','approved']) => 'jd-status-completed',
                        in_array($statusSlug, ['cancelled','rejected']) => 'jd-status-cancelled',
                        default => 'jd-status-open',
                    };
                @endphp
                <div class="jd-status-pill {{ $statusClass }}">
                    <i class="bi bi-circle-fill" style="font-size:.45rem"></i>
                    {{ strtoupper(str_replace('_', ' ', $statusSlug)) }}
                </div>
            </div>
            {{-- Grand Total --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Grand Total') }}</div>
                <div class="jd-kpi-value">{{ $formatMoney($totals['total_cents'] ?? $totals['grand_total_cents'] ?? null) }} {{ $currency }}</div>
            </div>
            {{-- Balance --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Balance Due') }}</div>
                <div class="jd-kpi-value" style="color: var(--rb-danger);">{{ $formatMoney($totals['balance_cents'] ?? null) }} {{ $currency }}</div>
            </div>
            {{-- Customer --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Customer') }}</div>
                <div class="jd-kpi-value" style="font-size:.92rem">{{ $customer?->name ?? '—' }}</div>
                <div class="jd-kpi-sub">{{ $customer?->email ?? '' }}</div>
            </div>
            {{-- Devices --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Devices') }}</div>
                <div class="jd-kpi-value">{{ count($jobDevices) }}</div>
            </div>
            {{-- Payment Status --}}
            <div class="jd-kpi-card">
                <div class="jd-kpi-label">{{ __('Payment') }}</div>
                <div class="jd-kpi-value" style="font-size:.92rem">{{ strtoupper(str_replace('_', ' ', $record?->payment_status_slug ?? '—')) }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ TAB STRIP ═══════════════ --}}
    <div class="jd-tabs-wrapper" x-data="{ tab: 'overview' }">
        <div class="jd-tabs">
            <button class="jd-tab" :class="tab === 'overview' && 'active'" @click="tab = 'overview'">
                <i class="bi bi-grid"></i> {{ __('Overview') }}
            </button>
            <button class="jd-tab" :class="tab === 'technicians' && 'active'" @click="tab = 'technicians'">
                <i class="bi bi-people"></i> {{ __('Technicians & Time Logs') }}
                <span class="jd-tab-badge">{{ $technicians->count() + count($jobTimelogs) }}</span>
            </button>
            <button class="jd-tab" :class="tab === 'payments' && 'active'" @click="tab = 'payments'">
                <i class="bi bi-credit-card-2-front"></i> {{ __('Payments') }}
                <span class="jd-tab-badge">{{ count($jobPayments) }}</span>
            </button>
            <button class="jd-tab" :class="tab === 'expenses' && 'active'" @click="tab = 'expenses'">
                <i class="bi bi-receipt"></i> {{ __('Expenses') }}
                <span class="jd-tab-badge">{{ count($jobExpenses) }}</span>
            </button>
            <button class="jd-tab" :class="tab === 'history' && 'active'" @click="tab = 'history'">
                <i class="bi bi-clock-history"></i> {{ __('History') }}
                <span class="jd-tab-badge">{{ count($jobEvents) }}</span>
            </button>
            <button class="jd-tab" :class="tab === 'feedback' && 'active'" @click="tab = 'feedback'">
                <i class="bi bi-star"></i> {{ __('Feedback') }}
                <span class="jd-tab-badge">{{ count($jobFeedback) }}</span>
            </button>
        </div>

        {{-- ═══════════════ TAB PANELS ═══════════════ --}}
        <div class="jd-content" style="padding-top:1.25rem">

            {{-- ── OVERVIEW TAB ── --}}
            <div x-show="tab === 'overview'" x-cloak>
                <div class="jd-grid-2">
                    {{-- Left: Job Info + Devices --}}
                    <div>
                        <div class="jd-card">
                            <div class="jd-card-head">
                                <i class="bi bi-clipboard-data"></i>
                                <h4>{{ $entityLabel }} {{ __('Details') }}</h4>
                            </div>
                            <div class="jd-card-body">
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Case #') }}</div><div class="jd-kv-value fw-bold">{{ $record?->case_number ?? '—' }}</div></div>
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Title') }}</div><div class="jd-kv-value">{{ $record?->title ?? '—' }}</div></div>
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Pickup') }}</div><div class="jd-kv-value">{{ $record?->pickup_date ?? '—' }}</div></div>
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Delivery') }}</div><div class="jd-kv-value">{{ $record?->delivery_date ?? '—' }}</div></div>
                                @if ($record?->case_detail)
                                <div class="jd-kv" style="flex-direction:column; gap:.25rem;">
                                    <div class="jd-kv-label" style="width:auto">{{ __('Notes') }}</div>
                                    <div class="jd-kv-value" style="white-space:pre-wrap; line-height:1.4;">{{ $record->case_detail }}</div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Devices --}}
                        <div class="jd-card">
                            <div class="jd-card-head">
                                <i class="bi bi-phone"></i>
                                <h4>{{ __('Devices') }} <span style="font-size:.75rem; color:var(--rb-text-3); font-weight:400;">({{ count($jobDevices) }})</span></h4>
                            </div>
                            <div class="jd-card-body">
                                @forelse ($jobDevices as $d)
                                    <div class="jd-device">
                                        <div class="jd-device-title">{{ $d->label_snapshot ?? __('Device') }}</div>
                                        <div class="jd-device-sub">{{ __('Serial') }}: {{ $d->serial_snapshot ?? '—' }} &middot; {{ __('PIN') }}: {{ $d->pin_snapshot ?? '—' }}</div>
                                        @if ($d->notes_snapshot)
                                            <div style="font-size:.78rem; color:var(--rb-text-2); margin-top:.4rem; white-space:pre-wrap;">{{ $d->notes_snapshot }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="jd-empty"><i class="bi bi-phone"></i>{{ __('No devices attached') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Right: Customer + Line Items + Financial --}}
                    <div>
                        {{-- Customer --}}
                        <div class="jd-card">
                            <div class="jd-card-head">
                                <i class="bi bi-person-badge"></i>
                                <h4>{{ __('Customer') }}</h4>
                            </div>
                            <div class="jd-card-body">
                                @if ($customer)
                                    <div class="jd-person">
                                        <div class="jd-person-avatar">{{ strtoupper(mb_substr($customer->name ?? '?', 0, 2)) }}</div>
                                        <div>
                                            <div class="jd-person-name">{{ $customer->name }}</div>
                                            <div class="jd-person-meta">{{ $customer->email ?? '' }} {{ $customer->phone ? '· ' . $customer->phone : '' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="jd-empty"><i class="bi bi-person"></i>{{ __('No customer assigned') }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Line Items Summary --}}
                        <div class="jd-card">
                            <div class="jd-card-head">
                                <i class="bi bi-list-check"></i>
                                <h4>{{ __('Line Items') }}</h4>
                            </div>
                            <div class="jd-card-body" style="padding:0;">
                                @if (count($jobItems) > 0)
                                    <table class="jd-table">
                                        <thead><tr>
                                            <th>{{ __('Item') }}</th>
                                            <th class="text-end">{{ __('Qty') }}</th>
                                            <th class="text-end">{{ __('Total') }}</th>
                                        </tr></thead>
                                        <tbody>
                                        @foreach ($jobItems as $item)
                                            @php
                                                $qty = max(1, (int)($item->qty ?? 1));
                                                $unit = (int)($item->unit_price_amount_cents ?? 0);
                                                $lineTotal = $qty * $unit;
                                                if (($item->item_type ?? null) === 'discount') $lineTotal = 0 - $lineTotal;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold" style="font-size:.85rem;">{{ $item->name_snapshot }}</div>
                                                    <div style="font-size:.72rem; color:var(--rb-text-3); text-transform:uppercase;">{{ $item->item_type ?? '' }}</div>
                                                </td>
                                                <td class="text-end">{{ $qty }}</td>
                                                <td class="text-end fw-bold">{{ $formatMoney($lineTotal) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="jd-empty"><i class="bi bi-list-check"></i>{{ __('No line items') }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Financial Summary --}}
                        <div class="jd-card">
                            <div class="jd-card-head">
                                <i class="bi bi-calculator"></i>
                                <h4>{{ __('Financial Summary') }}</h4>
                            </div>
                            <div class="jd-card-body">
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Subtotal') }}</div><div class="jd-kv-value">{{ $formatMoney($totals['subtotal_cents'] ?? null) }} {{ $currency }}</div></div>
                                <div class="jd-kv"><div class="jd-kv-label">{{ __('Tax') }}</div><div class="jd-kv-value">{{ $formatMoney($totals['tax_cents'] ?? null) }} {{ $currency }}</div></div>
                                <div class="jd-kv" style="border-top:2px solid var(--rb-text); margin-top:.5rem; padding-top:.75rem;">
                                    <div class="jd-kv-label" style="font-size:.78rem;">{{ __('Grand Total') }}</div>
                                    <div class="jd-kv-value fw-bold" style="font-size:1.1rem;">{{ $formatMoney($totals['total_cents'] ?? $totals['grand_total_cents'] ?? null) }} {{ $currency }}</div>
                                </div>
                                @if (isset($totals['balance_cents']))
                                <div class="jd-kv">
                                    <div class="jd-kv-label">{{ __('Balance Due') }}</div>
                                    <div class="jd-kv-value fw-bold" style="color:var(--rb-danger);">{{ $formatMoney($totals['balance_cents']) }} {{ $currency }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── TECHNICIANS & TIME LOGS TAB ── --}}
            <div x-show="tab === 'technicians'" x-cloak>
                <div class="jd-grid-2">
                    {{-- Assigned Technicians --}}
                    <div class="jd-card">
                        <div class="jd-card-head">
                            <i class="bi bi-people-fill"></i>
                            <h4>{{ __('Assigned Technicians') }}</h4>
                        </div>
                        <div class="jd-card-body">
                            @forelse ($technicians as $tech)
                                <div class="jd-person">
                                    <div class="jd-person-avatar" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                                        {{ strtoupper(mb_substr($tech->name ?? '?', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="jd-person-name">{{ $tech->name }}</div>
                                        <div class="jd-person-meta">{{ $tech->email ?? '' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="jd-empty"><i class="bi bi-people"></i>{{ __('No technicians assigned') }}</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Time Logs --}}
                    <div class="jd-card">
                        <div class="jd-card-head">
                            <i class="bi bi-stopwatch"></i>
                            <h4>{{ __('Time Logs') }}</h4>
                        </div>
                        <div class="jd-card-body" style="padding:0;">
                            @if (count($jobTimelogs) > 0)
                                <table class="jd-table">
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
                                <div class="jd-empty"><i class="bi bi-stopwatch"></i>{{ __('No time logs recorded') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── PAYMENTS TAB ── --}}
            <div x-show="tab === 'payments'" x-cloak>
                <div class="jd-card">
                    <div class="jd-card-head">
                        <i class="bi bi-credit-card-2-front"></i>
                        <h4>{{ __('Payment Records') }}</h4>
                    </div>
                    <div class="jd-card-body" style="padding:0;">
                        @if (count($jobPayments) > 0)
                            <table class="jd-table">
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
                                        <td><span class="jd-status-pill jd-status-open">{{ strtoupper($pmt->method ?? 'N/A') }}</span></td>
                                        <td style="font-family:monospace; font-size:.8rem;">{{ $pmt->reference ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($pmt->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem; color:var(--rb-text-2);">{{ $pmt->notes ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jd-empty"><i class="bi bi-credit-card"></i>{{ __('No payments recorded yet') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Payment Summary Sidebar --}}
                <div class="jd-grid-3" style="margin-top:1rem;">
                    <div class="jd-kpi-card">
                        <div class="jd-kpi-label">{{ __('Total Paid') }}</div>
                        <div class="jd-kpi-value" style="color:var(--rb-success);">{{ $formatMoney($totals['paid_cents'] ?? null) }} {{ $currency }}</div>
                    </div>
                    <div class="jd-kpi-card">
                        <div class="jd-kpi-label">{{ __('Grand Total') }}</div>
                        <div class="jd-kpi-value">{{ $formatMoney($totals['total_cents'] ?? $totals['grand_total_cents'] ?? null) }} {{ $currency }}</div>
                    </div>
                    <div class="jd-kpi-card">
                        <div class="jd-kpi-label">{{ __('Balance Due') }}</div>
                        <div class="jd-kpi-value" style="color:var(--rb-danger);">{{ $formatMoney($totals['balance_cents'] ?? null) }} {{ $currency }}</div>
                    </div>
                </div>
            </div>

            {{-- ── EXPENSES TAB ── --}}
            <div x-show="tab === 'expenses'" x-cloak>
                <div class="jd-card">
                    <div class="jd-card-head">
                        <i class="bi bi-receipt-cutoff"></i>
                        <h4>{{ __('Job Expenses') }}</h4>
                    </div>
                    <div class="jd-card-body" style="padding:0;">
                        @if (count($jobExpenses) > 0)
                            <table class="jd-table">
                                <thead><tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Added By') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobExpenses as $exp)
                                    <tr>
                                        <td>{{ $exp->expense_date ?? $exp->created_at ?? '—' }}</td>
                                        <td>
                                            <span class="jd-status-pill" style="background:var(--rb-warning-soft); color:#92400e;">
                                                {{ $exp->category?->name ?? $exp->category_name ?? '—' }}
                                            </span>
                                        </td>
                                        <td>{{ $exp->description ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($exp->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem;">{{ $exp->creator?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="jd-empty"><i class="bi bi-receipt"></i>{{ __('No expenses recorded') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── HISTORY TAB ── --}}
            <div x-show="tab === 'history'" x-cloak>
                <div class="jd-card">
                    <div class="jd-card-head">
                        <i class="bi bi-clock-history"></i>
                        <h4>{{ __('Activity Timeline') }}</h4>
                    </div>
                    <div class="jd-card-body">
                        @forelse ($jobEvents as $ev)
                            @php
                                $payload = is_array($ev->payload_json) ? $ev->payload_json : (is_string($ev->payload_json) ? json_decode($ev->payload_json, true) : []);
                                $evTitle = $payload['title'] ?? ($ev->event_type ?? 'Event');
                                $actorName = $ev->actor?->name ?? __('System');
                            @endphp
                            <div class="jd-timeline-item">
                                <div class="jd-timeline-dot"></div>
                                <div class="jd-timeline-body">
                                    <div class="jd-timeline-title">{{ $evTitle }}</div>
                                    <div class="jd-timeline-meta">
                                        {{ $actorName }} &middot; {{ $ev->created_at?->format('M d, Y H:i') ?? '' }}
                                    </div>
                                    @if (!empty($payload['message']))
                                        <div style="font-size:.82rem; color:var(--rb-text-2); margin-top:.25rem; white-space:pre-wrap;">{{ $payload['message'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="jd-empty"><i class="bi bi-clock-history"></i>{{ __('No activity recorded yet') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ── FEEDBACK TAB ── --}}
            <div x-show="tab === 'feedback'" x-cloak>
                <div class="jd-card">
                    <div class="jd-card-head">
                        <i class="bi bi-star-fill"></i>
                        <h4>{{ __('Customer Feedback') }}</h4>
                    </div>
                    <div class="jd-card-body">
                        @forelse ($jobFeedback as $fb)
                            <div style="border:1px solid var(--rb-border); border-radius:var(--rb-radius-sm); padding:1rem; margin-bottom:.75rem; background:var(--rb-bg);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
                                    <div class="jd-stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= ($fb->rating ?? 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                        <span style="font-size:.82rem; font-weight:700; color:var(--rb-text); margin-left:.5rem;">{{ $fb->rating ?? 0 }}/5</span>
                                    </div>
                                    <span style="font-size:.72rem; color:var(--rb-text-3);">{{ $fb->created_at?->format('M d, Y') ?? '' }}</span>
                                </div>
                                @if ($fb->comment)
                                    <div style="font-size:.85rem; color:var(--rb-text-2); white-space:pre-wrap; line-height:1.5;">{{ $fb->comment }}</div>
                                @endif
                                <div style="font-size:.75rem; color:var(--rb-text-3); margin-top:.5rem;">
                                    {{ __('By') }}: {{ $fb->customer?->name ?? $fb->author_name ?? __('Customer') }}
                                </div>
                            </div>
                        @empty
                            <div class="jd-empty"><i class="bi bi-star"></i>{{ __('No feedback received yet') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

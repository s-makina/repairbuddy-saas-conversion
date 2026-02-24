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
    $technicians = $isEstimate
        ? collect([$record?->assignedTechnician])->filter()
        : ($record?->technicians ?? collect());

    // Items — estimates pass categorised arrays; jobs pass a flat collection
    $jobItems       = is_iterable($jobItems ?? null) ? collect($jobItems) : collect();
    $jobDevices     = is_iterable($jobDevices ?? null) ? $jobDevices : ($isEstimate ? ($devices ?? []) : []);
    $jobEvents      = is_iterable($jobEvents ?? null) ? $jobEvents : ($isEstimate ? ($events ?? []) : []);
    $jobAttachments = is_iterable($jobAttachments ?? null) ? $jobAttachments : ($isEstimate ? ($attachments ?? []) : []);
    $jobTimelogs    = is_iterable($jobTimelogs ?? null) ? $jobTimelogs : [];
    $jobPayments    = is_iterable($jobPayments ?? null) ? $jobPayments : [];
    $jobExpenses    = is_iterable($jobExpenses ?? null) ? $jobExpenses : [];
    $jobFeedback    = is_iterable($jobFeedback ?? null) ? $jobFeedback : [];
    $paymentStatuses = $paymentStatuses ?? collect();

    // For estimates: merge categorised items if flat jobItems is empty
    if ($isEstimate && $jobItems->isEmpty()) {
        $flatItems = collect($productItems ?? [])
            ->concat($partItems ?? [])
            ->concat($serviceItems ?? [])
            ->concat($extraItems ?? []);
        $jobItems = $flatItems;
    }

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';
    $listUrl    = $isEstimate
        ? route('tenant.estimates.index', ['business' => $tenantSlug])
        : (route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs');
    $editUrl    = ($record && $tenantSlug)
        ? ($isEstimate
            ? route('tenant.estimates.edit', ['business' => $tenantSlug, 'estimateId' => $record->id])
            : route('tenant.jobs.edit', ['business' => $tenantSlug, 'jobId' => $record->id]))
        : '#';

    // Estimate-specific state
    $estStatus    = $isEstimate ? ($record?->status ?? 'pending') : null;
    $estIsPending  = $estStatus === 'pending';
    $estIsApproved = $estStatus === 'approved';
    $estIsRejected = $estStatus === 'rejected';
    $convertedJobUrl = null;
    if ($isEstimate && $record?->converted_job_id && $tenantSlug) {
        $convertedJobUrl = route('tenant.jobs.show', ['business' => $tenantSlug, 'jobId' => $record->converted_job_id]);
    }

    $currencyCode = strtoupper(is_string($tenant?->currency ?? null) && $tenant->currency !== '' ? $tenant->currency : 'USD');
    try {
        $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currencyCode);
        $currencySymbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($currencyCode . ' ');
    } catch (\Exception $e) {
        $currencySymbol = $currencyCode . ' ';
    }

    $formatMoney = function ($cents) use ($currencySymbol) {
        if ($cents === null) return '—';
        return $currencySymbol . number_format(((int) $cents) / 100, 2, '.', ',');
    };
@endphp

@push('page-styles')
<style>
    /* ═══════════════════════════════════════════════════════
       Mockup 2 — Accordion Detail Layout
       Re-uses Design B tokens from job-form exactly.
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
    .ja-page {
        background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
        min-height: 100vh;
        margin: -1rem -1rem 0 -1rem;
        padding: 0;
        width: calc(100% + 2rem);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: var(--rb-text);
    }

    /* ── Sticky top bar (matches job-form exactly) ── */
    .ja-topbar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--rb-border);
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 1px 0 var(--rb-border), 0 2px 8px rgba(14,165,233,.04);
    }
    .ja-topbar-inner {
        max-width: 1440px; margin: 0 auto;
        padding: .65rem 2rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }
    .ja-topbar-left { display: flex; align-items: center; gap: 1rem; }
    .ja-topbar-right { display: flex; gap: .5rem; }

    .ja-back-btn {
        width: 34px; height: 34px; border-radius: 10px;
        border: 1px solid var(--rb-border); background: #fff; color: var(--rb-text-2);
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: .88rem; transition: all .15s;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .ja-back-btn:hover { background: var(--rb-bg); color: var(--rb-brand); border-color: var(--rb-brand); }

    .ja-title-block .ja-badge {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .65rem; font-weight: 700; padding: .15rem .55rem;
        border-radius: 999px; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem;
    }
    .ja-badge-view { background: var(--rb-brand-soft); color: var(--rb-brand-dark); border: 1px solid #bae6fd; }
    .ja-title-block h1 {
        display: flex; align-items: center; gap: .5rem;
        font-size: 1rem; font-weight: 800; margin: 0 0 .15rem;
    }
    .ja-title-block h1 i { color: var(--rb-brand); font-size: .9rem; }
    .ja-breadcrumb {
        display: flex; align-items: center; gap: .2rem;
        font-size: .72rem; color: var(--rb-text-3); margin: 0; list-style: none; padding: 0;
    }
    .ja-breadcrumb a { color: var(--rb-text-3); text-decoration: none; }
    .ja-breadcrumb a:hover { color: var(--rb-brand); }
    .ja-breadcrumb .sep { font-size: .6rem; opacity: .4; }
    .ja-breadcrumb .cur { color: var(--rb-text-2); font-weight: 600; }

    .ja-btn {
        padding: .5rem 1.1rem; border-radius: var(--rb-radius-sm);
        font-size: .84rem; font-weight: 600; cursor: pointer;
        border: none; transition: all .15s;
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
    }
    .ja-btn-outline { background: transparent; color: var(--rb-text-2); border: 1px solid var(--rb-border); }
    .ja-btn-outline:hover { background: var(--rb-bg); color: var(--rb-text); }
    .ja-btn-primary { background: var(--rb-brand); color: #fff; }
    .ja-btn-primary:hover { background: var(--rb-brand-dark); color: #fff; }

    /* ── 2-column layout (matches job-form) ── */
    .ja-layout {
        display: flex; gap: 1.5rem;
        max-width: 1400px; margin: 0 auto;
        padding: 1.5rem 2rem; align-items: flex-start;
    }
    .ja-main { flex: 1; min-width: 0; }
    .ja-side { width: 340px; flex-shrink: 0; }
    .ja-side .ja-sticky { position: sticky; top: 5rem; }
    @media (max-width: 1023.98px) {
        .ja-layout { flex-direction: column; }
        .ja-side { width: 100%; }
    }

    /* ── Collapsible Sections (matches .jf-section exactly) ── */
    .ja-section {
        background: var(--rb-card); border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius); margin-bottom: 1rem;
        overflow: hidden; box-shadow: var(--rb-shadow);
    }
    .ja-section-head {
        display: flex; align-items: center; gap: .625rem;
        padding: .75rem 1rem; cursor: pointer; user-select: none;
        transition: background .12s;
    }
    .ja-section-head:hover { background: var(--rb-bg); }
    .ja-section-icon {
        width: 28px; height: 28px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .88rem; flex-shrink: 0;
    }
    .ja-section-head h3 { font-size: .92rem; font-weight: 700; flex: 1; margin: 0; }
    .ja-section-head .ja-tag {
        font-size: .68rem; font-weight: 700; padding: .15rem .5rem; border-radius: 999px;
    }
    .ja-section-head .ja-chevron {
        font-size: .75rem; color: var(--rb-text-3); transition: transform .25s;
    }
    .ja-section-body {
        padding: 1rem 1.25rem; border-top: 1px solid var(--rb-border);
    }

    /* Section color variants (same palette as job-form) */
    .ja-icon-blue   { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }
    .ja-icon-green  { background: var(--rb-success-soft); color: #16a34a; }
    .ja-icon-yellow { background: var(--rb-warning-soft); color: #92400e; }
    .ja-icon-red    { background: var(--rb-danger-soft); color: var(--rb-danger); }
    .ja-icon-violet { background: #ede9fe; color: #6d28d9; }
    .ja-icon-gray   { background: #f1f5f9; color: var(--rb-text-2); }

    .ja-tag-blue   { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }
    .ja-tag-green  { background: var(--rb-success-soft); color: #16a34a; }
    .ja-tag-yellow { background: var(--rb-warning-soft); color: #92400e; }
    .ja-tag-gray   { background: #f1f5f9; color: var(--rb-text-2); }

    /* ── Key-value rows ── */
    .ja-kv { display: flex; padding: .5rem 0; border-bottom: 1px solid #f1f5f9; font-size: .86rem; }
    .ja-kv:last-child { border-bottom: none; }
    .ja-kv-label {
        width: 140px; flex-shrink: 0; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .03em; color: var(--rb-text-3); padding-top: .15rem;
    }
    .ja-kv-value { flex: 1; color: var(--rb-text); font-weight: 500; }

    /* ── Table ── */
    .ja-table { width: 100%; border-collapse: collapse; }
    .ja-table th {
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--rb-text-3);
        padding: .65rem .75rem; border-bottom: 1px solid var(--rb-border); text-align: left;
    }
    .ja-table td { padding: .65rem .75rem; font-size: .85rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .ja-table tr:last-child td { border-bottom: none; }
    .ja-table .text-end { text-align: right; }
    .ja-table .fw-bold { font-weight: 700; }

    /* ── Person card ── */
    .ja-person {
        display: flex; align-items: center; gap: .75rem;
        padding: .6rem; border-radius: 8px; background: var(--rb-bg);
        border: 1px solid var(--rb-border); margin-bottom: .4rem;
    }
    .ja-person-avatar {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .78rem; flex-shrink: 0; color: #fff;
    }
    .ja-person-name { font-weight: 700; font-size: .86rem; }
    .ja-person-meta { font-size: .72rem; color: var(--rb-text-3); }

    /* ── Timeline ── */
    .ja-tl-item { display: flex; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; }
    .ja-tl-item:last-child { border-bottom: none; }
    .ja-tl-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--rb-brand); margin-top: 6px; flex-shrink: 0; }
    .ja-tl-title { font-size: .84rem; font-weight: 600; }
    .ja-tl-meta { font-size: .72rem; color: var(--rb-text-3); }

    /* ── Status pill ── */
    .ja-status-pill {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .2rem .55rem; border-radius: 999px;
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
    }
    .ja-sp-open    { background: var(--rb-brand-soft); color: var(--rb-brand-dark); }
    .ja-sp-done    { background: var(--rb-success-soft); color: #16a34a; }
    .ja-sp-danger  { background: var(--rb-danger-soft); color: var(--rb-danger); }

    /* ── Device row ── */
    .ja-dev-row {
        display: flex; align-items: center; gap: .625rem;
        padding: .5rem .625rem; border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm); background: var(--rb-bg);
        margin-bottom: .375rem; font-size: .84rem;
    }
    .ja-dev-icon {
        width: 32px; height: 32px; border-radius: 8px;
        background: #dbeafe; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: .9rem;
    }
    .ja-dev-info { flex: 1; min-width: 0; }
    .ja-dev-info strong { font-size: .84rem; display: block; }
    .ja-dev-info span { font-size: .72rem; color: var(--rb-text-3); display: block; }

    /* ── Empty state ── */
    .ja-empty { text-align: center; padding: 1.5rem 1rem; color: var(--rb-text-3); font-size: .84rem; }
    .ja-empty i { font-size: 1.5rem; display: block; margin-bottom: .4rem; opacity: .4; }

    /* ── Stars ── */
    .ja-stars { color: var(--rb-warning); font-size: 1rem; }

    /* ── Sidebar KPI (matches job-form sidebar) ── */
    .ja-sidebar-card {
        background: var(--rb-card); border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius); box-shadow: var(--rb-shadow);
        margin-bottom: 1rem; overflow: hidden;
    }
    .ja-sidebar-head {
        display: flex; align-items: center; gap: .5rem;
        padding: .7rem 1rem; border-bottom: 1px solid var(--rb-border);
        font-size: .82rem; font-weight: 700;
    }
    .ja-sidebar-head i { color: var(--rb-brand); }
    .ja-sidebar-body { padding: 1rem; }

    .ja-sidebar-kpi {
        display: flex; justify-content: space-between; align-items: baseline;
        padding: .4rem 0; font-size: .85rem;
    }
    .ja-sidebar-kpi + .ja-sidebar-kpi { border-top: 1px solid #f1f5f9; }
    .ja-sidebar-kpi-label { color: var(--rb-text-3); font-size: .72rem; font-weight: 600; text-transform: uppercase; }
    .ja-sidebar-kpi-value { font-weight: 700; }

    .ja-sidebar-grand {
        border-top: 2px solid var(--rb-text); margin-top: .5rem; padding-top: .6rem;
        display: flex; justify-content: space-between; font-size: 1rem; font-weight: 800;
    }
</style>
@endpush

{{-- ═══════════════ STICKY TOP BAR ═══════════════ --}}
<div class="ja-page">

    {{-- Flash messages --}}
    @if (session('success') || session('error'))
    <div style="max-width:1440px; margin:0 auto; padding:.75rem 2rem 0;">
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-2" role="alert" style="border-radius:var(--rb-radius);">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-2" role="alert" style="border-radius:var(--rb-radius);">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>
    @endif

    {{-- Estimate status banners --}}
    @if ($isEstimate)
    <div style="max-width:1440px; margin:0 auto; padding:.5rem 2rem 0;">
        @if ($estIsApproved && $convertedJobUrl)
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; border-radius:var(--rb-radius); padding:.8rem 1.2rem; margin-bottom:.5rem; display:flex; align-items:center; gap:.75rem; font-size:.88rem;">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ __('This estimate was approved and converted to a repair job.') }}
                <a href="{{ $convertedJobUrl }}" style="font-weight:700; color:#166534;">{{ __('View Repair Job →') }}</a>
            </div>
        </div>
        @endif
        @if ($estIsRejected)
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:var(--rb-radius); padding:.8rem 1.2rem; margin-bottom:.5rem; display:flex; align-items:center; gap:.75rem; font-size:.88rem;">
            <i class="bi bi-x-circle-fill fs-5"></i>
            <div>{{ __('This estimate was rejected') }}
                @if ($record?->rejected_at) {{ __('on') }} {{ $record->rejected_at->format('M d, Y \a\t H:i') }} @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="ja-topbar">
        <div class="ja-topbar-inner">
            <div class="ja-topbar-left">
                <a href="{{ $listUrl }}" class="ja-back-btn" title="Back">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="ja-title-block">
                    <div class="ja-badge ja-badge-view"><i class="bi bi-eye"></i> {{ __('Detail View') }}</div>
                    <h1>
                        <i class="{{ $entityIcon }}"></i>
                        {{ $entityLabel }}: {{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}
                        @php
                            $statusSlug = $record?->status_slug ?? ($record?->status ?? 'open');
                            $spClass = match (true) {
                                in_array($statusSlug, ['completed','delivered','approved']) => 'ja-sp-done',
                                in_array($statusSlug, ['cancelled','rejected']) => 'ja-sp-danger',
                                default => 'ja-sp-open',
                            };
                        @endphp
                        <span class="ja-status-pill {{ $spClass }}">
                            <i class="bi bi-circle-fill" style="font-size:.4rem"></i>
                            {{ strtoupper(str_replace('_', ' ', $statusSlug)) }}
                        </span>
                    </h1>
                    <ul class="ja-breadcrumb">
                        <li><a href="{{ $listUrl }}">{{ $isEstimate ? __('Estimates') : __('Jobs') }}</a></li>
                        <li class="sep"><i class="bi bi-chevron-right"></i></li>
                        <li class="cur">{{ $record?->case_number ?? '#' . ($record?->id ?? '?') }}</li>
                    </ul>
                </div>
            </div>
            <div class="ja-topbar-right">
                <button type="button" class="ja-btn ja-btn-outline"
                    onclick="Livewire.dispatch('openDocumentPreview', { type: '{{ $isEstimate ? 'estimate' : 'job' }}', id: {{ $record?->id ?? 0 }} })">
                    <i class="bi bi-printer"></i>{{ __('Preview / Print') }}
                </button>
                @if ($isEstimate)
                    @if ($customer && $customer->email)
                    <button type="button" class="ja-btn ja-btn-outline" data-bs-toggle="modal" data-bs-target="#sendEstimateModal">
                        <i class="bi bi-envelope"></i>{{ __('Send') }}
                    </button>
                    @endif
                    @if ($estIsPending)
                    <a href="{{ $editUrl }}" class="ja-btn ja-btn-outline"><i class="bi bi-pencil-square"></i>{{ __('Edit') }}</a>
                    @endif
                @else
                    <a href="{{ $editUrl }}" class="ja-btn ja-btn-outline"><i class="bi bi-pencil-square"></i>{{ __('Edit') }}</a>
                @endif
                <a href="{{ $listUrl }}" class="ja-btn ja-btn-primary"><i class="bi bi-arrow-left"></i>{{ __('Back to List') }}</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════ 2-COLUMN LAYOUT ═══════════════ --}}
    <div class="ja-layout" x-data="{ open: { overview: true, devices: true, techs: true, timelogs: false, payments: false, expenses: false, history: false, feedback: false } }">

        {{-- ──────────── MAIN COLUMN ──────────── --}}
        <div class="ja-main">

            {{-- §1 — Overview / Job Details --}}
            <div class="ja-section">
                <div class="ja-section-head" @click="open.overview = !open.overview">
                    <div class="ja-section-icon ja-icon-blue"><i class="bi bi-clipboard-data"></i></div>
                    <h3>{{ $entityLabel }} {{ __('Details') }}</h3>
                    <span class="ja-tag ja-tag-blue">{{ $record?->case_number ?? '—' }}</span>
                    <i class="bi ja-chevron" :class="open.overview ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.overview" x-collapse>
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Case #') }}</div><div class="ja-kv-value" style="font-weight:700;">{{ $record?->case_number ?? '—' }}</div></div>
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Title') }}</div><div class="ja-kv-value">{{ $record?->title ?? '—' }}</div></div>
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Pickup') }}</div><div class="ja-kv-value">{{ $record?->pickup_date ?? '—' }}</div></div>
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Delivery') }}</div><div class="ja-kv-value">{{ $record?->delivery_date ?? '—' }}</div></div>
                    @if (!$isEstimate)
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Payment') }}</div><div class="ja-kv-value">{{ strtoupper(str_replace('_', ' ', $record?->payment_status_slug ?? '—')) }}</div></div>
                    @endif
                    @if ($isEstimate)
                    <div class="ja-kv">
                        <div class="ja-kv-label">{{ __('Status') }}</div>
                        <div class="ja-kv-value">
                            @php
                                $estBadgeClass = match ($estStatus) {
                                    'approved' => 'ja-sp-done',
                                    'rejected' => 'ja-sp-danger',
                                    default    => 'ja-sp-open',
                                };
                            @endphp
                            <span class="ja-status-pill {{ $estBadgeClass }}">{{ strtoupper($estStatus ?? 'pending') }}</span>
                        </div>
                    </div>
                    @if ($record?->sent_at)
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Sent') }}</div><div class="ja-kv-value">{{ $record->sent_at->format('M d, Y H:i') }}</div></div>
                    @endif
                    @if ($record?->rejected_at)
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Rejected At') }}</div><div class="ja-kv-value" style="color:var(--rb-danger);">{{ $record->rejected_at->format('M d, Y H:i') }}</div></div>
                    @endif
                    @endif
                    <div class="ja-kv"><div class="ja-kv-label">{{ __('Created') }}</div><div class="ja-kv-value">{{ $record?->created_at?->format('M d, Y H:i') ?? '—' }}</div></div>
                    @if ($record?->case_detail)
                    <div class="ja-kv" style="flex-direction:column; gap:.25rem;">
                        <div class="ja-kv-label" style="width:auto">{{ __('Internal Notes') }}</div>
                        <div class="ja-kv-value" style="white-space:pre-wrap; line-height:1.45;">{{ $record->case_detail }}</div>
                    </div>
                    @endif

                    {{-- Customer inline --}}
                    <div style="margin-top:.75rem; padding-top:.75rem; border-top:1px solid var(--rb-border);">
                        <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:var(--rb-text-3); margin-bottom:.4rem;">{{ __('Customer') }}</div>
                        @if ($customer)
                            <div class="ja-person">
                                <div class="ja-person-avatar" style="background:linear-gradient(135deg, var(--rb-brand), var(--rb-brand-dark));">{{ strtoupper(mb_substr($customer->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="ja-person-name">{{ $customer->name }}</div>
                                    <div class="ja-person-meta">{{ $customer->email ?? '' }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</div>
                                    @if (!empty($customer->company ?? null))<div class="ja-person-meta">{{ $customer->company }}</div>@endif
                                </div>
                            </div>
                        @else
                            <div class="ja-empty" style="padding:.5rem 0;"><i class="bi bi-person" style="font-size:1rem;"></i>{{ __('No customer assigned') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- §2 — Devices --}}
            <div class="ja-section">
                <div class="ja-section-head" @click="open.devices = !open.devices">
                    <div class="ja-section-icon ja-icon-violet"><i class="bi bi-phone"></i></div>
                    <h3>{{ __('Devices') }}</h3>
                    <span class="ja-tag ja-tag-gray">{{ count($jobDevices) }}</span>
                    <i class="bi ja-chevron" :class="open.devices ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.devices" x-collapse>
                    @forelse ($jobDevices as $d)
                        <div class="ja-dev-row">
                            <div class="ja-dev-icon"><i class="bi bi-laptop"></i></div>
                            <div class="ja-dev-info">
                                @if ($isEstimate)
                                <strong>{{ $d->label_snapshot ?? __('Device') }}</strong>
                                <span>{{ __('SN') }}: {{ $d->serial_snapshot ?? '—' }}{{ ($d->pin_snapshot ?? null) ? ' · ' . __('PIN') . ': ' . $d->pin_snapshot : '' }}</span>
                                @if ($d->notes_snapshot ?? null)
                                    <span style="color:var(--rb-text-2);">{{ $d->notes_snapshot }}</span>
                                @endif
                                @else
                                <strong>{{ $d->label_snapshot ?? ($d->customerDevice?->device?->brand?->name . ' ' . $d->customerDevice?->device?->name ?? __('Device')) }}</strong>
                                <span>{{ __('SN') }}: {{ $d->serial_snapshot ?? '—' }} &middot; {{ __('PIN') }}: {{ $d->pin_snapshot ?? '—' }}</span>
                                @if ($d->notes_snapshot ?? null)
                                    <span style="color:var(--rb-text-2);">{{ $d->notes_snapshot }}</span>
                                @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="ja-empty"><i class="bi bi-phone"></i>{{ __('No devices attached') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- §3 — Technicians & Time Logs (job only) --}}
            @if (!$isEstimate)
            <div class="ja-section">
                <div class="ja-section-head" @click="open.techs = !open.techs">
                    <div class="ja-section-icon ja-icon-green"><i class="bi bi-people-fill"></i></div>
                    <h3>{{ __('Technicians & Time Logs') }}</h3>
                    <span class="ja-tag ja-tag-green">{{ $technicians->count() }} {{ __('techs') }} · {{ count($jobTimelogs) }} {{ __('logs') }}</span>
                    <i class="bi ja-chevron" :class="open.techs ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.techs" x-collapse>
                    {{-- Technicians --}}
                    <div style="margin-bottom:1rem;">
                        <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:var(--rb-text-3); margin-bottom:.4rem;">{{ __('Assigned') }}</div>
                        @forelse ($technicians as $tech)
                            <div class="ja-person">
                                <div class="ja-person-avatar" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">{{ strtoupper(mb_substr($tech->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="ja-person-name">{{ $tech->name }}</div>
                                    <div class="ja-person-meta">{{ $tech->email ?? '' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="ja-empty" style="padding:.5rem 0;"><i class="bi bi-people" style="font-size:1rem;"></i>{{ __('No technicians assigned') }}</div>
                        @endforelse
                    </div>

                    {{-- Time Logs --}}
                    <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:var(--rb-text-3); margin-bottom:.4rem;">{{ __('Time Logs') }}</div>
                    @if (count($jobTimelogs) > 0)
                        <div style="overflow-x:auto;">
                            <table class="ja-table">
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
                        </div>
                    @else
                        <div class="ja-empty"><i class="bi bi-stopwatch"></i>{{ __('No time logs recorded') }}</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- §4 — Payments (job only) --}}
            @if (!$isEstimate)
            <div class="ja-section">
                <div class="ja-section-head" @click="open.payments = !open.payments">
                    <div class="ja-section-icon ja-icon-green"><i class="bi bi-credit-card-2-front"></i></div>
                    <h3>{{ __('Payments') }}</h3>
                    <span class="ja-tag ja-tag-green">{{ count($jobPayments) }}</span>
                    <button type="button" class="ja-btn ja-btn-primary" style="margin-left:auto; padding:.3rem .75rem; font-size:.76rem;"
                        data-bs-toggle="modal" data-bs-target="#addPaymentModal"
                        @click.stop>
                        <i class="bi bi-plus-circle"></i> {{ __('Add Payment') }}
                    </button>
                    <i class="bi ja-chevron" :class="open.payments ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.payments" x-collapse>
                    {{-- Payment summary bar --}}
                    @php
                        $paidTotal   = collect($jobPayments)->sum('amount_cents');
                        $grandTotal  = $totals['grand_total_cents'] ?? $totals['total_cents'] ?? 0;
                        $balanceDue  = $grandTotal - $paidTotal;
                    @endphp
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:.75rem; padding:.6rem .75rem; background:var(--rb-bg); border-radius:var(--rb-radius-sm); border:1px solid var(--rb-border);">
                        <div style="flex:1; min-width:120px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Grand Total') }}</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--rb-text);">{{ $formatMoney($grandTotal) }}</div>
                        </div>
                        <div style="flex:1; min-width:120px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Total Paid') }}</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--rb-success);">{{ $formatMoney($paidTotal) }}</div>
                        </div>
                        <div style="flex:1; min-width:120px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Balance Due') }}</div>
                            <div style="font-size:1rem; font-weight:800; color:{{ $balanceDue > 0 ? 'var(--rb-danger)' : 'var(--rb-success)' }};">{{ $formatMoney($balanceDue) }}</div>
                        </div>
                    </div>

                    @if (count($jobPayments) > 0)
                        <div style="overflow-x:auto;">
                            <table class="ja-table">
                                <thead><tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Transaction ID') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Receiver') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($jobPayments as $pmt)
                                    <tr>
                                        <td>{{ ($pmt->paid_at ?? $pmt->created_at)?->format('M d, Y H:i') ?? '—' }}</td>
                                        <td><span class="ja-status-pill ja-sp-open">{{ strtoupper($pmt->method ?? 'N/A') }}</span></td>
                                        <td><span class="ja-status-pill ja-sp-done">{{ strtoupper($pmt->payment_status ?? 'N/A') }}</span></td>
                                        <td style="font-family:monospace; font-size:.8rem;">{{ $pmt->transaction_id ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($pmt->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem;">{{ $pmt->receiver?->name ?? '—' }}</td>
                                        <td style="font-size:.78rem; color:var(--rb-text-2);">{{ $pmt->notes ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ja-empty"><i class="bi bi-credit-card"></i>{{ __('No payments recorded yet') }}</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- §5 — Expenses (job only) --}}
            @if (!$isEstimate)
            <div class="ja-section">
                <div class="ja-section-head" @click="open.expenses = !open.expenses">
                    <div class="ja-section-icon ja-icon-yellow"><i class="bi bi-receipt-cutoff"></i></div>
                    <h3>{{ __('Job Expenses') }}</h3>
                    <span class="ja-tag ja-tag-yellow">{{ count($jobExpenses) }}</span>
                    <i class="bi ja-chevron" :class="open.expenses ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.expenses" x-collapse>
                    @if (count($jobExpenses) > 0)
                        <div style="overflow-x:auto;">
                            <table class="ja-table">
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
                                        <td><span class="ja-status-pill" style="background:var(--rb-warning-soft); color:#92400e;">{{ $exp->category?->name ?? $exp->category_name ?? '—' }}</span></td>
                                        <td>{{ $exp->description ?? '—' }}</td>
                                        <td class="text-end fw-bold">{{ $formatMoney($exp->amount_cents ?? null) }} {{ $currency }}</td>
                                        <td style="font-size:.78rem;">{{ $exp->creator?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ja-empty"><i class="bi bi-receipt"></i>{{ __('No expenses recorded') }}</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- §6 — History --}}
            <div class="ja-section">
                <div class="ja-section-head" @click="open.history = !open.history">
                    <div class="ja-section-icon ja-icon-gray"><i class="bi bi-clock-history"></i></div>
                    <h3>{{ $isEstimate ? __('Estimate History') : __('Job History') }}</h3>
                    <span class="ja-tag ja-tag-gray">{{ count($jobEvents) }}</span>
                    <i class="bi ja-chevron" :class="open.history ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.history" x-collapse>
                    @forelse ($jobEvents as $ev)
                        @php
                            $payload = is_array($ev->payload_json) ? $ev->payload_json : (is_string($ev->payload_json) ? json_decode($ev->payload_json, true) : []);
                            $evTitle = $payload['title'] ?? ($ev->event_type ?? 'Event');
                            $actorName = $ev->actor?->name ?? __('System');
                        @endphp
                        <div class="ja-tl-item">
                            <div class="ja-tl-dot"></div>
                            <div class="flex-grow-1">
                                <div class="ja-tl-title">{{ $evTitle }}</div>
                                <div class="ja-tl-meta">{{ $actorName }} &middot; {{ $ev->created_at?->format('M d, Y H:i') ?? '' }}</div>
                                @if (!empty($payload['message']))
                                    <div style="font-size:.8rem; color:var(--rb-text-2); margin-top:.2rem; white-space:pre-wrap;">{{ $payload['message'] }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="ja-empty"><i class="bi bi-clock-history"></i>{{ __('No activity recorded') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- §7 — Feedback --}}
            <div class="ja-section">
                <div class="ja-section-head" @click="open.feedback = !open.feedback">
                    <div class="ja-section-icon ja-icon-yellow"><i class="bi bi-star-fill"></i></div>
                    <h3>{{ __('Customer Feedback') }}</h3>
                    <span class="ja-tag ja-tag-yellow">{{ count($jobFeedback) }}</span>
                    <i class="bi ja-chevron" :class="open.feedback ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div class="ja-section-body" x-show="open.feedback" x-collapse>
                    @forelse ($jobFeedback as $fb)
                        <div style="border:1px solid var(--rb-border); border-radius:var(--rb-radius-sm); padding:.85rem; margin-bottom:.6rem; background:var(--rb-bg);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                                <div class="ja-stars">
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
                        <div class="ja-empty"><i class="bi bi-star"></i>{{ __('No feedback received') }}</div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- ──────────── SIDEBAR ──────────── --}}
        <div class="ja-side">
            <div class="ja-sticky">

                {{-- Financial Summary --}}
                <div class="ja-sidebar-card">
                    <div class="ja-sidebar-head"><i class="bi bi-calculator"></i> {{ __('Financial Summary') }}</div>
                    <div class="ja-sidebar-body">
                        @if ($isEstimate)
                        {{-- Estimate: use categorised totals from controller --}}
                        @php
                            $pt = $totals['products'] ?? ['subtotal' => 0]; $pa = $totals['parts'] ?? ['subtotal' => 0];
                            $sv = $totals['services'] ?? ['subtotal' => 0]; $ex = $totals['extras']   ?? ['subtotal' => 0];
                        @endphp
                        @if ($pt['subtotal'] > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Products') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($pt['subtotal']) }}</span></div>
                        @endif
                        @if ($pa['subtotal'] > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Parts') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($pa['subtotal']) }}</span></div>
                        @endif
                        @if ($sv['subtotal'] > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Services') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($sv['subtotal']) }}</span></div>
                        @endif
                        @if ($ex['subtotal'] > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Extras') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($ex['subtotal']) }}</span></div>
                        @endif
                        @else
                        {{-- Job: use controller-computed per-category totals --}}
                        @php
                            $svcT  = $totals['services']  ?? ['subtotal' => 0];
                            $prtT  = $totals['parts']     ?? ['subtotal' => 0];
                            $feeT  = $totals['fees']      ?? ['subtotal' => 0];
                            $dscT  = $totals['discounts'] ?? ['subtotal' => 0];
                        @endphp
                        @if (($svcT['subtotal'] ?? 0) > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Services') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($svcT['subtotal']) }}</span></div>
                        @endif
                        @if (($prtT['subtotal'] ?? 0) > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Parts') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($prtT['subtotal']) }}</span></div>
                        @endif
                        @if (($feeT['subtotal'] ?? 0) > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Fees / Extras') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($feeT['subtotal']) }}</span></div>
                        @endif
                        @if (($dscT['subtotal'] ?? 0) > 0)
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Discounts') }}</span><span class="ja-sidebar-kpi-value" style="color:var(--rb-danger);">-{{ $formatMoney($dscT['subtotal']) }}</span></div>
                        @endif
                        @endif
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ __('Subtotal') }}</span><span class="ja-sidebar-kpi-value">{{ $formatMoney($totals['subtotal_cents'] ?? null) }}</span></div>
                        @if (($totals['tax_cents'] ?? 0) > 0)
                        @php
                            $taxLabel = __('Tax');
                            if (!empty($totals['tax_name'])) $taxLabel .= ' (' . e($totals['tax_name']);
                            if (!empty($totals['tax_rate'])) $taxLabel .= ' ' . rtrim(rtrim(number_format((float)$totals['tax_rate'], 2), '0'), '.') . '%';
                            if (!empty($totals['tax_name'])) $taxLabel .= ')';
                            $taxModeTag = ($totals['tax_mode'] ?? 'exclusive') === 'inclusive' ? __('incl.') : __('excl.');
                        @endphp
                        <div class="ja-sidebar-kpi"><span class="ja-sidebar-kpi-label">{{ $taxLabel }} <small class="text-muted">({{ $taxModeTag }})</small></span><span class="ja-sidebar-kpi-value">{{ $formatMoney($totals['tax_cents']) }}</span></div>
                        @endif
                        <div class="ja-sidebar-grand">
                            <span>{{ __('Grand Total') }}</span>
                            <span>{{ $formatMoney($totals['grand_total_cents'] ?? $totals['total_cents'] ?? null) }} {{ $currency }}</span>
                        </div>
                        @if (isset($totals['balance_cents']))
                        <div class="ja-sidebar-kpi" style="margin-top:.4rem;">
                            <span class="ja-sidebar-kpi-label">{{ __('Total Paid') }}</span>
                            <span class="ja-sidebar-kpi-value" style="color:var(--rb-success);">{{ $formatMoney($totals['paid_cents'] ?? 0) }}</span>
                        </div>
                        <div class="ja-sidebar-kpi">
                            <span class="ja-sidebar-kpi-label">{{ __('Balance Due') }}</span>
                            <span class="ja-sidebar-kpi-value" style="color:{{ ($totals['balance_cents'] ?? 0) > 0 ? 'var(--rb-danger)' : 'var(--rb-success)' }};">{{ $formatMoney($totals['balance_cents']) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="ja-sidebar-card">
                    <div class="ja-sidebar-head"><i class="bi bi-list-check"></i> {{ __('Line Items') }} ({{ count($jobItems) }})</div>
                    <div class="ja-sidebar-body" style="padding:.75rem; max-height:300px; overflow-y:auto;">
                        @forelse ($jobItems as $item)
                            @php
                                $qty = max(1, (int)($item->qty ?? 1));
                                $unit = (int)($item->unit_price_amount_cents ?? 0);
                                $lt = $qty * $unit;
                                if (($item->item_type ?? null) === 'discount') $lt = 0 - $lt;
                            @endphp
                            <div style="display:flex; justify-content:space-between; padding:.35rem .25rem; border-bottom:1px solid #f1f5f9; font-size:.82rem;">
                                <div style="min-width:0; flex:1;">
                                    <div style="font-weight:600;">{{ $item->name_snapshot }}</div>
                                    <div style="font-size:.68rem; color:var(--rb-text-3); text-transform:uppercase;">{{ $item->item_type ?? '' }} × {{ $qty }}</div>
                                </div>
                                <div style="font-weight:700; white-space:nowrap; padding-left:.5rem;">{{ $formatMoney($lt) }}</div>
                            </div>
                        @empty
                            <div class="ja-empty" style="padding:.5rem;"><i class="bi bi-list-check" style="font-size:1rem;"></i>{{ __('No items') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="ja-sidebar-card">
                    <div class="ja-sidebar-head"><i class="bi bi-lightning"></i> {{ __('Actions') }}</div>
                    <div class="ja-sidebar-body" style="display:grid; gap:.4rem;">
                        @if ($isEstimate)
                            {{-- Estimate-specific actions --}}
                            @if ($estIsPending)
                            <a href="{{ $editUrl }}" class="ja-btn ja-btn-outline" style="justify-content:center; width:100%;">
                                <i class="bi bi-pencil-square"></i> {{ __('Edit Estimate') }}
                            </a>
                            @endif

                            @if ($convertedJobUrl)
                            <a href="{{ $convertedJobUrl }}" class="ja-btn" style="justify-content:center; width:100%; background:var(--rb-success-soft); color:#16a34a; border:1px solid #bbf7d0;">
                                <i class="bi bi-box-arrow-up-right"></i> {{ __('View Repair Job') }}
                            </a>
                            @elseif ($estIsPending)
                            <form method="POST" action="{{ route('tenant.estimates.convert', ['business' => $tenantSlug, 'estimateId' => $record->id]) }}" onsubmit="return confirm('{{ __('Convert this estimate to a repair job?') }}')">
                                @csrf
                                <button type="submit" class="ja-btn" style="justify-content:center; width:100%; background:var(--rb-success-soft); color:#16a34a; border:1px solid #bbf7d0;">
                                    <i class="bi bi-arrow-right-circle"></i> {{ __('Convert to Repair Job') }}
                                </button>
                            </form>
                            @endif

                            @if ($customer && $customer->email)
                            <button type="button" class="ja-btn ja-btn-outline" style="justify-content:center; width:100%;" data-bs-toggle="modal" data-bs-target="#sendEstimateModal">
                                <i class="bi bi-envelope"></i> {{ __('Send Estimate Email') }}
                            </button>
                            @endif

                            @if ($estIsPending)
                            <form method="POST" action="{{ route('tenant.estimates.approve', ['business' => $tenantSlug, 'estimateId' => $record->id]) }}" onsubmit="return confirm('{{ __('Approve this estimate?') }}')">
                                @csrf
                                <button type="submit" class="ja-btn" style="justify-content:center; width:100%; background:var(--rb-success-soft); color:#16a34a; border:1px solid #bbf7d0;">
                                    <i class="bi bi-check-circle"></i> {{ __('Approve Estimate') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('tenant.estimates.reject', ['business' => $tenantSlug, 'estimateId' => $record->id]) }}" onsubmit="return confirm('{{ __('Reject this estimate?') }}')">
                                @csrf
                                <button type="submit" class="ja-btn ja-btn-outline" style="justify-content:center; width:100%; color:var(--rb-warning); border-color:#fde68a;">
                                    <i class="bi bi-x-circle"></i> {{ __('Reject Estimate') }}
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('tenant.estimates.destroy', ['business' => $tenantSlug, 'estimateId' => $record->id]) }}" onsubmit="return confirm('{{ __('Delete this estimate permanently?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ja-btn ja-btn-outline" style="justify-content:center; width:100%; color:var(--rb-danger); border-color:#fecaca;">
                                    <i class="bi bi-trash3"></i> {{ __('Delete Estimate') }}
                                </button>
                            </form>
                        @else
                            {{-- Job actions --}}
                            <a href="{{ $editUrl }}" class="ja-btn ja-btn-outline" style="justify-content:center; width:100%;">
                                <i class="bi bi-pencil-square"></i> {{ __('Edit Job') }}
                            </a>
                            <button type="button" class="ja-btn" style="justify-content:center; width:100%; background:var(--rb-success-soft); color:#16a34a; border:1px solid #bbf7d0;"
                                data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="bi bi-credit-card"></i> {{ __('Add Payment') }}
                            </button>
                        @endif

                        <button type="button"
                            onclick="Livewire.dispatch('openDocumentPreview', { type: '{{ $isEstimate ? 'estimate' : 'job' }}', id: {{ $record?->id ?? 0 }} })"
                            class="ja-btn ja-btn-outline" style="justify-content:center; width:100%;">
                            <i class="bi bi-printer"></i> {{ __('Preview / Print') }}
                        </button>
                        <a href="{{ $listUrl }}" class="ja-btn ja-btn-primary" style="justify-content:center; width:100%;">
                            <i class="bi bi-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- Send Estimate Email Modal --}}
@if ($isEstimate && $customer && $customer->email && $record)
<div class="modal fade" id="sendEstimateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('tenant.estimates.send', ['business' => $tenantSlug, 'estimateId' => $record->id]) }}">
            @csrf
            <div class="modal-content" style="border-radius:14px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-envelope me-2"></i>{{ __('Send Estimate to Customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ __('The customer will receive an email with approve/reject buttons. Clicking approve will automatically convert this estimate into a repair job.') }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('To') }}</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $customer->name }} <{{ $customer->email }}>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('Subject') }}</label>
                        <input type="text" class="form-control form-control-sm" name="email_subject"
                               value="{{ __('Estimate') }} {{ $record->case_number }} {{ __('from') }} {{ $tenant->name ?? 'RepairBuddy' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('Message Body') }}</label>
                        <textarea class="form-control form-control-sm" name="email_body" rows="8">{{ __('Hello') }} {{ $customer->name }},

{{ __('Please find your estimate') }} {{ $record->case_number }} {{ __('below.') }}

{{ __('You can approve or reject this estimate using the buttons in the email.') }}

{{ __('Thank you,') }}
{{ $tenant->name ?? 'RepairBuddy' }}</textarea>
                        <div class="form-text">{{ __('Approve and reject buttons will be added automatically.') }}</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-send me-1"></i>{{ __('Send Email') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── Document Preview Modal (wired for job + estimate show pages) ── --}}
@livewire('tenant.operations.document-preview-modal', ['tenant' => $tenant ?? null])

{{-- ── Add Payment Modal ── --}}
@if (!$isEstimate && $record)
@php
    $paymentGrandTotal = $totals['grand_total_cents'] ?? $totals['total_cents'] ?? 0;
    $paymentPaidSoFar  = collect($jobPayments)->sum('amount_cents');
    $paymentBalance    = $paymentGrandTotal - $paymentPaidSoFar;
    $defaultMethods    = ['cash', 'bank-transfer', 'check', 'card-swipe', 'mobile-payment', 'credit-card', 'debit-card'];
@endphp
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('tenant.jobs.payments.store', ['business' => $tenantSlug, 'jobId' => $record->id]) }}">
            @csrf
            <div class="modal-content" style="border-radius:14px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addPaymentModalLabel">
                        <i class="bi bi-credit-card-2-front me-2" style="color:var(--rb-brand);"></i>{{ __('Make a Payment') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Payment summary --}}
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; padding:.6rem .75rem; background:var(--rb-bg); border-radius:8px; border:1px solid var(--rb-border);">
                        <div style="flex:1; min-width:100px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Payable') }}</div>
                            <div style="font-size:.95rem; font-weight:800;">{{ $formatMoney($paymentGrandTotal) }}</div>
                        </div>
                        <div style="flex:1; min-width:100px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Paid') }}</div>
                            <div style="font-size:.95rem; font-weight:800; color:var(--rb-success);">{{ $formatMoney($paymentPaidSoFar) }}</div>
                        </div>
                        <div style="flex:1; min-width:100px;">
                            <div style="font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--rb-text-3);">{{ __('Balance') }}</div>
                            <div id="paymentBalanceDisplay" style="font-size:.95rem; font-weight:800; color:{{ $paymentBalance > 0 ? 'var(--rb-danger)' : 'var(--rb-success)' }};">{{ $formatMoney($paymentBalance) }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Payment Method --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" for="pmt_method">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="pmt_method" name="method" required>
                                <option value="">{{ __('Select Method') }}</option>
                                @foreach ($defaultMethods as $m)
                                    <option value="{{ $m }}">{{ ucwords(str_replace('-', ' ', $m)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Payment Status --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" for="pmt_status">{{ __('Payment Status') }} <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="pmt_status" name="payment_status" required>
                                <option value="">{{ __('Select Status') }}</option>
                                @if ($paymentStatuses->count() > 0)
                                    @foreach ($paymentStatuses as $ps)
                                        <option value="{{ $ps->slug }}">{{ $ps->label }}</option>
                                    @endforeach
                                @else
                                    <option value="paid">{{ __('Paid') }}</option>
                                    <option value="pending">{{ __('Pending') }}</option>
                                    <option value="partial">{{ __('Partial') }}</option>
                                    <option value="refunded">{{ __('Refunded') }}</option>
                                @endif
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" for="pmt_amount">{{ __('Amount') }} ({{ $currency }}) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" id="pmt_amount" name="amount"
                                   value="{{ number_format($paymentBalance / 100, 2, '.', '') }}" required>
                        </div>

                        {{-- Payment Date --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" for="pmt_date">{{ __('Payment Date') }}</label>
                            <input type="datetime-local" class="form-control form-control-sm" id="pmt_date" name="paid_at"
                                   value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>

                        {{-- Transaction ID --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" for="pmt_transaction">{{ __('Transaction ID') }}</label>
                            <input type="text" class="form-control form-control-sm" id="pmt_transaction" name="transaction_id" placeholder="{{ __('Optional') }}">
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label fw-bold small" for="pmt_notes">{{ __('Notes') }}</label>
                            <textarea class="form-control form-control-sm" id="pmt_notes" name="notes" rows="2" placeholder="{{ __('Optional payment notes...') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Payment') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

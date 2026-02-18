@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Job'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyJob $job */
    $job = $job ?? null;

    $totals = is_array($totals ?? null) ? $totals : [];
    $currency = is_string($totals['currency'] ?? null) ? (string) $totals['currency'] : 'USD';

    $formatMoney = function ($cents) {
        if ($cents === null) {
            return '—';
        }
        $num = is_numeric($cents) ? ((int) $cents) / 100 : 0;
        return number_format($num, 2, '.', ',');
    };

    $jobItems = is_iterable($jobItems ?? null) ? $jobItems : [];
    $jobDevices = is_iterable($jobDevices ?? null) ? $jobDevices : [];
    $jobAttachments = is_iterable($jobAttachments ?? null) ? $jobAttachments : [];
    $jobEvents = is_iterable($jobEvents ?? null) ? $jobEvents : [];

    $customer = $job?->customer;
    $technicians = $job?->technicians;
@endphp

@push('page-styles')
    <style>
        :root {
            --rb-primary: #3B82F6; /* Application Primary Blue */
            --rb-primary-dark: #1D4ED8;
            --rb-hero-bg: radial-gradient(circle at center, #4b5563 0%, #1f2937 100%); /* Enhanced radial glow */
            --rb-card-border: #e2e8f0; 
            --rb-text-muted: #64748b;
            --rb-text-dark: #0f172a;
            --rb-accent: #3B82F6;
        }
        .hero-header {
            background: var(--rb-hero-bg);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hero-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.15); /* Glass icon background */
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hero-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #ffffff !important; /* Ensure it's not black */
        }
        .hero-title h2 span {
            color: var(--rb-primary); /* Highlight "Job Review:" with primary blue */
        }
        .hero-title .subtitle {
            font-size: 0.85rem;
            opacity: 0.8;
            font-family: monospace;
        }
        .job-card {
            border: 1px solid var(--rb-card-border);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            background: white;
            overflow: hidden;
        }
        .job-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .job-card .card-header i {
            color: var(--rb-accent);
            font-size: 1.25rem;
        }
        .job-card .card-title {
            font-weight: 700;
            color: var(--rb-text-dark);
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .job-card .card-body {
            padding: 1.5rem;
        }
        .status-badge {
            padding: 0.4em 0.8em;
            font-weight: 600;
            border-radius: 8px;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.025em;
        }
        .nav-tabs-modern {
            border-bottom: none;
            margin-bottom: 1.5rem;
        }
        .nav-tabs-modern .nav-link {
            border: none;
            color: var(--rb-text-muted);
            font-weight: 600;
            padding: 0.75rem 0;
            margin-right: 2rem;
            position: relative;
            background: transparent;
        }
        .nav-tabs-modern .nav-link.active {
            color: var(--rb-text-dark);
        }
        .nav-tabs-modern .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--rb-accent);
        }
        .detail-item {
            margin-bottom: 1.5rem;
        }
        .detail-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--rb-text-muted);
            margin-bottom: 0.25rem;
            display: block;
        }
        .detail-value {
            color: var(--rb-text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            display: block;
        }

        .rb-customer-top {
            display: flex;
            gap: 0.9rem;
            align-items: flex-start;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px dashed rgba(15, 23, 42, 0.12);
        }
        .rb-customer-avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.12);
            color: var(--rb-primary-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            letter-spacing: 0.02em;
            flex-shrink: 0;
            border: 1px solid rgba(59, 130, 246, 0.18);
        }
        .rb-customer-name {
            font-weight: 800;
            color: var(--rb-text-dark);
            line-height: 1.15;
        }
        .rb-customer-sub {
            color: var(--rb-text-muted);
            font-size: 0.8rem;
            margin-top: 0.15rem;
        }
        .rb-action-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .rb-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(248, 250, 252, 0.9);
            color: var(--rb-text-dark);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .rb-action-pill:hover {
            background: rgba(226, 232, 240, 0.7);
            color: var(--rb-text-dark);
        }
        .rb-kv {
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
        }
        .rb-kv i {
            margin-top: 0.1rem;
            color: #94a3b8;
        }
        .rb-kv-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--rb-text-muted);
            letter-spacing: 0.03em;
            line-height: 1.1;
        }
        .rb-kv-value {
            color: var(--rb-text-dark);
            font-weight: 650;
            font-size: 0.92rem;
            line-height: 1.25;
            word-break: break-word;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid #f1f5f9;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--rb-primary);
        }
        .timeline-item:last-child {
            border-left: none;
        }
        /* Buttons matching reference */
        .btn-export {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .btn-export:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .btn-save-review {
            background: var(--rb-primary);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-save-review:hover {
            background: var(--rb-primary-dark);
            color: white;
        }
        .text-primary { color: var(--rb-primary) !important; }
        .text-danger { color: #EF4444 !important; }
        .bg-primary { background-color: var(--rb-primary) !important; }
        .bg-danger { background-color: #EF4444 !important; }
        .border-primary { border-color: var(--rb-primary) !important; }
        .border-danger { border-color: #EF4444 !important; }
    </style>
@endpush

<main class="dashboard-content container-fluid py-4">
    <div class="hero-header shadow-sm">
        <div class="d-flex align-items-center gap-4">
            <a href="{{ route('tenant.dashboard', ['business' => $tenant?->slug]) . '?screen=jobs' }}" class="btn btn-export p-2">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div class="hero-icon">
                <i class="bi bi-person-fill text-white"></i>
            </div>
            <div class="hero-title">
                <h2 class="mb-0"><span>{{ __('Job Review') }}:</span> {{ $job?->title ?? __('No Title') }}</h2>
                <div class="subtitle">
                    #{{ $job?->case_number ?? 'N/A' }} • 
                    @if (! empty($job?->status_slug))
                        {{ strtoupper($job->status_slug) }}
                    @else
                        N/A
                    @endif
                    <i class="bi bi-pencil-square ms-2 opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-flex gap-2">
                <button type="button" class="btn btn-export" disabled>
                    <i class="bi bi-download me-2"></i>{{ __('Export Job') }}
                </button>
                <button type="button" class="btn btn-export" disabled>
                    <i class="bi bi-printer me-2"></i>{{ __('Print') }}
                </button>
            </div>
            @if ($job)
                <a href="{{ route('tenant.jobs.edit', ['business' => $tenant?->slug, 'jobId' => $job->id]) }}" class="btn btn-export">
                    <i class="bi bi-pencil-square me-2"></i>{{ __('Edit Job') }}
                </a>
            @endif
            <a href="{{ route('tenant.dashboard', ['business' => $tenant?->slug]) . '?screen=jobs' }}" class="btn btn-save-review">
                <i class="bi bi-check2-circle me-2"></i>{{ __('Back to List') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Column 1: Customer Details -->
        <div class="col-xl-3 col-lg-4">
            <div class="card job-card h-100 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-person-badge"></i>
                    <h5 class="card-title">{{ __('Customer Details') }}</h5>
                </div>
                <div class="card-body">
                    @php
                        $customerName = $customer->name ?? trim((string) ($customer->first_name ?? '') . ' ' . (string) ($customer->last_name ?? ''));
                        $customerName = trim((string) $customerName);
                        $customerName = $customerName !== '' ? $customerName : __('N/A');

                        $initialsSource = $customer->name ?? ($customer->first_name ?? '');
                        $initials = trim((string) $initialsSource) !== '' ? strtoupper(mb_substr(trim((string) $initialsSource), 0, 1)) : 'C';

                        $cityState = trim((string) ($customer->city ?? ''));
                        $state = trim((string) ($customer->state ?? ''));
                        if ($state !== '') {
                            $cityState = $cityState !== '' ? ($cityState . ', ' . $state) : $state;
                        }

                        $postcode = trim((string) ($customer->postcode ?? ($customer->zip ?? '')));
                        $addressLine = trim((string) ($customer->address ?? ''));
                        $addressFull = trim($addressLine . ($postcode !== '' ? (' ' . $postcode) : ''));
                    @endphp

                    <div class="rb-customer-top">
                        <div class="rb-customer-avatar">{{ $initials }}</div>
                        <div class="flex-grow-1">
                            <div class="rb-customer-name">{{ $customerName }}</div>

                            @if (! empty($customer?->company))
                                <div class="rb-customer-sub">{{ $customer->company }}</div>
                            @endif

                            @if ($cityState !== '')
                                <div class="rb-customer-sub">{{ $cityState }}</div>
                            @endif

                            <div class="rb-action-pills">
                                @if (! empty($customer?->phone))
                                    <a class="rb-action-pill" href="tel:{{ $customer->phone }}">
                                        <i class="bi bi-telephone"></i>{{ __('Call') }}
                                    </a>
                                @endif

                                @if (! empty($customer?->email))
                                    <a class="rb-action-pill" href="mailto:{{ $customer->email }}">
                                        <i class="bi bi-envelope"></i>{{ __('Email') }}
                                    </a>
                                @endif

                                @if (! empty($customer?->address))
                                    <a class="rb-action-pill" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-geo-alt"></i>{{ __('Map') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="rb-kv">
                                <i class="bi bi-envelope"></i>
                                <div>
                                    <div class="rb-kv-label">{{ __('Email') }}</div>
                                    <div class="rb-kv-value">{{ $customer->email ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="rb-kv">
                                <i class="bi bi-telephone"></i>
                                <div>
                                    <div class="rb-kv-label">{{ __('Phone') }}</div>
                                    <div class="rb-kv-value">{{ $customer->phone ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="rb-kv">
                                <i class="bi bi-geo-alt"></i>
                                <div>
                                    <div class="rb-kv-label">{{ __('Address') }}</div>
                                    <div class="rb-kv-value">{{ $addressFull !== '' ? $addressFull : '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="rb-kv">
                                <i class="bi bi-building"></i>
                                <div>
                                    <div class="rb-kv-label">{{ __('Company') }}</div>
                                    <div class="rb-kv-value">{{ $customer->company ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="rb-kv">
                                <i class="bi bi-hash"></i>
                                <div>
                                    <div class="rb-kv-label">{{ __('Customer ID') }}</div>
                                    <div class="rb-kv-value">{{ $customer->id ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        @if (! empty($customer?->created_at))
                            <div class="col-12">
                                <div class="rb-kv">
                                    <i class="bi bi-clock"></i>
                                    <div>
                                        <div class="rb-kv-label">{{ __('Customer Since') }}</div>
                                        <div class="rb-kv-value">{{ $customer->created_at }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Documents & Technical -->
        <div class="col-xl-5 col-lg-4">
            <!-- Job Details -->
            <div class="card job-card shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-clipboard-data"></i>
                    <h5 class="card-title">{{ __('Job Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="detail-label">{{ __('Case #') }}</span>
                            <span class="detail-value">{{ $job?->case_number ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="detail-label">{{ __('Status') }}</span>
                            <span class="status-badge bg-light text-dark border">{{ $job?->status_slug ? strtoupper($job->status_slug) : '—' }}</span>
                        </div>

                        <div class="col-12">
                            <span class="detail-label">{{ __('Title') }}</span>
                            <span class="detail-value">{{ $job?->title ?? '—' }}</span>
                        </div>

                        <div class="col-sm-6">
                            <span class="detail-label">{{ __('Pickup') }}</span>
                            <span class="detail-value">{{ $job?->pickup_date ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="detail-label">{{ __('Delivery') }}</span>
                            <span class="detail-value">{{ $job?->delivery_date ?? '—' }}</span>
                        </div>

                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="detail-label mb-0">{{ __('Payment Status') }}</span>
                                <span class="status-badge bg-light text-dark border">{{ $job?->payment_status_slug ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <span class="detail-label">{{ __('Internal Notes') }}</span>
                            <div class="detail-value small mt-1" style="white-space: pre-wrap; line-height: 1.4;">{{ $job?->case_detail ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card job-card shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-phone"></i>
                    <h5 class="card-title">{{ __('Devices') }}</h5>
                </div>
                <div class="card-body">
                    @if (count($jobDevices) > 0)
                        @foreach ($jobDevices as $d)
                            <div class="p-2 rounded bg-light bg-opacity-50 border border-light mb-2">
                                <div class="fw-semibold">{{ $d->label_snapshot ?? __('Device') }}</div>
                                @if (! empty($d?->serial_snapshot))
                                    <div class="text-muted small">{{ __('Serial') }}: {{ $d->serial_snapshot }}</div>
                                @endif
                                @if (! empty($d?->pin_snapshot))
                                    <div class="text-muted small">{{ __('PIN') }}: {{ $d->pin_snapshot }}</div>
                                @endif
                                @if (! empty($d?->notes_snapshot))
                                    <div class="text-muted small" style="white-space: pre-wrap; line-height: 1.35;">{{ $d->notes_snapshot }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted small mb-0">{{ __('No devices recorded') }}</p>
                    @endif
                </div>
            </div>

            <!-- Technicians & Devices -->
            <div class="card job-card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-mortarboard"></i>
                    <h5 class="card-title">{{ __('Team') }}</h5>
                </div>
                <div class="card-body">
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Assigned Techs') }}</span>
                        @if ($technicians && $technicians->count() > 0)
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach ($technicians as $tech)
                                    <span class="badge bg-light text-dark border px-2 py-1 small">{{ $tech->name }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="detail-value">{{ __('N/A') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 3: Order Summary & Schedules -->
        <div class="col-xl-4 col-lg-4">
            <div class="card job-card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bookmark-star"></i>
                        <h5 class="card-title">{{ __('Order Summary') }}</h5>
                    </div>
                    <button class="btn btn-primary btn-sm px-3 py-1 fw-bold" style="font-size: 0.7rem;">{{ __('PAYMENT') }}</button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="detail-label">{{ __('Grand Total') }}</span>
                            <span class="detail-value text-primary h5">{{ $formatMoney($totals['grand_total_cents'] ?? null) }} {{ $currency }}</span>
                        </div>
                        <div class="col-6">
                            <span class="detail-label">{{ __('Balance Due') }}</span>
                            <span class="detail-value text-danger h5">{{ $formatMoney($totals['balance_cents'] ?? null) }} {{ $currency }}</span>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="detail-label mb-0">{{ __('Payment Status') }}</span>
                                <span class="status-badge bg-light text-dark border">{{ $job?->payment_status_slug ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card job-card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-calendar3"></i>
                    <h5 class="card-title">{{ __('Schedule & Visibility') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="detail-label">{{ __('Pickup') }}</span>
                            <span class="detail-value">{{ $job?->pickup_date ?? 'N/A' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="detail-label">{{ __('Delivery') }}</span>
                            <span class="detail-value">{{ $job?->delivery_date ?? 'N/A' }}</span>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <span class="detail-label">{{ __('Public Visibility') }}</span>
                            <span class="status-badge bg-opacity-10 bg-success text-success border border-success border-opacity-25">{{ __('VISIBLE') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $items = collect($jobItems);
                $serviceItems = $items->where('item_type', 'service');
                $partItems = $items->where('item_type', 'part');
                $otherItems = $items->whereIn('item_type', ['fee', 'discount']);

                $lineTotalCents = function ($it) {
                    $qty = is_numeric($it->qty ?? null) ? (int) $it->qty : 1;
                    $unit = is_numeric($it->unit_price_amount_cents ?? null) ? (int) $it->unit_price_amount_cents : 0;
                    $total = $qty * $unit;
                    return ($it->item_type ?? null) === 'discount' ? (0 - $total) : $total;
                };
            @endphp

            <div class="card job-card shadow-sm mt-4">
                <div class="card-header">
                    <i class="bi bi-wrench-adjustable"></i>
                    <h5 class="card-title">{{ __('Services') }}</h5>
                </div>
                <div class="card-body">
                    @if ($serviceItems->count() === 0)
                        <p class="text-muted small mb-0">{{ __('No services recorded') }}</p>
                    @else
                        @foreach ($serviceItems as $it)
                            <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                                <div class="pe-2">
                                    <div class="fw-semibold">{{ $it->name_snapshot }}</div>
                                    <div class="text-muted small">{{ __('Qty') }}: {{ $it->qty ?? 1 }} • {{ __('Unit') }}: {{ $formatMoney($it->unit_price_amount_cents) }} {{ $currency }}</div>
                                </div>
                                <div class="text-end fw-semibold">{{ $formatMoney($lineTotalCents($it)) }} {{ $currency }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card job-card shadow-sm mt-4">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i>
                    <h5 class="card-title">{{ __('Parts') }}</h5>
                </div>
                <div class="card-body">
                    @if ($partItems->count() === 0)
                        <p class="text-muted small mb-0">{{ __('No parts recorded') }}</p>
                    @else
                        @foreach ($partItems as $it)
                            <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                                <div class="pe-2">
                                    <div class="fw-semibold">{{ $it->name_snapshot }}</div>
                                    <div class="text-muted small">{{ __('Qty') }}: {{ $it->qty ?? 1 }} • {{ __('Unit') }}: {{ $formatMoney($it->unit_price_amount_cents) }} {{ $currency }}</div>
                                </div>
                                <div class="text-end fw-semibold">{{ $formatMoney($lineTotalCents($it)) }} {{ $currency }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card job-card shadow-sm mt-4">
                <div class="card-header">
                    <i class="bi bi-receipt"></i>
                    <h5 class="card-title">{{ __('Other Line Items') }}</h5>
                </div>
                <div class="card-body">
                    @if ($otherItems->count() === 0)
                        <p class="text-muted small mb-0">{{ __('No additional items recorded') }}</p>
                    @else
                        @foreach ($otherItems as $it)
                            <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                                <div class="pe-2">
                                    <div class="fw-semibold">{{ $it->name_snapshot }}</div>
                                    <div class="text-muted small">{{ strtoupper((string) ($it->item_type ?? '')) }} • {{ __('Qty') }}: {{ $it->qty ?? 1 }} • {{ __('Unit') }}: {{ $formatMoney($it->unit_price_amount_cents) }} {{ $currency }}</div>
                                </div>
                                <div class="text-end fw-semibold">{{ $formatMoney($lineTotalCents($it)) }} {{ $currency }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Estimate'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyEstimate $estimate */
    $estimate    = $estimate ?? null;
    $totals      = is_array($totals ?? null) ? $totals : [];
    $currency    = is_string($totals['currency'] ?? null) ? (string) $totals['currency'] : 'USD';
    $items       = is_iterable($items ?? null) ? $items : [];
    $productItems = is_iterable($productItems ?? null) ? $productItems : [];
    $partItems    = is_iterable($partItems ?? null) ? $partItems : [];
    $serviceItems = is_iterable($serviceItems ?? null) ? $serviceItems : [];
    $extraItems   = is_iterable($extraItems ?? null) ? $extraItems : [];
    $devices      = is_iterable($devices ?? null) ? $devices : [];
    $attachments  = is_iterable($attachments ?? null) ? $attachments : [];
    $events       = is_iterable($events ?? null) ? $events : [];

    $customer   = $estimate?->customer;
    $technician = $estimate?->assignedTechnician;
    $status     = $estimate?->status ?? 'pending';
    $isPending  = $status === 'pending';
    $isApproved = $status === 'approved';
    $isRejected = $status === 'rejected';

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';

    $listUrl = $tenantSlug ? route('tenant.estimates.index', ['business' => $tenantSlug]) : '#';
    $editUrl = ($estimate && $tenantSlug) ? route('tenant.estimates.edit', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) : '#';

    $convertedJobUrl = null;
    if ($estimate?->converted_job_id && $tenantSlug) {
        $convertedJobUrl = route('tenant.jobs.show', ['business' => $tenantSlug, 'jobId' => $estimate->converted_job_id]);
    }

    $formatMoney = function ($cents) {
        if ($cents === null) return '—';
        return '$' . number_format(((int) $cents) / 100, 2, '.', ',');
    };

    $statusBadge = match ($status) {
        'approved' => ['class' => 'bg-success', 'label' => 'Approved'],
        'rejected' => ['class' => 'bg-danger', 'label' => 'Rejected'],
        default    => ['class' => 'bg-info', 'label' => 'Pending'],
    };
@endphp

@push('page-styles')
<style>
    :root {
        --rb-primary: #3B82F6;
        --rb-primary-dark: #1D4ED8;
        --rb-hero-bg: radial-gradient(circle at center, #4b5563 0%, #1f2937 100%);
        --rb-card-border: #e2e8f0;
        --rb-text-muted: #64748b;
        --rb-text-dark: #0f172a;
        --rb-accent: #3B82F6;
    }
    .est-hero { background: var(--rb-hero-bg); border-radius: 16px; padding: 1.75rem 2rem; color: #fff; margin-bottom: 1.75rem; }
    .est-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .25rem; color: #fff !important; }
    .est-hero h2 span { color: var(--rb-primary); }
    .est-hero .subtitle { font-size: .82rem; opacity: .75; font-family: monospace; }
    .est-hero .badge { font-size: .72rem; vertical-align: middle; }

    .est-card { border: 1px solid var(--rb-card-border); border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); background: #fff; overflow: hidden; margin-bottom: 1.25rem; }
    .est-card .card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,.04); padding: 1rem 1.25rem; display: flex; align-items: center; gap: .6rem; }
    .est-card .card-header i { color: var(--rb-accent); font-size: 1.15rem; }
    .est-card .card-title { font-weight: 700; font-size: 1rem; margin: 0; color: var(--rb-text-dark); }
    .est-card .card-body { padding: 1.25rem; }

    .est-detail-row { display: flex; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; font-size: .88rem; }
    .est-detail-row:last-child { border-bottom: none; }
    .est-detail-label { width: 140px; flex-shrink: 0; color: var(--rb-text-muted); font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
    .est-detail-value { flex: 1; color: var(--rb-text-dark); }

    .est-items-table th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--rb-text-muted); }
    .est-items-table td { font-size: .85rem; vertical-align: middle; }

    .est-action-btn { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .6rem 1rem; border-radius: 10px; border: 1px solid var(--rb-card-border); background: #fff; font-size: .85rem; font-weight: 600; color: var(--rb-text-dark); cursor: pointer; transition: all .15s; text-decoration: none; }
    .est-action-btn:hover { background: #f8fafc; box-shadow: 0 2px 6px rgba(0,0,0,.06); color: var(--rb-text-dark); text-decoration: none; }
    .est-action-btn i { font-size: 1rem; width: 20px; text-align: center; }
    .est-action-btn.danger { color: #dc2626; border-color: #fecaca; }
    .est-action-btn.danger:hover { background: #fef2f2; }
    .est-action-btn.success { color: #16a34a; border-color: #bbf7d0; }
    .est-action-btn.success:hover { background: #f0fdf4; }
    .est-action-btn.primary { color: #2563eb; border-color: #bfdbfe; }
    .est-action-btn.primary:hover { background: #eff6ff; }
    .est-action-btn.warning { color: #d97706; border-color: #fde68a; }
    .est-action-btn.warning:hover { background: #fffbeb; }

    .est-timeline-item { display: flex; gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #f1f5f9; }
    .est-timeline-item:last-child { border-bottom: none; }
    .est-timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--rb-primary); margin-top: 6px; flex-shrink: 0; }
    .est-timeline-body { flex: 1; }
    .est-timeline-title { font-size: .85rem; font-weight: 600; color: var(--rb-text-dark); }
    .est-timeline-meta { font-size: .75rem; color: var(--rb-text-muted); }

    .est-person-card { display: flex; align-items: center; gap: .75rem; padding: .75rem; border-radius: 10px; background: #f8fafc; }
    .est-person-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; flex-shrink: 0; }
    .est-person-name { font-weight: 700; font-size: .9rem; }
    .est-person-meta { font-size: .78rem; color: var(--rb-text-muted); }

    .est-totals-row { display: flex; justify-content: space-between; padding: .45rem 0; font-size: .85rem; }
    .est-totals-row.grand { font-size: 1rem; font-weight: 800; border-top: 2px solid var(--rb-text-dark); margin-top: .5rem; padding-top: .75rem; }

    .est-banner { border-radius: 10px; padding: .85rem 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: .75rem; font-size: .88rem; }
    .est-banner-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .est-banner-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .est-banner a { font-weight: 700; }

    /* Send Email Modal */
    .est-send-modal textarea { min-height: 120px; }
</style>
@endpush

{{-- ======================== HERO ======================== --}}
<div class="est-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>
                <i class="bi bi-file-earmark-text me-2"></i>
                <span>{{ __('Estimate') }}:</span>
                {{ $estimate?->case_number ?? '#' . ($estimate?->id ?? '?') }}
                <span class="badge {{ $statusBadge['class'] }} ms-2">{{ $statusBadge['label'] }}</span>
            </h2>
            @if ($estimate?->title && $estimate->title !== $estimate->case_number)
                <div class="subtitle mt-1">{{ $estimate->title }}</div>
            @endif
        </div>
        <div>
            <a href="{{ $listUrl }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Estimates') }}
            </a>
        </div>
    </div>
</div>

{{-- ======================== FLASH ======================== --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ======================== STATUS BANNERS ======================== --}}
@if ($isApproved && $convertedJobUrl)
    <div class="est-banner est-banner-success">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>
            {{ __('This estimate was approved and converted to a repair job.') }}
            <a href="{{ $convertedJobUrl }}">{{ __('View Repair Job →') }}</a>
        </div>
    </div>
@endif
@if ($isRejected)
    <div class="est-banner est-banner-danger">
        <i class="bi bi-x-circle-fill fs-5"></i>
        <div>
            {{ __('This estimate was rejected') }}
            @if ($estimate?->rejected_at)
                {{ __('on') }} {{ $estimate->rejected_at->format('M d, Y \a\t H:i') }}
            @endif
        </div>
    </div>
@endif

<div class="row g-3">
    {{-- ======================== LEFT COLUMN ======================== --}}
    <div class="col-lg-8">

        {{-- ---- Estimate Details ---- --}}
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i>
                <h5 class="card-title">{{ __('Estimate Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Case Number') }}</div>
                    <div class="est-detail-value fw-bold">{{ $estimate?->case_number ?? '—' }}</div>
                </div>
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Status') }}</div>
                    <div class="est-detail-value"><span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span></div>
                </div>
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Pickup Date') }}</div>
                    <div class="est-detail-value">{{ $estimate?->pickup_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Delivery Date') }}</div>
                    <div class="est-detail-value">{{ $estimate?->delivery_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                @if ($estimate?->sent_at)
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Sent to Customer') }}</div>
                    <div class="est-detail-value">{{ $estimate->sent_at->format('M d, Y \a\t H:i') }}</div>
                </div>
                @endif
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Created') }}</div>
                    <div class="est-detail-value">{{ $estimate?->created_at?->format('M d, Y \a\t H:i') ?? '—' }}</div>
                </div>
                @if ($estimate?->case_detail)
                <div class="est-detail-row">
                    <div class="est-detail-label">{{ __('Job Details') }}</div>
                    <div class="est-detail-value">{!! nl2br(e($estimate->case_detail)) !!}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- ---- Customer & Technician ---- --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="est-card h-100">
                    <div class="card-header">
                        <i class="bi bi-person"></i>
                        <h5 class="card-title">{{ __('Customer') }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($customer)
                            <div class="est-person-card">
                                <div class="est-person-avatar">{{ strtoupper(substr($customer->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="est-person-name">{{ $customer->name }}</div>
                                    <div class="est-person-meta">{{ $customer->email }}</div>
                                    @if ($customer->phone)<div class="est-person-meta">{{ $customer->phone }}</div>@endif
                                    @if ($customer->company)<div class="est-person-meta">{{ $customer->company }}</div>@endif
                                </div>
                            </div>
                            @if ($customer->address_line1 || $customer->address_city)
                                <div class="mt-2 small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ collect([$customer->address_line1, $customer->address_line2, $customer->address_city, $customer->address_state, $customer->address_postal_code, $customer->address_country])->filter()->implode(', ') }}
                                </div>
                            @endif
                        @else
                            <p class="text-muted mb-0">{{ __('No customer assigned.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="est-card h-100">
                    <div class="card-header">
                        <i class="bi bi-person-gear"></i>
                        <h5 class="card-title">{{ __('Technician') }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($technician)
                            <div class="est-person-card">
                                <div class="est-person-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">{{ strtoupper(substr($technician->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div class="est-person-name">{{ $technician->name }}</div>
                                    <div class="est-person-meta">{{ $technician->email }}</div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('No technician assigned.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- Devices ---- --}}
        @if (count($devices))
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-phone"></i>
                <h5 class="card-title">{{ __('Devices') }} <span class="badge bg-secondary ms-1">{{ count($devices) }}</span></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table est-items-table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('Device') }}</th>
                                <th>{{ __('Serial / IMEI') }}</th>
                                <th>{{ __('PIN') }}</th>
                                <th>{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($devices as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d->label_snapshot ?: '—' }}</td>
                                <td><code>{{ $d->serial_snapshot ?: '—' }}</code></td>
                                <td>{{ $d->pin_snapshot ?: '—' }}</td>
                                <td class="small text-muted">{{ $d->notes_snapshot ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- ---- Line Items by Category (mirrors plugin) ---- --}}
        @php
            $categories = [
                ['key' => 'products', 'label' => __('Products'),       'icon' => 'bi-box',       'items' => $productItems],
                ['key' => 'parts',    'label' => __('Parts'),          'icon' => 'bi-cpu',        'items' => $partItems],
                ['key' => 'services', 'label' => __('Services'),       'icon' => 'bi-wrench',     'items' => $serviceItems],
                ['key' => 'extras',   'label' => __('Extras / Other'), 'icon' => 'bi-plus-circle','items' => $extraItems],
            ];
        @endphp

        @foreach ($categories as $cat)
            @if (count($cat['items']) > 0)
            <div class="est-card">
                <div class="card-header">
                    <i class="bi {{ $cat['icon'] }}"></i>
                    <h5 class="card-title">{{ $cat['label'] }} <span class="badge bg-secondary ms-1">{{ count($cat['items']) }}</span></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table est-items-table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th class="text-center" style="width:70px">{{ __('Qty') }}</th>
                                    <th class="text-end" style="width:100px">{{ __('Unit Price') }}</th>
                                    <th class="text-end" style="width:90px">{{ __('Tax') }}</th>
                                    <th class="text-end" style="width:110px">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cat['items'] as $item)
                                @php
                                    $qty  = max(1, (int) ($item->qty ?? 1));
                                    $unit = (int) ($item->unit_price_amount_cents ?? 0);
                                    $lineSub = $qty * $unit;
                                    $taxAmount = 0;
                                    $taxLabel = '—';
                                    if ($item->relationLoaded('tax') && $item->tax) {
                                        $rate = (float) ($item->tax->rate ?? 0);
                                        $taxAmount = (int) round($lineSub * ($rate / 100));
                                        $taxLabel = number_format($rate, 1) . '%';
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $item->name_snapshot ?: '—' }}</td>
                                    <td class="text-center">{{ $qty }}</td>
                                    <td class="text-end">{{ $formatMoney($unit) }}</td>
                                    <td class="text-end small text-muted">{{ $taxLabel }}</td>
                                    <td class="text-end fw-bold">{{ $formatMoney($lineSub + $taxAmount) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        @endforeach

        {{-- ---- Attachments ---- --}}
        @if (count($attachments))
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-paperclip"></i>
                <h5 class="card-title">{{ __('Attachments') }} <span class="badge bg-secondary ms-1">{{ count($attachments) }}</span></h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach ($attachments as $att)
                    <div class="list-group-item d-flex align-items-center px-0">
                        <i class="bi bi-file-earmark me-2 text-muted"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $att->original_filename }}</div>
                            @if ($att->size_bytes)
                                <div class="text-muted" style="font-size:.72rem">{{ number_format($att->size_bytes / 1024, 1) }} KB</div>
                            @endif
                        </div>
                        @if ($att->url)
                            <a href="{{ $att->url }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0">
                                <i class="bi bi-download"></i>
                            </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ---- Timeline ---- --}}
        @if (count($events))
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i>
                <h5 class="card-title">{{ __('Activity Timeline') }}</h5>
            </div>
            <div class="card-body">
                @foreach ($events as $ev)
                    @php
                        $payload = is_array($ev->payload_json) ? $ev->payload_json : (is_string($ev->payload_json) ? json_decode($ev->payload_json, true) : []);
                        $evTitle = $payload['title'] ?? ($ev->event_type ?? 'Event');
                        $actorName = $ev->actor?->name ?? 'System';
                    @endphp
                    <div class="est-timeline-item">
                        <div class="est-timeline-dot"></div>
                        <div class="est-timeline-body">
                            <div class="est-timeline-title">{{ $evTitle }}</div>
                            <div class="est-timeline-meta">
                                {{ $actorName }} &middot; {{ $ev->created_at?->format('M d, Y H:i') ?? '' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ======================== RIGHT COLUMN (SIDEBAR) ======================== --}}
    <div class="col-lg-4">

        {{-- ---- Financial Summary (mirrors plugin Order Information box) ---- --}}
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-calculator"></i>
                <h5 class="card-title">{{ __('Financial Summary') }}</h5>
            </div>
            <div class="card-body">
                @php
                    $pt = $totals['products'] ?? ['subtotal' => 0, 'tax' => 0];
                    $pa = $totals['parts']    ?? ['subtotal' => 0, 'tax' => 0];
                    $sv = $totals['services'] ?? ['subtotal' => 0, 'tax' => 0];
                    $ex = $totals['extras']   ?? ['subtotal' => 0, 'tax' => 0];
                @endphp

                @if ($pt['subtotal'] > 0)
                <div class="est-totals-row"><span>{{ __('Products') }}</span><span>{{ $formatMoney($pt['subtotal']) }}</span></div>
                @endif
                @if ($pa['subtotal'] > 0)
                <div class="est-totals-row"><span>{{ __('Parts') }}</span><span>{{ $formatMoney($pa['subtotal']) }}</span></div>
                @endif
                @if ($sv['subtotal'] > 0)
                <div class="est-totals-row"><span>{{ __('Services') }}</span><span>{{ $formatMoney($sv['subtotal']) }}</span></div>
                @endif
                @if ($ex['subtotal'] > 0)
                <div class="est-totals-row"><span>{{ __('Extras') }}</span><span>{{ $formatMoney($ex['subtotal']) }}</span></div>
                @endif

                <div class="est-totals-row" style="border-top:1px solid #e2e8f0; padding-top:.5rem; margin-top:.25rem;">
                    <span class="fw-semibold">{{ __('Subtotal') }}</span>
                    <span class="fw-semibold">{{ $formatMoney($totals['subtotal_cents'] ?? 0) }}</span>
                </div>

                @if (($totals['tax_cents'] ?? 0) > 0)
                <div class="est-totals-row">
                    <span>{{ __('Tax') }}</span>
                    <span>{{ $formatMoney($totals['tax_cents'] ?? 0) }}</span>
                </div>
                @endif

                <div class="est-totals-row grand">
                    <span>{{ __('Grand Total') }}</span>
                    <span>{{ $formatMoney($totals['total_cents'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- ---- Actions (mirrors plugin sidebar actions) ---- --}}
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-lightning"></i>
                <h5 class="card-title">{{ __('Actions') }}</h5>
            </div>
            <div class="card-body d-grid gap-2">

                {{-- Edit --}}
                @if ($isPending)
                    <a href="{{ $editUrl }}" class="est-action-btn primary">
                        <i class="bi bi-pencil-square"></i>{{ __('Edit Estimate') }}
                    </a>
                @endif

                {{-- Convert to Job / View Job --}}
                @if ($convertedJobUrl)
                    <a href="{{ $convertedJobUrl }}" class="est-action-btn success">
                        <i class="bi bi-box-arrow-up-right"></i>{{ __('View Repair Job') }}
                    </a>
                @elseif ($isPending)
                    <form method="POST" action="{{ route('tenant.estimates.convert', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) }}"
                          onsubmit="return confirm('Convert this estimate to a repair job?')">
                        @csrf
                        <button type="submit" class="est-action-btn success">
                            <i class="bi bi-arrow-right-circle"></i>{{ __('Convert to Repair Job') }}
                        </button>
                    </form>
                @endif

                {{-- Send Estimate Email --}}
                @if ($customer && $customer->email)
                    <button type="button" class="est-action-btn primary" data-bs-toggle="modal" data-bs-target="#sendEstimateModal">
                        <i class="bi bi-envelope"></i>{{ __('Send Estimate Email') }}
                    </button>
                @endif

                {{-- Approve --}}
                @if ($isPending)
                    <form method="POST" action="{{ route('tenant.estimates.approve', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) }}"
                          onsubmit="return confirm('Approve this estimate and convert to a job?')">
                        @csrf
                        <button type="submit" class="est-action-btn success">
                            <i class="bi bi-check-circle"></i>{{ __('Approve Estimate') }}
                        </button>
                    </form>
                @endif

                {{-- Reject --}}
                @if ($isPending)
                    <form method="POST" action="{{ route('tenant.estimates.reject', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) }}"
                          onsubmit="return confirm('Reject this estimate? This cannot be undone easily.')">
                        @csrf
                        <button type="submit" class="est-action-btn warning">
                            <i class="bi bi-x-circle"></i>{{ __('Reject Estimate') }}
                        </button>
                    </form>
                @endif

                {{-- Print --}}
                <a href="javascript:window.print()" class="est-action-btn">
                    <i class="bi bi-printer"></i>{{ __('Print Estimate') }}
                </a>

                {{-- Delete --}}
                <form method="POST" action="{{ route('tenant.estimates.destroy', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) }}"
                      onsubmit="return confirm('Delete this estimate permanently? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="est-action-btn danger">
                        <i class="bi bi-trash3"></i>{{ __('Delete Estimate') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- ---- Order Notes ---- --}}
        @if ($estimate?->case_detail)
        <div class="est-card">
            <div class="card-header">
                <i class="bi bi-sticky"></i>
                <h5 class="card-title">{{ __('Order Notes') }}</h5>
            </div>
            <div class="card-body">
                <div class="small" style="white-space:pre-wrap;">{{ $estimate->case_detail }}</div>
            </div>
        </div>
        @endif

    </div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}

{{-- ======================== SEND EMAIL MODAL ======================== --}}
@if ($customer && $customer->email && $estimate)
<div class="modal fade" id="sendEstimateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('tenant.estimates.send', ['business' => $tenantSlug, 'estimateId' => $estimate->id]) }}">
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
                               value="Estimate {{ $estimate->case_number }} from {{ $tenant->name ?? 'RepairBuddy' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('Message Body') }}</label>
                        <textarea class="form-control form-control-sm est-send-modal" name="email_body" rows="8">Hello {{ $customer->name }},

Please find your estimate {{ $estimate->case_number }} below.

You can approve or reject this estimate using the buttons in the email.

Thank you,
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

@endsection

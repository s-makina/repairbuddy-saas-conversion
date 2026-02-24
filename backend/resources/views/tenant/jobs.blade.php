@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Jobs'])

@php
    /** @var \App\Models\Tenant|null $tenant */
    /** @var \App\Models\User|null   $user */
    $jobRows        = $jobRows ?? [];
    $_job_status    = $_job_status ?? [];
    $jobStatuses    = $jobStatuses ?? collect();
    $paymentStatuses= $paymentStatuses ?? collect();
    $customers      = $customers ?? collect();
    $technicians    = $technicians ?? collect();
    $devices        = $devices ?? collect();
    $role           = $role ?? null;
    $searchInput    = $searchInput ?? '';
    $statusFilter   = $statusFilter ?? '';
    $paymentFilter  = $paymentFilter ?? '';
    $priorityFilter = $priorityFilter ?? '';
    $deviceFilter   = $deviceFilter ?? '';

    $tenantSlug  = is_string($tenant?->slug) ? (string) $tenant->slug : '';
    $createUrl   = $tenantSlug ? route('tenant.jobs.create', ['business' => $tenantSlug]) : '#';
    $baseUrl     = $tenantSlug ? route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs' : '#';

    // ── Columns for <x-ui.datatable>
    $jobColumns = [
        ['key' => 'job_id',        'label' => __('Job #'),       'width' => '90px',  'sortable' => true,  'filter' => true],
        ['key' => 'case_number',   'label' => __('Case #'),      'width' => '120px', 'sortable' => true,  'filter' => true],
        ['key' => 'customer',      'label' => __('Customer'),    'width' => '160px', 'sortable' => true,  'filter' => true],
        ['key' => 'device',        'label' => __('Device'),      'width' => '160px', 'sortable' => true,  'filter' => true],
        ['key' => 'technician',    'label' => __('Technician'),  'width' => '130px', 'sortable' => true,  'filter' => true],
        ['key' => 'status',        'label' => __('Status'),      'width' => '120px', 'sortable' => true,  'badge' => true],
        ['key' => 'priority',      'label' => __('Priority'),    'width' => '100px', 'sortable' => true,  'badge' => true],
        ['key' => 'payment',       'label' => __('Payment'),     'width' => '100px', 'sortable' => true,  'badge' => true],
        ['key' => 'pickup_date',   'label' => __('Pickup'),      'width' => '110px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'delivery_date', 'label' => __('Delivery'),    'width' => '110px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'actions',       'label' => '',                 'width' => '160px', 'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];
@endphp

@push('page-scripts')
<script>
  /* ── openDocPreview: opens DocumentPreviewModal via Livewire dispatch ── */
  function openDocPreview(type, id) {
    if (window.Livewire) {
      window.Livewire.dispatch('openDocumentPreview', { type: type, id: id });
    }
  }
</script>
@endpush

@section('content')
<div class="container-fluid p-3">

    {{-- ═══════ Stats Cards ═══════ --}}
    <!-- @if (!empty($_job_status))
    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach ($_job_status as $_jobsstatus)
            @php $tc = $_jobsstatus['color'] ?? 'secondary'; @endphp
            <a href="{{ $_jobsstatus['url'] ?? '#' }}" class="text-decoration-none">
                <div class="stat-tile stat-tile--{{ $tc }}">
                    <span class="stat-tile__count">{{ $_jobsstatus['jobs_count'] ?? 0 }}</span>
                    <span class="stat-tile__label">{{ $_jobsstatus['status_name'] ?? '' }}</span>
                </div>
            </a>
        @endforeach
    </div>
    @endif -->

    {{-- ═══════ DataTable ═══════ --}}
    <x-ui.datatable
        tableId="jobsTable"
        :title="__('Repair Jobs')"
        :columns="$jobColumns"
        :rows="$jobRows"
        :searchable="true"
        :paginate="true"
        :perPage="25"
        :perPageOptions="[10, 25, 50, 100]"
        :exportable="true"
        :filterable="true"
        :createRoute="$createUrl"
        :createLabel="__('New Job')"
        :emptyMessage="__('No jobs found.')"
    >
        <x-slot:filters>
            <form method="get" action="{{ $baseUrl }}">
                <input type="hidden" name="screen" value="jobs" />
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Job Status') }}</label>
                        <select class="form-select form-select-sm" name="job_status">
                            <option value="all">{{ __('All Statuses') }}</option>
                            @foreach ($jobStatuses as $js)
                                <option value="{{ $js->code }}" {{ $statusFilter === $js->code ? 'selected' : '' }}>
                                    {{ $js->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Priority') }}</label>
                        <select class="form-select form-select-sm" name="wc_job_priority">
                            <option value="all">{{ __('All Priorities') }}</option>
                            @foreach (['normal', 'high', 'urgent'] as $p)
                                <option value="{{ $p }}" {{ $priorityFilter === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Payment') }}</label>
                        <select class="form-select form-select-sm" name="wc_payment_status">
                            <option value="all">{{ __('All') }}</option>
                            @foreach ($paymentStatuses as $ps)
                                <option value="{{ $ps->code }}" {{ $paymentFilter === $ps->code ? 'selected' : '' }}>
                                    {{ $ps->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Device') }}</label>
                        <select class="form-select form-select-sm" name="device_post_id">
                            <option value="">{{ __('All Devices') }}</option>
                            @foreach ($devices as $d)
                                <option value="{{ $d->id }}" {{ (string) $deviceFilter === (string) $d->id ? 'selected' : '' }}>
                                    {{ $d->label ?? 'Device #' . $d->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Apply') }}</button>
                        <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
            </form>
        </x-slot:filters>

        <x-slot:bulkActions>
            <button class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
            <button class="btn btn-sm btn-outline-danger" style="font-size: 0.75rem;"><i class="bi bi-trash me-1"></i>{{ __('Delete') }}</button>
        </x-slot:bulkActions>
    </x-ui.datatable>

</div>

{{-- ── Document Preview Modal ── --}}
@livewire('tenant.operations.document-preview-modal', ['tenant' => $tenant ?? null])
@endsection

@push('page-styles')
<style>
    /* ── badge pills ── */
    .wcrb-pill--progress { color: #1d4ed8; background: rgba(59,130,246,.10); border-color: rgba(59,130,246,.25); }
    .wcrb-pill--pending  { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }
    .wcrb-pill--warning  { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }
    .wcrb-pill--danger   { color: #991b1b; background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.25); }
    .wcrb-pill--high     { color: #991b1b; background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.25); }
    .wcrb-pill--medium   { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }
    .wcrb-pill--low      { color: #065f46; background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); }
    [data-bs-theme="dark"] .wcrb-pill--progress { background: rgba(59,130,246,.20); }
    [data-bs-theme="dark"] .wcrb-pill--pending  { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning  { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger   { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--high     { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--low      { background: rgba(16,185,129,.20); }

    /* ── status summary tiles ── */
    .stat-tile {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .3rem .75rem;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: .78rem;
        font-weight: 500;
        transition: filter .15s, transform .1s;
        white-space: nowrap;
    }
    .stat-tile:hover { filter: brightness(.95); transform: translateY(-1px); }
    .stat-tile__count {
        font-size: .9rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-tile__label { opacity: .85; }

    /* color variants */
    .stat-tile--primary   { color: #1d4ed8; background: rgba(59,130,246,.10); border-color: rgba(59,130,246,.30); }
    .stat-tile--success   { color: #065f46; background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.30); }
    .stat-tile--warning   { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.30); }
    .stat-tile--info      { color: #155e75; background: rgba(6,182,212,.10);  border-color: rgba(6,182,212,.30); }
    .stat-tile--danger    { color: #991b1b; background: rgba(239,68,68,.10);  border-color: rgba(239,68,68,.30); }
    .stat-tile--secondary { color: #374151; background: rgba(107,114,128,.10); border-color: rgba(107,114,128,.28); }

    [data-bs-theme="dark"] .stat-tile--primary   { color: #93c5fd; background: rgba(59,130,246,.18); border-color: rgba(59,130,246,.35); }
    [data-bs-theme="dark"] .stat-tile--success   { color: #6ee7b7; background: rgba(16,185,129,.18); border-color: rgba(16,185,129,.35); }
    [data-bs-theme="dark"] .stat-tile--warning   { color: #fcd34d; background: rgba(245,158,11,.18); border-color: rgba(245,158,11,.35); }
    [data-bs-theme="dark"] .stat-tile--info      { color: #67e8f9; background: rgba(6,182,212,.18);  border-color: rgba(6,182,212,.35); }
    [data-bs-theme="dark"] .stat-tile--danger    { color: #fca5a5; background: rgba(239,68,68,.18);  border-color: rgba(239,68,68,.35); }
    [data-bs-theme="dark"] .stat-tile--secondary { color: #d1d5db; background: rgba(107,114,128,.18); border-color: rgba(107,114,128,.32); }
</style>
@endpush

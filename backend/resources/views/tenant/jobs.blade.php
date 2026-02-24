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
    @if (!empty($_job_status))
    <div class="row g-3 mb-4">
        @foreach ($_job_status as $_jobsstatus)
        <div class="col">
            <a href="{{ $_jobsstatus['url'] ?? '#' }}" class="text-decoration-none">
                <div class="card stats-card {{ $_jobsstatus['color'] ?? 'bg-secondary' }} text-white">
                    <div class="card-body text-center p-3">
                        <h6 class="card-title text-white-50 mb-1">{{ $_jobsstatus['status_name'] ?? '' }}</h6>
                        <h4 class="mb-0">{{ $_jobsstatus['jobs_count'] ?? 0 }}</h4>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif

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
</style>
@endpush

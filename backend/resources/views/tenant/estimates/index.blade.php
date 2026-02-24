@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Estimates'])

@php
    /** @var \App\Models\Tenant|null $tenant */
    /** @var \App\Models\User|null   $user */
    $countPending  = (int) ($countPending ?? 0);
    $countApproved = (int) ($countApproved ?? 0);
    $countRejected = (int) ($countRejected ?? 0);
    $countTotal    = $countPending + $countApproved + $countRejected;

    $searchInput        = $searchInput ?? '';
    $statusFilter       = $statusFilter ?? '';
    $deviceFilter       = $deviceFilter ?? '';
    $customerFilterId   = $customerFilterId ?? '';
    $technicianFilterId = $technicianFilterId ?? '';

    $estimates      = $estimates ?? collect();
    $customers      = $customers ?? collect();
    $technicians    = $technicians ?? collect();
    $customerDevices= $customerDevices ?? collect();

    $role       = is_string($user?->role) ? (string) $user->role : null;
    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';

    $baseUrl = $tenantSlug ? route('tenant.estimates.index', ['business' => $tenantSlug]) : '#';
    $newUrl  = $tenantSlug ? route('tenant.estimates.create', ['business' => $tenantSlug]) : '#';

    $formatMoney = function ($cents) {
        if ($cents === null) return '—';
        return '$' . number_format(((int) $cents) / 100, 2, '.', ',');
    };

    // ── Badge map ──
    $statusBadgeMap = [
        'approved' => 'wcrb-pill--active',
        'rejected' => 'wcrb-pill--danger',
        'pending'  => 'wcrb-pill--pending',
    ];

    // ── Columns for <x-ui.datatable> ──
    $estColumns = [
        ['key' => 'id',           'label' => '#',                'width' => '60px',  'sortable' => true],
        ['key' => 'case_title',   'label' => __('Case / Title'), 'sortable' => true,  'filter' => true],
        ['key' => 'customer_name','label' => __('Customer'),     'sortable' => true,  'filter' => true],
        ['key' => 'devices',      'label' => __('Devices'),      'sortable' => false],
        ['key' => 'technician',   'label' => __('Technician'),   'sortable' => true,  'filter' => true],
        ['key' => 'dates',        'label' => __('Dates'),        'width' => '120px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'total',        'label' => __('Total'),        'width' => '100px', 'sortable' => true,  'align' => 'text-end'],
        ['key' => 'status',       'label' => __('Status'),       'width' => '110px', 'sortable' => true,  'badge' => true],
        ['key' => 'actions',      'label' => '',                  'width' => '160px', 'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];

    // ── Rows ──
    $estRows = [];
    foreach ($estimates as $est) {
        $showUrl = $tenantSlug ? route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $est->id]) : '#';

        $totalCents = 0;
        foreach ($est->items as $it) {
            $totalCents += max(1, (int)($it->qty ?? 1)) * (int)($it->unit_price_amount_cents ?? 0);
        }

        $caseTitle = ($est->case_number ?: '—');
        if ($est->title && $est->title !== $est->case_number) {
            $caseTitle .= ' — ' . \Illuminate\Support\Str::limit($est->title, 40);
        }

        $editUrl = ($est->status ?? 'pending') === 'pending' && $tenantSlug
            ? route('tenant.estimates.edit', ['business' => $tenantSlug, 'estimateId' => $est->id])
            : null;

        $actionHtml = '<div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">'
            . '<a href="' . e($showUrl) . '" class="btn btn-sm btn-primary" style="padding: .25rem .65rem; font-size: .78rem;" title="' . e(__('View')) . '"><i class="bi bi-eye me-1"></i>' . e(__('View')) . '</a>'
            . '<div class="dropdown">'
            . '<button class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" style="padding: .25rem .45rem;"><i class="bi bi-three-dots" style="font-size:.75rem;"></i></button>'
            . '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem; min-width: 160px;">'
            . '<li><button class="dropdown-item py-2" type="button" onclick="Livewire.dispatch(\'openDocumentPreview\', { type: \'estimate\', id: ' . (int) $est->id . ' })"><i class="bi bi-printer me-2 text-muted"></i>' . e(__('Print / Preview')) . '</button></li>';

        if ($editUrl) {
            $actionHtml .= '<li><a class="dropdown-item py-2" href="' . e($editUrl) . '"><i class="bi bi-pencil me-2 text-muted"></i>' . e(__('Edit Estimate')) . '</a></li>';
        }

        $actionHtml .= '</ul></div></div>';

        $estRows[] = [
            'id'            => $est->id,
            'case_title'    => $caseTitle,
            'customer_name' => $est->customer?->name ?? '—',
            'devices'       => $est->devices->pluck('label_snapshot')->filter()->implode(', ') ?: '—',
            'technician'    => $est->assignedTechnician?->name ?? '—',
            'dates'         => $est->created_at?->format('M d, Y') ?? '—',
            'total'         => $formatMoney($totalCents),
            'status'        => ucfirst($est->status ?? 'pending'),
            '_badgeClass_status' => $statusBadgeMap[$est->status ?? 'pending'] ?? 'wcrb-pill--pending',
            'actions'       => $actionHtml,
        ];
    }
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- ═══════ Stats Cards ═══════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?estimate_status=pending" class="text-decoration-none">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Pending') }}</div>
                                <h4 class="mb-0">{{ $countPending }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-clock-history"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?estimate_status=approved" class="text-decoration-none">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Approved') }}</div>
                                <h4 class="mb-0">{{ $countApproved }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-check-circle"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?estimate_status=rejected" class="text-decoration-none">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Rejected') }}</div>
                                <h4 class="mb-0">{{ $countRejected }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-x-circle"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}" class="text-decoration-none">
                <div class="card stats-card bg-secondary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total') }}</div>
                                <h4 class="mb-0">{{ $countTotal }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-file-earmark-text"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ═══════ Flash Messages ═══════ --}}
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

    {{-- ═══════ DataTable ═══════ --}}
    <x-ui.datatable
        tableId="estimatesTable"
        :title="__('Estimates')"
        :columns="$estColumns"
        :rows="$estRows"
        :searchable="true"
        :paginate="true"
        :perPage="25"
        :perPageOptions="[10, 25, 50, 100]"
        :exportable="true"
        :filterable="true"
        :createRoute="$newUrl"
        :createLabel="__('New Estimate')"
        :emptyMessage="__('No estimates found.')"
    >
        <x-slot:actions>
            {{-- Status quick-filter tabs --}}
            <div class="btn-group btn-group-sm" role="group">
                <!-- <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline {{ $statusFilter === '' ? 'active' : '' }}">
                    {{ __('All') }} <span class="badge bg-secondary bg-opacity-25 btn-sm text-secondary ms-1">{{ $countTotal }}</span>
                </a>
                <a href="{{ $baseUrl }}?estimate_status=pending" class="btn btn-outline-secondary {{ $statusFilter === 'pending' ? 'active' : '' }}">
                    {{ __('Pending') }} <span class="badge bg-primary bg-opacity-25 text-primary ms-1">{{ $countPending }}</span>
                </a>
                <a href="{{ $baseUrl }}?estimate_status=approved" class="btn btn-outline-secondary {{ $statusFilter === 'approved' ? 'active' : '' }}">
                    {{ __('Approved') }} <span class="badge bg-success bg-opacity-25 text-success ms-1">{{ $countApproved }}</span>
                </a>
                <a href="{{ $baseUrl }}?estimate_status=rejected" class="btn btn-outline-secondary {{ $statusFilter === 'rejected' ? 'active' : '' }}">
                    {{ __('Rejected') }} <span class="badge bg-danger bg-opacity-25 text-danger ms-1">{{ $countRejected }}</span>
                </a> -->
            </div>
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ $baseUrl }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Search') }}</label>
                        <input type="text" class="form-control form-control-sm" name="searchinput"
                               value="{{ $searchInput }}" placeholder="{{ __('Case #, name, email…') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Device') }}</label>
                        <select class="form-select form-select-sm" name="device_id">
                            <option value="">{{ __('All Devices') }}</option>
                            @foreach ($customerDevices as $cd)
                                <option value="{{ $cd->id }}" {{ (string)$deviceFilter === (string)$cd->id ? 'selected' : '' }}>
                                    {{ $cd->label ?? 'Device #'.$cd->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (in_array($role, ['administrator','store_manager','technician']) || ($user && $user->is_admin))
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Customer') }}</label>
                        <select class="form-select form-select-sm" name="customer_id">
                            <option value="">{{ __('All Customers') }}</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ (string)$customerFilterId === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if (in_array($role, ['administrator','store_manager']) || ($user && $user->is_admin))
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Technician') }}</label>
                        <select class="form-select form-select-sm" name="technician_id">
                            <option value="">{{ __('All Technicians') }}</option>
                            @foreach ($technicians as $t)
                                <option value="{{ $t->id }}" {{ (string)$technicianFilterId === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Apply') }}</button>
                        <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
            </form>
        </x-slot:filters>
    </x-ui.datatable>

</div>

{{-- ── Document Preview Modal ── --}}
@livewire('tenant.operations.document-preview-modal', ['tenant' => $tenant ?? null])
@endsection

@push('page-styles')
<style>
    .wcrb-pill--pending { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }
    .wcrb-pill--danger  { color: #991b1b; background: rgba(239,68,68,.10);  border-color: rgba(239,68,68,.25); }

    [data-bs-theme="dark"] .wcrb-pill--pending { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger  { background: rgba(239,68,68,.20); }
</style>
@endpush

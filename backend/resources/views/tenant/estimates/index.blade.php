@extends('tenant.layouts.myaccount', ['title' => 'Estimates'])

@section('content')
@php
    /** @var \App\Models\Tenant|null $tenant */
    /** @var \App\Models\User|null   $user */
    $countPending  = (int) ($countPending ?? 0);
    $countApproved = (int) ($countApproved ?? 0);
    $countRejected = (int) ($countRejected ?? 0);
    $countTotal    = $countPending + $countApproved + $countRejected;

    $viewMode            = $viewMode ?? 'table';
    $searchInput         = $searchInput ?? '';
    $statusFilter        = $statusFilter ?? '';
    $deviceFilter        = $deviceFilter ?? '';
    $customerFilterId    = $customerFilterId ?? '';
    $technicianFilterId  = $technicianFilterId ?? '';

    $estimates       = $estimates ?? collect();
    $customers       = $customers ?? collect();
    $technicians     = $technicians ?? collect();
    $customerDevices = $customerDevices ?? collect();

    $role = is_string($user?->role) ? (string) $user->role : null;

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';

    $baseUrl = $tenantSlug ? route('tenant.estimates.index', ['business' => $tenantSlug]) : '#';
    $newUrl  = $tenantSlug ? route('tenant.estimates.create', ['business' => $tenantSlug]) : '#';

    $formatMoney = function ($cents) {
        if ($cents === null) return '—';
        return '$' . number_format(((int) $cents) / 100, 2, '.', ',');
    };
@endphp

@push('page-styles')
<style>
    :root {
        --rb-primary: #3B82F6;
        --rb-primary-dark: #1D4ED8;
        --rb-card-border: #e2e8f0;
        --rb-text-muted: #64748b;
        --rb-text-dark: #0f172a;
    }
    .est-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .est-stat-card { border-radius: 12px; padding: 1.15rem 1.25rem; color: #fff; text-decoration: none; transition: transform .15s, box-shadow .15s; }
    .est-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.15); color: #fff; text-decoration: none; }
    .est-stat-card .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85; font-weight: 600; }
    .est-stat-card .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.2; }
    .est-stat-pending  { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .est-stat-approved { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .est-stat-rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .est-stat-total    { background: linear-gradient(135deg, #6b7280, #374151); }

    .est-filter-bar { background: #f8fafc; border: 1px solid var(--rb-card-border); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
    .est-filter-bar .form-select, .est-filter-bar .form-control { font-size: .85rem; border-radius: 8px; }

    .est-table th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--rb-text-muted); white-space: nowrap; border-bottom-width: 2px; }
    .est-table td { vertical-align: middle; font-size: .85rem; }
    .est-table tbody tr { cursor: pointer; transition: background .1s; }
    .est-table tbody tr:hover { background: #f1f5f9; }

    .est-badge { padding: .3em .7em; font-weight: 700; border-radius: 6px; font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; display: inline-block; }
    .est-badge-pending  { background: #dbeafe; color: #1e40af; }
    .est-badge-approved { background: #dcfce7; color: #166534; }
    .est-badge-rejected { background: #fee2e2; color: #991b1b; }

    .est-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
    .est-card-item { border: 1px solid var(--rb-card-border); border-radius: 12px; padding: 1.25rem; background: #fff; transition: box-shadow .15s; text-decoration: none; color: inherit; display: block; }
    .est-card-item:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); color: inherit; text-decoration: none; }
    .est-card-item .case-lbl { font-weight: 700; font-size: .95rem; color: var(--rb-text-dark); }
    .est-card-item .meta { font-size: .8rem; color: var(--rb-text-muted); }

    .est-hero { background: radial-gradient(circle at center, #4b5563 0%, #1f2937 100%); border-radius: 16px; padding: 1.75rem 2rem; color: #fff; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .est-hero h2 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #fff !important; }
    .est-hero h2 span { color: var(--rb-primary); }
    .est-hero .subtitle { font-size: .82rem; opacity: .75; }
</style>
@endpush

{{-- ======================== HERO ======================== --}}
<div class="est-hero">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i>{{ __('Estimates') }}</h2>
        <div class="subtitle">{{ __('Manage repair estimates — approve, reject, convert to jobs') }}</div>
    </div>
    <a href="{{ $newUrl }}" class="btn btn-primary btn-sm rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Estimate') }}
    </a>
</div>

{{-- ======================== STATS ======================== --}}
<div class="est-stats">
    <a href="{{ $baseUrl }}?status=pending" class="est-stat-card est-stat-pending">
        <div class="stat-label">{{ __('Pending') }}</div>
        <div class="stat-value">{{ $countPending }}</div>
    </a>
    <a href="{{ $baseUrl }}?status=approved" class="est-stat-card est-stat-approved">
        <div class="stat-label">{{ __('Approved') }}</div>
        <div class="stat-value">{{ $countApproved }}</div>
    </a>
    <a href="{{ $baseUrl }}?status=rejected" class="est-stat-card est-stat-rejected">
        <div class="stat-label">{{ __('Rejected') }}</div>
        <div class="stat-value">{{ $countRejected }}</div>
    </a>
    <a href="{{ $baseUrl }}" class="est-stat-card est-stat-total">
        <div class="stat-label">{{ __('Total') }}</div>
        <div class="stat-value">{{ $countTotal }}</div>
    </a>
</div>

{{-- ======================== FILTERS ======================== --}}
<form method="GET" action="{{ $baseUrl }}" class="est-filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">{{ __('Search') }}</label>
            <input type="text" class="form-control form-control-sm" name="searchinput"
                   value="{{ $searchInput }}" placeholder="{{ __('Case #, name, email…') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">{{ __('Status') }}</label>
            <select class="form-select form-select-sm" name="status">
                <option value="">{{ __('All') }}</option>
                @foreach (['pending','approved','rejected'] as $s)
                    <option value="{{ $s }}" {{ $statusFilter === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">{{ __('Device') }}</label>
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
            <label class="form-label small fw-bold mb-1">{{ __('Customer') }}</label>
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
            <label class="form-label small fw-bold mb-1">{{ __('Technician') }}</label>
            <select class="form-select form-select-sm" name="technician_id">
                <option value="">{{ __('All Technicians') }}</option>
                @foreach ($technicians as $t)
                    <option value="{{ $t->id }}" {{ (string)$technicianFilterId === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
            <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Reset') }}</a>
            <a href="{{ $baseUrl }}?{{ http_build_query(['view' => ($viewMode === 'card' ? 'table' : 'card')]) }}"
               class="btn btn-sm btn-outline-dark rounded-pill px-3">
                <i class="bi {{ $viewMode === 'card' ? 'bi-table' : 'bi-grid' }} me-1"></i>{{ $viewMode === 'card' ? __('Table') : __('Cards') }}
            </a>
        </div>
    </div>
</form>

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

{{-- ======================== TABLE VIEW ======================== --}}
@if ($viewMode === 'table')
<div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
    <div class="table-responsive">
        <table class="table est-table mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width:60px">#</th>
                    <th>{{ __('Case / Title') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Devices') }}</th>
                    <th>{{ __('Dates') }}</th>
                    <th class="text-end">{{ __('Total') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end" style="width:100px">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($estimates as $est)
                    @php
                        $showUrl = route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $est->id]);
                        $totalCents = 0;
                        foreach ($est->items as $it) {
                            $totalCents += max(1, (int)($it->qty ?? 1)) * (int)($it->unit_price_amount_cents ?? 0);
                        }
                        $statusClass = match ($est->status) {
                            'approved' => 'est-badge-approved',
                            'rejected' => 'est-badge-rejected',
                            default    => 'est-badge-pending',
                        };
                    @endphp
                    <tr onclick="window.location='{{ $showUrl }}'">
                        <td class="fw-bold text-muted">{{ $est->id }}</td>
                        <td>
                            <div class="fw-bold" style="font-size:.9rem">{{ $est->case_number ?: '—' }}</div>
                            @if ($est->title && $est->title !== $est->case_number)
                                <div class="text-muted small">{{ Str::limit($est->title, 40) }}</div>
                            @endif
                            @if ($est->assignedTechnician)
                                <div class="small text-primary fw-semibold"><i class="bi bi-person-gear me-1"></i>{{ $est->assignedTechnician->name }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($est->customer)
                                <div class="fw-semibold">{{ $est->customer->name }}</div>
                                <div class="text-muted small">{{ $est->customer->email }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @foreach ($est->devices as $d)
                                <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:.72rem">{{ $d->label_snapshot ?: 'Device' }}</span>
                            @endforeach
                            @if ($est->devices->isEmpty())
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">
                            @if ($est->pickup_date)
                                <div><i class="bi bi-box-arrow-in-down me-1 text-muted"></i>{{ $est->pickup_date->format('M d, Y') }}</div>
                            @endif
                            @if ($est->delivery_date)
                                <div><i class="bi bi-box-arrow-up me-1 text-muted"></i>{{ $est->delivery_date->format('M d, Y') }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ $formatMoney($totalCents) }}</td>
                        <td class="text-center"><span class="est-badge {{ $statusClass }}">{{ ucfirst($est->status ?? 'pending') }}</span></td>
                        <td class="text-end" onclick="event.stopPropagation()">
                            <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0" title="{{ __('View') }}"><i class="bi bi-eye"></i></a>
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0"
                                title="{{ __('Preview / Print') }}"
                                onclick="Livewire.dispatch('openDocumentPreview', { type: 'estimate', id: {{ $est->id }} })">
                                <i class="bi bi-printer"></i>
                            </button>
                            @if (($est->status ?? 'pending') === 'pending')
                                <a href="{{ route('tenant.estimates.edit', ['business' => $tenantSlug, 'estimateId' => $est->id]) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>{{ __('No estimates found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
{{-- ======================== CARD VIEW ======================== --}}
<div class="est-card-grid">
    @forelse ($estimates as $est)
        @php
            $showUrl = route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $est->id]);
            $totalCents = 0;
            foreach ($est->items as $it) {
                $totalCents += max(1, (int)($it->qty ?? 1)) * (int)($it->unit_price_amount_cents ?? 0);
            }
            $statusClass = match ($est->status) {
                'approved' => 'est-badge-approved',
                'rejected' => 'est-badge-rejected',
                default    => 'est-badge-pending',
            };
        @endphp
        <a href="{{ $showUrl }}" class="est-card-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="case-lbl">{{ $est->case_number ?: '#'.$est->id }}</div>
                <span class="est-badge {{ $statusClass }}">{{ ucfirst($est->status ?? 'pending') }}</span>
            </div>
            @if ($est->customer)
                <div class="meta"><i class="bi bi-person me-1"></i>{{ $est->customer->name }}</div>
            @endif
            @if ($est->devices->count())
                <div class="meta mt-1">
                    <i class="bi bi-phone me-1"></i>
                    {{ $est->devices->pluck('label_snapshot')->filter()->implode(', ') ?: 'Devices: '.$est->devices->count() }}
                </div>
            @endif
            <div class="mt-2 fw-bold" style="font-size:.95rem">{{ $formatMoney($totalCents) }}</div>
            @if ($est->created_at)
                <div class="meta mt-1"><i class="bi bi-clock me-1"></i>{{ $est->created_at->format('M d, Y H:i') }}</div>
            @endif
        </a>
    @empty
        <div class="text-center text-muted py-5 w-100">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>{{ __('No estimates found.') }}
        </div>
    @endforelse
</div>
@endif

{{-- ======================== PAGINATION ======================== --}}
@if ($estimates instanceof \Illuminate\Contracts\Pagination\Paginator && $estimates->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $estimates->withQueryString()->links() }}
    </div>
@endif

{{-- ── Document Preview Modal ── --}}
@livewire('tenant.operations.document-preview-modal', ['tenant' => $tenant ?? null])

@endsection

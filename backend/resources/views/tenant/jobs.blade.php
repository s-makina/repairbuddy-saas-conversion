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
    $baseUrl     = $tenantSlug ? route('tenant.jobs.index', ['business' => $tenantSlug]) : '#';

    // ── Columns for <x-ui.datatable>
    $jobColumns = [
        ['key' => 'job_id',        'label' => __('Job #'),       'width' => '90px',  'sortable' => true,  'filter' => true],
        ['key' => 'case_number',   'label' => __('Case #'),      'width' => '120px', 'sortable' => true,  'filter' => true],
        ['key' => 'customer',      'label' => __('Customer'),    'width' => '160px', 'sortable' => true,  'filter' => true],
        ['key' => 'device',        'label' => __('Device'),      'width' => '160px', 'sortable' => true,  'filter' => true],
        ['key' => 'technician',    'label' => __('Technician'),  'width' => '130px', 'sortable' => true,  'filter' => true],
        ['key' => 'status',        'label' => __('Status'),      'width' => '120px', 'sortable' => true,  'dropdown' => true, 'dropdownOptions' => 'window.rbJobStatuses', 'dropdownIdKey' => 'job_id_numeric', 'dropdownValueKey' => 'status_slug', 'dropdownType' => 'status'],
        ['key' => 'priority',      'label' => __('Priority'),    'width' => '100px', 'sortable' => true,  'dropdown' => true, 'dropdownOptions' => 'window.rbPriorities', 'dropdownIdKey' => 'job_id_numeric', 'dropdownValueKey' => 'priority_slug', 'dropdownType' => 'priority'],
        ['key' => 'payment',       'label' => __('Payment'),     'width' => '100px', 'sortable' => true,  'dropdown' => true, 'dropdownOptions' => 'window.rbPaymentStatuses', 'dropdownIdKey' => 'job_id_numeric', 'dropdownValueKey' => 'payment_status_slug', 'dropdownType' => 'payment'],
        ['key' => 'pickup_date',   'label' => __('Pickup'),      'width' => '110px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'delivery_date', 'label' => __('Delivery'),    'width' => '110px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'actions',       'label' => '',                 'width' => '160px', 'sortable' => false, 'align' => 'text-end', 'html' => true],
    ];
@endphp

@push('page-scripts')
<script>
  /* ── Global options for dropdowns ── */
  window.rbJobStatuses = {{ $jobStatusesJson ?? '[]' }};
  window.rbPaymentStatuses = {{ $paymentStatusesJson ?? '[]' }};
  window.rbPriorities = {{ $prioritiesJson ?? '[]' }};

  /* ── openDocPreview: opens DocumentPreviewModal via Livewire dispatch ── */
  function openDocPreview(type, id) {
    if (window.Livewire) {
      window.Livewire.dispatch('openDocumentPreview', { type: type, id: id });
    }
  }

  /* ── Badge class mappings ── */
  const rbBadgeClass = {
    status: {
      'new': 'wcrb-pill--pending',
      'neworder': 'wcrb-pill--pending',
      'in_process': 'wcrb-pill--progress',
      'inprocess': 'wcrb-pill--progress',
      'completed': 'wcrb-pill--active',
      'delivered': 'wcrb-pill--active',
      'waiting_parts': 'wcrb-pill--warning',
      'cancelled': 'wcrb-pill--danger',
    },
    payment: {
      'unpaid': 'wcrb-pill--danger',
      'partial': 'wcrb-pill--warning',
      'paid': 'wcrb-pill--active',
    },
    priority: {
      'normal': 'wcrb-pill--low',
      'high': 'wcrb-pill--high',
      'urgent': 'wcrb-pill--danger',
    }
  };

  /* ── Get options array by type ── */
  function rbGetOptions(type) {
    switch(type) {
      case 'status': return window.rbJobStatuses || [];
      case 'payment': return window.rbPaymentStatuses || [];
      case 'priority': return window.rbPriorities || [];
      default: return [];
    }
  }

  /* ── Populate dropdowns when they open ── */
  document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('show.bs.dropdown', function(e) {
      const dropdown = e.target.closest('.rb-status-dropdown');
      if (!dropdown) return;

      const btn = dropdown.querySelector('button');
      const menu = dropdown.querySelector('.dropdown-menu');
      if (!menu || menu.dataset.populated) return;

      // Get Alpine.js data directly from the element
      let jobId, currentValue, dropdownType;
      try {
        const alpineEl = Alpine.findClosest(dropdown);
        if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
          const data = alpineEl._x_dataStack[0];
          jobId = data.jobId;
          currentValue = data.currentVal;
          dropdownType = data.dropdownType || 'status';
        }
      } catch(err) {
        // Fallback to dataset
      }

      // Fallback to dataset
      if (!jobId) jobId = dropdown.dataset.jobId;
      if (!currentValue) currentValue = dropdown.dataset.currentValue;
      if (!dropdownType) dropdownType = dropdown.dataset.dropdownType || 'status';

      const options = rbGetOptions(dropdownType);
      if (!options.length) {
        console.warn('No options for dropdown type:', dropdownType);
        return;
      }

      // Clear existing items except header
      const header = menu.querySelector('.dropdown-header')?.parentElement;
      const divider = menu.querySelector('.dropdown-divider')?.parentElement;
      menu.innerHTML = '';
      if (header) menu.appendChild(header);
      if (divider) menu.appendChild(divider);

      // Add options
      options.forEach(function(opt) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item py-2 d-flex align-items-center gap-2' + (opt.code === currentValue ? ' active' : '');
        a.href = '#';
        const badgeClass = rbBadgeClass[dropdownType]?.[opt.code] || 'wcrb-pill--inactive';
        a.innerHTML = '<span class="wcrb-pill ' + badgeClass + '" style="font-size: 0.75rem;">' +
          '<span>' + opt.label + '</span></span>' +
          (opt.code === currentValue ? '<i class="bi bi-check2 ms-auto text-success"></i>' : '');
        a.addEventListener('click', function(e) {
          e.preventDefault();
          rbChangeJobField(jobId, dropdownType, opt.code, dropdown);
        });
        li.appendChild(a);
        menu.appendChild(li);
      });

      menu.dataset.populated = '1';
    });
  });

  /* ── Change job field via AJAX ── */
  function rbChangeJobField(jobId, fieldType, newValue, dropdownEl) {
    const tenantSlug = '{{ $tenant->slug ?? '' }}';
    if (!tenantSlug) return;

    const btn = dropdownEl.querySelector('button');
    const badge = dropdownEl.querySelector('.wcrb-pill');
    const originalContent = badge.innerHTML;

    // Show loading state
    badge.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';
    btn.disabled = true;

    // Build request body based on field type
    const body = {};
    if (fieldType === 'status') body.status_slug = newValue;
    else if (fieldType === 'payment') body.payment_status_slug = newValue;
    else if (fieldType === 'priority') body.priority = newValue;

    fetch('/api/' + encodeURIComponent(tenantSlug) + '/app/repairbuddy/jobs/' + jobId, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(body),
      credentials: 'same-origin',
    })
    .then(function(res) {
      if (!res.ok) throw new Error('Request failed');
      return res.json();
    })
    .then(function(data) {
      // Update badge
      const options = rbGetOptions(fieldType);
      const opt = options.find(o => o.code === newValue);
      const badgeClass = rbBadgeClass[fieldType]?.[newValue] || 'wcrb-pill--inactive';
      badge.innerHTML = '<span>' + (opt?.label || newValue) + '</span><i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.6;"></i>';
      badge.className = 'wcrb-pill ' + badgeClass;
      btn.dataset.currentValue = newValue;

      // Mark dropdown as needing repopulation
      dropdownEl.querySelector('.dropdown-menu').dataset.populated = '';

      // Close dropdown
      const inst = bootstrap.Dropdown.getInstance(btn);
      if (inst) inst.hide();
    })
    .catch(function(err) {
      badge.innerHTML = originalContent;
      console.error('Failed to update:', err);
      alert('Failed to update. Please try again.');
    })
    .finally(function() {
      btn.disabled = false;
    });
  }
</script>
@endpush

@section('content')
<div class="container-fluid p-3">

    {{-- ═══════ Stats Cards ═══════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?job_status=new" class="text-decoration-none">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('New') }}</div>
                                <h4 class="mb-0">{{ $jobStats['new'] ?? 0 }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-plus-circle"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?job_status=in_process" class="text-decoration-none">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('In Process') }}</div>
                                <h4 class="mb-0">{{ $jobStats['in_process'] ?? 0 }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-gear-wide-connected"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ $baseUrl }}?job_status=completed" class="text-decoration-none">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Completed') }}</div>
                                <h4 class="mb-0">{{ $jobStats['completed'] ?? 0 }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-check-circle"></i></div>
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
                                <h4 class="mb-0">{{ $jobStats['total'] ?? 0 }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-briefcase-fill"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

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
    .wcrb-pill--active   { color: #065f46; background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); }
    .wcrb-pill--inactive { color: #64748b; background: rgba(100,116,139,.10); border-color: rgba(100,116,139,.25); }
    [data-bs-theme="dark"] .wcrb-pill--progress { background: rgba(59,130,246,.20); }
    [data-bs-theme="dark"] .wcrb-pill--pending  { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning  { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger   { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--high     { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--low      { background: rgba(16,185,129,.20); }
    [data-bs-theme="dark"] .wcrb-pill--active   { background: rgba(16,185,129,.20); }

    /* ── status dropdown ── */
    .rb-status-dropdown .wcrb-pill { cursor: pointer; transition: filter 0.15s; }
    .rb-status-dropdown .wcrb-pill:hover { filter: brightness(0.95); }
    .rb-status-dropdown .dropdown-menu { z-index: 1050; }
    .rb-status-dropdown .dropdown-item.active { background-color: rgba(59,130,246,0.1); }

    /* ── spin animation ── */
    @keyframes rb-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .bi-arrow-repeat.spin { animation: rb-spin 0.8s linear infinite; }

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

<?php

use App\Models\RepairBuddyAppointment;

$tenantSlug = $tenant?->slug ?? '';

// Count appointments by status for stats cards
$countScheduled = $appointments->where('status', RepairBuddyAppointment::STATUS_SCHEDULED)->count();
$countConfirmed = $appointments->where('status', RepairBuddyAppointment::STATUS_CONFIRMED)->count();
$countCompleted = $appointments->where('status', RepairBuddyAppointment::STATUS_COMPLETED)->count();
$countCancelled = $appointments->where('status', RepairBuddyAppointment::STATUS_CANCELLED)->count()
    + $appointments->where('status', RepairBuddyAppointment::STATUS_NO_SHOW)->count();
$countTotal = $appointments->count();

// Status badge map
$statusBadgeMap = [
    RepairBuddyAppointment::STATUS_SCHEDULED => 'wcrb-pill--info',
    RepairBuddyAppointment::STATUS_CONFIRMED => 'wcrb-pill--active',
    RepairBuddyAppointment::STATUS_COMPLETED => 'wcrb-pill--secondary',
    RepairBuddyAppointment::STATUS_CANCELLED => 'wcrb-pill--danger',
    RepairBuddyAppointment::STATUS_NO_SHOW => 'wcrb-pill--warning',
];

// Build datatable columns
$apptColumns = [
    ['key' => 'datetime',    'label' => __('Date & Time'),    'width' => '140px', 'sortable' => true, 'nowrap' => true, 'html' => true],
    ['key' => 'customer',    'label' => __('Customer'),       'sortable' => true, 'filter' => true, 'html' => true],
    ['key' => 'type',        'label' => __('Type'),           'sortable' => true, 'filter' => true],
    ['key' => 'related',     'label' => __('Related To'),     'sortable' => false, 'html' => true],
    ['key' => 'status',      'label' => __('Status'),         'width' => '110px', 'sortable' => true, 'badge' => true],
    ['key' => 'actions',     'label' => '',                   'width' => '160px', 'sortable' => false, 'align' => 'text-end', 'html' => true],
];

// Build datatable rows
$apptRows = [];
foreach ($appointments as $appt) {
    // Build related link
    $relatedHtml = '<span class="badge bg-secondary">' . __('Standalone') . '</span>';
    if ($appt->job) {
        $jobUrl = route('tenant.jobs.show', ['business' => $tenantSlug, 'jobId' => $appt->job->id]);
        $relatedHtml = '<a href="' . e($jobUrl) . '" class="text-decoration-none">'
            . '<span class="badge bg-primary"><i class="bi bi-tools me-1"></i>' . e($appt->job->case_number) . '</span></a>';
    } elseif ($appt->estimate) {
        $estUrl = route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $appt->estimate->id]);
        $relatedHtml = '<a href="' . e($estUrl) . '" class="text-decoration-none">'
            . '<span class="badge bg-warning text-dark"><i class="bi bi-file-text me-1"></i>' . e($appt->estimate->case_number) . '</span></a>';
    }

    // Build actions dropdown
    $actionHtml = '<div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">'
        . '<div class="dropdown position-static">'
        . '<button class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" style="padding: .25rem .45rem;"><i class="bi bi-three-dots" style="font-size:.75rem;"></i></button>'
        . '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem; min-width: 160px;">';

    if ($appt->status === RepairBuddyAppointment::STATUS_SCHEDULED) {
        $actionHtml .= '<li><button class="dropdown-item py-2" type="button" wire:click="confirmAppointment(' . $appt->id . ')"><i class="bi bi-check-circle me-2 text-success"></i>' . e(__('Confirm')) . '</button></li>';
    }

    if (in_array($appt->status, [RepairBuddyAppointment::STATUS_SCHEDULED, RepairBuddyAppointment::STATUS_CONFIRMED])) {
        $actionHtml .= '<li><button class="dropdown-item py-2" type="button" wire:click="openRescheduleModal(' . $appt->id . ')"><i class="bi bi-calendar-event me-2 text-primary"></i>' . e(__('Reschedule')) . '</button></li>';
        $actionHtml .= '<li><button class="dropdown-item py-2" type="button" wire:click="markCompleted(' . $appt->id . ')"><i class="bi bi-check2-all me-2 text-secondary"></i>' . e(__('Mark Completed')) . '</button></li>';
        $actionHtml .= '<li><button class="dropdown-item py-2" type="button" wire:click="markNoShow(' . $appt->id . ')"><i class="bi bi-person-x me-2 text-warning"></i>' . e(__('Mark No Show')) . '</button></li>';
        $actionHtml .= '<li><hr class="dropdown-divider"></li>';
        $actionHtml .= '<li><button class="dropdown-item py-2 text-danger" type="button" wire:click="openCancelModal(' . $appt->id . ')"><i class="bi bi-x-circle me-2"></i>' . e(__('Cancel')) . '</button></li>';
    }

    $actionHtml .= '</ul></div></div>';

    $apptRows[] = [
        'id'          => $appt->id,
        'datetime'    => '<div class="fw-semibold">' . $appt->appointment_date->format('M d, Y') . '</div><small class="text-muted">' . e($appt->time_slot_display) . '</small>',
        'customer'    => '<div class="fw-semibold">' . e($appt->customer?->name ?? '—') . '</div><small class="text-muted">' . e($appt->customer?->email ?? '') . '</small>',
        'type'        => $appt->title ?? $appt->appointmentSetting?->title ?? '—',
        'related'     => $relatedHtml,
        'status'      => ucfirst($appt->status),
        '_badgeClass_status' => $statusBadgeMap[$appt->status] ?? 'wcrb-pill--secondary',
        'actions'     => $actionHtml,
    ];
}
?>

<div class="container-fluid p-3">

    {{-- ═══════ Stats Cards ═══════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <a href="#" wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_SCHEDULED }}')" class="text-decoration-none">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Scheduled') }}</div>
                                <h4 class="mb-0">{{ $countScheduled }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-calendar-event"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="#" wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_CONFIRMED }}')" class="text-decoration-none">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Confirmed') }}</div>
                                <h4 class="mb-0">{{ $countConfirmed }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-check-circle"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="#" wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_COMPLETED }}')" class="text-decoration-none">
                <div class="card stats-card bg-secondary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Completed') }}</div>
                                <h4 class="mb-0">{{ $countCompleted }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-check2-all"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="#" wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_CANCELLED }}')" class="text-decoration-none">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Cancelled') }}</div>
                                <h4 class="mb-0">{{ $countCancelled }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-x-circle"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="#" wire:click="$set('statusFilter', 'all')" class="text-decoration-none">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total') }}</div>
                                <h4 class="mb-0">{{ $countTotal }}</h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-calendar-check"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="{{ route('tenant.dashboard', ['business' => $tenantSlug, 'screen' => 'calendar']) }}" class="text-decoration-none">
                <div class="card stats-card bg-dark text-white">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Calendar') }}</div>
                                <h4 class="mb-0"><i class="bi bi-calendar3"></i></h4>
                            </div>
                            <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-arrow-right"></i></div>
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
        wire:key="appointments-{{ $statusFilter }}-{{ $typeFilter }}-{{ $dateFilter }}-{{ $search }}"
        tableId="appointmentsTable"
        :title="__('Appointments')"
        :columns="$apptColumns"
        :rows="$apptRows"
        :searchable="true"
        :paginate="true"
        :exportable="true"
        :filterable="false"
        :emptyMessage="__('No appointments found.')"
    >
        <x-slot:actions>
            <!-- <div class="btn-group btn-group-sm" role="group">
                <button wire:click="$set('statusFilter', 'all')" class="btn btn-sm btn-outline {{ $statusFilter === 'all' ? 'active' : '' }}">
                    {{ __('All') }}
                </button>
                <button wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_SCHEDULED }}')" class="btn btn-outline-info {{ $statusFilter === RepairBuddyAppointment::STATUS_SCHEDULED ? 'active' : '' }}">
                    {{ __('Scheduled') }}
                </button>
                <button wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_CONFIRMED }}')" class="btn btn-outline-success {{ $statusFilter === RepairBuddyAppointment::STATUS_CONFIRMED ? 'active' : '' }}">
                    {{ __('Confirmed') }}
                </button>
                <button wire:click="$set('statusFilter', '{{ RepairBuddyAppointment::STATUS_COMPLETED }}')" class="btn btn-outline-secondary {{ $statusFilter === RepairBuddyAppointment::STATUS_COMPLETED ? 'active' : '' }}">
                    {{ __('Completed') }}
                </button>
            </div> -->
        </x-slot:actions>

        <x-slot:filters>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" style="font-size: 0.75rem;">{{ __('Search') }}</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                           placeholder="{{ __('Customer, case, or title...') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size: 0.75rem;">{{ __('Status') }}</label>
                    <select class="form-select form-select-sm" wire:model.live="statusFilter">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size: 0.75rem;">{{ __('Type') }}</label>
                    <select class="form-select form-select-sm" wire:model.live="typeFilter">
                        <option value="all">{{ __('All Types') }}</option>
                        @foreach ($appointmentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size: 0.75rem;">{{ __('Date') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFilter">
                </div>
                <div class="col-auto d-flex gap-2">
                    @if ($search || $statusFilter !== 'all' || $dateFilter || $typeFilter !== 'all')
                        <button wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('dateFilter', ''); $set('typeFilter', 'all')"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    @endif
                </div>
            </div>
        </x-slot:filters>
    </x-ui.datatable>

    {{-- ═══════ Pagination ═══════ --}}
    <div class="mt-4">
        {{ $appointments->links() }}
    </div>

    {{-- Cancel Modal --}}
    @if ($showCancelModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Cancel Appointment') }}</h5>
                        <button type="button" class="btn-close" wire:click="closeCancelModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">{{ __('Are you sure you want to cancel this appointment?') }}</p>
                        <div class="mb-3">
                            <label for="cancellationReason" class="form-label">{{ __('Cancellation Reason (optional)') }}</label>
                            <textarea
                                id="cancellationReason"
                                wire:model="cancellationReason"
                                class="form-control"
                                rows="3"
                                placeholder="{{ __('Enter reason for cancellation...') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeCancelModal">
                            {{ __('Keep Appointment') }}
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="cancelAppointment">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ __('Cancel Appointment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reschedule Modal --}}
    @if ($showRescheduleModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Reschedule Appointment') }}</h5>
                        <button type="button" class="btn-close" wire:click="closeRescheduleModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rescheduleDate" class="form-label">{{ __('New Date') }}</label>
                            <input
                                type="date"
                                id="rescheduleDate"
                                wire:model.live="rescheduleDate"
                                class="form-control"
                                min="{{ now()->toDateString() }}"
                            >
                        </div>
                        <div class="mb-3">
                            <label for="rescheduleTime" class="form-label">{{ __('New Time Slot') }}</label>
                            @if (count($availableTimeSlots) > 0)
                                <select id="rescheduleTime" wire:model="rescheduleTime" class="form-select">
                                    <option value="">{{ __('Select a time slot...') }}</option>
                                    @foreach ($availableTimeSlots as $slot)
                                        <option value="{{ $slot['value'] }}">
                                            {{ $slot['label'] }} ({{ $slot['type'] }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <p class="text-muted small mb-0">{{ __('No available time slots for the selected date.') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRescheduleModal">
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="rescheduleAppointment"
                            @if (!$rescheduleDate || !$rescheduleTime) disabled @endif
                        >
                            <i class="bi bi-calendar-check me-1"></i>
                            {{ __('Reschedule') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('page-styles')
<style>
    .wcrb-pill--info     { color: #0369a1; background: rgba(14,165,233,.10); border-color: rgba(14,165,233,.25); }
    .wcrb-pill--active   { color: #15803d; background: rgba(34,197,94,.10);  border-color: rgba(34,197,94,.25); }
    .wcrb-pill--secondary{ color: #475569; background: rgba(100,116,139,.10);border-color: rgba(100,116,139,.25); }
    .wcrb-pill--danger   { color: #991b1b; background: rgba(239,68,68,.10);  border-color: rgba(239,68,68,.25); }
    .wcrb-pill--warning  { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }

    [data-bs-theme="dark"] .wcrb-pill--info     { background: rgba(14,165,233,.20); }
    [data-bs-theme="dark"] .wcrb-pill--active   { background: rgba(34,197,94,.20); }
    [data-bs-theme="dark"] .wcrb-pill--secondary{ background: rgba(100,116,139,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger   { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning  { background: rgba(245,158,11,.20); }

    .stats-card {
        transition: transform .15s, box-shadow .15s;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
</style>
@endpush

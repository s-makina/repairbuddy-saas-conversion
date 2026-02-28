<?php

use App\Models\RepairBuddyAppointment;

$tenantSlug = $tenant?->slug ?? '';
?>

<div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-calendar-check me-2 text-primary"></i>
                    {{ __('Appointments') }}
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('tenant.dashboard', ['business' => $tenantSlug, 'screen' => 'calendar']) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ __('Calendar View') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0"
                            placeholder="{{ __('Search by customer, case, or title...') }}"
                        >
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="typeFilter" class="form-select form-select-sm">
                        <option value="all">{{ __('All Types') }}</option>
                        @foreach ($appointmentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input
                        type="date"
                        wire:model.live="dateFilter"
                        class="form-control form-control-sm"
                        placeholder="{{ __('Filter by date') }}"
                    >
                </div>
                <div class="col-md-3 text-end">
                    @if ($search || $statusFilter !== 'all' || $dateFilter || $typeFilter !== 'all')
                        <button
                            wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('dateFilter', ''); $set('typeFilter', 'all')"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            {{ __('Clear Filters') }}
                        </button>
                    @endif
                </div>
            </div>

            @if ($appointments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Date & Time') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Related To') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $appointment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $appointment->appointment_date->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $appointment->time_slot_display }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $appointment->customer?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $appointment->customer?->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        {{ $appointment->title ?? $appointment->appointmentSetting?->title ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($appointment->job)
                                            <a href="{{ route('tenant.jobs.show', ['business' => $tenantSlug, 'jobId' => $appointment->job->id]) }}" class="text-decoration-none">
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-tools me-1"></i>
                                                    {{ $appointment->job->case_number }}
                                                </span>
                                            </a>
                                        @elseif ($appointment->estimate)
                                            <a href="{{ route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $appointment->estimate->id]) }}" class="text-decoration-none">
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-file-text me-1"></i>
                                                    {{ $appointment->estimate->case_number }}
                                                </span>
                                            </a>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Standalone') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClasses = [
                                                RepairBuddyAppointment::STATUS_SCHEDULED => 'bg-info',
                                                RepairBuddyAppointment::STATUS_CONFIRMED => 'bg-success',
                                                RepairBuddyAppointment::STATUS_COMPLETED => 'bg-secondary',
                                                RepairBuddyAppointment::STATUS_CANCELLED => 'bg-danger',
                                                RepairBuddyAppointment::STATUS_NO_SHOW => 'bg-danger',
                                            ];
                                            $statusClass = $statusClasses[$appointment->status] ?? 'bg-secondary';
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                @if ($appointment->status === RepairBuddyAppointment::STATUS_SCHEDULED)
                                                    <li>
                                                        <button class="dropdown-item py-2" wire:click="confirmAppointment({{ $appointment->id }})">
                                                            <i class="bi bi-check-circle me-2 text-success"></i>
                                                            {{ __('Confirm') }}
                                                        </button>
                                                    </li>
                                                @endif
                                                @if (in_array($appointment->status, [RepairBuddyAppointment::STATUS_SCHEDULED, RepairBuddyAppointment::STATUS_CONFIRMED]))
                                                    <li>
                                                        <button class="dropdown-item py-2" wire:click="openRescheduleModal({{ $appointment->id }})">
                                                            <i class="bi bi-calendar-event me-2 text-primary"></i>
                                                            {{ __('Reschedule') }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item py-2" wire:click="markCompleted({{ $appointment->id }})">
                                                            <i class="bi bi-check2-all me-2 text-secondary"></i>
                                                            {{ __('Mark Completed') }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item py-2" wire:click="markNoShow({{ $appointment->id }})">
                                                            <i class="bi bi-person-x me-2 text-warning"></i>
                                                            {{ __('Mark No Show') }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item py-2 text-danger" wire:click="openCancelModal({{ $appointment->id }})">
                                                            <i class="bi bi-x-circle me-2"></i>
                                                            {{ __('Cancel') }}
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $appointments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <h5 class="text-muted mt-3">{{ __('No appointments found') }}</h5>
                    <p class="text-muted small mb-0">
                        @if ($search || $statusFilter !== 'all' || $dateFilter || $typeFilter !== 'all')
                            {{ __('Try adjusting your filters.') }}
                        @else
                            {{ __('Appointments will appear here when created through bookings.') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
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

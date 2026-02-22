@php
    $appointmentSettings = $appointmentSettings ?? collect();
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $business = $tenant->slug ?? '';
@endphp

<style>
    .appointment-card {
        transition: all 0.2s ease;
        border-radius: 8px;
    }
    .appointment-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .schedule-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    .day-row:hover {
        background-color: #f8f9fa;
    }
    .quick-actions {
        gap: 0.5rem;
    }
    .stat-box {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
    }
</style>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <div>
            <h5 class="mb-1"><i class="bi bi-calendar-check me-2 text-primary"></i>Appointment Settings</h5>
            <small class="text-muted">Configure appointment types and availability schedules</small>
        </div>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addNewAppointment">
            <i class="bi bi-plus-circle me-1"></i> Add New
        </button>
    </div>
    <div class="card-body p-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Existing Appointment Options --}}
        @forelse($appointmentSettings as $setting)
            <div class="appointment-card card mb-3 border {{ $setting->is_enabled ? 'border-success' : 'border-secondary' }}">
                <div class="card-header d-flex justify-content-between align-items-center py-3" style="background: {{ $setting->is_enabled ? '#f0fdf4' : '#f8f9fa' }};">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $setting->title }}</h6>
                            @if($setting->description)
                                <small class="text-muted">{{ $setting->description }}</small>
                            @endif
                        </div>
                        @if($setting->is_enabled)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>Active
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-pause-circle me-1"></i>Inactive
                            </span>
                        @endif
                    </div>
                    <div class="d-flex quick-actions">
                        <form method="POST" action="{{ route('tenant.settings.appointments.toggle', ['business' => $business, 'setting' => $setting->id]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $setting->is_enabled ? 'btn-warning' : 'btn-success' }}" title="{{ $setting->is_enabled ? 'Disable' : 'Enable' }}">
                                <i class="bi {{ $setting->is_enabled ? 'bi-pause-fill' : 'bi-play-fill' }} me-1"></i>
                                {{ $setting->is_enabled ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editAppt{{ $setting->id }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <form method="POST" action="{{ route('tenant.settings.appointments.delete', ['business' => $business, 'setting' => $setting->id]) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this appointment type? This action cannot be undone.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="stat-box text-center">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-clock me-1"></i>Duration
                                </div>
                                <div class="fw-bold fs-5">{{ $setting->slot_duration_minutes }} <small class="text-muted">min</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box text-center">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-hourglass-split me-1"></i>Buffer Time
                                </div>
                                <div class="fw-bold fs-5">{{ $setting->buffer_minutes }} <small class="text-muted">min</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box text-center">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-calendar-day me-1"></i>Max Per Day
                                </div>
                                <div class="fw-bold fs-5">{{ $setting->max_appointments_per_day }}</div>
                            </div>
                        </div>
                    </div>

                    @php $slots = is_array($setting->time_slots) ? $setting->time_slots : []; @endphp
                    @if(count($slots) > 0)
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-calendar-week me-2 text-primary"></i>
                                <span class="fw-semibold">Weekly Schedule</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($slots as $slot)
                                    @if($slot['enabled'] ?? false)
                                        <span class="schedule-badge bg-primary bg-opacity-10 text-primary border border-primary">
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            <strong>{{ ucfirst(substr($slot['day'], 0, 3)) }}</strong> {{ $slot['start'] }} - {{ $slot['end'] }}
                                        </span>
                                    @endif
                                @endforeach
                                @php $disabledDays = collect($slots)->where('enabled', false)->count(); @endphp
                                @if($disabledDays > 0)
                                    <span class="schedule-badge bg-light text-muted border">
                                        <i class="bi bi-x-circle me-1"></i>{{ $disabledDays }} day(s) off
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Edit form (collapsed) --}}
                <div class="collapse" id="editAppt{{ $setting->id }}">
                    <div class="card-body border-top pt-4 bg-light">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-pencil-square me-2"></i>Edit Appointment Type
                        </h6>
                        <form method="POST" action="{{ route('tenant.settings.appointments.update', ['business' => $business, 'setting' => $setting->id]) }}">
                            @csrf
                            <div class="card mb-3 bg-white">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Basic Information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                Title <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="title" class="form-control" value="{{ $setting->title }}" placeholder="e.g., Standard Repair" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Description</label>
                                            <input type="text" name="description" class="form-control" value="{{ $setting->description }}" placeholder="Optional description">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3 bg-white">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Timing & Capacity</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock me-1"></i>Slot Duration (minutes)
                                            </label>
                                            <input type="number" name="slot_duration_minutes" class="form-control" value="{{ $setting->slot_duration_minutes }}" min="5" max="480" required>
                                            <small class="text-muted">How long each appointment lasts</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-hourglass-split me-1"></i>Buffer Time (minutes)
                                            </label>
                                            <input type="number" name="buffer_minutes" class="form-control" value="{{ $setting->buffer_minutes }}" min="0" max="120" required>
                                            <small class="text-muted">Break time between appointments</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-calendar-day me-1"></i>Max Appointments / Day
                                            </label>
                                            <input type="number" name="max_appointments_per_day" class="form-control" value="{{ $setting->max_appointments_per_day }}" min="1" max="200" required>
                                            <small class="text-muted">Daily booking limit</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Weekly time slots --}}
                            <div class="card bg-white">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">
                                        <i class="bi bi-calendar-week me-1"></i>Weekly Schedule
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:60px;" class="text-center">Active</th>
                                                    <th style="width:120px;">Day</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($days as $dayIdx => $day)
                                                    @php
                                                        $daySlot = collect($slots)->firstWhere('day', $day);
                                                        $dayEnabled = $daySlot ? ($daySlot['enabled'] ?? false) : false;
                                                        $dayStart = $daySlot ? ($daySlot['start'] ?? '09:00') : '09:00';
                                                        $dayEnd = $daySlot ? ($daySlot['end'] ?? '17:00') : '17:00';
                                                    @endphp
                                                    <tr class="day-row">
                                                        <td class="text-center align-middle">
                                                            <input type="hidden" name="time_slots[{{ $dayIdx }}][day]" value="{{ $day }}">
                                                            <input type="hidden" name="time_slots[{{ $dayIdx }}][enabled]" value="0">
                                                            <div class="form-check d-inline-block">
                                                                <input type="checkbox" name="time_slots[{{ $dayIdx }}][enabled]" value="1" class="form-check-input" id="edit_day_{{ $setting->id }}_{{ $day }}" {{ $dayEnabled ? 'checked' : '' }}>
                                                            </div>
                                                        </td>
                                                        <td class="align-middle">
                                                            <label for="edit_day_{{ $setting->id }}_{{ $day }}" class="fw-semibold mb-0" style="cursor: pointer;">
                                                                {{ ucfirst($day) }}
                                                            </label>
                                                        </td>
                                                        <td>
                                                            <input type="time" name="time_slots[{{ $dayIdx }}][start]" class="form-control" value="{{ $dayStart }}">
                                                        </td>
                                                        <td>
                                                            <input type="time" name="time_slots[{{ $dayIdx }}][end]" class="form-control" value="{{ $dayEnd }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#editAppt{{ $setting->id }}">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-muted mb-2">No Appointment Types Yet</h6>
                <p class="text-muted small mb-3">Create your first appointment type to start accepting bookings</p>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addNewAppointment">
                    <i class="bi bi-plus-circle me-1"></i> Create Appointment Type
                </button>
            </div>
        @endforelse

        {{-- Add New Appointment Option --}}
        <div class="collapse {{ $appointmentSettings->isEmpty() ? 'show' : '' }} mt-4" id="addNewAppointment">
            <div class="card border-primary">
                <div class="card-header bg-primary bg-opacity-10 border-primary">
                    <h6 class="mb-0 text-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create New Appointment Type
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('tenant.settings.appointments.store', ['business' => $business]) }}">
                        @csrf
                        <div class="card mb-3 bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Basic Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            Title <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g., Standard Repair Appointment" required>
                                        <small class="text-muted">Give this appointment type a clear name</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Description</label>
                                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                                        <small class="text-muted">Help customers understand this option</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3 bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Timing & Capacity</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-clock me-1"></i>Slot Duration (minutes)
                                        </label>
                                        <input type="number" name="slot_duration_minutes" class="form-control" value="30" min="5" max="480" required>
                                        <small class="text-muted">How long each appointment lasts</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-hourglass-split me-1"></i>Buffer Time (minutes)
                                        </label>
                                        <input type="number" name="buffer_minutes" class="form-control" value="10" min="0" max="120" required>
                                        <small class="text-muted">Break time between appointments</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-calendar-day me-1"></i>Max Appointments / Day
                                        </label>
                                        <input type="number" name="max_appointments_per_day" class="form-control" value="20" min="1" max="200" required>
                                        <small class="text-muted">Daily booking limit</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Weekly time slots for new entry --}}
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-calendar-week me-1"></i>Weekly Schedule
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-hover bg-white mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:60px;" class="text-center">Active</th>
                                                <th style="width:120px;">Day</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($days as $dayIdx => $day)
                                                @php $isWeekday = ! in_array($day, ['saturday', 'sunday']); @endphp
                                                <tr class="day-row">
                                                    <td class="text-center align-middle">
                                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][day]" value="{{ $day }}">
                                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][enabled]" value="0">
                                                        <div class="form-check d-inline-block">
                                                            <input type="checkbox" name="time_slots[{{ $dayIdx }}][enabled]" value="1" class="form-check-input" id="new_day_{{ $day }}" {{ $isWeekday ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <label for="new_day_{{ $day }}" class="fw-semibold mb-0" style="cursor: pointer;">
                                                            {{ ucfirst($day) }}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <input type="time" name="time_slots[{{ $dayIdx }}][start]" class="form-control" value="09:00">
                                                    </td>
                                                    <td>
                                                        <input type="time" name="time_slots[{{ $dayIdx }}][end]" class="form-control" value="17:00">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-circle me-1"></i> Create Appointment Type
                            </button>
                            @if($appointmentSettings->isNotEmpty())
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#addNewAppointment">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

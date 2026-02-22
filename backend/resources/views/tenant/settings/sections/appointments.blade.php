@php
    $appointmentSettings = $appointmentSettings ?? collect();
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $business = $tenant->slug ?? '';
@endphp

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Appointment Options</h5>
    </div>
    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Existing Appointment Options --}}
        @forelse($appointmentSettings as $setting)
            <div class="card mb-3 border {{ $setting->is_enabled ? 'border-success' : 'border-secondary' }}">
                <div class="card-header d-flex justify-content-between align-items-center py-2" style="background: {{ $setting->is_enabled ? '#f0fdf4' : '#f8f9fa' }};">
                    <div>
                        <strong>{{ $setting->title }}</strong>
                        @if($setting->is_enabled)
                            <span class="badge bg-success ms-2">Enabled</span>
                        @else
                            <span class="badge bg-secondary ms-2">Disabled</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('tenant.settings.appointments.toggle', ['business' => $business, 'setting' => $setting->id]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $setting->is_enabled ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $setting->is_enabled ? 'Disable' : 'Enable' }}">
                                <i class="bi {{ $setting->is_enabled ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editAppt{{ $setting->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('tenant.settings.appointments.delete', ['business' => $business, 'setting' => $setting->id]) }}" class="d-inline" onsubmit="return confirm('Delete this appointment option?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body py-2">
                    @if($setting->description)
                        <p class="text-muted small mb-2">{{ $setting->description }}</p>
                    @endif
                    <div class="row g-2 small">
                        <div class="col-auto"><strong>Duration:</strong> {{ $setting->slot_duration_minutes }} min</div>
                        <div class="col-auto"><strong>Buffer:</strong> {{ $setting->buffer_minutes }} min</div>
                        <div class="col-auto"><strong>Max/day:</strong> {{ $setting->max_appointments_per_day }}</div>
                    </div>

                    @php $slots = is_array($setting->time_slots) ? $setting->time_slots : []; @endphp
                    @if(count($slots) > 0)
                        <div class="mt-2">
                            <small class="fw-semibold text-muted">Schedule:</small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($slots as $slot)
                                    @if($slot['enabled'] ?? false)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            {{ ucfirst($slot['day']) }} {{ $slot['start'] }}-{{ $slot['end'] }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">
                                            {{ ucfirst($slot['day']) }} <small>(off)</small>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Edit form (collapsed) --}}
                <div class="collapse" id="editAppt{{ $setting->id }}">
                    <div class="card-body border-top pt-3">
                        <form method="POST" action="{{ route('tenant.settings.appointments.update', ['business' => $business, 'setting' => $setting->id]) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ $setting->title }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Description</label>
                                    <input type="text" name="description" class="form-control" value="{{ $setting->description }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Slot Duration (min)</label>
                                    <input type="number" name="slot_duration_minutes" class="form-control" value="{{ $setting->slot_duration_minutes }}" min="5" max="480" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Buffer Time (min)</label>
                                    <input type="number" name="buffer_minutes" class="form-control" value="{{ $setting->buffer_minutes }}" min="0" max="120" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Max Appointments / Day</label>
                                    <input type="number" name="max_appointments_per_day" class="form-control" value="{{ $setting->max_appointments_per_day }}" min="1" max="200" required>
                                </div>
                            </div>

                            {{-- Weekly time slots --}}
                            <div class="mt-3">
                                <label class="form-label fw-semibold">Weekly Schedule</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:30px;"></th>
                                                <th>Day</th>
                                                <th>Start</th>
                                                <th>End</th>
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
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][day]" value="{{ $day }}">
                                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][enabled]" value="0">
                                                        <input type="checkbox" name="time_slots[{{ $dayIdx }}][enabled]" value="1" class="form-check-input" {{ $dayEnabled ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="align-middle fw-semibold">{{ ucfirst($day) }}</td>
                                                    <td><input type="time" name="time_slots[{{ $dayIdx }}][start]" class="form-control form-control-sm" value="{{ $dayStart }}"></td>
                                                    <td><input type="time" name="time_slots[{{ $dayIdx }}][end]" class="form-control form-control-sm" value="{{ $dayEnd }}"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check2 me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">No appointment options configured yet.</p>
            </div>
        @endforelse

        <hr>

        {{-- Add New Appointment Option --}}
        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i> Add Appointment Option</h6>
        <form method="POST" action="{{ route('tenant.settings.appointments.store', ['business' => $business]) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Standard Repair Appointment" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional description">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Slot Duration (min)</label>
                    <input type="number" name="slot_duration_minutes" class="form-control" value="30" min="5" max="480" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Buffer Time (min)</label>
                    <input type="number" name="buffer_minutes" class="form-control" value="10" min="0" max="120" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Max Appointments / Day</label>
                    <input type="number" name="max_appointments_per_day" class="form-control" value="20" min="1" max="200" required>
                </div>
            </div>

            {{-- Weekly time slots for new entry --}}
            <div class="mt-3">
                <label class="form-label fw-semibold">Weekly Schedule</label>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Day</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $dayIdx => $day)
                                @php $isWeekday = ! in_array($day, ['saturday', 'sunday']); @endphp
                                <tr>
                                    <td class="text-center align-middle">
                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][day]" value="{{ $day }}">
                                        <input type="hidden" name="time_slots[{{ $dayIdx }}][enabled]" value="0">
                                        <input type="checkbox" name="time_slots[{{ $dayIdx }}][enabled]" value="1" class="form-check-input" {{ $isWeekday ? 'checked' : '' }}>
                                    </td>
                                    <td class="align-middle fw-semibold">{{ ucfirst($day) }}</td>
                                    <td><input type="time" name="time_slots[{{ $dayIdx }}][start]" class="form-control form-control-sm" value="09:00"></td>
                                    <td><input type="time" name="time_slots[{{ $dayIdx }}][end]" class="form-control form-control-sm" value="17:00"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Create Appointment Option
                </button>
            </div>
        </form>
    </div>
</div>

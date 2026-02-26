@extends('tenant.layouts.myaccount', ['title' => 'Time Log'])

@section('content')
@php
    $userRole = is_string($userRole ?? null) ? (string) $userRole : (is_object($user ?? null) ? (string) ($user->role ?? 'guest') : 'guest');
    $licenseState = (bool) ($licenseState ?? true);
    $stats = is_array($stats ?? null) ? $stats : [];

    $technician_id = $technician_id ?? (is_object($user ?? null) ? ($user->id ?? null) : null);
    $device_label = is_string($device_label ?? null) ? (string) $device_label : '';

    $job_id = $job_id ?? null;
    $device_id = $device_id ?? '';
    $device_serial = $device_serial ?? '';
    $device_index = $device_index ?? 0;

    $selected_value = is_string($selected_value ?? null) ? $selected_value : '';
    $eligible_jobs_with_devices_dropdown_html = is_string($eligible_jobs_with_devices_dropdown_html ?? null) ? (string) $eligible_jobs_with_devices_dropdown_html : '';

    $forma_job_id = $forma_job_id ?? null;
    $display_name = $display_name ?? '';

    $timelog_nonce_field_html = is_string($timelog_nonce_field_html ?? null) ? (string) $timelog_nonce_field_html : '';

    $timelog_activity_types_dropdown_html = is_string($timelog_activity_types_dropdown_html ?? null) ? (string) $timelog_activity_types_dropdown_html : '';
    $timelog_activity_types_dropdown_manual_html = is_string($timelog_activity_types_dropdown_manual_html ?? null) ? (string) $timelog_activity_types_dropdown_manual_html : '';

    $productivity_stats = is_array($productivity_stats ?? null) ? $productivity_stats : [];
    $activity_distribution = is_array($activity_distribution ?? null) ? $activity_distribution : [];

    $activity_types = is_array($activity_types ?? null) ? $activity_types : null;
    if (empty($activity_types) || !is_array($activity_types)) {
        $activity_types = array('Diagnosis', 'Repair', 'Testing', 'Cleaning', 'Consultation', 'Other');
    }

    $recent_time_logs_html = is_string($recent_time_logs_html ?? null) ? (string) $recent_time_logs_html : '';

    $activity_colors = [
        'repair' => 'primary',
        'diagnostic' => 'info',
        'diagnosis' => 'info',
        'testing' => 'success',
        'test' => 'success',
        'cleaning' => 'warning',
        'consultation' => 'secondary',
        'other' => 'secondary'
    ];

    $default_color = 'secondary';

    $canAccess = in_array($userRole, ['technician', 'administrator', 'store_manager'], true);
@endphp

@if (! $canAccess)
    {{ __("You do not have sufficient permissions to access this page.") }}
@elseif (! $licenseState)
    {{ __('This is a pro feature please activate your license.') }}
@else

<!-- Time Logs Content -->
<main class="dashboard-content container-fluid py-4">
<!-- Stats Overview -->
<div class="row g-3 mb-4">
    <!-- Today's Hours -->
    <div class="col">
        <div class="card stats-card bg-primary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">{{ __( 'Today' ) }}</h6>
                <h4 class="mb-0" id="todayHours">{{ $stats['today_hours'] ?? '' }}h</h4>
                <small class="text-white-50">{{ __( 'Hours Logged' ) }}</small>
            </div>
        </div>
    </div>
    
    <!-- This Week -->
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">{{ __( 'This Week' ) }}</h6>
                <h4 class="mb-0" id="weekHours">{{ $stats['week_hours'] ?? '' }}h</h4>
                <small class="text-white-50">{{ __( 'Total Hours' ) }}</small>
            </div>
        </div>
    </div>
   
    <!-- Billable Hours -->
    <div class="col">
        <div class="card stats-card bg-warning text-dark">
            <div class="card-body text-center p-3">
                <h6 class="card-title mb-1">{{ __( 'Billable' ) }}</h6>
                <h4 class="mb-0" id="billableRate">{{ $stats['billable_rate'] ?? '' }}%</h4>
                <small class="text-muted">{{ __( 'This Week' ) }}</small>
            </div>
        </div>
    </div>

    <!-- Month's Earnings -->
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">{{ __( 'Month\'s Earnings' ) }}</h6>
                <h4 class="mb-0" id="monthEarnings">
                    {{ $stats['month_earnings_formatted'] ?? ($stats['month_earnings'] ?? '') }}
                </h4>
                <small class="text-white-50">{{ __( 'This Month' ) }}</small>
            </div>
        </div>
    </div>
    
    <!-- Completed Jobs -->
    <div class="col">
        <div class="card stats-card bg-secondary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">{{ __( 'Completed' ) }}</h6>
                <h4 class="mb-0" id="completedJobs">{{ $stats['completed_jobs'] ?? '' }}</h4>
                <small class="text-white-50">{{ __( 'This Month' ) }}</small>
            </div>
        </div>
    </div>
    
    <!-- Avg. Time Per Job -->
    <div class="col">
        <div class="card stats-card bg-dark text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">{{ __( 'Avg. Time' ) }}</h6>
                <h4 class="mb-0" id="avgTime">{{ $stats['avg_time_per_job'] ?? '' }}h</h4>
                <small class="text-white-50">{{ __( 'Per Job' ) }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column - Active Time Tracking -->
    <div class="col-lg-6">
        <!-- Current Time Entry -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-play-circle me-2 text-primary"></i>
                    {{ __( "Current Time Entry" ) }}
                    <span class="badge bg-success float-end" id="timerStatus">{{ __( 'Stopped' ) }}</span>
                </h5>
            </div>
            @php
            $_select_options = '<div class="row g-3 mb-4"><div class="col">' . $eligible_jobs_with_devices_dropdown_html . '</div></div>';
            @endphp
            {!! $_select_options !!}

            @if ( isset( $job_id ) && ! empty( $job_id ) )
            <div class="widget-body">
                <div class="time-entry" id="currentTimeEntry">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">{{ $display_name }}</h6>
                            <p class="text-muted mb-2">{{ __( 'JOB' ) }}-{{ $forma_job_id }} | {{ __( 'Started' ) }}: <span id="startTime">--:--</span></p>
                            <div class="timer-display mb-2" id="currentTimer">00:00:00</div>
                            <input type="hidden" id="technicianId" value="{{ $technician_id }}">
                            <input type="hidden" id="jobId" value="{{ $job_id }}">
                            <input type="hidden" id="deviceId" value="{{ $device_id ?? '' }}">
                            <input type="hidden" id="deviceSerial" value="{{ $device_serial ?? '' }}">
                            <input type="hidden" id="deviceIndex" value="{{ $device_index ?? 0 }}">
                            {!! $timelog_nonce_field_html !!}
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group-vertical w-100">
                                <button class="btn btn-success btn-timer mb-2" id="startTimer">
                                    <i class="bi bi-play-fill me-1"></i>{{ __( 'Start' ) }}
                                </button>
                                <button class="btn btn-warning btn-timer mb-2" id="pauseTimer" disabled>
                                    <i class="bi bi-pause-fill me-1"></i>{{ __( 'Pause' ) }}
                                </button>
                                <button class="btn btn-danger btn-timer" id="stopTimer" disabled>
                                    <i class="bi bi-stop-fill me-1"></i>{{ __( 'Stop' ) }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Work Description -->
                    <div class="mt-3">
                        <label class="form-label fw-semibold">{{ __( 'Work Description' ) }}</label>
                        <textarea class="form-control" rows="3" id="workDescription" placeholder="{{ __( 'Brief description of work performed...' ) }}"></textarea>
                    </div>

                    <!-- Activity Type -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'Activity Type' ) }}</label>
                            {!! $timelog_activity_types_dropdown_html !!}
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'Is Billable?' ) }}</label>
                            <select class="form-select" id="isBillable">
                                <option value="1">{{ __( 'Yes' ) }}</option>
                                <option value="0">{{ __( 'No' ) }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @else
                <div class="widget-body">
                    <div class="alert alert-info mb-0">
                        {{ sprintf( __( 'Please select a job or device to start logging time.' ), $device_label ) }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Quick Time Entry -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>
                    {{ __( "Quick Time Entry" ) }}
                </h5>
            </div>
            @if ( isset( $job_id ) && ! empty( $job_id ) )
            <div class="widget-body">
                <form id="quickTimeForm">
                    <input type="hidden" name="technicianId" value="{{ $technician_id }}">
                    <input type="hidden" name="jobId" value="{{ $job_id }}">
                    <input type="hidden" name="deviceId" value="{{ $device_id ?? '' }}">
                    <input type="hidden" name="deviceSerial" value="{{ $device_serial ?? '' }}">
                    <input type="hidden" name="deviceIndex" value="{{ $device_index ?? 0 }}">
                    {!! $timelog_nonce_field_html !!}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'Is Billable?' ) }}</label>
                            <select class="form-select" name="isBillable_manual">
                                <option value="1">{{ __( 'Yes' ) }}</option>
                                <option value="0">{{ __( 'No' ) }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'Activity Type' ) }}</label>
                            <!-- Produce name timelog_activity_type  /-->
                            {!! $timelog_activity_types_dropdown_manual_html !!}
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'Start Time' ) }}</label>
                            <input type="datetime-local" class="form-control" name="manual_start_time" id="quickStartTime">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __( 'End Time' ) }}</label>
                            <input type="datetime-local" class="form-control" name="manual_end_time" id="quickEndTime">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __( 'Description' ) }}</label>
                            <textarea class="form-control" rows="2" id="quickDescription" name="manual_entry_description" placeholder="{{ __( 'Brief description of work performed...' ) }}"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>{{ __( 'Add Time Entry' ) }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @else
                <div class="widget-body">
                    <div class="alert alert-info mb-0">
                        {{ sprintf( __( 'Please select a job or device to start logging time.' ), $device_label ) }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Weekly Summary -->
        <div class="card time-log-widget">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2 text-primary"></i>
                    {{ __('Time Distribution') }}
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-calendar-week"></i> {{ __('This Week') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item active" href="#" data-chart-period="week">
                                <i class="bi bi-calendar-week me-2"></i>
                                {{ __('This Week') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="month">
                                <i class="bi bi-calendar-month me-2"></i>
                                {{ __('This Month') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="year">
                                <i class="bi bi-calendar me-2"></i>
                                {{ __('This Year') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="last-week">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                {{ __('Last Week') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="last-month">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                {{ __('Last Month') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="widget-body">
                <div class="time-chart" style="position: relative; height: 250px;">
                    <canvas id="weeklyTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Productivity Stats -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>
                    {{ __( "Productivity Stats" ) }}
                    <small class="text-muted float-end">{{ __( 'This Week' ) }}</small>
                </h5>
            </div>
            <div class="widget-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-semibold text-primary" id="avgDailyHours">
                            {{ $productivity_stats['avg_daily_hours'] ?? '' }}h
                        </div>
                        <small class="text-muted">{{ __( 'Avg Daily' ) }}</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold text-success" id="totalJobsCompleted">
                            {{ $productivity_stats['total_jobs_completed'] ?? '' }}
                        </div>
                        <small class="text-muted">{{ __( 'Jobs Done' ) }}</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold text-info" id="efficiencyScore">
                            {{ $productivity_stats['efficiency_score'] ?? '' }}%
                        </div>
                        <small class="text-muted">{{ __( 'Efficiency' ) }}</small>
                    </div>
                </div>
                <hr>
                <div class="progress-stats">
                    @php
                        $hasAnyActivity = !empty(array_filter($activity_distribution));
                    @endphp
                    @foreach ($activity_distribution as $activity_key => $percentage)
                        @if ($percentage > 0)
                            @php
                                $label = '';
                                foreach ($activity_types as $type) {
                                    $type_key = strtolower(preg_replace('/[^a-z0-9]/', '_', $type));
                                    if ($type_key === $activity_key) {
                                        $label = $type;
                                        break;
                                    }
                                }

                                if (empty($label)) {
                                    $label = ucwords(str_replace('_', ' ', $activity_key));
                                }

                                $color = $default_color;
                                foreach ($activity_colors as $color_key => $color_value) {
                                    if (stripos($activity_key, $color_key) !== false) {
                                        $color = $color_value;
                                        break;
                                    }
                                }
                            @endphp
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ $label }}</span>
                                <span class="fw-semibold">{{ $percentage }}%</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-{{ $color }}" 
                                    style="width: {{ $percentage }}%"
                                    role="progressbar">
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if (! $hasAnyActivity)
                        <div class="text-center text-muted py-3">
                        {{ __('No activity data available for this period.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - History & Reports -->
    <div class="col-lg-6">
        <!-- Today's Time Logs -->
        <div class="card time-log-widget">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    {{ __( 'Your Time Logs' ) }}
                </h5>
            </div>
            <div class="widget-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover largetext mb-0 log-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __( 'Job' ) }}</th>
                                <th>{{ __( 'Activity' ) }}</th>
                                <th>{{ __( 'Time' ) }}</th>
                                <th>{{ __( 'Duration' ) }}</th>
                                <th class="text-end pe-4">{{ __( 'Amount' ) }}</th>
                            </tr>
                        </thead>
                        <tbody id="todayLogsTable">
                            {!! $recent_time_logs_html !!}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</main>

@endif
@endsection

@push('page-scripts')
<script defer src="{{ asset('js/timelog.js') }}"></script>
@endpush

@push('page-styles')
<style>
    .time-log-widget { border-radius: .75rem; overflow: hidden; margin-bottom: 1rem; }
    .time-log-widget .widget-header { padding: .75rem 1rem; border-bottom: 1px solid #dee2e6; background: #f8f9fa; }
    .time-log-widget .widget-body { padding: 1rem; }
    .stats-card { border-radius: .75rem; border: none; }
    .timer-display { font-size: 2rem; font-weight: 700; font-family: 'Courier New', monospace; color: var(--bs-primary); }
    .btn-timer { border-radius: .5rem; font-weight: 600; }
    .log-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; }
    .log-table td { font-size: .84rem; vertical-align: middle; }
</style>
@endpush

@extends('tenant.layouts.myaccount', ['title' => 'Dashboard'])

@section('content')
  <main class="dashboard-content container-fluid py-4">
    @php
      $role = $role ?? (is_object($user ?? null) ? ($user->role ?? null) : null);
      $overviewStats = $overviewStats ?? [];
      $latestJobs = $latestJobs ?? [];
      $latestEstimates = $latestEstimates ?? [];
      $activities = $activities ?? [];
      $priorityJobs = $priorityJobs ?? [];
      $upcomingVisits = $upcomingVisits ?? [];
    @endphp
    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">{{ __('Active Jobs') }}</h6>
                            <h3 class="mb-0">{{ $overviewStats['active_jobs_count'] ?? 0 }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-briefcase display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">{{ __('Completed') }}</h6>
                            <h3 class="mb-0">{{ $overviewStats['completed_jobs_count'] ?? 0 }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-check-circle display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-2">{{ __('Pending Estimates') }}</h6>
                            <h3 class="mb-0">{{ $overviewStats['pending_estimates_count'] ?? 0 }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-clock display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">{{ __('Revenue') }}</h6>
                            <h3 class="mb-0">{{ $overviewStats['revenue_formatted'] ?? '0.00' }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-currency-dollar display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Overview stats ends /-->

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        @if ($role === 'customer')
        <!-- Customer View -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('My Jobs Overview') }}</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active">{{ __('Last 7 Days') }}</button>
                        <button class="btn btn-outline-secondary">{{ __('Last 30 Days') }}</button>
                        <button class="btn btn-outline-secondary">{{ __('Last 90 Days') }}</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="customerJobsChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('My Active Jobs') }}</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="customerStatusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Revenue Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Revenue Analytics') }}</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active">{{ __('Weekly') }}</button>
                        <button class="btn btn-outline-secondary">{{ __('Monthly') }}</button>
                        <button class="btn btn-outline-secondary">{{ __('Yearly') }}</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Status Distribution -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Job Status') }}</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="jobStatusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <!-- End Analytics and Status chart /-->

    <!-- Additional Charts & Content -->
    <div class="row g-4 mb-4">
        <!-- Left Column (8 columns) -->
        <div class="col-xl-8">
            <div class="row g-4">
                @if ($role === 'customer')
                    <!-- My Jobs List -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ __('My Jobs') }}</h5>
                                <a href="{{ $my_jobs_view_all_url ?? '#' }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('View All') }}
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="latest-items-list" style="max-height: 250px; overflow-y: auto;">
                                    @forelse ($latestJobs as $job)
                                            <div class="latest-item p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <h6 class="mb-1" style="font-size: 0.9rem;" title="{{ $job['title'] ?? '' }}">
                                                            <a href="{{ $job['edit_url'] ?? '#' }}" class="text-decoration-none text-truncate d-block">
                                                                {{ $job['display_title'] ?? ($job['title'] ?? '') }}
                                                            </a>
                                                        </h6>
                                                        <div class="small text-muted">
                                                            <span style="font-size: 0.8rem;">{{ ($job['formatted_total'] ?? 'N/A') . ' ' . __('Job') . '# ' . ($job['job_number'] ?? '') }}</span>
                                                            <span class="mx-1">•</span>
                                                            <span style="font-size: 0.8rem;">{{ $job['date'] ?? '' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-2">
                                                        <span class="badge {{ $job['status_badge_class'] ?? 'bg-secondary' }}" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            {{ $job['status_display'] ?? '' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                    @empty
                                        <div class="text-center p-4">
                                            <i class="bi bi-briefcase display-6 text-muted mb-2"></i>
                                            <p class="small text-muted mb-0">{{ __('No jobs found') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Estimates List -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ __('My Estimates') }}</h5>
                                <a href="{{ $my_estimates_view_all_url ?? '#' }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('View All') }}
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="latest-items-list" style="max-height: 250px; overflow-y: auto;">
                                    @forelse ($latestEstimates as $estimate)
                                            <div class="latest-item p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <h6 class="mb-1" style="font-size: 0.9rem;" title="{{ $estimate['title'] ?? '' }}">
                                                            <a href="{{ $estimate['edit_url'] ?? '#' }}" class="text-decoration-none text-truncate d-block">
                                                                {{ $estimate['display_title'] ?? ($estimate['title'] ?? '') }}
                                                            </a>
                                                        </h6>
                                                        <div class="small text-muted">
                                                            <span style="font-size: 0.8rem;">{{ $estimate['formatted_total'] ?? 'N/A' }}</span>
                                                            <span class="mx-1">•</span>
                                                            <span style="font-size: 0.8rem;">{{ $estimate['date'] ?? '' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-2">
                                                        <span class="badge {{ $estimate['status_badge_class'] ?? 'bg-secondary' }}" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            {{ $estimate['status_display'] ?? '' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                    @empty
                                        <div class="text-center p-4">
                                            <i class="bi bi-file-text display-6 text-muted mb-2"></i>
                                            <p class="small text-muted mb-0">{{ __('No estimates found') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                <!-- Device Types -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Device Types') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="deviceTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Performance') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                    <!-- Recent Activity - Full width in left column -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ __('Recent Activity') }}</h5>
                                <a href="#" class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline" style="max-height: 550px; overflow-y: auto;">
                                    @forelse ($activities as $activity)
                                            <div class="activity-item d-flex mb-3 border">
                                                <div class="activity-icon bg-{{ $activity['activity_color'] ?? 'info' }} p-2 me-3">
                                                    <i class="bi {{ $activity['activity_icon'] ?? 'bi-info-circle' }} text-white"></i>
                                                </div>
                                                <div class="activity-content flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <a href="{{ $activity['edit_url'] ?? '#' }}" target="_blank" class="text-decoration-none">
                                                            {{ $activity['name'] ?? '' }}
                                                        </a>
                                                    </h6>
                                                    <p class="text-strong">
                                                        {!! $activity['change_detail_html'] ?? '' !!}
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        {{ $activity['device_display'] ?? '' }} 
                                                        ({{ __('Job #') . ' ' . ($activity['formatted_job_number'] ?? '') }})
                                                    </p>
                                                    <small class="text-muted">
                                                        {{ $activity['time_ago'] ?? '' }} 
                                                        @if (($activity['user_name'] ?? null) && $role !== 'customer')
                                                            • {{ __('by') . ' ' . ($activity['user_name'] ?? '') }}
                                                        @endif
                                                    </small>
                                                </div>
                                                <div class="activity-badge">
                                                    <span class="badge bg-{{ $activity['activity_color'] ?? 'info' }}">
                                                        {{ $activity['badge_text'] ?? '' }}
                                                    </span>
                                                </div>
                                            </div>
                                    @empty
                                        <div class="text-center py-4">
                                            <i class="bi bi-activity display-4 text-muted mb-3"></i>
                                            <h6 class="text-muted">{{ __('No Recent Activity') }}</h6>
                                            <p class="small text-muted">{{ __('Your jobs will show updates here') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div><!-- Recent Activity ends /-->
            </div>
        </div>

        <!-- Right Column (4 columns) -->
        <div class="col-xl-4">
            <div class="row g-4">
                <!-- Priority Jobs -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ __('Priority Jobs') }}</h5>
                            <span class="badge bg-danger">{{ __('High Priority') }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="priority-jobs-list" style="max-height: 300px; overflow-y: auto;">
                                @php $priorityCount = 0; @endphp
                                @forelse ($priorityJobs as $job)
                                    @php $priorityCount++; @endphp
                                        <div class="priority-job-item p-3 border-bottom {{ (($job['priority'] ?? null) === 'urgent') ? 'urgent-job' : '' }}">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1 me-2" style="min-width: 0;">
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">
                                                        <a href="{{ $job['edit_url'] ?? '#' }}" target="_blank" class="text-decoration-none text-truncate d-block" title="{{ $job['device_name'] ?? '' }}">
                                                            {{ $job['device_display'] ?? ($job['device_name'] ?? '') }}
                                                        </a>
                                                    </h6>
                                                    <div class="small text-muted">
                                                        @if (($job['customer_name'] ?? null) && $role !== 'customer')
                                                            <div class="text-truncate" style="font-size: 0.8rem;" title="{{ $job['customer_name'] ?? '' }}">
                                                                {{ __('C:') . ' ' . ($job['customer_name'] ?? '') }}
                                                            </div>
                                                        @endif
                                                        <div style="font-size: 0.8rem;">{{ __('Job #:') . ' ' . ($job['job_number'] ?? '') }}</div>
                                                        @if ($job['pickup_date_formatted'] ?? null)
                                                            <div style="font-size: 0.8rem;">{{ __('Pickup:') . ' ' . ($job['pickup_date_formatted'] ?? '') }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0 text-end" style="min-width: 70px;">
                                                    <div class="mb-1">
                                                        <span class="badge {{ $job['priority_badge_class'] ?? 'bg-primary' }}" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            {{ $job['priority_label'] ?? '' }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <small class="badge {{ $job['status_badge_class'] ?? 'bg-secondary' }}" style="font-size: 0.65rem; padding: 0.15em 0.3em;">
                                                            {{ $job['status_label'] ?? '' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if (($job['priority'] ?? null) === 'urgent')
                                                <div class="urgent-alert mt-1">
                                                    <small class="text-danger d-flex align-items-center" style="font-size: 0.7rem;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                        {{ __('Urgent') }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                @empty
                                    <div class="text-center p-4">
                                        <i class="bi bi-check-circle display-6 text-success mb-2"></i>
                                        <h6 class="text-muted" style="font-size: 0.9rem;">{{ __('No Priority Jobs') }}</h6>
                                        <p class="small text-muted" style="font-size: 0.8rem;">{{ __('All jobs at normal priority') }}</p>
                                    </div>
                                @endforelse

                                @if ($priorityCount < 4)
                                        <div class="text-center text-muted p-3">
                                            <small style="font-size: 0.8rem;">
                                                <i class="bi bi-check-circle me-1"></i>
                                                {{ sprintf(__('%d priority job(s)'), $priorityCount) }}
                                            </small>
                                        </div>
                                @endif
                            </div>
                            
                            @if (($priorityJobsFoundPosts ?? 0) > 4)
                                <div class="card-footer text-center pt-2 pb-2">
                                    <a href="{{ $priority_jobs_view_all_url ?? '#' }}" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-list-ul me-1"></i>
                                        {{ sprintf(__('View All (%d)'), $priorityJobsFoundPosts ?? 0) }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Upcoming Visits (Pickups) -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ __('Upcoming Visits') }}</h5>
                            @if(count($upcomingVisits ?? []) > 0)
                                <span class="badge bg-primary">{{ count($upcomingVisits) }}</span>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <div class="appointment-list" style="max-height:350px; overflow-y:auto;">
                                @forelse ($upcomingVisits ?? [] as $visit)
                                    <div class="appointment-item p-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <h6 class="mb-1" style="font-size: 0.9rem;">
                                                    <a href="{{ $visit['edit_url'] }}" target="_blank" class="text-decoration-none">
                                                        {{ $visit['title'] }}
                                                    </a>
                                                </h6>
                                                <small class="text-muted" style="font-size: 0.8rem;">{{ $visit['device_display'] }}</small>
                                            </div>
                                            <span class="badge {{ $visit['pickup_date_relative'] === 'Today' ? 'bg-warning text-dark' : 'bg-primary' }}" style="font-size: 0.7rem;">
                                                {{ $visit['pickup_date_relative'] }}
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">
                                            {{ __('Pickup:') }} {{ $visit['pickup_date_formatted'] }}
                                        </small>
                                        
                                        @if($visit['customer_address'])
                                        <div class="customer-address mb-2">
                                            <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                {{ $visit['customer_address'] }}
                                            </small>
                                        </div>
                                        @endif
                                        
                                        <div class="customer-info">
                                            <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                {{ $visit['customer_name'] }}
                                                @if($visit['customer_phone'])
                                                    • {{ $visit['customer_phone'] }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center p-4">
                                        <i class="bi bi-calendar-check display-6 text-muted mb-2"></i>
                                        <h6 class="text-muted" style="font-size: 0.9rem;">{{ __('No Upcoming Visits') }}</h6>
                                        <p class="small text-muted" style="font-size: 0.8rem;">{{ __('Scheduled pickups will appear here') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a class="btn btn-primary btn-sm" href="{{ $job_url ?? '#' }}" target="_blank">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    {{ __('New Job') }}
                                </a>
                                <a class="btn btn-outline-primary btn-sm" href="{{ $calendar_url ?? '#' }}">
                                    <i class="bi bi-calendar-plus me-2"></i>
                                    {{ __('Schedule Appointment') }}
                                </a>
                                @if (in_array($role, ['administrator', 'store_manager', 'technician'], true))
                                <a class="btn btn-outline-success btn-sm" href="{{ $estimate_url ?? '#' }}" target="_blank">
                                    <i class="bi bi-file-text me-2"></i>
                                    {{ __('Create Estimate') }}
                                </a>
                                <a class="btn btn-outline-info btn-sm" href="{{ $report_url ?? '#' }}" target="_blank">
                                    <i class="bi bi-graph-up me-2"></i>
                                    {{ __('Generate Report') }}
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </main>
@endsection

@push('page-scripts')
  <script>
    // Chart data from backend
    @php
        $defaultChartData = [
            'revenue' => ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'data' => [0, 0, 0, 0, 0, 0, 0]],
            'jobs_completed' => ['data' => [0, 0, 0, 0, 0, 0, 0]],
            'job_status' => ['labels' => [], 'data' => []],
            'device_types' => ['labels' => [], 'data' => []],
            'performance' => ['avg_repair_days' => 0],
        ];
    @endphp
    var chartData = @json($chartData ?? $defaultChartData);

    (function () {
      if (typeof Chart === 'undefined') return;

      function initRevenueChart() {
        var el = document.getElementById('revenueChart');
        if (!el) return;

        var labels = chartData.revenue.labels;
        var revenue = chartData.revenue.data;
        var jobs = chartData.jobs_completed.data;

        new Chart(el, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Revenue',
              data: revenue,
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: '#0d6efd',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6
            }, {
              label: 'Jobs Completed',
              data: jobs,
              borderColor: '#198754',
              backgroundColor: 'rgba(25, 135, 84, 0.1)',
              borderWidth: 2,
              fill: true,
              tension: 0.4,
              yAxisID: 'y1',
              pointBackgroundColor: '#198754',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  usePointStyle: true,
                  padding: 20
                }
              },
              tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                  label: function(context) {
                    var label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) {
                      if (context.datasetIndex === 0) {
                        return label + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                      }
                      return label + Math.round(context.parsed.y) + ' jobs';
                    }
                    return label;
                  }
                }
              }
            },
            interaction: { intersect: false, mode: 'index' },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Revenue',
                  font: { size: 14, weight: 'bold' }
                },
                ticks: {
                  callback: function(value) {
                    return new Intl.NumberFormat('en-US', {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0
                    }).format(value);
                  }
                },
                grid: { color: 'rgba(0, 0, 0, 0.1)' }
              },
              y1: {
                beginAtZero: true,
                position: 'right',
                title: {
                  display: true,
                  text: 'Jobs Completed',
                  font: { size: 14, weight: 'bold' }
                },
                grid: { drawOnChartArea: false },
                ticks: {
                  callback: function(value) {
                    return Math.round(value) + '';
                  }
                }
              },
              x: {
                grid: { color: 'rgba(0, 0, 0, 0.1)' },
                ticks: { maxRotation: 0 }
              }
            }
          }
        });
      }

      function initDoughnutChart(canvasId, labels, data, colors) {
        var el = document.getElementById(canvasId);
        if (!el) return;

        // Hide chart and show empty state if no data
        if (!data || data.length === 0 || data.every(function(v) { return v === 0; })) {
          el.parentElement.innerHTML = '<div class="text-center p-4"><i class="bi bi-pie-chart display-6 text-muted mb-2"></i><p class="text-muted small">No data available</p></div>';
          return;
        }

        new Chart(el, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: colors,
              borderWidth: 3,
              borderColor: '#fff',
              hoverBorderWidth: 4,
              hoverBorderColor: '#fff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  usePointStyle: true,
                  font: { size: 13 }
                }
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                  label: function(context) {
                    var label = context.label || '';
                    var value = context.raw || 0;
                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                    var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                    return label + ': ' + value + ' jobs (' + percentage + '%)';
                  }
                }
              }
            },
            cutout: '65%'
          }
        });
      }

      function initDeviceTypePieChart() {
        var el = document.getElementById('deviceTypeChart');
        if (!el) return;

        var labels = chartData.device_types.labels;
        var data = chartData.device_types.data;

        // Hide chart and show empty state if no data
        if (!data || data.length === 0 || data.every(function(v) { return v === 0; })) {
          el.parentElement.innerHTML = '<div class="text-center p-4"><i class="bi bi-pie-chart display-6 text-muted mb-2"></i><p class="text-muted small">No device data</p></div>';
          return;
        }

        new Chart(el, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: ['#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#20c997', '#6610f2', '#6c757d', '#198754'],
              borderWidth: 3,
              borderColor: '#fff',
              hoverBorderWidth: 4,
              hoverBorderColor: '#fff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  usePointStyle: true,
                  font: { size: 13 }
                }
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                  label: function(context) {
                    var label = context.label || '';
                    var value = context.raw || 0;
                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                    var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                    return label + ': ' + value + ' jobs (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      }

      function initPerformanceChart() {
        var el = document.getElementById('performanceChart');
        if (!el) return;

        var avgDays = chartData.performance.avg_repair_days;

        // Show single bar with average
        new Chart(el, {
          type: 'bar',
          data: {
            labels: ['Current'],
            datasets: [{
              label: 'Average Repair Time',
              data: [avgDays],
              backgroundColor: 'rgba(13, 202, 240, 0.8)',
              borderColor: 'rgba(13, 202, 240, 1)',
              borderWidth: 2,
              borderRadius: 4,
              hoverBackgroundColor: 'rgba(13, 202, 240, 1)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                  label: function(context) {
                    var value = context.parsed.y;
                    if (value === null || value === undefined || value === 0) {
                      return 'No completed jobs yet';
                    }
                    return 'Average: ' + value.toFixed(1) + ' days';
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Average Repair Time (Days)',
                  font: { size: 14, weight: 'bold' }
                },
                ticks: {
                  callback: function(value) {
                    return value.toFixed(1) + ' days';
                  }
                },
                grid: { color: 'rgba(0, 0, 0, 0.1)' }
              },
              x: {
                grid: { color: 'rgba(0, 0, 0, 0.1)' }
              }
            }
          }
        });
      }

      function initCustomerJobsChart() {
        var el = document.getElementById('customerJobsChart');
        if (!el) return;

        var labels = chartData.revenue.labels;
        var jobCounts = chartData.jobs_completed.data;
        var completedJobs = chartData.jobs_completed.data;

        new Chart(el, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'My Jobs',
              data: jobCounts,
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.4
            }, {
              label: 'Jobs Completed',
              data: completedJobs,
              borderColor: '#198754',
              backgroundColor: 'rgba(25, 135, 84, 0.1)',
              borderWidth: 2,
              fill: true,
              tension: 0.4,
              yAxisID: 'y1'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top' },
              tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                  label: function(context) {
                    var label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) {
                      label += context.parsed.y + ' jobs';
                    }
                    return label;
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: { display: true, text: 'Jobs Created' },
                ticks: {
                  callback: function(value) {
                    return value + ' jobs';
                  }
                }
              },
              y1: {
                beginAtZero: true,
                position: 'right',
                title: { display: true, text: 'Jobs Completed' },
                grid: { drawOnChartArea: false }
              }
            }
          }
        });
      }

      // Staff charts - use dynamic data
      initRevenueChart();
      initDoughnutChart('jobStatusChart', chartData.job_status.labels, chartData.job_status.data, ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6c757d', '#0d6efd', '#6f42c1', '#fd7e14']);
      initDeviceTypePieChart();
      initPerformanceChart();

      // Customer charts
      initCustomerJobsChart();
      initDoughnutChart('customerStatusChart', chartData.job_status.labels, chartData.job_status.data, ['#198754', '#0dcaf0', '#ffc107', '#dc3545']);
    })();
  </script>
@endpush

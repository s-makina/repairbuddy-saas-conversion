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

                <!-- Upcoming Appointments -->
                <style type="text/css">
                    /* Add this to your stylesheet */
                    .appointment-item .badge {
                        white-space: nowrap;
                    }

                    .appointment-map {
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .appointment-map iframe {
                        border-radius: 4px;
                    }

                    /* Mobile responsiveness */
                    @media (max-width: 768px) {
                        .appointment-item .row {
                            flex-direction: column;
                        }
                        
                        .appointment-item .col-md-5 {
                            margin-top: 10px;
                        }
                        
                        .appointment-map {
                            height: 120px !important;
                        }
                    }
                </style>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Upcoming Visits') }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="appointment-list" style="height:350px; overflow-y:scroll;">
                                
                                <!-- Appointment Item 1 -->
                                <div class="appointment-item p-3 border-bottom">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Laptop Diagnostic</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">Dell Inspiron 15</small>
                                                </div>
                                                <span class="badge bg-primary d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Tomorrow</span>
                                                <span class="badge bg-primary d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Tomorrow</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">10:00 AM - 11:30 AM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    123 Main Street, San Francisco, CA 94105
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    John Smith • (555) 123-4567
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.681434336427!2d-122.41941548468158!3d37.77492977975915!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8085808c3d6b3f3f%3A0x8d7c5b9a7b5b5b5b!2s123%20Main%20St%2C%20San%20Francisco%2C%20CA%2094105!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appointment Item 2 -->
                                <div class="appointment-item p-3 border-bottom">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Screen Replacement</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">MacBook Pro 14"</small>
                                                </div>
                                                <span class="badge bg-warning d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Today</span>
                                                <span class="badge bg-warning d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Today</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">2:00 PM - 3:30 PM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    456 Tech Avenue, San Jose, CA 95113
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    Sarah Johnson • (555) 987-6543
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3172.3323495308524!2d-121.88699468472234!3d37.3388475798426!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808fcc5b8b6b6b6b%3A0x8b7c5b9a7b5b5b5b!2s456%20Tech%20Ave%2C%20San%20Jose%2C%20CA%2095113!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appointment Item 3 -->
                                <div class="appointment-item p-3">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Virus Removal</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">HP Pavilion</small>
                                                </div>
                                                <span class="badge bg-success d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Tomorrow</span>
                                                <span class="badge bg-success d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Tomorrow</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">11:00 AM - 12:00 PM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    789 Innovation Way, Palo Alto, CA 94301
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    Michael Chen • (555) 456-7890
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3168.635259966661!2d-122.16071918471973!3d37.44188337983366!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808fbb5b8b6b6b6b%3A0x8b7c5b9a7b5b5b5b!2s789%20Innovation%20Way%2C%20Palo%20Alto%2C%20CA%2094301!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
                                <a class="btn btn-outline-primary btn-sm">
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
    (function () {
      if (typeof Chart === 'undefined') return;

      function initRevenueChart() {
        var el = document.getElementById('revenueChart');
        if (!el) return;

        var labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        var revenue = [320, 540, 410, 760, 610, 980, 720];
        var jobs = [2, 4, 3, 5, 4, 7, 6];

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
        var labels = ['Phones', 'Laptops', 'Tablets', 'PC'];
        var data = [12, 8, 5, 7];
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

        var labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        var data = [3.2, 2.8, 2.5, 2.1, 1.9, 1.7];

        new Chart(el, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Average Repair Time',
              data: data,
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
                    if (value === null || value === undefined) {
                      return 'No data';
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

        var labels = ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'];
        var jobCounts = [1, 0, 2, 1, 1, 0, 1];
        var completedJobs = [0, 0, 1, 0, 1, 0, 1];

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

      // Staff charts
      initRevenueChart();
      initDoughnutChart('jobStatusChart', ['Completed', 'In Progress', 'Pending', 'Cancelled'], [11, 9, 5, 2], ['#198754', '#0dcaf0', '#ffc107', '#dc3545']);
      initDeviceTypePieChart();
      initPerformanceChart();

      // Customer charts
      initCustomerJobsChart();
      initDoughnutChart('customerStatusChart', ['Completed', 'In Progress', 'Pending Estimates', 'Cancelled'], [3, 2, 1, 0], ['#198754', '#0dcaf0', '#ffc107', '#dc3545']);
    })();
  </script>
@endpush

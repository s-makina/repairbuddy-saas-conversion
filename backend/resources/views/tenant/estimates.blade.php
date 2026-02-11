@extends('tenant.layouts.myaccount', ['title' => 'Jobs'])

@section('content')
@php
    $current_view = $current_view ?? (request()->query('screen') === 'estimates_card' ? 'card' : 'table');
    $_page = $_page ?? (request()->query('screen') === 'estimates_card' ? 'estimates_card' : 'estimates');

    $base_page_url = $base_page_url ?? request()->url();

    $jobs_list = $jobs_list ?? ['rows' => '', 'pagination' => ''];

    $_count_pending = $_count_pending ?? 0;
    $_count_approved = $_count_approved ?? 0;
    $_count_rejected = $_count_rejected ?? 0;

    $_counts_array = $_counts_array ?? [
        'approved' => [
            'count' => $_count_approved,
            'slug' => 'approved',
            'color' => 'bg-success',
            'name' => __('Approved'),
        ],
        'rejected' => [
            'count' => $_count_rejected,
            'slug' => 'rejected',
            'color' => 'bg-danger',
            'name' => __('Rejected'),
        ],
        'pending' => [
            'count' => $_count_pending,
            'slug' => 'pending',
            'color' => 'bg-info',
            'name' => __('Pending'),
        ],
    ];

    $view_label = $view_label ?? ($current_view === 'card' ? __('Table View') : __('Card View'));
    $view_url = $view_url ?? ($current_view === 'card'
        ? ($base_page_url . '?' . http_build_query(['screen' => 'estimates']))
        : ($base_page_url . '?' . http_build_query(['screen' => 'estimates_card'])));

    $use_store_select = $use_store_select ?? false;
    $store_select_options_html = $store_select_options_html ?? '';

    $use_woo_devices = $use_woo_devices ?? false;
    $woo_device_select_html = $woo_device_select_html ?? '';
    $device_options_html = $device_options_html ?? '';

    $customer_select_options_html = $customer_select_options_html ?? '';
    $technician_select_options_html = $technician_select_options_html ?? '';

    $case_number_label_first = $case_number_label_first ?? __('Case Number');
    $device_label_plural = $device_label_plural ?? __('Devices');

    $export_buttons_html = $export_buttons_html ?? '';
    $license_state = $license_state ?? true;

    $role = $role ?? null;
    $_mainpage = $_mainpage ?? null;

    $clear_filters_url = $clear_filters_url ?? ($base_page_url . '?' . http_build_query(['screen' => $_page]));
@endphp
<!-- Jobs Content -->
<main class="dashboard-content container-fluid py-4">
    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        @if ( ! empty( $_counts_array ) )
            @foreach( $_counts_array as $_countsarr )
                @php
                    $_count = $_countsarr['count'] ?? '';
                    $_slug  = $_countsarr['slug'] ?? '';
                    $_color = $_countsarr['color'] ?? '';
                    $_name  = $_countsarr['name'] ?? '';

                    $_pageurl = $base_page_url . '?' . http_build_query(['screen' => $_page, 'estimate_status' => $_slug]);
                @endphp
        <div class="col">
            <a href="{{ $_pageurl }}">
            <div class="card stats-card {{ $_color }} text-white">
                <div class="card-body text-center p-3">
                    <h6 class="card-title text-white-50 mb-1">{{ $_name }}</h6>
                    <h4 class="mb-0">{{ $_count }}</h4>
                </div>
            </div>
            </a>
        </div>
            @endforeach
        @endif

    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="">
            <input type="hidden" name="screen" value="{{ $_page }}" />
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="searchinput" id="searchInput" 
                        value="{{ request()->query('searchinput', '') }}"
                        placeholder="{{ __('Search by Job ID, Case Number, Title, Notes, Dates') }}...">
                    </div>
                </div>
                <!-- Job Status Starts /-->
                <div class="col">
                    @php $current_status = request()->query('estimate_status', ''); @endphp
                    <select class="form-select" name="estimate_status" id="estimate_status">
                        <option value="all" {{ ((string) $current_status === 'all') ? "selected='selected'" : '' }}>{{ __('Estimate Status (All)') }}</option>
                        <option value="approved" {{ ((string) $current_status === 'approved') ? "selected='selected'" : '' }}>{{ __('Approved') }}</option>
                        <option value="rejected" {{ ((string) $current_status === 'rejected') ? "selected='selected'" : '' }}>{{ __('Rejected') }}</option>
                    </select>
                </div>
                <!-- Job Status Ends /-->
                
                <!-- Start select store /-->
                @if ($use_store_select)
                    <div class="col">                    
                        <select name="wc_store" class="form-select" id="wc_store">
                            <option value="all">{{ __('Store (All)') }}</option>
                            {!! $store_select_options_html !!}
                        </select>
                    </div>
				@endif
                <!-- End Select store /-->

                <!-- Select device starts /-->
                <div class="col">
                    @if ($use_woo_devices)
                        {!! $woo_device_select_html !!}
                    @else
                    <select id="rep_devices" name="device_post_id" class="form-select">
                    {!! $device_options_html !!}	
                    </select>
                    @endif
                </div>
                <!-- Select device Ends /-->

                @if ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' )
                <div class="col">
                    {!! $customer_select_options_html !!}
                </div>
                @endif
                
                @if ( $role == 'administrator' || $role == 'store_manager' )
                <div class="col">
                    {!! $technician_select_options_html !!}
                </div>
                @endif

                <div class="col">
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="{{ $clear_filters_url }}" id="clearFilters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                        <button class="btn btn-primary" id="applyFilters">
                            <i class="bi bi-funnel"></i> {{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <!-- Jobs Table/Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Jobs') }}</h5>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> {{ __('Export') }}
                    </button>
                    {!! $export_buttons_html !!}
                </div>

                @if ($license_state)
                <a href="{{ $view_url }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid-3x3-gap me-1"></i> {{ $view_label }}
                </a>
                @else
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ __('Card View') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><span class="dropdown-item text-muted">
                            <i class="bi bi-lock me-2"></i>{{ __('Pro Feature') }}
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-success" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/pricing/" target="_blank" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                            <i class="bi bi-star me-2"></i>{{ __('Upgrade Now') }}
                        </a></li>
                        <li><a class="dropdown-item text-info" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/repairbuddy-features/" target="_blank">
                            <i class="bi bi-info-circle me-2"></i>{{ __('View Features') }}
                        </a></li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if ($current_view === 'card')
            <!-- Card View -->
            <div class="cardView" id="cardView">
                {!! $jobs_list['rows'] ?? '' !!}
            </div>
            @else
            <!-- Table View -->
            <div class="table-responsive" id="jobsTable_list">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __("ID") }}</th>
                            <th>{{ __($case_number_label_first) }}/{{ __('Tech') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ $device_label_plural }}</th>
                            <th>{{ __('Dates') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!! $jobs_list['rows'] ?? '' !!}
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        <!-- Pagination -->
        {!! $jobs_list['pagination'] ?? '' !!}
    </div>
</main>

{!! $duplicate_job_front_box_html ?? '' !!}
@endsection

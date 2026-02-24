@extends('tenant.layouts.myaccount', ['title' => 'Jobs'])

@push('page-styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" />
@endpush

@push('page-scripts')
<script>
  /* ── openDocPreview: opens DocumentPreviewModal via Livewire dispatch ── */
  function openDocPreview(type, id) {
    if (window.Livewire) {
      window.Livewire.dispatch('openDocumentPreview', { type: type, id: id });
    }
  }
</script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  (function () {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return;
    }

    var $table = window.jQuery('#jobsTable');
    if ($table.length === 0) {
      return;
    }

    if (window.jQuery.fn.DataTable.isDataTable($table)) {
      return;
    }

    $table.DataTable({
      processing: true,
      serverSide: true,
      pageLength: 25,
      ajax: {
        url: "{{ route('tenant.jobs.datatable', ['business' => $tenant->slug]) }}",
        data: function (d) {
          d.job_status = window.jQuery('#job_status').val() || '';
          d.wc_payment_status = window.jQuery('#wc_payment_status').val() || '';
          d.device_post_id = window.jQuery('#rep_devices').val() || '';
          d.searchinput = window.jQuery('#searchInput').val() || '';
        }
      },
      order: [[0, 'desc']],
      columns: [
        { data: 'job_id_display', name: 'job_number' },
        { data: 'case_number_display', name: 'case_number' },
        { data: 'customer_display', name: 'customer_id', orderable: false, searchable: false },
        { data: 'devices_display', name: 'jobDevices.label_snapshot', orderable: false, searchable: false },
        { data: 'dates_display', name: 'pickup_date', orderable: false, searchable: false },
        { data: 'total_display', name: 'total_display', orderable: false, searchable: false },
        { data: 'balance_display', name: 'balance_display', orderable: false, searchable: false },
        { data: 'payment_display', name: 'payment_status_slug' },
        { data: 'status_display', name: 'status_slug' },
        { data: 'priority_display', name: 'priority' },
        { data: 'actions_display', name: 'actions_display', orderable: false, searchable: false, className: 'text-end pe-4' }
      ]
    });

    window.jQuery('#applyFilters').on('click', function (e) {
      e.preventDefault();
      $table.DataTable().ajax.reload();
    });

    window.jQuery('#clearFilters').on('click', function (e) {
      e.preventDefault();
      window.jQuery('#searchInput').val('');
      window.jQuery('#job_status').val('all');
      window.jQuery('#wc_payment_status').val('all');
      window.jQuery('#rep_devices').val('');
      $table.DataTable().search('').ajax.reload();
    });

    window.jQuery('#searchInput').on('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        $table.DataTable().search(window.jQuery(this).val() || '').draw();
      }
    });
  })();
</script>
@endpush

@section('content')
@php
    $current_view = $current_view ?? (request()->query('screen') === 'jobs_card' ? 'card' : 'table');
    $_page = $_page ?? (request()->query('screen') === 'jobs_card' ? 'jobs_card' : 'jobs');

    $jobs_list = $jobs_list ?? ['rows' => '', 'pagination' => ''];
    $_job_status = $_job_status ?? [];

    $job_status_options_html = $job_status_options_html ?? '';
    $job_priority_options_html = $job_priority_options_html ?? '';
    $store_select_options_html = $store_select_options_html ?? '';
    $device_options_html = $device_options_html ?? '';
    $payment_status_options_html = $payment_status_options_html ?? '';
    $customer_select_options_html = $customer_select_options_html ?? '';
    $technician_select_options_html = $technician_select_options_html ?? '';
    $export_buttons_html = $export_buttons_html ?? '';
    $use_store_select = $use_store_select ?? false;
    $license_state = $license_state ?? true;
    $clear_filters_url = $clear_filters_url ?? request()->fullUrlWithQuery(['screen' => $_page]);

    $view_label = $view_label ?? ($current_view === 'card' ? 'Table View' : 'Card View');
    $view_url = $view_url ?? '#';

    $role = $role ?? null;
    $_mainpage = $_mainpage ?? null;
@endphp
<!-- Jobs Content -->
<main class="dashboard-content container-fluid py-4">
    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        @if (! empty($_job_status))
            @foreach ($_job_status as $_jobsstatus)
                @php
                    $_color = $_jobsstatus['color'] ?? '';
                    $_pageurl = $_jobsstatus['url'] ?? '#';
                @endphp
                @if (($_jobsstatus['jobs_count'] ?? 0) > 0)
        <div class="col">
            <a href="{{ $_pageurl }}">
            <div class="card stats-card {{ $_color }} text-white">
                <div class="card-body text-center p-3">
                    <h6 class="card-title text-white-50 mb-1">{{ $_jobsstatus['status_name'] ?? '' }}</h6>
                    <h4 class="mb-0">{{ $_jobsstatus['jobs_count'] ?? '' }}</h4>
                </div>
            </div>
            </a>
        </div>
                @endif
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
                    @php $current_status = request()->query('job_status', ''); @endphp
                    <select class="form-select" name="job_status" id="job_status">
                        <option value="all" {{ ((string) $current_status === 'all') ? 'selected' : '' }}>{{ __('Job Status (All)') }}</option>
                    {!! $job_status_options_html !!}
                    </select>
                </div>
                <!-- Job Status Ends /-->
                
                <div class="col">
                    {!! $job_priority_options_html !!}
                </div>

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
                    <select id="rep_devices" name="device_post_id" class="form-select">
                    {!! $device_options_html !!}
                    </select>
                </div>
                <!-- Select device Ends /-->

                <!-- Starts payment status /-->
                <div class="col">
                    @php $current_payment_status = request()->query('wc_payment_status', ''); @endphp
                    <select name="wc_payment_status" id="wc_payment_status" class="form-select">
                        <option value="all" {{ ((string) $current_payment_status === 'all') ? 'selected' : '' }}>{{ __('Payment Status (All)') }}</option>
                        {!! $payment_status_options_html !!}
                    </select>
                </div>
                <!-- Ends payment status /-->
                
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
                <table class="table table-hover mb-0 table-striped" id="jobsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Case Number') }}/{{ __('Tech') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Devices') }}</th>
                            <th>{{ __('Dates') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Priority') }}</th>
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

{{-- ── Document Preview Modal ── --}}
@livewire('tenant.operations.document-preview-modal', ['tenant' => $tenant ?? null])

{!! $duplicate_job_front_box_html ?? '' !!}

<!-- Bootstrap Modal for Post Entry -->
<div class="modal fade" id="openTakePaymentModal" tabindex="-1" aria-labelledby="openTakePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="openTakePaymentModalLabel">
                    {{ __('Make a payment') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="" method="post" name="wcrb_jl_form_submit_payment" data-success-class=".set_addpayment_joblist_message">
                    <div id="replacementpart_joblist_formfields">
                        <!-- Replacementpart starts -->
                         Champ
                        <!-- Replacementpart Ends -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

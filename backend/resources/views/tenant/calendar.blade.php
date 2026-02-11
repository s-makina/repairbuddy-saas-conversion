@extends('tenant.layouts.myaccount', ['title' => 'Calendar'])

@push('page-styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.19/index.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.19/index.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.19/index.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.19/index.min.css">
  <style>
    #frontend-calendar .fc .fc-daygrid-event {
      border: 0;
      border-radius: 0.375rem;
      padding: 0.2rem 0.35rem;
      margin: 0.15rem 0;
      box-shadow: 0 1px 2px rgba(0,0,0,0.08);
    }

    #frontend-calendar .fc .fc-daygrid-event .fc-event-main {
      padding: 0;
    }

    #frontend-calendar .fc a.fc-event {
      text-decoration: none;
    }

    #frontend-calendar .fc .fc-daygrid-block-event .fc-event-title {
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      word-break: break-word;
    }

    #frontend-calendar .fc .fc-event-title,
    #frontend-calendar .fc .fc-event-status {
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    #frontend-calendar .fc .fc-event-status {
      white-space: nowrap;
    }

    #frontend-calendar .fc a.fc-event,
    #frontend-calendar .fc a.fc-event:hover {
      color: #fff !important;
    }

    #frontend-calendar .fc .fc-event .fc-event-main,
    #frontend-calendar .fc .fc-event .fc-event-main-frame {
      color: inherit;
    }

    #frontend-calendar .fc .bg-warning.fc-event,
    #frontend-calendar .fc .bg-warning.fc-event:hover {
      color: #212529 !important;
    }

    #frontend-calendar .fc .bg-warning.fc-event .fc-event-main,
    #frontend-calendar .fc .bg-warning.fc-event:hover .fc-event-main {
      color: #212529;
    }

    #frontend-calendar .fc .bg-warning.fc-event .fc-event-status {
      color: rgba(33, 37, 41, 0.8) !important;
    }

    #frontend-calendar .fc .bg-light.fc-event,
    #frontend-calendar .fc .bg-light.fc-event:hover {
      color: #212529;
    }

    .tooltip.calendar-tooltip .tooltip-inner {
      max-width: 320px;
      padding: 0.5rem 0.65rem;
      font-size: 0.85rem;
      text-align: left;
      background: rgba(0, 0, 0, 0.85);
    }

    .tooltip.calendar-tooltip .tooltip-arrow::before {
      border-top-color: rgba(0, 0, 0, 0.85);
    }
  </style>
@endpush

@section('content')
<main class="dashboard-content container-fluid py-4">
    <div class="calendar-container bg-white rounded-3 shadow-sm p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <div class="mb-3 mb-md-0">
                <h2 class="h4 mb-1">{{ __('Service Calendar') }}</h2>
                <p class="text-muted mb-0">{{ __('View and manage all your service appointments and estimates') }}</p>
            </div>
            <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Calendar date options">
                <button type="button" class="btn btn-outline-primary active date-field-btn" data-field="pickup_date">{{ $pickup_date_label ?? 'Pickup' }}</button>
                <button type="button" class="btn btn-outline-primary date-field-btn" data-field="delivery_date">{{ $delivery_date_label ?? 'Delivery' }}</button>
                @if (($enable_next_service ?? false) === true)
                    <button type="button" class="btn btn-outline-primary date-field-btn" data-field="next_service_date">{{ $nextservice_date_label ?? 'Next Service' }}</button>
                @endif
                <button type="button" class="btn btn-outline-primary date-field-btn" data-field="post_date">{{ __('Creation') }}</button>
            </div>
        </div>

        <div class="calendar-filters bg-light rounded p-3 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                <div class="filter-group d-flex align-items-center">
                    <label for="calendar-filter" class="form-label mb-0 me-2 fw-semibold">{{ __('Filter:') }}</label>
                    <select id="calendar-filter" class="form-select form-select-sm" style="min-width: 180px;">
                        <option value="all">{{ __('All Items') }}</option>
                        <option value="jobs">{{ __('Jobs Only') }}</option>
                        <option value="estimates">{{ __('Estimates Only') }}</option>
                        @if (in_array(($user?->role ?? ''), ['administrator', 'store_manager'], true))
                            <option value="my_assignments">{{ __('My Assignments') }}</option>
                        @endif
                    </select>
                </div>

                <input type="hidden" id="date-field" value="pickup_date">

                <div class="filter-group">
                    <button id="refresh-calendar" class="btn btn-primary btn-sm">{{ __('Refresh') }}</button>
                </div>
            </div>
        </div>

        <div id="frontend-calendar" class="position-relative" style="min-height: 500px;"></div>

        <div id="calendar-loading" class="d-none position-absolute top-50 start-50 translate-middle bg-white rounded-3 p-4 shadow" style="z-index: 1050;">
            <div class="d-flex flex-column align-items-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
                <p class="mb-0 text-muted">{{ __('Loading calendar events...') }}</p>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted me-2">{{ __('Legend:') }}</span>
                <span class="badge bg-primary">{{ __('Job') }}</span>
                <span class="badge bg-warning text-dark">{{ __('Estimate') }}</span>
                <span class="badge bg-success">{{ __('New/Quote') }}</span>
                <span class="badge bg-info">{{ __('In Process') }}</span>
                <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                <span class="badge" style="background-color: #fd7e14; color: white;">{{ __('Ready') }}</span>
                <span class="badge" style="background-color: #6f42c1; color: white;">{{ __('Completed') }}</span>
                <span class="badge" style="background-color: #e83e8c; color: white;">{{ __('Delivered') }}</span>
            </div>
        </div>

    </div>

    <div class="row mt-4 g-3">
        <div class="col-md-6">
            <div class="card border-0 bg-primary text-white shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h5 class="card-title h6 mb-2">{{ __('Total Jobs') }}</h5>
                    <h3 class="card-text display-6 fw-bold mb-0" id="total-jobs">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 bg-success text-white shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h5 class="card-title h6 mb-2">{{ __('Total Estimates') }}</h5>
                    <h3 class="card-text display-6 fw-bold mb-0" id="total-estimates">0</h3>
                </div>
            </div>
        </div>
    </div>
</main>
<style type="text/css">
    .status-new { background-color: #28a745 !important; }
.status-quote { background-color: #17a2b8 !important; }
.status-inprocess { background-color: #20c997 !important; }
.status-ready { background-color: #fd7e14 !important; }
.status-completed { background-color: #6f42c1 !important; }
.status-delivered { background-color: #e83e8c !important; }
.status-cancelled { background-color: #dc3545 !important; }
</style>
@endsection

@push('page-scripts')
  <script src="{{ asset('repairbuddy/my_account/js/fullcalendar/index.global.min.js') }}"></script>
  <script src="{{ asset('repairbuddy/my_account/js/fullcalendar/locales-all.global.min.js') }}"></script>
  <script>
    window.calendar_frontend_vars = {
      ajax_url: @json($calendar_events_url ?? ''),
      nonce: '',
      locale: 'en-US',
      is_user_logged_in: true,
      user_id: @json($user?->id),
      user_roles: @json([$user?->role]),
      enable_next_service: @json(($enable_next_service ?? false) === true ? 'yes' : 'no'),
      pickup_date_label: @json($pickup_date_label ?? 'Pickup'),
      delivery_date_label: @json($delivery_date_label ?? 'Delivery'),
      nextservice_date_label: @json($nextservice_date_label ?? 'Next Service'),
      day: @json(__('Day')),
      month: @json(__('Month')),
      week: @json(__('Week')),
      list: @json(__('List')),
      today: @json(__('Today'))
    };
  </script>
  <script src="{{ asset('repairbuddy/my_account/js/frontend-calendar.js') }}"></script>
@endpush

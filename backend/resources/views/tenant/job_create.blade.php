@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Job'])

@section('content')
@php
    $jobStatuses = is_iterable($jobStatuses ?? null) ? $jobStatuses : [];
    $paymentStatuses = is_iterable($paymentStatuses ?? null) ? $paymentStatuses : [];
    $customers = is_iterable($customers ?? null) ? $customers : [];
    $technicians = is_iterable($technicians ?? null) ? $technicians : [];
    $customerDevices = is_iterable($customerDevices ?? null) ? $customerDevices : [];
    $devices = is_iterable($devices ?? null) ? $devices : [];
    $parts = is_iterable($parts ?? null) ? $parts : [];
    $services = is_iterable($services ?? null) ? $services : [];

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : null;

    $settings = data_get($tenant?->setup_state ?? [], 'repairbuddy_settings');
    $devicesBrandsSettings = is_array($settings) ? (array) data_get($settings, 'devicesBrands', []) : [];
    $enablePinCodeField = (bool) ($devicesBrandsSettings['enablePinCodeField'] ?? false);
@endphp

@push('page-styles')
    <style>
        #customer_id + .select2-container--bootstrap-5 .select2-selection,
        #technician_ids + .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            background-color: var(--bs-body-bg);
        }

        #customer_id + .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0;
            padding-right: 1.5rem;
            line-height: 1.5;
        }

        #customer_id + .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
            right: .75rem;
        }

        #technician_ids + .select2-container--bootstrap-5 .select2-selection--multiple {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }

        #technician_ids + .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            padding: 0;
        }

        #technician_ids + .select2-container--bootstrap-5 .select2-selection--multiple .select2-search__field {
            margin-top: 0;
        }

        #customer_id + .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        #technician_ids + .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
        }

        .input-group > .select2-container {
            width: auto !important;
            flex: 1 1 auto;
            min-width: 0;
        }

        .input-group {
            flex-wrap: nowrap;
        }

        .input-group > .btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .input-group > .select2-container--bootstrap-5 .select2-selection,
        .input-group > .select2-container--bootstrap-5 .select2-selection--multiple {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        #customer_id + .select2-container--bootstrap-5 .select2-selection__rendered {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        #technician_ids + .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            max-width: 100%;
        }

        #technician_ids + .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__display {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
            display: inline-block;
        }

        #section-extras td,
        #section-extras th,
        #section-devices td,
        #section-devices th {
            vertical-align: middle;
        }

        #section-extras .extra-data span {
            max-width: 420px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

<main class="dashboard-content container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="mb-1">{{ __('Create Job') }}</h3>
            <div class="text-muted">{{ __('Enter job details and create a new job.') }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs' }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                {{ __('Back') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('tenant.jobs.store', ['business' => $tenantSlug]) }}" enctype="multipart/form-data">
        @csrf

        @include('tenant.partials.quick_create_customer_modal')
        @include('tenant.partials.quick_create_technician_modal')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4" id="section-job-details">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Job Details') }}</h5>
                        <a href="#section-items" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-seam me-1"></i>
                            {{ __('Jump to Items') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Case Number') }}</label>
                                <input type="text" name="case_number" class="form-control" value="{{ old('case_number') }}" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Title') }}</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="{{ __('Optional') }}" />
                                @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customer_id" class="form-select">
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" {{ (string) old('customer_id') === (string) $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}
                                                @if (!empty($c->email)) ({{ $c->email }}) @endif
                                                @if (!empty($c->phone)) — {{ $c->phone }} @endif
                                                @if (!empty($c->company)) — {{ $c->company }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-primary" id="rb_open_quick_customer" title="{{ __('Add new customer') }}" aria-label="{{ __('Add new customer') }}">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                </div>
                                @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="technician_ids">{{ __('Technicians') }}</label>
                                <div class="input-group">
                                    <select name="technician_ids[]" id="technician_ids" class="form-select" multiple>
                                        @foreach ($technicians as $t)
                                            <option value="{{ $t->id }}" {{ in_array((string) $t->id, array_map('strval', (array) old('technician_ids', [])), true) ? 'selected' : '' }}>
                                                {{ $t->name }} ({{ $t->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-primary" id="rb_open_quick_technician" title="{{ __('Add technician') }}" aria-label="{{ __('Add technician') }}">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                </div>
                                @error('technician_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Pickup Date') }}</label>
                                <input type="date" name="pickup_date" class="form-control" value="{{ old('pickup_date') }}" />
                                @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Delivery Date') }}</label>
                                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date') }}" />
                                @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Next Service Date') }}</label>
                                <input type="date" name="next_service_date" class="form-control" value="{{ old('next_service_date') }}" />
                                @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('Job Details') }}</label>
                                <textarea name="case_detail" class="form-control" rows="4" placeholder="{{ __('Enter details about job.') }}">{{ old('case_detail') }}</textarea>
                                @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Device') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-lg-3 col-4">
                                                <label class="form-label mb-0" for="device_modal_device">{{ __('Device') }}</label>
                                            </div>
                                            <div class="col-lg-9 col-8">
                                                <select id="device_modal_device" class="form-select">
                                                    <option value="">{{ __('Select Device') }}</option>
                                                    @foreach ($devices as $d)
                                                        @php
                                                            $brand = is_string($d->brand?->name ?? null) ? (string) $d->brand?->name : '';
                                                            $model = is_string($d->model ?? null) ? (string) $d->model : '';
                                                            $text = trim(trim($brand . ' ' . $model));
                                                            if ($text === '') {
                                                                $text = __('Device') . ' #' . $d->id;
                                                            }
                                                        @endphp
                                                        <option value="{{ $d->id }}">{{ $text }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-lg-3 col-4">
                                                <label class="form-label mb-0" for="device_modal_imei">{{ __('Device ID/IMEI') }}</label>
                                            </div>
                                            <div class="col-lg-9 col-8">
                                                <input type="text" id="device_modal_imei" class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    @if ($enablePinCodeField)
                                        <div class="col-12">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-lg-3 col-4">
                                                    <label class="form-label mb-0" for="device_modal_password">{{ __('Pin Code/Password') }}</label>
                                                </div>
                                                <div class="col-lg-9 col-8">
                                                    <input type="text" id="device_modal_password" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-12">
                                        <div class="row g-2">
                                            <div class="col-lg-3 col-4">
                                                <label class="form-label mb-0" for="device_modal_note">{{ __('Device Note') }}</label>
                                            </div>
                                            <div class="col-lg-9 col-8">
                                                <textarea id="device_modal_note" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="button" class="btn btn-primary" id="deviceModalSave">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-devices">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Devices') }}</h5>
                        <button type="button" class="btn btn-success btn-sm" id="addDeviceLine">
                            <i class="bi bi-plus-circle me-1"></i>
                            {{ __('Add Device') }}
                        </button>
                    </div>
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="devicesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Device') }}</th>
                                        <th style="width:180px">{{ __('Device ID/IMEI') }}</th>
                                        @if ($enablePinCodeField)
                                            <th style="width:180px">{{ __('Pin Code/Password') }}</th>
                                        @endif
                                        <th>{{ __('Device Note') }}</th>
                                        <th style="width:120px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldDevIds = (array) old('job_device_customer_device_id', []);
                                        $oldDevSerials = (array) old('job_device_serial', []);
                                        $oldDevPins = (array) old('job_device_pin', []);
                                        $oldDevNotes = (array) old('job_device_notes', []);
                                        $devRows = max(count($oldDevIds), count($oldDevSerials), count($oldDevPins), count($oldDevNotes));
                                    @endphp
                                    @if ($devRows === 0)
                                        <tr class="devices-empty-row">
                                            <td colspan="{{ $enablePinCodeField ? 5 : 4 }}" class="text-center text-muted py-4">
                                                {{ __('No devices added yet.') }}
                                            </td>
                                        </tr>
                                    @else
                                        @for ($i = 0; $i < $devRows; $i++)
                                            @php
                                                $devId = (string) ($oldDevIds[$i] ?? '');
                                                $imei = is_string($oldDevSerials[$i] ?? null) ? (string) $oldDevSerials[$i] : '';
                                                $pwd = is_string($oldDevPins[$i] ?? null) ? (string) $oldDevPins[$i] : '';
                                                $note = is_string($oldDevNotes[$i] ?? null) ? (string) $oldDevNotes[$i] : '';

                                                $deviceText = '';
                                                foreach ($devices as $d) {
                                                    if ((string) $d->id === $devId) {
                                                        $brand = is_string($d->brand?->name ?? null) ? (string) $d->brand?->name : '';
                                                        $model = is_string($d->model ?? null) ? (string) $d->model : '';
                                                        $deviceText = trim(trim($brand . ' ' . $model));
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td class="device-label" data-value="{{ $devId }}">{{ $deviceText !== '' ? $deviceText : '—' }}</td>
                                                <td class="device-imei" data-value="{{ $imei }}">{{ $imei !== '' ? $imei : '—' }}</td>
                                                @if ($enablePinCodeField)
                                                    <td class="device-password" data-value="{{ $pwd }}">{{ $pwd !== '' ? $pwd : '—' }}</td>
                                                @endif
                                                <td class="device-note" data-value="{{ $note }}"><span class="d-inline-block text-truncate" style="max-width: 420px;">{{ $note !== '' ? $note : '—' }}</span></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-outline-primary btn-sm editDeviceLine" aria-label="{{ __('Edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm removeDeviceLine" aria-label="{{ __('Remove') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                    <input type="hidden" name="job_device_customer_device_id[]" value="{{ $devId }}" />
                                                    <input type="hidden" name="job_device_serial[]" value="{{ $imei }}" />
                                                    @if ($enablePinCodeField)
                                                        <input type="hidden" name="job_device_pin[]" value="{{ $pwd }}" />
                                                    @endif
                                                    <input type="hidden" name="job_device_notes[]" value="{{ $note }}" />
                                                </td>
                                            </tr>
                                        @endfor
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @error('job_device_customer_device_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card mb-4" id="section-extras">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Extra Fields & Files') }}</h5>
                        <button type="button" class="btn btn-success btn-sm" id="addExtraLine">
                            <i class="bi bi-plus-circle me-1"></i>
                            {{ __('Add Field') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="extraTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:160px">{{ __('Date') }}</th>
                                        <th style="width:220px">{{ __('Label') }}</th>
                                        <th>{{ __('Data') }}</th>
                                        <th style="width:140px">{{ __('Visibility') }}</th>
                                        <th style="width:140px">{{ __('File') }}</th>
                                        <th style="width:120px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldExDates = (array) old('extra_item_occurred_at', []);
                                        $oldExLabels = (array) old('extra_item_label', []);
                                        $oldExData = (array) old('extra_item_data_text', []);
                                        $oldExDesc = (array) old('extra_item_description', []);
                                        $oldExVis = (array) old('extra_item_visibility', []);
                                        $exRows = max(count($oldExLabels), count($oldExData), count($oldExDesc), count($oldExVis), count($oldExDates));
                                    @endphp
                                    @if ($exRows === 0)
                                        <tr class="extras-empty-row">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                {{ __('No extra fields added yet.') }}
                                            </td>
                                        </tr>
                                    @else
                                        @for ($i = 0; $i < $exRows; $i++)
                                            @php
                                                $d = is_string($oldExDates[$i] ?? null) ? (string) $oldExDates[$i] : '';
                                                $l = is_string($oldExLabels[$i] ?? null) ? (string) $oldExLabels[$i] : '';
                                                $data = is_string($oldExData[$i] ?? null) ? (string) $oldExData[$i] : '';
                                                $desc = is_string($oldExDesc[$i] ?? null) ? (string) $oldExDesc[$i] : '';
                                                $vis = is_string($oldExVis[$i] ?? null) ? (string) $oldExVis[$i] : 'private';
                                            @endphp
                                            <tr>
                                                <td class="extra-date" data-value="{{ $d }}">{{ $d !== '' ? $d : '—' }}</td>
                                                <td class="extra-label" data-value="{{ $l }}">{{ $l !== '' ? $l : '—' }}</td>
                                                <td class="extra-data" data-value="{{ $data }}" data-desc="{{ $desc }}">
                                                    <span class="d-inline-block text-truncate" style="max-width: 420px;">{{ $data !== '' ? $data : '—' }}</span>
                                                </td>
                                                <td class="extra-vis" data-value="{{ $vis }}">{{ $vis === 'public' ? __('Public') : __('Private') }}</td>
                                                <td class="extra-file" data-value="">—</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-outline-primary btn-sm editExtraLine" aria-label="{{ __('Edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm removeExtraLine" aria-label="{{ __('Remove') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                    <input type="hidden" name="extra_item_occurred_at[]" value="{{ $d }}" />
                                                    <input type="hidden" name="extra_item_label[]" value="{{ $l }}" />
                                                    <input type="hidden" name="extra_item_data_text[]" value="{{ $data }}" />
                                                    <input type="hidden" name="extra_item_description[]" value="{{ $desc }}" />
                                                    <input type="hidden" name="extra_item_visibility[]" value="{{ $vis }}" />
                                                </td>
                                            </tr>
                                        @endfor
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        @error('extra_item_label')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="modal fade" id="extraModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Extra Field / File') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-lg-6">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-4">
                                                <label class="form-label mb-0" for="extra_modal_date">{{ __('Date') }}</label>
                                            </div>
                                            <div class="col-8">
                                                <input type="date" id="extra_modal_date" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-4">
                                                <label class="form-label mb-0" for="extra_modal_vis">{{ __('Visibility') }}</label>
                                            </div>
                                            <div class="col-8">
                                                <select id="extra_modal_vis" class="form-select">
                                                    <option value="public">{{ __('Customer & Staff') }}</option>
                                                    <option value="private">{{ __('Staff') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-lg-2 col-4">
                                                <label class="form-label mb-0" for="extra_modal_label">{{ __('Label') }}</label>
                                            </div>
                                            <div class="col-lg-10 col-8">
                                                <input type="text" id="extra_modal_label" class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-lg-2 col-4">
                                                <label class="form-label mb-0" for="extra_modal_data">{{ __('Data') }}</label>
                                            </div>
                                            <div class="col-lg-10 col-8">
                                                <input type="text" id="extra_modal_data" class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2">
                                            <div class="col-lg-2 col-4">
                                                <label class="form-label mb-0" for="extra_modal_desc">{{ __('Description') }}</label>
                                            </div>
                                            <div class="col-lg-10 col-8">
                                                <textarea id="extra_modal_desc" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-lg-2 col-4">
                                                <label class="form-label mb-0" for="extra_modal_file">{{ __('File') }}</label>
                                            </div>
                                            <div class="col-lg-10 col-8">
                                                <input type="file" id="extra_modal_file" class="form-control" />
                                                <div class="form-text">{{ __('Files will be submitted with the job form.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="button" class="btn btn-primary" id="extraModalSave">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-parts">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Select Parts') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="devicePartsSelects" class="row g-2"></div>

                        <select id="parts_select" class="form-select d-none" tabindex="-1" aria-hidden="true">
                            <option value="">{{ __('Search and select...') }}</option>
                            @foreach ($parts as $p)
                                <option value="{{ $p->name }}">{{ $p->name }}</option>
                            @endforeach
                        </select>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0" id="partsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:110px">{{ __('Code') }}</th>
                                        <th style="width:110px">{{ __('Capacity') }}</th>
                                        <th style="width:140px">{{ __('Device') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Total') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="parts-empty-row">
                                        <td colspan="8" class="text-center text-muted py-3">{{ __('No parts selected yet.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-services">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Select Services') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addServiceLineBtn">
                            <i class="bi bi-wrench-adjustable-circle me-1"></i>
                            {{ __('Add Service') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="deviceServicesSelects" class="row g-2"></div>

                        <select id="services_select" class="form-select d-none" tabindex="-1" aria-hidden="true">
                            <option value="">{{ __('Search and select...') }}</option>
                            @foreach ($services as $s)
                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                            @endforeach
                        </select>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0" id="servicesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:140px">{{ __('Service Code') }}</th>
                                        <th style="width:160px">{{ __('Device') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Total') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="services-empty-row">
                                        <td colspan="7" class="text-center text-muted py-3">{{ __('No services selected yet.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="section-other-items">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Other Items') }}</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addOtherItemLineBtn">
                            <i class="bi bi-plus-circle me-1"></i>
                            {{ __('Add Other Item') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0" id="otherItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:140px">{{ __('Code') }}</th>
                                        <th style="width:160px">{{ __('Device') }}</th>
                                        <th style="width:90px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Price') }}</th>
                                        <th style="width:130px" class="text-end">{{ __('Total') }}</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="other-empty-row">
                                        <td colspan="7" class="text-center text-muted py-3">{{ __('No other items added yet.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="position-sticky" style="top: 1rem;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Order Information') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted mb-3">{{ __('Totals will calculate after saving the job.') }}</div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">{{ __('Order Status') }}</label>
                                    @php $statusOld = old('status_slug', 'neworder'); @endphp
                                    <select name="status_slug" class="form-select">
                                        <option value="" {{ $statusOld === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                        @foreach ($jobStatuses as $s)
                                            <option value="{{ $s->code }}" {{ $statusOld === $s->code ? 'selected' : '' }}>
                                                {{ $s->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Payment Status') }}</label>
                                    @php $payOld = old('payment_status_slug', 'nostatus'); @endphp
                                    <select name="payment_status_slug" class="form-select">
                                        <option value="" {{ $payOld === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                        @foreach ($paymentStatuses as $ps)
                                            <option value="{{ $ps->slug }}" {{ $payOld === $ps->slug ? 'selected' : '' }}>
                                                {{ $ps->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Priority') }}</label>
                                    @php $p = old('priority', 'normal'); @endphp
                                    <select name="priority" class="form-select">
                                        <option value="normal" {{ $p === 'normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                                        <option value="high" {{ $p === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                        <option value="urgent" {{ $p === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                                    </select>
                                    @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Tax Mode') }}</label>
                                    @php $taxMode = old('prices_inclu_exclu'); @endphp
                                    <select name="prices_inclu_exclu" class="form-select">
                                        <option value="" {{ $taxMode === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                        <option value="exclusive" {{ $taxMode === 'exclusive' ? 'selected' : '' }}>{{ __('Exclusive') }}</option>
                                        <option value="inclusive" {{ $taxMode === 'inclusive' ? 'selected' : '' }}>{{ __('Inclusive') }}</option>
                                    </select>
                                    @error('prices_inclu_exclu')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Order Notes') }}</label>
                                    <textarea name="wc_order_note" class="form-control" rows="3" placeholder="{{ __('Visible to customer.') }}">{{ old('wc_order_note') }}</textarea>
                                    @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('File Attachment') }}</label>
                                    <input type="file" name="job_file" class="form-control" />
                                    @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-check2-circle me-1"></i>
                                        {{ __('Create Job') }}
                                    </button>
                                    <div class="text-muted small mt-2">{{ __('After creating, you will be redirected to the job page.') }}</div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="bi bi-printer me-1"></i>
                                            {{ __('Print') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="bi bi-file-earmark-pdf me-1"></i>
                                            {{ __('Download PDF') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="bi bi-envelope me-1"></i>
                                            {{ __('Email Customer') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="bi bi-pen me-1"></i>
                                            {{ __('Signature Request') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Navigation') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-job-details">
                                    <i class="bi bi-card-text me-1"></i>
                                    {{ __('Job Details') }}
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-devices">
                                    <i class="bi bi-phone me-1"></i>
                                    {{ __('Devices') }}
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-extras">
                                    <i class="bi bi-paperclip me-1"></i>
                                    {{ __('Extra Fields & Files') }}
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-parts">
                                    <i class="bi bi-box-seam me-1"></i>
                                    {{ __('Select Parts') }}
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-services">
                                    <i class="bi bi-wrench-adjustable-circle me-1"></i>
                                    {{ __('Select Services') }}
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-start" href="#section-other-items">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    {{ __('Other Items') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
(function () {
    var partsTable = document.getElementById('partsTable');
    var servicesTable = document.getElementById('servicesTable');
    var otherItemsTable = document.getElementById('otherItemsTable');

    var partsSelect = document.getElementById('parts_select');

    var devicePartsSelects = document.getElementById('devicePartsSelects');

    var deviceServicesSelects = document.getElementById('deviceServicesSelects');

    var servicesSelect = document.getElementById('services_select');
    var addServiceLineBtn = document.getElementById('addServiceLineBtn');
    var addOtherItemLineBtn = document.getElementById('addOtherItemLineBtn');

    var partRows = [];
    var serviceRows = [];
    var otherRows = [];

    function newRowId(prefix) {
        var p = (prefix || 'row');
        return p + '-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function ensureOtherEmptyState() {
        if (!otherItemsTable) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.other-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.other-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'other-empty-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-3">No other items added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function upsertOtherRowFromItemTr(itemTr) {
        if (!otherItemsTable || !itemTr) return;

        var typeVal = itemTr.querySelector('input[name="item_type[]"]')?.value || '';
        if (typeVal !== 'other') return;

        var nameVal = itemTr.querySelector('input[name="item_name[]"]')?.value || '';
        var qtyVal = itemTr.querySelector('input[name="item_qty[]"]')?.value || '1';
        var priceVal = itemTr.querySelector('input[name="item_unit_price_cents[]"]')?.value || '0';
        if (nameVal === '') return;

        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;

        var existing = tbody.querySelector('tr[data-item-row-id="' + itemTr.dataset.itemRowId + '"]');
        if (!existing) {
            existing = document.createElement('tr');
            existing.dataset.itemRowId = itemTr.dataset.itemRowId;
            existing.innerHTML = ''
                + '<td class="other-name"></td>'
                + '<td class="other-code"></td>'
                + '<td class="other-device"></td>'
                + '<td class="other-qty text-end"></td>'
                + '<td class="other-price text-end"></td>'
                + '<td class="other-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removeOther" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';
            tbody.appendChild(existing);
        }

        existing.querySelector('.other-name').textContent = nameVal;
        existing.querySelector('.other-code').textContent = '—';
        existing.querySelector('.other-device').textContent = '—';
        existing.querySelector('.other-qty').textContent = qtyVal;
        existing.querySelector('.other-price').textContent = formatCents(priceVal);
        existing.querySelector('.other-total').textContent = calcTotalCents(qtyVal, priceVal);
        ensureOtherEmptyState();
    }

    function removeOtherRowByItemRowId(itemRowId) {
        if (!otherItemsTable || !itemRowId) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;
        var tr = tbody.querySelector('tr[data-item-row-id="' + itemRowId + '"]');
        if (tr) tr.remove();
        ensureOtherEmptyState();
    }

    function hydrateOtherTableFromItems() {
        if (!table || !otherItemsTable) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;

        tbody.querySelectorAll('tr:not(.other-empty-row)').forEach(function (tr) { tr.remove(); });

        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var typeVal = tr.querySelector('input[name="item_type[]"]')?.value || '';
            if (typeVal === 'other') {
                if (!tr.dataset.itemRowId) {
                    tr.dataset.itemRowId = newRowId('item');
                }
                upsertOtherRowFromItemTr(tr);
            }
        });

        ensureOtherEmptyState();
    }

    function initDevicePartsSelect2(selectEl, placeholderText) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: placeholderText || 'Search and select...',
            allowClear: true
        });
    }

    function initDeviceServicesSelect2(selectEl, placeholderText) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: placeholderText || 'Search and select...',
            allowClear: true
        });
    }

    function renderDeviceServicesSelects() {
        if (!deviceServicesSelects) return;
        var devices = getSelectedDeviceLabels();
        deviceServicesSelects.innerHTML = '';

        if (devices.length === 0) {
            return;
        }

        var optionsHtml = cloneServicesOptionsHtml();

        devices.forEach(function (d) {
            var col = document.createElement('div');
            col.className = 'col-md-6';

            var label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = 'Service for ' + d.label;

            var sel = document.createElement('select');
            sel.className = 'form-select js-device-service-select';
            sel.dataset.deviceId = d.deviceId;
            sel.dataset.deviceLabel = d.label;
            sel.innerHTML = optionsHtml;

            col.appendChild(label);
            col.appendChild(sel);
            deviceServicesSelects.appendChild(col);

            initDeviceServicesSelect2(sel, 'Select services for ' + d.label);

            function handleServiceSelected() {
                var serviceName = '';
                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    serviceName = window.jQuery(sel).val() || '';
                } else {
                    serviceName = sel.value || '';
                }
                if (serviceName === '') return;

                var deviceIdForRow = d.deviceId ? String(d.deviceId) : '';

                serviceRows.push({
                    id: newRowId('service'),
                    name: serviceName,
                    code: '',
                    device_id: deviceIdForRow,
                    device: d.label,
                    qty: '1',
                    price: '0'
                });
                renderServiceRows();

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    window.jQuery(sel).val(null).trigger('change');
                } else {
                    sel.value = '';
                }
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(sel)
                    .off('change.rbDeviceService')
                    .on('change.rbDeviceService', handleServiceSelected)
                    .off('select2:select.rbDeviceService')
                    .on('select2:select.rbDeviceService', handleServiceSelected);
            } else {
                sel.addEventListener('change', handleServiceSelected);
            }
        });
    }

    function clonePartsOptionsHtml() {
        if (!partsSelect) return '';
        return partsSelect.innerHTML || '';
    }

    function cloneServicesOptionsHtml() {
        if (!servicesSelect) return '';
        return servicesSelect.innerHTML || '';
    }

    function getSelectedDeviceLabels() {
        var devicesTable = document.getElementById('devicesTable');
        if (!devicesTable) return [];
        var labels = [];
        devicesTable.querySelectorAll('tbody tr:not(.devices-empty-row)').forEach(function (tr) {
            var deviceId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
            if (!deviceId) {
                deviceId = tr.querySelector('.device-label')?.dataset?.value || '';
            }
            if (!deviceId) return;
            var label = (typeof deviceLabelMap !== 'undefined' && deviceLabelMap && deviceLabelMap[deviceId]) ? deviceLabelMap[deviceId] : (tr.querySelector('.device-label')?.textContent || '');
            label = (label || '').trim();
            if (label === '' || label === '—') {
                label = 'Device';
            }
            labels.push({ deviceId: deviceId, label: label });
        });
        return labels;
    }

    function buildDeviceOptionsHtml(selectedDeviceId) {
        var devices = getSelectedDeviceLabels();
        if (!Array.isArray(devices) || devices.length === 0) {
            return '<option value="">—</option>';
        }

        var selId = selectedDeviceId ? String(selectedDeviceId) : '';
        return devices
            .map(function (d) {
                var id = String(d.deviceId || '');
                var label = String(d.label || '');
                var selected = selId !== '' && id === selId ? ' selected' : '';
                return '<option value="' + id.replace(/"/g, '&quot;') + '"' + selected + '>' + label + '</option>';
            })
            .join('');
    }

    function buildDeviceOptionsHtmlAllowBlank(selectedDeviceId) {
        var devices = getSelectedDeviceLabels();
        var selId = selectedDeviceId ? String(selectedDeviceId) : '';
        var options = ['<option value=""' + (selId === '' ? ' selected' : '') + '>—</option>'];

        if (!Array.isArray(devices) || devices.length === 0) {
            return options.join('');
        }

        devices.forEach(function (d) {
            var id = String(d.deviceId || '');
            var label = String(d.label || '');
            var selected = selId !== '' && id === selId ? ' selected' : '';
            options.push('<option value="' + id.replace(/"/g, '&quot;') + '"' + selected + '>' + label + '</option>');
        });

        return options.join('');
    }

    function initRowDeviceSelect2(selectEl) {
        if (!selectEl) return;
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        var $sel = window.jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: 'Select device...',
            allowClear: true
        });
    }

    function refreshRowDeviceSelectOptions(rootEl) {
        if (!rootEl) return;
        rootEl.querySelectorAll('select.js-part-device, select.js-service-device, select.js-other-device').forEach(function (sel) {
            var current = sel.value || '';
            var nextOptionsHtml = buildDeviceOptionsHtml(current);

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                var $sel = window.jQuery(sel);

                if ($sel.hasClass('select2-hidden-accessible')) {
                    try {
                        $sel.select2('destroy');
                    } catch (e) {
                    }
                }

                sel.innerHTML = nextOptionsHtml;
                if (current) {
                    sel.value = current;
                }
                initRowDeviceSelect2(sel);

                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.trigger('change');
                }
            } else {
                sel.innerHTML = nextOptionsHtml;
                if (current) {
                    sel.value = current;
                }
            }
        });
    }

    function renderDevicePartsSelects() {
        if (!devicePartsSelects) return;
        var devices = getSelectedDeviceLabels();
        devicePartsSelects.innerHTML = '';

        if (devices.length === 0) {
            return;
        }

        var optionsHtml = clonePartsOptionsHtml();

        devices.forEach(function (d, idx) {
            var col = document.createElement('div');
            col.className = 'col-md-6';

            var label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = 'Part for ' + d.label;

            var sel = document.createElement('select');
            sel.className = 'form-select js-device-part-select';
            sel.dataset.deviceId = d.deviceId;
            sel.dataset.deviceLabel = d.label;
            sel.innerHTML = optionsHtml;

            col.appendChild(label);
            col.appendChild(sel);
            devicePartsSelects.appendChild(col);

            initDevicePartsSelect2(sel, 'Select parts for ' + d.label);

            function handlePartSelected() {
                var partName = '';
                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    partName = window.jQuery(sel).val() || '';
                } else {
                    partName = sel.value || '';
                }
                if (partName === '') return;

                var deviceIdForRow = d.deviceId ? String(d.deviceId) : '';

                partRows.push({
                    id: newRowId('part'),
                    name: partName,
                    code: '',
                    capacity: '',
                    device_id: deviceIdForRow,
                    device: d.label,
                    qty: '1',
                    price: '0'
                });
                renderPartRows();

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                    window.jQuery(sel).val(null).trigger('change');
                } else {
                    sel.value = '';
                }
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(sel)
                    .off('change.rbDevicePart')
                    .on('change.rbDevicePart', handlePartSelected)
                    .off('select2:select.rbDevicePart')
                    .on('select2:select.rbDevicePart', handlePartSelected);
            } else {
                sel.addEventListener('change', handlePartSelected);
            }
        });
    }

    if (addOtherItemLineBtn) {
        addOtherItemLineBtn.addEventListener('click', function () {
            otherRows.push({
                id: newRowId('other'),
                name: '',
                code: '',
                device_id: '',
                device: '',
                qty: '1',
                price: '0'
            });
            renderOtherRows();
        });
    }

    function initServicesSelect2() {
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        if (!servicesSelect) {
            return;
        }
        var $ss = window.jQuery(servicesSelect);
        if ($ss.hasClass('select2-hidden-accessible')) {
            return;
        }
        $ss.select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: 'Search and select...'
        });
    }

    function resetItemModal() {
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
            if (itemModalService) window.jQuery(itemModalService).val('').trigger('change');
            if (itemModalPart) window.jQuery(itemModalPart).val('').trigger('change');
        } else {
            if (itemModalService) itemModalService.value = '';
            if (itemModalPart) itemModalPart.value = '';
        }
        if (itemModalOther) itemModalOther.value = '';
        if (itemModalQty) itemModalQty.value = '1';
        if (itemModalPrice) itemModalPrice.value = '0';
        editingItemRow = null;
    }

    function setItemModalMode(mode) {
        itemMode = mode;
        if (itemModalPickService) itemModalPickService.style.display = (mode === 'service') ? '' : 'none';
        if (itemModalPickPart) itemModalPickPart.style.display = (mode === 'part') ? '' : 'none';
        if (itemModalOtherWrap) itemModalOtherWrap.style.display = (mode === 'other') ? '' : 'none';

        if (itemModalTitle) {
            itemModalTitle.textContent = mode === 'service' ? 'Service' : (mode === 'part' ? 'Part' : 'Other Item');
        }
    }

    function openItemModalForAdd(mode) {
        resetItemModal();
        setItemModalMode(mode);
        var m = ensureItemModal();
        initItemModalSelect2();
        if (m) m.show();
    }

    function openItemModalForEdit(tr) {
        if (!tr) return;
        editingItemRow = tr;

        var typeVal = tr.querySelector('input[name="item_type[]"]')?.value || 'other';
        var nameVal = tr.querySelector('input[name="item_name[]"]')?.value || '';
        var qtyVal = tr.querySelector('input[name="item_qty[]"]')?.value || '1';
        var priceVal = tr.querySelector('input[name="item_unit_price_cents[]"]')?.value || '0';

        setItemModalMode(typeVal === 'service' ? 'service' : (typeVal === 'part' ? 'part' : 'other'));
        initItemModalSelect2();

        if (itemModalQty) itemModalQty.value = qtyVal;
        if (itemModalPrice) itemModalPrice.value = priceVal;

        if (typeVal === 'service') {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(itemModalService).val(nameVal).trigger('change');
            } else if (itemModalService) {
                itemModalService.value = nameVal;
            }
        } else if (typeVal === 'part') {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(itemModalPart).val(nameVal).trigger('change');
            } else if (itemModalPart) {
                itemModalPart.value = nameVal;
            }
        } else {
            if (itemModalOther) itemModalOther.value = nameVal;
        }

        var m = ensureItemModal();
        if (m) m.show();
    }

    function typeLabel(type) {
        if (type === 'service') return 'Service';
        if (type === 'part') return 'Part';
        return 'Other';
    }

    function ensureItemsEmptyState() {
        if (!table) return;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.items-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.items-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'items-empty-row';
                tr.innerHTML = '<td colspan="5" class="text-center text-muted py-4">No items added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function ensurePartsEmptyState() {
        if (!partsTable) return;
        var tbody = partsTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.parts-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.parts-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'parts-empty-row';
                tr.innerHTML = '<td colspan="8" class="text-center text-muted py-3">No parts selected yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function formatCents(centsStr) {
        var n = parseInt(centsStr || '0', 10);
        if (!Number.isFinite(n)) n = 0;
        return String(n);
    }

    function normalizeQty(qtyStr) {
        var n = parseInt(qtyStr || '0', 10);
        if (!Number.isFinite(n) || n < 0) n = 0;
        return String(n);
    }

    function calcTotalCents(qtyStr, priceStr) {
        var q = parseInt(qtyStr || '0', 10);
        var p = parseInt(priceStr || '0', 10);
        if (!Number.isFinite(q)) q = 0;
        if (!Number.isFinite(p)) p = 0;
        return String(q * p);
    }

    function ensureServicesEmptyState() {
        if (!servicesTable) return;
        var tbody = servicesTable.querySelector('tbody');
        if (!tbody) return;
        var hasRows = tbody.querySelectorAll('tr:not(.services-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.services-empty-row');
        if (hasRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'services-empty-row';
                tr.innerHTML = '<td colspan="7" class="text-center text-muted py-3">No services selected yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function renderServiceRows() {
        if (!servicesTable) return;
        var tbody = servicesTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.services-empty-row)').forEach(function (tr) { tr.remove(); });

        serviceRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td class="js-service-name"></td>'
                + '<td class="js-service-code"></td>'
                + '<td><select class="form-select form-select-sm js-service-device" aria-label="Device">' + buildDeviceOptionsHtml(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-service-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-service-price" value="0" /></td>'
                + '<td class="service-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removeService" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-service-name').textContent = row.name || '';
            tr.querySelector('.js-service-code').textContent = row.code || '';
            var devSel = tr.querySelector('.js-service-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                if (current !== '') {
                    devSel.value = current;
                } else {
                    var opt0 = devSel.querySelector('option');
                    if (opt0) {
                        devSel.value = opt0.value;
                    }
                }
            }
            tr.querySelector('.js-service-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-service-price').value = formatCents(row.price);
            tr.querySelector('.service-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensureServicesEmptyState();
    }

    function renderPartRows() {
        if (!partsTable) return;
        var tbody = partsTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.parts-empty-row)').forEach(function (tr) { tr.remove(); });

        partRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td class="js-part-name"></td>'
                + '<td class="js-part-code"></td>'
                + '<td class="js-part-capacity"></td>'
                + '<td><select class="form-select form-select-sm js-part-device" aria-label="Device">' + buildDeviceOptionsHtml(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-part-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-part-price" value="0" /></td>'
                + '<td class="part-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removePart" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-part-name').textContent = row.name || '';
            tr.querySelector('.js-part-code').textContent = row.code || '';
            tr.querySelector('.js-part-capacity').textContent = row.capacity || '';
            var devSel = tr.querySelector('.js-part-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                if (current !== '') {
                    devSel.value = current;
                } else {
                    var opt0 = devSel.querySelector('option');
                    if (opt0) {
                        devSel.value = opt0.value;
                    }
                }
            }
            tr.querySelector('.js-part-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-part-price').value = formatCents(row.price);
            tr.querySelector('.part-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensurePartsEmptyState();
    }

    var devicesTableForParts = document.getElementById('devicesTable');
    if (devicesTableForParts) {
        devicesTableForParts.addEventListener('click', function (e) {
            var rmBtn = e.target.closest('.removeDeviceLine');
            if (rmBtn) {
                setTimeout(function () {
                    renderDevicePartsSelects();
                    renderDeviceServicesSelects();
                    renderPartRows();
                    renderServiceRows();
                    renderOtherRows();
                    refreshRowDeviceSelectOptions(document);
                }, 0);
            }
        });
    }

    function renderOtherRows() {
        if (!otherItemsTable) return;
        var tbody = otherItemsTable.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr:not(.other-empty-row)').forEach(function (tr) { tr.remove(); });

        otherRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.rowId = row.id;
            tr.innerHTML = ''
                + '<td><input type="text" class="form-control form-control-sm js-other-name" value="" /></td>'
                + '<td><input type="text" class="form-control form-control-sm js-other-code" value="" /></td>'
                + '<td><select class="form-select form-select-sm js-other-device" aria-label="Device">' + buildDeviceOptionsHtmlAllowBlank(row.device_id) + '</select></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-other-qty" value="0" /></td>'
                + '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm text-end js-other-price" value="0" /></td>'
                + '<td class="other-total text-end"></td>'
                + '<td class="text-end">'
                + '  <button type="button" class="btn btn-outline-danger btn-sm removeOther" aria-label="Remove"><i class="bi bi-trash"></i></button>'
                + '</td>';

            tr.querySelector('.js-other-name').value = row.name || '';
            tr.querySelector('.js-other-code').value = row.code || '';
            var devSel = tr.querySelector('.js-other-device');
            if (devSel) {
                var current = row.device_id ? String(row.device_id) : '';
                devSel.value = current;
                initRowDeviceSelect2(devSel);
            }
            tr.querySelector('.js-other-qty').value = normalizeQty(row.qty);
            tr.querySelector('.js-other-price').value = formatCents(row.price);
            tr.querySelector('.other-total').textContent = calcTotalCents(row.qty, row.price);
            tbody.appendChild(tr);
        });

        ensureOtherEmptyState();
    }

    if (addServiceLineBtn) {
        addServiceLineBtn.addEventListener('click', function () {
            var first = deviceServicesSelects ? deviceServicesSelects.querySelector('select.js-device-service-select') : null;
            if (first) {
                first.focus();
            }
        });
    }

    if (partsTable) {
        partsTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = partRows.find(function (r) { return r.id === rowId; });
            if (!row) return;

            var devSel = tr.querySelector('.js-part-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && typeof deviceLabelMap !== 'undefined' && deviceLabelMap && deviceLabelMap[devId]) {
                    row.device = deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-part-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-part-price')?.value || '0');

            var totalCell = tr.querySelector('.part-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        partsTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removePart');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            partRows = partRows.filter(function (r) { return r.id !== rowId; });
            renderPartRows();
        });
    }

    if (servicesTable) {
        servicesTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = serviceRows.find(function (r) { return r.id === rowId; });
            if (!row) return;

            var devSel = tr.querySelector('.js-service-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && typeof deviceLabelMap !== 'undefined' && deviceLabelMap && deviceLabelMap[devId]) {
                    row.device = deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-service-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-service-price')?.value || '0');

            var totalCell = tr.querySelector('.service-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        servicesTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removeService');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            serviceRows = serviceRows.filter(function (r) { return r.id !== rowId; });
            renderServiceRows();
        });
    }

    if (otherItemsTable) {
        otherItemsTable.addEventListener('input', function (e) {
            var tr = e.target.closest('tr');
            if (!tr) return;
            var rowId = tr.dataset.rowId || '';
            if (!rowId) return;
            var row = otherRows.find(function (r) { return r.id === rowId; });
            if (!row) return;

            row.name = tr.querySelector('.js-other-name')?.value || '';
            row.code = tr.querySelector('.js-other-code')?.value || '';
            var devSel = tr.querySelector('.js-other-device');
            if (devSel) {
                var devId = devSel.value || '';
                row.device_id = devId;
                if (devId && typeof deviceLabelMap !== 'undefined' && deviceLabelMap && deviceLabelMap[devId]) {
                    row.device = deviceLabelMap[devId];
                } else {
                    var opt = devSel.options && devSel.selectedIndex >= 0 ? devSel.options[devSel.selectedIndex] : null;
                    row.device = opt ? (opt.textContent || '') : '';
                }
            }
            row.qty = normalizeQty(tr.querySelector('.js-other-qty')?.value || '0');
            row.price = formatCents(tr.querySelector('.js-other-price')?.value || '0');

            var totalCell = tr.querySelector('.other-total');
            if (totalCell) totalCell.textContent = calcTotalCents(row.qty, row.price);
        });

        otherItemsTable.addEventListener('click', function (e) {
            var rm = e.target.closest('.removeOther');
            if (!rm) return;
            var tr = rm.closest('tr');
            var rowId = tr ? tr.dataset.rowId : '';
            if (!rowId) return;
            otherRows = otherRows.filter(function (r) { return r.id !== rowId; });
            renderOtherRows();
        });
    }

    renderPartRows();
    renderServiceRows();
    renderOtherRows();

    renderDevicePartsSelects();
    renderDeviceServicesSelects();

    var deviceAddBtn = document.getElementById('addDeviceLine');
    var devicesTable = document.getElementById('devicesTable');
    var deviceModalEl = document.getElementById('deviceModal');
    var deviceModal = null;

    var deviceModalDevice = document.getElementById('device_modal_device');
    var deviceModalImei = document.getElementById('device_modal_imei');
    var deviceModalNote = document.getElementById('device_modal_note');
    var deviceModalSave = document.getElementById('deviceModalSave');
    var deviceModalPassword = document.getElementById('device_modal_password');

    var editingDeviceRow = null;

    var enablePinCodeField = @json($enablePinCodeField);

    var deviceLabelMap = {};
    @foreach ($devices as $d)
        @php
            $brand = is_string($d->brand?->name ?? null) ? (string) $d->brand?->name : '';
            $model = is_string($d->model ?? null) ? (string) $d->model : '';
            $text = trim(trim($brand . ' ' . $model));
            if ($text === '') {
                $text = __('Device') . ' #' . $d->id;
            }
        @endphp
        deviceLabelMap[@json((string) $d->id)] = @json($text);
    @endforeach

    function ensureDeviceModal() {
        if (!deviceModalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }
        if (!deviceModal) {
            deviceModal = new window.bootstrap.Modal(deviceModalEl);
        }
        return deviceModal;
    }

    function initDeviceModalSelect2() {
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            return;
        }
        if (!deviceModalEl || !deviceModalDevice) {
            return;
        }

        var $sel = window.jQuery(deviceModalDevice);
        if ($sel.hasClass('select2-hidden-accessible')) {
            return;
        }

        $sel.select2({
            width: '100%',
            theme: 'bootstrap-5',
            dropdownParent: window.jQuery(deviceModalEl),
            placeholder: @json(__('Select Device')),
            allowClear: true
        });
    }

    function resetDeviceModal() {
        if (deviceModalDevice) deviceModalDevice.value = '';
        if (deviceModalImei) deviceModalImei.value = '';
        if (deviceModalNote) deviceModalNote.value = '';
        if (deviceModalPassword) deviceModalPassword.value = '';
        editingDeviceRow = null;
    }

    function openDeviceModalForAdd() {
        resetDeviceModal();
        var m = ensureDeviceModal();
        initDeviceModalSelect2();
        if (m) m.show();
    }

    function openDeviceModalForEdit(tr) {
        if (!tr) return;
        editingDeviceRow = tr;

        initDeviceModalSelect2();

        var devId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
        var imeiVal = tr.querySelector('input[name="job_device_serial[]"]')?.value || '';
        var noteVal = tr.querySelector('input[name="job_device_notes[]"]')?.value || '';
        var pwdVal = enablePinCodeField ? (tr.querySelector('input[name="job_device_pin[]"]')?.value || '') : '';

        if (deviceModalDevice) {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(deviceModalDevice).val(devId || '').trigger('change');
            } else {
                deviceModalDevice.value = devId;
            }
        }
        if (deviceModalImei) deviceModalImei.value = imeiVal;
        if (deviceModalNote) deviceModalNote.value = noteVal;
        if (deviceModalPassword) deviceModalPassword.value = pwdVal;

        var m = ensureDeviceModal();
        if (m) m.show();
    }

    function renderDeviceRowSummary(tr) {
        if (!tr) return;
        var devId = tr.querySelector('input[name="job_device_customer_device_id[]"]')?.value || '';
        var imeiVal = tr.querySelector('input[name="job_device_serial[]"]')?.value || '';
        var noteVal = tr.querySelector('input[name="job_device_notes[]"]')?.value || '';
        var pwdVal = enablePinCodeField ? (tr.querySelector('input[name="job_device_pin[]"]')?.value || '') : '';

        var labelCell = tr.querySelector('.device-label');
        var imeiCell = tr.querySelector('.device-imei');
        var noteCell = tr.querySelector('.device-note');
        var pwdCell = tr.querySelector('.device-password');

        var labelText = devId && deviceLabelMap[devId] ? deviceLabelMap[devId] : '';
        if (labelCell) {
            labelCell.dataset.value = devId;
            labelCell.textContent = labelText !== '' ? labelText : '—';
        }
        if (imeiCell) {
            imeiCell.dataset.value = imeiVal;
            imeiCell.textContent = imeiVal !== '' ? imeiVal : '—';
        }
        if (pwdCell) {
            pwdCell.dataset.value = pwdVal;
            pwdCell.textContent = pwdVal !== '' ? pwdVal : '—';
        }
        if (noteCell) {
            noteCell.dataset.value = noteVal;
            var span = noteCell.querySelector('span');
            if (span) {
                span.textContent = noteVal !== '' ? noteVal : '—';
                span.title = noteVal;
            } else {
                noteCell.textContent = noteVal !== '' ? noteVal : '—';
            }
        }
    }

    function buildDeviceRow(values) {
        var tr = document.createElement('tr');
        tr.innerHTML = ''
            + '<td class="device-label" data-value=""></td>'
            + '<td class="device-imei" data-value=""></td>'
            + (enablePinCodeField ? '<td class="device-password" data-value=""></td>' : '')
            + '<td class="device-note" data-value=""><span class="d-inline-block text-truncate" style="max-width: 420px;"></span></td>'
            + '<td class="text-end">'
            + '  <button type="button" class="btn btn-outline-primary btn-sm editDeviceLine" aria-label="Edit"><i class="bi bi-pencil"></i></button>'
            + '  <button type="button" class="btn btn-outline-danger btn-sm removeDeviceLine" aria-label="Remove"><i class="bi bi-trash"></i></button>'
            + '  <input type="hidden" name="job_device_customer_device_id[]" value="" />'
            + '  <input type="hidden" name="job_device_serial[]" value="" />'
            + (enablePinCodeField ? '  <input type="hidden" name="job_device_pin[]" value="" />' : '')
            + '  <input type="hidden" name="job_device_notes[]" value="" />'
            + '</td>';

        tr.querySelector('input[name="job_device_customer_device_id[]"]').value = values.deviceId || '';
        tr.querySelector('input[name="job_device_serial[]"]').value = values.imei || '';
        tr.querySelector('input[name="job_device_notes[]"]').value = values.note || '';
        if (enablePinCodeField) {
            tr.querySelector('input[name="job_device_pin[]"]').value = values.password || '';
        }
        renderDeviceRowSummary(tr);
        return tr;
    }

    function ensureDevicesEmptyState() {
        if (!devicesTable) return;
        var tbody = devicesTable.querySelector('tbody');
        if (!tbody) return;
        var hasRealRows = tbody.querySelectorAll('tr:not(.devices-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.devices-empty-row');
        if (hasRealRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'devices-empty-row';
                tr.innerHTML = '<td colspan="' + (enablePinCodeField ? 5 : 4) + '" class="text-center text-muted py-4">No devices added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function saveDeviceModal() {
        if (!devicesTable) return;
        var tbody = devicesTable.querySelector('tbody');
        if (!tbody) return;

        var values = {
            deviceId: (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function' && deviceModalDevice)
                ? (window.jQuery(deviceModalDevice).val() || '')
                : (deviceModalDevice ? deviceModalDevice.value : ''),
            imei: deviceModalImei ? deviceModalImei.value : '',
            note: deviceModalNote ? deviceModalNote.value : '',
            password: deviceModalPassword ? deviceModalPassword.value : ''
        };

        var targetRow = editingDeviceRow;
        if (!targetRow) {
            var emptyRow = tbody.querySelector('.devices-empty-row');
            if (emptyRow) emptyRow.remove();
            targetRow = buildDeviceRow(values);
            tbody.appendChild(targetRow);
            ensureDevicesEmptyState();
        } else {
            targetRow.querySelector('input[name="job_device_customer_device_id[]"]').value = values.deviceId;
            targetRow.querySelector('input[name="job_device_serial[]"]').value = values.imei;
            targetRow.querySelector('input[name="job_device_notes[]"]').value = values.note;
            if (enablePinCodeField) {
                targetRow.querySelector('input[name="job_device_pin[]"]').value = values.password;
            }
            renderDeviceRowSummary(targetRow);
        }

        editingDeviceRow = null;
        ensureDevicesEmptyState();
        renderDevicePartsSelects();
        renderDeviceServicesSelects();
        renderPartRows();
        renderServiceRows();
        renderOtherRows();
        refreshRowDeviceSelectOptions(document);
        var m = ensureDeviceModal();
        if (m) m.hide();
    }

    if (deviceAddBtn) {
        deviceAddBtn.addEventListener('click', function () {
            openDeviceModalForAdd();
        });
    }

    if (deviceModalSave) {
        deviceModalSave.addEventListener('click', function () {
            saveDeviceModal();
        });
    }

    if (devicesTable) {
        devicesTable.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.editDeviceLine');
            if (editBtn) {
                var tr = editBtn.closest('tr');
                openDeviceModalForEdit(tr);
                return;
            }
            var rmBtn = e.target.closest('.removeDeviceLine');
            if (rmBtn) {
                var tr2 = rmBtn.closest('tr');
                if (tr2) {
                    tr2.remove();
                    ensureDevicesEmptyState();
                    renderDevicePartsSelects();
                    renderDeviceServicesSelects();
                    renderPartRows();
                    renderServiceRows();
                    renderOtherRows();
                    refreshRowDeviceSelectOptions(document);
                }
            }
        });
    }

    ensureDevicesEmptyState();

    var extraAddBtn = document.getElementById('addExtraLine');
    var extraTable = document.getElementById('extraTable');
    var extraModalEl = document.getElementById('extraModal');
    var extraModal = null;

    var extraModalDate = document.getElementById('extra_modal_date');
    var extraModalLabel = document.getElementById('extra_modal_label');
    var extraModalData = document.getElementById('extra_modal_data');
    var extraModalDesc = document.getElementById('extra_modal_desc');
    var extraModalVis = document.getElementById('extra_modal_vis');
    var extraModalFile = document.getElementById('extra_modal_file');
    var extraModalSave = document.getElementById('extraModalSave');

    var editingExtraRow = null;

    function ensureExtraModal() {
        if (!extraModalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }
        if (!extraModal) {
            extraModal = new window.bootstrap.Modal(extraModalEl);
        }
        return extraModal;
    }

    function truncateText(str, max) {
        if (typeof str !== 'string') return '';
        if (str.length <= max) return str;
        return str.slice(0, max - 1) + '…';
    }

    function resetExtraModal() {
        if (extraModalDate) extraModalDate.value = '';
        if (extraModalLabel) extraModalLabel.value = '';
        if (extraModalData) extraModalData.value = '';
        if (extraModalDesc) extraModalDesc.value = '';
        if (extraModalVis) extraModalVis.value = 'private';
        if (extraModalFile) extraModalFile.value = '';
        editingExtraRow = null;
    }

    function openExtraModalForAdd() {
        resetExtraModal();
        var m = ensureExtraModal();
        if (m) m.show();
    }

    function openExtraModalForEdit(tr) {
        if (!tr) return;
        editingExtraRow = tr;

        var dateVal = tr.querySelector('input[name="extra_item_occurred_at[]"]')?.value || '';
        var labelVal = tr.querySelector('input[name="extra_item_label[]"]')?.value || '';
        var dataVal = tr.querySelector('input[name="extra_item_data_text[]"]')?.value || '';
        var descVal = tr.querySelector('input[name="extra_item_description[]"]')?.value || '';
        var visVal = tr.querySelector('input[name="extra_item_visibility[]"]')?.value || 'private';

        if (extraModalDate) extraModalDate.value = dateVal;
        if (extraModalLabel) extraModalLabel.value = labelVal;
        if (extraModalData) extraModalData.value = dataVal;
        if (extraModalDesc) extraModalDesc.value = descVal;
        if (extraModalVis) extraModalVis.value = visVal;
        if (extraModalFile) extraModalFile.value = '';

        var m = ensureExtraModal();
        if (m) m.show();
    }

    function renderExtraRowSummary(tr) {
        if (!tr) return;

        var dateVal = tr.querySelector('input[name="extra_item_occurred_at[]"]')?.value || '';
        var labelVal = tr.querySelector('input[name="extra_item_label[]"]')?.value || '';
        var dataVal = tr.querySelector('input[name="extra_item_data_text[]"]')?.value || '';
        var descVal = tr.querySelector('input[name="extra_item_description[]"]')?.value || '';
        var visVal = tr.querySelector('input[name="extra_item_visibility[]"]')?.value || 'private';

        var dateCell = tr.querySelector('.extra-date');
        var labelCell = tr.querySelector('.extra-label');
        var dataCell = tr.querySelector('.extra-data');
        var visCell = tr.querySelector('.extra-vis');

        if (dateCell) {
            dateCell.dataset.value = dateVal;
            dateCell.textContent = dateVal !== '' ? dateVal : '—';
        }

        if (labelCell) {
            labelCell.dataset.value = labelVal;
            labelCell.textContent = labelVal !== '' ? labelVal : '—';
        }

        if (dataCell) {
            dataCell.dataset.value = dataVal;
            dataCell.dataset.desc = descVal;
            var span = dataCell.querySelector('span');
            if (span) {
                span.textContent = dataVal !== '' ? truncateText(dataVal, 120) : '—';
                span.title = dataVal;
            }
        }

        if (visCell) {
            visCell.dataset.value = visVal;
            visCell.textContent = visVal === 'public' ? 'Public' : 'Private';
        }
    }

    function ensureExtraRowFileInput(tr) {
        if (!tr) return;
        var existing = tr.querySelector('input[name="extra_item_file[]"]');
        if (existing) return existing;
        var input = document.createElement('input');
        input.type = 'file';
        input.name = 'extra_item_file[]';
        input.className = 'd-none';
        tr.appendChild(input);
        return input;
    }

    function updateExtraFileCell(tr, fileName) {
        var cell = tr.querySelector('.extra-file');
        if (!cell) return;
        cell.dataset.value = fileName || '';
        cell.textContent = fileName ? fileName : '—';
        cell.title = fileName || '';
    }

    function buildExtraRow(values) {
        var tr = document.createElement('tr');
        tr.innerHTML = ''
            + '<td class="extra-date" data-value=""></td>'
            + '<td class="extra-label" data-value=""></td>'
            + '<td class="extra-data" data-value="" data-desc=""><span class="d-inline-block text-truncate" style="max-width: 420px;"></span></td>'
            + '<td class="extra-vis" data-value=""></td>'
            + '<td class="extra-file" data-value="">—</td>'
            + '<td class="text-end">'
            + '  <button type="button" class="btn btn-outline-primary btn-sm editExtraLine" aria-label="Edit"><i class="bi bi-pencil"></i></button>'
            + '  <button type="button" class="btn btn-outline-danger btn-sm removeExtraLine" aria-label="Remove"><i class="bi bi-trash"></i></button>'
            + '  <input type="hidden" name="extra_item_occurred_at[]" value="" />'
            + '  <input type="hidden" name="extra_item_label[]" value="" />'
            + '  <input type="hidden" name="extra_item_data_text[]" value="" />'
            + '  <input type="hidden" name="extra_item_description[]" value="" />'
            + '  <input type="hidden" name="extra_item_visibility[]" value="private" />'
            + '</td>';

        tr.querySelector('input[name="extra_item_occurred_at[]"]').value = values.date || '';
        tr.querySelector('input[name="extra_item_label[]"]').value = values.label || '';
        tr.querySelector('input[name="extra_item_data_text[]"]').value = values.data || '';
        tr.querySelector('input[name="extra_item_description[]"]').value = values.desc || '';
        tr.querySelector('input[name="extra_item_visibility[]"]').value = values.vis || 'private';

        renderExtraRowSummary(tr);
        updateExtraFileCell(tr, values.fileName || '');

        return tr;
    }

    function ensureExtrasEmptyState() {
        if (!extraTable) return;
        var tbody = extraTable.querySelector('tbody');
        if (!tbody) return;
        var hasRealRows = tbody.querySelectorAll('tr:not(.extras-empty-row)').length > 0;
        var emptyRow = tbody.querySelector('.extras-empty-row');
        if (hasRealRows) {
            if (emptyRow) emptyRow.remove();
        } else {
            if (!emptyRow) {
                var tr = document.createElement('tr');
                tr.className = 'extras-empty-row';
                tr.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No extra fields added yet.</td>';
                tbody.appendChild(tr);
            }
        }
    }

    function saveExtraModal() {
        if (!extraTable) return;
        var tbody = extraTable.querySelector('tbody');
        if (!tbody) return;

        var values = {
            date: extraModalDate ? extraModalDate.value : '',
            label: extraModalLabel ? extraModalLabel.value : '',
            data: extraModalData ? extraModalData.value : '',
            desc: extraModalDesc ? extraModalDesc.value : '',
            vis: extraModalVis ? extraModalVis.value : 'private',
            fileName: ''
        };

        var targetRow = editingExtraRow;

        if (!targetRow) {
            targetRow = buildExtraRow(values);
            tbody.appendChild(targetRow);
            ensureExtrasEmptyState();
        } else {
            targetRow.querySelector('input[name="extra_item_occurred_at[]"]').value = values.date;
            targetRow.querySelector('input[name="extra_item_label[]"]').value = values.label;
            targetRow.querySelector('input[name="extra_item_data_text[]"]').value = values.data;
            targetRow.querySelector('input[name="extra_item_description[]"]').value = values.desc;
            targetRow.querySelector('input[name="extra_item_visibility[]"]').value = values.vis;
            renderExtraRowSummary(targetRow);
        }

        var selectedFile = extraModalFile && extraModalFile.files && extraModalFile.files[0] ? extraModalFile.files[0] : null;
        if (selectedFile) {
            var rowFileInput = ensureExtraRowFileInput(targetRow);
            try {
                rowFileInput.files = extraModalFile.files;
            } catch (e) {
            }
            updateExtraFileCell(targetRow, selectedFile.name);
        }

        editingExtraRow = null;
        ensureExtrasEmptyState();

        var m = ensureExtraModal();
        if (m) m.hide();
    }

    if (extraAddBtn) {
        extraAddBtn.addEventListener('click', function () {
            openExtraModalForAdd();
        });
    }

    if (extraModalSave) {
        extraModalSave.addEventListener('click', function () {
            saveExtraModal();
        });
    }

    if (extraTable) {
        extraTable.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.editExtraLine');
            if (editBtn) {
                var tr = editBtn.closest('tr');
                openExtraModalForEdit(tr);
                return;
            }

            var rmBtn = e.target.closest('.removeExtraLine');
            if (rmBtn) {
                var tr2 = rmBtn.closest('tr');
                if (tr2) {
                    tr2.remove();
                }
            }
        });
    }
})();

@push('page-scripts')
    <script>
        (function () {
            window.RBQuickCustomerModal = window.RBQuickCustomerModal || {};
            window.RBQuickCustomerModal.createUrl = @json(route('tenant.operations.clients.store', ['business' => $tenantSlug]));
            window.RBQuickCustomerModal.targetSelectId = 'customer_id';

            window.RBQuickTechnicianModal = window.RBQuickTechnicianModal || {};
            window.RBQuickTechnicianModal.createUrl = @json(route('tenant.technicians.store', ['business' => $tenantSlug]));
            window.RBQuickTechnicianModal.targetSelectId = 'technician_ids';

            var openBtn = document.getElementById('rb_open_quick_customer');
            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    if (window.RBQuickCustomerModal && typeof window.RBQuickCustomerModal.open === 'function') {
                        window.RBQuickCustomerModal.open({});
                    }
                });
            }

            var openTechBtn = document.getElementById('rb_open_quick_technician');
            if (openTechBtn) {
                openTechBtn.addEventListener('click', function () {
                    if (window.RBQuickTechnicianModal && typeof window.RBQuickTechnicianModal.open === 'function') {
                        window.RBQuickTechnicianModal.open({});
                    }
                });
            }
        })();
    </script>
@endpush

@push('page-scripts')
    <script>
        (function () {
            if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
                return;
            }

            window.jQuery(function () {
                var $customer = window.jQuery('#customer_id');
                var $techs = window.jQuery('#technician_ids');

                function initOnce($el, opts) {
                    if (!$el || !$el.length) {
                        return;
                    }
                    if ($el.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    $el.select2(opts);
                }

                initOnce($customer, {
                    width: '100%',
                    theme: 'bootstrap-5',
                    placeholder: @json(__('Select...')),
                    allowClear: true
                });

                initOnce($techs, {
                    width: '100%',
                    theme: 'bootstrap-5',
                    placeholder: @json(__('Select...'))
                });
            });
        })();
    </script>
@endpush

@endsection

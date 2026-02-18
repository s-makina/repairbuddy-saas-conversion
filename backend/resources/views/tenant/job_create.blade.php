@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Job'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyJob|null $job */
    $job = $job ?? null;
    $jobId = $jobId ?? null;
    $isEdit = is_numeric($jobId) && (int) $jobId > 0;

    $jobStatuses = is_iterable($jobStatuses ?? null) ? $jobStatuses : [];
    $paymentStatuses = is_iterable($paymentStatuses ?? null) ? $paymentStatuses : [];
    $customers = is_iterable($customers ?? null) ? $customers : [];
    $technicians = is_iterable($technicians ?? null) ? $technicians : [];
    $customerDevices = is_iterable($customerDevices ?? null) ? $customerDevices : [];
    $devices = is_iterable($devices ?? null) ? $devices : [];
    $parts = is_iterable($parts ?? null) ? $parts : [];
    $services = is_iterable($services ?? null) ? $services : [];

    $jobDevices = is_iterable($jobDevices ?? null) ? $jobDevices : [];
    $jobItems = is_iterable($jobItems ?? null) ? $jobItems : [];

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
    <x-ui.page-hero
        :back-href="route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs'"
        icon-class="bi bi-briefcase-fill"
        :title="e($isEdit ? __('Edit Job') : __('Create Job'))"
        :subtitle="e($isEdit ? __('Update job details.') : __('Enter job details and create a new job.'))"
    >
        <x-slot:actions>
            <a href="{{ route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs' }}" class="btn btn-save-review">
                <i class="bi bi-check2-circle me-2"></i>{{ __('Back to List') }}
            </a>
        </x-slot:actions>
    </x-ui.page-hero>

    @include('tenant.partials.quick_create_customer_modal')
    @include('tenant.partials.quick_create_technician_modal')

    <form id="jobForm" method="POST" action="{{ $isEdit ? route('tenant.jobs.update', ['business' => $tenantSlug, 'jobId' => $jobId]) : route('tenant.jobs.store', ['business' => $tenantSlug]) }}" enctype="multipart/form-data">
        @csrf

        @if ($isEdit)
            @method('PUT')
        @endif

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
                                <input type="text" name="case_number" class="form-control" value="{{ old('case_number', $isEdit ? ($job?->case_number ?? '') : '') }}" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Title') }}</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $isEdit ? ($job?->title ?? '') : '') }}" placeholder="{{ __('Optional') }}" />
                                @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customer_id" class="form-select">
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" {{ (string) old('customer_id', $isEdit ? (string) ($job?->customer_id ?? '') : '') === (string) $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}
                                                <!-- @if (!empty($c->email)) ({{ $c->email }}) @endif -->
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
                                @php
                                    $techOld = (array) old('technician_ids', $isEdit ? ($job?->technicians?->pluck('id')->all() ?? []) : []);
                                    $techOld = array_map('strval', $techOld);
                                @endphp
                                <div class="input-group">
                                    <select name="technician_ids[]" id="technician_ids" class="form-select" multiple>
                                        @foreach ($technicians as $t)
                                            <option value="{{ $t->id }}" {{ in_array((string) $t->id, $techOld, true) ? 'selected' : '' }}>
                                                {{ $t->name }}
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
                                <input type="date" name="pickup_date" class="form-control" value="{{ old('pickup_date', $isEdit ? ($job?->pickup_date ?? '') : '') }}" />
                                @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Delivery Date') }}</label>
                                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $isEdit ? ($job?->delivery_date ?? '') : '') }}" />
                                @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Next Service Date') }}</label>
                                <input type="date" name="next_service_date" class="form-control" value="{{ old('next_service_date', $isEdit ? ($job?->next_service_date ?? '') : '') }}" />
                                @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('Job Details') }}</label>
                                <textarea name="case_detail" class="form-control" rows="4" placeholder="{{ __('Enter details about job.') }}">{{ old('case_detail', $isEdit ? ($job?->case_detail ?? '') : '') }}</textarea>
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
                                        $oldDevIds = (array) old('job_device_customer_device_id', $isEdit ? collect($jobDevices)->pluck('customer_device_id')->all() : []);
                                        $oldDevSerials = (array) old('job_device_serial', $isEdit ? collect($jobDevices)->pluck('serial_snapshot')->all() : []);
                                        $oldDevPins = (array) old('job_device_pin', $isEdit ? collect($jobDevices)->pluck('pin_snapshot')->all() : []);
                                        $oldDevNotes = (array) old('job_device_notes', $isEdit ? collect($jobDevices)->pluck('notes_snapshot')->all() : []);
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
                                    @php $statusOld = old('status_slug', $isEdit ? (string) ($job?->status_slug ?? 'neworder') : 'neworder'); @endphp
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
                                    @php $payOld = old('payment_status_slug', $isEdit ? (string) ($job?->payment_status_slug ?? 'nostatus') : 'nostatus'); @endphp
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
                                    @php $p = old('priority', $isEdit ? (string) ($job?->priority ?? 'normal') : 'normal'); @endphp
                                    <select name="priority" class="form-select">
                                        <option value="normal" {{ $p === 'normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                                        <option value="high" {{ $p === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                        <option value="urgent" {{ $p === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                                    </select>
                                    @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Tax Mode') }}</label>
                                    @php $taxMode = old('prices_inclu_exclu', $isEdit ? (string) ($job?->prices_inclu_exclu ?? '') : ''); @endphp
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
                                        {{ $isEdit ? __('Update Job') : __('Create Job') }}
                                    </button>
                                    <div class="text-muted small mt-2">{{ $isEdit ? __('After updating, you will be redirected to the job page.') : __('After creating, you will be redirected to the job page.') }}</div>
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

@push('page-scripts')
<script>
@php
    $oldTypes = (array) old('item_type', []);
    $oldNames = (array) old('item_name', []);
    $oldQtys = (array) old('item_qty', []);
    $oldPrices = (array) old('item_unit_price_cents', []);
    $oldMax = max(count($oldTypes), count($oldNames), count($oldQtys), count($oldPrices));

    $seedItems = [];
    if ($oldMax > 0) {
        for ($i = 0; $i < $oldMax; $i++) {
            $t = is_string($oldTypes[$i] ?? null) ? trim((string) $oldTypes[$i]) : '';
            $n = is_string($oldNames[$i] ?? null) ? trim((string) $oldNames[$i]) : '';
            if ($t === '' || $n === '') {
                continue;
            }
            $q = is_numeric($oldQtys[$i] ?? null) ? (int) $oldQtys[$i] : 1;
            $p = is_numeric($oldPrices[$i] ?? null) ? (int) $oldPrices[$i] : 0;
            $seedItems[] = ['item_type' => $t, 'name_snapshot' => $n, 'qty' => $q, 'unit_price_amount_cents' => $p];
        }
    } elseif ($isEdit) {
        $seedItems = collect($jobItems)
            ->map(fn ($it) => [
                'item_type' => $it->item_type,
                'name_snapshot' => $it->name_snapshot,
                'qty' => $it->qty,
                'unit_price_amount_cents' => $it->unit_price_amount_cents,
                'meta_json' => $it->meta_json,
            ])
            ->values()
            ->all();
    }

    $initialPartRows = [];
    $initialServiceRows = [];
    $initialOtherRows = [];

    foreach ($seedItems as $it) {
        $type = (string) ($it['item_type'] ?? '');
        $meta = is_array($it['meta_json'] ?? null) ? (array) $it['meta_json'] : [];
        $row = [
            'id' => uniqid($type . '-', true),
            'name' => (string) ($it['name_snapshot'] ?? ''),
            'code' => is_string($meta['code'] ?? null) ? (string) $meta['code'] : '',
            'capacity' => is_string($meta['capacity'] ?? null) ? (string) $meta['capacity'] : '',
            'device_id' => is_string($meta['device_id'] ?? null) ? (string) $meta['device_id'] : '',
            'device' => is_string($meta['device_label'] ?? null) ? (string) $meta['device_label'] : '',
            'qty' => (string) ((int) ($it['qty'] ?? 1)),
            'price' => (string) ((int) ($it['unit_price_amount_cents'] ?? 0)),
        ];

        if ($type === 'part') {
            $initialPartRows[] = $row;
        } elseif ($type === 'service') {
            $initialServiceRows[] = $row;
        } else {
            $initialOtherRows[] = $row;
        }
    }
@endphp
window.RBJobCreateConfig = {
    enablePinCodeField: @json($enablePinCodeField),
    deviceLabelMap: {
        @foreach ($devices as $d)
            @php
                $brand = is_string($d->brand?->name ?? null) ? (string) $d->brand?->name : '';
                $model = is_string($d->model ?? null) ? (string) $d->model : '';
                $text = trim(trim($brand . ' ' . $model));
                if ($text === '') {
                    $text = __('Device') . ' #' . $d->id;
                }
            @endphp
            @json((string) $d->id): @json($text),
        @endforeach
    },
    quickCustomerCreateUrl: @json(route('tenant.operations.clients.store', ['business' => $tenantSlug])),
    quickTechnicianCreateUrl: @json(route('tenant.technicians.store', ['business' => $tenantSlug])),
    initialPartRows: @json($initialPartRows),
    initialServiceRows: @json($initialServiceRows),
    initialOtherRows: @json($initialOtherRows),
    translations: {
        select: @json(__('Select...')),
        selectDevice: @json(__('Select Device')),
        noPartsSelected: @json(__('No parts selected yet.')),
        noServicesSelected: @json(__('No services selected yet.')),
        noOtherItems: @json(__('No other items added yet.')),
        device: @json(__('Device')),
        public: @json(__('Public')),
        private: @json(__('Private'))
    }
};
</script>
<script src="{{ asset('repairbuddy/my_account/js/job_create.js') }}"></script>
@endpush

@endsection

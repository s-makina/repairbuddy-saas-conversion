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
        /* Vertical Stepper Styles */
        .job-stepper-container {
            display: flex;
            gap: 2rem;
            min-height: calc(100vh - 280px);
        }

        .stepper-sidebar {
            width: 280px;
            flex-shrink: 0;
        }

        .stepper-content {
            flex: 1;
            min-width: 0;
        }

        .stepper-nav {
            position: sticky;
            top: 1rem;
        }

        .stepper-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            border: 2px solid transparent;
        }

        .stepper-item:hover {
            background: rgba(var(--bs-primary-rgb), 0.05);
        }

        .stepper-item.active {
            background: rgba(var(--bs-primary-rgb), 0.1);
            border-color: var(--bs-primary);
        }

        .stepper-item.completed {
            background: rgba(25, 135, 84, 0.08);
        }

        .stepper-item.completed .stepper-icon {
            background: var(--bs-success);
            border-color: var(--bs-success);
            color: white;
        }

        .stepper-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            background: var(--bs-gray-200);
            border: 2px solid var(--bs-gray-300);
            color: var(--bs-gray-600);
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .stepper-item.active .stepper-icon {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
        }

        .stepper-info {
            margin-left: 1rem;
            flex: 1;
        }

        .stepper-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--bs-gray-800);
            margin-bottom: 0.125rem;
        }

        .stepper-desc {
            font-size: 0.8rem;
            color: var(--bs-gray-500);
        }

        .stepper-item.completed .stepper-title {
            color: var(--bs-success);
        }

        /* Step Content Panels */
        .step-panel {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .step-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .step-card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--bs-border-color);
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.03) 0%, rgba(var(--bs-primary-rgb), 0.01) 100%);
        }

        .step-card-header h4 {
            margin: 0;
            font-weight: 600;
            color: var(--bs-gray-800);
        }

        .step-card-header p {
            margin: 0.5rem 0 0;
            color: var(--bs-gray-500);
            font-size: 0.9rem;
        }

        .step-card-body {
            padding: 2rem;
        }

        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: var(--bs-gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--bs-gray-300);
            padding: 0.625rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.15);
        }

        /* Navigation Buttons */
        .step-navigation {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--bs-border-color);
            background: var(--bs-gray-50);
        }

        .btn-step {
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Select2 Styling */
        #customer_id + .select2-container--bootstrap-5 .select2-selection,
        #technician_ids + .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid var(--bs-gray-300);
            border-radius: 8px;
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
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.15);
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
            border-radius: 0 8px 8px 0;
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

        /* Tables in steps */
        .step-table {
            margin-top: 1rem;
        }

        .step-table th {
            background: var(--bs-gray-50);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: var(--bs-gray-600);
        }

        .step-table td, .step-table th {
            vertical-align: middle;
            padding: 0.875rem 1rem;
        }

        /* Progress bar */
        .stepper-progress {
            height: 4px;
            background: var(--bs-gray-200);
            border-radius: 2px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .stepper-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-success) 100%);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .job-stepper-container {
                flex-direction: column;
            }

            .stepper-sidebar {
                width: 100%;
            }

            .stepper-nav {
                position: relative;
                top: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }

            .stepper-item {
                flex: 1;
                min-width: 140px;
                padding: 0.75rem;
            }

            .stepper-info {
                margin-left: 0.5rem;
            }

            .stepper-icon {
                width: 36px;
                height: 36px;
                font-size: 0.875rem;
            }

            .stepper-desc {
                display: none;
            }
        }

        @media (max-width: 575.98px) {
            .stepper-item {
                min-width: auto;
                flex-direction: column;
                text-align: center;
            }

            .stepper-info {
                margin-left: 0;
                margin-top: 0.5rem;
            }

            .step-card-body {
                padding: 1.25rem;
            }

            .step-navigation {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
    </style>
@endpush

<main class="dashboard-content container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">{{ $isEdit ? __('Edit Job') : __('Create New Job') }}</h4>
            <p class="text-muted mb-0">{{ $isEdit ? __('Update job details and information.') : __('Fill in the details below to create a new repair job.') }}</p>
        </div>
        <a href="{{ route('tenant.dashboard', ['business' => $tenantSlug]) . '?screen=jobs' }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Jobs') }}
        </a>
    </div>

    @include('tenant.partials.quick_create_customer_modal')
    @include('tenant.partials.quick_create_technician_modal')

    <form id="jobForm" method="POST" action="{{ $isEdit ? route('tenant.jobs.update', ['business' => $tenantSlug, 'jobId' => $jobId]) : route('tenant.jobs.store', ['business' => $tenantSlug]) }}" enctype="multipart/form-data">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="job-stepper-container">
            <!-- Stepper Sidebar -->
            <aside class="stepper-sidebar">
                <nav class="stepper-nav">
                    <div class="stepper-progress">
                        <div class="stepper-progress-bar" id="stepperProgress" style="width: 25%"></div>
                    </div>

                    <div class="stepper-item active" data-step="1" onclick="goToStep(1)">
                        <div class="stepper-icon">
                            <i class="bi bi-clipboard-data"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Job Details') }}</div>
                            <div class="stepper-desc">{{ __('Basic information') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" data-step="2" onclick="goToStep(2)">
                        <div class="stepper-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Devices') }}</div>
                            <div class="stepper-desc">{{ __('Add repair devices') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" data-step="3" onclick="goToStep(3)">
                        <div class="stepper-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Parts & Services') }}</div>
                            <div class="stepper-desc">{{ __('Items and costs') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" data-step="4" onclick="goToStep(4)">
                        <div class="stepper-icon">
                            <i class="bi bi-gear"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Settings & Review') }}</div>
                            <div class="stepper-desc">{{ __('Finalize job') }}</div>
                        </div>
                    </div>
                </nav>
            </aside>

            <!-- Stepper Content -->
            <div class="stepper-content">
                <!-- Step 1: Job Details -->
                <div class="step-panel active" id="step1">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-clipboard-data me-2"></i>{{ __('Job Details') }}</h4>
                            <p>{{ __('Enter the basic information for this repair job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Case Number') }}</label>
                                    <input type="text" name="case_number" class="form-control" value="{{ old('case_number', $isEdit ? ($job?->case_number ?? '') : '') }}" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                    <div class="form-text">{{ __('Auto-generated if left empty') }}</div>
                                    @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Title') }}</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title', $isEdit ? ($job?->title ?? '') : '') }}" placeholder="{{ __('e.g., iPhone 14 Screen Repair') }}" />
                                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="customer_id">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select name="customer_id" id="customer_id" class="form-select" required>
                                            <option value="">{{ __('Select customer...') }}</option>
                                            @foreach ($customers as $c)
                                                <option value="{{ $c->id }}" {{ (string) old('customer_id', $isEdit ? (string) ($job?->customer_id ?? '') : '') === (string) $c->id ? 'selected' : '' }}>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-primary" id="rb_open_quick_customer" title="{{ __('Add new customer') }}">
                                            <i class="bi bi-person-plus"></i>
                                        </button>
                                    </div>
                                    @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="technician_ids">{{ __('Assigned Technicians') }}</label>
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
                                        <button type="button" class="btn btn-primary" id="rb_open_quick_technician" title="{{ __('Add technician') }}">
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
                                    <label class="form-label">{{ __('Job Description') }}</label>
                                    <textarea name="case_detail" class="form-control" rows="4" placeholder="{{ __('Describe the repair issue, customer notes, or any relevant details...') }}">{{ old('case_detail', $isEdit ? ($job?->case_detail ?? '') : '') }}</textarea>
                                    @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <div></div>
                            <button type="button" class="btn btn-primary btn-step" onclick="nextStep()">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Devices -->
                <div class="step-panel" id="step2">
                    @php
                        $oldDevIds = (array) old('job_device_customer_device_id', $isEdit ? collect($jobDevices)->pluck('customer_device_id')->all() : []);
                        $oldDevSerials = (array) old('job_device_serial', $isEdit ? collect($jobDevices)->pluck('serial_snapshot')->all() : []);
                        $oldDevPins = (array) old('job_device_pin', $isEdit ? collect($jobDevices)->pluck('pin_snapshot')->all() : []);
                        $oldDevNotes = (array) old('job_device_notes', $isEdit ? collect($jobDevices)->pluck('notes_snapshot')->all() : []);
                        $devRows = max(count($oldDevIds), count($oldDevSerials), count($oldDevPins), count($oldDevNotes));

                        $seedDevices = [];
                        for ($i = 0; $i < $devRows; $i++) {
                            $seedDevices[] = [
                                'device_id' => (string) ($oldDevIds[$i] ?? ''),
                                'serial' => is_string($oldDevSerials[$i] ?? null) ? (string) $oldDevSerials[$i] : '',
                                'pin' => is_string($oldDevPins[$i] ?? null) ? (string) $oldDevPins[$i] : '',
                                'notes' => is_string($oldDevNotes[$i] ?? null) ? (string) $oldDevNotes[$i] : '',
                            ];
                        }
                    @endphp

                    <div class="step-card" x-data="window.RBJobDevicesExtras.devices(@json($seedDevices), @json($enablePinCodeField))" x-init="window.rbDevices = $data">
                        <div class="step-card-header">
                            <h4><i class="bi bi-phone me-2"></i>{{ __('Devices') }}</h4>
                            <p>{{ __('Add the devices that need to be repaired.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <div class="d-flex justify-content-end mb-3">
                                <button type="button" class="btn btn-success" id="addDeviceLine" @click="openAdd()">
                                    <i class="bi bi-plus-circle me-1"></i>{{ __('Add Device') }}
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table step-table" id="devicesTable" data-alpine-managed="1">
                                    <thead>
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
                                        <template x-if="rows.length === 0">
                                            <tr class="devices-empty-row">
                                                <td colspan="{{ $enablePinCodeField ? 5 : 4 }}" class="text-center text-muted py-5">
                                                    <i class="bi bi-phone display-4 d-block mb-2 opacity-50"></i>
                                                    {{ __('No devices added yet. Click "Add Device" to get started.') }}
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-for="(row, idx) in rows" :key="idx">
                                            <tr>
                                                <td class="device-label" :data-value="row.device_id" x-text="deviceLabel(row.device_id)"></td>
                                                <td class="device-imei" :data-value="row.serial" x-text="row.serial && row.serial.trim() !== '' ? row.serial : '—'"></td>
                                                @if ($enablePinCodeField)
                                                    <td class="device-password" :data-value="row.pin" x-text="row.pin && row.pin.trim() !== '' ? row.pin : '—'"></td>
                                                @endif
                                                <td class="device-note" :data-value="row.notes"><span class="d-inline-block text-truncate" style="max-width: 420px;" x-text="row.notes && row.notes.trim() !== '' ? row.notes : '—'" :title="row.notes"></span></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-outline-primary btn-sm me-1" @click="openEdit(idx)" aria-label="{{ __('Edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="remove(idx)" aria-label="{{ __('Remove') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                    <input type="hidden" name="job_device_customer_device_id[]" :value="row.device_id" />
                                                    <input type="hidden" name="job_device_serial[]" :value="row.serial" />
                                                    @if ($enablePinCodeField)
                                                        <input type="hidden" name="job_device_pin[]" :value="row.pin" />
                                                    @endif
                                                    <input type="hidden" name="job_device_notes[]" :value="row.notes" />
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            @error('job_device_customer_device_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-step" onclick="nextStep()">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Parts & Services -->
                <div class="step-panel" id="step3">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-box-seam me-2"></i>{{ __('Parts & Services') }}</h4>
                            <p>{{ __('Add parts, services, and other items for this job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <!-- Parts Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-cpu me-2"></i>{{ __('Parts') }}</h6>
                                </div>
                                <div id="devicePartsSelects" class="row g-2"></div>
                                <select id="parts_select" class="form-select d-none" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('Search and select...') }}</option>
                                    @foreach ($parts as $p)
                                        <option value="{{ $p->name }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <div class="table-responsive">
                                    <table class="table step-table" id="partsTable">
                                        <thead>
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
                                                <td colspan="8" class="text-center text-muted py-4">{{ __('No parts selected yet.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Services Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-wrench-adjustable-circle me-2"></i>{{ __('Services') }}</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addServiceLineBtn">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Service') }}
                                    </button>
                                </div>
                                <div id="deviceServicesSelects" class="row g-2"></div>
                                <select id="services_select" class="form-select d-none" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('Search and select...') }}</option>
                                    @foreach ($services as $s)
                                        <option value="{{ $s->name }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <div class="table-responsive">
                                    <table class="table step-table" id="servicesTable">
                                        <thead>
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

                            <!-- Other Items Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>{{ __('Other Items') }}</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addOtherItemLineBtn">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Item') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table" id="otherItemsTable">
                                        <thead>
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
                                                <td colspan="7" class="text-center text-muted py-4">{{ __('No other items added yet.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-step" onclick="nextStep()">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Settings & Review -->
                <div class="step-panel" id="step4">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-gear me-2"></i>{{ __('Settings & Review') }}</h4>
                            <p>{{ __('Set status, payment, and other options. Review and submit your job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            @php
                                $oldExDates = (array) old('extra_item_occurred_at', []);
                                $oldExLabels = (array) old('extra_item_label', []);
                                $oldExData = (array) old('extra_item_data_text', []);
                                $oldExDesc = (array) old('extra_item_description', []);
                                $oldExVis = (array) old('extra_item_visibility', []);
                                $exRows = max(count($oldExLabels), count($oldExData), count($oldExDesc), count($oldExVis), count($oldExDates));

                                $seedExtras = [];
                                for ($i = 0; $i < $exRows; $i++) {
                                    $seedExtras[] = [
                                        'occurred_at' => is_string($oldExDates[$i] ?? null) ? (string) $oldExDates[$i] : '',
                                        'label' => is_string($oldExLabels[$i] ?? null) ? (string) $oldExLabels[$i] : '',
                                        'data_text' => is_string($oldExData[$i] ?? null) ? (string) $oldExData[$i] : '',
                                        'description' => is_string($oldExDesc[$i] ?? null) ? (string) $oldExDesc[$i] : '',
                                        'visibility' => is_string($oldExVis[$i] ?? null) ? (string) $oldExVis[$i] : 'private',
                                    ];
                                }
                            @endphp

                            <!-- Extra Fields & Files Section -->
                            <div class="mb-4" x-data="window.RBJobDevicesExtras.extras(@json($seedExtras))" x-init="window.rbExtras = $data">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>{{ __('Extra Fields & Files') }}</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addExtraLine" @click="openAdd()">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Field') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table" id="extraTable" data-alpine-managed="1">
                                        <thead>
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
                                            <template x-if="rows.length === 0">
                                                <tr class="extras-empty-row">
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        {{ __('No extra fields added yet.') }}
                                                    </td>
                                                </tr>
                                            </template>

                                            <template x-for="(row, idx) in rows" :key="idx">
                                                <tr>
                                                    <td class="extra-date" :data-value="row.occurred_at" x-text="row.occurred_at && row.occurred_at.trim() !== '' ? row.occurred_at : '—'"></td>
                                                    <td class="extra-label" :data-value="row.label" x-text="row.label && row.label.trim() !== '' ? row.label : '—'"></td>
                                                    <td class="extra-data" :data-value="row.data_text" :data-desc="row.description">
                                                        <span class="d-inline-block text-truncate" style="max-width: 420px;" x-text="row.data_text && row.data_text.trim() !== '' ? row.data_text : '—'" :title="row.data_text"></span>
                                                    </td>
                                                    <td class="extra-vis" :data-value="row.visibility" x-text="row.visibility === 'public' ? '{{ __('Public') }}' : '{{ __('Private') }}'"></td>
                                                    <td class="extra-file" :data-value="row.file_name" x-text="row.file_name && row.file_name.trim() !== '' ? row.file_name : '—'"></td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-outline-primary btn-sm me-1" @click="openEdit(idx)" aria-label="{{ __('Edit') }}">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="remove(idx)" aria-label="{{ __('Remove') }}">
                                                            <i class="bi bi-trash"></i>
                                                        </button>

                                                        <input type="hidden" name="extra_item_occurred_at[]" :value="row.occurred_at" />
                                                        <input type="hidden" name="extra_item_label[]" :value="row.label" />
                                                        <input type="hidden" name="extra_item_data_text[]" :value="row.data_text" />
                                                        <input type="hidden" name="extra_item_description[]" :value="row.description" />
                                                        <input type="hidden" name="extra_item_visibility[]" :value="row.visibility" />
                                                        <input type="file" name="extra_item_file[]" class="d-none" :ref="'file_' + idx" />
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                @error('extra_item_label')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            </div>

                            <!-- Order Settings -->
                            <div class="row g-3">
                                <div class="col-md-6">
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

                                <div class="col-md-6">
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

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Priority') }}</label>
                                    @php $p = old('priority', $isEdit ? (string) ($job?->priority ?? 'normal') : 'normal'); @endphp
                                    <select name="priority" class="form-select">
                                        <option value="normal" {{ $p === 'normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                                        <option value="high" {{ $p === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                        <option value="urgent" {{ $p === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                                    </select>
                                    @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
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
                                    <textarea name="wc_order_note" class="form-control" rows="3" placeholder="{{ __('Notes visible to customer.') }}">{{ old('wc_order_note') }}</textarea>
                                    @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('File Attachment') }}</label>
                                    <input type="file" name="job_file" class="form-control" />
                                    @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="submit" class="btn btn-success btn-step">
                                <i class="bi bi-check2-circle"></i> {{ $isEdit ? __('Update Job') : __('Create Job') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Device Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Device') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="device_modal_device">{{ __('Device') }}</label>
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

                        <div class="col-md-6">
                            <label class="form-label" for="device_modal_imei">{{ __('Device ID/IMEI') }}</label>
                            <input type="text" id="device_modal_imei" class="form-control" />
                        </div>

                        @if ($enablePinCodeField)
                            <div class="col-md-6">
                                <label class="form-label" for="device_modal_password">{{ __('Pin Code/Password') }}</label>
                                <input type="text" id="device_modal_password" class="form-control" />
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label" for="device_modal_note">{{ __('Device Note') }}</label>
                            <textarea id="device_modal_note" class="form-control" rows="3"></textarea>
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

    <!-- Extra Field Modal -->
    <div class="modal fade" id="extraModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Extra Field / File') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="extra_modal_date">{{ __('Date') }}</label>
                            <input type="date" id="extra_modal_date" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="extra_modal_vis">{{ __('Visibility') }}</label>
                            <select id="extra_modal_vis" class="form-select">
                                <option value="public">{{ __('Customer & Staff') }}</option>
                                <option value="private">{{ __('Staff Only') }}</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="extra_modal_label">{{ __('Label') }}</label>
                            <input type="text" id="extra_modal_label" class="form-control" placeholder="{{ __('e.g., Diagnosis Result') }}" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="extra_modal_data">{{ __('Data') }}</label>
                            <input type="text" id="extra_modal_data" class="form-control" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="extra_modal_desc">{{ __('Description') }}</label>
                            <textarea id="extra_modal_desc" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="extra_modal_file">{{ __('File') }}</label>
                            <input type="file" id="extra_modal_file" class="form-control" />
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

window.RBJobDevicesExtras = window.RBJobDevicesExtras || {
    devices: function (seedRows, enablePinCodeField) {
        return {
            rows: Array.isArray(seedRows) ? seedRows : [],
            enablePinCodeField: !!enablePinCodeField,
            editingIndex: null,
            modal: null,
            openAdd: function () {
                this.editingIndex = null;
                this.setModalValues({ device_id: '', serial: '', pin: '', notes: '' });
                this.showModal();
            },
            openEdit: function (idx) {
                this.editingIndex = idx;
                var r = this.rows[idx] || { device_id: '', serial: '', pin: '', notes: '' };
                this.setModalValues(r);
                this.showModal();
            },
            remove: function (idx) {
                this.rows.splice(idx, 1);
                this.notifyDevicesChanged();
            },
            deviceLabel: function (deviceId) {
                var id = deviceId != null ? String(deviceId) : '';
                var map = (window.RBJobCreateConfig && window.RBJobCreateConfig.deviceLabelMap) ? window.RBJobCreateConfig.deviceLabelMap : {};
                var t = id !== '' && map && map[id] ? String(map[id]) : '';
                return t !== '' ? t : '—';
            },
            ensureModal: function () {
                if (this.modal) return this.modal;
                var el = document.getElementById('deviceModal');
                if (!el || !window.bootstrap || !window.bootstrap.Modal) return null;
                this.modal = new window.bootstrap.Modal(el);
                return this.modal;
            },
            initModalSelect2: function () {
                var modalEl = document.getElementById('deviceModal');
                var sel = document.getElementById('device_modal_device');
                if (!modalEl || !sel) return;
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') return;
                var $sel = window.jQuery(sel);
                if ($sel.hasClass('select2-hidden-accessible')) return;
                $sel.select2({
                    width: '100%',
                    theme: 'bootstrap-5',
                    dropdownParent: window.jQuery(modalEl),
                    placeholder: (window.RBJobCreateConfig && window.RBJobCreateConfig.translations && window.RBJobCreateConfig.translations.selectDevice) ? window.RBJobCreateConfig.translations.selectDevice : 'Select Device',
                    allowClear: true
                });
            },
            setModalValues: function (values) {
                var sel = document.getElementById('device_modal_device');
                var imei = document.getElementById('device_modal_imei');
                var note = document.getElementById('device_modal_note');
                var pin = document.getElementById('device_modal_password');

                var devId = values && values.device_id != null ? String(values.device_id) : '';
                if (sel) {
                    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                        window.jQuery(sel).val(devId).trigger('change');
                    } else {
                        sel.value = devId;
                    }
                }
                if (imei) imei.value = values && values.serial != null ? String(values.serial) : '';
                if (note) note.value = values && values.notes != null ? String(values.notes) : '';
                if (pin) pin.value = values && values.pin != null ? String(values.pin) : '';
            },
            readModalValues: function () {
                var sel = document.getElementById('device_modal_device');
                var imei = document.getElementById('device_modal_imei');
                var note = document.getElementById('device_modal_note');
                var pin = document.getElementById('device_modal_password');

                var devId = '';
                if (sel) {
                    devId = (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') ? (window.jQuery(sel).val() || '') : (sel.value || '');
                }
                return {
                    device_id: devId,
                    serial: imei ? imei.value : '',
                    notes: note ? note.value : '',
                    pin: pin ? pin.value : ''
                };
            },
            showModal: function () {
                this.initModalSelect2();
                var m = this.ensureModal();
                if (m) m.show();
            },
            hideModal: function () {
                var m = this.ensureModal();
                if (m) m.hide();
            },
            saveFromModal: function () {
                var values = this.readModalValues();
                if (this.editingIndex === null || this.editingIndex === undefined) {
                    this.rows.push(values);
                } else {
                    this.rows[this.editingIndex] = values;
                }
                this.editingIndex = null;
                this.hideModal();
                this.notifyDevicesChanged();
            },
            notifyDevicesChanged: function () {
                try {
                    document.dispatchEvent(new CustomEvent('rb:devices-changed'));
                } catch (e) {
                }
            }
        };
    },
    extras: function (seedRows) {
        return {
            rows: (Array.isArray(seedRows) ? seedRows : []).map(function (r) {
                return {
                    occurred_at: r && r.occurred_at != null ? String(r.occurred_at) : '',
                    label: r && r.label != null ? String(r.label) : '',
                    data_text: r && r.data_text != null ? String(r.data_text) : '',
                    description: r && r.description != null ? String(r.description) : '',
                    visibility: r && r.visibility != null ? String(r.visibility) : 'private',
                    file_name: ''
                };
            }),
            editingIndex: null,
            modal: null,
            openAdd: function () {
                this.editingIndex = null;
                this.setModalValues({ occurred_at: '', label: '', data_text: '', description: '', visibility: 'private' });
                this.showModal();
            },
            openEdit: function (idx) {
                this.editingIndex = idx;
                var r = this.rows[idx] || { occurred_at: '', label: '', data_text: '', description: '', visibility: 'private', file_name: '' };
                this.setModalValues(r);
                this.showModal();
            },
            remove: function (idx) {
                this.rows.splice(idx, 1);
            },
            ensureModal: function () {
                if (this.modal) return this.modal;
                var el = document.getElementById('extraModal');
                if (!el || !window.bootstrap || !window.bootstrap.Modal) return null;
                this.modal = new window.bootstrap.Modal(el);
                return this.modal;
            },
            setModalValues: function (values) {
                var d = document.getElementById('extra_modal_date');
                var l = document.getElementById('extra_modal_label');
                var data = document.getElementById('extra_modal_data');
                var desc = document.getElementById('extra_modal_desc');
                var vis = document.getElementById('extra_modal_vis');
                var file = document.getElementById('extra_modal_file');
                if (d) d.value = values && values.occurred_at != null ? String(values.occurred_at) : '';
                if (l) l.value = values && values.label != null ? String(values.label) : '';
                if (data) data.value = values && values.data_text != null ? String(values.data_text) : '';
                if (desc) desc.value = values && values.description != null ? String(values.description) : '';
                if (vis) vis.value = values && values.visibility != null ? String(values.visibility) : 'private';
                if (file) file.value = '';
            },
            readModalValues: function () {
                var d = document.getElementById('extra_modal_date');
                var l = document.getElementById('extra_modal_label');
                var data = document.getElementById('extra_modal_data');
                var desc = document.getElementById('extra_modal_desc');
                var vis = document.getElementById('extra_modal_vis');
                var file = document.getElementById('extra_modal_file');
                return {
                    occurred_at: d ? d.value : '',
                    label: l ? l.value : '',
                    data_text: data ? data.value : '',
                    description: desc ? desc.value : '',
                    visibility: vis ? vis.value : 'private',
                    file: file
                };
            },
            showModal: function () {
                var m = this.ensureModal();
                if (m) m.show();
            },
            hideModal: function () {
                var m = this.ensureModal();
                if (m) m.hide();
            },
            saveFromModal: function () {
                var values = this.readModalValues();
                var fileInput = values.file;
                var fileName = '';
                var selectedFile = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (selectedFile) {
                    fileName = selectedFile.name || '';
                }

                if (this.editingIndex === null || this.editingIndex === undefined) {
                    this.rows.push({
                        occurred_at: values.occurred_at,
                        label: values.label,
                        data_text: values.data_text,
                        description: values.description,
                        visibility: values.visibility,
                        file_name: fileName
                    });
                    this.editingIndex = this.rows.length - 1;
                } else {
                    this.rows[this.editingIndex] = {
                        occurred_at: values.occurred_at,
                        label: values.label,
                        data_text: values.data_text,
                        description: values.description,
                        visibility: values.visibility,
                        file_name: fileName || (this.rows[this.editingIndex] ? this.rows[this.editingIndex].file_name : '')
                    };
                }

                if (selectedFile) {
                    try {
                        var refName = 'file_' + this.editingIndex;
                        var target = this.$refs && this.$refs[refName] ? this.$refs[refName] : null;
                        if (target) {
                            try { target.files = fileInput.files; } catch (e) { }
                        }
                    } catch (e) {
                    }
                }

                this.editingIndex = null;
                this.hideModal();
            }
        };
    }
};

// Stepper Navigation Functions
var currentStep = 1;
var totalSteps = 4;

function goToStep(step) {
    if (step < 1 || step > totalSteps) return;
    
    // Update current step
    currentStep = step;
    
    // Update stepper items
    document.querySelectorAll('.stepper-item').forEach(function(item) {
        var itemStep = parseInt(item.getAttribute('data-step'), 10);
        item.classList.remove('active');
        if (itemStep < step) {
            item.classList.add('completed');
        } else {
            item.classList.remove('completed');
        }
        if (itemStep === step) {
            item.classList.add('active');
        }
    });
    
    // Update step panels
    document.querySelectorAll('.step-panel').forEach(function(panel) {
        panel.classList.remove('active');
    });
    var activePanel = document.getElementById('step' + step);
    if (activePanel) {
        activePanel.classList.add('active');
    }
    
    // Update progress bar
    var progressBar = document.getElementById('stepperProgress');
    if (progressBar) {
        var progress = (step / totalSteps) * 100;
        progressBar.style.width = progress + '%';
    }
    
    // Scroll to top of form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep() {
    if (currentStep < totalSteps) {
        goToStep(currentStep + 1);
    }
}

function prevStep() {
    if (currentStep > 1) {
        goToStep(currentStep - 1);
    }
}

document.addEventListener('click', function (e) {
    var deviceSave = e.target && e.target.id === 'deviceModalSave' ? e.target : (e.target ? e.target.closest('#deviceModalSave') : null);
    if (deviceSave && window.rbDevices && typeof window.rbDevices.saveFromModal === 'function') {
        e.preventDefault();
        window.rbDevices.saveFromModal();
        return;
    }

    var extraSave = e.target && e.target.id === 'extraModalSave' ? e.target : (e.target ? e.target.closest('#extraModalSave') : null);
    if (extraSave && window.rbExtras && typeof window.rbExtras.saveFromModal === 'function') {
        e.preventDefault();
        window.rbExtras.saveFromModal();
        return;
    }
});
</script>
<script src="{{ asset('repairbuddy/my_account/js/job_create.js') }}"></script>
@endpush

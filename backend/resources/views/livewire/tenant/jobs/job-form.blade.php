@props([
    'tenant',
    'customers' => [],
    'technicians' => [],
    'customerDevices' => [],
    'jobStatuses' => [],
    'paymentStatuses' => [],
])

@push('page-styles')
<style>
    /* Vertical Stepper Layout */
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

    .stepper-progress {
        height: 4px;
        background: #e9ecef;
        border-radius: 2px;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .stepper-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #10b981 100%);
        border-radius: 2px;
        transition: width 0.3s ease;
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
        background: rgba(102, 126, 234, 0.05);
    }

    .stepper-item.active {
        background: rgba(102, 126, 234, 0.1);
        border-color: #667eea;
    }

    .stepper-item.completed {
        background: rgba(16, 185, 129, 0.08);
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
        background: #e9ecef;
        border: 2px solid #dee2e6;
        color: #6c757d;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .stepper-item.active .stepper-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .stepper-item.completed .stepper-icon {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }

    .stepper-info {
        margin-left: 1rem;
        flex: 1;
    }

    .stepper-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #1a1a2e;
        margin-bottom: 0.125rem;
    }

    .stepper-desc {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .stepper-item.completed .stepper-title {
        color: #10b981;
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
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .step-card-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(102, 126, 234, 0.01) 100%);
    }

    .step-card-header h4 {
        margin: 0;
        font-weight: 600;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .step-card-header p {
        margin: 0.5rem 0 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .step-card-body {
        padding: 2rem;
    }

    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.625rem 1rem;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    /* Navigation Buttons */
    .step-navigation {
        display: flex;
        justify-content: space-between;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e9ecef;
        background: #f8fafc;
    }

    .btn-step {
        padding: 0.75rem 1.75rem;
        border-radius: 8px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Tables in steps */
    .step-table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: #64748b;
    }

    .step-table td, .step-table th {
        vertical-align: middle;
        padding: 0.875rem 1rem;
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

        .stepper-progress {
            display: none;
        }

        .stepper-item {
            flex: 1;
            min-width: 140px;
            padding: 0.75rem;
            flex-direction: column;
            text-align: center;
        }

        .stepper-info {
            margin-left: 0;
            margin-top: 0.5rem;
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
        }

        .step-card-body {
            padding: 1.25rem;
        }

        .step-navigation {
            flex-direction: column;
            gap: 0.75rem;
        }

        .step-navigation .btn-step {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

<div x-data="{ currentStep: 1 }">
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        <div class="job-stepper-container">
            <!-- Stepper Sidebar -->
            <aside class="stepper-sidebar">
                <nav class="stepper-nav">
                    <div class="stepper-progress">
                        <div class="stepper-progress-bar" :style="'width: ' + (currentStep / 4 * 100) + '%'"></div>
                    </div>

                    <div class="stepper-item" :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }" @click="currentStep = 1">
                        <div class="stepper-icon">
                            <i class="bi bi-clipboard-data" x-show="currentStep <= 1"></i>
                            <i class="bi bi-check2" x-show="currentStep > 1"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Job Details') }}</div>
                            <div class="stepper-desc">{{ __('Basic information') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" :class="{ 'active': currentStep === 2, 'completed': currentStep > 2 }" @click="currentStep = 2">
                        <div class="stepper-icon">
                            <i class="bi bi-phone" x-show="currentStep <= 2"></i>
                            <i class="bi bi-check2" x-show="currentStep > 2"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Devices') }}</div>
                            <div class="stepper-desc">{{ __('Add repair devices') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" :class="{ 'active': currentStep === 3, 'completed': currentStep > 3 }" @click="currentStep = 3">
                        <div class="stepper-icon">
                            <i class="bi bi-box-seam" x-show="currentStep <= 3"></i>
                            <i class="bi bi-check2" x-show="currentStep > 3"></i>
                        </div>
                        <div class="stepper-info">
                            <div class="stepper-title">{{ __('Parts & Services') }}</div>
                            <div class="stepper-desc">{{ __('Items and costs') }}</div>
                        </div>
                    </div>

                    <div class="stepper-item" :class="{ 'active': currentStep === 4 }" @click="currentStep = 4">
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
                <div class="step-panel" :class="{ 'active': currentStep === 1 }">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-clipboard-data me-2"></i>{{ __('Job Details') }}</h4>
                            <p>{{ __('Enter the basic information for this repair job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Case Number') }}</label>
                                    <input type="text" class="form-control" wire:model.defer="case_number" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                    <div class="form-text">{{ __('Auto-generated if left empty') }}</div>
                                    @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Title') }}</label>
                                    <input type="text" class="form-control" wire:model.defer="title" placeholder="{{ __('e.g., iPhone 14 Screen Repair') }}" />
                                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="customer_id">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                    <select id="customer_id" class="form-select" wire:model.defer="customer_id">
                                        <option value="">{{ __('Select customer...') }}</option>
                                        @foreach ($customers ?? [] as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="technician_ids">{{ __('Assigned Technicians') }}</label>
                                    <select id="technician_ids" class="form-select" multiple wire:model.defer="technician_ids">
                                        @foreach ($technicians ?? [] as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('technician_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Pickup Date') }}</label>
                                    <input type="date" class="form-control" wire:model.defer="pickup_date" />
                                    @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Delivery Date') }}</label>
                                    <input type="date" class="form-control" wire:model.defer="delivery_date" />
                                    @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Next Service Date') }}</label>
                                    <input type="date" class="form-control" wire:model.defer="next_service_date" />
                                    @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Job Description') }}</label>
                                    <textarea class="form-control" rows="4" wire:model.defer="case_detail" placeholder="{{ __('Describe the repair issue, customer notes, or any relevant details...') }}"></textarea>
                                    @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <div></div>
                            <button type="button" class="btn btn-primary btn-step" @click="currentStep = 2">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

            <!-- Step 2: Devices -->
                <div class="step-panel" :class="{ 'active': currentStep === 2 }">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-phone me-2"></i>{{ __('Devices') }}</h4>
                            <p>{{ __('Add the devices that need to be repaired.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <div class="d-flex justify-content-end mb-3">
                                <button type="button" class="btn btn-success" wire:click="addDevice">
                                    <i class="bi bi-plus-circle me-1"></i>{{ __('Add Device') }}
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table step-table">
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
                                        @if (count($deviceRows) === 0)
                                            <tr>
                                                <td colspan="{{ $enablePinCodeField ? 5 : 4 }}" class="text-center text-muted py-5">
                                                    <i class="bi bi-phone display-4 d-block mb-2 opacity-50"></i>
                                                    {{ __('No devices added yet. Click "Add Device" to get started.') }}
                                                </td>
                                            </tr>
                                        @endif

                                        @foreach ($deviceRows as $i => $row)
                                            <tr>
                                                <td>
                                                    <select class="form-select" wire:model.defer="deviceRows.{{ $i }}.customer_device_id">
                                                        <option value="">{{ __('Select...') }}</option>
                                                        @foreach ($customerDevices ?? [] as $cd)
                                                            <option value="{{ $cd->id }}">{{ $cd->label ?? ('#'.$cd->id) }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('deviceRows.' . $i . '.customer_device_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" wire:model.defer="deviceRows.{{ $i }}.serial" />
                                                    @error('deviceRows.' . $i . '.serial')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </td>
                                                @if ($enablePinCodeField)
                                                    <td>
                                                        <input type="text" class="form-control" wire:model.defer="deviceRows.{{ $i }}.pin" />
                                                        @error('deviceRows.' . $i . '.pin')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </td>
                                                @endif
                                                <td>
                                                    <input type="text" class="form-control" wire:model.defer="deviceRows.{{ $i }}.notes" />
                                                    @error('deviceRows.' . $i . '.notes')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeDevice({{ $i }})">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" @click="currentStep = 1">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-step" @click="currentStep = 3">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

            <!-- Step 3: Parts & Services -->
                <div class="step-panel" :class="{ 'active': currentStep === 3 }">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-box-seam me-2"></i>{{ __('Parts & Services') }}</h4>
                            <p>{{ __('Add parts, services, and other items for this job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <!-- Parts -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-cpu me-2"></i>{{ __('Parts') }}</h6>
                                    <button type="button" class="btn btn-success btn-sm" wire:click="addPart">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Part') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th style="width:140px">{{ __('Code') }}</th>
                                                <th style="width:100px">{{ __('Qty') }}</th>
                                                <th style="width:140px">{{ __('Price') }}</th>
                                                <th style="width:80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $partsItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'part'); @endphp
                                            @if (count($partsItems) === 0)
                                                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No parts added yet') }}</td></tr>
                                            @endif
                                            @foreach ($items as $i => $row)
                                                @if (($row['type'] ?? '') === 'part')
                                                <tr>
                                                    <td><input type="text" class="form-control" wire:model.defer="items.{{ $i }}.name" /></td>
                                                    <td><input type="text" class="form-control" wire:model.defer="items.{{ $i }}.code" /></td>
                                                    <td><input type="number" min="1" class="form-control" wire:model.defer="items.{{ $i }}.qty" /></td>
                                                    <td><input type="number" class="form-control" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Services -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-wrench-adjustable-circle me-2"></i>{{ __('Services') }}</h6>
                                    <button type="button" class="btn btn-success btn-sm" wire:click="addService">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Service') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th style="width:140px">{{ __('Code') }}</th>
                                                <th style="width:100px">{{ __('Qty') }}</th>
                                                <th style="width:140px">{{ __('Price') }}</th>
                                                <th style="width:80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $servicesItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'service'); @endphp
                                            @if (count($servicesItems) === 0)
                                                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No services added yet') }}</td></tr>
                                            @endif
                                            @foreach ($items as $i => $row)
                                                @if (($row['type'] ?? '') === 'service')
                                                <tr>
                                                    <td><input type="text" class="form-control" wire:model.defer="items.{{ $i }}.name" /></td>
                                                    <td><input type="text" class="form-control" wire:model.defer="items.{{ $i }}.code" /></td>
                                                    <td><input type="number" min="1" class="form-control" wire:model.defer="items.{{ $i }}.qty" /></td>
                                                    <td><input type="number" class="form-control" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Other Items -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>{{ __('Other Items') }}</h6>
                                    <button type="button" class="btn btn-success btn-sm" wire:click="addOtherItem">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Item') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th style="width:120px">{{ __('Type') }}</th>
                                                <th style="width:100px">{{ __('Qty') }}</th>
                                                <th style="width:140px">{{ __('Price') }}</th>
                                                <th style="width:80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $otherItems = array_filter($items, fn($r) => !in_array($r['type'] ?? '', ['part', 'service'])); @endphp
                                            @if (count($otherItems) === 0)
                                                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No other items added yet') }}</td></tr>
                                            @endif
                                            @foreach ($items as $i => $row)
                                                @if (!in_array($row['type'] ?? '', ['part', 'service']))
                                                <tr>
                                                    <td><input type="text" class="form-control" wire:model.defer="items.{{ $i }}.name" /></td>
                                                    <td>
                                                        <select class="form-select" wire:model.defer="items.{{ $i }}.type">
                                                            <option value="fee">{{ __('Fee') }}</option>
                                                            <option value="discount">{{ __('Discount') }}</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" min="1" class="form-control" wire:model.defer="items.{{ $i }}.qty" /></td>
                                                    <td><input type="number" class="form-control" wire:model.defer="items.{{ $i }}.unit_price_cents" /></td>
                                                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $i }})"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" @click="currentStep = 2">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-step" @click="currentStep = 4">
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

            <!-- Step 4: Settings & Review -->
                <div class="step-panel" :class="{ 'active': currentStep === 4 }">
                    <div class="step-card">
                        <div class="step-card-header">
                            <h4><i class="bi bi-gear me-2"></i>{{ __('Settings & Review') }}</h4>
                            <p>{{ __('Set status, payment, and other options. Review and submit your job.') }}</p>
                        </div>
                        <div class="step-card-body">
                            <!-- Extra Fields & Files -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>{{ __('Extra Fields & Files') }}</h6>
                                    <button type="button" class="btn btn-success btn-sm" wire:click="addExtra">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Field') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead>
                                            <tr>
                                                <th style="width:140px">{{ __('Date') }}</th>
                                                <th style="width:180px">{{ __('Label') }}</th>
                                                <th>{{ __('Data') }}</th>
                                                <th style="width:120px">{{ __('Visibility') }}</th>
                                                <th style="width:160px">{{ __('File') }}</th>
                                                <th style="width:80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (count($extras) === 0)
                                                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No extra fields added yet') }}</td></tr>
                                            @endif
                                            @foreach ($extras as $i => $row)
                                                <tr>
                                                    <td><input type="date" class="form-control" wire:model.defer="extras.{{ $i }}.occurred_at" /></td>
                                                    <td>
                                                        <input type="text" class="form-control" wire:model.defer="extras.{{ $i }}.label" />
                                                        @error('extras.' . $i . '.label')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </td>
                                                    <td><input type="text" class="form-control" wire:model.defer="extras.{{ $i }}.data_text" /></td>
                                                    <td>
                                                        <select class="form-select" wire:model.defer="extras.{{ $i }}.visibility">
                                                            <option value="public">{{ __('Public') }}</option>
                                                            <option value="private">{{ __('Private') }}</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="file" class="form-control form-control-sm" wire:model="extra_item_files.{{ $i }}" />
                                                        @error('extra_item_files.' . $i)<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </td>
                                                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeExtra({{ $i }})"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Order Settings -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Order Status') }}</label>
                                    <select class="form-select" wire:model.defer="status_slug">
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach ($jobStatuses ?? [] as $st)
                                            <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                        @endforeach
                                    </select>
                                    @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Payment Status') }}</label>
                                    <select class="form-select" wire:model.defer="payment_status_slug">
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach ($paymentStatuses ?? [] as $st)
                                            <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Priority') }}</label>
                                    <select class="form-select" wire:model.defer="priority">
                                        <option value="normal">{{ __('Normal') }}</option>
                                        <option value="high">{{ __('High') }}</option>
                                        <option value="urgent">{{ __('Urgent') }}</option>
                                    </select>
                                    @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Tax Mode') }}</label>
                                    <select class="form-select" wire:model.defer="prices_inclu_exclu">
                                        <option value="">{{ __('Select...') }}</option>
                                        <option value="inclusive">{{ __('Inclusive') }}</option>
                                        <option value="exclusive">{{ __('Exclusive') }}</option>
                                    </select>
                                    @error('prices_inclu_exclu')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="can_review_it" wire:model.defer="can_review_it">
                                        <label class="form-check-label" for="can_review_it">{{ __('Customer can review this job') }}</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('Order Notes') }}</label>
                                    <textarea class="form-control" rows="3" wire:model.defer="wc_order_note" placeholder="{{ __('Notes visible to customer.') }}"></textarea>
                                    @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('File Attachment') }}</label>
                                    <input type="file" class="form-control" wire:model="job_file" />
                                    @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" @click="currentStep = 3">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="submit" class="btn btn-success btn-step">
                                <i class="bi bi-check2-circle"></i> {{ __('Create Job') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

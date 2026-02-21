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
    /* ── Shared Design Tokens (matching job_show) ── */
    :root {
        --rb-primary: #3B82F6;
        --rb-primary-hover: #2563EB;
        --rb-success: #10B981;
        --rb-danger: #EF4444;
        --rb-warning: #F59E0B;
        --rb-hero-bg: #1e293b;
        --rb-card-bg: #ffffff;
        --rb-card-border: #e2e8f0;
        --rb-card-radius: 14px;
        --rb-card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 14px rgba(0,0,0,.04);
        --rb-section-bg: #f8fafc;
        --rb-text-primary: #1e293b;
        --rb-text-secondary: #64748b;
        --rb-text-muted: #94a3b8;
        --rb-border-color: #cbd5e1; /* Slightly darker for visibility */
        --rb-gradient-active: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);
    }

    [x-cloak] { display: none !important; }

    /* ── Hero Header ── */
    .job-hero-header {
        background: var(--rb-hero-bg);
        background-image: radial-gradient(ellipse at 50% 0%, rgba(100,116,139,.35) 0%, transparent 70%);
        border-radius: var(--rb-card-radius);
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .hero-left { display: flex; align-items: center; gap: 1rem; }
    .job-hero-icon {
        width: 48px; height: 48px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; color: #93c5fd;
    }
    .job-hero-header h4 {
        margin: 0; font-weight: 700; font-size: 1.25rem; color: #f1f5f9;
    }
    .hero-subtitle { color: #94a3b8; font-size: .875rem; margin-top: .2rem; }
    .btn-hero-back {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .5rem 1.15rem; border-radius: 8px; font-size: .875rem;
        font-weight: 500; color: #cbd5e1;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.1);
        text-decoration: none; transition: all .2s;
    }
    .btn-hero-back:hover { background: rgba(255,255,255,.12); color: #f1f5f9; }

    /* ── Vertical Stepper Layout ── */
    .job-stepper-container {
        display: flex;
        gap: 2rem;
        min-height: calc(100vh - 280px);
    }
    .stepper-sidebar { width: 280px; flex-shrink: 0; }
    .stepper-content  { flex: 1; min-width: 0; }

    .stepper-nav {
        position: sticky; top: 1rem;
        background: var(--rb-card-bg);
        border-radius: var(--rb-card-radius);
        box-shadow: var(--rb-card-shadow);
        padding: 1.25rem;
    }

    .stepper-progress {
        height: 4px; background: #e9ecef; border-radius: 2px;
        margin-bottom: 1rem; overflow: hidden;
    }
    .stepper-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--rb-primary) 0%, var(--rb-success) 100%);
        border-radius: 2px; transition: width .3s ease;
    }

    /* Stepper items + vertical connector line */
    .stepper-item {
        display: flex; align-items: flex-start;
        padding: .85rem .75rem; margin-bottom: .35rem;
        border-radius: 10px; cursor: pointer;
        transition: all .2s ease;
        background: transparent;
        border: 2px solid transparent;
        position: relative;
    }
    .stepper-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: calc(.75rem + 19px);
        top: calc(.85rem + 40px);
        width: 2px;
        height: calc(100% - 40px + .35rem);
        background: var(--rb-border-color);
    }
    .stepper-item:not(:last-child).completed::after {
        background: var(--rb-success);
    }

    .stepper-item:hover { background: rgba(59,130,246,.04); }
    .stepper-item.active {
        background: rgba(59,130,246,.06);
        border-color: var(--rb-primary);
    }
    .stepper-item.completed { background: rgba(16,185,129,.05); }

    .stepper-icon {
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 1rem;
        background: #f1f5f9; border: 2px solid var(--rb-border-color);
        color: var(--rb-text-secondary);
        flex-shrink: 0; transition: all .2s;
    }
    .stepper-item.active .stepper-icon {
        background: var(--rb-gradient-active);
        border-color: var(--rb-primary); color: #fff;
        box-shadow: 0 4px 14px rgba(59,130,246,.25);
    }
    .stepper-item.completed .stepper-icon {
        background: var(--rb-success); border-color: var(--rb-success); color: #fff;
    }

    .stepper-info  { margin-left: .85rem; flex: 1; }
    .stepper-title { font-weight: 600; font-size: .9rem; color: var(--rb-text-primary); margin-bottom: .1rem; }
    .stepper-desc  { font-size: .78rem; color: var(--rb-text-muted); }
    .stepper-item.active  .stepper-title { color: var(--rb-primary); }
    .stepper-item.completed .stepper-title { color: var(--rb-success); }

    /* ── Step Panels ── */
    .step-panel { display: none; animation: fadeIn .3s ease; }
    .step-panel.active { display: block; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Step Cards ── */
    .step-card {
        background: var(--rb-card-bg);
        border: 1px solid var(--rb-card-border);
        border-radius: var(--rb-card-radius);
        box-shadow: var(--rb-card-shadow);
        overflow: hidden;
    }
    .step-card-header {
        display: flex; align-items: flex-start; gap: 1rem;
        padding: 1.35rem 1.75rem;
        border-bottom: 1px solid var(--rb-border-color);
        background: linear-gradient(135deg, rgba(59,130,246,.025) 0%, transparent 100%);
    }
    .step-header-icon {
        width: 38px; height: 38px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(59,130,246,.08); color: var(--rb-primary);
        border-radius: 10px; font-size: 1.1rem;
    }
    .step-card-header h4 {
        margin: 0; font-weight: 650; font-size: 1.05rem; color: var(--rb-text-primary);
    }
    .step-card-header p {
        margin: .2rem 0 0; color: var(--rb-text-secondary); font-size: .85rem;
    }
    .step-card-body { padding: 1.75rem; }

    /* ── Form Styling ── */
    .form-label { font-weight: 500; color: #374151; margin-bottom: .4rem; font-size: .9rem; }
    .form-control, .form-select {
        background-color: #f8fafc;
        border-radius: 8px; border: 1.5px solid var(--rb-border-color);
        padding: .7rem .95rem; transition: all .2s;
        color: var(--rb-text-primary); font-size: .9rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: var(--rb-primary);
        box-shadow: none;
        outline: none;
    }
    .form-text { font-size: .8rem; color: var(--rb-text-muted); }

    /* ── Navigation Buttons ── */
    .step-navigation {
        display: flex; justify-content: space-between;
        padding: 1.25rem 1.75rem;
        border-top: 1px solid var(--rb-border-color);
        background: var(--rb-section-bg);
        border-radius: 0 0 var(--rb-card-radius) var(--rb-card-radius);
    }
    .btn-step {
        padding: .65rem 1.5rem; border-radius: 8px; font-weight: 500;
        display: inline-flex; align-items: center; gap: .45rem;
        transition: all .2s;
    }
    .btn-primary { background: var(--rb-primary); border-color: var(--rb-primary); }
    .btn-primary:hover { background: var(--rb-primary-hover); border-color: var(--rb-primary-hover); }
    .btn-success { background: var(--rb-success); border-color: var(--rb-success); }
    .btn-success:hover { background: #059669; border-color: #059669; }

    /* ── Tables ── */
    .step-table { margin-bottom: 0; }
    .step-table th {
        background: var(--rb-section-bg); font-weight: 600; font-size: .8rem;
        text-transform: uppercase; letter-spacing: .04em;
        color: var(--rb-text-secondary); border-bottom-width: 1px;
    }
    .step-table td, .step-table th {
        vertical-align: middle; padding: .75rem .85rem;
        border-color: var(--rb-border-color);
    }
    .step-table tbody tr:hover { background: rgba(59,130,246,.02); }

    /* ── Empty states ── */
    .step-empty-state {
        text-align: center; padding: 2.5rem 1rem !important;
        color: var(--rb-text-muted); font-size: .9rem;
    }
    .step-empty-state i {
        display: block; font-size: 2rem; margin-bottom: .5rem; opacity: .45;
    }

    /* ── Section headings & dividers ── */
    .step-section-heading {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: .85rem;
    }
    .step-section-heading h6 {
        margin: 0; font-weight: 600; font-size: .95rem; color: var(--rb-text-primary);
        display: flex; align-items: center; gap: .5rem;
    }
    .step-section-heading h6 i { color: var(--rb-primary); font-size: 1rem; }
    .step-section-divider {
        border: none; border-top: 1px dashed var(--rb-border-color);
        margin: 1.5rem 0;
    }

    /* ── Horizontal Field Layout (Labels Left, Inputs Right) ── */
    .field-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        gap: 2rem;
    }
    .field-label {
        width: 180px;
        flex-shrink: 0;
        padding-top: calc(0.6rem + 1px); /* Align with input padding */
        font-weight: 600;
        color: var(--rb-text-primary);
        font-size: 0.9rem;
    }
    .field-content {
        flex: 1;
        min-width: 0;
    }

    /* ── Searchable Select (Custom) ── */
    .search-select-container { position: relative; width: 100%; }
    .search-dropdown {
        position: absolute; top: 100%; left: 0; right: 0;
        z-index: 1050; background: #fff;
        border: 1px solid var(--rb-card-border);
        border-radius: 10px; margin-top: 5px;
        box-shadow: 0 10px 25px rgba(0,0,0,.1);
        max-height: 280px; overflow-y: auto;
    }
    .search-item {
        padding: .65rem 1rem; cursor: pointer;
        display: flex; align-items: center; justify-content: space-between;
        transition: all .15s ease; border-bottom: 1px solid #f1f5f9;
    }
    .search-item:last-child { border-bottom: none; }
    .search-item:hover { background: #f8fafc; }
    .search-item .item-title { font-weight: 500; font-size: .875rem; color: var(--rb-text-primary); }
    .search-item .item-meta { font-size: .75rem; color: var(--rb-text-muted); }

    .search-selected-box {
        background: #f8fafc; border: 1.5px solid var(--rb-card-border);
        border-radius: 10px; padding: .5rem .75rem;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: .5rem;
    }
    .search-chips-container { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
    .search-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
        padding: .25rem .65rem; border-radius: 6px; font-size: .78rem; font-weight: 500;
    }
    .search-chip .btn-remove-chip {
        background: none; border: none; padding: 0;
        color: #60a5fa; cursor: pointer; font-size: 1rem;
        line-height: 1; transition: color .15s;
    }
    .search-chip .btn-remove-chip:hover { color: #1d4ed8; }

    .btn-gradient {
        background: var(--rb-gradient-active);
        border: none;
        color: #fff !important;
        font-weight: 600;
        transition: all .2s ease;
        box-shadow: 0 4px 12px rgba(59,130,246,.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(59,130,246,.3);
        filter: brightness(1.1);
        color: #fff !important;
    }
    .btn-gradient:active { transform: translateY(0); }

    .dates-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    @media (max-width: 991px) {
        .dates-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 767.98px) {
        .field-row {
            flex-direction: column;
            gap: 0.5rem;
        }
        .field-label {
            width: 100%;
            text-align: left;
            padding-top: 0;
        }
    }

    /* ── Responsive ── */
    @media (max-width: 991.98px) {
        .job-stepper-container { flex-direction: column; }
        .stepper-sidebar { width: 100%; }
        .stepper-nav {
            position: relative; top: 0;
            display: flex; flex-wrap: wrap; gap: .5rem;
            padding: 1rem;
        }
        .stepper-progress { display: none; }
        .stepper-item {
            flex: 1; min-width: 130px; padding: .65rem;
            flex-direction: column; text-align: center; margin-bottom: 0;
        }
        .stepper-item:not(:last-child)::after { display: none; }
        .stepper-info { margin-left: 0; margin-top: .35rem; }
        .stepper-icon { width: 34px; height: 34px; font-size: .85rem; }
        .stepper-desc { display: none; }
        .job-hero-header { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 575.98px) {
        .stepper-item { min-width: auto; }
    .step-card-body { padding: 1.15rem; }
        .step-card-header { padding: 1.15rem; }
        .step-navigation {
            flex-direction: column; gap: .65rem; padding: 1rem;
        }
    }

    /* ── Modal Styling (Custom) ── */
    .rb-modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
        z-index: 2000; display: flex; align-items: center; justify-content: center;
        padding: 1.5rem; animation: fadeInModal .2s ease;
    }
    .rb-modal-container {
        background: #fff; width: 100%; max-width: 550px;
        border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden; position: relative; animation: slideUpModal .3s ease;
    }
    .rb-modal-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: #f8fafc;
    }
    .rb-modal-body { padding: 1.5rem; max-height: 80vh; overflow-y: auto; }
    .rb-modal-footer {
        padding: 1.25rem 1.5rem; border-top: 1px solid #f1f5f9;
        background: #f8fafc; display: flex; justify-content: flex-end; gap: .75rem;
    }
    @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUpModal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

<div class="container-fluid px-4 py-4">
    <div x-data="{ currentStep: 1 }">
    <div class="job-hero-header">
        <div class="hero-left">
            <div class="job-hero-icon">
                <i class="bi bi-clipboard-plus"></i>
            </div>
            <div>
                <h4>{{ $jobId ? __('Edit Job') : __('Create New Job') }}</h4>
                <div class="hero-subtitle">{{ $jobId ? __('Update job details and information.') : __('Fill in the details below to create a new repair job.') }}</div>
            </div>
        </div>
        <a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=jobs' }}" class="btn-hero-back">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Jobs') }}
        </a>
    </div>

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
                        <div class="step-card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="field-row">
                                        <label class="field-label">{{ __('Case Number') }}</label>
                                        <div class="field-content">
                                            <input type="text" class="form-control" wire:model.defer="case_number" placeholder="{{ __('Leave blank to auto-generate') }}" />
                                            <div class="form-text">{{ __('Auto-generated if left empty') }}</div>
                                            @error('case_number')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>


                                    <!-- Customer Selection -->
                                    <div class="field-row">
                                        <label class="field-label" for="customer_id">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                        <div class="field-content">
                                            @if($this->selected_customer)
                                                <div class="search-selected-box">
                                                    <div>
                                                        <div class="item-title">{{ $this->selected_customer->name }}</div>
                                                        <div class="item-meta">{{ $this->selected_customer->email }} | {{ $this->selected_customer->phone }}</div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-link text-danger" wire:click="$set('customer_id', null)">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            @else

                                                <div class="search-select-container" x-data="{ open: false }" @click.away="open = false">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" 
                                                               placeholder="{{ __('Search by name, email or phone...') }}"
                                                               wire:model.live.debounce.300ms="customer_search"
                                                               autocomplete="off"
                                                               @focus="open = true" 
                                                               @input="open = true"
                                                               @keydown.escape="open = false" />
                                                        <div wire:loading wire:target="customer_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                                        <a href="{{ route('tenant.operations.clients.create', ['business' => $tenant->slug]) }}" class="btn btn-gradient" title="{{ __('Create New Customer') }}" target="_blank">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </a>
                                                    </div>
                                                    
                                                    <div class="search-dropdown" x-show="open" x-cloak>
                                                        <!-- Loading State -->
                                                        <div wire:loading wire:target="customer_search" class="p-3 text-center">
                                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                            <span class="text-muted small">{{ __('Searching customers...') }}</span>
                                                        </div>

                                                        <div wire:loading.remove wire:target="customer_search">
                                                            @forelse($this->filtered_customers as $c)
                                                                <div class="search-item" wire:key="cust-res-{{ $c->id }}" wire:click="selectCustomer({{ $c->id }})" @click="open = false">
                                                                    <div>
                                                                        <div class="item-title">{{ $c->name }}</div>
                                                                        <div class="item-meta">{{ $c->email }} | {{ $c->phone }}</div>
                                                                    </div>
                                                                    <i class="bi bi-plus text-primary"></i>
                                                                </div>
                                                            @empty
                                                                <div class="p-3 text-center text-muted small">{{ __('No customers found') }}</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <!-- Technician Selection -->
                                    <div class="field-row">
                                        <label class="field-label" for="technician_ids">{{ __('Assigned Technicians') }}</label>
                                        <div class="field-content">
                                            <div class="search-select-container" x-data="{ open: false }" @click.away="open = false">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" 
                                                           placeholder="{{ __('Search technician...') }}"
                                                           wire:model.live.debounce.300ms="technician_search"
                                                           autocomplete="off"
                                                           @focus="open = true"
                                                           @input="open = true"
                                                           @keydown.escape="open = false" />
                                                    <div wire:loading wire:target="technician_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                                    <a href="{{ route('tenant.settings.users.create', ['business' => $tenant->slug]) }}" class="btn btn-gradient" title="{{ __('Create New Technician') }}" target="_blank">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </a>
                                                </div>
                                                
                                                <div class="search-dropdown" x-show="open" x-cloak>
                                                    <!-- Loading State -->
                                                    <div wire:loading wire:target="technician_search" class="p-3 text-center">
                                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                        <span class="text-muted small">{{ __('Searching technicians...') }}</span>
                                                    </div>

                                                    <div wire:loading.remove wire:target="technician_search">
                                                        @forelse($this->filtered_technicians as $t)
                                                            <div class="search-item" wire:key="tech-res-{{ $t->id }}" wire:click="selectTechnician({{ $t->id }})" @click="open = false">
                                                                <div>
                                                                    <div class="item-title">{{ $t->name }}</div>
                                                                    <div class="item-meta">{{ $t->email }}</div>
                                                                </div>
                                                                <i class="bi bi-plus text-primary"></i>
                                                            </div>
                                                        @empty
                                                            <div class="p-3 text-center text-muted small">{{ __('No technicians found') }}</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="search-chips-container">
                                                @foreach($this->selected_technicians as $st)
                                                    <span class="search-chip" wire:key="selected-tech-{{ $st->id }}">
                                                        {{ $st->name }}
                                                        <button type="button" class="btn-remove-chip" wire:click="removeTechnician({{ $st->id }})">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </span>
                                                @endforeach
                                            </div>
                                            @error('technician_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Schedule Dates') }}</label>
                                        <div class="field-content">
                                            <div class="dates-grid">
                                                <div>
                                                    <label class="form-label small text-muted mb-1">{{ __('Pickup Date') }}</label>
                                                    <input type="date" class="form-control" wire:model.defer="pickup_date" />
                                                    @error('pickup_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </div>
                                                <div>
                                                    <label class="form-label small text-muted mb-1">{{ __('Delivery Date') }}</label>
                                                    <input type="date" class="form-control" wire:model.defer="delivery_date" />
                                                    @error('delivery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </div>
                                                <div>
                                                    <label class="form-label small text-muted mb-1">{{ __('Next Service') }}</label>
                                                    <input type="date" class="form-control" wire:model.defer="next_service_date" />
                                                    @error('next_service_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Job Description') }}</label>
                                        <div class="field-content">
                                            <textarea class="form-control" rows="4" wire:model.defer="case_detail" placeholder="{{ __('Describe the repair issue, customer notes, or any relevant details...') }}"></textarea>
                                            @error('case_detail')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
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
                    <!-- Add Device Form -->
                    <div class="step-card mb-4 shadow-sm">
                        <div class="step-card-header py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-plus-square me-2"></i>{{ __('Add New Device') }}</h6>
                        </div>
                        <div class="step-card-body">
                            <div class="row g-3">
                                <!-- Grouped Device Search -->
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">{{ __('Select Device (Search Model or Brand)') }}</label>
                                    <div class="position-relative" x-data="{ 
                                        open: false, 
                                        search: @entangle('device_search').live 
                                    }" @click.away="open = false">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" 
                                                   class="form-control border-start-0" 
                                                   placeholder="{{ __('Start typing model...') }}"
                                                   x-model="search"
                                                   @focus="open = true"
                                                   @input="open = true">
                                            @if($selected_device_id)
                                                <button type="button" class="btn btn-outline-secondary border-start-0" wire:click="$set('selected_device_id', null); $set('selected_device_name', '')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Selected Device Display -->
                                        @if($selected_device_name)
                                            <div class="mt-2">
                                                <span class="badge bg-primary px-3 py-2 rounded-pill d-inline-flex align-items-center">
                                                    @if($selected_device_image)
                                                        <img src="{{ $selected_device_image }}" class="rounded-circle me-2" style="width: 20px; height: 20px; object-fit: cover; border: 1px solid rgba(255,255,255,0.5);">
                                                    @else
                                                        <i class="bi bi-check-circle-fill me-1"></i>
                                                    @endif
                                                    {{ $selected_device_name }}
                                                </span>
                                            </div>
                                        @endif

                                        <!-- Dropdown Results -->
                                        <div class="dropdown-menu shadow-lg border-0 w-100 mt-1 scrollbar-thin" 
                                             :class="{ 'show': open && search.length >= 2 }" 
                                             style="max-height: 400px; overflow-y: auto; z-index: 1050;">
                                            
                                            <!-- Loading State -->
                                            <div wire:loading wire:target="device_search" class="p-4 text-center">
                                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                <span class="text-muted small">{{ __('Searching devices...') }}</span>
                                            </div>

                                            <div wire:loading.remove wire:target="device_search">
                                                @forelse($this->filteredDevices as $brandName => $groupDevices)
                                                    <div class="dropdown-header bg-light py-2 text-uppercase fw-bold small text-primary sticky-top">
                                                        {{ $brandName }}
                                                    </div>
                                                    @foreach($groupDevices as $device)
                                                        <button type="button" 
                                                                class="dropdown-item py-2 d-flex align-items-center" 
                                                                wire:click="selectDevice({{ $device->id }}, '{{ $brandName }} {{ $device->model }}')"
                                                                @click="open = false">
                                                            <div class="me-2 rounded border overflow-hidden d-flex align-items-center justify-content-center bg-light" style="width: 32px; height: 32px;">
                                                                @if($device->image_url)
                                                                    <img src="{{ $device->image_url }}" alt="{{ $device->model }}" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                                @else
                                                                    <i class="bi bi-phone text-muted small"></i>
                                                                @endif
                                                            </div>
                                                            <span>{{ $device->model }}</span>
                                                        </button>
                                                    @endforeach
                                                @empty
                                                    <div class="p-3 text-center text-muted small">
                                                        {{ __('No matching devices found') }}
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                    @error('selected_device_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">{{ __('ID / IMEI / Serial') }}</label>
                                    <input type="text" class="form-control" wire:model.defer="device_serial" placeholder="{{ __('Search or Enter Serial...') }}">
                                    @error('device_serial') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                @if($enablePinCodeField)
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">{{ __('Pincode / Password') }}</label>
                                        <input type="text" class="form-control" wire:model.defer="device_pin" placeholder="{{ __('e.g. 1234') }}">
                                    </div>
                                @endif

                                @foreach($fieldDefinitions as $def)
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">{{ __($def->label) }}</label>
                                        <input type="text" class="form-control" wire:model.defer="additional_fields.{{ $def->key }}">
                                    </div>
                                @endforeach

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">{{ __('Device Note') }}</label>
                                    <textarea class="form-control" wire:model.defer="device_note" rows="2" placeholder="{{ __('Pre-existing damage, specific issues, etc.') }}"></textarea>
                                </div>

                                <div class="col-12 text-end">
                                    @if($editingDeviceIndex !== null)
                                        <button type="button" class="btn btn-outline-secondary px-4 me-2" wire:click="cancelEditDevice">
                                            {{ __('Cancel Edit') }}
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" wire:click="addDeviceToTable">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('Update Device') }}
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success px-4" wire:click="addDeviceToTable">
                                            <i class="bi bi-plus-circle me-1"></i>{{ __('Add to Job') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Added Devices List -->
                    <div class="step-card shadow-sm">
                        <div class="step-card-header py-3 bg-light">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>{{ __('Devices in this Job') }}</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">{{ __('Device Details') }}</th>
                                        <th>{{ __('IMEI / Serial') }}</th>
                                        @if($enablePinCodeField)
                                            <th>{{ __('Pin') }}</th>
                                        @endif
                                        <th>{{ __('Notes') }}</th>
                                        <th class="text-end pe-4">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deviceRows as $idx => $row)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    @if(!empty($row['image_url']))
                                                        <div class="me-3 rounded border overflow-hidden d-flex align-items-center justify-content-center bg-light" style="width: 40px; height: 40px; flex-shrink: 0;">
                                                            <img src="{{ $row['image_url'] }}" alt="{{ $row['device_model'] }}" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                        </div>
                                                    @else
                                                        <div class="me-3 rounded border d-flex align-items-center justify-content-center bg-light text-muted" style="width: 40px; height: 40px; flex-shrink: 0;">
                                                            <i class="bi bi-phone"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-bold">{{ $row['brand_name'] }} {{ $row['device_model'] }}</div>
                                                        @if(!empty($row['additional_fields']))
                                                            <div class="small mt-1">
                                                                @foreach($fieldDefinitions as $def)
                                                                    @if(!empty($row['additional_fields'][$def->key]))
                                                                        <span class="badge bg-light text-dark border me-1">{{ $def->label }}: {{ $row['additional_fields'][$def->key] }}</span>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code class="text-dark bg-light px-2 py-1 rounded small">{{ $row['serial'] ?: '--' }}</code></td>
                                            @if($enablePinCodeField)
                                                <td>{{ $row['pin'] ?: '--' }}</td>
                                            @endif
                                            <td>
                                                @if($row['notes'])
                                                    <span class="text-muted small d-inline-block text-truncate" style="max-width: 200px;" title="{{ $row['notes'] }}">
                                                        {{ $row['notes'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted italic small">--</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm border-0" wire:click="editDevice({{ $idx }})" title="{{ __('Edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm border-0" wire:click="removeDevice({{ $idx }})" title="{{ __('Remove') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $enablePinCodeField ? 5 : 4 }}" class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="bi bi-phone fs-2 d-block mb-2 opacity-25"></i>
                                                    {{ __('No devices added yet. Please use the form above to add at least one device.') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="step-navigation p-3">
                            <button type="button" class="btn btn-outline-secondary btn-step px-4" @click="currentStep = 1">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-step px-4" @click="currentStep = 3" {{ count($deviceRows) === 0 ? 'disabled' : '' }}>
                                {{ __('Continue') }} <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

            <!-- Step 3: Parts & Services -->
                <div class="step-panel" :class="{ 'active': currentStep === 3 }">
                    <div class="step-card">
                        <div class="step-card-body">
                            <!-- Parts -->
                            <div class="mb-5">
                                <div class="step-section-heading mb-3">
                                    <h6><i class="bi bi-cpu me-2"></i>{{ __('Parts') }}</h6>
                                </div>
                                
                                <!-- Add Part Form -->
                                <div class="row g-3 align-items-end mb-4 bg-light p-3 rounded border">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold text-muted">{{ __('Search Part') }}</label>
                                        <div class="position-relative" x-data="{ open: false, search: @entangle('part_search').live }" @click.away="open = false">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                <input type="text" 
                                                       class="form-control border-start-0" 
                                                       placeholder="{{ __('Type part name or code...') }}"
                                                       x-model="search"
                                                       @focus="open = true"
                                                       @input="open = true">
                                            </div>

                                            @if($selected_part_id)
                                                <div class="mt-2">
                                                    <span class="badge bg-secondary px-3 py-2 rounded-pill d-inline-flex align-items-center">
                                                        <i class="bi bi-check-circle-fill me-1"></i>
                                                        {{ $selected_part_name }}
                                                        <button type="button" class="btn btn-sm p-0 ms-2 text-white" wire:click="$set('selected_part_id', null)">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Dropdown Results -->
                                            <div class="dropdown-menu shadow-lg border-0 w-100 mt-1 scrollbar-thin" 
                                                 :class="{ 'show': open && search.length >= 2 }" 
                                                 style="max-height: 300px; overflow-y: auto; z-index: 1050;">
                                                
                                                <div wire:loading wire:target="part_search" class="p-3 text-center">
                                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                    <span class="text-muted small">{{ __('Searching...') }}</span>
                                                </div>

                                                <div wire:loading.remove wire:target="part_search">
                                                    @forelse($this->filteredParts as $part)
                                                        <button type="button" 
                                                                class="dropdown-item py-2 d-flex justify-content-between align-items-center" 
                                                                wire:click="selectPart({{ $part->id }}, '{{ $part->name }}')"
                                                                @click="open = false">
                                                            <div>
                                                                <div class="fw-bold">{{ $part->name }}</div>
                                                                <div class="small text-muted">{{ $part->manufacturing_code ?: $part->sku }}</div>
                                                            </div>
                                                            <div class="text-primary fw-bold">
                                                                {{ Number::currency($part->price_amount_cents / 100, $part->price_currency ?: $currency_code) }}
                                                            </div>
                                                        </button>
                                                    @empty
                                                        <div class="p-3 text-center">
                                                            <div class="text-muted small mb-2">{{ __('No parts found matching your search.') }}</div>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addCustomPart">
                                                                <i class="bi bi-plus-circle me-1"></i>{{ __('Add as Custom Part') }}
                                                            </button>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                        @error('selected_part_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">{{ __('Associate with Device') }}</label>
                                        <select class="form-select" wire:model.defer="selected_device_link_index">
                                            <option value="">{{ __('Select Device...') }}</option>
                                            @foreach($deviceRows as $idx => $row)
                                                <option value="{{ $idx }}">{{ $row['brand_name'] }} {{ $row['device_model'] }} ({{ $row['serial'] }})</option>
                                            @endforeach
                                        </select>
                                        @error('selected_device_link_index') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-3 text-end">
                                        <button type="button" class="btn btn-success w-100" wire:click="addPart" {{ !$selected_part_id ? 'disabled' : '' }}>
                                            <i class="bi bi-plus-circle me-1"></i>{{ __('Add Part') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle border-top">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">{{ __('Name') }}</th>
                                                <th>{{ __('Code') }}</th>
                                                <th>{{ __('Device') }}</th>
                                                <th style="width:100px" class="text-center">{{ __('Qty') }}</th>
                                                <th class="text-end" style="width: 140px;">{{ __('Price') }}</th>
                                                <th class="text-end" style="width: 120px;">{{ __('Total') }}</th>
                                                <th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $partsItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'part'); @endphp
                                            @forelse ($partsItems as $i => $row)
                                                <tr>
                                                    <td class="ps-3">
                                                        @if(empty($row['part_id']))
                                                            <input type="text" class="form-control form-control-sm mb-1" wire:model.defer="items.{{ $i }}.name" placeholder="{{ __('Part Name') }}" />
                                                        @else
                                                            <div class="fw-bold">{{ $row['name'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(empty($row['part_id']))
                                                            <input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.code" placeholder="{{ __('Code') }}" />
                                                        @else
                                                            <span class="text-muted small">{{ $row['code'] ?: '--' }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">{{ $row['device_info'] ?? '--' }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="input-group input-group-sm justify-content-center">
                                                            <input type="number" min="1" class="form-control text-center" style="max-width: 60px;" wire:model.live="items.{{ $i }}.qty" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white px-2">{{ $currency_symbol }}</span>
                                                            <input type="number" class="form-control text-end px-2" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        {{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" wire:click="removeItem({{ $i }})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center py-5 text-muted italic">
                                                        <i class="bi bi-cpu fs-2 d-block mb-2 opacity-25"></i>
                                                        {{ __('No parts added yet') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <hr class="step-section-divider" />

                            <!-- Services -->
                            <div class="mb-4">
                                <div class="step-section-heading align-items-end">
                                    <div class="flex-grow-1">
                                        <h6><i class="bi bi-wrench-adjustable-circle"></i>{{ __('Services') }}</h6>
                                        <div class="row align-items-end gx-3">
                                            <!-- Service Search (Col-5) -->
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold text-muted">{{ __('Search Catalog') }}</label>
                                                <div class="position-relative" x-data="{ open: false, search: @entangle('service_search').live }" @click.away="open = false">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                        <input type="text" 
                                                               class="form-control border-start-0" 
                                                               placeholder="{{ __('Type service name...') }}"
                                                               x-model="search"
                                                               @focus="open = true"
                                                               @input="open = true" />
                                                    </div>

                                                    <!-- Selected Badge -->
                                                    @if($selected_service_id)
                                                        <div class="mt-2">
                                                            <span class="badge bg-secondary px-3 py-2 rounded-pill d-inline-flex align-items-center">
                                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                                {{ $selected_service_name }}
                                                                <button type="button" class="btn btn-sm p-0 ms-2 text-white" wire:click="$set('selected_service_id', null)">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    @endif

                                                    <div class="dropdown-menu shadow-lg border-0 w-100 mt-1 scrollbar-thin" 
                                                         style="max-height: 300px; overflow-y: auto; z-index: 1050;" 
                                                         :class="{ 'show': open && search.length >= 2 }">
                                                        
                                                        <div wire:loading wire:target="service_search" class="w-100 p-3 text-center">
                                                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                            <span class="ms-2 small text-muted">{{ __('Searching...') }}</span>
                                                        </div>

                                                        <div wire:loading.remove wire:target="service_search">
                                                            @forelse($this->filteredServices as $service)
                                                                <button type="button" 
                                                                        class="dropdown-item py-2 d-flex justify-content-between align-items-center" 
                                                                        wire:click="selectService({{ $service->id }}, '{{ $service->name }}')"
                                                                        @click="open = false">
                                                                    <div>
                                                                        <div class="fw-bold">{{ $service->name }}</div>
                                                                        <div class="small text-muted">{{ $service->service_code ?: '--' }}</div>
                                                                    </div>
                                                                    <div class="text-primary fw-bold">
                                                                        {{ Number::currency($service->base_price_amount_cents / 100, $currency_code) }}
                                                                    </div>
                                                                </button>
                                                            @empty
                                                                <div class="p-3 text-center">
                                                                    <div class="text-muted small mb-2">{{ __('No services found.') }}</div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addService">
                                                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Custom') }}
                                                                    </button>
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Device Selection (Col-4) -->
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold text-muted">{{ __('Associate with Device') }}</label>
                                                <select class="form-select" wire:model.defer="selected_device_link_index">
                                                    <option value="">{{ __('Select Device...') }}</option>
                                                    @foreach($deviceRows as $idx => $device)
                                                        <option value="{{ $idx }}">{{ $device['brand_name'] }} {{ $device['device_model'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Add Button (Col-3) -->
                                            <div class="col-md-3 text-end">
                                                <button type="button" class="btn btn-success w-100" wire:click="addService" {{ !$selected_service_id && strlen($service_search) < 2 ? 'disabled' : '' }}>
                                                    <i class="bi bi-plus-circle me-1"></i>{{ $selected_service_id ? __('Add Service') : __('Add Custom') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">{{ __('Name') }}</th>
                                                <th>{{ __('Code') }}</th>
                                                <th>{{ __('Device') }}</th>
                                                <th style="width:100px" class="text-center">{{ __('Qty') }}</th>
                                                <th class="text-end" style="width: 140px;">{{ __('Price') }}</th>
                                                <th class="text-end" style="width: 120px;">{{ __('Total') }}</th>
                                                <th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $servicesItems = array_filter($items, fn($r) => ($r['type'] ?? '') === 'service'); @endphp
                                            @forelse ($servicesItems as $i => $row)
                                                <tr>
                                                    <td class="ps-3 align-middle fw-medium">
                                                        {{ $row['name'] ?? '--' }}
                                                    </td>
                                                    <td class="align-middle text-muted small">
                                                        {{ $row['code'] ?? '--' }}
                                                    </td>
                                                    <td class="small text-muted align-middle">
                                                        {{ $row['device_info'] ?? '--' }}
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="input-group input-group-sm justify-content-center">
                                                            <input type="number" min="1" class="form-control text-center" style="max-width: 60px;" wire:model.live="items.{{ $i }}.qty" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white px-2">{{ $currency_symbol }}</span>
                                                            <input type="number" class="form-control text-end px-2" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        {{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" wire:click="removeItem({{ $i }})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted italic small">
                                                        <i class="bi bi-wrench-adjustable-circle fs-3 d-block mb-1 opacity-25"></i>
                                                        {{ __('No services added yet') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <hr class="step-section-divider" />

                            <!-- Other Items -->
                            <div class="mb-4">
                                <div class="step-section-heading align-items-end">
                                    <div class="flex-grow-1">
                                        <h6><i class="bi bi-receipt"></i>{{ __('Other Items') }}</h6>
                                        <div class="row align-items-end gx-3">
                                            <!-- Label/Spacer (Col-5) -->
                                            <div class="col-md-5">
                                                <div class="small fw-bold text-muted mb-2">{{ __('Add Miscellaneous Fees or Items') }}</div>
                                                <div class="text-muted small opacity-75">
                                                    {{ __('Use this section for any additional charges not covered by parts or services.') }}
                                                </div>
                                            </div>

                                            <!-- Device Selection (Col-4) -->
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold text-muted">{{ __('Associate with Device') }}</label>
                                                <select class="form-select form-select-sm" wire:model.defer="selected_device_link_index">
                                                    <option value="">{{ __('Select Device...') }}</option>
                                                    @foreach($deviceRows as $idx => $device)
                                                        <option value="{{ $idx }}">{{ $device['brand_name'] }} {{ $device['device_model'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Add Button (Col-3) -->
                                            <div class="col-md-3 text-end">
                                                <button type="button" class="btn btn-success w-100 btn-sm" wire:click="addOtherItem">
                                                    <i class="bi bi-plus-circle me-1"></i>{{ __('Add Item') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">{{ __('Name') }}</th>
                                                <th style="width: 140px;">{{ __('Code') }}</th>
                                                <th>{{ __('Device') }}</th>
                                                <th style="width:100px" class="text-center">{{ __('Qty') }}</th>
                                                <th class="text-end" style="width: 140px;">{{ __('Price') }}</th>
                                                <th class="text-end" style="width: 120px;">{{ __('Total') }}</th>
                                                <th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $otherItems = array_filter($items, fn($r) => !in_array($r['type'] ?? '', ['part', 'service'])); @endphp
                                            @forelse ($otherItems as $i => $row)
                                                <tr>
                                                    <td class="ps-3">
                                                        <input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.name" placeholder="{{ __('Item Name...') }}" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" wire:model.defer="items.{{ $i }}.code" placeholder="{{ __('Code...') }}" />
                                                    </td>
                                                    <td class="small text-muted align-middle">
                                                        {{ $row['device_info'] ?? '--' }}
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="input-group input-group-sm justify-content-center">
                                                            <input type="number" min="1" class="form-control text-center" style="max-width: 60px;" wire:model.live="items.{{ $i }}.qty" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white px-2">{{ $currency_symbol }}</span>
                                                            <input type="number" class="form-control text-end px-2" wire:model.live="items.{{ $i }}.unit_price_cents" step="1" />
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        {{ Number::currency(($row['unit_price_cents'] ?? 0) * ($row['qty'] ?? 1), $currency_code) }}
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" wire:click="removeItem({{ $i }})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4 text-muted italic small">
                                                        <i class="bi bi-receipt fs-3 d-block mb-1 opacity-25"></i>
                                                        {{ __('No other items added yet') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                            </div>

                            <hr class="step-section-divider" />

                            <!-- Extra Fields & Files -->
                            <div class="mb-4">
                                <div class="step-section-heading">
                                    <h6><i class="bi bi-paperclip"></i>{{ __('Job Extras & Attachments') }}</h6>
                                    <button type="button" class="btn btn-outline-success btn-sm" wire:click="openExtraModal()">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Extra Field') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table step-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3" style="width:140px">{{ __('Date') }}</th>
                                                <th style="width:200px">{{ __('Label') }}</th>
                                                <th>{{ __('Data / Description') }}</th>
                                                <th style="width:120px" class="text-center">{{ __('Visibility') }}</th>
                                                <th style="width:80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($extras as $i => $row)
                                                <tr>
                                                    <td class="ps-3 align-middle">{{ $row['occurred_at'] ?: '--' }}</td>
                                                    <td class="align-middle fw-bold text-primary">{{ $row['label'] }}</td>
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-grow-1">
                                                                <div>{{ $row['data_text'] ?: '--' }}</div>
                                                                @if(!empty($row['description']))
                                                                    <div class="small text-muted">{{ $row['description'] }}</div>
                                                                @endif
                                                            </div>
                                                            @if(!empty($extra_item_files[$i]))
                                                                <div class="ms-2">
                                                                    <i class="bi bi-file-earmark-check text-success" title="{{ __('File attached') }}"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge {{ ($row['visibility'] ?? 'public') === 'public' ? 'bg-light text-success border border-success' : 'bg-light text-muted border' }}">
                                                            {{ __('' . ucfirst($row['visibility'] ?? 'public')) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3 align-middle">
                                                        <div class="d-flex justify-content-end gap-1">
                                                            <button type="button" class="btn btn-outline-primary btn-sm border-0" wire:click="openExtraModal({{ $i }})">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm border-0" wire:click="removeExtra({{ $i }})">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted italic small">
                                                        <i class="bi bi-paperclip fs-3 d-block mb-1 opacity-25"></i>
                                                        {{ __('No extra fields or attachments added yet') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Grand Total -->
                            <div class="row justify-content-end mt-4">
                                <div class="col-md-4">
                                    <div class="card bg-light border-0">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small">{{ __('Subtotal') }}</span>
                                                <span class="fw-bold">
                                                    @php 
                                                        $totalAmount = array_reduce($items, fn($carry, $item) => $carry + (($item['unit_price_cents'] ?? 0) * ($item['qty'] ?? 1)), 0);
                                                    @endphp
                                                    {{ Number::currency($totalAmount, $currency_code) }}
                                                </span>
                                            </div>
                                            <hr class="my-2 opacity-50" />
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="h6 mb-0">{{ __('Grand Total') }}</span>
                                                <span class="h5 mb-0 text-primary">
                                                    {{ Number::currency($totalAmount, $currency_code) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
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
                        <div class="step-card-body">

                            <!-- Order Settings -->
                            <div class="step-section-heading">
                                <h6><i class="bi bi-gear"></i>{{ __('Order Settings') }}</h6>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="field-row">
                                        <label class="field-label">{{ __('Order Status') }}</label>
                                        <div class="field-content">
                                            <select class="form-select" wire:model.defer="status_slug">
                                                <option value="">{{ __('Select...') }}</option>
                                                @foreach ($jobStatuses ?? [] as $st)
                                                    <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                                @endforeach
                                            </select>
                                            @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Payment Status') }}</label>
                                        <div class="field-content">
                                            <select class="form-select" wire:model.defer="payment_status_slug">
                                                <option value="">{{ __('Select...') }}</option>
                                                @foreach ($paymentStatuses ?? [] as $st)
                                                    <option value="{{ $st->code }}">{{ $st->label ?? $st->code }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Priority') }}</label>
                                        <div class="field-content">
                                            <select class="form-select" wire:model.defer="priority">
                                                <option value="normal">{{ __('Normal') }}</option>
                                                <option value="high">{{ __('High') }}</option>
                                                <option value="urgent">{{ __('Urgent') }}</option>
                                            </select>
                                            @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Tax Mode') }}</label>
                                        <div class="field-content">
                                            <select class="form-select" wire:model.defer="prices_inclu_exclu">
                                                <option value="">{{ __('Select...') }}</option>
                                                <option value="inclusive">{{ __('Inclusive') }}</option>
                                                <option value="exclusive">{{ __('Exclusive') }}</option>
                                            </select>
                                            @error('prices_inclu_exclu')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Customer Review') }}</label>
                                        <div class="field-content">
                                            <div class="form-check form-switch pt-1">
                                                <input class="form-check-input" type="checkbox" role="switch" id="can_review_it" wire:model.defer="can_review_it">
                                                <label class="form-check-label" for="can_review_it">{{ __('Customer can review this job') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('Order Notes') }}</label>
                                        <div class="field-content">
                                            <textarea class="form-control" rows="3" wire:model.defer="wc_order_note" placeholder="{{ __('Notes visible to customer.') }}"></textarea>
                                            @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="field-row">
                                        <label class="field-label">{{ __('File Attachment') }}</label>
                                        <div class="field-content">
                                            <input type="file" class="form-control" wire:model="job_file" />
                                            @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn btn-outline-secondary btn-step" @click="currentStep = 3">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="submit" class="btn btn-success btn-step">
                                <i class="bi bi-check2-circle"></i> {{ $jobId ? __('Update Job') : __('Create Job') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Job Extra Modal -->
    <div x-data="{ show: @entangle('showExtraModal') }" x-show="show" x-cloak class="rb-modal-backdrop" @keydown.escape.window="show = false">
        <div class="rb-modal-container" @click.away="show = false">
            <div class="rb-modal-header">
                <h5 class="mb-0 fw-bold">{{ $editingExtraIndex !== null ? __('Edit Job Extra') : __('Add Job Extra') }}</h5>
                <button type="button" class="btn-close" @click="show = false"></button>
            </div>
            <div class="rb-modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Field Label / Name') }}</label>
                        <input type="text" class="form-control" wire:model.defer="extra_label" placeholder="{{ __('e.g. Purchase Receipt, Box Status...') }}">
                        @error('extra_label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">{{ __('Occurrence Date') }}</label>
                        <input type="date" class="form-control" wire:model.defer="extra_occurred_at">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">{{ __('Visibility') }}</label>
                        <select class="form-select" wire:model.defer="extra_visibility">
                            <option value="public">{{ __('Public (Customer can see)') }}</option>
                            <option value="private">{{ __('Private (Internal only)') }}</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Data / Short Note') }}</label>
                        <input type="text" class="form-control" wire:model.defer="extra_data_text" placeholder="{{ __('Value or summary data') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Extended Description') }}</label>
                        <textarea class="form-control" rows="2" wire:model.defer="extra_description" placeholder="{{ __('Optional details...') }}"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">{{ __('Attachment / File') }}</label>
                        <div class="p-3 border rounded bg-light">
                            <input type="file" class="form-control form-control-sm" wire:model="extra_temp_file">
                            <div wire:loading wire:target="extra_temp_file" class="mt-2 small text-primary">
                                <div class="spinner-border spinner-border-sm me-1"></div> {{ __('Uploading...') }}
                            </div>
                            @if($extra_temp_file)
                                <div class="mt-2 small text-success">
                                    <i class="bi bi-file-earmark-check me-1"></i> {{ __('File ready to save') }}
                                </div>
                            @endif
                            @error('extra_temp_file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="rb-modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4" @click="show = false">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary px-4" wire:click="saveExtra" wire:loading.attr="disabled">
                    <i class="bi bi-check-circle me-1"></i> {{ __('Save Extra') }}
                </button>
            </div>
        </div>
    </div>

    </div>
</div>

@props([
    'tenant',
])

@push('page-styles')
<style>
    /* ═══════════════════════════════════════════════════════
       Appointment Form — Design B Layout
       Based on job-form.blade.php pattern
       ═══════════════════════════════════════════════════════ */

    :root {
        --rb-brand: #0ea5e9;
        --rb-brand-soft: #e0f2fe;
        --rb-brand-dark: #0284c7;
        --rb-success: #22c55e;
        --rb-success-soft: #dcfce7;
        --rb-danger: #ef4444;
        --rb-danger-soft: #fef2f2;
        --rb-warning: #f59e0b;
        --rb-warning-soft: #fef3c7;
        --rb-bg: #f8fafc;
        --rb-card: #ffffff;
        --rb-border: #e2e8f0;
        --rb-border-h: #cbd5e1;
        --rb-text: #0f172a;
        --rb-text-2: #475569;
        --rb-text-3: #94a3b8;
        --rb-radius: 12px;
        --rb-radius-sm: 8px;
        --rb-shadow: 0 1px 3px rgba(0,0,0,.06);
        --rb-shadow-md: 0 4px 12px rgba(0,0,0,.07);
    }

    [x-cloak] { display: none !important; }

    /* ── Page background override ── */
    .container-fluid.af-page {
        background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
        min-height: 100vh;
        margin: -1rem -1rem 0 -1rem;
        padding: 0;
        width: calc(100% + 2rem);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        line-height: 1.5;
        color: var(--rb-text);
    }

    /* ── Sticky Top Bar ── */
    .af-top-bar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--rb-border);
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 0 var(--rb-border), 0 2px 8px rgba(14,165,233,.04);
    }
    .af-top-bar-inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: .65rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .af-top-bar-inner .af-left { display: flex; align-items: center; gap: 1rem; }

    /* Back button */
    .af-back-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid var(--rb-border);
        background: #fff;
        color: var(--rb-text-2);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        flex-shrink: 0;
        font-size: .88rem;
        transition: all .15s;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .af-back-btn:hover {
        background: var(--rb-bg);
        color: var(--rb-brand);
        border-color: var(--rb-brand);
    }

    /* Page title block */
    .af-title-block { line-height: 1.2; }
    .af-title-block .af-mode-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .55rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .3rem;
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .af-title-block .af-page-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: 1rem;
        font-weight: 800;
        color: var(--rb-text);
        margin: 0 0 .15rem 0;
    }
    .af-title-block .af-page-title i { color: var(--rb-brand); font-size: .9rem; }

    /* Breadcrumb */
    .af-breadcrumb {
        display: flex;
        align-items: center;
        gap: .2rem;
        font-size: .72rem;
        color: var(--rb-text-3);
        margin: 0;
        list-style: none;
        padding: 0;
    }
    .af-breadcrumb a {
        color: var(--rb-text-3);
        text-decoration: none;
        transition: color .12s;
    }
    .af-breadcrumb a:hover { color: var(--rb-brand); }
    .af-breadcrumb .af-bc-sep {
        font-size: .6rem;
        opacity: .4;
        margin: 0 .05rem;
    }
    .af-breadcrumb .af-bc-current {
        color: var(--rb-text-2);
        font-weight: 600;
    }

    .af-top-bar-inner .af-right { display: flex; gap: .5rem; }

    .af-btn {
        padding: .5rem 1.25rem;
        border-radius: var(--rb-radius-sm);
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        text-decoration: none;
    }
    .af-btn-cancel {
        background: transparent;
        color: var(--rb-text-2);
        border: 1px solid var(--rb-border);
    }
    .af-btn-cancel:hover { background: var(--rb-bg); color: var(--rb-text); }
    .af-btn-save {
        background: var(--rb-brand);
        color: #fff;
    }
    .af-btn-save:hover { background: var(--rb-brand-dark); }

    /* ── Page Layout ── */
    .af-layout {
        display: flex;
        gap: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem 2rem;
        align-items: flex-start;
    }
    .af-main { flex: 1; min-width: 0; }
    .af-side { width: 320px; flex-shrink: 0; }
    .af-side .af-sticky {
        position: sticky;
        top: 5rem;
    }

    /* ── Collapsible Sections ── */
    .af-section {
        background: var(--rb-card);
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: var(--rb-shadow);
    }
    .af-section-head {
        display: flex;
        align-items: center;
        gap: .625rem;
        padding: .75rem 1rem;
        cursor: pointer;
        user-select: none;
        transition: background .12s;
    }
    .af-section-head:hover { background: var(--rb-bg); }
    .af-section-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .88rem;
        flex-shrink: 0;
    }
    .af-section-head h3 {
        font-size: .92rem;
        font-weight: 700;
        flex: 1;
        margin: 0;
    }
    .af-section-head .af-tag {
        font-size: .68rem;
        font-weight: 700;
        padding: .15rem .5rem;
        border-radius: 999px;
    }
    .af-section-head .af-chevron {
        font-size: .75rem;
        color: var(--rb-text-3);
        transition: transform .25s;
    }
    .af-section-body {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--rb-border);
    }

    /* ── Form Groups ── */
    .af-fg { margin-bottom: .875rem; }
    .af-fg:last-child { margin-bottom: 0; }
    .af-fg > label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: var(--rb-text-2);
        margin-bottom: .3rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .af-fg .form-control,
    .af-fg .form-select {
        width: 100%;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        padding: .5rem .75rem;
        font-size: .86rem;
        color: var(--rb-text);
        background: #fff;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        height: auto;
    }
    .af-fg .form-control:focus,
    .af-fg .form-select:focus {
        border-color: var(--rb-brand);
        box-shadow: 0 0 0 3px rgba(14,165,233,.12);
        background: #fff;
    }
    .af-fg .form-text,
    .af-fg .af-hint {
        font-size: .72rem;
        color: var(--rb-text-3);
        margin-top: .2rem;
    }

    .af-row { display: flex; gap: .75rem; }
    .af-c2 { flex: 1; }

    .af-dates-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .75rem;
    }
    @media (max-width: 767.98px) {
        .af-dates-grid { grid-template-columns: 1fr; }
    }

    /* ── Search Select / Dropdown ── */
    .af-search-container { position: relative; width: 100%; }
    .af-search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #fff;
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius-sm);
        margin-top: 4px;
        box-shadow: 0 10px 25px rgba(0,0,0,.1);
        max-height: 280px;
        overflow-y: auto;
    }
    .af-search-item {
        padding: .6rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background .12s;
        border-bottom: 1px solid #f8fafc;
    }
    .af-search-item:last-child { border-bottom: none; }
    .af-search-item:hover { background: #f8fafc; }
    .af-search-item .af-item-title { font-weight: 500; font-size: .875rem; color: var(--rb-text); }
    .af-search-item .af-item-meta { font-size: .75rem; color: var(--rb-text-3); }

    .af-selected-box {
        background: var(--rb-brand-soft);
        border: 1px solid #bae6fd;
        border-radius: var(--rb-radius-sm);
        padding: .5rem .75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .btn-gradient {
        background: linear-gradient(135deg, var(--rb-brand) 0%, var(--rb-brand-dark) 100%);
        border: 1px solid var(--rb-brand);
        color: #fff !important;
        font-weight: 600;
        transition: all .2s ease;
        box-shadow: 0 4px 12px rgba(14,165,233,.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(14,165,233,.3);
        filter: brightness(1.05);
        color: #fff !important;
        border-color: var(--rb-brand-dark);
    }

    /* Input group fix */
    .af-search-container .input-group {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
    }
    .af-search-container .input-group .form-control {
        border-radius: var(--rb-radius-sm);
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
    }
    .af-search-container .input-group .btn-gradient {
        border-radius: var(--rb-radius-sm);
        margin-left: -1px;
        flex-shrink: 0;
    }

    /* ── Button Group Styling ── */
    .af-fg .btn-group .btn.btn-outline-primary {
        border-color: var(--rb-border);
        color: var(--rb-text-2);
        background: #fff;
        font-size: .82rem;
        font-weight: 600;
        padding: .5rem .75rem;
    }
    .af-fg .btn-group .btn.btn-outline-primary:hover {
        background: var(--rb-bg);
        color: var(--rb-brand);
    }
    .af-fg .btn-group .btn.btn-outline-primary.active {
        background: var(--rb-brand);
        border-color: var(--rb-brand);
        color: #fff;
        z-index: 1;
    }
    .af-fg .btn-group .btn.btn-outline-primary.active:hover {
        background: var(--rb-brand-dark);
    }

    /* ── Sidebar Cards ── */
    .af-sc {
        background: var(--rb-card);
        border: 1px solid var(--rb-border);
        border-radius: var(--rb-radius);
        margin-bottom: .75rem;
        overflow: hidden;
        box-shadow: var(--rb-shadow);
    }
    .af-sc-head {
        padding: .625rem .875rem;
        font-weight: 700;
        font-size: .85rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        border-bottom: 1px solid var(--rb-border);
    }
    .af-sc-head i { color: var(--rb-brand); }
    .af-sc-body { padding: .875rem; }
    .af-sc-row {
        display: flex;
        justify-content: space-between;
        padding: .25rem 0;
        font-size: .82rem;
    }
    .af-sc-row .af-val { font-weight: 600; }

    .af-divider {
        border: none;
        border-top: 1px solid var(--rb-border);
        margin: .55rem 0;
    }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .af-layout {
            flex-direction: column;
            padding: 1rem;
        }
        .af-side { width: 100%; }
        .af-side .af-sticky { position: static; }
        .af-row { flex-direction: column; }
        .af-c2 { flex: 1 1 100%; }
        .af-dates-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px) {
        .af-top-bar { padding: .75rem 1rem; flex-wrap: wrap; gap: .5rem; }
        .af-top-bar .af-left h1 { font-size: 1rem; }
    }
</style>
@endpush

@php
    $backUrl = route('tenant.appointments.index', ['business' => $tenant->slug]);
@endphp

<div class="container-fluid px-0 af-page">
    @livewire('tenant.operations.quick-customer-modal', ['tenant' => $tenant])
    @livewire('tenant.operations.quick-technician-modal', ['tenant' => $tenant])

    <form id="appointment-form" wire:submit.prevent="save">
    <div x-data="{
        sections: { details: true, related: false, notes: false }
    }">

    {{-- ══════ STICKY TOP BAR ══════ --}}
    <header class="af-top-bar">
        <div class="af-top-bar-inner">
            <div class="af-left">
                {{-- Back button --}}
                <a href="{{ $backUrl }}"
                   class="af-back-btn" title="{{ __('Back to Appointments') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                {{-- Title + breadcrumb --}}
                <div class="af-title-block">
                    <span class="af-mode-badge">
                        <i class="bi bi-plus-lg"></i>
                        {{ __('New') }}
                    </span>
                    <h1 class="af-page-title">
                        <i class="bi bi-calendar-event"></i>
                        {{ __('Create Appointment') }}
                    </h1>
                    <ol class="af-breadcrumb">
                        <li><a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) }}">{{ __('Dashboard') }}</a></li>
                        <li><i class="bi bi-chevron-right af-bc-sep"></i></li>
                        <li><a href="{{ $backUrl }}">{{ __('Appointments') }}</a></li>
                        <li><i class="bi bi-chevron-right af-bc-sep"></i></li>
                        <li><span class="af-bc-current">{{ __('New Appointment') }}</span></li>
                    </ol>
                </div>
            </div>
            <div class="af-right">
                <a href="{{ $backUrl }}" class="af-btn af-btn-cancel">
                    <i class="bi bi-x-lg"></i> {{ __('Cancel') }}
                </a>
                <button type="submit" class="af-btn af-btn-save" wire:loading.attr="disabled" wire:target="save">
                    <i class="bi bi-check-lg" wire:loading.remove wire:target="save"></i>
                    <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    <span wire:loading.remove wire:target="save">{{ __('Create Appointment') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </button>
            </div>
        </div>
    </header>

    <div class="af-layout">

        {{-- ════════════════════════════════════
             MAIN COLUMN
             ════════════════════════════════════ --}}
        <div class="af-main">

            {{-- ── Section 1: Appointment Details ── --}}
            <div class="af-section">
                <div class="af-section-head" @click="sections.details = !sections.details">
                    <div class="af-section-badge" style="background:var(--rb-brand-soft);color:var(--rb-brand)">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h3>{{ __('Appointment Details') }}</h3>
                    <span class="af-tag" style="background:var(--rb-success-soft);color:#16a34a;">{{ __('Required') }}</span>
                    <i class="bi bi-chevron-down af-chevron" :style="sections.details ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="af-section-body" x-show="sections.details" x-collapse>

                    {{-- Customer Selection --}}
                    <div class="af-fg">
                        <label><i class="bi bi-person"></i> {{ __('Customer') }} <span class="text-danger">*</span></label>
                        @if($selected_customer)
                            <div class="af-selected-box">
                                <div>
                                    <div class="af-item-title fw-semibold">{{ $selected_customer->name }}</div>
                                    <div class="af-item-meta" style="font-size:.75rem;color:var(--rb-text-3);">{{ $selected_customer->email }} | {{ $selected_customer->phone }}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" wire:click="removeCustomer">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        @else
                            <div class="af-search-container" x-data="{ open: false }" @click.away="open = false">
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           placeholder="{{ __('Search by name, email or phone...') }}"
                                           wire:model.live.debounce.300ms="customer_search"
                                           autocomplete="off"
                                           @focus="open = true"
                                           @input="open = true"
                                           @keydown.escape="open = false" />
                                    <div wire:loading wire:target="customer_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                    <button type="button" class="btn btn-gradient" title="{{ __('Quick Add Customer') }}" wire:click="$dispatch('openQuickCustomerModal')">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="af-search-dropdown" x-show="open" x-cloak>
                                    <div wire:loading wire:target="customer_search" class="p-3 text-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span class="text-muted small">{{ __('Searching customers...') }}</span>
                                    </div>
                                    <div wire:loading.remove wire:target="customer_search">
                                        @forelse($this->filtered_customers as $c)
                                            <div class="af-search-item" wire:key="cust-res-{{ $c->id }}" wire:click="selectCustomer({{ $c->id }})" @click="open = false">
                                                <div>
                                                    <div class="af-item-title">{{ $c->name }}</div>
                                                    <div class="af-item-meta">{{ $c->email }} | {{ $c->phone }}</div>
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

                    {{-- Technician Selection --}}
                    <div class="af-fg">
                        <label><i class="bi bi-person-gear"></i> {{ __('Assigned Technician') }} <span class="text-danger">*</span></label>
                        @if($selected_technician)
                            <div class="af-selected-box">
                                <div>
                                    <div class="af-item-title fw-semibold">{{ $selected_technician->name }}</div>
                                    <div class="af-item-meta" style="font-size:.75rem;color:var(--rb-text-3);">{{ $selected_technician->email }}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" wire:click="removeTechnician">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        @else
                            <div class="af-search-container" x-data="{ open: false }" @click.away="open = false">
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           placeholder="{{ __('Search technician...') }}"
                                           wire:model.live.debounce.300ms="technician_search"
                                           autocomplete="off"
                                           @focus="open = true"
                                           @input="open = true"
                                           @keydown.escape="open = false" />
                                    <div wire:loading wire:target="technician_search" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-5" style="z-index: 5;"></div>
                                    <button type="button" class="btn btn-gradient" title="{{ __('Quick Add Technician') }}" wire:click="$dispatch('openQuickTechnicianModal')">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="af-search-dropdown" x-show="open" x-cloak>
                                    <div wire:loading wire:target="technician_search" class="p-3 text-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span class="text-muted small">{{ __('Searching technicians...') }}</span>
                                    </div>
                                    <div wire:loading.remove wire:target="technician_search">
                                        @forelse($this->filtered_technicians as $t)
                                            <div class="af-search-item" wire:key="tech-res-{{ $t->id }}" wire:click="selectTechnician({{ $t->id }})" @click="open = false">
                                                <div>
                                                    <div class="af-item-title">{{ $t->name }}</div>
                                                    <div class="af-item-meta">{{ $t->email }}</div>
                                                </div>
                                                <i class="bi bi-plus text-primary"></i>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">{{ __('No technicians found') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        @error('technician_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Appointment Type & Date --}}
                    <div class="af-dates-grid">
                        <div class="af-fg" style="margin-bottom:0;">
                            <label><i class="bi bi-tag"></i> {{ __('Appointment Type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="appointment_setting_id">
                                <option value="">{{ __('Select type...') }}</option>
                                @foreach($appointmentTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                            @error('appointment_setting_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="af-fg" style="margin-bottom:0;">
                            <label><i class="bi bi-calendar3"></i> {{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model.live="appointment_date" min="{{ now()->toDateString() }}" />
                            @error('appointment_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Time Slot --}}
                    <div class="af-fg">
                        <label><i class="bi bi-clock"></i> {{ __('Time Slot') }} <span class="text-danger">*</span></label>
                        @if($appointment_setting_id && $appointment_date && count($availableTimeSlots) > 0)
                            <select class="form-select" wire:model="time_slot">
                                <option value="">{{ __('Select a Time Slot...') }}</option>
                                @foreach($availableTimeSlots as $slot)
                                    <option value="{{ $slot['value'] }}">{{ $slot['label'] }}</option>
                                @endforeach
                            </select>
                        @elseif($appointment_setting_id && $appointment_date)
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                {{ __('No available time slots for the selected date. Please choose another date.') }}
                            </div>
                        @else
                            <select class="form-select" disabled>
                                <option value="">{{ __('Select appointment type and date first...') }}</option>
                            </select>
                        @endif
                        @error('time_slot')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Section 2: Related To ── --}}
            <div class="af-section">
                <div class="af-section-head" @click="sections.related = !sections.related">
                    <div class="af-section-badge" style="background:#dbeafe;color:#2563eb">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <h3>{{ __('Related To') }}</h3>
                    <span class="af-tag" style="background:#dbeafe;color:#1e40af;">{{ __('Optional') }}</span>
                    <i class="bi bi-chevron-down af-chevron" :style="sections.related ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="af-section-body" x-show="sections.related" x-collapse>

                    {{-- Link Type Selector --}}
                    <div class="af-fg">
                        <label>{{ __('Link To') }}</label>
                        <div class="btn-group w-100" role="group" x-data="{ linkType: @entangle('link_type') }">
                            <button type="button" class="btn btn-outline-primary" :class="{ 'active': linkType === 'none' }" @click="linkType = 'none'">
                                {{ __('None') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary" :class="{ 'active': linkType === 'job' }" @click="linkType = 'job'">
                                <i class="bi bi-tools me-1"></i> {{ __('Job') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary" :class="{ 'active': linkType === 'estimate' }" @click="linkType = 'estimate'">
                                <i class="bi bi-file-text me-1"></i> {{ __('Estimate') }}
                            </button>
                        </div>
                    </div>

                    {{-- Job Search --}}
                    @if($link_type === 'job')
                        <div class="af-fg">
                            <label><i class="bi bi-search"></i> {{ __('Search Job by Case Number') }}</label>
                            @if($selected_job)
                                <div class="af-selected-box">
                                    <div>
                                        <div class="af-item-title fw-semibold">{{ $selected_job->case_number }}</div>
                                        <div class="af-item-meta" style="font-size:.75rem;color:var(--rb-text-3);">{{ $selected_job->title }}</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger" wire:click="removeJob">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            @else
                                <div class="af-search-container" x-data="{ open: false }" @click.away="open = false">
                                    <input type="text" class="form-control"
                                           placeholder="{{ __('Search by case number or title...') }}"
                                           wire:model.live.debounce.300ms="job_search"
                                           autocomplete="off"
                                           @focus="open = true"
                                           @input="open = true"
                                           @keydown.escape="open = false" />
                                    <div class="af-search-dropdown" x-show="open && $wire.job_search.length >= 2" x-cloak>
                                        @forelse($this->filtered_jobs as $j)
                                            <div class="af-search-item" wire:key="job-res-{{ $j->id }}" wire:click="selectJob({{ $j->id }})" @click="open = false">
                                                <div>
                                                    <div class="af-item-title">{{ $j->case_number }}</div>
                                                    <div class="af-item-meta">{{ $j->title }}</div>
                                                </div>
                                                <i class="bi bi-plus text-primary"></i>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">{{ __('No jobs found') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                            @error('job_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    @endif

                    {{-- Estimate Search --}}
                    @if($link_type === 'estimate')
                        <div class="af-fg">
                            <label><i class="bi bi-search"></i> {{ __('Search Estimate by Case Number') }}</label>
                            @if($selected_estimate)
                                <div class="af-selected-box">
                                    <div>
                                        <div class="af-item-title fw-semibold">{{ $selected_estimate->case_number }}</div>
                                        <div class="af-item-meta" style="font-size:.75rem;color:var(--rb-text-3);">{{ $selected_estimate->title }}</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger" wire:click="removeEstimate">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            @else
                                <div class="af-search-container" x-data="{ open: false }" @click.away="open = false">
                                    <input type="text" class="form-control"
                                           placeholder="{{ __('Search by case number or title...') }}"
                                           wire:model.live.debounce.300ms="estimate_search"
                                           autocomplete="off"
                                           @focus="open = true"
                                           @input="open = true"
                                           @keydown.escape="open = false" />
                                    <div class="af-search-dropdown" x-show="open && $wire.estimate_search.length >= 2" x-cloak>
                                        @forelse($this->filtered_estimates as $e)
                                            <div class="af-search-item" wire:key="est-res-{{ $e->id }}" wire:click="selectEstimate({{ $e->id }})" @click="open = false">
                                                <div>
                                                    <div class="af-item-title">{{ $e->case_number }}</div>
                                                    <div class="af-item-meta">{{ $e->title }}</div>
                                                </div>
                                                <i class="bi bi-plus text-primary"></i>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">{{ __('No estimates found') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                            @error('estimate_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    @endif

                </div>
            </div>

            {{-- ── Section 3: Notes ── --}}
            <div class="af-section">
                <div class="af-section-head" @click="sections.notes = !sections.notes">
                    <div class="af-section-badge" style="background:#fef3c7;color:#d97706">
                        <i class="bi bi-chat-text"></i>
                    </div>
                    <h3>{{ __('Notes') }}</h3>
                    <span class="af-tag" style="background:#fef3c7;color:#92400e;">{{ __('Optional') }}</span>
                    <i class="bi bi-chevron-down af-chevron" :style="sections.notes ? '' : 'transform:rotate(-90deg)'"></i>
                </div>
                <div class="af-section-body" x-show="sections.notes" x-collapse>
                    <div class="af-fg" style="margin-bottom:0;">
                        <label>{{ __('Appointment Notes') }}</label>
                        <textarea class="form-control" rows="4" wire:model.defer="notes" placeholder="{{ __('Any additional notes for this appointment...') }}"></textarea>
                        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════
             SIDEBAR
             ════════════════════════════════════ --}}
        <div class="af-side">
            <div class="af-sticky">

                {{-- Summary Card --}}
                <div class="af-sc">
                    <div class="af-sc-head">
                        <i class="bi bi-calendar-check"></i>
                        {{ __('Appointment Summary') }}
                    </div>
                    <div class="af-sc-body">
                        <div class="af-sc-row">
                            <span>{{ __('Customer') }}</span>
                            <span class="af-val">{{ $selected_customer?->name ?? '—' }}</span>
                        </div>
                        <div class="af-sc-row">
                            <span>{{ __('Technician') }}</span>
                            <span class="af-val">{{ $selected_technician?->name ?? '—' }}</span>
                        </div>
                        <hr class="af-divider">
                        <div class="af-sc-row">
                            <span>{{ __('Type') }}</span>
                            <span class="af-val">
                                @if($appointment_setting_id)
                                    {{ $appointmentTypes->firstWhere('id', $appointment_setting_id)?->title ?? '—' }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="af-sc-row">
                            <span>{{ __('Date') }}</span>
                            <span class="af-val">{{ $appointment_date ? \Carbon\Carbon::parse($appointment_date)->format('M d, Y') : '—' }}</span>
                        </div>
                        <div class="af-sc-row">
                            <span>{{ __('Time') }}</span>
                            <span class="af-val">{{ $time_slot ? \Carbon\Carbon::parse($time_slot)->format('H:i') : '—' }}</span>
                        </div>
                        @if($selected_job || $selected_estimate)
                            <hr class="af-divider">
                            <div class="af-sc-row">
                                <span>{{ __('Linked To') }}</span>
                                <span class="af-val">
                                    @if($selected_job)
                                        <span class="badge bg-primary"><i class="bi bi-tools me-1"></i>{{ $selected_job->case_number }}</span>
                                    @elseif($selected_estimate)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-file-text me-1"></i>{{ $selected_estimate->case_number }}</span>
                                    @endif
                                </span>
                            </div>
                        @endif
                        <hr class="af-divider">
                        <div class="af-sc-row">
                            <span>{{ __('Status') }}</span>
                            <span class="badge bg-success">{{ __('Confirmed') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
    </div>
    </form>
</div>

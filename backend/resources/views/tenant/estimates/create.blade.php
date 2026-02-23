@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Estimate'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyEstimate|null $estimate */
    $estimate = $estimate ?? null;
    $isEdit   = $estimate !== null;

    $customers       = $customers ?? collect();
    $technicians     = $technicians ?? collect();
    $customerDevices = $customerDevices ?? collect();
    $devices         = $devices ?? collect();
    $parts           = $parts ?? collect();
    $services        = $services ?? collect();

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';

    $formAction = $isEdit
        ? route('tenant.estimates.update', ['business' => $tenantSlug, 'estimateId' => $estimate->id])
        : route('tenant.estimates.store', ['business' => $tenantSlug]);

    $backUrl = $isEdit
        ? route('tenant.estimates.show', ['business' => $tenantSlug, 'estimateId' => $estimate->id])
        : route('tenant.estimates.index', ['business' => $tenantSlug]);

    /* Pre-populate existing data for edit mode */
    $existingDevices = [];
    $existingItems   = [];
    if ($isEdit) {
        foreach ($estimate->devices as $d) {
            $existingDevices[] = [
                'customer_device_id' => $d->customer_device_id,
                'serial'             => $d->serial_snapshot,
                'pin'                => $d->pin_snapshot,
                'notes'              => $d->notes_snapshot,
                'label'              => $d->label_snapshot,
            ];
        }
        foreach ($estimate->items as $item) {
            $existingItems[] = [
                'type'             => $item->item_type,
                'name'             => $item->name_snapshot,
                'qty'              => $item->qty,
                'unit_price_cents' => $item->unit_price_amount_cents,
            ];
        }
    }
@endphp

@push('page-styles')
<style>
    :root {
        --ef-primary: #4f46e5;
        --ef-primary-light: #eef2ff;
        --ef-primary-border: #c7d2fe;
        --ef-border: #e2e8f0;
        --ef-border-hover: #cbd5e1;
        --ef-bg-subtle: #f8fafc;
        --ef-text: #0f172a;
        --ef-text-muted: #64748b;
        --ef-text-light: #94a3b8;
        --ef-radius: 12px;
        --ef-radius-sm: 8px;
        --ef-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --ef-shadow-md: 0 4px 12px rgba(0,0,0,.07);
    }

    /* ── Page header ── */
    .ef-page-header { margin-bottom: 1.5rem; }
    .ef-page-header h1 { font-size: 1.5rem; font-weight: 800; color: var(--ef-text); margin: 0; display: flex; align-items: center; gap: .5rem; }
    .ef-page-header h1 .ef-case-num { color: var(--ef-primary); }
    .ef-page-header .ef-subtitle { font-size: .85rem; color: var(--ef-text-muted); margin-top: .25rem; }
    .ef-breadcrumb { font-size: .8rem; color: var(--ef-text-muted); margin-bottom: .5rem; }
    .ef-breadcrumb a { color: var(--ef-primary); text-decoration: none; }
    .ef-breadcrumb a:hover { text-decoration: underline; }

    /* ── Section cards ── */
    .ef-section { border: 1px solid var(--ef-border); border-radius: var(--ef-radius); background: #fff; margin-bottom: 1rem; box-shadow: var(--ef-shadow); overflow: hidden; transition: box-shadow .2s; }
    .ef-section:hover { box-shadow: var(--ef-shadow-md); }
    .ef-section-header { padding: .875rem 1.25rem; display: flex; align-items: center; gap: .75rem; cursor: pointer; user-select: none; border-bottom: 1px solid transparent; transition: background .15s, border-color .15s; }
    .ef-section-header:hover { background: var(--ef-bg-subtle); }
    .ef-section-header.active { border-bottom-color: var(--ef-border); }
    .ef-section-num { width: 28px; height: 28px; border-radius: 50%; background: var(--ef-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }
    .ef-section-num.completed { background: #16a34a; }
    .ef-section-icon { color: var(--ef-primary); font-size: 1.1rem; flex-shrink: 0; }
    .ef-section-title { font-weight: 700; font-size: .95rem; color: var(--ef-text); flex: 1; }
    .ef-section-chevron { color: var(--ef-text-light); font-size: .85rem; transition: transform .2s; }
    .ef-section-header.active .ef-section-chevron { transform: rotate(180deg); }
    .ef-section-body { padding: 1.25rem; display: none; }
    .ef-section-body.show { display: block; }

    /* ── Form fields ── */
    .ef-field-group { margin-bottom: 1rem; }
    .ef-field-group:last-child { margin-bottom: 0; }
    .ef-label { display: block; font-size: .78rem; font-weight: 600; color: var(--ef-text); margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .03em; }
    .ef-hint { font-size: .72rem; color: var(--ef-text-muted); margin-top: .25rem; }

    /* ── Repeater rows ── */
    .ef-repeater-row { border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm); padding: 1rem; margin-bottom: .625rem; background: var(--ef-bg-subtle); position: relative; transition: border-color .15s, box-shadow .15s; }
    .ef-repeater-row:hover { border-color: var(--ef-border-hover); box-shadow: 0 2px 6px rgba(0,0,0,.04); }
    .ef-btn-remove { position: absolute; top: .5rem; right: .5rem; border: none; background: #fef2f2; color: #dc2626; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .8rem; transition: all .15s; }
    .ef-btn-remove:hover { background: #dc2626; color: #fff; }

    /* ── Add buttons ── */
    .ef-btn-add { border: 2px dashed var(--ef-border); border-radius: var(--ef-radius-sm); padding: .5rem .875rem; background: transparent; color: var(--ef-text-muted); font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .15s; display: inline-flex; align-items: center; gap: .4rem; }
    .ef-btn-add:hover { border-color: var(--ef-primary); color: var(--ef-primary); background: var(--ef-primary-light); }

    /* ── Item type badges ── */
    .ef-type-badge { font-size: .65rem; font-weight: 700; padding: .2rem .5rem; border-radius: 4px; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }
    .ef-type-product { background: #dbeafe; color: #1e40af; }
    .ef-type-part { background: #ede9fe; color: #6d28d9; }
    .ef-type-service { background: #fef3c7; color: #92400e; }
    .ef-type-other { background: #f1f5f9; color: #475569; }

    /* ── Empty states ── */
    .ef-empty { text-align: center; padding: 1.5rem 1rem; color: var(--ef-text-light); }
    .ef-empty i { font-size: 1.75rem; display: block; margin-bottom: .5rem; opacity: .5; }
    .ef-empty p { font-size: .82rem; margin: 0; }

    /* ── Sidebar ── */
    .ef-sidebar-sticky { position: sticky; top: 1rem; }

    .ef-summary-card { border: 1px solid var(--ef-border); border-radius: var(--ef-radius); background: #fff; box-shadow: var(--ef-shadow); overflow: hidden; margin-bottom: 1rem; }
    .ef-summary-header { padding: .75rem 1rem; background: var(--ef-bg-subtle); border-bottom: 1px solid var(--ef-border); display: flex; align-items: center; gap: .5rem; }
    .ef-summary-header i { color: var(--ef-primary); }
    .ef-summary-header span { font-weight: 700; font-size: .88rem; color: var(--ef-text); }
    .ef-summary-body { padding: 1rem; }

    .ef-total-row { display: flex; justify-content: space-between; padding: .35rem 0; font-size: .85rem; color: var(--ef-text); }
    .ef-total-row.grand { font-size: 1.05rem; font-weight: 800; border-top: 2px solid var(--ef-text); margin-top: .5rem; padding-top: .6rem; }
    .ef-total-empty { font-size: .8rem; color: var(--ef-text-light); text-align: center; padding: .5rem 0; }

    .ef-item-count { display: inline-flex; align-items: center; justify-content: center; background: var(--ef-primary-light); color: var(--ef-primary); border-radius: 999px; padding: .1rem .5rem; font-size: .7rem; font-weight: 700; margin-left: .25rem; }

    /* ── Action buttons ── */
    .ef-actions { display: flex; flex-direction: column; gap: .5rem; }
    .ef-btn-save { background: var(--ef-primary); color: #fff; border: none; border-radius: var(--ef-radius-sm); padding: .7rem 1rem; font-size: .88rem; font-weight: 700; cursor: pointer; transition: background .15s; display: flex; align-items: center; justify-content: center; gap: .5rem; }
    .ef-btn-save:hover { background: #4338ca; }
    .ef-btn-cancel { background: transparent; color: var(--ef-text-muted); border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm); padding: .55rem 1rem; font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .15s; text-align: center; text-decoration: none; display: block; }
    .ef-btn-cancel:hover { background: var(--ef-bg-subtle); color: var(--ef-text); text-decoration: none; }

    /* ── Responsive ── */
    @media (max-width: 991.98px) {
        .ef-sidebar-sticky { position: static; }
    }

    /* ── Line-total in item rows ── */
    .ef-line-total { font-size: .82rem; font-weight: 700; color: var(--ef-primary); white-space: nowrap; }

    /* ═══════════ TAB BAR ═══════════ */
    .ef-tab-bar {
        display: flex; gap: .25rem; padding: 0 0 .75rem; border-bottom: 2px solid var(--ef-border);
        margin-bottom: 1rem; overflow-x: auto;
    }
    .ef-tab-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .875rem;
        font-size: .82rem; font-weight: 600; color: var(--ef-text-muted); background: none;
        border: none; border-bottom: 2px solid transparent; margin-bottom: -2px;
        cursor: pointer; transition: all .15s; white-space: nowrap; border-radius: 6px 6px 0 0;
    }
    .ef-tab-btn:hover { color: var(--ef-primary); background: var(--ef-primary-light); }
    .ef-tab-btn.active { color: var(--ef-primary); border-bottom-color: var(--ef-primary); background: var(--ef-primary-light); }
    .ef-tab-badge {
        display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px;
        border-radius: 999px; font-size: .68rem; font-weight: 700; padding: 0 .35rem;
        background: var(--ef-border); color: var(--ef-text-muted);
    }
    .ef-tab-btn.active .ef-tab-badge { background: var(--ef-primary); color: #fff; }

    /* ═══════════ TAB PANELS ═══════════ */
    .ef-tab-panel { display: none; }
    .ef-tab-panel.active { display: block; }

    /* ═══════════ SEARCH BAR ═══════════ */
    .ef-search-bar {
        display: flex; align-items: center; gap: .5rem; padding: .625rem 0; margin-bottom: .5rem;
    }
    .ef-search-wrap {
        flex: 1; position: relative; display: flex; align-items: center;
        border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm);
        background: var(--ef-bg-subtle); transition: border-color .15s, box-shadow .15s;
    }
    .ef-search-wrap:focus-within { border-color: var(--ef-primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); background: #fff; }
    .ef-search-wrap .search-icon { padding: 0 .75rem; color: var(--ef-text-light); font-size: .9rem; flex-shrink: 0; }
    .ef-search-wrap input {
        flex: 1; border: none; background: transparent; padding: .45rem .5rem .45rem 0;
        font-size: .84rem; color: var(--ef-text); outline: none;
    }
    .ef-search-wrap input::placeholder { color: var(--ef-text-light); }

    /* ── Autocomplete dropdown ── */
    .ef-ac-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1060;
        background: #fff; border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm);
        box-shadow: var(--ef-shadow-md); max-height: 260px; overflow-y: auto;
        display: none; margin-top: 4px;
    }
    .ef-ac-dropdown.open { display: block; }
    .ef-ac-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: .55rem .875rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background .1s;
    }
    .ef-ac-item:last-child { border-bottom: none; }
    .ef-ac-item:hover { background: var(--ef-primary-light); }
    .ef-ac-item .ac-name { font-weight: 500; font-size: .84rem; color: var(--ef-text); }
    .ef-ac-item .ac-code { font-size: .72rem; color: var(--ef-text-muted); }
    .ef-ac-item .ac-price { font-weight: 700; font-size: .82rem; color: var(--ef-primary); white-space: nowrap; }
    .ef-ac-empty { padding: .875rem; text-align: center; color: var(--ef-text-light); font-size: .82rem; }

    /* ═══════════ ITEM CARDS ═══════════ */
    .ef-item-card {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem 1rem; border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm);
        background: #fff; margin-bottom: .5rem; transition: border-color .15s, box-shadow .15s;
    }
    .ef-item-card:hover { border-color: var(--ef-border-hover); box-shadow: 0 2px 6px rgba(0,0,0,.04); }

    .ef-item-icon {
        width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center;
        justify-content: center; font-size: 1rem; flex-shrink: 0;
    }
    .ef-item-icon.ic-part { background: #ede9fe; color: #6d28d9; }
    .ef-item-icon.ic-service { background: #fef3c7; color: #92400e; }
    .ef-item-icon.ic-product { background: #dbeafe; color: #1e40af; }
    .ef-item-icon.ic-other { background: #f1f5f9; color: #475569; }

    .ef-item-body { flex: 1; min-width: 0; }
    .ef-item-name { font-weight: 600; font-size: .88rem; color: var(--ef-text); }
    .ef-item-code { font-size: .72rem; color: var(--ef-text-muted); }

    .ef-item-controls {
        display: flex; align-items: center; gap: .5rem; flex-shrink: 0;
    }
    .ef-item-controls .qty-inp {
        width: 52px; text-align: center; border: 1px solid var(--ef-border); border-radius: 6px;
        padding: .3rem; font-size: .82rem; font-weight: 600; color: var(--ef-text);
    }
    .ef-item-controls .qty-inp:focus { border-color: var(--ef-primary); outline: none; box-shadow: 0 0 0 2px rgba(79,70,229,.12); }
    .ef-item-controls .price-grp {
        display: flex; align-items: center; border: 1px solid var(--ef-border); border-radius: 6px;
        overflow: hidden; background: #fff;
    }
    .ef-item-controls .price-grp:focus-within { border-color: var(--ef-primary); box-shadow: 0 0 0 2px rgba(79,70,229,.12); }
    .ef-item-controls .price-grp .cur { padding: .3rem .4rem; background: var(--ef-bg-subtle); font-size: .72rem; color: var(--ef-text-muted); font-weight: 600; border-right: 1px solid var(--ef-border); }
    .ef-item-controls .price-grp input {
        width: 80px; border: none; padding: .3rem .4rem; font-size: .82rem; text-align: right;
        color: var(--ef-text); outline: none; background: transparent;
    }

    .ef-item-total {
        min-width: 70px; text-align: right; font-weight: 700; font-size: .88rem;
        color: var(--ef-primary); white-space: nowrap;
    }

    .ef-item-remove {
        border: none; background: none; color: var(--ef-text-light); cursor: pointer;
        width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; transition: all .15s;
    }
    .ef-item-remove:hover { background: #fef2f2; color: #dc2626; }

    /* ── Section subtotal ── */
    .ef-subtotal-bar {
        display: flex; justify-content: space-between; align-items: center;
        padding: .625rem 1rem; background: var(--ef-bg-subtle); border-radius: var(--ef-radius-sm);
        border: 1px solid var(--ef-border); margin-top: .25rem; margin-bottom: .5rem;
    }
    .ef-subtotal-bar .lbl { font-size: .82rem; font-weight: 600; color: var(--ef-text-muted); }
    .ef-subtotal-bar .amt { font-size: .95rem; font-weight: 800; color: var(--ef-primary); }

    /* ── Improved device card ── */
    .ef-device-card {
        border: 1px solid var(--ef-border); border-radius: var(--ef-radius-sm);
        padding: .875rem 1rem; margin-bottom: .5rem; background: #fff; position: relative;
        transition: border-color .15s, box-shadow .15s;
    }
    .ef-device-card:hover { border-color: var(--ef-primary-border); box-shadow: 0 2px 8px rgba(79,70,229,.06); }
    .ef-device-header { display: flex; align-items: center; gap: .5rem; margin-bottom: .625rem; }
    .ef-device-icon { width: 32px; height: 32px; border-radius: 8px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: .95rem; }
    .ef-device-title { font-weight: 600; font-size: .85rem; color: var(--ef-text); }
    .ef-device-num { font-size: .72rem; font-weight: 700; color: var(--ef-text-light); }

    /* ── Responsive overrides ── */
    @media (max-width: 767.98px) {
        .ef-tab-bar { gap: .125rem; }
        .ef-tab-btn { padding: .4rem .5rem; font-size: .75rem; }
        .ef-search-bar { flex-wrap: wrap; }
        .ef-item-card { flex-wrap: wrap; gap: .5rem; }
        .ef-item-controls { width: 100%; justify-content: flex-start; }
        .ef-item-total { min-width: auto; }
    }
</style>
@endpush

<main class="dashboard-content container-fluid py-3">

{{-- ── Breadcrumb ── --}}
<div class="ef-breadcrumb">
    <a href="{{ route('tenant.estimates.index', ['business' => $tenantSlug]) }}">{{ __('Estimates') }}</a>
    <span class="mx-1">/</span>
    @if ($isEdit)
        <a href="{{ $backUrl }}">{{ $estimate->case_number ?? '#'.$estimate->id }}</a>
        <span class="mx-1">/</span>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New Estimate') }}</span>
    @endif
</div>

{{-- ── Page header ── --}}
<div class="ef-page-header">
    <h1>
        @if ($isEdit)
            {{ __('Edit Estimate') }} <span class="ef-case-num">{{ $estimate->case_number ?? '#'.$estimate->id }}</span>
        @else
            {{ __('New Estimate') }}
        @endif
    </h1>
    <div class="ef-subtitle">{{ $isEdit ? __('Update the estimate details, devices, and line items below.') : __('Fill in the sections below to create a repair estimate.') }}</div>
</div>

{{-- ── Validation errors ── --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.85rem;">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>{{ __('Please fix the following errors:') }}</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $formAction }}" id="estimateForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        {{-- ════════════ LEFT COLUMN ════════════ --}}
        <div class="col-lg-8">

            {{-- ── Section 1: Estimate Details ── --}}
            <div class="ef-section" id="section-details">
                <div class="ef-section-header active" onclick="toggleSection('details')">
                    <span class="ef-section-num" id="num-details">1</span>
                    <i class="bi bi-file-earmark-text ef-section-icon"></i>
                    <span class="ef-section-title">{{ __('Estimate Details') }}</span>
                    <i class="bi bi-chevron-down ef-section-chevron"></i>
                </div>
                <div class="ef-section-body show" id="body-details">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="case_number">{{ __('Case Number') }}</label>
                                <input type="text" class="form-control form-control-sm" id="case_number" name="case_number"
                                       value="{{ old('case_number', $estimate?->case_number ?? '') }}"
                                       placeholder="{{ __('Auto-generated if empty') }}">
                                <div class="ef-hint">{{ __('Leave blank to auto-generate') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="title">{{ __('Title') }}</label>
                                <input type="text" class="form-control form-control-sm" id="title" name="title"
                                       value="{{ old('title', $estimate?->title ?? '') }}"
                                       placeholder="{{ __('e.g. Screen replacement iPhone 14') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="pickup_date"><i class="bi bi-calendar-event me-1"></i>{{ __('Pickup Date') }}</label>
                                <input type="date" class="form-control form-control-sm" id="pickup_date" name="pickup_date"
                                       value="{{ old('pickup_date', $estimate?->pickup_date?->format('Y-m-d') ?? '') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="delivery_date"><i class="bi bi-calendar-check me-1"></i>{{ __('Delivery Date') }}</label>
                                <input type="date" class="form-control form-control-sm" id="delivery_date" name="delivery_date"
                                       value="{{ old('delivery_date', $estimate?->delivery_date?->format('Y-m-d') ?? '') }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="ef-field-group">
                                <label class="ef-label" for="case_detail">{{ __('Description') }}</label>
                                <textarea class="form-control form-control-sm" id="case_detail" name="case_detail" rows="3"
                                          placeholder="{{ __('Describe what the customer needs repaired…') }}">{{ old('case_detail', $estimate?->case_detail ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Customer & Technician (side by side) ── --}}
            <div class="ef-section" id="section-people">
                <div class="ef-section-header active" onclick="toggleSection('people')">
                    <span class="ef-section-num" id="num-people">2</span>
                    <i class="bi bi-people ef-section-icon"></i>
                    <span class="ef-section-title">{{ __('Customer & Technician') }}</span>
                    <i class="bi bi-chevron-down ef-section-chevron"></i>
                </div>
                <div class="ef-section-body show" id="body-people">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="customer_id"><i class="bi bi-person me-1"></i>{{ __('Customer') }}</label>
                                <select class="form-select form-select-sm" id="customer_id" name="customer_id">
                                    <option value="">{{ __('Select customer…') }}</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}"
                                            {{ old('customer_id', $estimate?->customer_id) == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} ({{ $c->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ef-field-group">
                                <label class="ef-label" for="assigned_technician_id"><i class="bi bi-person-gear me-1"></i>{{ __('Technician') }}</label>
                                <select class="form-select form-select-sm" id="assigned_technician_id" name="assigned_technician_id">
                                    <option value="">{{ __('Select technician…') }}</option>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}"
                                            {{ old('assigned_technician_id', $estimate?->assigned_technician_id) == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 3: Devices ── --}}
            <div class="ef-section" id="section-devices">
                <div class="ef-section-header active" onclick="toggleSection('devices')">
                    <span class="ef-section-num" id="num-devices">3</span>
                    <i class="bi bi-phone ef-section-icon"></i>
                    <span class="ef-section-title">{{ __('Devices') }} <span class="ef-item-count" id="deviceCount">0</span></span>
                    <i class="bi bi-chevron-down ef-section-chevron"></i>
                </div>
                <div class="ef-section-body show" id="body-devices">
                    <div id="devicesContainer">
                        {{-- Rows injected by JS --}}
                    </div>
                    <div id="devicesEmpty" class="ef-empty">
                        <i class="bi bi-phone"></i>
                        <p>{{ __('No devices added yet. Add a device to link it to this estimate.') }}</p>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="ef-btn-add" onclick="addDeviceRow()">
                            <i class="bi bi-plus-lg"></i>{{ __('Add Device') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Section 4: Line Items (Tabbed) ── --}}
            <div class="ef-section" id="section-items">
                <div class="ef-section-header active" onclick="toggleSection('items')">
                    <span class="ef-section-num" id="num-items">4</span>
                    <i class="bi bi-receipt ef-section-icon"></i>
                    <span class="ef-section-title">{{ __('Line Items') }} <span class="ef-item-count" id="itemCount">0</span></span>
                    <i class="bi bi-chevron-down ef-section-chevron"></i>
                </div>
                <div class="ef-section-body show" id="body-items">
                    {{-- Tab Bar --}}
                    <div class="ef-tab-bar">
                        <button type="button" class="ef-tab-btn active" data-tab="part" onclick="switchItemTab('part')">
                            <i class="bi bi-cpu"></i> {{ __('Parts') }}
                            <span class="ef-tab-badge" id="tab-count-part">0</span>
                        </button>
                        <button type="button" class="ef-tab-btn" data-tab="service" onclick="switchItemTab('service')">
                            <i class="bi bi-wrench"></i> {{ __('Services') }}
                            <span class="ef-tab-badge" id="tab-count-service">0</span>
                        </button>
                        <button type="button" class="ef-tab-btn" data-tab="product" onclick="switchItemTab('product')">
                            <i class="bi bi-box"></i> {{ __('Products') }}
                            <span class="ef-tab-badge" id="tab-count-product">0</span>
                        </button>
                        <button type="button" class="ef-tab-btn" data-tab="other" onclick="switchItemTab('other')">
                            <i class="bi bi-plus-circle"></i> {{ __('Other') }}
                            <span class="ef-tab-badge" id="tab-count-other">0</span>
                        </button>
                    </div>

                    {{-- ── Parts Tab ── --}}
                    <div class="ef-tab-panel active" id="panel-part">
                        <div class="ef-search-bar">
                            <div class="ef-search-wrap">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" id="partSearchInput" placeholder="{{ __('Search part by name or SKU…') }}" autocomplete="off" />
                                <div class="ef-ac-dropdown" id="partAcDropdown"></div>
                            </div>
                            <button type="button" class="btn btn-sm px-3" style="background:var(--ef-primary);color:#fff;font-weight:600;border-radius:var(--ef-radius-sm);" onclick="addItemRow('part')">
                                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Part') }}
                            </button>
                        </div>
                        <div id="container-part"></div>
                        <div class="ef-empty" id="empty-part">
                            <i class="bi bi-cpu"></i>
                            <p>{{ __('No parts added yet. Search above or click "Add Part".') }}</p>
                        </div>
                        <div class="ef-subtotal-bar" id="subtotal-part" style="display:none;">
                            <span class="lbl">{{ __('Parts Subtotal') }}</span>
                            <span class="amt" id="subtotal-val-part">$0.00</span>
                        </div>
                    </div>

                    {{-- ── Services Tab ── --}}
                    <div class="ef-tab-panel" id="panel-service">
                        <div class="ef-search-bar">
                            <div class="ef-search-wrap">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" id="serviceSearchInput" placeholder="{{ __('Search service by name or code…') }}" autocomplete="off" />
                                <div class="ef-ac-dropdown" id="serviceAcDropdown"></div>
                            </div>
                            <button type="button" class="btn btn-sm px-3" style="background:var(--ef-primary);color:#fff;font-weight:600;border-radius:var(--ef-radius-sm);" onclick="addItemRow('service')">
                                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Service') }}
                            </button>
                        </div>
                        <div id="container-service"></div>
                        <div class="ef-empty" id="empty-service">
                            <i class="bi bi-wrench"></i>
                            <p>{{ __('No services added yet. Search above or click "Add Service".') }}</p>
                        </div>
                        <div class="ef-subtotal-bar" id="subtotal-service" style="display:none;">
                            <span class="lbl">{{ __('Services Subtotal') }}</span>
                            <span class="amt" id="subtotal-val-service">$0.00</span>
                        </div>
                    </div>

                    {{-- ── Products Tab ── --}}
                    <div class="ef-tab-panel" id="panel-product">
                        <div class="ef-search-bar">
                            <div class="ef-search-wrap" style="opacity:.5;pointer-events:none;">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" placeholder="{{ __('Manual entry — add below') }}" disabled />
                            </div>
                            <button type="button" class="btn btn-sm px-3" style="background:var(--ef-primary);color:#fff;font-weight:600;border-radius:var(--ef-radius-sm);" onclick="addItemRow('product')">
                                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Product') }}
                            </button>
                        </div>
                        <div id="container-product"></div>
                        <div class="ef-empty" id="empty-product">
                            <i class="bi bi-box"></i>
                            <p>{{ __('No products added yet. Click "Add Product" to create a line item.') }}</p>
                        </div>
                        <div class="ef-subtotal-bar" id="subtotal-product" style="display:none;">
                            <span class="lbl">{{ __('Products Subtotal') }}</span>
                            <span class="amt" id="subtotal-val-product">$0.00</span>
                        </div>
                    </div>

                    {{-- ── Other Tab ── --}}
                    <div class="ef-tab-panel" id="panel-other">
                        <div class="ef-search-bar">
                            <div class="ef-search-wrap" style="opacity:.5;pointer-events:none;">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" placeholder="{{ __('Manual entry — add below') }}" disabled />
                            </div>
                            <button type="button" class="btn btn-sm px-3" style="background:var(--ef-primary);color:#fff;font-weight:600;border-radius:var(--ef-radius-sm);" onclick="addItemRow('other')">
                                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Extra') }}
                            </button>
                        </div>
                        <div id="container-other"></div>
                        <div class="ef-empty" id="empty-other">
                            <i class="bi bi-plus-circle"></i>
                            <p>{{ __('No extras added yet. Click "Add Extra" for a miscellaneous charge.') }}</p>
                        </div>
                        <div class="ef-subtotal-bar" id="subtotal-other" style="display:none;">
                            <span class="lbl">{{ __('Other Subtotal') }}</span>
                            <span class="amt" id="subtotal-val-other">$0.00</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /col-lg-8 --}}

        {{-- ════════════ RIGHT COLUMN (Sticky Sidebar) ════════════ --}}
        <div class="col-lg-4">
            <div class="ef-sidebar-sticky">

                {{-- ── Live Financial Summary ── --}}
                <div class="ef-summary-card">
                    <div class="ef-summary-header">
                        <i class="bi bi-calculator"></i>
                        <span>{{ __('Estimate Total') }}</span>
                    </div>
                    <div class="ef-summary-body" id="liveSummary">
                        <div class="ef-total-empty" id="summaryEmpty">{{ __('Add items to see the total') }}</div>
                        <div id="summaryRows" style="display:none;"></div>
                        <div class="ef-total-row grand" id="summaryGrandTotal" style="display:none;">
                            <span>{{ __('Total') }}</span>
                            <span id="grandTotalValue">$0.00</span>
                        </div>
                    </div>
                </div>

                {{-- ── Order Notes ── --}}
                <div class="ef-summary-card">
                    <div class="ef-summary-header">
                        <i class="bi bi-sticky"></i>
                        <span>{{ __('Internal Notes') }}</span>
                    </div>
                    <div class="ef-summary-body">
                        <textarea class="form-control form-control-sm" name="order_notes" rows="4"
                                  placeholder="{{ __('Notes visible only to staff…') }}" style="border-color:var(--ef-border); font-size:.82rem;">{{ old('order_notes', $estimate?->case_detail ?? '') }}</textarea>
                    </div>
                </div>

                {{-- ── Actions ── --}}
                <div class="ef-actions">
                    <button type="submit" class="ef-btn-save">
                        <i class="bi bi-{{ $isEdit ? 'check-lg' : 'plus-lg' }}"></i>
                        {{ $isEdit ? __('Update Estimate') : __('Create Estimate') }}
                    </button>
                    <a href="{{ $backUrl }}" class="ef-btn-cancel">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>{{-- /col-lg-4 --}}
    </div>{{-- /row --}}
</form>

@push('page-scripts')
@php
    $jsDeviceOptions = $customerDevices->map(fn($d) => ['id' => $d->id, 'label' => $d->label ?? 'Device #'.$d->id])->values();
    $jsPartOptions = $parts->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku ?? '', 'price' => round(($p->price_amount_cents ?? 0) / 100, 2)])->values();
    $jsServiceOptions = $services->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->service_code ?? '', 'price' => round(($s->base_price_amount_cents ?? 0) / 100, 2)])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Data from PHP ──
    const existingDevices = @json($existingDevices);
    const customerDeviceOptions = @json($jsDeviceOptions);
    const existingItems = @json($existingItems);
    const partOptions = @json($jsPartOptions);
    const serviceOptions = @json($jsServiceOptions);

    let deviceIdx = 0;
    let itemIdx = 0;
    const itemTypes = ['part', 'service', 'product', 'other'];
    let activeTab = 'part';

    // ── Section toggle ──
    window.toggleSection = function(sectionId) {
        const header = document.querySelector('#section-' + sectionId + ' .ef-section-header');
        const body = document.getElementById('body-' + sectionId);
        if (!header || !body) return;
        header.classList.toggle('active');
        body.classList.toggle('show');
    };

    // ── Tab switching ──
    window.switchItemTab = function(tab) {
        activeTab = tab;
        document.querySelectorAll('.ef-tab-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        document.querySelectorAll('.ef-tab-panel').forEach(function(panel) {
            panel.classList.toggle('active', panel.id === 'panel-' + tab);
        });
    };

    // ── Helpers ──
    function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function escAttr(str) { return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function fmtMoney(n) { return '$' + (parseFloat(n) || 0).toFixed(2); }

    function updateCounts() {
        const dc = document.querySelectorAll('#devicesContainer .ef-device-card').length;
        document.getElementById('deviceCount').textContent = dc;
        document.getElementById('devicesEmpty').style.display = dc > 0 ? 'none' : 'block';
        const numDevices = document.getElementById('num-devices');
        if (dc > 0) numDevices.classList.add('completed'); else numDevices.classList.remove('completed');

        let totalItems = 0;
        itemTypes.forEach(function(type) {
            const count = document.querySelectorAll('#container-' + type + ' .ef-item-card').length;
            totalItems += count;
            document.getElementById('tab-count-' + type).textContent = count;
            document.getElementById('empty-' + type).style.display = count > 0 ? 'none' : 'block';
        });
        document.getElementById('itemCount').textContent = totalItems;
        const numItems = document.getElementById('num-items');
        if (totalItems > 0) numItems.classList.add('completed'); else numItems.classList.remove('completed');
    }

    function updateSubtotals() {
        let grandTotal = 0;
        const summaryRows = document.getElementById('summaryRows');
        const summaryEmpty = document.getElementById('summaryEmpty');
        const grandTotalEl = document.getElementById('summaryGrandTotal');
        summaryRows.innerHTML = '';

        itemTypes.forEach(function(type) {
            const cards = document.querySelectorAll('#container-' + type + ' .ef-item-card');
            let sub = 0;
            cards.forEach(function(card) {
                const q = parseInt(card.querySelector('.qty-inp').value) || 0;
                const p = parseFloat(card.querySelector('.price-inp').value) || 0;
                const lineTotal = q * p;
                sub += lineTotal;
                const totalEl = card.querySelector('.ef-item-total');
                if (totalEl) totalEl.textContent = fmtMoney(lineTotal);
            });

            const subtotalBar = document.getElementById('subtotal-' + type);
            const subtotalVal = document.getElementById('subtotal-val-' + type);
            if (cards.length > 0) {
                subtotalBar.style.display = 'flex';
                subtotalVal.textContent = fmtMoney(sub);

                // Sidebar row per type
                const typeLabels = { part: 'Parts', service: 'Services', product: 'Products', other: 'Other' };
                const div = document.createElement('div');
                div.className = 'ef-total-row';
                div.innerHTML = '<span>' + typeLabels[type] + ' (' + cards.length + ')</span><span>' + fmtMoney(sub) + '</span>';
                summaryRows.appendChild(div);
            } else {
                subtotalBar.style.display = 'none';
            }
            grandTotal += sub;
        });

        if (grandTotal > 0 || summaryRows.children.length > 0) {
            summaryEmpty.style.display = 'none';
            summaryRows.style.display = 'block';
            grandTotalEl.style.display = 'flex';
            document.getElementById('grandTotalValue').textContent = fmtMoney(grandTotal);
        } else {
            summaryEmpty.style.display = 'block';
            summaryRows.style.display = 'none';
            grandTotalEl.style.display = 'none';
        }
    }

    // ── Autocomplete ──
    function setupAutocomplete(inputId, dropdownId, options, nameKey, codeKey, onSelect) {
        const input = document.getElementById(inputId);
        const dropdown = document.getElementById(dropdownId);
        if (!input || !dropdown) return;

        input.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            if (q.length < 1) { dropdown.classList.remove('open'); return; }
            const matches = options.filter(function(o) {
                return (o[nameKey] || '').toLowerCase().includes(q) || (o[codeKey] || '').toLowerCase().includes(q);
            }).slice(0, 10);

            dropdown.innerHTML = '';
            if (matches.length === 0) {
                dropdown.innerHTML = '<div class="ef-ac-empty">{{ __("No matches found") }}</div>';
            } else {
                matches.forEach(function(o) {
                    const item = document.createElement('div');
                    item.className = 'ef-ac-item';
                    item.innerHTML = '<div><div class="ac-name">' + escHtml(o[nameKey]) + '</div><div class="ac-code">' + escHtml(o[codeKey] || '') + '</div></div><span class="ac-price">' + fmtMoney(o.price) + '</span>';
                    item.addEventListener('click', function() {
                        onSelect(o);
                        input.value = '';
                        dropdown.classList.remove('open');
                    });
                    dropdown.appendChild(item);
                });
            }
            dropdown.classList.add('open');
        });

        input.addEventListener('focus', function() {
            if (this.value.trim().length >= 1) input.dispatchEvent(new Event('input'));
        });
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.remove('open');
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') dropdown.classList.remove('open');
        });
    }

    // Parts autocomplete
    setupAutocomplete('partSearchInput', 'partAcDropdown', partOptions, 'name', 'sku', function(part) {
        addItemRow('part', { name: part.name, qty: 1, unit_price_cents: part.price * 100 });
    });

    // Services autocomplete
    setupAutocomplete('serviceSearchInput', 'serviceAcDropdown', serviceOptions, 'name', 'code', function(service) {
        addItemRow('service', { name: service.name, qty: 1, unit_price_cents: service.price * 100 });
    });

    // ── Device Row (improved) ──
    window.addDeviceRow = function(data) {
        data = data || {};
        const idx = deviceIdx++;
        const container = document.getElementById('devicesContainer');
        const div = document.createElement('div');
        div.className = 'ef-device-card';
        div.id = 'device-row-' + idx;

        let deviceOpts = '<option value="">' + '{{ __("Select device…") }}' + '</option>';
        customerDeviceOptions.forEach(function(d) {
            const sel = data.customer_device_id == d.id ? 'selected' : '';
            deviceOpts += '<option value="' + d.id + '" ' + sel + '>' + escHtml(d.label) + '</option>';
        });

        div.innerHTML = ''
            + '<button type="button" class="ef-btn-remove" onclick="removeRow(\'device-row-' + idx + '\')"><i class="bi bi-x-lg"></i></button>'
            + '<div class="ef-device-header">'
            + '  <div class="ef-device-icon"><i class="bi bi-phone"></i></div>'
            + '  <span class="ef-device-title">{{ __("Device") }}</span>'
            + '  <span class="ef-device-num">#' + (idx + 1) + '</span>'
            + '</div>'
            + '<div class="row g-2">'
            + '  <div class="col-md-6"><label class="ef-label">{{ __("Existing Device") }}</label><select class="form-select form-select-sm" name="devices[' + idx + '][customer_device_id]">' + deviceOpts + '</select></div>'
            + '  <div class="col-md-6"><label class="ef-label">{{ __("Serial / IMEI") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][serial]" value="' + escAttr(data.serial || '') + '" placeholder="e.g. 35420911…"></div>'
            + '  <div class="col-md-4"><label class="ef-label">{{ __("PIN / Passcode") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][pin]" value="' + escAttr(data.pin || '') + '" placeholder="{{ __("Optional") }}"></div>'
            + '  <div class="col-md-8"><label class="ef-label">{{ __("Notes") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][notes]" value="' + escAttr(data.notes || '') + '" placeholder="{{ __("e.g. Cracked screen, water damage…") }}"></div>'
            + '</div>';
        container.appendChild(div);
        updateCounts();
    };

    // ── Item Row (card-based) ──
    window.addItemRow = function(type, data) {
        data = data || {};
        if (typeof type === 'object') { data = type; type = data.type || 'other'; }
        const idx = itemIdx++;
        const container = document.getElementById('container-' + type);
        if (!container) return;

        const icons = { part: 'bi-cpu', service: 'bi-wrench', product: 'bi-box', other: 'bi-plus-circle' };
        const iconCls = { part: 'ic-part', service: 'ic-service', product: 'ic-product', other: 'ic-other' };
        const priceDollars = data.unit_price_cents ? (parseInt(data.unit_price_cents) / 100).toFixed(2) : '';
        const qty = data.qty || 1;
        const lineTotal = (parseFloat(priceDollars) || 0) * qty;

        const card = document.createElement('div');
        card.className = 'ef-item-card';
        card.id = 'item-row-' + idx;
        card.innerHTML = ''
            + '<input type="hidden" name="items[' + idx + '][type]" value="' + type + '">'
            + '<div class="ef-item-icon ' + (iconCls[type] || iconCls.other) + '"><i class="bi ' + (icons[type] || icons.other) + '"></i></div>'
            + '<div class="ef-item-body">'
            + '  <input type="text" class="form-control form-control-sm item-name" name="items[' + idx + '][name]" value="' + escAttr(data.name || '') + '" placeholder="{{ __("Item name") }}" required style="font-weight:600;border:none;padding:0;font-size:.88rem;background:transparent;">'
            + '</div>'
            + '<div class="ef-item-controls">'
            + '  <input type="number" class="qty-inp" name="items[' + idx + '][qty]" value="' + qty + '" min="1" required>'
            + '  <div class="price-grp">'
            + '    <span class="cur">$</span>'
            + '    <input type="number" class="price-inp" name="items[' + idx + '][unit_price_dollars]" value="' + priceDollars + '" step="0.01" min="0" placeholder="0.00" required>'
            + '  </div>'
            + '</div>'
            + '<div class="ef-item-total">' + fmtMoney(lineTotal) + '</div>'
            + '<button type="button" class="ef-item-remove" onclick="removeRow(\'item-row-' + idx + '\')"><i class="bi bi-trash"></i></button>';

        container.appendChild(card);

        // Live calculation
        const qtyInp = card.querySelector('.qty-inp');
        const priceInp = card.querySelector('.price-inp');
        function recalc() { updateSubtotals(); }
        qtyInp.addEventListener('input', recalc);
        priceInp.addEventListener('input', recalc);

        // Switch to this tab
        switchItemTab(type);
        updateCounts();
        updateSubtotals();

        // Focus the name field if empty
        const nameInput = card.querySelector('.item-name');
        if (!data.name) setTimeout(function() { nameInput.focus(); }, 50);
    };

    // ── Remove row (with animation) ──
    window.removeRow = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            el.style.transition = 'opacity .2s, transform .2s';
            setTimeout(function() {
                el.remove();
                updateCounts();
                updateSubtotals();
            }, 200);
        }
    };

    // ── Populate existing data ──
    if (existingDevices.length) {
        existingDevices.forEach(function(d) { window.addDeviceRow(d); });
    }
    if (existingItems.length) {
        existingItems.forEach(function(item) { window.addItemRow(item); });
    }

    // ── Check customer/technician for section badge ──
    function checkPeopleSection() {
        const custVal = document.getElementById('customer_id').value;
        const numPeople = document.getElementById('num-people');
        if (custVal) numPeople.classList.add('completed'); else numPeople.classList.remove('completed');
    }
    document.getElementById('customer_id').addEventListener('change', checkPeopleSection);
    checkPeopleSection();

    // ── Check details section badge ──
    function checkDetailsSection() {
        const titleVal = document.getElementById('title').value.trim();
        const numDetails = document.getElementById('num-details');
        if (titleVal) numDetails.classList.add('completed'); else numDetails.classList.remove('completed');
    }
    document.getElementById('title').addEventListener('input', checkDetailsSection);
    checkDetailsSection();

    // Initial counts
    updateCounts();
    updateSubtotals();
});
</script>
@endpush

</main>

@endsection

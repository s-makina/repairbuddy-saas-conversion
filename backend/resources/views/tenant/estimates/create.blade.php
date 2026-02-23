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

            {{-- ── Section 4: Line Items ── --}}
            <div class="ef-section" id="section-items">
                <div class="ef-section-header active" onclick="toggleSection('items')">
                    <span class="ef-section-num" id="num-items">4</span>
                    <i class="bi bi-receipt ef-section-icon"></i>
                    <span class="ef-section-title">{{ __('Line Items') }} <span class="ef-item-count" id="itemCount">0</span></span>
                    <i class="bi bi-chevron-down ef-section-chevron"></i>
                </div>
                <div class="ef-section-body show" id="body-items">
                    <div id="itemsContainer">
                        {{-- Rows injected by JS --}}
                    </div>
                    <div id="itemsEmpty" class="ef-empty">
                        <i class="bi bi-receipt"></i>
                        <p>{{ __('No line items yet. Add products, parts, services, or extras below.') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="ef-btn-add" onclick="addItemRow('product')">
                            <i class="bi bi-box"></i>{{ __('Product') }}
                        </button>
                        <button type="button" class="ef-btn-add" onclick="addItemRow('part')">
                            <i class="bi bi-cpu"></i>{{ __('Part') }}
                        </button>
                        <button type="button" class="ef-btn-add" onclick="addItemRow('service')">
                            <i class="bi bi-wrench"></i>{{ __('Service') }}
                        </button>
                        <button type="button" class="ef-btn-add" onclick="addItemRow('other')">
                            <i class="bi bi-plus-circle"></i>{{ __('Extra') }}
                        </button>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Data from PHP ──
    const existingDevices = @json($existingDevices);
    const customerDeviceOptions = @json($customerDevices->map(fn($d) => ['id' => $d->id, 'label' => $d->label ?? 'Device #'.$d->id])->values());
    const existingItems = @json($existingItems);
    const partOptions = @json($parts->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values());
    const serviceOptions = @json($services->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values());

    let deviceIdx = 0;
    let itemIdx = 0;

    // ── Section toggle ──
    window.toggleSection = function(sectionId) {
        const header = document.querySelector('#section-' + sectionId + ' .ef-section-header');
        const body = document.getElementById('body-' + sectionId);
        if (!header || !body) return;
        header.classList.toggle('active');
        body.classList.toggle('show');
    };

    // ── Helpers ──
    function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function escAttr(str) { return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function updateCounts() {
        const dc = document.querySelectorAll('#devicesContainer .ef-repeater-row').length;
        const ic = document.querySelectorAll('#itemsContainer .ef-repeater-row').length;
        document.getElementById('deviceCount').textContent = dc;
        document.getElementById('itemCount').textContent = ic;
        document.getElementById('devicesEmpty').style.display = dc > 0 ? 'none' : 'block';
        document.getElementById('itemsEmpty').style.display = ic > 0 ? 'none' : 'block';

        // Update section number badges
        const numDevices = document.getElementById('num-devices');
        const numItems = document.getElementById('num-items');
        if (dc > 0) numDevices.classList.add('completed'); else numDevices.classList.remove('completed');
        if (ic > 0) numItems.classList.add('completed'); else numItems.classList.remove('completed');
    }

    function updateSidebar() {
        const rows = document.querySelectorAll('#itemsContainer .ef-repeater-row');
        let grandTotal = 0;
        const summaryRows = document.getElementById('summaryRows');
        const summaryEmpty = document.getElementById('summaryEmpty');
        const grandTotalEl = document.getElementById('summaryGrandTotal');

        summaryRows.innerHTML = '';

        rows.forEach(function(row) {
            const nameEl = row.querySelector('input[type="text"][name*="[name]"]');
            const qtyEl = row.querySelector('input[name*="[qty]"]');
            const priceEl = row.querySelector('.item-price');
            if (!nameEl || !qtyEl || !priceEl) return;

            const name = nameEl.value.trim() || 'Unnamed';
            const q = parseInt(qtyEl.value) || 0;
            const p = parseFloat(priceEl.value) || 0;
            const lineTotal = q * p;
            grandTotal += lineTotal;

            const div = document.createElement('div');
            div.className = 'ef-total-row';
            div.innerHTML = '<span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escHtml(name) + ' ×' + q + '</span><span>$' + lineTotal.toFixed(2) + '</span>';
            summaryRows.appendChild(div);
        });

        if (rows.length > 0) {
            summaryEmpty.style.display = 'none';
            summaryRows.style.display = 'block';
            grandTotalEl.style.display = 'flex';
            document.getElementById('grandTotalValue').textContent = '$' + grandTotal.toFixed(2);
        } else {
            summaryEmpty.style.display = 'block';
            summaryRows.style.display = 'none';
            grandTotalEl.style.display = 'none';
        }
    }

    // ── Device Row ──
    window.addDeviceRow = function(data) {
        data = data || {};
        const idx = deviceIdx++;
        const container = document.getElementById('devicesContainer');
        const div = document.createElement('div');
        div.className = 'ef-repeater-row';
        div.id = 'device-row-' + idx;

        let deviceOpts = '<option value="">' + '{{ __("Select device…") }}' + '</option>';
        customerDeviceOptions.forEach(function(d) {
            const sel = data.customer_device_id == d.id ? 'selected' : '';
            deviceOpts += '<option value="' + d.id + '" ' + sel + '>' + escHtml(d.label) + '</option>';
        });

        div.innerHTML = '<button type="button" class="ef-btn-remove" onclick="removeRow(\'device-row-' + idx + '\')"><i class="bi bi-x-lg"></i></button>'
            + '<div class="row g-2">'
            + '<div class="col-md-6"><label class="ef-label">{{ __("Device") }}</label><select class="form-select form-select-sm" name="devices[' + idx + '][customer_device_id]">' + deviceOpts + '</select></div>'
            + '<div class="col-md-6"><label class="ef-label">{{ __("Serial / IMEI") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][serial]" value="' + escAttr(data.serial || '') + '" placeholder="e.g. 35420911…"></div>'
            + '<div class="col-md-4"><label class="ef-label">{{ __("PIN") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][pin]" value="' + escAttr(data.pin || '') + '" placeholder="Optional"></div>'
            + '<div class="col-md-8"><label class="ef-label">{{ __("Notes") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][notes]" value="' + escAttr(data.notes || '') + '" placeholder="e.g. Cracked screen, water damage…"></div>'
            + '</div>';
        container.appendChild(div);
        updateCounts();
    };

    // ── Item Row ──
    window.addItemRow = function(type, data) {
        data = data || {};
        if (typeof type === 'object') { data = type; type = data.type || 'other'; }
        const idx = itemIdx++;
        const container = document.getElementById('itemsContainer');
        const div = document.createElement('div');
        div.className = 'ef-repeater-row';
        div.id = 'item-row-' + idx;

        const typeLabels = { product: '{{ __("Product") }}', part: '{{ __("Part") }}', service: '{{ __("Service") }}', other: '{{ __("Extra") }}' };
        const typeCssClass = { product: 'ef-type-product', part: 'ef-type-part', service: 'ef-type-service', other: 'ef-type-other' };
        const typeLabel = typeLabels[type] || typeLabels.other;
        const typeCls = typeCssClass[type] || typeCssClass.other;

        const priceDollars = data.unit_price_cents ? (parseInt(data.unit_price_cents) / 100).toFixed(2) : '';

        div.innerHTML = '<button type="button" class="ef-btn-remove" onclick="removeRow(\'item-row-' + idx + '\')"><i class="bi bi-x-lg"></i></button>'
            + '<div class="d-flex align-items-center gap-2 mb-2"><span class="ef-type-badge ' + typeCls + '">' + typeLabel + '</span><span class="ef-line-total" id="lt-' + idx + '">$0.00</span></div>'
            + '<input type="hidden" name="items[' + idx + '][type]" value="' + type + '">'
            + '<div class="row g-2 align-items-end">'
            + '<div class="col-md-5"><label class="ef-label">{{ __("Name") }}</label><input type="text" class="form-control form-control-sm" name="items[' + idx + '][name]" value="' + escAttr(data.name || '') + '" placeholder="Item name" required></div>'
            + '<div class="col-md-2"><label class="ef-label">{{ __("Qty") }}</label><input type="number" class="form-control form-control-sm" name="items[' + idx + '][qty]" value="' + (data.qty || 1) + '" min="1" required></div>'
            + '<div class="col-md-3"><label class="ef-label">{{ __("Unit Price") }}</label><div class="input-group input-group-sm"><span class="input-group-text" style="font-size:.78rem;">$</span><input type="number" class="form-control form-control-sm item-price" name="items[' + idx + '][unit_price_dollars]" value="' + priceDollars + '" step="0.01" min="0" placeholder="0.00" required></div></div>'
            + '</div>';
        container.appendChild(div);

        // Live line total + sidebar update
        const qtyInput = div.querySelector('input[name="items[' + idx + '][qty]"]');
        const priceInput = div.querySelector('.item-price');
        const nameInput = div.querySelector('input[name="items[' + idx + '][name]"]');
        const totalSpan = document.getElementById('lt-' + idx);

        function updateLineTotal() {
            const q = parseInt(qtyInput.value) || 0;
            const p = parseFloat(priceInput.value) || 0;
            totalSpan.textContent = '$' + (q * p).toFixed(2);
            updateSidebar();
        }
        qtyInput.addEventListener('input', updateLineTotal);
        priceInput.addEventListener('input', updateLineTotal);
        nameInput.addEventListener('input', updateSidebar);
        updateLineTotal();
        updateCounts();
    };

    // ── Remove row ──
    window.removeRow = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            el.style.transition = 'opacity .2s, transform .2s';
            setTimeout(function() {
                el.remove();
                updateCounts();
                updateSidebar();
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
    updateSidebar();
});
</script>
@endpush

</main>

@endsection

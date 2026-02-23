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
        --rb-primary: #3B82F6;
        --rb-primary-dark: #1D4ED8;
        --rb-hero-bg: radial-gradient(circle at center, #4b5563 0%, #1f2937 100%);
        --rb-card-border: #e2e8f0;
        --rb-text-muted: #64748b;
        --rb-text-dark: #0f172a;
    }
    .est-hero { background: var(--rb-hero-bg); border-radius: 16px; padding: 1.75rem 2rem; color: #fff; margin-bottom: 1.75rem; }
    .est-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .25rem; color: #fff !important; }
    .est-hero h2 span { color: var(--rb-primary); }
    .est-hero .subtitle { font-size: .82rem; opacity: .75; font-family: monospace; }

    .form-card { border: 1px solid var(--rb-card-border); border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); margin-bottom: 1.25rem; background: #fff; overflow: hidden; }
    .form-card .card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,.04); padding: 1rem 1.25rem; display: flex; align-items: center; gap: .6rem; }
    .form-card .card-header i { color: var(--rb-primary); font-size: 1.15rem; }
    .form-card .card-title { font-weight: 700; font-size: 1rem; margin: 0; color: var(--rb-text-dark); }
    .form-card .card-body { padding: 1.25rem; }

    .repeater-row { border: 1px solid #f1f5f9; border-radius: 10px; padding: 1rem; margin-bottom: .75rem; background: #fafbfc; position: relative; }
    .repeater-row:hover { border-color: #cbd5e1; }
    .btn-remove-row { position: absolute; top: .5rem; right: .5rem; border: none; background: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
    .btn-remove-row:hover { background: #fee2e2; }

    .btn-add-row { border: 2px dashed #cbd5e1; border-radius: 10px; padding: .6rem 1rem; background: transparent; color: var(--rb-text-muted); font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .15s; width: auto; display: inline-flex; align-items: center; gap: .4rem; }
    .btn-add-row:hover { border-color: var(--rb-primary); color: var(--rb-primary); background: #eff6ff; }
</style>
@endpush

{{-- ======================== HERO ======================== --}}
<div class="est-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>
                <i class="bi bi-file-earmark-text me-2"></i>
                @if ($isEdit)
                    <span>{{ __('Edit Estimate') }}:</span> {{ $estimate->case_number ?? '#'.$estimate->id }}
                @else
                    {{ __('New Estimate') }}
                @endif
            </h2>
            <div class="subtitle">{{ $isEdit ? __('Update estimate details, devices, and line items') : __('Create a new repair estimate for a customer') }}</div>
        </div>
        <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
        </a>
    </div>
</div>

{{-- ======================== VALIDATION ERRORS ======================== --}}
@if ($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}" id="estimateForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        {{-- ==== LEFT COLUMN ==== --}}
        <div class="col-lg-8">

            {{-- ---- Basic Details ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i>
                    <h5 class="card-title">{{ __('Estimate Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('Case Number') }}</label>
                            <input type="text" class="form-control form-control-sm" name="case_number"
                                   value="{{ old('case_number', $estimate?->case_number ?? '') }}"
                                   placeholder="{{ __('Auto-generated if empty') }}">
                            <div class="form-text">{{ __('Leave blank to auto-generate') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('Title') }}</label>
                            <input type="text" class="form-control form-control-sm" name="title"
                                   value="{{ old('title', $estimate?->title ?? '') }}"
                                   placeholder="{{ __('Estimate title (optional)') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('Pickup Date') }}</label>
                            <input type="date" class="form-control form-control-sm" name="pickup_date"
                                   value="{{ old('pickup_date', $estimate?->pickup_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('Delivery Date') }}</label>
                            <input type="date" class="form-control form-control-sm" name="delivery_date"
                                   value="{{ old('delivery_date', $estimate?->delivery_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">{{ __('Job Details / Description') }}</label>
                            <textarea class="form-control form-control-sm" name="case_detail" rows="3"
                                      placeholder="{{ __('Describe the repair request…') }}">{{ old('case_detail', $estimate?->case_detail ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---- Customer ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-person"></i>
                    <h5 class="card-title">{{ __('Customer') }}</h5>
                </div>
                <div class="card-body">
                    <select class="form-select form-select-sm" name="customer_id">
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

            {{-- ---- Technician ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-person-gear"></i>
                    <h5 class="card-title">{{ __('Technician') }}</h5>
                </div>
                <div class="card-body">
                    <select class="form-select form-select-sm" name="assigned_technician_id">
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

            {{-- ---- Devices (repeater) ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-phone"></i>
                    <h5 class="card-title">{{ __('Devices') }}</h5>
                </div>
                <div class="card-body">
                    <div id="devicesContainer">
                        {{-- Rows will be injected by JS --}}
                    </div>
                    <button type="button" class="btn-add-row" onclick="addDeviceRow()">
                        <i class="bi bi-plus-lg"></i>{{ __('Add Device') }}
                    </button>
                </div>
            </div>

            {{-- ---- Line Items (repeater by category) ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-receipt"></i>
                    <h5 class="card-title">{{ __('Line Items') }}</h5>
                </div>
                <div class="card-body">
                    <div id="itemsContainer">
                        {{-- Rows will be injected by JS --}}
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="btn-add-row" onclick="addItemRow('product')">
                            <i class="bi bi-box"></i>{{ __('Add Product') }}
                        </button>
                        <button type="button" class="btn-add-row" onclick="addItemRow('part')">
                            <i class="bi bi-cpu"></i>{{ __('Add Part') }}
                        </button>
                        <button type="button" class="btn-add-row" onclick="addItemRow('service')">
                            <i class="bi bi-wrench"></i>{{ __('Add Service') }}
                        </button>
                        <button type="button" class="btn-add-row" onclick="addItemRow('other')">
                            <i class="bi bi-plus-circle"></i>{{ __('Add Extra') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- /col-lg-8 --}}

        {{-- ==== RIGHT COLUMN ==== --}}
        <div class="col-lg-4">

            {{-- ---- Order Notes ---- --}}
            <div class="form-card">
                <div class="card-header">
                    <i class="bi bi-sticky"></i>
                    <h5 class="card-title">{{ __('Order Notes') }}</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control form-control-sm" name="order_notes" rows="5"
                              placeholder="{{ __('Internal notes…') }}">{{ old('order_notes', $estimate?->case_detail ?? '') }}</textarea>
                </div>
            </div>

            {{-- ---- Submit ---- --}}
            <div class="form-card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-{{ $isEdit ? 'check-lg' : 'plus-lg' }} me-1"></i>
                        {{ $isEdit ? __('Update Estimate') : __('Create Estimate') }}
                    </button>
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary rounded-pill">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>{{-- /col-lg-4 --}}
    </div>{{-- /row --}}
</form>

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---- Device data from PHP ----
    const existingDevices = @json($existingDevices);
    const customerDeviceOptions = @json($customerDevices->map(fn($d) => ['id' => $d->id, 'label' => $d->label ?? 'Device #'.$d->id])->values());

    // ---- Item data from PHP ----
    const existingItems = @json($existingItems);
    const partOptions = @json($parts->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values());
    const serviceOptions = @json($services->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values());

    let deviceIdx = 0;
    let itemIdx = 0;

    // ==== Device Row ====
    window.addDeviceRow = function(data) {
        data = data || {};
        const idx = deviceIdx++;
        const container = document.getElementById('devicesContainer');
        const div = document.createElement('div');
        div.className = 'repeater-row';
        div.id = 'device-row-' + idx;

        let deviceOpts = '<option value="">' + '{{ __("Select device…") }}' + '</option>';
        customerDeviceOptions.forEach(function(d) {
            const sel = data.customer_device_id == d.id ? 'selected' : '';
            deviceOpts += '<option value="' + d.id + '" ' + sel + '>' + escHtml(d.label) + '</option>';
        });

        div.innerHTML = '<button type="button" class="btn-remove-row" onclick="removeRow(\'device-row-' + idx + '\')"><i class="bi bi-x-lg"></i></button>'
            + '<div class="row g-2">'
            + '<div class="col-md-6"><label class="form-label small fw-bold">{{ __("Device") }}</label><select class="form-select form-select-sm" name="devices[' + idx + '][customer_device_id]">' + deviceOpts + '</select></div>'
            + '<div class="col-md-6"><label class="form-label small fw-bold">{{ __("Serial / IMEI") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][serial]" value="' + escAttr(data.serial || '') + '"></div>'
            + '<div class="col-md-4"><label class="form-label small fw-bold">{{ __("PIN") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][pin]" value="' + escAttr(data.pin || '') + '"></div>'
            + '<div class="col-md-8"><label class="form-label small fw-bold">{{ __("Notes") }}</label><input type="text" class="form-control form-control-sm" name="devices[' + idx + '][notes]" value="' + escAttr(data.notes || '') + '"></div>'
            + '</div>';
        container.appendChild(div);
    };

    // ==== Item Row ====
    window.addItemRow = function(type, data) {
        data = data || {};
        if (typeof type === 'object') { data = type; type = data.type || 'other'; }
        const idx = itemIdx++;
        const container = document.getElementById('itemsContainer');
        const div = document.createElement('div');
        div.className = 'repeater-row';
        div.id = 'item-row-' + idx;

        const typeLabels = { product: '{{ __("Product") }}', part: '{{ __("Part") }}', service: '{{ __("Service") }}', other: '{{ __("Extra") }}' };
        const typeColors = { product: '#3b82f6', part: '#8b5cf6', service: '#f59e0b', other: '#6b7280' };
        const typeLabel = typeLabels[type] || typeLabels.other;
        const typeColor = typeColors[type] || typeColors.other;

        const priceDollars = data.unit_price_cents ? (parseInt(data.unit_price_cents) / 100).toFixed(2) : '';

        div.innerHTML = '<button type="button" class="btn-remove-row" onclick="removeRow(\'item-row-' + idx + '\')"><i class="bi bi-x-lg"></i></button>'
            + '<div class="mb-2"><span class="badge" style="background:' + typeColor + '; font-size:.7rem;">' + typeLabel + '</span></div>'
            + '<input type="hidden" name="items[' + idx + '][type]" value="' + type + '">'
            + '<div class="row g-2">'
            + '<div class="col-md-5"><label class="form-label small fw-bold">{{ __("Name") }}</label><input type="text" class="form-control form-control-sm" name="items[' + idx + '][name]" value="' + escAttr(data.name || '') + '" required></div>'
            + '<div class="col-md-2"><label class="form-label small fw-bold">{{ __("Qty") }}</label><input type="number" class="form-control form-control-sm" name="items[' + idx + '][qty]" value="' + (data.qty || 1) + '" min="1" required></div>'
            + '<div class="col-md-3"><label class="form-label small fw-bold">{{ __("Price ($)") }}</label><input type="number" class="form-control form-control-sm item-price" name="items[' + idx + '][unit_price_dollars]" value="' + priceDollars + '" step="0.01" min="0" required></div>'
            + '<div class="col-md-2 d-flex align-items-end"><span class="text-muted small pb-2">$<span class="item-line-total">0.00</span></span></div>'
            + '</div>';
        container.appendChild(div);

        // Attach live total calculation
        const qtyInput = div.querySelector('input[name="items[' + idx + '][qty]"]');
        const priceInput = div.querySelector('.item-price');
        const totalSpan = div.querySelector('.item-line-total');
        function updateLineTotal() {
            const q = parseInt(qtyInput.value) || 0;
            const p = parseFloat(priceInput.value) || 0;
            totalSpan.textContent = (q * p).toFixed(2);
        }
        qtyInput.addEventListener('input', updateLineTotal);
        priceInput.addEventListener('input', updateLineTotal);
        updateLineTotal();
    };

    window.removeRow = function(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    };

    function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function escAttr(str) { return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    // ---- Populate existing data ----
    if (existingDevices.length) {
        existingDevices.forEach(function(d) { window.addDeviceRow(d); });
    }
    if (existingItems.length) {
        existingItems.forEach(function(item) { window.addItemRow(item); });
    }
});
</script>
@endpush

@endsection

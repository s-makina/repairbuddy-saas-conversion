@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Job'])

@section('content')
@php
    $jobStatuses = is_iterable($jobStatuses ?? null) ? $jobStatuses : [];
    $paymentStatuses = is_iterable($paymentStatuses ?? null) ? $paymentStatuses : [];
    $customers = is_iterable($customers ?? null) ? $customers : [];
    $technicians = is_iterable($technicians ?? null) ? $technicians : [];
    $customerDevices = is_iterable($customerDevices ?? null) ? $customerDevices : [];

    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : null;
@endphp

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

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Job Details') }}</h5>
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

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Status') }}</label>
                                @php $statusOld = old('status_slug', 'neworder'); @endphp
                                <select name="status_slug" class="form-select">
                                    <option value="" {{ $statusOld === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                    @foreach ($jobStatuses as $s)
                                        <option value="{{ $s->slug }}" {{ $statusOld === $s->slug ? 'selected' : '' }}>
                                            {{ $s->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_slug')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Priority') }}</label>
                                @php $p = old('priority', 'normal'); @endphp
                                <select name="priority" class="form-select">
                                    <option value="normal" {{ $p === 'normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                                    <option value="high" {{ $p === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                    <option value="urgent" {{ $p === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                                </select>
                                @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Tax Mode') }}</label>
                                @php $taxMode = old('prices_inclu_exclu'); @endphp
                                <select name="prices_inclu_exclu" class="form-select">
                                    <option value="" {{ $taxMode === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                    <option value="exclusive" {{ $taxMode === 'exclusive' ? 'selected' : '' }}>{{ __('Exclusive') }}</option>
                                    <option value="inclusive" {{ $taxMode === 'inclusive' ? 'selected' : '' }}>{{ __('Inclusive') }}</option>
                                </select>
                                @error('prices_inclu_exclu')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Customer') }}</label>
                                <select name="customer_id" class="form-select">
                                    <option value="" {{ old('customer_id') === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" {{ (string) old('customer_id') === (string) $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} ({{ $c->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Technicians') }}</label>
                                <select name="technician_ids[]" class="form-select" multiple>
                                    @foreach ($technicians as $t)
                                        <option value="{{ $t->id }}" {{ in_array((string) $t->id, array_map('strval', (array) old('technician_ids', [])), true) ? 'selected' : '' }}>
                                            {{ $t->name }} ({{ $t->email }})
                                        </option>
                                    @endforeach
                                </select>
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

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Order Notes') }}</label>
                                <textarea name="wc_order_note" class="form-control" rows="3" placeholder="{{ __('Visible to customer.') }}">{{ old('wc_order_note') }}</textarea>
                                @error('wc_order_note')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('File Attachment') }}</label>
                                <input type="file" name="job_file" class="form-control" />
                                @error('job_file')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Devices') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small">{{ __('Attach customer devices (optional).') }}</div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addDeviceLine">{{ __('Add Device') }}</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="devicesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Customer Device') }}</th>
                                        <th style="width:180px">{{ __('Serial / ID') }}</th>
                                        <th style="width:140px">{{ __('Pin') }}</th>
                                        <th>{{ __('Notes') }}</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldDevIds = (array) old('job_device_customer_device_id', []);
                                        $oldDevSerials = (array) old('job_device_serial', []);
                                        $oldDevPins = (array) old('job_device_pin', []);
                                        $oldDevNotes = (array) old('job_device_notes', []);
                                        $devRows = max(count($oldDevIds), count($oldDevSerials), count($oldDevPins), count($oldDevNotes), 1);
                                    @endphp
                                    @for ($i = 0; $i < $devRows; $i++)
                                        <tr>
                                            <td>
                                                <select name="job_device_customer_device_id[]" class="form-select form-select-sm">
                                                    <option value="">{{ __('Select...') }}</option>
                                                    @foreach ($customerDevices as $cd)
                                                        @php
                                                            $label = $cd->label;
                                                            $cname = $cd->customer?->name;
                                                            $serial = $cd->serial;
                                                            $text = $label;
                                                            if (is_string($cname) && $cname !== '') {
                                                                $text .= ' — ' . $cname;
                                                            }
                                                            if (is_string($serial) && $serial !== '') {
                                                                $text .= ' (' . $serial . ')';
                                                            }
                                                            $sel = (string) ($oldDevIds[$i] ?? '') === (string) $cd->id;
                                                        @endphp
                                                        <option value="{{ $cd->id }}" {{ $sel ? 'selected' : '' }}>{{ $text }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="job_device_serial[]" class="form-control form-control-sm" value="{{ $oldDevSerials[$i] ?? '' }}" /></td>
                                            <td><input type="text" name="job_device_pin[]" class="form-control form-control-sm" value="{{ $oldDevPins[$i] ?? '' }}" /></td>
                                            <td><input type="text" name="job_device_notes[]" class="form-control form-control-sm" value="{{ $oldDevNotes[$i] ?? '' }}" /></td>
                                            <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeDeviceLine">{{ __('X') }}</button></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        @error('job_device_customer_device_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Attach Fields & Files') }}</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addExtraLine">{{ __('Add Field') }}</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="extraTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:160px">{{ __('Date') }}</th>
                                        <th style="width:220px">{{ __('Label') }}</th>
                                        <th>{{ __('Data') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th style="width:140px">{{ __('Visibility') }}</th>
                                        <th style="width:220px">{{ __('File') }}</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldExDates = (array) old('extra_item_occurred_at', []);
                                        $oldExLabels = (array) old('extra_item_label', []);
                                        $oldExData = (array) old('extra_item_data_text', []);
                                        $oldExDesc = (array) old('extra_item_description', []);
                                        $oldExVis = (array) old('extra_item_visibility', []);
                                        $exRows = max(count($oldExLabels), count($oldExData), count($oldExDesc), count($oldExVis), count($oldExDates), 1);
                                    @endphp
                                    @for ($i = 0; $i < $exRows; $i++)
                                        <tr>
                                            <td><input type="date" name="extra_item_occurred_at[]" class="form-control form-control-sm" value="{{ $oldExDates[$i] ?? '' }}" /></td>
                                            <td><input type="text" name="extra_item_label[]" class="form-control form-control-sm" value="{{ $oldExLabels[$i] ?? '' }}" /></td>
                                            <td><input type="text" name="extra_item_data_text[]" class="form-control form-control-sm" value="{{ $oldExData[$i] ?? '' }}" /></td>
                                            <td><input type="text" name="extra_item_description[]" class="form-control form-control-sm" value="{{ $oldExDesc[$i] ?? '' }}" /></td>
                                            @php $vis = is_string($oldExVis[$i] ?? null) ? (string) $oldExVis[$i] : 'private'; @endphp
                                            <td>
                                                <select name="extra_item_visibility[]" class="form-select form-select-sm">
                                                    <option value="private" {{ $vis === 'private' ? 'selected' : '' }}>{{ __('Private') }}</option>
                                                    <option value="public" {{ $vis === 'public' ? 'selected' : '' }}>{{ __('Public') }}</option>
                                                </select>
                                            </td>
                                            <td><input type="file" name="extra_item_file[]" class="form-control form-control-sm" /></td>
                                            <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeExtraLine">{{ __('X') }}</button></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        @error('extra_item_label')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('Items & Services') }}</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addLine">{{ __('Add Line') }}</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="linesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:160px">{{ __('Type') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th style="width:110px" class="text-end">{{ __('Qty') }}</th>
                                        <th style="width:190px" class="text-end">{{ __('Unit Price (cents)') }}</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldTypes = (array) old('item_type', []);
                                        $oldNames = (array) old('item_name', []);
                                        $oldQtys = (array) old('item_qty', []);
                                        $oldPrices = (array) old('item_unit_price_cents', []);
                                        $rows = max(count($oldTypes), count($oldNames), count($oldQtys), count($oldPrices), 1);
                                    @endphp
                                    @for ($i = 0; $i < $rows; $i++)
                                        <tr>
                                            <td>
                                                @php $ot = is_string($oldTypes[$i] ?? null) ? (string) $oldTypes[$i] : ''; @endphp
                                                <select name="item_type[]" class="form-select form-select-sm">
                                                    <option value="" {{ $ot === '' ? 'selected' : '' }}>{{ __('Select...') }}</option>
                                                    <option value="service" {{ $ot === 'service' ? 'selected' : '' }}>{{ __('Service') }}</option>
                                                    <option value="part" {{ $ot === 'part' ? 'selected' : '' }}>{{ __('Part') }}</option>
                                                    <option value="fee" {{ $ot === 'fee' ? 'selected' : '' }}>{{ __('Fee') }}</option>
                                                    <option value="discount" {{ $ot === 'discount' ? 'selected' : '' }}>{{ __('Discount') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="item_name[]" class="form-control form-control-sm" value="{{ $oldNames[$i] ?? '' }}" />
                                            </td>
                                            <td>
                                                <input type="number" name="item_qty[]" class="form-control form-control-sm text-end" min="1" value="{{ $oldQtys[$i] ?? '1' }}" />
                                            </td>
                                            <td>
                                                <input type="number" name="item_unit_price_cents[]" class="form-control form-control-sm text-end" value="{{ $oldPrices[$i] ?? '0' }}" />
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm removeLine">{{ __('X') }}</button>
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        @error('item_type')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2-circle me-1"></i>
                            {{ __('Create Job') }}
                        </button>
                        <div class="text-muted small mt-2">{{ __('After creating, you will be redirected to the job page.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
(function () {
    var addBtn = document.getElementById('addLine');
    var table = document.getElementById('linesTable');

    function removeHandler(e) {
        var btn = e.target.closest('.removeLine');
        if (!btn) return;
        var tr = btn.closest('tr');
        if (!tr) return;
        var tbody = tr.parentElement;
        tr.remove();
        if (tbody && tbody.children.length === 0) {
            addLine();
        }
    }

    function addLine() {
        var tbody = table.querySelector('tbody');
        var tr = document.createElement('tr');
        tr.innerHTML = ''
          + '<td><select name="item_type[]" class="form-select form-select-sm">'
          + '<option value="">Select...</option>'
          + '<option value="service">Service</option>'
          + '<option value="part">Part</option>'
          + '<option value="fee">Fee</option>'
          + '<option value="discount">Discount</option>'
          + '</select></td>'
          + '<td><input type="text" name="item_name[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="number" name="item_qty[]" class="form-control form-control-sm text-end" min="1" value="1" /></td>'
          + '<td><input type="number" name="item_unit_price_cents[]" class="form-control form-control-sm text-end" value="0" /></td>'
          + '<td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeLine">X</button></td>';
        tbody.appendChild(tr);
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            addLine();
        });
    }

    if (table) {
        table.addEventListener('click', removeHandler);
    }

    var deviceAddBtn = document.getElementById('addDeviceLine');
    var devicesTable = document.getElementById('devicesTable');

    function deviceRemoveHandler(e) {
        var btn = e.target.closest('.removeDeviceLine');
        if (!btn) return;
        var tr = btn.closest('tr');
        if (!tr) return;
        var tbody = tr.parentElement;
        tr.remove();
        if (tbody && tbody.children.length === 0) {
            addDeviceLine();
        }
    }

    function addDeviceLine() {
        if (!devicesTable) return;
        var tbody = devicesTable.querySelector('tbody');
        if (!tbody) return;

        var optionsHtml = '<option value="">Select...</option>';
        @foreach ($customerDevices as $cd)
            @php
                $label = $cd->label;
                $cname = $cd->customer?->name;
                $serial = $cd->serial;
                $text = $label;
                if (is_string($cname) && $cname !== '') {
                    $text .= ' — ' . $cname;
                }
                if (is_string($serial) && $serial !== '') {
                    $text .= ' (' . $serial . ')';
                }
            @endphp
            optionsHtml += '<option value="{{ $cd->id }}">{!! e($text) !!}</option>';
        @endforeach

        var tr = document.createElement('tr');
        tr.innerHTML = ''
          + '<td><select name="job_device_customer_device_id[]" class="form-select form-select-sm">' + optionsHtml + '</select></td>'
          + '<td><input type="text" name="job_device_serial[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="text" name="job_device_pin[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="text" name="job_device_notes[]" class="form-control form-control-sm" value="" /></td>'
          + '<td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeDeviceLine">X</button></td>';
        tbody.appendChild(tr);
    }

    if (deviceAddBtn) {
        deviceAddBtn.addEventListener('click', function () {
            addDeviceLine();
        });
    }

    if (devicesTable) {
        devicesTable.addEventListener('click', deviceRemoveHandler);
    }

    var extraAddBtn = document.getElementById('addExtraLine');
    var extraTable = document.getElementById('extraTable');

    function extraRemoveHandler(e) {
        var btn = e.target.closest('.removeExtraLine');
        if (!btn) return;
        var tr = btn.closest('tr');
        if (!tr) return;
        var tbody = tr.parentElement;
        tr.remove();
        if (tbody && tbody.children.length === 0) {
            addExtraLine();
        }
    }

    function addExtraLine() {
        if (!extraTable) return;
        var tbody = extraTable.querySelector('tbody');
        if (!tbody) return;

        var tr = document.createElement('tr');
        tr.innerHTML = ''
          + '<td><input type="date" name="extra_item_occurred_at[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="text" name="extra_item_label[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="text" name="extra_item_data_text[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><input type="text" name="extra_item_description[]" class="form-control form-control-sm" value="" /></td>'
          + '<td><select name="extra_item_visibility[]" class="form-select form-select-sm"><option value="private">Private</option><option value="public">Public</option></select></td>'
          + '<td><input type="file" name="extra_item_file[]" class="form-control form-control-sm" /></td>'
          + '<td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeExtraLine">X</button></td>';
        tbody.appendChild(tr);
    }

    if (extraAddBtn) {
        extraAddBtn.addEventListener('click', function () {
            addExtraLine();
        });
    }

    if (extraTable) {
        extraTable.addEventListener('click', extraRemoveHandler);
    }
})();
</script>
@endsection

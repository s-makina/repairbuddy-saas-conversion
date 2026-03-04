@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Customer Devices'])

@php
    /** @var \App\Models\Tenant|null $tenant */
    /** @var \App\Models\User|null   $user */

    $rows              = $rows ?? collect();
    $columns           = $columns ?? [];
    $customers         = $customers ?? collect();
    $deviceTypes       = $deviceTypes ?? collect();
    $deviceBrands      = $deviceBrands ?? collect();

    $role              = is_string($user?->role) ? (string) $user->role : null;
    $tenantSlug        = is_string($tenant?->slug) ? (string) $tenant->slug : '';
    $isAdminUser       = (bool) ($isAdminUser ?? false);

    $totalDevices      = $totalDevices ?? 0;
    $devicesWithSerial = $devicesWithSerial ?? 0;
    $devicesWithoutDevice = $devicesWithoutDevice ?? 0;

    // Filters
    $searchInput       = $searchInput ?? '';
    $customerFilter    = $customerFilter ?? '';
    $deviceTypeFilter  = $deviceTypeFilter ?? '';
    $deviceBrandFilter = $deviceBrandFilter ?? '';
    $baseUrl           = $baseUrl ?? '#';
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- ═══════ Summary Cards ═══════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Devices') }}</div>
                            <h4 class="mb-0">{{ $totalDevices }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-phone"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-success text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('With Serial') }}</div>
                            <h4 class="mb-0">{{ $devicesWithSerial }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-barcode"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Unassigned') }}</div>
                            <h4 class="mb-0">{{ $devicesWithoutDevice }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════ DataTable ═══════ --}}
    <x-ui.datatable
        tableId="customerDevicesTable"
        :title="__('Customer Devices')"
        :columns="$columns"
        :rows="$rows"
        :searchable="true"
        :paginate="true"
        :perPage="25"
        :perPageOptions="[10, 25, 50, 100]"
        :exportable="true"
        :filterable="true"
        :emptyMessage="__('No customer devices found.')"
    >
        <x-slot:filters>
            <form method="GET" action="{{ $baseUrl }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Search') }}</label>
                        <input type="text" class="form-control form-control-sm" name="searchinput"
                               value="{{ $searchInput }}" placeholder="{{ __('Device, serial, customer…') }}">
                    </div>

                    @if ($isAdminUser)
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Customer') }}</label>
                        <select class="form-select form-select-sm" name="customer_id">
                            <option value="">{{ __('All Customers') }}</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ (string)$customerFilter === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Device Type') }}</label>
                        <select class="form-select form-select-sm" name="device_type_id">
                            <option value="">{{ __('All Types') }}</option>
                            @foreach ($deviceTypes as $dt)
                                <option value="{{ $dt->id }}" {{ (string)$deviceTypeFilter === (string)$dt->id ? 'selected' : '' }}>{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Device Brand') }}</label>
                        <select class="form-select form-select-sm" name="device_brand_id">
                            <option value="">{{ __('All Brands') }}</option>
                            @foreach ($deviceBrands as $db)
                                <option value="{{ $db->id }}" {{ (string)$deviceBrandFilter === (string)$db->id ? 'selected' : '' }}>{{ $db->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Apply') }}</button>
                        <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
            </form>
        </x-slot:filters>
    </x-ui.datatable>

</div>

{{-- ═══════ View Device Modal ═══════ --}}
<div class="modal fade" id="viewCustomerDeviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title"><i class="bi bi-phone me-2"></i>{{ __('Device Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="viewDeviceContent" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">{{ __('Loading...') }}</span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Edit Device Modal ═══════ --}}
<div class="modal fade" id="editCustomerDeviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>{{ __('Edit Device') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="editDeviceForm">
                    <input type="hidden" id="edit_device_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Customer') }}</label>
                            <select class="form-select form-select-sm" id="edit_customer_id" required>
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Device Model') }}</label>
                            <select class="form-select form-select-sm" id="edit_device_model_id">
                                <option value="">{{ __('(none)') }}</option>
                                @foreach ($deviceBrands as $brand)
                                    @php
                                        $brandDevices = App\Models\RepairBuddyDevice::where('device_brand_id', $brand->id)->where('is_active', true)->get();
                                    @endphp
                                    @if($brandDevices->count() > 0)
                                        <optgroup label="{{ $brand->name }}">
                                            @foreach ($brandDevices as $d)
                                                <option value="{{ $d->id }}" data-brand-id="{{ $brand->id }}">{{ $d->model }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Label') }}</label>
                            <input type="text" class="form-control form-control-sm" id="edit_label" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Serial / IMEI') }}</label>
                            <input type="text" class="form-control form-control-sm" id="edit_serial">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('PIN / Password') }}</label>
                            <input type="text" class="form-control form-control-sm" id="edit_pin">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Notes') }}</label>
                            <textarea class="form-control form-control-sm" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveEditDeviceBtn" onclick="saveEditDevice()">
                    <i class="bi bi-check-lg me-1"></i>{{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
const TENANT_SLUG = @json($tenantSlug);
const WEB_BASE = '/t/' + TENANT_SLUG + '/customer-devices';

// CSRF token for web routes
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Build headers for web routes (session auth + CSRF)
function getWebHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
    };
}

// View customer device
function viewCustomerDevice(id) {
    const modalEl = document.getElementById('viewCustomerDeviceModal');
    const contentEl = document.getElementById('viewDeviceContent');
    
    // Show loading state
    contentEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">{{ __('Loading...') }}</span></div>';
    
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    
    fetch(WEB_BASE + '/' + id, {
        headers: getWebHeaders(),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.customer_device) {
            contentEl.innerHTML = '<div class="alert alert-danger">{{ __('Device not found') }}</div>';
            return;
        }
        
        const cd = data.customer_device;
        const customer = cd.customer || {};
        const device = cd.device || {};
        const brand = device.brand || {};
        const type = device.type || {};
        const job = cd.latest_job || null;
        
        let html = '<div class="row g-3">';
        
        // Device info
        html += '<div class="col-md-6"><div class="card h-100"><div class="card-body p-2">';
        html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Device Info') }}</h6>';
        html += '<table class="table table-sm mb-0" style="font-size: 0.85rem;">';
        html += '<tr><td class="text-muted" style="width: 100px;">ID</td><td><strong>#' + cd.id + '</strong></td></tr>';
        html += '<tr><td class="text-muted">{{ __('Label') }}</td><td><strong>' + (cd.label || '—') + '</strong></td></tr>';
        html += '<tr><td class="text-muted">{{ __('Brand') }}</td><td>' + (brand.name || '—') + '</td></tr>';
        html += '<tr><td class="text-muted">{{ __('Model') }}</td><td>' + (device.model || '—') + '</td></tr>';
        html += '<tr><td class="text-muted">{{ __('Type') }}</td><td>' + (type.name || '—') + '</td></tr>';
        html += '</table></div></div></div>';
        
        // Serial & PIN
        html += '<div class="col-md-6"><div class="card h-100"><div class="card-body p-2">';
        html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Identification') }}</h6>';
        html += '<table class="table table-sm mb-0" style="font-size: 0.85rem;">';
        html += '<tr><td class="text-muted" style="width: 100px;">{{ __('Serial') }}</td><td><code>' + (cd.serial || '—') + '</code></td></tr>';
        html += '<tr><td class="text-muted">{{ __('PIN') }}</td><td><code>' + (cd.pin || '—') + '</code></td></tr>';
        html += '</table></div></div></div>';
        
        // Customer info
        html += '<div class="col-md-6"><div class="card h-100"><div class="card-body p-2">';
        html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Customer') }}</h6>';
        html += '<table class="table table-sm mb-0" style="font-size: 0.85rem;">';
        html += '<tr><td class="text-muted" style="width: 100px;">{{ __('Name') }}</td><td><strong>' + (customer.name || '—') + '</strong></td></tr>';
        html += '<tr><td class="text-muted">{{ __('Email') }}</td><td>' + (customer.email || '—') + '</td></tr>';
        html += '<tr><td class="text-muted">{{ __('Phone') }}</td><td>' + (customer.phone || '—') + '</td></tr>';
        html += '</table></div></div></div>';
        
        // Latest Job
        html += '<div class="col-md-6"><div class="card h-100"><div class="card-body p-2">';
        html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Latest Job') }}</h6>';
        if (job) {
            const jobUrl = '/t/' + TENANT_SLUG + '/jobs/' + job.id;
            const techs = job.technicians && job.technicians.length ? job.technicians.map(t => t.name).join(', ') : '—';
            html += '<table class="table table-sm mb-0" style="font-size: 0.85rem;">';
            html += '<tr><td class="text-muted" style="width: 100px;">{{ __('Case') }}</td><td><a href="' + jobUrl + '" class="text-decoration-none"><strong>' + (job.case_number || '#' + job.id) + '</strong></a></td></tr>';
            html += '<tr><td class="text-muted">{{ __('Title') }}</td><td>' + (job.title || '—') + '</td></tr>';
            html += '<tr><td class="text-muted">{{ __('Status') }}</td><td>' + (job.status_slug || '—') + '</td></tr>';
            html += '<tr><td class="text-muted">{{ __('Assigned') }}</td><td>' + techs + '</td></tr>';
            html += '</table>';
        } else {
            html += '<p class="text-muted mb-0" style="font-size: 0.85rem;">{{ __('No jobs found for this device') }}</p>';
        }
        html += '</div></div></div>';
        
        // Notes
        if (cd.notes) {
            html += '<div class="col-12"><div class="card"><div class="card-body p-2">';
            html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Notes') }}</h6>';
            html += '<p class="mb-0" style="font-size: 0.85rem; white-space: pre-wrap;">' + cd.notes + '</p>';
            html += '</div></div></div>';
        }
        
        // Time Logs
        const timeLogs = cd.time_logs || [];
        if (timeLogs.length > 0) {
            html += '<div class="col-12"><div class="card"><div class="card-body p-2">';
            html += '<h6 class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase;">{{ __('Time Logs') }} <span class="badge bg-secondary">' + timeLogs.length + '</span></h6>';
            html += '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size: 0.8rem;">';
            html += '<thead><tr><th>{{ __('Date') }}</th><th>{{ __('Technician') }}</th><th>{{ __('Activity') }}</th><th>{{ __('Minutes') }}</th><th>{{ __('Billable') }}</th></tr></thead><tbody>';
            timeLogs.forEach(function(log) {
                const date = log.start_time ? new Date(log.start_time).toLocaleDateString() : '—';
                const tech = log.technician ? log.technician.name : '—';
                const activity = log.activity || log.work_description || '—';
                const mins = log.total_minutes || 0;
                const billable = log.is_billable ? '<span class="text-success">✓</span>' : '<span class="text-muted">—</span>';
                html += '<tr><td>' + date + '</td><td>' + tech + '</td><td>' + activity + '</td><td>' + mins + '</td><td>' + billable + '</td></tr>';
            });
            html += '</tbody></table></div></div></div></div>';
        }
        
        html += '</div>';
        contentEl.innerHTML = html;
    })
    .catch(err => {
        console.error('Error loading device:', err);
        contentEl.innerHTML = '<div class="alert alert-danger">{{ __('Failed to load device details') }}</div>';
    });
}

// Edit customer device
function editCustomerDevice(id) {
    const modalEl = document.getElementById('editCustomerDeviceModal');
    
    // Reset form
    document.getElementById('edit_device_id').value = '';
    document.getElementById('edit_customer_id').value = '';
    document.getElementById('edit_device_model_id').value = '';
    document.getElementById('edit_label').value = '';
    document.getElementById('edit_serial').value = '';
    document.getElementById('edit_pin').value = '';
    document.getElementById('edit_notes').value = '';
    
    // Fetch device data
    fetch(WEB_BASE + '/' + id, {
        headers: getWebHeaders(),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.customer_device) {
            alert('{{ __('Device not found') }}');
            return;
        }
        
        const cd = data.customer_device;
        document.getElementById('edit_device_id').value = cd.id;
        document.getElementById('edit_customer_id').value = cd.customer_id || '';
        document.getElementById('edit_device_model_id').value = cd.device_id || '';
        document.getElementById('edit_label').value = cd.label || '';
        document.getElementById('edit_serial').value = cd.serial || '';
        document.getElementById('edit_pin').value = cd.pin || '';
        document.getElementById('edit_notes').value = cd.notes || '';
        
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    })
    .catch(err => {
        console.error('Error loading device:', err);
        alert('{{ __('Failed to load device details') }}');
    });
}

// Save edited device
function saveEditDevice() {
    const id = document.getElementById('edit_device_id').value;
    const customerId = document.getElementById('edit_customer_id').value;
    const deviceId = document.getElementById('edit_device_model_id').value || null;
    const label = document.getElementById('edit_label').value.trim();
    const serial = document.getElementById('edit_serial').value.trim() || null;
    const pin = document.getElementById('edit_pin').value.trim() || null;
    const notes = document.getElementById('edit_notes').value.trim() || null;
    
    if (!customerId || !label) {
        alert('{{ __('Customer and Label are required') }}');
        return;
    }
    
    const btn = document.getElementById('saveEditDeviceBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Saving...') }}';
    
    fetch(WEB_BASE + '/' + id + '/update', {
        method: 'POST',
        headers: getWebHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify({
            customer_id: parseInt(customerId),
            device_id: deviceId ? parseInt(deviceId) : null,
            label: label,
            serial: serial,
            pin: pin,
            notes: notes
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.message && data.message.includes('not found')) {
            alert('{{ __('Device not found') }}');
            return;
        }
        
        // Close modal and refresh page
        const modalEl = document.getElementById('editCustomerDeviceModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        
        // Reload page to show updated data
        window.location.reload();
    })
    .catch(err => {
        console.error('Error saving device:', err);
        alert('{{ __('Failed to save device') }}');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Delete customer device
function deleteCustomerDevice(id) {
    if (!confirm('{{ __('Are you sure you want to delete this device? This action cannot be undone.') }}')) {
        return;
    }
    
    fetch(WEB_BASE + '/' + id + '/delete', {
        method: 'POST',
        headers: getWebHeaders(),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.code === 'in_use') {
            alert('{{ __('Cannot delete: this device is associated with one or more jobs.') }}');
            return;
        }
        
        if (data.message === 'Deleted.') {
            // Reload page to show updated data
            window.location.reload();
        } else {
            alert(data.message || '{{ __('Failed to delete device') }}');
        }
    })
    .catch(err => {
        console.error('Error deleting device:', err);
        alert('{{ __('Failed to delete device') }}');
    });
}
</script>
@endpush

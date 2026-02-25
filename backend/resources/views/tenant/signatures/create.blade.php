@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Generate Signature Request'])

@section('content')
<div class="container p-3 p-md-4" style="max-width:700px;">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>{{ __('Please fix the following errors:') }}</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                    <li style="font-size:.85rem;">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-1">
                <i class="bi bi-pen me-2 text-primary"></i>{{ __('Generate Signature Request') }}
            </h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.signatures.index', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list me-1"></i>{{ __('All Requests') }}
            </a>
            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
            </a>
        </div>
    </div>

    {{-- Context Strip --}}
    <div class="ctx-strip mb-3">
        <div class="row g-3">
            <div class="col-6 col-sm-3">
                <div class="ctx-item">
                    <span class="ctx-key">{{ __('Job #') }}</span>
                    <span class="ctx-val fw-bold text-primary">{{ $job->job_number ?? $job->id }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="ctx-item">
                    <span class="ctx-key">{{ __('Case #') }}</span>
                    <span class="ctx-val">{{ $job->case_number ?? '' }}</span>
                </div>
            </div>
            @if($job->customer)
            <div class="col-6 col-sm-3">
                <div class="ctx-item">
                    <span class="ctx-key">{{ __('Customer') }}</span>
                    <span class="ctx-val">{{ $job->customer->name }}</span>
                </div>
            </div>
            @endif
            <div class="col-6 col-sm-3">
                <div class="ctx-item">
                    <span class="ctx-key">{{ __('Status') }}</span>
                    <span class="ctx-val">
                        <span class="badge bg-secondary" style="font-size:.7rem;">
                            {{ $job->status_label ?? ucfirst(str_replace('-',' ', $job->status_slug ?? '')) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card p-4">
        <form method="POST"
              action="{{ route('tenant.signatures.store', ['business' => $tenant->slug, 'jobId' => $job->id]) }}">
            @csrf

            {{--  Signature Type  --}}
            <div class="mb-4">
                <div class="section-label mb-2">
                    {{ __('Signature Type') }} <span class="text-danger">*</span>
                </div>

                @if(!empty($signatureSettings['pickup_enabled']))
                <label class="type-row pickup-row {{ old('signature_type') === 'pickup' ? 'selected' : '' }}"
                       onclick="rowSelect(this, 'pickup', '{{ __('Pickup Signature') }}')">
                    <input type="radio" name="signature_type" value="pickup"
                           {{ old('signature_type') === 'pickup' ? 'checked' : '' }}>
                    <div class="tr-icon"><i class="bi bi-box-arrow-in-up-right"></i></div>
                    <div class="tr-text">
                        <div class="tr-name">{{ __('Pickup Signature') }}</div>
                        <div class="tr-note">{{ __('Customer collects device from shop') }}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-primary check-icon {{ old('signature_type') === 'pickup' ? '' : 'd-none' }}"
                       style="font-size:1rem;"></i>
                </label>
                @endif

                @if(!empty($signatureSettings['delivery_enabled']))
                <label class="type-row delivery-row {{ old('signature_type') === 'delivery' ? 'selected' : '' }}"
                       onclick="rowSelect(this, 'delivery', '{{ __('Delivery Signature') }}')">
                    <input type="radio" name="signature_type" value="delivery"
                           {{ old('signature_type') === 'delivery' ? 'checked' : '' }}>
                    <div class="tr-icon"><i class="bi bi-truck"></i></div>
                    <div class="tr-text">
                        <div class="tr-name">{{ __('Delivery Signature') }}</div>
                        <div class="tr-note">{{ __('Device delivered to customer\'s location') }}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-primary check-icon {{ old('signature_type') === 'delivery' ? '' : 'd-none' }}"
                       style="font-size:1rem;"></i>
                </label>
                @endif

                <label class="type-row custom-row {{ old('signature_type') === 'custom' ? 'selected' : '' }}"
                       onclick="rowSelect(this, 'custom', '')">
                    <input type="radio" name="signature_type" value="custom"
                           {{ old('signature_type') === 'custom' ? 'checked' : '' }}>
                    <div class="tr-icon"><i class="bi bi-pen"></i></div>
                    <div class="tr-text">
                        <div class="tr-name">{{ __('Custom Signature') }}</div>
                        <div class="tr-note">{{ __('Any other signature requirement') }}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-primary check-icon {{ old('signature_type') === 'custom' ? '' : 'd-none' }}"
                       style="font-size:1rem;"></i>
                </label>

                @error('signature_type')
                    <div class="text-danger mt-1" style="font-size:.82rem;"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
                <div class="form-text mt-1">{{ __('Pickup and Delivery types are linked to workflow settings.') }}</div>
            </div>

            {{--  Signature Label  --}}
            <div class="mb-4">
                <label for="signature_label" class="form-label fw-semibold" style="font-size:.85rem;">
                    {{ __('Signature Label') }} <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="signature_label"
                       id="signature_label"
                       class="form-control @error('signature_label') is-invalid @enderror"
                       value="{{ old('signature_label') }}"
                       placeholder="{{ __('e.g., Delivery Signature, Pickup Authorization') }}"
                       required>
                @error('signature_label')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">{{ __('A short description shown to the customer when they sign.') }}</div>
            </div>

            {{--  Email Notification  --}}
            @if($job->customer && $job->customer->email)
            <div class="email-block mb-4">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox"
                           name="send_email" id="send_email" value="1"
                           {{ old('send_email', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="send_email" style="font-size:.85rem;">
                        <i class="bi bi-envelope me-1 text-primary"></i>{{ __('Send email notification to customer') }}
                    </label>
                </div>
                <div class="ms-4 text-muted" style="font-size:.78rem;">
                    {{ __('Signature link will be sent to') }} <strong>{{ $job->customer->email }}</strong>
                </div>
            </div>
            @endif

            <hr class="mb-3">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i>{{ __('Generate Request') }}
                </button>
            </div>
        </form>
    </div>

    {{-- How it works hint --}}
    <div class="d-flex align-items-start gap-2 mt-3 px-1" style="font-size:.78rem;color:#9ca3af;">
        <i class="bi bi-info-circle mt-1 flex-shrink-0"></i>
        <span>{{ __('A unique signing link is generated and optionally emailed. The customer signs remotely and the result is saved to the job automatically.') }}</span>
    </div>
</div>
@endsection

@push('page-styles')
<style>
    /*  Context strip  */
    .ctx-strip {
        background: #fff;
        border-radius: .5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 8px rgba(0,0,0,.04);
        padding: 1rem 1.25rem;
    }
    .ctx-item { display: flex; flex-direction: column; gap: .15rem; }
    .ctx-key  { font-size: .67rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; }
    .ctx-val  { font-size: .84rem; font-weight: 500; color: #1f2937; }

    /*  Form card  */
    .form-card {
        background: #fff;
        border-radius: .5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 8px rgba(0,0,0,.04);
    }

    /*  Section label  */
    .section-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; }

    /*  Type radio rows  */
    .type-row {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .85rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: .45rem;
        cursor: pointer;
        transition: border-color .12s ease, background .12s ease;
        margin-bottom: .5rem;
        user-select: none;
    }
    .type-row:last-of-type { margin-bottom: 0; }
    .type-row:hover    { border-color: #93c5fd; background: #f8fbff; }
    .type-row.selected { border-color: #2563eb; background: #eff6ff; }
    .type-row input[type=radio] { width: 16px; height: 16px; flex-shrink: 0; accent-color: #2563eb; }
    .type-row .tr-icon {
        width: 36px; height: 36px; border-radius: .4rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .type-row .tr-text { flex: 1; }
    .type-row .tr-name { font-weight: 600; font-size: .84rem; color: #374151; }
    .type-row .tr-note { font-size: .74rem; color: #9ca3af; }
    .type-row.selected .tr-name { color: #1d4ed8; }
    .pickup-row   .tr-icon { background: rgba(6,182,212,.10);   color: #0e7490; }
    .delivery-row .tr-icon { background: rgba(245,158,11,.10);  color: #b45309; }
    .custom-row   .tr-icon { background: rgba(107,114,128,.10); color: #4b5563; }

    /*  Email block  */
    .email-block {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: .45rem;
        padding: .85rem 1rem;
    }

    /* Dark mode adjustments */
    [data-bs-theme="dark"] .ctx-strip,
    [data-bs-theme="dark"] .form-card {
        background: var(--bs-body-bg);
        box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    [data-bs-theme="dark"] .type-row { border-color: rgba(255,255,255,.1); background: transparent; }
    [data-bs-theme="dark"] .type-row:hover    { border-color: #3b82f6; background: rgba(59,130,246,.08); }
    [data-bs-theme="dark"] .type-row.selected { border-color: #2563eb; background: rgba(37,99,235,.15); }
    [data-bs-theme="dark"] .email-block { background: rgba(14,165,233,.08); border-color: rgba(14,165,233,.2); }
</style>
@endpush

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const autoLabels = {
        'pickup':   '{{ __("Pickup Signature") }}',
        'delivery': '{{ __("Delivery Signature") }}',
        'custom':   ''
    };

    window.rowSelect = function (rowEl, value, defaultLabel) {
        document.querySelectorAll('.type-row').forEach(function (r) {
            r.classList.remove('selected');
            r.querySelector('.check-icon').classList.add('d-none');
        });
        rowEl.classList.add('selected');
        rowEl.querySelector('.check-icon').classList.remove('d-none');

        const labelInput = document.getElementById('signature_label');
        const current    = labelInput.value.trim();
        const wasAuto    = Object.values(autoLabels).includes(current) || current === '';
        if (wasAuto) {
            labelInput.value = defaultLabel;
            if (!defaultLabel) { labelInput.focus(); }
        }
    };
});
</script>
@endpush

@extends('tenant.layouts.myaccount', ['title' => __('New Signature Request')])

@section('content')

{{-- Flash messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <strong>{{ __('Please fix the following errors:') }}</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="sig-create-wrap">

{{-- Hero header --}}
<div class="sig-hero-header mb-4">
    <div class="hero-left">
        <div class="sig-hero-icon">
            <i class="bi bi-pen-fill"></i>
        </div>
        <div>
            <h4>{{ __('New Signature Request') }}</h4>
            <div class="hero-subtitle">{{ __('Send a digital signature request to your customer') }}</div>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
        <a href="{{ route('tenant.signatures.index', ['business' => $tenant->slug, 'jobId' => $job->id]) }}" class="btn-hero-back">
            <i class="bi bi-list-ul me-1"></i>{{ __('All Requests') }}
        </a>
        <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}" class="btn-hero-back">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
        </a>
    </div>
</div>

{{-- 2-column layout --}}
<div class="row g-4">

    {{-- =====================  LEFT SIDEBAR  ===================== --}}
    <div class="col-lg-4">

        {{-- Combined context card --}}
        @php
            $statusMap = ['open'=>'primary','in_progress'=>'warning','completed'=>'success','cancelled'=>'danger','pending'=>'secondary'];
            $statusColor = $statusMap[$job->status ?? ''] ?? 'secondary';
        @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">

                {{-- Customer row --}}
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="customer-avatar-sm flex-shrink-0">
                        {{ strtoupper(substr($job->customer->name ?? 'C', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-semibold lh-1" style="font-size:.85rem;color:#111827;">{{ $job->customer->name ?? __('Unknown') }}</div>
                        @if(!empty($job->customer->email))
                        <div class="text-truncate text-muted" style="font-size:.73rem;" title="{{ $job->customer->email }}">{{ $job->customer->email }}</div>
                        @endif
                    </div>
                    @if(!empty($job->customer->phone_number))
                    <a href="tel:{{ $job->customer->phone_number }}" class="text-muted flex-shrink-0" style="font-size:.8rem;" title="{{ $job->customer->phone_number }}">
                        <i class="bi bi-telephone"></i>
                    </a>
                    @endif
                </div>

                {{-- Key-value grid --}}
                <div class="ctx-grid">
                    <span class="ctx-k"><i class="bi bi-hash me-1 opacity-50"></i>{{ __('Job') }}</span>
                    <span class="ctx-v">#{{ $job->job_number }}</span>

                    @if($job->case_number)
                    <span class="ctx-k"><i class="bi bi-file-earmark me-1 opacity-50"></i>{{ __('Case') }}</span>
                    <span class="ctx-v">{{ $job->case_number }}</span>
                    @endif

                    <span class="ctx-k"><i class="bi bi-activity me-1 opacity-50"></i>{{ __('Status') }}</span>
                    <span class="ctx-v">
                        <span class="badge rounded-pill text-bg-{{ $statusColor }} fw-normal" style="font-size:.68rem;">
                            {{ ucfirst(str_replace('_', ' ', $job->status ?? __('Unknown'))) }}
                        </span>
                    </span>

                    <span class="ctx-k"><i class="bi bi-calendar3 me-1 opacity-50"></i>{{ __('Created') }}</span>
                    <span class="ctx-v">{{ $job->created_at->format('M d, Y') }}</span>

                    @if($job->jobDevices && $job->jobDevices->count())
                    <span class="ctx-k"><i class="bi bi-phone me-1 opacity-50"></i>{{ __('Devices') }}</span>
                    <span class="ctx-v d-flex flex-wrap gap-1">
                        @foreach($job->jobDevices as $jd)
                        @php $dev = $jd->customerDevice->device ?? null; @endphp
                        <span class="badge text-bg-light border fw-normal" style="font-size:.68rem;">{{ $dev->name ?? '—' }}</span>
                        @endforeach
                    </span>
                    @endif
                </div>

            </div>
        </div>

        {{-- How It Works --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-lightbulb-fill text-success" style="font-size:.8rem;"></i>
                <span class="fw-semibold" style="font-size:.8rem;">{{ __('How It Works') }}</span>
            </div>
            <div class="card-body p-3">
                <ol class="how-steps mb-0">
                    <li>{{ __('Choose a signature type') }}</li>
                    <li>{{ __('A secure link is generated') }}</li>
                    <li>{{ __('Customer signs on any device') }}</li>
                    <li>{{ __('Timestamped & tamper-evident') }}</li>
                </ol>
            </div>
        </div>

    </div>{{-- /col-lg-4 --}}

    {{-- =====================  RIGHT COLUMN: FORM  ===================== --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">{{ __('Request Details') }}</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('tenant.signatures.store', ['business' => $tenant->slug, 'jobId' => $job->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" id="type-hidden" value="{{ old('type', 'pickup') }}">

                    {{-- Signature type --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold">{{ __('Signature Type') }}</label>

                        <label class="type-row pickup-row {{ old('type','pickup') === 'pickup' ? 'selected' : '' }}"
                               onclick="rowSelect(this, 'pickup', '{{ __('Pickup Signature') }}')">
                            <input type="radio" name="_type_ui" value="pickup" class="d-none" {{ old('type','pickup') === 'pickup' ? 'checked' : '' }}>
                            <span class="tr-icon pickup-icon"><i class="bi bi-box-arrow-up"></i></span>
                            <span class="tr-body">
                                <span class="tr-name">{{ __('Pickup') }}</span>
                                <span class="tr-note">{{ __('Customer confirms they are collecting their device.') }}</span>
                            </span>
                            <span class="tr-check"><i class="bi bi-check-lg"></i></span>
                        </label>

                        <label class="type-row delivery-row {{ old('type') === 'delivery' ? 'selected' : '' }}"
                               onclick="rowSelect(this, 'delivery', '{{ __('Delivery Signature') }}')">
                            <input type="radio" name="_type_ui" value="delivery" class="d-none" {{ old('type') === 'delivery' ? 'checked' : '' }}>
                            <span class="tr-icon delivery-icon"><i class="bi bi-truck"></i></span>
                            <span class="tr-body">
                                <span class="tr-name">{{ __('Delivery') }}</span>
                                <span class="tr-note">{{ __('Customer confirms receipt of a delivered device.') }}</span>
                            </span>
                            <span class="tr-check"><i class="bi bi-check-lg"></i></span>
                        </label>

                        <label class="type-row custom-row {{ old('type') === 'custom' ? 'selected' : '' }}"
                               onclick="rowSelect(this, 'custom', '')">
                            <input type="radio" name="_type_ui" value="custom" class="d-none" {{ old('type') === 'custom' ? 'checked' : '' }}>
                            <span class="tr-icon custom-icon"><i class="bi bi-pencil-square"></i></span>
                            <span class="tr-body">
                                <span class="tr-name">{{ __('Custom') }}</span>
                                <span class="tr-note">{{ __('Write your own label for any other purpose.') }}</span>
                            </span>
                            <span class="tr-check"><i class="bi bi-check-lg"></i></span>
                        </label>
                    </div>

                    <div class="sig-divider my-4"><span>{{ __('Details') }}</span></div>

                    {{-- Label --}}
                    <div class="mb-4">
                        <label for="signature_label" class="form-label fw-semibold">{{ __('Signature Label') }}</label>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted" style="font-size:.72rem;">{{ __('Visible to the customer when they open the signature link.') }}</span>
                            <span id="label-counter" class="text-muted" style="font-size:.72rem;white-space:nowrap;">0 / 255</span>
                        </div>
                        <input type="text"
                               class="form-control @error('label') is-invalid @enderror"
                               id="signature_label"
                               name="label"
                               placeholder="{{ __("e.g. Pickup Signature — John's iPhone 14") }}"
                               value="{{ old('label', 'Pickup Signature') }}"
                               maxlength="255">
                        @error('label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email notification strip --}}
                    @if(!empty($job->customer->email))
                    <div class="email-strip d-flex align-items-center gap-3 mb-4">
                        <div class="email-icon-wrap flex-shrink-0">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div style="font-size:.8rem;font-weight:600;color:#1f2937;">{{ __('Email Notification') }}</div>
                            <div class="text-truncate" style="font-size:.74rem;color:#6b7280;">
                                {{ __('Send a copy of the link to') }}
                                <strong>{{ $job->customer->email }}</strong>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0 flex-shrink-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="send_email" name="send_email" value="1"
                                   {{ old('send_email', '1') ? 'checked' : '' }}>
                        </div>
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 pt-3 border-top flex-wrap">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.74rem;">
                            <i class="bi bi-shield-lock-fill text-success" style="font-size:.85rem;"></i>
                            {{ __('Signatures are timestamped, IP-logged, and tamper-evident.') }}
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('tenant.signatures.index', ['business' => $tenant->slug, 'jobId' => $job->id]) }}" class="btn btn-outline-secondary btn-sm">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-pen-fill me-1"></i>{{ __('Generate Request') }}
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>{{-- /col-lg-8 --}}

</div>{{-- /row --}}

</div>{{-- /sig-create-wrap --}}

@endsection

@push('page-styles')
<style>
    /* Design tokens */
    :root {
        --rb-primary: #3B82F6;
        --rb-primary-dark: #1D4ED8;
        --rb-primary-rgb: 59, 130, 246;
        --rb-hero-bg: radial-gradient(circle at center, #4b5563 0%, #1f2937 100%);
        --rb-card-border: #e2e8f0;
        --rb-text-muted: #64748b;
        --rb-text-dark: #0f172a;
    }

    /* Layout wrapper */
    .sig-create-wrap { max-width: 1100px; margin: 0 auto; }

    /* Hero header */
    .sig-hero-header {
        background: var(--rb-hero-bg);
        border-radius: 16px;
        padding: 1.75rem 2rem;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .sig-hero-header .hero-left {
        display: flex; align-items: center; gap: 1rem;
        flex: 1; min-width: 0;
    }
    .sig-hero-icon {
        width: 48px; height: 48px;
        background: rgba(255,255,255,.12);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
        border: 1px solid rgba(255,255,255,.18);
    }
    .sig-hero-header h4 {
        font-size: 1.35rem; font-weight: 700;
        margin: 0; color: #fff !important;
    }
    .sig-hero-header .hero-subtitle {
        font-size: .85rem; opacity: .75; margin-top: .15rem;
    }
    .btn-hero-back {
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.2);
        color: white;
        padding: .5rem 1rem;
        border-radius: 8px;
        font-size: .85rem; font-weight: 500;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .4rem;
        transition: background .2s;
        white-space: nowrap;
    }
    .btn-hero-back:hover { background: rgba(255,255,255,.2); color: white; }

    /* Form elements (matching job create) */
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: .4rem;
        font-size: .875rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: .6rem .875rem;
        font-size: .9rem;
        transition: border-color .2s ease, box-shadow .2s ease;
        background-color: #fff;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--rb-primary);
        box-shadow: 0 0 0 3px rgba(var(--rb-primary-rgb), .12);
    }

    /* Sidebar section icon */
    .sb-section-icon {
        width: 28px; height: 28px; border-radius: .4rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; flex-shrink: 0;
    }

    /* Compact context grid (2-col: label | value) */
    .ctx-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: .28rem .75rem;
        align-items: center;
    }
    .ctx-k {
        font-size: .72rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em;
        color: #9ca3af; white-space: nowrap;
    }
    .ctx-v {
        font-size: .8rem; font-weight: 500; color: #1f2937;
    }

    /* Small customer avatar */
    .customer-avatar-sm {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 700; flex-shrink: 0;
    }

    .how-steps {
        list-style: none;
        counter-reset: step;
        padding-left: 0;
        margin: 0;
    }
    .how-steps li {
        counter-increment: step;
        padding-left: 1.75rem;
        position: relative;
        padding-bottom: .55rem;
        font-size: .8rem;
        color: #374151;
    }
    .how-steps li:last-child { padding-bottom: 0; }
    .how-steps li::before {
        content: counter(step);
        position: absolute;
        left: 0; top: 1px;
        width: 18px; height: 18px;
        background: rgba(16,185,129,.15);
        color: #059669;
        border-radius: 50%;
        font-size: .65rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }
    .how-steps li::after {
        content: '';
        position: absolute;
        left: 8px; top: 21px; bottom: 0;
        width: 1px;
        background: #e5e7eb;
    }
    .how-steps li:last-child::after { display: none; }

    /* ----  Form (right column)  ---- */

    /* Ornamental divider */
    .sig-divider { display: flex; align-items: center; gap: .75rem; }
    .sig-divider::before, .sig-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
    .sig-divider span { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #d1d5db; white-space: nowrap; }

    /* Type rows */
    .type-row {
        display: flex; align-items: center; gap: 1rem;
        padding: .9rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: .5rem;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        margin-bottom: .5rem;
        user-select: none;
        position: relative; overflow: hidden;
    }
    .type-row:last-of-type { margin-bottom: 0; }
    .type-row::before {
        content: '';
        position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
        background: transparent;
        transition: background .15s ease;
        border-radius: .5rem 0 0 .5rem;
    }
    .type-row:hover    { border-color: #bfdbfe; background: #f8fbff; box-shadow: 0 1px 4px rgba(37,99,235,.08); }
    .type-row.selected { border-color: #93c5fd; background: #eff6ff; box-shadow: 0 1px 6px rgba(37,99,235,.12); }
    .type-row.selected::before { background: #2563eb; }

    .tr-icon {
        width: 42px; height: 42px; border-radius: .5rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        transition: transform .15s ease;
    }
    .type-row.selected .tr-icon { transform: scale(1.08); }
    .pickup-icon   { background: rgba(6,182,212,.10);   color: #0e7490; }
    .delivery-icon { background: rgba(245,158,11,.10);  color: #b45309; }
    .custom-icon   { background: rgba(107,114,128,.10); color: #4b5563; }

    .tr-body { flex: 1; min-width: 0; }
    .tr-name { font-weight: 600; font-size: .85rem; color: #374151; transition: color .15s; }
    .tr-note { font-size: .74rem; color: #9ca3af; line-height: 1.4; margin-top: .1rem; }
    .type-row.selected .tr-name { color: #1d4ed8; }

    .tr-check {
        width: 20px; height: 20px; border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; color: transparent;
        transition: all .15s ease;
        font-size: .75rem;
    }
    .type-row.selected .tr-check { border-color: #2563eb; background: #2563eb; color: #fff; }

    /* Email strip */
    .email-strip {
        background: linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 100%);
        border: 1px solid #bae6fd;
        border-radius: .5rem;
        padding: .9rem 1.1rem;
    }
    .email-icon-wrap {
        width: 36px; height: 36px; border-radius: .45rem;
        background: rgba(37,99,235,.10); color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
    }

    /* Dark mode */
    [data-bs-theme="dark"] .card { background: var(--bs-body-bg); }
    [data-bs-theme="dark"] .ctx-v { color: var(--bs-body-color); }
    [data-bs-theme="dark"] .type-row { border-color: rgba(255,255,255,.1); background: transparent; }
    [data-bs-theme="dark"] .type-row:hover    { border-color: #3b82f6; background: rgba(59,130,246,.07); }
    [data-bs-theme="dark"] .type-row.selected { border-color: #60a5fa; background: rgba(37,99,235,.15); }
    [data-bs-theme="dark"] .tr-name  { color: var(--bs-body-color); }
    [data-bs-theme="dark"] .email-strip { background: rgba(14,165,233,.07); border-color: rgba(14,165,233,.2); }
    [data-bs-theme="dark"] .sig-divider::before, [data-bs-theme="dark"] .sig-divider::after { background: rgba(255,255,255,.1); }
    [data-bs-theme="dark"] .how-steps li::after { background: rgba(255,255,255,.1); }
</style>
@endpush

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const autoLabels = {
        pickup:   '{{ __("Pickup Signature") }}',
        delivery: '{{ __("Delivery Signature") }}',
        custom:   ''
    };

    window.rowSelect = function (rowEl, value, defaultLabel) {
        document.querySelectorAll('.type-row').forEach(function (r) {
            r.classList.remove('selected');
        });
        rowEl.classList.add('selected');
        document.getElementById('type-hidden').value = value;

        const labelInput = document.getElementById('signature_label');
        const current    = labelInput.value.trim();
        const wasAuto    = Object.values(autoLabels).includes(current) || current === '';
        if (wasAuto) {
            labelInput.value = defaultLabel;
            updateCounter();
            if (!defaultLabel) { labelInput.focus(); }
        }
    };

    const labelInput = document.getElementById('signature_label');
    const counterEl  = document.getElementById('label-counter');
    function updateCounter() {
        if (!labelInput || !counterEl) return;
        const len = labelInput.value.length;
        counterEl.textContent = len + ' / 255';
        counterEl.style.color = len > 230 ? '#ef4444' : '#9ca3af';
    }
    if (labelInput) {
        labelInput.addEventListener('input', updateCounter);
        updateCounter();
    }
});
</script>
@endpush

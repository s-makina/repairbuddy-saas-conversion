@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Signature Request'])

@php
    $sigStatus = $signatureRequest->status === 'completed'
        ? 'completed'
        : ($signatureRequest->isExpired() ? 'expired' : 'pending');

    $statusPillMap = [
        'completed' => ['pill' => 'wcrb-pill--active',  'icon' => 'bi-check-circle',    'label' => __('Completed')],
        'expired'   => ['pill' => 'wcrb-pill--danger',  'icon' => 'bi-clock',           'label' => __('Expired')],
        'pending'   => ['pill' => 'wcrb-pill--pending', 'icon' => 'bi-hourglass-split', 'label' => __('Pending')],
    ];
    $typePillMap = [
        'pickup'   => ['pill' => 'wcrb-pill--info',    'label' => __('Pickup')],
        'delivery' => ['pill' => 'wcrb-pill--warning', 'label' => __('Delivery')],
        'custom'   => ['pill' => 'wcrb-pill--muted',   'label' => __('Custom')],
    ];
    $statusBorderMap = [
        'completed' => 'border-success',
        'expired'   => 'border-danger',
        'pending'   => 'border-warning',
    ];
    $statusIconMap = [
        'completed' => 'bi-check-circle-fill text-success',
        'expired'   => 'bi-clock-fill text-danger',
        'pending'   => 'bi-hourglass-split text-warning',
    ];
    $statusBgMap = [
        'completed' => 'bg-success',
        'expired'   => 'bg-danger',
        'pending'   => 'bg-warning',
    ];
    $currentStatus   = $statusPillMap[$sigStatus];
    $currentType     = $typePillMap[$signatureRequest->signature_type] ?? $typePillMap['custom'];
    $currentBorder   = $statusBorderMap[$sigStatus];
    $currentIcon     = $statusIconMap[$sigStatus];
    $currentBg       = $statusBgMap[$sigStatus];
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h5 class="fw-bold mb-1">
                <i class="bi bi-pen me-2 text-primary"></i>{{ __('Signature Request Details') }}
            </h5>
            <p class="text-muted small mb-0">
                {{ __('Job') }} #{{ $job->case_number ?? $job->id }}
                @if($job->customer) &mdash; {{ $job->customer->name }} @endif
            </p>
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

    <div class="row g-4">

        {{-- ═══════════════════════════
             LEFT COLUMN — Context cards
             ═══════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Status Banner --}}
            <div class="card border-0 shadow-sm border-start {{ $currentBorder }} border-3 mb-3">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle {{ $currentBg }} bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;">
                        <i class="bi {{ $currentIcon }}" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <span class="wcrb-pill {{ $currentStatus['pill'] }}">
                            <i class="bi {{ $currentStatus['icon'] }}"></i>
                            {{ $currentStatus['label'] }}
                        </span>
                        @if($sigStatus === 'completed' && $signatureRequest->completed_at)
                            <div class="text-muted mt-1" style="font-size:.74rem;">
                                {{ __('Signed') }} {{ $signatureRequest->completed_at->format('M d, Y') }}
                                {{ __('at') }} {{ $signatureRequest->completed_at->format('H:i') }}
                            </div>
                        @elseif($sigStatus === 'pending' && $signatureRequest->expires_at)
                            <div class="text-muted mt-1" style="font-size:.74rem;">
                                {{ __('Expires') }} {{ $signatureRequest->expires_at->diffForHumans() }}
                            </div>
                        @elseif($sigStatus === 'expired')
                            <div class="text-muted mt-1" style="font-size:.74rem;">
                                {{ __('Expired') }} {{ $signatureRequest->expires_at?->format('M d, Y') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Request Info --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-tag me-2 text-muted"></i>{{ __('Request Info') }}</h6>
                </div>
                <div class="card-body" style="font-size:.85rem;">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Label') }}</span>
                            <span class="fw-medium text-truncate ms-2" style="max-width:160px;" title="{{ $signatureRequest->signature_label }}">
                                {{ $signatureRequest->signature_label }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Type') }}</span>
                            <span class="wcrb-pill {{ $currentType['pill'] }}">{{ $currentType['label'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Generated') }}</span>
                            <span class="fw-medium">{{ ($signatureRequest->generated_at ?? $signatureRequest->created_at)?->format('M d, Y H:i') }}</span>
                        </div>
                        @if($signatureRequest->expires_at)
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Expires') }}</span>
                            <span class="fw-medium {{ $sigStatus === 'expired' ? 'text-danger' : '' }}">
                                {{ $signatureRequest->expires_at->format('M d, Y H:i') }}
                            </span>
                        </div>
                        @endif
                        @if($signatureRequest->generator)
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('By') }}</span>
                            <span class="fw-medium">{{ $signatureRequest->generator->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Customer --}}
            @if($job->customer)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-muted"></i>{{ __('Customer') }}</h6>
                </div>
                <div class="card-body" style="font-size:.85rem;">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Name') }}</span>
                            <span class="fw-medium">{{ $job->customer->name }}</span>
                        </div>
                        @if($job->customer->email)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Email') }}</span>
                            <span class="fw-medium text-truncate ms-2" style="max-width:160px;">{{ $job->customer->email }}</span>
                        </div>
                        @endif
                        @if($job->customer->phone)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Phone') }}</span>
                            <span class="fw-medium">{{ $job->customer->phone }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Job Details --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-tools me-2 text-muted"></i>{{ __('Job Details') }}</h6>
                </div>
                <div class="card-body" style="font-size:.85rem;">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Job #') }}</span>
                            <span class="fw-bold text-primary">{{ $job->job_number ?? $job->id }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Case #') }}</span>
                            <span class="fw-medium">{{ $job->case_number ?? '—' }}</span>
                        </div>
                        @if($job->status_label ?? $job->status_slug)
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Status') }}</span>
                            <span class="badge bg-secondary" style="font-size:.72rem;">
                                {{ $job->status_label ?? ucfirst(str_replace('-', ' ', $job->status_slug ?? '')) }}
                            </span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Created') }}</span>
                            <span class="fw-medium">{{ $job->created_at?->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════
             RIGHT COLUMN — Link → Preview → Log
             ═══════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- 1 ── Signature Link (TOP) --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-link-45deg me-2 text-primary"></i>{{ __('Signature Link') }}
                    </h6>
                </div>
                <div class="card-body">
                    {{-- URL --}}
                    <div class="mb-3">
                        <label class="form-label mb-1"
                               style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#9ca3af;">
                            {{ __('Public URL') }}
                        </label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control bg-light"
                                   id="sig-url-input"
                                   value="{{ $signatureUrl }}"
                                   readonly>
                            <button class="btn btn-outline-primary" type="button" id="sig-copy-btn">
                                <i class="bi bi-clipboard me-1"></i>{{ __('Copy') }}
                            </button>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $signatureUrl }}" target="_blank"
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Open Page') }}
                        </a>

                        @if($job->customer && $job->customer->email)
                        <form method="POST"
                              action="{{ route('tenant.signatures.send', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $signatureRequest->id]) }}"
                              onsubmit="return confirm('{{ __('Send signature request email to customer?') }}')">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i>{{ __('Resend Email') }}
                            </button>
                        </form>
                        @endif

                        @if($sigStatus !== 'completed')
                        <a href="{{ route('tenant.signatures.generator', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $signatureRequest->id]) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>{{ __('Regenerate') }}
                        </a>
                        @endif

                        @if($sigStatus === 'completed' && $signatureRequest->signature_file_path)
                        <a href="{{ $signatureRequest->signature_file_path }}" download
                           class="btn btn-outline-secondary btn-sm ms-auto">
                            <i class="bi bi-download me-1"></i>{{ __('Download') }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2 ── Signature Preview --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-pen me-2 text-primary"></i>{{ __('Signature') }}
                    </h6>
                    @if($sigStatus === 'completed' && $signatureRequest->signature_file_path)
                    <a href="{{ $signatureRequest->signature_file_path }}" target="_blank" download
                       class="btn btn-primary btn-sm" style="padding:.25rem .65rem;font-size:.78rem;">
                        <i class="bi bi-download me-1"></i>{{ __('Download') }}
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    @if($sigStatus === 'completed' && $signatureRequest->signature_file_path)
                        {{-- Actual signature image --}}
                        <div class="sig-preview-wrap d-flex align-items-center justify-content-center"
                             style="background:#fff;border:2px solid #e5e7eb;border-radius:.5rem;min-height:180px;padding:1rem;">
                            <img src="{{ $signatureRequest->signature_file_path }}"
                                 alt="{{ __('Customer Signature') }}"
                                 style="max-width:100%;max-height:220px;object-fit:contain;">
                        </div>
                        @if($signatureRequest->completed_at)
                        <p class="text-center text-muted mb-0 mt-2" style="font-size:.75rem;">
                            {{ __('Signed on') }}
                            <strong>{{ $signatureRequest->completed_at->format('F d, Y') }}</strong>
                            {{ __('at') }}
                            <strong>{{ $signatureRequest->completed_at->format('H:i') }}</strong>
                            @if($signatureRequest->completed_ip)
                                &nbsp;&middot;&nbsp; IP: {{ $signatureRequest->completed_ip }}
                            @endif
                        </p>
                        @endif
                    @elseif($sigStatus === 'pending')
                        <div class="sig-preview-empty d-flex flex-column align-items-center justify-content-center text-muted"
                             style="border:2px dashed #e5e7eb;border-radius:.5rem;min-height:180px;">
                            <i class="bi bi-pen" style="font-size:2rem;opacity:.3;"></i>
                            <p class="mt-2 mb-0 small">{{ __('Awaiting customer signature') }}</p>
                            <p class="mb-0" style="font-size:.75rem;">
                                {{ __('Share the link above with your customer.') }}
                            </p>
                        </div>
                    @else
                        <div class="sig-preview-empty d-flex flex-column align-items-center justify-content-center text-muted"
                             style="border:2px dashed #fecaca;border-radius:.5rem;min-height:180px;background:rgba(239,68,68,.03);">
                            <i class="bi bi-clock text-danger" style="font-size:2rem;opacity:.5;"></i>
                            <p class="mt-2 mb-0 small text-danger">{{ __('Signature link expired') }}</p>
                            <p class="mb-0" style="font-size:.75rem;">{{ __('Generate a new request to collect the signature.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3 ── Activity Log --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2 text-muted"></i>{{ __('Activity Log') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="audit-track">
                        @foreach($auditLog as $entry)
                        <div class="audit-item {{ !$loop->last ? 'mb-3' : '' }}">
                            <div class="audit-dot audit-dot--{{ $entry['color'] }}"></div>
                            <div>
                                <div class="fw-medium" style="font-size:.84rem;">{{ $entry['label'] }}</div>
                                <div class="text-muted" style="font-size:.76rem;">
                                    {{ $entry['timestamp']?->format('M d, Y H:i') }}
                                    @if(!empty($entry['meta']))
                                        &middot; {{ $entry['meta'] }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-muted small mt-3 pt-2 border-top px-1">
                <i class="bi bi-shield-check me-1"></i>
                {{ __('Signature ID') }}: #SIG-{{ $job->id }}-{{ $signatureRequest->id }}
                &middot; {{ __('Code') }}: {{ $signatureRequest->verification_code }}
            </div>

        </div>{{-- /col right --}}
    </div>{{-- /row --}}
</div>
@endsection

@push('page-styles')
<style>
    /* ── Pill badges (shared with index) ── */
    .wcrb-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .24rem .7rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .wcrb-pill--active  { color: #065f46; background: rgba(16,185,129,.10);  border-color: rgba(16,185,129,.25); }
    .wcrb-pill--pending { color: #92400e; background: rgba(245,158,11,.10);  border-color: rgba(245,158,11,.25); }
    .wcrb-pill--danger  { color: #991b1b; background: rgba(239,68,68,.10);   border-color: rgba(239,68,68,.25); }
    .wcrb-pill--info    { color: #155e75; background: rgba(6,182,212,.10);   border-color: rgba(6,182,212,.25); }
    .wcrb-pill--warning { color: #78350f; background: rgba(245,158,11,.10);  border-color: rgba(245,158,11,.25); }
    .wcrb-pill--muted   { color: #4b5563; background: rgba(107,114,128,.10); border-color: rgba(107,114,128,.25); }

    [data-bs-theme="dark"] .wcrb-pill--active  { background: rgba(16,185,129,.20); }
    [data-bs-theme="dark"] .wcrb-pill--pending { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger  { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--info    { background: rgba(6,182,212,.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--muted   { background: rgba(107,114,128,.20); }

    /* ── URL input ── */
    .form-control.bg-light { background-color: #f3f4f6 !important; border-color: #e5e7eb; font-size: .84rem; }

    /* ── Activity log track ── */
    .audit-track { position: relative; padding-left: 1.4rem; }
    .audit-track::before {
        content: '';
        position: absolute;
        left: .3rem;
        top: .6rem;
        bottom: .6rem;
        width: 2px;
        background: #e5e7eb;
        border-radius: 1px;
    }
    .audit-item { position: relative; }
    .audit-dot {
        position: absolute;
        left: -1.4rem;
        top: .22rem;
        width: 11px; height: 11px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        background: #fff;
    }
    .audit-dot--success { border-color: #10b981; background: #10b981; }
    .audit-dot--info    { border-color: #2563eb; background: #2563eb; }
    .audit-dot--warning { border-color: #f59e0b; background: #f59e0b; }
    .audit-dot--danger  { border-color: #ef4444; background: #ef4444; }
</style>
@endpush

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn   = document.getElementById('sig-copy-btn');
    const input = document.getElementById('sig-url-input');
    if (!btn || !input) return;

    btn.addEventListener('click', function () {
        input.select();
        navigator.clipboard.writeText(input.value).then(function () {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check me-1"></i>{{ __("Copied!") }}';
            btn.classList.replace('btn-outline-primary', 'btn-success');
            setTimeout(function () {
                btn.innerHTML = orig;
                btn.classList.replace('btn-success', 'btn-outline-primary');
            }, 2000);
        });
    });
    input.addEventListener('focus', function () { this.select(); });
});
</script>
@endpush

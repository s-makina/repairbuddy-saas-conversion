@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Signature Request Generated'])

@section('content')
<div class="container-fluid p-3">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h5 class="fw-bold mb-1">
                <i class="bi bi-check-circle me-2 text-success"></i>{{ __('Signature Request Generated') }}
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
        {{-- Left Column: Context --}}
        <div class="col-lg-4">
            {{-- Customer Card --}}
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

            {{-- Job Card --}}
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
                            <span class="fw-medium">{{ $job->case_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Status') }}</span>
                            <span class="badge bg-secondary">{{ $job->status_slug }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Created') }}</span>
                            <span class="fw-medium">{{ $job->created_at?->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Signature URL --}}
        <div class="col-lg-8">
            {{-- Success Banner --}}
            <div class="card border-0 shadow-sm border-start border-success border-3 mb-3">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:1.25rem;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">{{ __('Signature Request Generated!') }}</h6>
                        <small class="text-muted">{{ __('Share the URL below with your customer to collect their signature.') }}</small>
                    </div>
                </div>
            </div>

            {{-- Share URL Card --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-link-45deg me-2 text-primary"></i>{{ __('Signature Link') }}</h6>
                </div>
                <div class="card-body p-4">
                    {{-- URL Input --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;font-size:.7rem;">
                            {{ __('Signature Request URL') }}
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" id="signatureUrl" value="{{ $signatureUrl }}" readonly>
                            <button class="btn btn-outline-primary" type="button" id="copyUrlBtn" title="{{ __('Copy URL') }}">
                                <i class="bi bi-clipboard"></i> {{ __('Copy') }}
                            </button>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $signatureUrl }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Open Page') }}
                        </a>
                        @if($job->customer && $job->customer->email)
                        <form method="POST"
                              action="{{ route('tenant.signatures.send', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $signatureRequest->id]) }}"
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i>{{ __('Send via Email') }}
                            </button>
                        </form>
                        @endif
                    </div>

                    <hr class="my-3">

                    {{-- Request Details --}}
                    <div class="row g-3" style="font-size:.85rem;">
                        <div class="col-sm-6">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('Label') }}</span>
                                    <span class="fw-medium">{{ $signatureRequest->signature_label }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('Type') }}</span>
                                    <span class="fw-medium">{{ ucfirst($signatureRequest->signature_type) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('Status') }}</span>
                                    @if($signatureRequest->isPending())
                                        <span class="badge rounded-pill" style="color:#92400e;background:rgba(245,158,11,.10);border:1px solid rgba(245,158,11,.25);font-size:.72rem;">{{ __('Pending') }}</span>
                                    @elseif($signatureRequest->isCompleted())
                                        <span class="badge rounded-pill" style="color:#065f46;background:rgba(16,185,129,.10);border:1px solid rgba(16,185,129,.25);font-size:.72rem;">{{ __('Completed') }}</span>
                                    @else
                                        <span class="badge rounded-pill" style="color:#991b1b;background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.25);font-size:.72rem;">{{ __('Expired') }}</span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('Expires') }}</span>
                                    <span class="fw-medium">{{ $signatureRequest->expires_at?->format('M d, Y H:i') ?? __('Never') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Settings Tip --}}
            <div class="d-flex align-items-center gap-2 mt-3 px-1">
                <i class="bi bi-info-circle text-muted"></i>
                <small class="text-muted">
                    {{ __('To automate signature requests via email/SMS, configure') }}
                    <a href="#" class="text-decoration-none">{{ __('Signature Settings') }}</a>.
                </small>
            </div>

            {{-- Footer --}}
            <div class="text-muted small mt-3 pt-2 border-top px-1">
                <i class="bi bi-shield-check me-1"></i>
                {{ __('Generated by') }} {{ $user->name ?? __('System') }} &middot; {{ now()->format('M d, Y H:i') }}
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copyUrlBtn');
    const urlInput = document.getElementById('signatureUrl');

    if (copyBtn && urlInput) {
        copyBtn.addEventListener('click', function() {
            urlInput.select();
            urlInput.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(urlInput.value).then(function() {
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="bi bi-check"></i> {{ __("Copied!") }}';
                copyBtn.classList.remove('btn-outline-primary');
                copyBtn.classList.add('btn-success');

                setTimeout(function() {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.classList.remove('btn-success');
                    copyBtn.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(function() {
                document.execCommand('copy');
            });
        });

        urlInput.addEventListener('focus', function() {
            this.select();
        });
    }
});
</script>
@endpush
@endsection

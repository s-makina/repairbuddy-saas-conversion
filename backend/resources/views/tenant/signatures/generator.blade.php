@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Signature Request Generated'])

@section('content')
<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-pen me-2"></i>{{ __('Signature Request Generated') }}
            </h4>
            <p class="text-muted mb-0">
                {{ __('Job') }} #{{ $job->case_number ?? $job->id }}
                @if($job->customer)
                    — {{ $job->customer->name }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.signatures.index', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-list me-1"></i>{{ __('All Requests') }}
            </a>
            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left: Job & Customer Details --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row">
                        {{-- Customer Information --}}
                        @if($job->customer)
                        <div class="col-12 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                        <i class="bi bi-person-circle me-2"></i>{{ __('Customer Information') }}
                                    </h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                <i class="bi bi-person me-1"></i>{{ __('Name') }}:
                                            </span>
                                            <span class="fw-medium">{{ $job->customer->name }}</span>
                                        </div>
                                        @if($job->customer->email)
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                <i class="bi bi-envelope me-1"></i>{{ __('Email') }}:
                                            </span>
                                            <span class="fw-medium">{{ $job->customer->email }}</span>
                                        </div>
                                        @endif
                                        @if($job->customer->phone)
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                <i class="bi bi-telephone me-1"></i>{{ __('Phone') }}:
                                            </span>
                                            <span class="fw-medium">{{ $job->customer->phone }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Job Information --}}
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                        <i class="bi bi-tools me-2"></i>{{ __('Job Information') }}
                                    </h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                <i class="bi bi-hash me-1"></i>{{ __('Order #') }}:
                                            </span>
                                            <span class="fw-bold text-primary">{{ $job->job_number ?? $job->id }}</span>
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                <i class="bi bi-folder me-1"></i>{{ __('Case #') }}:
                                            </span>
                                            <span class="fw-medium">{{ $job->case_number }}</span>
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                <i class="bi bi-info-circle me-1"></i>{{ __('Status') }}:
                                            </span>
                                            <span class="badge bg-secondary">{{ $job->status_slug }}</span>
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                <i class="bi bi-calendar me-1"></i>{{ __('Created') }}:
                                            </span>
                                            <span class="fw-medium">{{ $job->created_at?->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Signature URL --}}
        <div class="col-lg-7">
            <div class="alert alert-info d-block mb-3">
                <i class="bi bi-info-circle me-2"></i>
                {{ __('To generate automated signature request through email/sms please check settings.') }}
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-pen me-2"></i>{{ __('Signature Request Details') }}
                    </h6>
                </div>
                <div class="card-body p-4 text-center">
                    {{-- Success Banner --}}
                    <div class="alert alert-success mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>{{ __('Signature Request Generated!') }}</strong>
                        <p class="mb-0 mt-2">{{ __('Share this URL with the customer to collect their signature:') }}</p>
                    </div>

                    {{-- URL Input with Copy Button --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-link-45deg me-2"></i>{{ __('Signature Request URL:') }}
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="text"
                                   class="form-control"
                                   id="signatureUrl"
                                   value="{{ $signatureUrl }}"
                                   readonly>
                            <button class="btn btn-outline-primary" type="button" id="copyUrlBtn" title="{{ __('Copy URL') }}">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                            <a href="{{ $signatureUrl }}"
                               class="btn btn-success"
                               target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Open Signature Page') }}
                            </a>
                            @if($job->customer && $job->customer->email)
                            <form method="POST"
                                  action="{{ route('tenant.signatures.send', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $signatureRequest->id]) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-envelope me-1"></i>{{ __('Send via Email') }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    {{-- Request Details --}}
                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle me-2"></i>{{ __('Request Details:') }}
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>{{ __('Job ID:') }}</strong>
                                        {{ $job->job_number ?? $job->id }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>{{ __('Case Number:') }}</strong>
                                        {{ $job->case_number }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>{{ __('Signature Label:') }}</strong>
                                        {{ $signatureRequest->signature_label }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>{{ __('Signature Type:') }}</strong>
                                        {{ ucfirst($signatureRequest->signature_type) }}
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>{{ __('Status:') }}</strong>
                                        @if($signatureRequest->isPending())
                                            <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                                        @elseif($signatureRequest->isCompleted())
                                            <span class="badge bg-success">{{ __('Completed') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('Expired') }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>{{ __('Expires:') }}</strong>
                                        {{ $signatureRequest->expires_at?->format('M d, Y H:i') ?? __('Never') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Note --}}
            <div class="text-center mt-3 pt-2 border-top">
                <p class="text-muted small mb-0">
                    <i class="bi bi-shield-check me-1"></i>
                    {{ __('Generated by:') }}
                    {{ $user->name ?? __('System') }}
                    |
                    {{ now()->format('M d, Y H:i') }}
                </p>
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
                copyBtn.innerHTML = '<i class="bi bi-check"></i>';
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

@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Generate Signature Request'])

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
                <i class="bi bi-pen me-2"></i>{{ __('Generate Signature Request') }}
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
                <i class="bi bi-arrow-left me-1"></i>{{ __('All Signature Requests') }}
            </a>
            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Job Details Card --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-tools me-2"></i>{{ __('Job Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Job #') }}</span>
                            <span class="fw-bold text-primary">{{ $job->job_number ?? $job->id }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Case Number') }}</span>
                            <span class="fw-medium">{{ $job->case_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Status') }}</span>
                            <span class="badge bg-secondary">{{ $job->status_slug }}</span>
                        </div>
                        @if($job->customer)
                        <hr class="my-1">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Customer') }}</span>
                            <span class="fw-medium">{{ $job->customer->name }}</span>
                        </div>
                        @if($job->customer->email)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Email') }}</span>
                            <span class="fw-medium">{{ $job->customer->email }}</span>
                        </div>
                        @endif
                        @if($job->customer->phone)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Phone') }}</span>
                            <span class="fw-medium">{{ $job->customer->phone }}</span>
                        </div>
                        @endif
                        @endif
                        @if($job->pickup_date)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Pickup Date') }}</span>
                            <span class="fw-medium">{{ $job->pickup_date->format('M d, Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>{{ __('How it works:') }}</strong>
                        <ol class="mb-0 mt-2 ps-3">
                            <li>{{ __('Choose the signature type and enter a label.') }}</li>
                            <li>{{ __('A unique signature URL will be generated.') }}</li>
                            <li>{{ __('Share the URL with the customer or send it via email.') }}</li>
                            <li>{{ __('The customer signs on the page and the signature is saved to the job.') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generate Form --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pen me-2"></i>{{ __('Generate Signature Request') }}</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('tenant.signatures.store', ['business' => $tenant->slug, 'jobId' => $job->id]) }}">
                        @csrf

                        {{-- Signature Type --}}
                        <div class="mb-4">
                            <label for="signature_type" class="form-label fw-bold">
                                <i class="bi bi-tag me-1"></i>{{ __('Signature Type') }}
                                <span class="text-danger">*</span>
                            </label>
                            <select name="signature_type" id="signature_type" class="form-select form-select-lg @error('signature_type') is-invalid @enderror" required>
                                <option value="">{{ __('Select type...') }}</option>
                                @if(!empty($signatureSettings['pickup_enabled']))
                                <option value="pickup" {{ old('signature_type') === 'pickup' ? 'selected' : '' }}>
                                    {{ __('Pickup Signature') }}
                                </option>
                                @endif
                                @if(!empty($signatureSettings['delivery_enabled']))
                                <option value="delivery" {{ old('signature_type') === 'delivery' ? 'selected' : '' }}>
                                    {{ __('Delivery Signature') }}
                                </option>
                                @endif
                                <option value="custom" {{ old('signature_type') === 'custom' ? 'selected' : '' }}>
                                    {{ __('Custom Signature') }}
                                </option>
                            </select>
                            @error('signature_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ __('Pickup and Delivery types are linked to the workflow settings. Custom allows any label.') }}
                            </div>
                        </div>

                        {{-- Signature Label --}}
                        <div class="mb-4">
                            <label for="signature_label" class="form-label fw-bold">
                                <i class="bi bi-card-text me-1"></i>{{ __('Signature Label') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="signature_label"
                                   id="signature_label"
                                   class="form-control form-control-lg @error('signature_label') is-invalid @enderror"
                                   value="{{ old('signature_label') }}"
                                   placeholder="{{ __('e.g., Delivery Signature, Pickup Authorization, Work Completion') }}"
                                   required>
                            @error('signature_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ __('Enter a descriptive label for this signature request.') }}
                            </div>
                        </div>

                        {{-- Send Email Checkbox --}}
                        @if($job->customer && $job->customer->email)
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_email" id="send_email" value="1" {{ old('send_email', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="send_email">
                                    <i class="bi bi-envelope me-1"></i>{{ __('Send email notification to customer') }}
                                </label>
                            </div>
                            <div class="form-text ms-4">
                                {{ __('An email will be sent to') }} <strong>{{ $job->customer->email }}</strong> {{ __('with the signature link.') }}
                            </div>
                        </div>
                        @endif

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                               class="btn btn-outline-secondary rounded-pill px-4">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i>{{ __('Generate Signature Request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('signature_type');
    const labelInput = document.getElementById('signature_label');

    if (typeSelect && labelInput) {
        typeSelect.addEventListener('change', function() {
            const labels = {
                'pickup': '{{ __("Pickup Signature") }}',
                'delivery': '{{ __("Delivery Signature") }}',
                'custom': ''
            };
            if (labels[this.value] !== undefined && (labelInput.value === '' || labelInput.value === '{{ __("Pickup Signature") }}' || labelInput.value === '{{ __("Delivery Signature") }}')) {
                labelInput.value = labels[this.value];
            }
        });
    }
});
</script>
@endpush
@endsection

@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Signature Requests'])

@section('content')
<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-pen me-2"></i>{{ __('Signature Requests') }}
            </h4>
            <p class="text-muted mb-0">
                {{ __('Job') }} #{{ $job->case_number ?? $job->id }}
                @if($job->customer)
                    — {{ $job->customer->name }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.signatures.create', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="bi bi-plus-circle me-1"></i>{{ __('New Signature Request') }}
            </a>
            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
               class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
            </a>
        </div>
    </div>

    {{-- Signature Settings Summary --}}
    @php
        $pickupEnabled = !empty($signatureSettings['pickup_enabled']);
        $deliveryEnabled = !empty($signatureSettings['delivery_enabled']);
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center {{ $pickupEnabled ? 'bg-success' : 'bg-secondary' }} bg-opacity-10" style="width:48px;height:48px;">
                        <i class="bi bi-box-arrow-up {{ $pickupEnabled ? 'text-success' : 'text-secondary' }}" style="font-size:1.25rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ __('Pickup Signature') }}</div>
                        <small class="text-muted">{{ $pickupEnabled ? __('Enabled') : __('Disabled') }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center {{ $deliveryEnabled ? 'bg-success' : 'bg-secondary' }} bg-opacity-10" style="width:48px;height:48px;">
                        <i class="bi bi-box-arrow-down {{ $deliveryEnabled ? 'text-success' : 'text-secondary' }}" style="font-size:1.25rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ __('Delivery Signature') }}</div>
                        <small class="text-muted">{{ $deliveryEnabled ? __('Enabled') : __('Disabled') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Signature Requests Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($signatureRequests->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-pen text-muted" style="font-size:3rem;"></i>
                    <p class="text-muted mt-3 mb-0">{{ __('No signature requests yet for this job.') }}</p>
                    <a href="{{ route('tenant.signatures.create', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                       class="btn btn-primary btn-sm rounded-pill px-3 mt-3">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('Create First Signature Request') }}
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('Label') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Generated') }}</th>
                                <th>{{ __('Completed') }}</th>
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($signatureRequests as $sig)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-medium">{{ $sig->signature_label }}</div>
                                </td>
                                <td>
                                    @php
                                        $typeColors = ['pickup' => 'info', 'delivery' => 'warning', 'custom' => 'secondary'];
                                        $typeColor = $typeColors[$sig->signature_type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $typeColor }}">{{ ucfirst($sig->signature_type) }}</span>
                                </td>
                                <td>
                                    @if($sig->status === 'completed')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>{{ __('Completed') }}</span>
                                    @elseif($sig->isExpired())
                                        <span class="badge bg-danger"><i class="bi bi-clock me-1"></i>{{ __('Expired') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $sig->generated_at?->format('M d, Y H:i') }}</small>
                                    @if($sig->generator)
                                        <br><small class="text-muted">{{ __('by') }} {{ $sig->generator->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($sig->completed_at)
                                        <small class="text-muted">{{ $sig->completed_at->format('M d, Y H:i') }}</small>
                                        @if($sig->completed_ip)
                                            <br><small class="text-muted">IP: {{ $sig->completed_ip }}</small>
                                        @endif
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    @if($sig->isPending() && !$sig->isExpired())
                                        <a href="{{ route('tenant.signatures.generator', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                           class="btn btn-outline-primary btn-sm me-1" title="{{ __('View / Share Link') }}">
                                            <i class="bi bi-link-45deg"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('tenant.signatures.send', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('{{ __('Send signature request email to customer?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="{{ __('Send Email') }}">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        </form>
                                    @elseif($sig->isCompleted() && $sig->signature_file_path)
                                        <a href="{{ $sig->signature_file_path }}" target="_blank"
                                           class="btn btn-outline-primary btn-sm" title="{{ __('View Signature') }}">
                                            <i class="bi bi-image"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

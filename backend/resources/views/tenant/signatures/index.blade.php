@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Signature Requests'])

@php
    $countPending   = 0;
    $countCompleted = 0;
    $countExpired   = 0;
    foreach ($signatureRequests as $sig) {
        if ($sig->status === 'completed') { $countCompleted++; }
        elseif ($sig->isExpired())        { $countExpired++; }
        else                              { $countPending++; }
    }
    $countTotal = $signatureRequests->count();

    $pickupEnabled   = !empty($signatureSettings['pickup_enabled']);
    $deliveryEnabled = !empty($signatureSettings['delivery_enabled']);

    $statusBadgeMap = [
        'completed' => 'wcrb-pill--active',
        'expired'   => 'wcrb-pill--danger',
        'pending'   => 'wcrb-pill--pending',
    ];
    $typeBadgeMap = [
        'pickup'   => 'wcrb-pill--info',
        'delivery' => 'wcrb-pill--warning',
        'custom'   => 'wcrb-pill--muted',
    ];
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-primary text-white border-0">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;opacity:.85;">{{ __('Pending') }}</div>
                            <h4 class="mb-0">{{ $countPending }}</h4>
                        </div>
                        <div style="font-size:1.5rem;opacity:.4;"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-success text-white border-0">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;opacity:.85;">{{ __('Completed') }}</div>
                            <h4 class="mb-0">{{ $countCompleted }}</h4>
                        </div>
                        <div style="font-size:1.5rem;opacity:.4;"><i class="bi bi-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-danger text-white border-0">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;opacity:.85;">{{ __('Expired') }}</div>
                            <h4 class="mb-0">{{ $countExpired }}</h4>
                        </div>
                        <div style="font-size:1.5rem;opacity:.4;"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-secondary text-white border-0">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;opacity:.85;">{{ __('Total') }}</div>
                            <h4 class="mb-0">{{ $countTotal }}</h4>
                        </div>
                        <div style="font-size:1.5rem;opacity:.4;"><i class="bi bi-pen"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    {{-- Workflow Badges --}}
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <span class="text-muted small fw-semibold me-1">{{ __('Workflow:') }}</span>
        <span class="badge rounded-pill {{ $pickupEnabled ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }} border px-2 py-1" style="font-size:.75rem;">
            <i class="bi bi-box-arrow-up me-1"></i>{{ __('Pickup') }} {{ $pickupEnabled ? __('ON') : __('OFF') }}
        </span>
        <span class="badge rounded-pill {{ $deliveryEnabled ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }} border px-2 py-1" style="font-size:.75rem;">
            <i class="bi bi-box-arrow-down me-1"></i>{{ __('Delivery') }} {{ $deliveryEnabled ? __('ON') : __('OFF') }}
        </span>
    </div>

    {{-- Main Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-pen me-2 text-primary"></i>{{ __('Signature Requests') }}
                </h6>
                <small class="text-muted">
                    {{ __('Job') }} #{{ $job->case_number ?? $job->id }}
                    @if($job->customer) &mdash; {{ $job->customer->name }} @endif
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('tenant.signatures.create', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                   class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('New Request') }}
                </a>
                <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Job') }}
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if($signatureRequests->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                            <i class="bi bi-pen text-primary" style="font-size:1.75rem;"></i>
                        </div>
                    </div>
                    <h6 class="fw-semibold mb-1">{{ __('No Signature Requests Yet') }}</h6>
                    <p class="text-muted small mb-3">{{ __('Create a signature request to collect a customer signature for this job.') }}</p>
                    <a href="{{ route('tenant.signatures.create', ['business' => $tenant->slug, 'jobId' => $job->id]) }}"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Create First Request') }}
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                        <thead>
                            <tr class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;">
                                <th class="fw-semibold ps-3 border-0 pb-2">{{ __('Label') }}</th>
                                <th class="fw-semibold border-0 pb-2" style="width:90px;">{{ __('Type') }}</th>
                                <th class="fw-semibold border-0 pb-2" style="width:110px;">{{ __('Status') }}</th>
                                <th class="fw-semibold border-0 pb-2" style="width:140px;">{{ __('Generated') }}</th>
                                <th class="fw-semibold border-0 pb-2" style="width:140px;">{{ __('Completed') }}</th>
                                <th class="fw-semibold border-0 pb-2 text-end pe-3" style="width:120px;">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($signatureRequests as $sig)
                            @php
                                $sigStatus = $sig->status === 'completed' ? 'completed' : ($sig->isExpired() ? 'expired' : 'pending');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('tenant.signatures.show', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                       class="fw-medium text-decoration-none text-body">
                                        {{ $sig->signature_label }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $typeBadgeMap[$sig->signature_type] ?? 'wcrb-pill--muted' }}" style="font-size:.72rem;">
                                        {{ ucfirst($sig->signature_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $statusBadgeMap[$sigStatus] ?? 'wcrb-pill--pending' }}" style="font-size:.72rem;">
                                        @if($sigStatus === 'completed')
                                            <i class="bi bi-check-circle me-1"></i>{{ __('Completed') }}
                                        @elseif($sigStatus === 'expired')
                                            <i class="bi bi-clock me-1"></i>{{ __('Expired') }}
                                        @else
                                            <i class="bi bi-hourglass-split me-1"></i>{{ __('Pending') }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small">{{ $sig->generated_at?->format('M d, Y H:i') ?? '---' }}</div>
                                    @if($sig->generator)
                                        <div class="text-muted small">{{ __('by') }} {{ $sig->generator->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($sig->completed_at)
                                        <div class="text-muted small">{{ $sig->completed_at->format('M d, Y H:i') }}</div>
                                        @if($sig->completed_ip)
                                            <div class="text-muted small">IP: {{ $sig->completed_ip }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">
                                        {{-- View Details: always shown --}}
                                        <a href="{{ route('tenant.signatures.show', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" style="padding:.25rem .65rem;font-size:.78rem;"
                                           title="{{ __('View Details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if($sig->isPending() && !$sig->isExpired())
                                            <a href="{{ route('tenant.signatures.generator', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                               class="btn btn-sm btn-primary" style="padding:.25rem .65rem;font-size:.78rem;" title="{{ __('View / Share Link') }}">
                                                <i class="bi bi-link-45deg me-1"></i>{{ __('Link') }}
                                            </a>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" style="padding:.25rem .45rem;">
                                                    <i class="bi bi-three-dots" style="font-size:.75rem;"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:165px;">
                                                    <li>
                                                        <form method="POST"
                                                              action="{{ route('tenant.signatures.send', ['business' => $tenant->slug, 'jobId' => $job->id, 'signatureId' => $sig->id]) }}"
                                                              onsubmit="return confirm('{{ __('Send signature request email to customer?') }}')">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item py-2">
                                                                <i class="bi bi-envelope me-2 text-muted"></i>{{ __('Send via Email') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @elseif($sig->isCompleted() && $sig->signature_file_path)
                                            <a href="{{ $sig->signature_file_path }}" target="_blank" download
                                               class="btn btn-sm btn-outline-primary" style="padding:.25rem .65rem;font-size:.78rem;" title="{{ __('Download Signature') }}">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif
                                    </div>
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

@push('page-styles')
<style>
    .wcrb-pill--active  { color: #065f46; background: rgba(16,185,129,.10);  border: 1px solid rgba(16,185,129,.25); }
    .wcrb-pill--pending { color: #92400e; background: rgba(245,158,11,.10);  border: 1px solid rgba(245,158,11,.25); }
    .wcrb-pill--danger  { color: #991b1b; background: rgba(239,68,68,.10);   border: 1px solid rgba(239,68,68,.25); }
    .wcrb-pill--info    { color: #155e75; background: rgba(6,182,212,.10);   border: 1px solid rgba(6,182,212,.25); }
    .wcrb-pill--warning { color: #78350f; background: rgba(245,158,11,.10);  border: 1px solid rgba(245,158,11,.25); }
    .wcrb-pill--muted   { color: #4b5563; background: rgba(107,114,128,.10); border: 1px solid rgba(107,114,128,.25); }

    [data-bs-theme="dark"] .wcrb-pill--active  { background: rgba(16,185,129,.20); }
    [data-bs-theme="dark"] .wcrb-pill--pending { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger  { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--info    { background: rgba(6,182,212,.20); }
    [data-bs-theme="dark"] .wcrb-pill--warning { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--muted   { background: rgba(107,114,128,.20); }
</style>
@endpush

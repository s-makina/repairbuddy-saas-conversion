@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', ($purpose === 'approve' ? 'Estimate Approved' : 'Estimate Rejected') . ' - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-lg overflow-hidden">
            <div class="card-body p-5 text-center">
                @if($purpose === 'approve')
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-lg" style="font-size: 40px;"></i>
                        </div>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-3">Estimate Approved!</h1>
                    <p class="text-muted mb-4">
                        Thank you for approving your estimate. We have received your confirmation and will begin processing your request shortly.
                    </p>
                @elseif($purpose === 'reject')
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-x-lg" style="font-size: 35px;"></i>
                        </div>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-3">Estimate Rejected</h1>
                    <p class="text-muted mb-4">
                        You have rejected the estimate for <strong>#{{ $estimate->case_number }}</strong>. We have updated our records accordingly.
                    </p>
                @else
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 35px;"></i>
                        </div>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-3">Action Failed</h1>
                    <p class="text-muted mb-4">
                        {{ $message ?? 'We encountered an issue while processing your request. This could be due to an expired or invalid link.' }}
                    </p>
                @endif

                <div class="bg-light rounded p-4 mb-4 text-start">
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Estimate Number</span>
                            <span class="d-block fw-bold text-dark">#{{ $estimate->case_number }}</span>
                        </div>
                        <div class="col-6">
                            <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Status</span>
                            <span class="badge rounded-pill {{ $purpose === 'approve' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($estimate->status) }}
                            </span>
                        </div>
                        @if($estimate->title)
                        <div class="col-12 border-top pt-3">
                            <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Description</span>
                            <span class="d-block text-dark">{{ $estimate->title }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="{{ route('tenant.status.show', ['business' => $business ?? '']) }}" class="btn btn-primary px-4 py-2 fw-semibold">
                        View Detailed Status
                    </a>
                    <a href="{{ route('tenant.booking.show', ['business' => $business ?? '']) }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <p class="text-center text-muted small mt-4">
            If you have any questions, please contact us at 
            @if($tenant && $tenant->email)
                <a href="mailto:{{ $tenant->email }}" class="text-decoration-none fw-semibold">{{ $tenant->email }}</a>
            @else
                our support line.
            @endif
        </p>
    </div>
</div>
@endsection

@push('page-styles')
<style>
    .card {
        border-radius: 1rem !important;
    }
    .bg-success.bg-opacity-10 {
        background-color: rgba(16, 185, 129, 0.1) !important;
    }
    .bg-secondary.bg-opacity-10 {
        background-color: rgba(107, 114, 128, 0.1) !important;
    }
    .text-success {
        color: #10b981 !important;
    }
    .btn-primary {
        background-color: #063e70;
        border-color: #063e70;
    }
    .btn-primary:hover {
        background-color: #05335d;
        border-color: #05335d;
    }
</style>
@endpush

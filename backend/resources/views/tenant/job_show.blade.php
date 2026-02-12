@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Job'])

@section('content')
@php
    /** @var \App\Models\RepairBuddyJob $job */
    $job = $job ?? null;

    $totals = is_array($totals ?? null) ? $totals : [];
    $currency = is_string($totals['currency'] ?? null) ? (string) $totals['currency'] : 'USD';

    $formatMoney = function ($cents) {
        if ($cents === null) {
            return '—';
        }
        $num = is_numeric($cents) ? ((int) $cents) / 100 : 0;
        return number_format($num, 2, '.', ',');
    };

    $jobItems = is_iterable($jobItems ?? null) ? $jobItems : [];
    $jobDevices = is_iterable($jobDevices ?? null) ? $jobDevices : [];
    $jobAttachments = is_iterable($jobAttachments ?? null) ? $jobAttachments : [];
    $jobEvents = is_iterable($jobEvents ?? null) ? $jobEvents : [];

    $customer = $job?->customer;
    $technicians = $job?->technicians;
@endphp

<main class="dashboard-content container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="mb-0">{{ __('Job') }} {{ $job?->case_number ?? '' }}</h3>
                @if (! empty($job?->status_slug))
                    <span class="badge bg-primary">{{ $job->status_slug }}</span>
                @endif
                @if (! empty($job?->payment_status_slug))
                    <span class="badge bg-secondary">{{ $job->payment_status_slug }}</span>
                @endif
                @if (! empty($job?->priority))
                    <span class="badge bg-warning text-dark">{{ $job->priority }}</span>
                @endif
            </div>
            <div class="text-muted">
                {{ __('ID') }}: {{ $job?->id ?? '' }}
                @if (! empty($job?->title))
                    <span class="mx-2">•</span>
                    {{ $job->title }}
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.dashboard', ['business' => $tenant?->slug]) . '?screen=jobs' }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                {{ __('Back to Jobs') }}
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                <i class="bi bi-printer"></i>
                {{ __('Print') }}
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                <i class="bi bi-file-earmark-pdf"></i>
                {{ __('Download PDF') }}
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                <i class="bi bi-envelope"></i>
                {{ __('Email Customer') }}
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Order Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Items Subtotal') }}</span>
                        <span><strong>{{ $formatMoney($totals['items_subtotal_cents'] ?? null) }}</strong> {{ $currency }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Tax') }}</span>
                        <span>{{ $formatMoney($totals['tax_total_cents'] ?? null) }} {{ $currency }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Grand Total') }}</span>
                        <span><strong>{{ $formatMoney($totals['grand_total_cents'] ?? null) }}</strong> {{ $currency }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Received') }}</span>
                        <span>{{ $formatMoney($totals['paid_total_cents'] ?? null) }} {{ $currency }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('Balance') }}</span>
                        <span><strong>{{ $formatMoney($totals['balance_cents'] ?? null) }}</strong> {{ $currency }}</span>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Attachments') }}</h5>
                </div>
                <div class="card-body">
                    @if (count($jobAttachments) === 0)
                        <div class="text-muted">{{ __('No attachments yet.') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($jobAttachments as $att)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <div style="min-width:0;">
                                            <div class="fw-semibold text-truncate">{{ $att->original_filename }}</div>
                                            <div class="small text-muted">
                                                {{ $att->visibility }}
                                                <span class="mx-1">•</span>
                                                {{ $att->created_at }}
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            @if (! empty($att->url))
                                                <a class="btn btn-outline-secondary btn-sm" href="{{ $att->url }}" target="_blank">{{ __('Open') }}</a>
                                            @else
                                                <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Open') }}</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>
                            <i class="bi bi-upload"></i>
                            {{ __('Upload') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Payments') }}</h5>
                </div>
                <div class="card-body">
                    <div class="text-muted">{{ __('Payments UI will be wired next.') }}</div>
                    <button class="btn btn-outline-primary btn-sm mt-3" type="button" disabled>
                        <i class="bi bi-credit-card"></i>
                        {{ __('Take Payment') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">
                        {{ __('Job Details') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-devices" type="button" role="tab">
                        {{ __('Devices') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                        {{ __('Items & Services') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expenses" type="button" role="tab">
                        {{ __('Expenses') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab">
                        {{ __('History') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">
                <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">{{ __('Customer') }}</h6>
                                    @if ($customer)
                                        <div class="fw-semibold">{{ $customer->name ?? ($customer->first_name ?? '') }}</div>
                                        <div class="small text-muted">{{ $customer->email ?? '' }}</div>
                                    @else
                                        <div class="text-muted">{{ __('No customer selected.') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">{{ __('Dates') }}</h6>
                                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">{{ __('Pickup') }}</span><span>{{ $job?->pickup_date ?? '—' }}</span></div>
                                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">{{ __('Delivery') }}</span><span>{{ $job?->delivery_date ?? '—' }}</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">{{ __('Next Service') }}</span><span>{{ $job?->next_service_date ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">{{ __('Technicians') }}</h6>
                                    @if ($technicians && $technicians->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($technicians as $tech)
                                                <span class="badge bg-light text-dark border">{{ $tech->name ?? $tech->email ?? ('User #' . $tech->id) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted">{{ __('No technicians assigned.') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">{{ __('Job Details') }}</h6>
                                    <div style="white-space: pre-wrap;">{{ $job?->case_detail ?? '' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-devices" role="tabpanel">
                    @if (count($jobDevices) === 0)
                        <div class="text-muted">{{ __('No devices attached.') }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Device') }}</th>
                                        <th>{{ __('Serial') }}</th>
                                        <th>{{ __('Notes') }}</th>
                                        <th>{{ __('Created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobDevices as $d)
                                        <tr>
                                            <td>{{ $d->label_snapshot }}</td>
                                            <td>{{ $d->serial_snapshot }}</td>
                                            <td>{{ $d->notes_snapshot }}</td>
                                            <td>{{ $d->created_at }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <button class="btn btn-outline-primary btn-sm" type="button" disabled>
                        <i class="bi bi-plus-circle"></i>
                        {{ __('Attach Device') }}
                    </button>
                </div>

                <div class="tab-pane fade" id="tab-items" role="tabpanel">
                    @if (count($jobItems) === 0)
                        <div class="text-muted">{{ __('No items yet.') }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th class="text-end">{{ __('Qty') }}</th>
                                        <th class="text-end">{{ __('Unit') }}</th>
                                        <th class="text-end">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobItems as $it)
                                        @php
                                            $qty = is_numeric($it->qty) ? (int) $it->qty : 1;
                                            $unit = is_numeric($it->unit_price_amount_cents) ? (int) $it->unit_price_amount_cents : 0;
                                            $line = $qty * $unit;
                                        @endphp
                                        <tr>
                                            <td><span class="badge bg-light text-dark border">{{ $it->item_type }}</span></td>
                                            <td>{{ $it->name_snapshot }}</td>
                                            <td class="text-end">{{ $qty }}</td>
                                            <td class="text-end">{{ $formatMoney($unit) }}</td>
                                            <td class="text-end"><strong>{{ $formatMoney($line) }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>{{ __('Add Service') }}</button>
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>{{ __('Add Part') }}</button>
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>{{ __('Add Fee') }}</button>
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>{{ __('Add Discount') }}</button>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-expenses" role="tabpanel">
                    <div class="text-muted">{{ __('Job expenses UI will be wired next.') }}</div>
                </div>

                <div class="tab-pane fade" id="tab-history" role="tabpanel">
                    @if (count($jobEvents) === 0)
                        <div class="text-muted">{{ __('No history yet.') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($jobEvents as $e)
                                @php
                                    $payload = is_array($e->payload_json ?? null) ? $e->payload_json : [];
                                    $title = is_string($payload['title'] ?? null) ? $payload['title'] : (string) ($e->event_type ?? 'event');
                                    $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;
                                @endphp
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold">{{ $title }}</div>
                                            @if ($message)
                                                <div class="small" style="white-space: pre-wrap;">{{ $message }}</div>
                                            @endif
                                            <div class="small text-muted">
                                                {{ $e->created_at }}
                                                <span class="mx-1">•</span>
                                                {{ $e->visibility }}
                                                @if ($e->actor)
                                                    <span class="mx-1">•</span>
                                                    {{ $e->actor->name ?? $e->actor->email ?? ('User #' . $e->actor->id) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm" type="button" disabled>
                            <i class="bi bi-plus-circle"></i>
                            {{ __('Add Note') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

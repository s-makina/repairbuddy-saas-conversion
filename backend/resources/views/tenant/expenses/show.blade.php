@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Expense Details'])

@section('content')
@php
    $currency = is_string($tenant->currency ?? null) && $tenant->currency !== ''
        ? strtoupper($tenant->currency)
        : 'USD';
    try {
        $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
        $currencySymbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($currency . ' ');
    } catch (\Exception $e) {
        $currencySymbol = $currency . ' ';
    }
    $formatMoney = function ($amount) use ($currencySymbol) {
        if ($amount === null) return '—';
        return $currencySymbol . number_format((float) $amount, 2, '.', ',');
    };

    $statusClasses = [
        'active' => 'success',
        'void' => 'secondary',
        'refunded' => 'warning',
    ];
    $paymentStatusClasses = [
        'paid' => 'success',
        'pending' => 'warning',
        'partial' => 'info',
        'overdue' => 'danger',
    ];
@endphp

<div class="wcrb-page">
    {{-- Page Header --}}
    <div class="wcrb-page-header mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}">{{ __('Expenses') }}</a></li>
                        <li class="breadcrumb-item active">{{ e($expense->expense_number) }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ e($expense->expense_number) }}</h1>
            </div>
            <div class="d-flex gap-2">
                @if ($expense->status === 'active')
                    <a href="{{ route('tenant.expenses.edit', ['business' => $tenant->slug, 'expense' => $expense->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                    </a>
                @endif
                <a href="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Status Banner --}}
            @if ($expense->status !== 'active')
                <div class="alert alert-{{ $statusClasses[$expense->status] ?? 'secondary' }} mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ __('This expense has been :status.', ['status' => strtolower(\App\Models\Expense::STATUSES[$expense->status] ?? $expense->status)]) }}
                </div>
            @endif

            {{-- Amount Card --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">{{ __('Amount') }}</div>
                            <div class="fs-4 fw-semibold">{{ e($formatMoney($expense->amount)) }}</div>
                        </div>
                        <div class="col-4 border-start">
                            <div class="text-muted small">{{ __('Tax') }}</div>
                            <div class="fs-4 fw-semibold">{{ e($formatMoney($expense->tax_amount)) }}</div>
                        </div>
                        <div class="col-4 border-start">
                            <div class="text-muted small">{{ __('Total') }}</div>
                            <div class="fs-4 fw-bold text-danger">{{ e($formatMoney($expense->total_amount)) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Details Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>{{ __('Details') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">{{ __('Date') }}</dt>
                        <dd class="col-sm-8">{{ e($expense->expense_date?->format('F d, Y') ?? '—') }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('Category') }}</dt>
                        <dd class="col-sm-8">
                            @if ($expense->category)
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 16px; height: 16px; border-radius: 4px; background-color: {{ e($expense->category->color_code ?? '#3498db') }};"></div>
                                    <span>{{ e($expense->category->category_name) }}</span>
                                </div>
                            @else
                                <span class="text-muted">{{ __('Uncategorized') }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">{{ __('Type') }}</dt>
                        <dd class="col-sm-8">{{ e(\App\Models\Expense::TYPES[$expense->expense_type] ?? $expense->expense_type) }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('Payment Method') }}</dt>
                        <dd class="col-sm-8">{{ e(\App\Models\Expense::PAYMENT_METHODS[$expense->payment_method] ?? $expense->payment_method) }}</dd>

                        <dt class="col-sm-4 text-muted">{{ __('Payment Status') }}</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $paymentStatusClasses[$expense->payment_status] ?? 'secondary' }}">
                                {{ e(\App\Models\Expense::PAYMENT_STATUSES[$expense->payment_status] ?? $expense->payment_status) }}
                            </span>
                        </dd>

                        @if ($expense->receipt_number)
                            <dt class="col-sm-4 text-muted">{{ __('Receipt #') }}</dt>
                            <dd class="col-sm-8">{{ e($expense->receipt_number) }}</dd>
                        @endif

                        @if ($expense->description)
                            <dt class="col-sm-4 text-muted">{{ __('Description') }}</dt>
                            <dd class="col-sm-8">
                                <p class="mb-0" style="white-space: pre-wrap;">{{ e($expense->description) }}</p>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-flag me-2"></i>{{ __('Status') }}</h5>
                </div>
                <div class="card-body">
                    <span class="badge bg-{{ $statusClasses[$expense->status] ?? 'secondary' }} fs-6">
                        {{ e(\App\Models\Expense::STATUSES[$expense->status] ?? $expense->status) }}
                    </span>
                </div>
            </div>

            {{-- Relations Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-link-45deg me-2"></i>{{ __('Related To') }}</h5>
                </div>
                <div class="card-body">
                    @if ($expense->job)
                        <div class="mb-3">
                            <div class="text-muted small">{{ __('Job') }}</div>
                            <a href="{{ route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $expense->job_id]) }}" class="text-decoration-none">
                                {{ e($expense->job->case_number ?? '#' . $expense->job_id) }}
                            </a>
                        </div>
                    @endif

                    @if ($expense->technician)
                        <div class="mb-3">
                            <div class="text-muted small">{{ __('Technician') }}</div>
                            <span>{{ e($expense->technician->name) }}</span>
                        </div>
                    @endif

                    @if (! $expense->job && ! $expense->technician)
                        <span class="text-muted">{{ __('No related records') }}</span>
                    @endif
                </div>
            </div>

            {{-- Audit Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('History') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted small">{{ __('Created') }}</dt>
                        <dd>
                            {{ e($expense->created_at?->format('M d, Y H:i') ?? '—') }}
                            @if ($expense->creator)
                                <br><span class="text-muted small">by {{ e($expense->creator->name) }}</span>
                            @endif
                        </dd>

                        @if ($expense->updated_at && $expense->updated_at != $expense->created_at)
                            <dt class="text-muted small mt-2">{{ __('Updated') }}</dt>
                            <dd>
                                {{ e($expense->updated_at?->format('M d, Y H:i') ?? '—') }}
                                @if ($expense->updater)
                                    <br><span class="text-muted small">by {{ e($expense->updater->name) }}</span>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Void Action --}}
            @if ($expense->status === 'active')
                <div class="card mt-4 border-danger">
                    <div class="card-body">
                        <form method="post" action="{{ route('tenant.expenses.delete', ['business' => $tenant->slug, 'expense' => $expense->id]) }}">
                            @csrf
                            <p class="text-muted small mb-2">{{ __('Mark this expense as void if it was entered in error.') }}</p>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('{{ e(__('Are you sure you want to void this expense?')) }}')">
                                <i class="bi bi-x-circle me-1"></i> {{ __('Void Expense') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

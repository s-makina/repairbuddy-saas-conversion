@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Add Expense'])

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
@endphp

<div class="wcrb-page">
    {{-- Page Header --}}
    <div class="wcrb-page-header mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}">{{ __('Expenses') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Add New') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('Add Expense') }}</h1>
            </div>
            <a href="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    {{-- Form --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('tenant.expenses.store', ['business' => $tenant->slug]) }}" id="expenseForm">
                        @csrf

                        {{-- Error Summary --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ e($error) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            {{-- Date --}}
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" id="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Category --}}
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select a category') }}</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" data-taxable="{{ $cat->taxable ? '1' : '0' }}" data-tax-rate="{{ $cat->tax_rate }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ e($cat->category_name) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Amount --}}
                            <div class="col-md-6">
                                <label for="amount" class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ e($currencySymbol) }}</span>
                                    <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0.00" required>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Tax Display --}}
                            <div class="col-md-6">
                                <label class="form-label text-muted">{{ __('Tax & Total') }}</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <div>
                                        <span class="small text-muted">{{ __('Tax:') }}</span>
                                        <span id="taxDisplay" class="fw-semibold">{{ e($currencySymbol) }}0.00</span>
                                    </div>
                                    <div>
                                        <span class="small text-muted">{{ __('Total:') }}</span>
                                        <span id="totalDisplay" class="fw-bold text-danger">{{ e($currencySymbol) }}0.00</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div class="col-12">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('Enter expense details...') }}">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Expense Type --}}
                            <div class="col-md-6">
                                <label for="expense_type" class="form-label">{{ __('Type') }}</label>
                                <select name="expense_type" id="expense_type" class="form-select @error('expense_type') is-invalid @enderror">
                                    @foreach (\App\Models\Expense::TYPES as $value => $label)
                                        <option value="{{ $value }}" {{ old('expense_type', 'general') == $value ? 'selected' : '' }}>
                                            {{ e(__($label)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('expense_type')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Payment Method --}}
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    @foreach (\App\Models\Expense::PAYMENT_METHODS as $value => $label)
                                        <option value="{{ $value }}" {{ old('payment_method', 'cash') == $value ? 'selected' : '' }}>
                                            {{ e(__($label)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Payment Status --}}
                            <div class="col-md-6">
                                <label for="payment_status" class="form-label">{{ __('Payment Status') }}</label>
                                <select name="payment_status" id="payment_status" class="form-select @error('payment_status') is-invalid @enderror">
                                    @foreach (\App\Models\Expense::PAYMENT_STATUSES as $value => $label)
                                        <option value="{{ $value }}" {{ old('payment_status', 'paid') == $value ? 'selected' : '' }}>
                                            {{ e(__($label)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_status')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Receipt Number --}}
                            <div class="col-md-6">
                                <label for="receipt_number" class="form-label">{{ __('Receipt Number') }}</label>
                                <input type="text" name="receipt_number" id="receipt_number" class="form-control @error('receipt_number') is-invalid @enderror" value="{{ old('receipt_number') }}" placeholder="{{ __('Optional') }}">
                                @error('receipt_number')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Job --}}
                            <div class="col-md-6">
                                <label for="job_id" class="form-label">{{ __('Related Job') }}</label>
                                <select name="job_id" id="job_id" class="form-select @error('job_id') is-invalid @enderror">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}" {{ old('job_id') == $job->id ? 'selected' : '' }}>
                                            {{ e($job->case_number ?? '#' . $job->id) }} @if($job->title) - {{ e($job->title) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('job_id')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>

                            {{-- Technician --}}
                            <div class="col-md-6">
                                <label for="technician_id" class="form-label">{{ __('Technician') }}</label>
                                <select name="technician_id" id="technician_id" class="form-select @error('technician_id') is-invalid @enderror">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }}>
                                            {{ e($tech->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('technician_id')
                                    <div class="invalid-feedback">{{ e($message) }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}" class="btn btn-outline-secondary">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> {{ __('Save Expense') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>{{ __('Tips') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            {{ __('Tax is automatically calculated based on the selected category.') }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            {{ __('Link expenses to jobs for better cost tracking.') }}
                        </li>
                        <li>
                            <i class="bi bi-check-circle text-success me-2"></i>
                            {{ __('A unique expense number will be generated automatically.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
<script>
$(document).ready(function() {
    var currencySymbol = '{{ e($currencySymbol) }}';

    function calculateTotals() {
        var amount = parseFloat($('#amount').val()) || 0;
        var $selectedCategory = $('#category_id option:selected');
        var taxable = $selectedCategory.data('taxable') == 1;
        var taxRate = parseFloat($selectedCategory.data('tax-rate')) || 0;

        var taxAmount = 0;
        if (taxable && taxRate > 0) {
            taxAmount = amount * (taxRate / 100);
        }

        var total = amount + taxAmount;

        $('#taxDisplay').text(currencySymbol + taxAmount.toFixed(2));
        $('#totalDisplay').text(currencySymbol + total.toFixed(2));
    }

    $('#amount').on('input', calculateTotals);
    $('#category_id').on('change', calculateTotals);

    // Initial calculation
    calculateTotals();
});
</script>
@endpush
@endsection

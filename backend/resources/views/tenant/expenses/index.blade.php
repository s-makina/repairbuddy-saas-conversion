@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Expenses'])

@push('page-styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" />
@endpush

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
@endphp

<div class="wcrb-page mx-3">
    {{-- Page Header --}}
    <div class="wcrb-page-header mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Expenses') }}</h1>
                <p class="text-muted mb-0">{{ __('Track and manage your business expenses') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('tenant.expense_categories.index', ['business' => $tenant->slug]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-tags me-1"></i> {{ __('Categories') }}
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add Expense') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card stats-card bg-primary text-white h-100">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bi bi-cash-stack fs-1 opacity-75"></i>
                    </div>
                    <h6 class="card-title mb-1 text-white-50">{{ __('Total Expenses') }}</h6>
                    <h3 class="mb-0">{{ e($formatMoney($statistics['grand_total'] ?? 0)) }}</h3>
                    <small class="d-block mt-1 opacity-75">{{ sprintf(__('%d Expenses'), $statistics['total_count'] ?? 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stats-card bg-success text-white h-100">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bi bi-check-circle fs-1 opacity-75"></i>
                    </div>
                    <h6 class="card-title mb-1 text-white-50">{{ __('Paid') }}</h6>
                    <h3 class="mb-0">
                        @php
                            $paidTotal = collect($statistics['by_payment_status'] ?? [])
                                ->firstWhere('payment_status', 'paid')->total ?? 0;
                        @endphp
                        {{ e($formatMoney($paidTotal)) }}
                    </h3>
                    <small class="d-block mt-1 opacity-75">{{ __('Amount') }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stats-card bg-info text-white h-100">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bi bi-receipt fs-1 opacity-75"></i>
                    </div>
                    <h6 class="card-title mb-1 text-white-50">{{ __('Tax') }}</h6>
                    <h3 class="mb-0">{{ e($formatMoney($statistics['total_tax'] ?? 0)) }}</h3>
                    <small class="d-block mt-1 opacity-75">{{ __('Total Tax') }}</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stats-card bg-warning text-white h-100">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bi bi-hourglass-split fs-1 opacity-75"></i>
                    </div>
                    <h6 class="card-title mb-1 text-white-50">{{ __('Pending') }}</h6>
                    @php
                        $pendingTotal = collect($statistics['by_payment_status'] ?? [])
                            ->firstWhere('payment_status', 'pending')->total ?? 0;
                    @endphp
                    <h3 class="mb-0">{{ e($formatMoney($pendingTotal)) }}</h3>
                    <small class="d-block mt-1 opacity-75">{{ __('Unpaid Expenses') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="{{ route('tenant.expenses.index', ['business' => $tenant->slug]) }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="category_id" class="form-label small text-muted">{{ __('Category') }}</label>
                        <select name="category_id" id="category_id" class="form-select form-select-sm">
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $filters['category_id'] == $cat->id ? 'selected' : '' }}>
                                    {{ e($cat->category_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="payment_status" class="form-label small text-muted">{{ __('Payment Status') }}</label>
                        <select name="payment_status" id="payment_status" class="form-select form-select-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\Expense::PAYMENT_STATUSES as $value => $label)
                                <option value="{{ $value }}" {{ $filters['payment_status'] == $value ? 'selected' : '' }}>
                                    {{ e(__($label)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="start_date" class="form-label small text-muted">{{ __('From') }}</label>
                        <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ e($filters['start_date'] ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label small text-muted">{{ __('To') }}</label>
                        <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ e($filters['end_date'] ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label small text-muted">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            @foreach (\App\Models\Expense::STATUSES as $value => $label)
                                <option value="{{ $value }}" {{ $filters['status'] == $value ? 'selected' : '' }}>
                                    {{ e(__($label)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Expenses Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="expensesTable" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Job') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Expense Modal --}}
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add New Expense') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addExpenseForm" method="post" action="{{ route('tenant.expenses.store', ['business' => $tenant->slug]) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_expense_date" class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_expense_date" name="expense_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_category_id" class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_category_id" name="category_id" required>
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ e($cat->category_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modal_description" class="form-label">{{ __('Description') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="modal_description" name="description" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_amount" class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ e($currencySymbol) }}</span>
                                <input type="number" class="form-control" id="modal_amount" name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_payment_method" class="form-label">{{ __('Payment Method') }}</label>
                            <select class="form-select" id="modal_payment_method" name="payment_method">
                                <option value="">{{ __('Select Method') }}</option>
                                @foreach (\App\Models\Expense::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}">{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_payment_status" class="form-label">{{ __('Payment Status') }}</label>
                            <select class="form-select" id="modal_payment_status" name="payment_status">
                                @foreach (\App\Models\Expense::PAYMENT_STATUSES as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'paid' ? 'selected' : '' }}>{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_receipt_number" class="form-label">{{ __('Receipt Number') }}</label>
                            <input type="text" class="form-control" id="modal_receipt_number" name="receipt_number">
                        </div>
                        <div class="col-md-6">
                            <label for="modal_expense_type" class="form-label">{{ __('Expense Type') }}</label>
                            <select class="form-select" id="modal_expense_type" name="expense_type">
                                @foreach (\App\Models\Expense::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="submitExpenseForm">{{ __('Add Expense') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Expense Modal --}}
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Expense') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" method="post" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" id="edit_expense_id" name="expense_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_expense_date" class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_expense_date" name="expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_category_id" class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ e($cat->category_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label">{{ __('Description') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_amount" class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ e($currencySymbol) }}</span>
                                <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_method" class="form-label">{{ __('Payment Method') }}</label>
                            <select class="form-select" id="edit_payment_method" name="payment_method">
                                <option value="">{{ __('Select Method') }}</option>
                                @foreach (\App\Models\Expense::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}">{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_status" class="form-label">{{ __('Payment Status') }}</label>
                            <select class="form-select" id="edit_payment_status" name="payment_status">
                                @foreach (\App\Models\Expense::PAYMENT_STATUSES as $value => $label)
                                    <option value="{{ $value }}">{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_receipt_number" class="form-label">{{ __('Receipt Number') }}</label>
                            <input type="text" class="form-control" id="edit_receipt_number" name="receipt_number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_expense_type" class="form-label">{{ __('Expense Type') }}</label>
                            <select class="form-select" id="edit_expense_type" name="expense_type">
                                @foreach (\App\Models\Expense::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ e(__($label)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="updateExpenseForm">{{ __('Update Expense') }}</button>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var filterParams = {
        category_id: '{{ e($filters['category_id'] ?? '') }}',
        payment_status: '{{ e($filters['payment_status'] ?? '') }}',
        start_date: '{{ e($filters['start_date'] ?? '') }}',
        end_date: '{{ e($filters['end_date'] ?? '') }}',
        status: '{{ e($filters['status'] ?? 'active') }}',
    };

    var expensesTable = $('#expensesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('tenant.expenses.datatable', ['business' => $tenant->slug]) }}',
            type: 'GET',
            data: function (d) {
                d.category_id = filterParams.category_id;
                d.payment_status = filterParams.payment_status;
                d.start_date = filterParams.start_date;
                d.end_date = filterParams.end_date;
                d.status = filterParams.status;
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'expense_number_display', name: 'expense_number', orderable: true },
            { data: 'date_display', name: 'expense_date', orderable: true },
            { data: 'category_display', name: 'category_id', orderable: false },
            { data: 'amount_display', name: 'amount', orderable: true, className: 'text-end' },
            { data: 'tax_display', name: 'tax_amount', orderable: false, className: 'text-end' },
            { data: 'total_display', name: 'total_amount', orderable: true, className: 'text-end' },
            { data: 'payment_status_display', name: 'payment_status', orderable: false },
            { data: 'job_display', name: 'job_id', orderable: false },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'actions_display', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ],
        columnDefs: [
            { targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], className: 'align-middle' }
        ]
    });

    // Update filter params when form changes
    $('#filterForm input, #filterForm select').on('change', function() {
        filterParams = {
            category_id: $('#category_id').val(),
            payment_status: $('#payment_status').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            status: $('#status').val(),
        };
    });

    // Add Expense Modal Submission
    $('#submitExpenseForm').on('click', function() {
        var $form = $('#addExpenseForm');
        var $btn = $(this);
        
        // Clear previous errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Saving...') }}');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#addExpenseModal').modal('hide');
                    // Reset form
                    $form[0].reset();
                    // Refresh table
                    expensesTable.ajax.reload(null, false);
                    // Show success message
                    alert(response.message || '{{ __('Expense added successfully.') }}');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var $input = $form.find('[name="' + field + '"]');
                        $input.addClass('is-invalid');
                        $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else {
                    alert(xhr.responseJSON?.message || '{{ __('An error occurred. Please try again.') }}');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('{{ __('Add Expense') }}');
            }
        });
    });

    // Edit Expense Modal
    $(document).on('click', '.edit-expense-btn', function() {
        var expenseId = $(this).data('expense-id');
        var $form = $('#editExpenseForm');
        
        // Clear previous errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        // Fetch expense data
        $.ajax({
            url: '{{ route('tenant.expenses.json', ['business' => $tenant->slug, 'expense' => 0]) }}'.replace('/0', '/' + expenseId),
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    var expense = response.expense;
                    // Set form action
                    $form.attr('action', '{{ route('tenant.expenses.update', ['business' => $tenant->slug, 'expense' => 0]) }}'.replace('/0', '/' + expenseId));
                    // Populate form fields
                    $('#edit_expense_id').val(expense.id);
                    $('#edit_expense_date').val(expense.expense_date);
                    $('#edit_category_id').val(expense.category_id);
                    $('#edit_description').val(expense.description);
                    $('#edit_amount').val(expense.amount);
                    $('#edit_payment_method').val(expense.payment_method);
                    $('#edit_payment_status').val(expense.payment_status);
                    $('#edit_receipt_number').val(expense.receipt_number);
                    $('#edit_expense_type').val(expense.expense_type);
                    // Show modal
                    $('#editExpenseModal').modal('show');
                }
            },
            error: function() {
                alert('{{ __('Failed to load expense data.') }}');
            }
        });
    });

    // Update Expense Form Submission
    $('#updateExpenseForm').on('click', function() {
        var $form = $('#editExpenseForm');
        var $btn = $(this);
        
        // Clear previous errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Updating...') }}');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#editExpenseModal').modal('hide');
                    // Refresh table
                    expensesTable.ajax.reload(null, false);
                    // Show success message
                    alert(response.message || '{{ __('Expense updated successfully.') }}');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var $input = $form.find('[name="' + field + '"]');
                        $input.addClass('is-invalid');
                        $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else {
                    alert(xhr.responseJSON?.message || '{{ __('An error occurred. Please try again.') }}');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('{{ __('Update Expense') }}');
            }
        });
    });
});
</script>
@endpush
@endsection

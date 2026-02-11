@extends('tenant.layouts.myaccount', ['title' => 'Expenses'])

@section('content')
@php
    $userRole = is_string($userRole ?? null) ? (string) $userRole : (is_object($user ?? null) ? (string) ($user->role ?? 'guest') : 'guest');
    $licenseState = (bool) ($licenseState ?? true);

    $search = is_string($search ?? null) ? $search : '';
    $category_id = $category_id ?? '';
    $payment_status = is_string($payment_status ?? null) ? $payment_status : '';
    $start_date = is_string($start_date ?? null) ? $start_date : '';
    $end_date = is_string($end_date ?? null) ? $end_date : '';

    $page = (int) ($page ?? 1);
    $page = $page > 0 ? $page : 1;

    $limit = (int) ($limit ?? 20);
    $offset = (int) ($offset ?? (($page - 1) * $limit));

    $expenses = is_array($expenses ?? null) ? $expenses : (is_iterable($expenses ?? null) ? $expenses : []);
    $total_expenses = (int) ($total_expenses ?? 0);
    $total_pages = (int) ($total_pages ?? (int) ceil(($total_expenses ?: 0) / ($limit ?: 1)));

    $stats = is_array($stats ?? null) ? $stats : [];
    $categories = is_iterable($categories ?? null) ? $categories : [];
    $payment_methods = is_iterable($payment_methods ?? null) ? $payment_methods : [];
    $payment_statuses = is_iterable($payment_statuses ?? null) ? $payment_statuses : [];

    $canAccess = in_array($userRole, ['store_manager', 'administrator'], true);

    $reset_url = $reset_url ?? url()->current();
    $page_url_prev = $page_url_prev ?? url()->current();
    $page_url_next = $page_url_next ?? url()->current();

    $page_urls = is_array($page_urls ?? null) ? $page_urls : [];

    $nonce = is_string($nonce ?? null) ? $nonce : csrf_token();

    $currency_format = $currency_format ?? null;
    $currency_symbol = is_string($currency_symbol ?? null) ? $currency_symbol : '$';

    $format_money = function ($value) use ($currency_format, $currency_symbol) {
        if (is_callable($currency_format)) {
            return $currency_format($value);
        }

        $num = is_numeric($value) ? (float) $value : 0;
        return $currency_symbol . number_format($num, 2, '.', ',');
    };

    $date_format = is_string($date_format ?? null) ? $date_format : 'Y-m-d';
@endphp

@if ($canAccess)
    {{ __("You do not have sufficient permissions to access this page.") }}
@else
    <!-- Expenses Content -->
    <main class="dashboard-content container-fluid py-4">
        <!-- Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-cash-stack fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50">{{ __('Total Expenses') }}</h6>
                        <h3 class="mb-0">{{ $format_money(data_get($stats, 'totals.grand_total', 0)) }}</h3>
                        <small class="d-block mt-1 opacity-75">{{ sprintf(__('%d Expenses'), data_get($stats, 'totals.total_count', 0)) }}</small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-check-circle fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50">{{ __('Paid') }}</h6>
                        <h3 class="mb-0">{{ $format_money(data_get($stats, 'totals.total_amount', 0)) }}</h3>
                        <small class="d-block mt-1 opacity-75">{{ __('Amount') }}</small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-receipt fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50">{{ __('Tax') }}</h6>
                        <h3 class="mb-0">{{ $format_money(data_get($stats, 'totals.total_tax', 0)) }}</h3>
                        <small class="d-block mt-1 opacity-75">{{ __('Total Tax') }}</small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-hourglass-split fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50">{{ __('Pending') }}</h6>
                        <h3 class="mb-0">{{ $format_money(0) }}</h3>
                        <small class="d-block mt-1 opacity-75">{{ __('Unpaid Expenses') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="">
                    <input type="hidden" name="screen" value="expenses" />
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" 
                                       name="search" value="{{ $search }}" 
                                       placeholder="{{ __('Search expenses...') }}">
                            </div>
                        </div>
                        <div class="col">
                            <select name="category_id" class="form-select">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->category_id }}" 
                                        {{ (string) $category_id === (string) ($category->category_id ?? '') ? "selected='selected'" : '' }}>
                                        {{ $category->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <select name="payment_status" class="form-select">
                                <option value="">{{ __('All Status') }}</option>
                                @foreach ($payment_statuses as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ (string) $payment_status === (string) $key ? "selected='selected'" : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" name="start_date" 
                                   value="{{ $start_date }}"
                                   placeholder="{{ __('From Date') }}">
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" name="end_date" 
                                   value="{{ $end_date }}"
                                   placeholder="{{ __('To Date') }}">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <div class="d-flex gap-2">
                                <a href="{{ $reset_url }}" 
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> {{ __('Filter') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-receipt me-2"></i>
                    {{ __('All Expenses') }}
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="bi bi-plus-circle me-1"></i>
                        {{ __('Add Expense') }}
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> {{ __('Export') }}
                        </button>
                        @if ( $licenseState )
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item export-csv" href="#">
                                        <i class="bi bi-filetype-csv me-2"></i>{{ __('CSV') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item export-pdf" href="#">
                                        <i class="bi bi-filetype-pdf me-2"></i>{{ __('PDF') }}
                                    </a>
                                </li>
                            </ul>
                        @else
                            <ul class="dropdown-menu">
                                <li><span class="dropdown-item text-muted">
                                    <i class="bi bi-lock me-2"></i>{{ __( 'Pro Feature' ) }}
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/pricing/" target="_blank" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                                    <i class="bi bi-star me-2"></i>{{ __( 'Upgrade Now' ) }}
                                </a></li>
                                <li><a class="dropdown-item text-info" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/repairbuddy-features/" target="_blank">
                                    <i class="bi bi-info-circle me-2"></i>{{ __( 'View Features' ) }}
                                </a></li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('ID') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('By') }}</th>
                                <th class="text-end pe-4">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (empty($expenses))
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-receipt fs-1 opacity-50"></i>
                                            <p class="mt-2">{{ __('No expenses found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="ps-4">
                                            <strong>#{{ $expense->expense_number }}</strong>
                                        </td>
                                        <td>
                                            {{ $expense->expense_date_formatted ?? ($expense->expense_date ?? '') }}
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $expense->color_code }}">
                                                {{ $expense->category_name }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $expense->description_trimmed ?? ($expense->description ?? '') }}
                                            @if ($expense->receipt_number)
                                                <br><small class="text-muted">{{ sprintf(__('Receipt: %s'), $expense->receipt_number) }}</small>
                                            @endif
                                            @php
                                                $_job_id = $expense->job_id ?? null;
                                                $_job_display_id = $_job_id;
                                                if (is_array($job_display_ids ?? null) && array_key_exists($_job_id, $job_display_ids)) {
                                                    $_job_display_id = $job_display_ids[$_job_id];
                                                }
                                            @endphp
                                            @if ( $_job_id )
                                                <br><small class="text-muted">{{ sprintf(__('Job ID: %s'), $_job_display_id ) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $expense->amount_formatted ?? $format_money($expense->amount) }}</strong>
                                        </td>
                                        <td>
                                            {{ $expense->tax_amount_formatted ?? $format_money($expense->tax_amount) }}
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $expense->total_amount_formatted ?? $format_money($expense->total_amount) }}</strong>
                                        </td>
                                        <td>
                                            @php
                                            $payment_status_labels = is_array($payment_status_labels ?? null) ? $payment_status_labels : (is_iterable($payment_statuses ?? null) ? $payment_statuses : []);
                                            $status_class = array(
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'partial' => 'info',
                                                'overdue' => 'danger'
                                            );
                                            @endphp
                                            <span class="badge bg-{{ $status_class[$expense->payment_status] ?? 'secondary' }}">
                                                {{ $payment_status_labels[$expense->payment_status] ?? $expense->payment_status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($expense->payment_method)
                                                <small class="text-muted">{{ $expense->payment_method }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $expense->status == 'active' ? 'success' : 'secondary' }}">
                                                {{ $expense->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $creator_name = '';
                                                if (is_array($user_names ?? null) && array_key_exists($expense->created_by, $user_names)) {
                                                    $creator_name = (string) $user_names[$expense->created_by];
                                                }
                                            @endphp
                                            {{ $creator_name }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-gear me-1"></i> {{ __('Actions') }}
                                                </button>
                                                <ul class="dropdown-menu shadow-sm">
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                                                           data-expense-id="{{ $expense->expense_id }}">
                                                            <i class="bi bi-pencil-square text-primary me-2"></i>
                                                            {{ __('Edit') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" data-bs-target="#viewExpenseModal"
                                                           data-expense-id="{{ $expense->expense_id }}">
                                                            <i class="bi bi-eye text-info me-2"></i>
                                                            {{ __('View Details') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-expense-btn" href="#" 
                                                        data-expense-id="{{ $expense->expense_id }}">
                                                            <i class="bi bi-trash text-danger me-2"></i>
                                                            {{ __('Delete') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            @if ($total_pages > 1)
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            {{ sprintf(__('Showing %d to %d of %d expenses'), 
                                $offset + 1, 
                                min($offset + $limit, $total_expenses), 
                                $total_expenses) }}
                        </div>
                        <ul class="pagination mb-0">
                            @if ($page > 1)
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="{{ $page_url_prev }}">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            @endif
                            
                            @for ($i = 1; $i <= $total_pages; $i++)
                                @if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2))
                                    <li class="page-item {{ $i == $page ? 'active' : '' }}">
                                        <a class="page-link" 
                                           href="{{ $page_urls[$i] ?? url()->current() }}">
                                            {{ $i }}
                                        </a>
                                    </li>
                                @elseif ($i == $page - 3 || $i == $page + 3)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endfor
                            
                            @if ($page < $total_pages)
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="{{ $page_url_next }}">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addExpenseForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_add_expense">
                        <input type="hidden" name="nonce" value="{{ $nonce }}">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">
                                    {{ __('Date *') }}
                                </label>
                                <input type="date" class="form-control" id="expense_date" 
                                       name="expense_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">
                                    {{ __('Category *') }}
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">{{ __('Select Category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->category_id }}">
                                            {{ $category->category_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">
                                    {{ __('Description *') }}
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="amount" class="form-label">
                                    {{ __('Amount *') }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currency_symbol }}</span>
                                    <input type="number" class="form-control" id="amount" 
                                           name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="payment_method" class="form-label">
                                    {{ __('Payment Method') }}
                                </label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">{{ __('Select Method') }}</option>
                                    @foreach ($payment_methods as $key => $label)
                                        <option value="{{ $key }}">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="payment_status" class="form-label">
                                    {{ __('Payment Status') }}
                                </label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    @foreach ($payment_statuses as $key => $label)
                                        <option value="{{ $key }}" 
                                            {{ (string) $key === 'paid' ? "selected='selected'" : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="receipt_number" class="form-label">
                                    {{ __('Receipt Number') }}
                                </label>
                                <input type="text" class="form-control" id="receipt_number" 
                                       name="receipt_number">
                            </div>
                            <div class="col-md-6">
                                <label for="expense_type" class="form-label">
                                    {{ __('Expense Type') }}
                                </label>
                                <select class="form-select" id="expense_type" name="expense_type">
                                    @foreach (($expense_types ?? []) as $key => $label)
                                        <option value="{{ $key }}">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="submitExpenseForm">
                        {{ __('Add Expense') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Expense') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" data-async method="post">
                    <input type="hidden" name="action" value="wcrb_update_expense">
                    <input type="hidden" name="nonce" value="{{ $nonce }}">
                    <input type="hidden" name="expense_id" id="edit_expense_id">
                    
                    <!-- Same fields as add form but with edit_ prefix IDs -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_expense_date" class="form-label">
                                {{ __('Date *') }}
                            </label>
                            <input type="date" class="form-control" id="edit_expense_date" 
                                   name="expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_category_id" class="form-label">
                                {{ __('Category *') }}
                            </label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->category_id }}">
                                        {{ $category->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label">
                                {{ __('Description *') }}
                            </label>
                            <textarea class="form-control" id="edit_description" name="description" 
                                      rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_amount" class="form-label">
                                {{ __('Amount *') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency_symbol }}</span>
                                <input type="number" class="form-control" id="edit_amount" 
                                       name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_method" class="form-label">
                                {{ __('Payment Method') }}
                            </label>
                            <select class="form-select" id="edit_payment_method" name="payment_method">
                                <option value="">{{ __('Select Method') }}</option>
                                @foreach ($payment_methods as $key => $label)
                                    <option value="{{ $key }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_status" class="form-label">
                                {{ __('Payment Status') }}
                            </label>
                            <select class="form-select" id="edit_payment_status" name="payment_status">
                                @foreach ($payment_statuses as $key => $label)
                                    <option value="{{ $key }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_receipt_number" class="form-label">
                                {{ __('Receipt Number') }}
                            </label>
                            <input type="text" class="form-control" id="edit_receipt_number" 
                                   name="receipt_number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_expense_type" class="form-label">
                                {{ __('Expense Type') }}
                            </label>
                            <select class="form-select" id="edit_expense_type" name="expense_type">
                                @foreach (($expense_types ?? []) as $key => $label)
                                    <option value="{{ $key }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">
                                {{ __('Status') }}
                            </label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">{{ __( 'Active' ) }}</option>
                                <option value="void">{{ __( 'Void' ) }}</option>
                                <option value="refunded">{{ __( 'Refunded' ) }}</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __( 'Close' ) }}
                </button>
                <button type="button" class="btn btn-primary" id="submitEditExpenseForm">
                    {{ __( 'Update Expense' ) }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Expense Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="expense-details">
                    <!-- AJAX content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

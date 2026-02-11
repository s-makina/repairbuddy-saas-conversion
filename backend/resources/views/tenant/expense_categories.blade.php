@extends('tenant.layouts.myaccount', ['title' => 'Expense Categories'])

@section('content')
@php
    $userRole = is_string($userRole ?? null) ? (string) $userRole : (is_object($user ?? null) ? (string) ($user->role ?? 'guest') : 'guest');
    $licenseState = (bool) ($licenseState ?? true);

    $categories = is_iterable($categories ?? null) ? $categories : [];

    $canAccess = in_array($userRole, ['store_manager', 'administrator'], true);

    $nonce = is_string($nonce ?? null) ? $nonce : csrf_token();
@endphp

@if ( $canAccess)
        {{ __("You do not have sufficient permissions to access this page.") }}
@else
    <!-- Categories Content -->
    <main class="dashboard-content container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"></h4>
            <button type="button" class="btn btn-primary btn-sm" 
                    data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-1"></i>
                {{ __('Add Category') }}
            </button>
        </div>

        <!-- Categories Grid -->
        <div class="row g-3">
            @if (empty($categories))
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-tags fs-1 text-muted"></i>
                            <p class="mt-3 text-muted">{{ __('No categories found') }}</p>
                        </div>
                    </div>
                </div>
            @else
                @foreach ( $categories as $category )
                    <div class="col-md-4">
                        <div class="card category-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="category-icon rounded-circle d-flex align-items-center justify-content-center" 
                                             style="background-color: {{ $category->color_code }}; width: 50px; height: 50px;">
                                            <i class="bi bi-tag text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">{{ $category->category_name }}</h5>
                                        @if ( $category->category_description )
                                            <p class="card-text text-muted small mb-2">
                                                {{ $category->category_description }}
                                            </p>
                                        @endif
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                                {{ $category->is_active ? __( 'Active' ) : __( 'Inactive' ) }}
                                            </span>
                                            @if ( $category->taxable )
                                                <span class="badge bg-info">
                                                    {{ sprintf( __( 'Tax: %s%%' ), $category->tax_rate ) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                   data-category-id="{{ $category->category_id }}">
                                                    <i class="bi bi-pencil-square text-primary me-2"></i>
                                                    {{ __( 'Edit' ) }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-category-btn" href="#" 
                                                data-category-id="{{ $category->category_id }}">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    {{ __( 'Delete' ) }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </main>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_add_expense_category">
                        <input type="hidden" name="nonce" value="{{ $nonce }}">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">
                                {{ __('Category Name *') }}
                            </label>
                            <input type="text" class="form-control" id="category_name" 
                                   name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_description" class="form-label">
                                {{ __('Description') }}
                            </label>
                            <textarea class="form-control" id="category_description" 
                                      name="category_description" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="color_code" class="form-label">
                                    {{ __('Color') }}
                                </label>
                                <input type="color" class="form-control form-control-color" 
                                       id="color_code" name="color_code" 
                                       value="#3498db" title="Choose color">
                            </div>
                            <div class="col-md-6">
                                <label for="sort_order" class="form-label">
                                    {{ __('Sort Order') }}
                                </label>
                                <input type="number" class="form-control" id="sort_order" 
                                       name="sort_order" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="taxable" name="taxable" value="1" checked>
                                    <label class="form-check-label" for="taxable">
                                        {{ __('Taxable') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Active') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="tax_rate_field" style="display: block;">
                            <label for="tax_rate" class="form-label">
                                {{ __('Tax Rate (%)') }}
                            </label>
                            <input type="number" class="form-control" id="tax_rate" 
                                   name="tax_rate" value="0" step="0.01" min="0" max="100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="submitCategoryForm">
                        {{ __('Add Category') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_update_category">
                        <input type="hidden" name="nonce" value="{{ $nonce }}">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">
                                {{ __('Category Name *') }}
                            </label>
                            <input type="text" class="form-control" id="edit_category_name" 
                                name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category_description" class="form-label">
                                {{ __('Description') }}
                            </label>
                            <textarea class="form-control" id="edit_category_description" 
                                    name="category_description" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_color_code" class="form-label">
                                    {{ __('Color') }}
                                </label>
                                <input type="color" class="form-control form-control-color" 
                                    id="edit_color_code" name="color_code" 
                                    value="#3498db" title="Choose color">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_sort_order" class="form-label">
                                    {{ __('Sort Order') }}
                                </label>
                                <input type="number" class="form-control" id="edit_sort_order" 
                                    name="sort_order" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        id="edit_taxable" name="taxable" value="1">
                                    <label class="form-check-label" for="edit_taxable">
                                        {{ __('Taxable') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        {{ __('Active') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="edit_tax_rate_field" style="display: none;">
                            <label for="edit_tax_rate" class="form-label">
                                {{ __('Tax Rate (%)') }}
                            </label>
                            <input type="number" class="form-control" id="edit_tax_rate" 
                                name="tax_rate" value="0" step="0.01" min="0" max="100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="submitEditCategoryForm">
                        {{ __('Update Category') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

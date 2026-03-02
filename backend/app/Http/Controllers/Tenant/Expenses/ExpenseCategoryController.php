<?php

namespace App\Http\Controllers\Tenant\Expenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Expenses\StoreExpenseCategoryRequest;
use App\Http\Requests\Tenant\Expenses\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories.
     */
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $categories = ExpenseCategory::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return view('tenant.expenses.categories.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expense_categories',
            'pageTitle' => __('Expense Categories'),
            'categories' => $categories,
        ]);
    }

    /**
     * Get categories datatable.
     */
    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = ExpenseCategory::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('category_name');

        return DataTables::eloquent($query)
            ->addColumn('color_display', function (ExpenseCategory $category) {
                $color = is_string($category->color_code) ? $category->color_code : '#3498db';
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div style="width: 24px; height: 24px; border-radius: 6px; background-color: ' . e($color) . '; border: 1px solid rgba(0,0,0,0.1);"></div>'
                    . '<span class="text-muted small">' . e($color) . '</span>'
                    . '</div>';
            })
            ->addColumn('status_display', function (ExpenseCategory $category) {
                if ($category->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('tax_display', function (ExpenseCategory $category) {
                if (! $category->taxable) {
                    return '<span class="text-muted">' . e(__('Non-taxable')) . '</span>';
                }

                return '<span class="badge bg-info">' . e($category->tax_rate . '%') . '</span>';
            })
            ->addColumn('actions_display', function (ExpenseCategory $category) use ($tenant) {
                $editUrl = route('tenant.expense_categories.edit', ['business' => $tenant->slug, 'category' => $category->id]);
                $deleteUrl = route('tenant.expense_categories.delete', ['business' => $tenant->slug, 'category' => $category->id]);
                $csrf = csrf_field();

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '" onclick="return confirm(\'' . e(__('Are you sure you want to delete this category?')) . '\')"><i class="bi bi-trash"></i></button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['color_display', 'status_display', 'tax_display', 'actions_display'])
            ->toJson();
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $parentOptions = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->limit(500)
            ->get(['id', 'category_name'])
            ->mapWithKeys(fn (ExpenseCategory $c) => [(string) $c->id => (string) $c->category_name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.expenses.categories.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expense_categories',
            'pageTitle' => __('Add Expense Category'),
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreExpenseCategoryRequest $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $name = trim((string) $validated['category_name']);

        $exists = ExpenseCategory::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('category_name', $name)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('tenant.expense_categories.create', ['business' => $tenant->slug])
                ->withErrors(['category_name' => __('Category name already exists.')])
                ->withInput();
        }

        ExpenseCategory::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'category_name' => $name,
            'category_description' => $validated['category_description'] ?? null,
            'category_type' => $validated['category_type'] ?? 'expense',
            'color_code' => $validated['color_code'] ?? '#3498db',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'taxable' => (bool) ($validated['taxable'] ?? false),
            'tax_rate' => (float) ($validated['tax_rate'] ?? 0),
            'parent_category_id' => $validated['parent_category_id'] ?? null,
        ]);

        return redirect()
            ->route('tenant.expense_categories.index', ['business' => $tenant->slug])
            ->with('status', __('Expense category added.'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Request $request, string $business, int $category)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = ExpenseCategory::query()->findOrFail($category);

        $parentOptions = ExpenseCategory::query()
            ->where('is_active', true)
            ->whereKeyNot($model->id)
            ->orderBy('category_name')
            ->limit(500)
            ->get(['id', 'category_name'])
            ->mapWithKeys(fn (ExpenseCategory $c) => [(string) $c->id => (string) $c->category_name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.expenses.categories.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expense_categories',
            'pageTitle' => __('Edit Expense Category'),
            'category' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateExpenseCategoryRequest $request, string $business, int $category): RedirectResponse
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $model = ExpenseCategory::query()->findOrFail($category);

        $name = trim((string) $validated['category_name']);

        $parentId = $validated['parent_category_id'] ?? null;
        if ($parentId !== null && (int) $parentId === (int) $model->id) {
            $parentId = null;
        }

        $exists = ExpenseCategory::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('category_name', $name)
            ->whereKeyNot($model->id)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('tenant.expense_categories.edit', ['business' => $tenant->slug, 'category' => $model->id])
                ->withErrors(['category_name' => __('Category name already exists.')])
                ->withInput();
        }

        $model->forceFill([
            'category_name' => $name,
            'category_description' => $validated['category_description'] ?? null,
            'category_type' => $validated['category_type'] ?? 'expense',
            'color_code' => $validated['color_code'] ?? '#3498db',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'taxable' => (bool) ($validated['taxable'] ?? false),
            'tax_rate' => (float) ($validated['tax_rate'] ?? 0),
            'parent_category_id' => $parentId,
        ])->save();

        return redirect()
            ->route('tenant.expense_categories.index', ['business' => $tenant->slug])
            ->with('status', __('Expense category updated.'));
    }

    /**
     * Delete the specified category.
     */
    public function delete(Request $request, string $business, int $category): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = ExpenseCategory::query()->findOrFail($category);

        // Check if category has children
        if ($model->children()->exists()) {
            return redirect()
                ->route('tenant.expense_categories.index', ['business' => $tenant->slug])
                ->withErrors(['category' => __('Cannot delete a category that has sub-categories.')]);
        }

        // Check if category has expenses
        if ($model->expenses()->exists()) {
            return redirect()
                ->route('tenant.expense_categories.index', ['business' => $tenant->slug])
                ->withErrors(['category' => __('Cannot delete a category that has expenses.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.expense_categories.index', ['business' => $tenant->slug])
            ->with('status', __('Expense category deleted.'));
    }
}

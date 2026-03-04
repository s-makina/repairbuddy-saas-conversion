<?php

namespace App\Http\Controllers\Tenant\Expenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Expenses\SetExpenseStatusRequest;
use App\Http\Requests\Tenant\Expenses\StoreExpenseRequest;
use App\Http\Requests\Tenant\Expenses\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        // Get filter values
        $filters = [
            'category_id' => $request->input('category_id'),
            'payment_status' => $request->input('payment_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'job_id' => $request->input('job_id'),
            'status' => $request->input('status', 'active'),
        ];

        // Get statistics for summary cards
        $statistics = $this->getStatistics($tenant->id, $branchId, $filters);

        // Get categories for filter dropdown
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'color_code']);

        // Get expenses for card grid
        $expenses = Expense::query()
            ->with(['category', 'job', 'technician', 'creator'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($filters['category_id'], fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['payment_status'], fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['job_id'], fn ($q, $v) => $q->where('job_id', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['start_date'], fn ($q, $v) => $q->where('expense_date', '>=', $v))
            ->when($filters['end_date'], fn ($q, $v) => $q->where('expense_date', '<=', $v))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('tenant.expenses.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expenses',
            'pageTitle' => __('Expenses'),
            'categories' => $categories,
            'filters' => $filters,
            'statistics' => $statistics,
            'expenses' => $expenses,
        ]);
    }

    /**
     * Get expenses datatable.
     */
    public function datatable(Request $request, string $business): JsonResponse
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = Expense::query()
            ->with(['category', 'job', 'technician', 'creator'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->input('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->input('payment_status'), fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->input('job_id'), fn ($q, $v) => $q->where('job_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('start_date'), fn ($q, $v) => $q->where('expense_date', '>=', $v))
            ->when($request->input('end_date'), fn ($q, $v) => $q->where('expense_date', '<=', $v))
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        $currency = is_string($tenant->currency) && $tenant->currency !== ''
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
            return $currencySymbol . number_format((float) $amount, 2, '.', ',');
        };

        return DataTables::eloquent($query)
            ->addColumn('expense_number_display', function (Expense $expense) use ($tenant) {
                $url = route('tenant.expenses.show', ['business' => $tenant->slug, 'expense' => $expense->id]);
                return '<a href="' . e($url) . '" class="fw-semibold">' . e($expense->expense_number) . '</a>';
            })
            ->addColumn('date_display', function (Expense $expense) {
                return $expense->expense_date?->format('M d, Y') ?? '';
            })
            ->addColumn('category_display', function (Expense $expense) {
                if (! $expense->category) {
                    return '<span class="text-muted">' . e(__('Uncategorized')) . '</span>';
                }
                $color = $expense->category->color_code ?? '#3498db';
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div style="width: 12px; height: 12px; border-radius: 3px; background-color: ' . e($color) . ';"></div>'
                    . '<span>' . e($expense->category->category_name) . '</span>'
                    . '</div>';
            })
            ->addColumn('amount_display', function (Expense $expense) use ($formatMoney) {
                return '<span class="fw-semibold">' . e($formatMoney($expense->amount)) . '</span>';
            })
            ->addColumn('tax_display', function (Expense $expense) use ($formatMoney) {
                if ($expense->tax_amount > 0) {
                    return '<span class="text-muted">' . e($formatMoney($expense->tax_amount)) . '</span>';
                }
                return '<span class="text-muted">—</span>';
            })
            ->addColumn('total_display', function (Expense $expense) use ($formatMoney) {
                return '<span class="fw-semibold text-danger">' . e($formatMoney($expense->total_amount)) . '</span>';
            })
            ->addColumn('payment_status_display', function (Expense $expense) {
                $statusClasses = [
                    'paid' => 'success',
                    'pending' => 'warning',
                    'partial' => 'info',
                    'overdue' => 'danger',
                ];
                $class = $statusClasses[$expense->payment_status] ?? 'secondary';
                $label = Expense::PAYMENT_STATUSES[$expense->payment_status] ?? $expense->payment_status;
                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->addColumn('job_display', function (Expense $expense) use ($tenant) {
                if (! $expense->job_id || ! $expense->job) {
                    return '<span class="text-muted">—</span>';
                }
                $url = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $expense->job_id]);
                return '<a href="' . e($url) . '" class="text-decoration-none">' . e($expense->job->case_number ?? '#' . $expense->job_id) . '</a>';
            })
            ->addColumn('status_display', function (Expense $expense) {
                $statusClasses = [
                    'active' => 'success',
                    'void' => 'secondary',
                    'refunded' => 'warning',
                ];
                $class = $statusClasses[$expense->status] ?? 'secondary';
                $label = Expense::STATUSES[$expense->status] ?? $expense->status;
                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->addColumn('actions_display', function (Expense $expense) use ($tenant) {
                $deleteUrl = route('tenant.expenses.delete', ['business' => $tenant->slug, 'expense' => $expense->id]);
                $csrf = csrf_field();

                $buttons = '<div class="d-inline-flex gap-1">'
                    . '<button type="button" class="btn btn-sm btn-outline-primary edit-expense-btn" data-expense-id="' . $expense->id . '" title="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></button>';

                if ($expense->status === 'active') {
                    $buttons .= '<form method="post" action="' . e($deleteUrl) . '" style="display:inline;">' . $csrf
                        . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Void')) . '" onclick="return confirm(\'' . e(__('Are you sure you want to void this expense?')) . '\')"><i class="bi bi-x-circle"></i></button>'
                        . '</form>';
                }

                $buttons .= '</div>';

                return $buttons;
            })
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                $search = is_string($search) ? trim($search) : '';
                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('expense_number', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('receipt_number', 'like', '%' . $search . '%');
                });
            })
            ->rawColumns([
                'expense_number_display',
                'category_display',
                'amount_display',
                'tax_display',
                'total_display',
                'payment_status_display',
                'job_display',
                'status_display',
                'actions_display',
            ])
            ->toJson();
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'color_code', 'taxable', 'tax_rate']);

        $jobs = RepairBuddyJob::query()
            ->with('customer:id,name')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'case_number', 'customer_id']);

        $technicians = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('role', 'technician')
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'));
            })
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        return view('tenant.expenses.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expenses',
            'pageTitle' => __('Add Expense'),
            'categories' => $categories,
            'jobs' => $jobs,
            'technicians' => $technicians,
            'expense' => null,
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();
        $user = $request->user();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $expense = null;
        DB::transaction(function () use ($validated, $tenant, $branchId, $user, &$expense) {
            $expense = new Expense();
            $expense->tenant_id = $tenant->id;
            $expense->branch_id = $branchId;
            $expense->expense_number = Expense::generateExpenseNumber($tenant->id);
            $expense->expense_date = $validated['expense_date'];
            $expense->category_id = $validated['category_id'];
            $expense->expense_type = $validated['expense_type'] ?? 'general';
            $expense->amount = (float) $validated['amount'];
            $expense->description = $validated['description'] ?? null;
            $expense->payment_method = $validated['payment_method'] ?? 'cash';
            $expense->payment_status = $validated['payment_status'] ?? 'paid';
            $expense->receipt_number = $validated['receipt_number'] ?? null;
            $expense->job_id = $validated['job_id'] ?? null;
            $expense->technician_id = $validated['technician_id'] ?? null;
            $expense->currency = $tenant->currency ?? 'USD';
            $expense->status = 'active';
            $expense->created_by = $user?->id;

            // Calculate tax and total
            $expense->calculateTotals();
            $expense->save();
        });

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Expense added successfully.'),
                'expense' => [
                    'id' => $expense->id,
                    'expense_number' => $expense->expense_number,
                    'amount' => $expense->amount,
                    'total_amount' => $expense->total_amount,
                ],
            ]);
        }

        // If expense was added from job page, redirect back to job
        if (!empty($validated['job_id'])) {
            return redirect()
                ->route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $validated['job_id']])
                ->with('status', __('Expense added successfully.'));
        }

        return redirect()
            ->route('tenant.expenses.index', ['business' => $tenant->slug])
            ->with('status', __('Expense added successfully.'));
    }

    /**
     * Display the specified expense.
     */
    public function show(Request $request, string $business, int $expense)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $expenseModel = Expense::query()
            ->with(['category', 'job', 'technician', 'creator'])
            ->whereKey($expense)
            ->firstOrFail();

        return view('tenant.expenses.show', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expenses',
            'pageTitle' => __('Expense :number', ['number' => $expenseModel->expense_number]),
            'expense' => $expenseModel,
        ]);
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Request $request, string $business, int $expense)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $expenseModel = Expense::query()
            ->with(['category', 'job', 'technician'])
            ->whereKey($expense)
            ->firstOrFail();

        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'color_code', 'taxable', 'tax_rate']);

        $jobs = RepairBuddyJob::query()
            ->with('customer:id,name')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'case_number', 'customer_id']);

        $technicians = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('role', 'technician')
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'));
            })
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        return view('tenant.expenses.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'expenses',
            'pageTitle' => __('Edit Expense'),
            'expense' => $expenseModel,
            'categories' => $categories,
            'jobs' => $jobs,
            'technicians' => $technicians,
        ]);
    }

    /**
     * Get expense data for editing (JSON response for modal).
     */
    public function getExpenseJson(Request $request, string $business, int $expense)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $expenseModel = Expense::query()
            ->whereKey($expense)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'expense' => [
                'id' => $expenseModel->id,
                'expense_date' => $expenseModel->expense_date?->format('Y-m-d'),
                'category_id' => $expenseModel->category_id,
                'description' => $expenseModel->description,
                'amount' => $expenseModel->amount,
                'payment_method' => $expenseModel->payment_method,
                'payment_status' => $expenseModel->payment_status,
                'receipt_number' => $expenseModel->receipt_number,
                'expense_type' => $expenseModel->expense_type,
            ],
        ]);
    }

    /**
     * Update the specified expense.
     */
    public function update(UpdateExpenseRequest $request, string $business, int $expense)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $expenseModel = Expense::query()->whereKey($expense)->firstOrFail();

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $expenseModel, $user) {
            $expenseModel->expense_date = $validated['expense_date'] ?? $expenseModel->expense_date;
            $expenseModel->category_id = $validated['category_id'] ?? $expenseModel->category_id;
            $expenseModel->expense_type = $validated['expense_type'] ?? $expenseModel->expense_type;
            $expenseModel->amount = (float) ($validated['amount'] ?? $expenseModel->amount);
            $expenseModel->description = $validated['description'] ?? $expenseModel->description;
            $expenseModel->payment_method = $validated['payment_method'] ?? $expenseModel->payment_method;
            $expenseModel->payment_status = $validated['payment_status'] ?? $expenseModel->payment_status;
            $expenseModel->receipt_number = $validated['receipt_number'] ?? $expenseModel->receipt_number;
            $expenseModel->job_id = $validated['job_id'] ?? $expenseModel->job_id;
            $expenseModel->technician_id = $validated['technician_id'] ?? $expenseModel->technician_id;
            $expenseModel->updated_by = $user?->id;

            // Recalculate tax and total
            $expenseModel->calculateTotals();
            $expenseModel->save();
        });

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Expense updated successfully.'),
                'expense' => [
                    'id' => $expenseModel->id,
                    'expense_number' => $expenseModel->expense_number,
                    'amount' => $expenseModel->amount,
                    'total_amount' => $expenseModel->total_amount,
                ],
            ]);
        }

        return redirect()
            ->route('tenant.expenses.index', ['business' => $tenant->slug])
            ->with('status', __('Expense updated successfully.'));
    }

    /**
     * Void the specified expense (soft delete by setting status).
     */
    public function delete(Request $request, string $business, int $expense): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $expenseModel = Expense::query()->whereKey($expense)->firstOrFail();

        $expenseModel->forceFill([
            'status' => 'void',
            'updated_by' => $request->user()?->id,
        ])->save();

        return redirect()
            ->route('tenant.expenses.index', ['business' => $tenant->slug])
            ->with('status', __('Expense voided successfully.'));
    }

    /**
     * Update expense status.
     */
    public function setStatus(SetExpenseStatusRequest $request, string $business, int $expense): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $expenseModel = Expense::query()->whereKey($expense)->firstOrFail();

        $expenseModel->forceFill([
            'status' => $validated['status'],
            'updated_by' => $request->user()?->id,
        ])->save();

        return redirect()
            ->route('tenant.expenses.show', ['business' => $tenant->slug, 'expense' => $expenseModel->id])
            ->with('status', __('Expense status updated.'));
    }

    /**
     * Get expense statistics.
     */
    public function statistics(Request $request, string $business): JsonResponse
    {
        $tenant = TenantContext::tenant();
        $branchId = BranchContext::branchId();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $filters = [
            'category_id' => $request->input('category_id'),
            'payment_status' => $request->input('payment_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'job_id' => $request->input('job_id'),
        ];

        $statistics = $this->getStatistics($tenant->id, $branchId, $filters);

        return response()->json($statistics);
    }

    /**
     * Calculate expense statistics.
     */
    protected function getStatistics(int $tenantId, ?int $branchId, array $filters): array
    {
        $baseQuery = Expense::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['job_id'] ?? null, fn ($q, $v) => $q->where('job_id', $v))
            ->when($filters['start_date'] ?? null, fn ($q, $v) => $q->where('expense_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn ($q, $v) => $q->where('expense_date', '<=', $v));

        $totals = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(amount) as total_amount,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as grand_total
            ')
            ->first();

        // Category breakdown
        $categoryBreakdown = (clone $baseQuery)
            ->selectRaw('
                category_id,
                COUNT(*) as count,
                SUM(total_amount) as total
            ')
            ->with('category:id,category_name,color_code')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Payment status breakdown
        $paymentBreakdown = (clone $baseQuery)
            ->selectRaw('
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as total
            ')
            ->groupBy('payment_status')
            ->get();

        return [
            'total_count' => (int) ($totals->total_count ?? 0),
            'total_amount' => (float) ($totals->total_amount ?? 0),
            'total_tax' => (float) ($totals->total_tax ?? 0),
            'grand_total' => (float) ($totals->grand_total ?? 0),
            'by_category' => $categoryBreakdown,
            'by_payment_status' => $paymentBreakdown,
        ];
    }
}

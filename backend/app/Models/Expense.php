<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'expense_number',
        'expense_date',
        'category_id',
        'expense_type',
        'amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'receipt_number',
        'currency',
        'description',
        'status',
        'job_id',
        'technician_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Expense types available.
     */
    public const TYPES = [
        'general' => 'General',
        'business' => 'Business',
        'personal' => 'Personal',
        'operational' => 'Operational',
    ];

    /**
     * Payment methods available.
     */
    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'credit' => 'Credit Card',
        'debit' => 'Debit Card',
        'bank_transfer' => 'Bank Transfer',
        'check' => 'Check',
        'online' => 'Online Payment',
        'paypal' => 'PayPal',
        'other' => 'Other',
    ];

    /**
     * Payment statuses available.
     */
    public const PAYMENT_STATUSES = [
        'paid' => 'Paid',
        'pending' => 'Pending',
        'partial' => 'Partially Paid',
        'overdue' => 'Overdue',
    ];

    /**
     * Expense statuses available.
     */
    public const STATUSES = [
        'active' => 'Active',
        'void' => 'Void',
        'refunded' => 'Refunded',
    ];

    /**
     * Generate a unique expense number.
     */
    public static function generateExpenseNumber(int $tenantId): string
    {
        $year = now()->year;

        $lastNumber = static::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('expense_number', 'like', "EXP-{$year}-%")
            ->orderByDesc('id')
            ->value('expense_number');

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $lastSeq = (int) end($parts);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '0001';
        }

        return "EXP-{$year}-{$newSeq}";
    }

    /**
     * Calculate tax and total based on category.
     */
    public function calculateTotals(): void
    {
        $taxRate = 0;

        if ($this->category_id && $this->category) {
            if ($this->category->taxable) {
                $taxRate = (float) $this->category->tax_rate;
            }
        }

        $this->tax_amount = round($this->amount * ($taxRate / 100), 2);
        $this->total_amount = round($this->amount + $this->tax_amount, 2);
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * Get the branch this expense belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the category this expense belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the job this expense is associated with.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    /**
     * Get the technician associated with this expense.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the user who created this expense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this expense.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    /**
     * Scope to active expenses only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to expenses for a specific job.
     */
    public function scopeForJob(Builder $query, int $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope to expenses for a specific category.
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to expenses within a date range.
     */
    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->where('expense_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('expense_date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to expenses with a specific payment status.
     */
    public function scopePaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope to expenses for a specific technician.
     */
    public function scopeForTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Scope to expenses of a specific type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('expense_type', $type);
    }
}

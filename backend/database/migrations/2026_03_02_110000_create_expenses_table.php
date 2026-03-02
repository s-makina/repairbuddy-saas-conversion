<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Expense identification
            $table->string('expense_number', 50)->unique();
            $table->date('expense_date');
            
            // Category and classification
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('expense_type', 30)->default('general'); // general, business, personal, operational
            
            // Amounts
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            
            // Payment details
            $table->string('payment_method', 30)->default('cash'); // cash, credit, debit, bank_transfer, check, online, paypal, other
            $table->string('payment_status', 20)->default('paid'); // paid, pending, partial, overdue
            $table->string('receipt_number', 100)->nullable();
            $table->string('currency', 10)->default('USD');
            
            // Description and status
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active'); // active, void, refunded
            
            // Relations
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'job_id']);
            $table->index(['tenant_id', 'technician_id']);
            $table->index('expense_number');
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
            
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
            
            $table->foreign('category_id')
                ->references('id')
                ->on('expense_categories')
                ->nullOnDelete();
            
            $table->foreign('job_id')
                ->references('id')
                ->on('rb_jobs')
                ->nullOnDelete();
            
            $table->foreign('technician_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

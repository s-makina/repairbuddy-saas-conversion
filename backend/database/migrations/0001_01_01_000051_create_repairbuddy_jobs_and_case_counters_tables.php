<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_case_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id']);
        });

        Schema::create('rb_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('case_number', 64);
            $table->string('title', 255)->default('');

            $table->string('status_slug', 64);
            $table->string('payment_status_slug', 64)->nullable();
            $table->string('priority', 32)->nullable();

            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'case_number']);
            $table->index(['tenant_id', 'branch_id', 'status_slug']);
            $table->index(['tenant_id', 'branch_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_jobs');
        Schema::dropIfExists('rb_case_counters');
    }
};

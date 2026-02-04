<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            $table->string('time_type', 50)->default('time_charge');
            $table->string('activity', 100);
            $table->string('priority', 20)->default('medium');
            $table->text('work_description')->nullable();

            $table->json('device_data_json')->nullable();

            $table->string('log_state', 20)->default('pending');
            $table->integer('total_minutes')->nullable();

            $table->integer('hourly_rate_cents')->nullable();
            $table->integer('hourly_cost_cents')->nullable();
            $table->string('currency', 3)->nullable();

            $table->boolean('is_billable')->default(true);

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('billed_job_item_id')->nullable()->constrained('rb_job_items')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['job_id']);
            $table->index(['technician_id']);
            $table->index(['start_time']);
            $table->index(['log_state']);
            $table->index(['activity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_time_logs');
    }
};

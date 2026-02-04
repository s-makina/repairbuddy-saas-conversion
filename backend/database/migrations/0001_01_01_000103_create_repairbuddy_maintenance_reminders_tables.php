<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_maintenance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('description', 1024)->nullable();
            $table->unsignedInteger('interval_days');

            $table->foreignId('device_type_id')->nullable()->constrained('rb_device_types')->nullOnDelete();
            $table->foreignId('device_brand_id')->nullable()->constrained('rb_device_brands')->nullOnDelete();

            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('reminder_enabled')->default(true);

            $table->longText('email_body')->nullable();
            $table->longText('sms_body')->nullable();

            $table->timestamp('last_executed_at')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'reminder_enabled']);
            $table->index(['tenant_id', 'branch_id', 'updated_at']);
        });

        Schema::create('rb_maintenance_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('reminder_id')->constrained('rb_maintenance_reminders')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('rb_jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('channel', 16);
            $table->string('to_address', 255)->nullable();
            $table->string('status', 16);
            $table->string('error_message', 2048)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'reminder_id', 'created_at']);
            $table->index(['tenant_id', 'branch_id', 'job_id', 'created_at']);
        });

        Schema::create('rb_job_maintenance_reminder_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();
            $table->foreignId('reminder_id')->constrained('rb_maintenance_reminders')->cascadeOnDelete();

            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'job_id', 'reminder_id'], 'rb_job_maint_rem_state_unique');
            $table->index(['tenant_id', 'branch_id', 'reminder_id', 'last_sent_at'], 'rb_job_maint_rem_state_rem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_job_maintenance_reminder_state');
        Schema::dropIfExists('rb_maintenance_reminder_logs');
        Schema::dropIfExists('rb_maintenance_reminders');
    }
};

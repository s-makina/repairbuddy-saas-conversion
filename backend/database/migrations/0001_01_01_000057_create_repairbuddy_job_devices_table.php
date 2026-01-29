<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_job_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();
            $table->foreignId('customer_device_id')->constrained('rb_customer_devices')->cascadeOnDelete();

            $table->string('label_snapshot', 255);
            $table->string('serial_snapshot', 255)->nullable();
            $table->string('pin_snapshot', 255)->nullable();
            $table->text('notes_snapshot')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'job_id', 'customer_device_id'], 'rb_job_devices_unique');
            $table->index(['tenant_id', 'branch_id', 'job_id'], 'rb_job_devices_job_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_job_devices');
    }
};

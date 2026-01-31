<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_job_technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();
            $table->foreignId('technician_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'job_id', 'technician_user_id'], 'rb_job_technicians_unique');
            $table->index(['tenant_id', 'branch_id', 'job_id'], 'rb_job_technicians_job_idx');
            $table->index(['tenant_id', 'branch_id', 'technician_user_id'], 'rb_job_technicians_tech_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_job_technicians');
    }
};

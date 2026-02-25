<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_signature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();

            // pickup | delivery | custom
            $table->string('signature_type', 32)->default('custom');
            $table->string('signature_label', 255);

            // Verification
            $table->string('verification_code', 64)->unique();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            // State: pending | completed | expired
            $table->string('status', 16)->default('pending');

            // Completed data
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_ip', 45)->nullable();
            $table->string('completed_user_agent', 255)->nullable();
            $table->string('signature_file_path', 500)->nullable();

            // Who generated the request
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'job_id'], 'rb_sig_req_job_idx');
            $table->index(['tenant_id', 'branch_id', 'status'], 'rb_sig_req_status_idx');
            $table->index(['verification_code'], 'rb_sig_req_verification_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_signature_requests');
    }
};

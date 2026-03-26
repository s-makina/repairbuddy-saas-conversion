<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add estimate_id to support signatures for estimates.
     * Makes job_id nullable since a signature request can belong to either a job OR an estimate.
     */
    public function up(): void
    {
        Schema::table('rb_signature_requests', function (Blueprint $table) {
            // Make job_id nullable (signature can be for job OR estimate)
            $table->unsignedBigInteger('job_id')->nullable()->change();

            // Add estimate_id foreign key
            $table->foreignId('estimate_id')
                ->nullable()
                ->after('job_id')
                ->constrained('rb_estimates')
                ->cascadeOnDelete();

            // Add index for estimate lookups
            $table->index(['tenant_id', 'branch_id', 'estimate_id'], 'rb_sig_req_estimate_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_signature_requests', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['estimate_id']);
            $table->dropIndex('rb_sig_req_estimate_idx');
            $table->dropColumn('estimate_id');

            // Restore job_id as required (this may fail if there are null job_ids)
            // In production, ensure all records have job_id before rolling back
            $table->unsignedBigInteger('job_id')->nullable(false)->change();
        });
    }
};

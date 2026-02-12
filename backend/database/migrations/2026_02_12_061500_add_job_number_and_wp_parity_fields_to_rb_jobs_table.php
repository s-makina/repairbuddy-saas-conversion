<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('job_number')->nullable()->after('id');

            // WP parity fields
            $table->string('prices_inclu_exclu', 16)->nullable()->after('payment_status_slug');
            $table->boolean('can_review_it')->default(true)->after('prices_inclu_exclu');

            $table->index(['tenant_id', 'branch_id', 'job_number'], 'rb_jobs_job_number_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->dropIndex('rb_jobs_job_number_idx');
            $table->dropColumn(['job_number', 'prices_inclu_exclu', 'can_review_it']);
        });
    }
};

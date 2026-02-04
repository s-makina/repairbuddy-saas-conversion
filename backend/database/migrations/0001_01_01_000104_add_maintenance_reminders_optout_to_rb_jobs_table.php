<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('rb_jobs', 'maintenance_reminders_opted_out_at')) {
                $table->timestamp('maintenance_reminders_opted_out_at')->nullable()->after('next_service_date');
                $table->index(['tenant_id', 'branch_id', 'maintenance_reminders_opted_out_at'], 'rb_jobs_maint_optout_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('rb_jobs', 'maintenance_reminders_opted_out_at')) {
                $table->dropIndex('rb_jobs_maint_optout_idx');
                $table->dropColumn('maintenance_reminders_opted_out_at');
            }
        });
    }
};

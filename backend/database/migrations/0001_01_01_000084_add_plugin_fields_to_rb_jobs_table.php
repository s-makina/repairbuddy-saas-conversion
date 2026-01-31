<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('opened_at');
            $table->date('delivery_date')->nullable()->after('pickup_date');
            $table->date('next_service_date')->nullable()->after('delivery_date');
            $table->text('case_detail')->nullable()->after('next_service_date');
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete()->after('case_detail');

            $table->index(['tenant_id', 'branch_id', 'assigned_technician_id'], 'rb_jobs_assigned_technician_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->dropIndex('rb_jobs_assigned_technician_idx');

            $table->dropConstrainedForeignId('assigned_technician_id');
            $table->dropColumn('case_detail');
            $table->dropColumn('next_service_date');
            $table->dropColumn('delivery_date');
            $table->dropColumn('pickup_date');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('statuses', 'code')) {
                $table->string('code', 64)->nullable()->after('status_type');
            }
            if (! Schema::hasColumn('statuses', 'description')) {
                $table->string('description', 255)->nullable()->after('label');
            }
            if (! Schema::hasColumn('statuses', 'invoice_label')) {
                $table->string('invoice_label', 255)->nullable()->after('sms_enabled');
            }
        });

        Schema::table('statuses', function (Blueprint $table) {
            $hasTenant = Schema::hasColumn('statuses', 'tenant_id');
            $hasType = Schema::hasColumn('statuses', 'status_type');
            $hasCode = Schema::hasColumn('statuses', 'code');
            $hasActive = Schema::hasColumn('statuses', 'is_active');

            if ($hasTenant && $hasType && $hasCode) {
                $table->unique(['tenant_id', 'status_type', 'code'], 'statuses_tenant_type_code_unique');
            }

            if ($hasTenant && $hasType && $hasActive) {
                $table->index(['tenant_id', 'status_type', 'is_active'], 'statuses_tenant_type_active_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = [];
            try {
                $indexes = $sm->listTableIndexes('statuses');
            } catch (\Throwable $e) {
                $indexes = [];
            }

            if (isset($indexes['statuses_tenant_type_code_unique'])) {
                $table->dropUnique('statuses_tenant_type_code_unique');
            }
            if (isset($indexes['statuses_tenant_type_active_idx'])) {
                $table->dropIndex('statuses_tenant_type_active_idx');
            }

            if (Schema::hasColumn('statuses', 'invoice_label')) {
                $table->dropColumn('invoice_label');
            }
            if (Schema::hasColumn('statuses', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('statuses', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};

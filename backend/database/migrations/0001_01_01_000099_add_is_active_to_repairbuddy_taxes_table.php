<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_taxes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_default');
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('rb_taxes', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'branch_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};

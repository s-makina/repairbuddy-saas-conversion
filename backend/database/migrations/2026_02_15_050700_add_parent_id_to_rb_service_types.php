<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_service_types', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('branch_id')->constrained('rb_service_types')->nullOnDelete();
            $table->index(['tenant_id', 'branch_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rb_service_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};

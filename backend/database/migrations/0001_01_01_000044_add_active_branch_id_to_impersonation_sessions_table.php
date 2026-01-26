<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impersonation_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('active_branch_id')->nullable()->after('tenant_id');
            $table->index('active_branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('impersonation_sessions', function (Blueprint $table) {
            $table->dropIndex(['active_branch_id']);
            $table->dropColumn('active_branch_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('default_branch_id')->nullable()->after('id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('default_branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['default_branch_id']);
            $table->dropColumn('default_branch_id');
        });
    }
};

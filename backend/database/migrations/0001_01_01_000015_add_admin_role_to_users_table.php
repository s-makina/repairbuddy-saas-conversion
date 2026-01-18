<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_role')->nullable()->after('is_admin')->index();
        });

        DB::table('users')
            ->where('is_admin', true)
            ->whereNull('admin_role')
            ->update(['admin_role' => 'platform_admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['admin_role']);
            $table->dropColumn('admin_role');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('rb_case_prefix', 32)->nullable()->after('code');
            $table->unsignedTinyInteger('rb_case_digits')->default(6)->after('rb_case_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['rb_case_prefix', 'rb_case_digits']);
        });
    }
};

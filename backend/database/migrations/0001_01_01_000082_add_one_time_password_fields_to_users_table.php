<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('one_time_password_hash')->nullable()->after('password');
            $table->timestamp('one_time_password_expires_at')->nullable()->after('one_time_password_hash');
            $table->timestamp('one_time_password_used_at')->nullable()->after('one_time_password_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('one_time_password_used_at');
            $table->dropColumn('one_time_password_expires_at');
            $table->dropColumn('one_time_password_hash');
        });
    }
};

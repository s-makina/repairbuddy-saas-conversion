<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('otp_enabled')->default(false)->after('email_verified_at')->index();
            $table->text('otp_secret')->nullable()->after('otp_enabled');
            $table->timestamp('otp_confirmed_at')->nullable()->after('otp_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp_confirmed_at');
            $table->dropColumn('otp_secret');
            $table->dropColumn('otp_enabled');
        });
    }
};

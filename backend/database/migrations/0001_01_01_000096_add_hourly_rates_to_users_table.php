<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('tech_hourly_rate_cents')->nullable()->after('otp_confirmed_at');
            $table->integer('client_hourly_rate_cents')->nullable()->after('tech_hourly_rate_cents');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tech_hourly_rate_cents', 'client_hourly_rate_cents']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_security_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->json('mfa_required_roles')->nullable();
            $table->unsignedSmallInteger('mfa_grace_period_days')->default(7);
            $table->timestamp('mfa_enforce_after')->nullable();

            $table->unsignedSmallInteger('session_idle_timeout_minutes')->default(60);
            $table->unsignedSmallInteger('session_max_lifetime_days')->default(30);

            $table->unsignedSmallInteger('lockout_max_attempts')->default(10);
            $table->unsignedSmallInteger('lockout_duration_minutes')->default(15);

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_security_settings');
    }
};

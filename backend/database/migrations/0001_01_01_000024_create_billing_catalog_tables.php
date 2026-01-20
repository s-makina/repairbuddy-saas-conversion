<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('billing_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_plan_id')->constrained('billing_plans')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft')->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique(['billing_plan_id', 'version']);
        });

        Schema::create('billing_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_plan_version_id')->constrained('billing_plan_versions')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->string('interval');
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('trial_days')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('default_for_currency_interval')->nullable();
            $table->timestamps();

            $table->index(['billing_plan_version_id', 'currency', 'interval']);
            $table->unique(['billing_plan_version_id', 'default_for_currency_interval'], 'billing_prices_default_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_prices');
        Schema::dropIfExists('billing_plan_versions');
        Schema::dropIfExists('billing_plans');
    }
};

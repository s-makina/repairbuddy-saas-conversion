<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlement_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('value_type');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_plan_version_id')->constrained('billing_plan_versions')->cascadeOnDelete();
            $table->foreignId('entitlement_definition_id')->constrained('entitlement_definitions')->cascadeOnDelete();
            $table->json('value_json');
            $table->timestamps();

            $table->unique(['billing_plan_version_id', 'entitlement_definition_id'], 'plan_entitlements_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_entitlements');
        Schema::dropIfExists('entitlement_definitions');
    }
};

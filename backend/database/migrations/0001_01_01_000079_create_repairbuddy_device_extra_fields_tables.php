<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_device_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('key', 64);
            $table->string('label', 255);
            $table->string('type', 32)->default('text');

            $table->boolean('show_in_booking')->default(false);
            $table->boolean('show_in_invoice')->default(false);
            $table->boolean('show_in_portal')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'key'], 'rb_device_field_definitions_unique');
            $table->index(['tenant_id', 'branch_id', 'is_active'], 'rb_device_field_definitions_active_idx');
        });

        Schema::create('rb_customer_device_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('customer_device_id')->constrained('rb_customer_devices')->cascadeOnDelete();
            $table->foreignId('field_definition_id')->constrained('rb_device_field_definitions')->restrictOnDelete();

            $table->text('value_text')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'customer_device_id', 'field_definition_id'], 'rb_customer_device_field_values_unique');
            $table->index(['tenant_id', 'branch_id', 'customer_device_id'], 'rb_customer_device_field_values_cd_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_customer_device_field_values');
        Schema::dropIfExists('rb_device_field_definitions');
    }
};

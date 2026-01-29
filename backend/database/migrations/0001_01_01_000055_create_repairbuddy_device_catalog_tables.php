<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_device_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('name', 255);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });

        Schema::create('rb_device_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });

        Schema::create('rb_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('device_type_id')->constrained('rb_device_types')->cascadeOnDelete();
            $table->foreignId('device_brand_id')->constrained('rb_device_brands')->cascadeOnDelete();

            $table->string('model', 255);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'device_type_id', 'device_brand_id', 'model'], 'rb_devices_unique_model');
            $table->index(['tenant_id', 'branch_id', 'is_active']);
            $table->index(['tenant_id', 'branch_id', 'device_type_id']);
            $table->index(['tenant_id', 'branch_id', 'device_brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_devices');
        Schema::dropIfExists('rb_device_brands');
        Schema::dropIfExists('rb_device_types');
    }
};

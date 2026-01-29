<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_part_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('name', 255);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });

        Schema::create('rb_part_brands', function (Blueprint $table) {
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

        Schema::create('rb_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('part_type_id')->nullable()->constrained('rb_part_types')->nullOnDelete();
            $table->foreignId('part_brand_id')->nullable()->constrained('rb_part_brands')->nullOnDelete();

            $table->string('name', 255);
            $table->string('sku', 128)->nullable();

            $table->integer('price_amount_cents')->nullable();
            $table->string('price_currency', 8)->nullable();

            $table->integer('stock')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'is_active']);
            $table->index(['tenant_id', 'branch_id', 'part_type_id']);
            $table->index(['tenant_id', 'branch_id', 'part_brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_parts');
        Schema::dropIfExists('rb_part_brands');
        Schema::dropIfExists('rb_part_types');
    }
};

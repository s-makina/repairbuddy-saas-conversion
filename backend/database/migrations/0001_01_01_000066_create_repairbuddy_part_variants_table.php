<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_part_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('part_id')->constrained('rb_parts')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('sku', 128)->nullable();

            $table->string('manufacturing_code', 255)->nullable();
            $table->string('stock_code', 255)->nullable();

            $table->integer('price_amount_cents')->nullable();
            $table->string('price_currency', 8)->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('rb_taxes')->nullOnDelete();

            $table->string('warranty', 255)->nullable();
            $table->text('core_features')->nullable();
            $table->string('capacity', 255)->nullable();

            $table->integer('installation_charges_amount_cents')->nullable();
            $table->string('installation_charges_currency', 8)->nullable();
            $table->string('installation_message', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'part_id', 'is_active'], 'rb_part_variants_part_idx');
            $table->index(['tenant_id', 'branch_id', 'tax_id'], 'rb_part_variants_tax_idx');
            $table->unique(['tenant_id', 'branch_id', 'part_id', 'name'], 'rb_part_variants_unique_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_part_variants');
    }
};

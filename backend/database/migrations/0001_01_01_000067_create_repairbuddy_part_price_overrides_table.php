<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_part_price_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('part_id')->constrained('rb_parts')->cascadeOnDelete();
            $table->foreignId('part_variant_id')->nullable()->constrained('rb_part_variants')->cascadeOnDelete();

            $table->string('scope_type', 32);
            $table->unsignedBigInteger('scope_ref_id');

            $table->integer('price_amount_cents')->nullable();
            $table->string('price_currency', 8)->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('rb_taxes')->nullOnDelete();

            $table->string('manufacturing_code', 255)->nullable();
            $table->string('stock_code', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'part_id', 'part_variant_id', 'scope_type', 'scope_ref_id'], 'rb_part_price_overrides_unique');
            $table->index(['tenant_id', 'branch_id', 'part_id', 'part_variant_id', 'scope_type', 'scope_ref_id', 'is_active'], 'rb_part_price_overrides_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_part_price_overrides');
    }
};

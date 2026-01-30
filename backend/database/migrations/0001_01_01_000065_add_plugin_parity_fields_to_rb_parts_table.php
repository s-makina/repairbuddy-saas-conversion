<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->string('manufacturing_code', 255)->nullable()->after('sku');
            $table->string('stock_code', 255)->nullable()->after('manufacturing_code');

            $table->foreignId('tax_id')->nullable()->after('price_currency')->constrained('rb_taxes')->nullOnDelete();

            $table->string('warranty', 255)->nullable()->after('tax_id');
            $table->text('core_features')->nullable()->after('warranty');
            $table->string('capacity', 255)->nullable()->after('core_features');

            $table->integer('installation_charges_amount_cents')->nullable()->after('capacity');
            $table->string('installation_charges_currency', 8)->nullable()->after('installation_charges_amount_cents');
            $table->string('installation_message', 255)->nullable()->after('installation_charges_currency');

            $table->index(['tenant_id', 'branch_id', 'tax_id'], 'rb_parts_tax_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->dropIndex('rb_parts_tax_idx');

            $table->dropForeign(['tax_id']);
            $table->dropColumn('tax_id');

            $table->dropColumn('manufacturing_code');
            $table->dropColumn('stock_code');
            $table->dropColumn('warranty');
            $table->dropColumn('core_features');
            $table->dropColumn('capacity');
            $table->dropColumn('installation_charges_amount_cents');
            $table->dropColumn('installation_charges_currency');
            $table->dropColumn('installation_message');
        });
    }
};

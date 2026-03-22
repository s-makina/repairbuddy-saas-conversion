<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert price columns from cents (integer) to decimal amounts.
     */
    public function up(): void
    {
        // rb_job_items
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->decimal('unit_price_amount', 12, 2)->nullable()->after('unit_price_amount_cents');
        });
        DB::statement('UPDATE rb_job_items SET unit_price_amount = unit_price_amount_cents / 100');
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->dropColumn('unit_price_amount_cents');
        });

        // rb_estimate_items
        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->decimal('unit_price_amount', 12, 2)->nullable()->after('unit_price_amount_cents');
        });
        DB::statement('UPDATE rb_estimate_items SET unit_price_amount = unit_price_amount_cents / 100');
        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->dropColumn('unit_price_amount_cents');
        });

        // rb_services
        Schema::table('rb_services', function (Blueprint $table) {
            $table->decimal('base_price_amount', 12, 2)->nullable()->after('base_price_amount_cents');
        });
        DB::statement('UPDATE rb_services SET base_price_amount = base_price_amount_cents / 100 WHERE base_price_amount_cents IS NOT NULL');
        Schema::table('rb_services', function (Blueprint $table) {
            $table->dropColumn('base_price_amount_cents');
        });

        // rb_parts
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->decimal('price_amount', 12, 2)->nullable()->after('price_amount_cents');
            $table->decimal('installation_charges_amount', 12, 2)->nullable()->after('installation_charges_amount_cents');
        });
        DB::statement('UPDATE rb_parts SET price_amount = price_amount_cents / 100 WHERE price_amount_cents IS NOT NULL');
        DB::statement('UPDATE rb_parts SET installation_charges_amount = installation_charges_amount_cents / 100 WHERE installation_charges_amount_cents IS NOT NULL');
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->dropColumn(['price_amount_cents', 'installation_charges_amount_cents']);
        });

        // rb_part_variants
        Schema::table('rb_part_variants', function (Blueprint $table) {
            $table->decimal('price_amount', 12, 2)->nullable()->after('price_amount_cents');
            $table->decimal('installation_charges_amount', 12, 2)->nullable()->after('installation_charges_amount_cents');
        });
        DB::statement('UPDATE rb_part_variants SET price_amount = price_amount_cents / 100 WHERE price_amount_cents IS NOT NULL');
        DB::statement('UPDATE rb_part_variants SET installation_charges_amount = installation_charges_amount_cents / 100 WHERE installation_charges_amount_cents IS NOT NULL');
        Schema::table('rb_part_variants', function (Blueprint $table) {
            $table->dropColumn(['price_amount_cents', 'installation_charges_amount_cents']);
        });

        // rb_service_price_overrides
        Schema::table('rb_service_price_overrides', function (Blueprint $table) {
            $table->decimal('price_amount', 12, 2)->nullable()->after('price_amount_cents');
        });
        DB::statement('UPDATE rb_service_price_overrides SET price_amount = price_amount_cents / 100 WHERE price_amount_cents IS NOT NULL');
        Schema::table('rb_service_price_overrides', function (Blueprint $table) {
            $table->dropColumn('price_amount_cents');
        });

        // rb_part_price_overrides
        Schema::table('rb_part_price_overrides', function (Blueprint $table) {
            $table->decimal('price_amount', 12, 2)->nullable()->after('price_amount_cents');
        });
        DB::statement('UPDATE rb_part_price_overrides SET price_amount = price_amount_cents / 100 WHERE price_amount_cents IS NOT NULL');
        Schema::table('rb_part_price_overrides', function (Blueprint $table) {
            $table->dropColumn('price_amount_cents');
        });
    }

    /**
     * Reverse the migrations.
     * Convert back from decimal amounts to cents (integer).
     */
    public function down(): void
    {
        // rb_job_items
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->bigInteger('unit_price_amount_cents')->nullable()->after('unit_price_amount');
        });
        DB::statement('UPDATE rb_job_items SET unit_price_amount_cents = ROUND(unit_price_amount * 100)');
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->dropColumn('unit_price_amount');
        });

        // rb_estimate_items
        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->bigInteger('unit_price_amount_cents')->nullable()->after('unit_price_amount');
        });
        DB::statement('UPDATE rb_estimate_items SET unit_price_amount_cents = ROUND(unit_price_amount * 100)');
        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->dropColumn('unit_price_amount');
        });

        // rb_services
        Schema::table('rb_services', function (Blueprint $table) {
            $table->integer('base_price_amount_cents')->nullable()->after('base_price_amount');
        });
        DB::statement('UPDATE rb_services SET base_price_amount_cents = ROUND(base_price_amount * 100) WHERE base_price_amount IS NOT NULL');
        Schema::table('rb_services', function (Blueprint $table) {
            $table->dropColumn('base_price_amount');
        });

        // rb_parts
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->integer('price_amount_cents')->nullable()->after('price_amount');
            $table->integer('installation_charges_amount_cents')->nullable()->after('installation_charges_amount');
        });
        DB::statement('UPDATE rb_parts SET price_amount_cents = ROUND(price_amount * 100) WHERE price_amount IS NOT NULL');
        DB::statement('UPDATE rb_parts SET installation_charges_amount_cents = ROUND(installation_charges_amount * 100) WHERE installation_charges_amount IS NOT NULL');
        Schema::table('rb_parts', function (Blueprint $table) {
            $table->dropColumn(['price_amount', 'installation_charges_amount']);
        });

        // rb_part_variants
        Schema::table('rb_part_variants', function (Blueprint $table) {
            $table->integer('price_amount_cents')->nullable()->after('price_amount');
            $table->integer('installation_charges_amount_cents')->nullable()->after('installation_charges_amount');
        });
        DB::statement('UPDATE rb_part_variants SET price_amount_cents = ROUND(price_amount * 100) WHERE price_amount IS NOT NULL');
        DB::statement('UPDATE rb_part_variants SET installation_charges_amount_cents = ROUND(installation_charges_amount * 100) WHERE installation_charges_amount IS NOT NULL');
        Schema::table('rb_part_variants', function (Blueprint $table) {
            $table->dropColumn(['price_amount', 'installation_charges_amount']);
        });

        // rb_service_price_overrides
        Schema::table('rb_service_price_overrides', function (Blueprint $table) {
            $table->integer('price_amount_cents')->nullable()->after('price_amount');
        });
        DB::statement('UPDATE rb_service_price_overrides SET price_amount_cents = ROUND(price_amount * 100) WHERE price_amount IS NOT NULL');
        Schema::table('rb_service_price_overrides', function (Blueprint $table) {
            $table->dropColumn('price_amount');
        });

        // rb_part_price_overrides
        Schema::table('rb_part_price_overrides', function (Blueprint $table) {
            $table->integer('price_amount_cents')->nullable()->after('price_amount');
        });
        DB::statement('UPDATE rb_part_price_overrides SET price_amount_cents = ROUND(price_amount * 100) WHERE price_amount IS NOT NULL');
        Schema::table('rb_part_price_overrides', function (Blueprint $table) {
            $table->dropColumn('price_amount');
        });
    }
};

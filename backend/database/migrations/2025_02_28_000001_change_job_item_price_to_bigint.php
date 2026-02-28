<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->bigInteger('unit_price_amount_cents')->change();
        });

        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->bigInteger('unit_price_amount_cents')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rb_job_items', function (Blueprint $table) {
            $table->integer('unit_price_amount_cents')->change();
        });

        Schema::table('rb_estimate_items', function (Blueprint $table) {
            $table->integer('unit_price_amount_cents')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rb_payment_statuses')) {
            return;
        }

        Schema::table('rb_payment_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('rb_payment_statuses', 'description')) {
                $table->string('description', 255)->nullable()->after('label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rb_payment_statuses')) {
            return;
        }

        Schema::table('rb_payment_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('rb_payment_statuses', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (! Schema::hasColumn('rb_device_brands', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

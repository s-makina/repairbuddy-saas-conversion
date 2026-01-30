<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_devices', function (Blueprint $table) {
            $table->dropForeign(['device_type_id']);
        });

        Schema::table('rb_devices', function (Blueprint $table) {
            $table->foreign('device_type_id')
                ->references('id')
                ->on('rb_device_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rb_devices', function (Blueprint $table) {
            $table->dropForeign(['device_type_id']);
        });

        Schema::table('rb_devices', function (Blueprint $table) {
            $table->foreign('device_type_id')
                ->references('id')
                ->on('rb_device_types')
                ->cascadeOnDelete();
        });
    }
};

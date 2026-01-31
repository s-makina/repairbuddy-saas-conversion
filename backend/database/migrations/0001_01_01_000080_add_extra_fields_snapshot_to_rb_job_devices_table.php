<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->json('extra_fields_snapshot_json')->nullable()->after('notes_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->dropColumn('extra_fields_snapshot_json');
        });
    }
};

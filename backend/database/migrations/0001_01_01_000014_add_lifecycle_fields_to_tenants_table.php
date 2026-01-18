<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('contact_email');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('closed_at')->nullable()->after('suspension_reason');
            $table->string('closed_reason')->nullable()->after('closed_at');
            $table->unsignedInteger('data_retention_days')->nullable()->after('closed_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'activated_at',
                'suspended_at',
                'suspension_reason',
                'closed_at',
                'closed_reason',
                'data_retention_days',
            ]);
        });
    }
};

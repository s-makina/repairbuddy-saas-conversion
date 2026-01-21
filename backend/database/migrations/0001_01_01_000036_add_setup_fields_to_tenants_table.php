<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('timezone')->nullable()->after('contact_phone');
            $table->string('language')->nullable()->after('timezone');

            $table->string('brand_color')->nullable()->after('billing_address_json');
            $table->string('logo_path')->nullable()->after('brand_color');

            $table->timestamp('setup_completed_at')->nullable()->after('logo_path');
            $table->string('setup_step')->nullable()->after('setup_completed_at');
            $table->json('setup_state')->nullable()->after('setup_step');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'timezone',
                'language',
                'brand_color',
                'logo_path',
                'setup_completed_at',
                'setup_step',
                'setup_state',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('entitlement_overrides');
            $table->string('billing_country', 2)->nullable()->after('currency');
            $table->string('billing_vat_number')->nullable()->after('billing_country');
            $table->json('billing_address_json')->nullable()->after('billing_vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'billing_country',
                'billing_vat_number',
                'billing_address_json',
            ]);
        });
    }
};

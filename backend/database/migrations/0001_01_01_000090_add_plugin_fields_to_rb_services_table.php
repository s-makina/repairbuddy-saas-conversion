<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_services', function (Blueprint $table) {
            $table->string('service_code', 128)->nullable()->after('description');
            $table->string('time_required', 128)->nullable()->after('service_code');
            $table->string('warranty', 255)->nullable()->after('time_required');

            $table->boolean('pick_up_delivery_available')->default(false)->after('warranty');
            $table->boolean('laptop_rental_available')->default(false)->after('pick_up_delivery_available');

            $table->foreignId('tax_id')->nullable()->after('base_price_currency')->constrained('rb_taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rb_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_id');
            $table->dropColumn([
                'service_code',
                'time_required',
                'warranty',
                'pick_up_delivery_available',
                'laptop_rental_available',
            ]);
        });
    }
};

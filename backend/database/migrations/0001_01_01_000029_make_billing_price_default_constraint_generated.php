<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE billing_prices SET default_for_currency_interval = CONCAT(currency, '|', `interval`) WHERE is_default = 1");
        DB::statement("UPDATE billing_prices SET default_for_currency_interval = NULL WHERE is_default = 0");

        DB::statement("ALTER TABLE billing_prices MODIFY default_for_currency_interval VARCHAR(32) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CONCAT(currency, '|', `interval`) ELSE NULL END) STORED");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE billing_prices MODIFY default_for_currency_interval VARCHAR(32) NULL');
    }
};

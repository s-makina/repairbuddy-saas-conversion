<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_intervals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->unsignedInteger('months');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('billing_prices', function (Blueprint $table) {
            $table->foreignId('billing_interval_id')->nullable()->after('interval')->constrained('billing_intervals');
        });

        // Seed baseline intervals if missing.
        $exists = DB::table('billing_intervals')->whereIn('code', ['month', 'year'])->count();
        if ($exists === 0) {
            $now = now();
            DB::table('billing_intervals')->insert([
                ['code' => 'month', 'name' => 'Monthly', 'months' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'year', 'name' => 'Yearly', 'months' => 12, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Backfill billing_interval_id for existing prices.
        $monthId = DB::table('billing_intervals')->where('code', 'month')->value('id');
        $yearId = DB::table('billing_intervals')->where('code', 'year')->value('id');

        if ($monthId) {
            DB::table('billing_prices')->whereNull('billing_interval_id')->where('interval', 'month')->update(['billing_interval_id' => $monthId]);
        }
        if ($yearId) {
            DB::table('billing_prices')->whereNull('billing_interval_id')->where('interval', 'year')->update(['billing_interval_id' => $yearId]);
        }

        // For any other legacy interval values, create an interval row (default months=1) and link.
        $unknowns = DB::table('billing_prices')
            ->select('interval')
            ->whereNull('billing_interval_id')
            ->distinct()
            ->pluck('interval')
            ->map(fn ($v) => strtolower((string) $v))
            ->filter(fn ($v) => $v !== '' && $v !== 'month' && $v !== 'year')
            ->values();

        foreach ($unknowns as $code) {
            $row = DB::table('billing_intervals')->where('code', $code)->first();
            if (! $row) {
                $now = now();
                $id = DB::table('billing_intervals')->insertGetId([
                    'code' => $code,
                    'name' => $code,
                    'months' => 1,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $id = (int) $row->id;
            }

            DB::table('billing_prices')->whereNull('billing_interval_id')->where('interval', $code)->update(['billing_interval_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('billing_prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_interval_id');
        });

        Schema::dropIfExists('billing_intervals');
    }
};

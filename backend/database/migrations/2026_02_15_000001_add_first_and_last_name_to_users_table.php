<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        DB::table('users')
            ->whereNull('first_name')
            ->whereNull('last_name')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $name = is_string($row->name ?? null) ? trim((string) $row->name) : '';

                    if ($name === '') {
                        continue;
                    }

                    $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
                    if (! is_array($parts) || count($parts) === 0) {
                        continue;
                    }

                    $first = (string) array_shift($parts);
                    $last = count($parts) > 0 ? implode(' ', $parts) : null;

                    DB::table('users')
                        ->where('id', $row->id)
                        ->update([
                            'first_name' => $first,
                            'last_name' => $last,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};

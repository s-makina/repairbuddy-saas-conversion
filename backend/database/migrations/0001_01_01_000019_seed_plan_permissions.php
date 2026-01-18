<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (['admin.plans.read', 'admin.plans.write'] as $name) {
                Permission::query()->firstOrCreate([
                    'name' => $name,
                ]);
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};

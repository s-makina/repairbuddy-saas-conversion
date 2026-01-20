<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'admin.billing.read',
            'admin.billing.write',
        ] as $name) {
            Permission::query()->firstOrCreate(['name' => $name]);
        }
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', [
            'admin.billing.read',
            'admin.billing.write',
        ])->delete();
    }
};

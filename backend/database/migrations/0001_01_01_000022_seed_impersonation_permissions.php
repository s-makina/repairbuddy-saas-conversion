<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'admin.impersonation.start',
            'admin.impersonation.stop',
            'admin.diagnostics.read',
        ] as $name) {
            Permission::query()->firstOrCreate(['name' => $name]);
        }
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', [
            'admin.impersonation.start',
            'admin.impersonation.stop',
            'admin.diagnostics.read',
        ])->delete();
    }
};

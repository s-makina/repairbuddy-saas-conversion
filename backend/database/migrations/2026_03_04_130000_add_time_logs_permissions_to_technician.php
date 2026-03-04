<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure permissions exist
        $viewPerm = Permission::query()->firstOrCreate(['name' => 'time_logs.view']);
        $managePerm = Permission::query()->firstOrCreate(['name' => 'time_logs.manage']);

        // Add to all Technician roles
        Role::query()
            ->where('name', 'Technician')
            ->each(function ($role) use ($viewPerm, $managePerm) {
                $role->permissions()->syncWithoutDetaching([$viewPerm->id, $managePerm->id]);
            });
    }

    public function down(): void
    {
        // Remove from Technician roles
        $viewPerm = Permission::query()->where('name', 'time_logs.view')->first();
        $managePerm = Permission::query()->where('name', 'time_logs.manage')->first();

        if ($viewPerm && $managePerm) {
            Role::query()
                ->where('name', 'Technician')
                ->each(function ($role) use ($viewPerm, $managePerm) {
                    $role->permissions()->detach([$viewPerm->id, $managePerm->id]);
                });
        }
    }
};

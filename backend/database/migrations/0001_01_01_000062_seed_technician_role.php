<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (Permissions::all() as $permName) {
                Permission::query()->firstOrCreate([
                    'name' => $permName,
                ]);
            }

            $technicianPermissions = [
                'app.access',
                'dashboard.view',
                'jobs.view',
                'appointments.view',
                'estimates.view',
                'clients.view',
                'customer_devices.view',
                'profile.manage',
                'security.manage',
            ];

            $permissionIdsByName = Permission::query()->pluck('id', 'name')->all();

            foreach (Tenant::query()->get() as $tenant) {
                $technicianRole = Role::query()->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'name' => 'Technician',
                ]);

                $technicianRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                    return $permissionIdsByName[$name] ?? null;
                }, $technicianPermissions))));
            }
        });
    }

    public function down(): void
    {

    }
};

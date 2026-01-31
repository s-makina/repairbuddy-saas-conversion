<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $perm = Permission::query()->firstOrCreate([
                'name' => 'devices.manage',
            ]);

            $tenants = Tenant::query()->pluck('id');

            foreach ($tenants as $tenantId) {
                $ownerRole = Role::query()->where('tenant_id', $tenantId)->where('name', 'Owner')->first();
                if (! $ownerRole) {
                    continue;
                }

                $permissionIds = $ownerRole->permissions()->pluck('permissions.id')->all();

                if (! in_array($perm->id, $permissionIds, true)) {
                    $permissionIds[] = $perm->id;
                    $ownerRole->permissions()->sync($permissionIds);
                }
            }
        });
    }

    public function down(): void
    {
        Permission::query()->where('name', 'devices.manage')->delete();
    }
};

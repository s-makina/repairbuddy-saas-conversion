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
                'name' => 'estimates.manage',
            ]);

            $tenants = Tenant::query()->pluck('id');

            foreach ($tenants as $tenantId) {
                $roles = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('name', ['Owner', 'Member', 'Technician'])
                    ->get();

                foreach ($roles as $role) {
                    $permissionIds = $role->permissions()->pluck('permissions.id')->all();

                    if (! in_array($perm->id, $permissionIds, true)) {
                        $permissionIds[] = $perm->id;
                        $role->permissions()->sync($permissionIds);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Permission::query()->where('name', 'estimates.manage')->delete();
    }
};

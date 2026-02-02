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
            $permServicesManage = Permission::query()->firstOrCreate([
                'name' => 'services.manage',
            ]);

            $permServiceTypesManage = Permission::query()->firstOrCreate([
                'name' => 'service_types.manage',
            ]);

            $permServiceTypesView = Permission::query()->firstOrCreate([
                'name' => 'service_types.view',
            ]);

            $tenants = Tenant::query()->pluck('id');

            foreach ($tenants as $tenantId) {
                $ownerRoles = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->whereRaw('LOWER(name) = ?', ['owner'])
                    ->get();

                foreach ($ownerRoles as $ownerRole) {
                    $permissionIds = $ownerRole->permissions()->pluck('permissions.id')->all();

                    foreach ([$permServicesManage->id, $permServiceTypesManage->id, $permServiceTypesView->id] as $permId) {
                        if (! in_array($permId, $permissionIds, true)) {
                            $permissionIds[] = $permId;
                        }
                    }

                    $ownerRole->permissions()->sync($permissionIds);
                }
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};

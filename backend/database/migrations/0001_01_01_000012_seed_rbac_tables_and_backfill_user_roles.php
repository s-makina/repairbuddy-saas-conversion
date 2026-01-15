<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
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

            $ownerPermissions = [
                'app.access',
                'dashboard.view',
                'appointments.view',
                'jobs.view',
                'estimates.view',
                'services.view',
                'devices.view',
                'device_brands.view',
                'device_types.view',
                'parts.view',
                'payments.view',
                'reports.view',
                'expenses.view',
                'expense_categories.view',
                'clients.view',
                'customer_devices.view',
                'technicians.view',
                'managers.view',
                'job_reviews.view',
                'time_logs.view',
                'hourly_rates.view',
                'reminder_logs.view',
                'print_screen.view',
                'security.manage',
                'profile.manage',
                'settings.manage',
                'users.manage',
                'roles.manage',
            ];

            $memberPermissions = [
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
                $ownerRole = Role::query()->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'name' => 'Owner',
                ]);

                $memberRole = Role::query()->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'name' => 'Member',
                ]);

                $ownerRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                    return $permissionIdsByName[$name] ?? null;
                }, $ownerPermissions))));

                $memberRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                    return $permissionIdsByName[$name] ?? null;
                }, $memberPermissions))));

                $users = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('is_admin', false)
                    ->whereNull('role_id')
                    ->get();

                foreach ($users as $user) {
                    $legacy = (string) ($user->role ?? '');
                    $user->forceFill([
                        'role_id' => $legacy === 'owner' ? $ownerRole->id : $memberRole->id,
                    ])->save();
                }
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};

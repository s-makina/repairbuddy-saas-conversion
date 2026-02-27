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
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permName],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            $ownerPermissions = [
                'app.access',
                'dashboard.view',
                'appointments.view',
                'jobs.view',
                'estimates.view',
                'estimates.manage',
                'services.view',
                'services.manage',
                'service_types.view',
                'service_types.manage',
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
                'estimates.manage',
                'clients.view',
                'customer_devices.view',
                'profile.manage',
                'security.manage',
            ];

            $permissionIdsByName = DB::table('permissions')->pluck('id', 'name')->all();

            foreach (DB::table('tenants')->get() as $tenant) {
                // Get or create roles
                $ownerRoleId = DB::table('roles')->where('tenant_id', $tenant->id)->where('name', 'Owner')->value('id');
                if (!$ownerRoleId) {
                    $ownerRoleId = DB::table('roles')->insertGetId([
                        'tenant_id' => $tenant->id,
                        'name' => 'Owner',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $memberRoleId = DB::table('roles')->where('tenant_id', $tenant->id)->where('name', 'Member')->value('id');
                if (!$memberRoleId) {
                    $memberRoleId = DB::table('roles')->insertGetId([
                        'tenant_id' => $tenant->id,
                        'name' => 'Member',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Technician role
                if (!DB::table('roles')->where('tenant_id', $tenant->id)->where('name', 'Technician')->exists()) {
                    DB::table('roles')->insert([
                        'tenant_id' => $tenant->id,
                        'name' => 'Technician',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Sync permissions manually for legacy table
                // Assuming pivot table is 'role_permissions'
                $ownerPermIds = array_values(array_filter(array_map(fn($name) => $permissionIdsByName[$name] ?? null, $ownerPermissions)));
                foreach ($ownerPermIds as $pId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $ownerRoleId, 'permission_id' => $pId]
                    );
                }

                $memberPermIds = array_values(array_filter(array_map(fn($name) => $permissionIdsByName[$name] ?? null, $memberPermissions)));
                foreach ($memberPermIds as $pId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $memberRoleId, 'permission_id' => $pId]
                    );
                }

                $users = DB::table('users')
                    ->where('tenant_id', $tenant->id)
                    ->where('is_admin', false)
                    ->whereNull('role_id')
                    ->get();

                foreach ($users as $user) {
                    $legacy = (string) ($user->role ?? '');
                    DB::table('users')->where('id', $user->id)->update([
                        'role_id' => $legacy === 'owner' ? $ownerRoleId : $memberRoleId,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};

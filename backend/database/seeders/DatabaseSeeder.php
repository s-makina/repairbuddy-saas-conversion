<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdminEmail = (string) env('SUPERADMIN_EMAIL', 'superadmin@99smartx.com');
        $superAdminPassword = (string) env('SUPERADMIN_PASSWORD', 'Password123!@#Aa');
        $superAdminRole = (string) env('SUPERADMIN_ROLE', 'platform_admin');

        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Tenant',
            'status' => 'active',
            'contact_email' => 'demo-owner@99smartx.com',
        ]);

        $tenant->forceFill([
            'name' => $tenant->name ?: 'Demo Tenant',
            'status' => $tenant->status ?: 'active',
            'contact_email' => $tenant->contact_email ?: 'demo-owner@99smartx.com',
        ])->save();

        if (Schema::hasColumn('tenants', 'currency')) {
            $tenant->forceFill([
                'currency' => $tenant->currency ?: 'USD',
                'billing_country' => $tenant->billing_country ?: 'EG',
                'billing_vat_number' => $tenant->billing_vat_number,
                'billing_address_json' => $tenant->billing_address_json ?: [
                    'line1' => 'Demo Address',
                    'city' => 'Cairo',
                    'country' => 'EG',
                ],
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'activated_at') && ! $tenant->activated_at) {
            $tenant->forceFill([
                'activated_at' => now(),
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'contact_phone') && ! $tenant->contact_phone) {
            $tenant->forceFill([
                'contact_phone' => '+20 100 000 0000',
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'timezone') && ! $tenant->timezone) {
            $tenant->forceFill([
                'timezone' => 'Africa/Cairo',
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'language') && ! $tenant->language) {
            $tenant->forceFill([
                'language' => 'en',
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'brand_color') && ! $tenant->brand_color) {
            $tenant->forceFill([
                'brand_color' => '#063e70',
            ])->save();
        }

        if (Schema::hasColumn('tenants', 'setup_completed_at') && ! $tenant->setup_completed_at) {
            $tenant->forceFill([
                'setup_completed_at' => now(),
                'setup_step' => null,
                'setup_state' => $tenant->setup_state ?: [
                    'seeded' => true,
                    'source' => 'DatabaseSeeder',
                ],
            ])->save();
        }

        $demoBranchId = null;
        if (Schema::hasTable('branches')) {
            $demoBranchId = DB::table('branches')
                ->where('tenant_id', $tenant->id)
                ->where('code', 'MAIN')
                ->value('id');

            if (! $demoBranchId) {
                $demoBranchId = DB::table('branches')->insertGetId([
                    'tenant_id' => $tenant->id,
                    'name' => 'Main Branch',
                    'code' => 'MAIN',
                    'phone' => '+20 100 000 0000',
                    'email' => 'demo@99smartx.com',
                    'address_line1' => 'Demo Address',
                    'address_city' => 'Cairo',
                    'address_country' => 'EG',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasColumn('tenants', 'default_branch_id') && $demoBranchId) {
            if ((int) ($tenant->default_branch_id ?? 0) !== (int) $demoBranchId) {
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'default_branch_id' => $demoBranchId,
                    'updated_at' => now(),
                ]);
                $tenant = $tenant->fresh();
            }
        }

        User::query()->updateOrCreate([
            'email' => $superAdminEmail,
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make($superAdminPassword),
            'is_admin' => true,
            'admin_role' => $superAdminRole,
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'sarah@99smartx.com',
        ], [
            'name' => 'Demo Owner',
            'password' => Hash::make('Password123!@#Aa'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'tech1@99smartx.com',
        ], [
            'name' => 'Demo Technician 1',
            'password' => Hash::make('Password123!@#Aa'),
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'tech2@99smartx.com',
        ], [
            'name' => 'Demo Technician 2',
            'password' => Hash::make('Password123!@#Aa'),
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        if (Schema::hasTable('roles')) {
            Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
                'guard_name' => 'web',
            ]);

            Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Member',
                'guard_name' => 'web',
            ]);

            Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Technician',
                'guard_name' => 'web',
            ]);

            Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Manager',
                'guard_name' => 'web',
            ]);
        }

        $ownerRoleId = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Owner')
            ->value('id');

        if ($ownerRoleId) {
            User::query()->where('email', 'sarah@99smartx.com')->update([
                'role_id' => $ownerRoleId,
                'role' => null,
            ]);
        }

        $memberRoleId = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Member')
            ->value('id');

        if ($memberRoleId) {
            User::query()->whereIn('email', ['tech1@99smartx.com', 'tech2@99smartx.com'])->update([
                'role_id' => $memberRoleId,
                'role' => null,
            ]);
        }

        $technicianRoleId = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Technician')
            ->value('id');

        if ($technicianRoleId) {
            $technicianRole = Role::query()->whereKey((int) $technicianRoleId)->first();
            $demoTechs = User::query()->whereIn('email', ['tech1@99smartx.com', 'tech2@99smartx.com'])->get();
            if ($technicianRole) {
                foreach ($demoTechs as $demoTech) {
                    $demoTech->syncRoles([$technicianRole]);
                }
            }
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->id);

            foreach (Permissions::all() as $permissionName) {
                DB::table('permissions')->updateOrInsert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ], [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $ownerRoleId = Role::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', 'Owner')
                ->value('id');

            if ($ownerRoleId) {
                $ownerRole = Role::query()->whereKey((int) $ownerRoleId)->first();

                $baseline = [
                    'settings.manage',
                    'users.manage',
                    'roles.manage',
                    'branches.manage',
                ];

                if ($ownerRole) {
                    $ownerRole->givePermissionTo($baseline);
                }

                $permissionIds = DB::table('permissions')
                    ->where('guard_name', 'web')
                    ->whereIn('name', $baseline)
                    ->pluck('id')
                    ->all();

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'permission_id' => (int) $permissionId,
                        'role_id' => (int) $ownerRoleId,
                    ], []);
                }

                $demoOwner = User::query()->where('email', 'sarah@99smartx.com')->first();
                if ($demoOwner && $ownerRole) {
                    $demoOwner->syncRoles([$ownerRole]);
                }

                // Clear permission cache to ensure new grants take effect immediately.
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        if ($demoBranchId && Schema::hasTable('branch_user')) {
            $demoUserIds = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_admin', false)
                ->pluck('id');

            foreach ($demoUserIds as $userId) {
                DB::table('branch_user')->updateOrInsert([
                    'branch_id' => $demoBranchId,
                    'user_id' => $userId,
                ], [
                    'tenant_id' => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $seedCatalog = filter_var(env('SEED_REPAIRBUDDY_CATALOG', true), FILTER_VALIDATE_BOOL);
        if ($seedCatalog) {
            $this->call(RepairBuddyCatalogSeeder::class);
        }

        if (! Schema::hasTable('billing_plans')
            || ! Schema::hasTable('billing_plan_versions')
            || ! Schema::hasTable('billing_prices')
            || ! Schema::hasTable('entitlement_definitions')
            || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        DB::table('entitlement_definitions')->updateOrInsert([
            'code' => 'max_users',
        ], [
            'name' => 'Maximum users',
            'value_type' => 'integer',
            'description' => 'Maximum number of active users allowed for this tenant.',
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        foreach (['feature_customer_portal', 'feature_invoices', 'feature_sms', 'feature_inventory'] as $flag) {
            DB::table('entitlement_definitions')->updateOrInsert([
                'code' => $flag,
            ], [
                'name' => $flag,
                'value_type' => 'boolean',
                'description' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $planId = DB::table('billing_plans')->where('code', 'starter')->value('id');

        if (! $planId) {
            $planId = DB::table('billing_plans')->insertGetId([
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Starter plan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('billing_plans')->where('id', $planId)->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        $versionId = DB::table('billing_plan_versions')
            ->where('billing_plan_id', $planId)
            ->where('version', 1)
            ->value('id');

        if (! $versionId) {
            $versionId = DB::table('billing_plan_versions')->insertGetId([
                'billing_plan_id' => $planId,
                'version' => 1,
                'status' => 'active',
                'locked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('billing_plan_versions')->where('id', $versionId)->update([
                'status' => 'active',
                'locked_at' => DB::raw('COALESCE(locked_at, NOW())'),
                'activated_at' => DB::raw('COALESCE(activated_at, NOW())'),
                'updated_at' => now(),
            ]);
        }

        DB::table('billing_prices')->updateOrInsert([
            'billing_plan_version_id' => $versionId,
            'currency' => 'USD',
            'interval' => 'month',
            'is_default' => 1,
        ], [
            'amount_cents' => 4900,
            'trial_days' => 14,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        if (Schema::hasTable('tenant_subscriptions')) {
            $starterPriceId = DB::table('billing_prices')
                ->where('billing_plan_version_id', $versionId)
                ->where('currency', 'USD')
                ->where('interval', 'month')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');

            $existingSubId = DB::table('tenant_subscriptions')
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['trial', 'active', 'past_due'])
                ->orderByDesc('id')
                ->value('id');

            if (! $existingSubId && $starterPriceId) {
                $now = now();
                DB::table('tenant_subscriptions')->insert([
                    'tenant_id' => $tenant->id,
                    'billing_plan_version_id' => $versionId,
                    'billing_price_id' => $starterPriceId,
                    'currency' => 'USD',
                    'status' => 'active',
                    'started_at' => $now,
                    'current_period_start' => $now,
                    'current_period_end' => $now->copy()->addMonth(),
                    'cancel_at_period_end' => 0,
                    'canceled_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $entitlementIds = DB::table('entitlement_definitions')
            ->whereIn('code', ['max_users', 'feature_customer_portal', 'feature_invoices', 'feature_sms', 'feature_inventory'])
            ->pluck('id', 'code');

        DB::table('plan_entitlements')->updateOrInsert([
            'billing_plan_version_id' => $versionId,
            'entitlement_definition_id' => $entitlementIds['max_users'],
        ], [
            'value_json' => json_encode(5),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        foreach (['feature_customer_portal', 'feature_invoices'] as $flag) {
            DB::table('plan_entitlements')->updateOrInsert([
                'billing_plan_version_id' => $versionId,
                'entitlement_definition_id' => $entitlementIds[$flag],
            ], [
                'value_json' => json_encode(true),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach (['feature_sms', 'feature_inventory'] as $flag) {
            DB::table('plan_entitlements')->updateOrInsert([
                'billing_plan_version_id' => $versionId,
                'entitlement_definition_id' => $entitlementIds[$flag],
            ], [
                'value_json' => json_encode(false),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}

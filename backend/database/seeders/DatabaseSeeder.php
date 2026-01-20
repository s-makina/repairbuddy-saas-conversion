<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdminEmail = (string) env('SUPERADMIN_EMAIL', 'superadmin@repairbuddy.com');
        $superAdminPassword = (string) env('SUPERADMIN_PASSWORD', 'Password123!@#Aa');
        $superAdminRole = (string) env('SUPERADMIN_ROLE', 'platform_admin');

        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Tenant',
            'status' => 'active',
            'contact_email' => 'demo-owner@repairbuddy.com',
        ]);

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
            'email' => 'sarah@repairbuddy.com',
        ], [
            'name' => 'Demo Owner',
            'password' => Hash::make('Password123!@#Aa'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $ownerRoleId = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Owner')
            ->value('id');

        if ($ownerRoleId) {
            User::query()->where('email', 'sarah@repairbuddy.com')->update([
                'role_id' => $ownerRoleId,
                'role' => null,
            ]);
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

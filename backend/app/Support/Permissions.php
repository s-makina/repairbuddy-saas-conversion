<?php

namespace App\Support;

use App\Models\User;

class Permissions
{
    public static function all(): array
    {
        return [
            'admin.access',
            'admin.tenants.read',
            'admin.tenants.write',
            'admin.plans.read',
            'admin.plans.write',
            'admin.billing.read',
            'admin.billing.write',
            'admin.impersonation.start',
            'admin.impersonation.stop',
            'admin.diagnostics.read',

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
            'devices.manage',
            'device_brands.view',
            'device_brands.manage',
            'device_types.view',
            'device_types.manage',
            'parts.view',
            'parts.manage',

            'payments.view',
            'reports.view',
            'expenses.view',
            'expense_categories.view',

            'clients.view',
            'customer_devices.view',
            'customer_devices.manage',
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
            'branches.manage',
        ];
    }

    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $known = array_fill_keys(self::all(), true);

        if ($user->is_admin) {
            $adminRole = (string) ($user->admin_role ?? '');
            if ($adminRole === '') {
                $adminRole = 'platform_admin';
            }

            $adminMap = [
                'platform_admin' => self::all(),
                'support_agent' => [
                    'admin.access',
                    'admin.tenants.read',
                    'admin.plans.read',
                    'admin.impersonation.start',
                    'admin.impersonation.stop',
                    'admin.diagnostics.read',
                ],
                'billing_admin' => [
                    'admin.access',
                    'admin.plans.read',
                    'admin.plans.write',
                    'admin.billing.read',
                    'admin.billing.write',
                ],
                'read_only_auditor' => [
                    'admin.access',
                    'admin.tenants.read',
                    'admin.plans.read',
                    'admin.diagnostics.read',
                ],
            ];

            $permissions = $adminMap[$adminRole] ?? ['admin.access'];

            return array_values(array_unique(array_values(array_filter($permissions, function (string $p) use ($known) {
                return isset($known[$p]);
            }))));
        }

        if ($user->role_id) {
            $role = $user->roleModel;

            if ($role && (int) $role->tenant_id === (int) $user->tenant_id) {
                $permissions = $role->permissions()->pluck('name')->all();

                return array_values(array_unique(array_values(array_filter($permissions, function (string $p) use ($known) {
                    return isset($known[$p]);
                }))));
            }
        }

        $role = (string) ($user->role ?? '');

        $map = [
            'owner' => [
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
                'devices.manage',
                'device_brands.view',
                'device_brands.manage',
                'device_types.view',
                'device_types.manage',
                'parts.view',
                'parts.manage',
                'payments.view',
                'reports.view',
                'expenses.view',
                'expense_categories.view',
                'clients.view',
                'customer_devices.view',
                'customer_devices.manage',
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
                'branches.manage',
            ],
            'member' => [
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
            ],
        ];

        $permissions = $map[$role] ?? ['app.access', 'dashboard.view', 'profile.manage'];

        return array_values(array_unique(array_values(array_filter($permissions, function (string $p) use ($known) {
            return isset($known[$p]);
        }))));
    }

    public static function userHas(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($permission, self::forUser($user), true);
    }
}

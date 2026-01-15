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
    }

    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->is_admin) {
            return self::all();
        }

        $known = array_fill_keys(self::all(), true);

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
            ],
            'member' => [
                'app.access',
                'dashboard.view',
                'jobs.view',
                'appointments.view',
                'estimates.view',
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

        if ($user->is_admin) {
            return true;
        }

        return in_array($permission, self::forUser($user), true);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        return response()->json([
            'tenants' => Tenant::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'unique:tenants,slug'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

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

        [$tenant, $owner] = DB::transaction(function () use ($validated, $ownerPermissions, $memberPermissions) {
            $slug = $validated['slug'] ?? Str::slug($validated['name']);
            $slug = $slug ?: 'tenant';

            $baseSlug = $slug;
            $i = 1;

            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$i;
                $i++;
            }

            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'status' => 'active',
                'contact_email' => $validated['contact_email'] ?? $validated['owner_email'],
            ]);

            foreach (Permissions::all() as $permName) {
                Permission::query()->firstOrCreate([
                    'name' => $permName,
                ]);
            }

            $ownerRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
            ]);

            $memberRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Member',
            ]);

            $permissionIdsByName = Permission::query()->pluck('id', 'name')->all();

            $ownerRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $ownerPermissions))));

            $memberRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $memberPermissions))));

            $owner = User::query()->create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_admin' => false,
            ]);

            return [$tenant, $owner];
        });

        return response()->json([
            'tenant' => $tenant,
            'owner' => $owner,
        ], 201);
    }
}

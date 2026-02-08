<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $tenantId = TenantContext::tenantId();

        return response()->json([
            'roles' => Role::query()
                ->where('tenant_id', $tenantId)
                ->with(['permissions'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = TenantContext::tenantId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'permission_names' => ['array'],
            'permission_names.*' => ['string'],
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
        ]);

        $permNames = $validated['permission_names'] ?? [];
        $permissionIds = Permission::query()->whereIn('name', $permNames)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        return response()->json([
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function update(Request $request, string $tenant, $role)
    {
        $tenantId = TenantContext::tenantId();

        if (!($role instanceof Role)) {
            $roleKey = (string) $role;

            if (ctype_digit($roleKey)) {
                $role = Role::query()->find((int) $roleKey);
            } else {
                $role = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->where('name', $roleKey)
                    ->first();
            }
        }

        if (!$role) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        if ((int) $role->tenant_id !== (int) $tenantId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($role->id),
            ],
            'permission_names' => ['array'],
            'permission_names.*' => ['string'],
        ]);

        $role->forceFill([
            'name' => $validated['name'],
        ])->save();

        $permNames = $validated['permission_names'] ?? [];
        $permissionIds = Permission::query()->whereIn('name', $permNames)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        return response()->json([
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(string $tenant, $role)
    {
        $tenantId = TenantContext::tenantId();

        if (!($role instanceof Role)) {
            $roleKey = (string) $role;

            if (ctype_digit($roleKey)) {
                $role = Role::query()->find((int) $roleKey);
            } else {
                $role = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->where('name', $roleKey)
                    ->first();
            }
        }

        if (!$role) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        if ((int) $role->tenant_id !== (int) $tenantId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        if (Role::query()->where('tenant_id', $tenantId)->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last role.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'status' => 'ok',
        ]);
    }
}

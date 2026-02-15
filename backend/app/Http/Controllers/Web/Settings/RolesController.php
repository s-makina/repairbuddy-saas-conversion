<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class RolesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.settings.roles.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Roles'),
        ]);
    }

    public function permissionsIndex(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        return response()->json([
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->where('name', 'not like', 'admin.%')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Permission $p) => ['id' => (int) $p->id, 'name' => (string) $p->name])
                ->values()
                ->all(),
        ]);
    }

    public function rolePermissionsShow(Request $request, string $business, int $role)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $roleModel = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($role)
            ->firstOrFail();

        return response()->json([
            'role_id' => (int) $roleModel->id,
            'permission_ids' => $roleModel->permissions()
                ->pluck('permissions.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ]);
    }

    public function rolePermissionsSync(Request $request, string $business, int $role)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $roleModel = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($role)
            ->firstOrFail();

        $validated = $request->validate([
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);

        $permissionIds = array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            $validated['permission_ids'] ?? []
        ))));

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        $roleModel->syncPermissions($permissions);

        return response()->json([
            'status' => 'ok',
            'role_id' => (int) $roleModel->id,
            'permission_ids' => $roleModel->permissions()
                ->pluck('permissions.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('permissions_count', function (Role $role) {
                return (int) $role->permissions()->count();
            })
            ->addColumn('users_count', function (Role $role) use ($tenant) {
                return (int) User::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('is_admin', false)
                    ->whereHas('roles', fn ($q) => $q->whereKey($role->id))
                    ->count();
            })
            ->addColumn('delete_action', function (Role $role) use ($tenant) {
                $deleteUrl = route('tenant.settings.roles.delete', ['business' => $tenant->slug, 'role' => $role->id]);
                $csrf = csrf_field();

                return '<form method="post" action="' . e($deleteUrl) . '" onsubmit="return confirm(\'' . e(__('Delete this role?')) . '\')">'
                    . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '"><i class="bi bi-trash"></i></button>'
                    . '</form>';
            })
            ->rawColumns(['delete_action'])
            ->toJson();
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $p) => ['id' => (int) $p->id, 'name' => (string) $p->name])
            ->values()
            ->all();

        return view('tenant.settings.roles.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Add Role'),
            'permissions' => $permissions,
        ]);
    }

    public function edit(Request $request, string $business, int $role)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $roleModel = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($role)
            ->firstOrFail();

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $p) => ['id' => (int) $p->id, 'name' => (string) $p->name])
            ->values()
            ->all();

        $selectedPermissionIds = $roleModel->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();

        return view('tenant.settings.roles.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Edit Role'),
            'role' => $roleModel,
            'permissions' => $permissions,
            'selectedPermissionIds' => $selectedPermissionIds,
        ]);
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);

        $permissionIds = array_values(array_unique(array_filter(array_map(fn ($v) => is_numeric($v) ? (int) $v : null, $validated['permission_ids'] ?? []))));
        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        $role->syncPermissions($permissions);

        return redirect()
            ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
            ->with('status', __('Role added.'));
    }

    public function update(Request $request, string $business, int $role): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $roleModel = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($role)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('tenant_id', $tenant->id))->ignore($roleModel->id),
            ],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);

        $roleModel->forceFill([
            'name' => trim((string) $validated['name']),
        ])->save();

        $permissionIds = array_values(array_unique(array_filter(array_map(fn ($v) => is_numeric($v) ? (int) $v : null, $validated['permission_ids'] ?? []))));
        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        $roleModel->syncPermissions($permissions);

        return redirect()
            ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
            ->with('status', __('Role updated.'));
    }

    public function delete(Request $request, string $business, int $role): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $roleModel = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($role)
            ->firstOrFail();

        $protectedNames = ['Owner', 'Technician'];
        if (in_array((string) $roleModel->name, $protectedNames, true)) {
            return redirect()
                ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
                ->with('status', __('Cannot delete this role.'));
        }

        if (Role::query()->where('tenant_id', $tenant->id)->count() <= 1) {
            return redirect()
                ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
                ->with('status', __('Cannot delete the last role.'));
        }

        if (User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereHas('roles', fn ($q) => $q->whereKey($roleModel->id))
            ->exists()
        ) {
            return redirect()
                ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
                ->with('status', __('Cannot delete a role that is assigned to users.'));
        }

        $roleModel->syncPermissions([]);
        $roleModel->delete();

        return redirect()
            ->route('tenant.settings.roles.index', ['business' => $tenant->slug])
            ->with('status', __('Role deleted.'));
    }
}

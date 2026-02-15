<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Yajra\DataTables\Facades\DataTables;

class UsersController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.settings.users.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Users'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('roles_display', function (User $u) {
                $roles = $u->getRoleNames()->map(fn ($n) => (string) $n)->filter(fn ($n) => trim($n) !== '')->values()->all();
                return e(implode(', ', $roles));
            })
            ->addColumn('status_display', function (User $u) {
                $status = (string) ($u->status ?? 'active');
                if ($status === 'active') {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (User $u) use ($tenant) {
                $editUrl = route('tenant.settings.users.edit', ['business' => $tenant->slug, 'user' => $u->id]);
                $deleteUrl = route('tenant.settings.users.delete', ['business' => $tenant->slug, 'user' => $u->id]);
                $csrf = csrf_field();

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '"><i class="bi bi-trash"></i></button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['status_display', 'actions_display'])
            ->toJson();
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $roleOptions = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $r) => [(string) $r->id => (string) $r->name])
            ->prepend((string) __('Select role'), '')
            ->all();

        $statusOptions = [
            'active' => __('Active'),
            'inactive' => __('Inactive'),
        ];

        return view('tenant.settings.users.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Add User'),
            'roleOptions' => $roleOptions,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function edit(Request $request, string $business, int $user)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $editUser = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereKey($user)
            ->firstOrFail();

        $roleOptions = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $r) => [(string) $r->id => (string) $r->name])
            ->prepend((string) __('Select role'), '')
            ->all();

        $statusOptions = [
            'active' => __('Active'),
            'inactive' => __('Inactive'),
        ];

        $currentRoleId = $editUser->roles()->value('roles.id');
        $currentRoleId = is_numeric($currentRoleId) ? (int) $currentRoleId : null;

        return view('tenant.settings.users.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Edit User'),
            'editUser' => $editUser,
            'roleOptions' => $roleOptions,
            'statusOptions' => $statusOptions,
            'currentRoleId' => $currentRoleId,
        ]);
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id))],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = is_string($validated['status'] ?? null) ? (string) $validated['status'] : 'active';

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'is_admin' => false,
            'status' => $status,
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => Hash::make((string) $validated['password']),
        ]);

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $validated['role_id'])
            ->firstOrFail();

        $user->syncRoles([$role]);

        return redirect()
            ->route('tenant.settings.users.index', ['business' => $tenant->slug])
            ->with('status', __('User added.'));
    }

    public function update(Request $request, string $business, int $user): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $editUser = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereKey($user)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editUser->id)],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id))],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)],
        ]);

        $status = is_string($validated['status'] ?? null) ? (string) $validated['status'] : 'active';

        $editUser->forceFill([
            'status' => $status,
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
        ]);

        if (is_string($validated['password'] ?? null) && trim((string) $validated['password']) !== '') {
            $editUser->forceFill([
                'password' => Hash::make((string) $validated['password']),
            ]);
        }

        $editUser->save();

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $validated['role_id'])
            ->firstOrFail();

        $editUser->syncRoles([$role]);

        return redirect()
            ->route('tenant.settings.users.index', ['business' => $tenant->slug])
            ->with('status', __('User updated.'));
    }

    public function delete(Request $request, string $business, int $user): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $editUser = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereKey($user)
            ->firstOrFail();

        if ((int) $request->user()?->id === (int) $editUser->id) {
            return redirect()
                ->route('tenant.settings.users.index', ['business' => $tenant->slug])
                ->with('status', __('You cannot delete your own user.'));
        }

        $editUser->syncRoles([]);
        $editUser->delete();

        return redirect()
            ->route('tenant.settings.users.index', ['business' => $tenant->slug])
            ->with('status', __('User deleted.'));
    }
}

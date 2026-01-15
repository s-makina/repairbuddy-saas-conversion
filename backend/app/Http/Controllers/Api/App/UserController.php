<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index()
    {
        $tenantId = TenantContext::tenantId();

        return response()->json([
            'users' => User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_admin', false)
                ->with(['roleModel'])
                ->orderBy('id', 'desc')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = TenantContext::tenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()->where('tenant_id', $tenantId)->where('id', $validated['role_id'])->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role is invalid.',
            ], 422);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'role' => null,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'user' => $user->load('roleModel'),
        ], 201);
    }

    public function updateRole(Request $request, User $user)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $user->tenant_id !== (int) $tenantId || $user->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()->where('tenant_id', $tenantId)->where('id', $validated['role_id'])->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role is invalid.',
            ], 422);
        }

        $user->forceFill([
            'role_id' => $role->id,
            'role' => null,
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }
}

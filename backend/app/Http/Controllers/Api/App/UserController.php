<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = TenantContext::tenantId();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $role = is_string($validated['role'] ?? null) ? $validated['role'] : null;
        $status = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->with(['roleModel'])
            ->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (is_string($role) && $role !== '' && $role !== 'all') {
            if ($role === 'none') {
                $query->whereNull('role_id');
            } elseif (ctype_digit($role)) {
                $query->where('role_id', (int) $role);
            }
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            if (in_array($status, ['pending', 'active', 'inactive', 'suspended'], true)) {
                $query->where('status', $status);
            }
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'users' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
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
            'status' => 'active',
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

    public function update(Request $request, User $user)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $user->tenant_id !== (int) $tenantId || $user->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => ['nullable', 'integer'],
        ]);

        $roleId = $validated['role_id'] ?? null;

        if (! is_null($roleId)) {
            $role = Role::query()->where('tenant_id', $tenantId)->where('id', $roleId)->first();

            if (! $role) {
                return response()->json([
                    'message' => 'Role is invalid.',
                ], 422);
            }

            $user->forceFill([
                'role_id' => $role->id,
                'role' => null,
            ]);
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $user->tenant_id !== (int) $tenantId || $user->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,active,inactive,suspended'],
        ]);

        $user->forceFill([
            'status' => $validated['status'],
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }

    public function sendPasswordResetLink(Request $request, string $user)
    {
        $tenantId = TenantContext::tenantId();

        Log::info('users.reset_password.requested', [
            'tenant_id' => $tenantId,
            'user_param' => $user,
        ]);

        if (! ctype_digit($user)) {
            $payload = [
                'message' => 'Invalid user id.',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'tenant_id' => $tenantId,
                    'user_param' => $user,
                ];
            }

            return response()->json($payload, 422);
        }

        $userModel = User::query()->where('id', (int) $user)->first();

        if (! $userModel) {
            $payload = [
                'message' => 'User not found.',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'tenant_id' => $tenantId,
                    'user_param' => $user,
                ];
            }

            return response()->json($payload, 404);
        }

        if ((int) $userModel->tenant_id !== (int) $tenantId || $userModel->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $status = Password::sendResetLink([
            'email' => $userModel->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Failed to send reset link.',
                'status' => $status,
            ], 422);
        }

        return response()->json([
            'message' => 'Reset link sent.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OneTimePasswordNotification;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TechnicianController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = TenantContext::tenantId();

        $roleId = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', 'Technician')
            ->value('id');

        $roleId = is_numeric($roleId) ? (int) $roleId : null;

        if (! $roleId) {
            return response()->json([
                'users' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => (int) ($validated['per_page'] ?? 10),
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $status = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $sort = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir = is_string($validated['dir'] ?? null) ? strtolower($validated['dir']) : null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $allowedSorts = [
            'id' => 'users.id',
            'name' => 'users.name',
            'email' => 'users.email',
            'status' => 'users.status',
            'created_at' => 'users.created_at',
        ];

        $query = User::query()
            ->where('users.tenant_id', $tenantId)
            ->where('users.is_admin', false)
            ->where('users.role_id', $roleId);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%");
            });
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            if (in_array($status, ['pending', 'active', 'inactive', 'suspended'], true)) {
                $query->where('users.status', $status);
            }
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir);
            $query->orderBy('users.id', 'desc');
        } else {
            $query->orderBy('users.id', 'desc');
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

    public function updateRates(Request $request, string $tenant, User $user)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $user->tenant_id !== (int) $tenantId || $user->is_admin) {
            abort(403, 'Forbidden.');
        }

        $roleId = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', 'Technician')
            ->value('id');

        $roleId = is_numeric($roleId) ? (int) $roleId : null;

        if (! $roleId || (int) $user->role_id !== (int) $roleId) {
            abort(404);
        }

        $validated = $request->validate([
            'tech_hourly_rate_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'client_hourly_rate_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $user->forceFill([
            'tech_hourly_rate_cents' => array_key_exists('tech_hourly_rate_cents', $validated) ? $validated['tech_hourly_rate_cents'] : $user->tech_hourly_rate_cents,
            'client_hourly_rate_cents' => array_key_exists('client_hourly_rate_cents', $validated) ? $validated['client_hourly_rate_cents'] : $user->client_hourly_rate_cents,
        ])->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function store(Request $request, string $tenant)
    {
        $tenantId = TenantContext::tenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer'],
        ]);

        $roleId = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', 'Technician')
            ->value('id');

        $roleId = is_numeric($roleId) ? (int) $roleId : null;

        if (! $roleId) {
            return response()->json([
                'message' => 'Technician role is missing.',
            ], 422);
        }

        $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));

        $validBranchIds = Branch::query()
            ->where('is_active', true)
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($branchIds);
        sort($validBranchIds);

        if (count($branchIds) === 0 || $branchIds !== $validBranchIds) {
            return response()->json([
                'message' => 'Shop selection is invalid.',
            ], 422);
        }

        $oneTimePassword = Str::password(16);
        $oneTimePasswordExpiresAt = now()->addMinutes(60 * 24);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(72)),
            'must_change_password' => true,
            'one_time_password_hash' => Hash::make($oneTimePassword),
            'one_time_password_expires_at' => $oneTimePasswordExpiresAt,
            'one_time_password_used_at' => null,
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'role' => null,
            'status' => 'active',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $sync = [];
        foreach ($validBranchIds as $id) {
            $sync[$id] = ['tenant_id' => $tenantId];
        }

        $user->branches()->sync($sync);

        try {
            $user->notify(new OneTimePasswordNotification($oneTimePassword, 60 * 24));
        } catch (\Throwable $e) {
            Log::error('technician.onetime_password_notification_failed', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'user' => $user->load(['roleModel', 'branches:id,code,name,is_active']),
        ], 201);
    }
}

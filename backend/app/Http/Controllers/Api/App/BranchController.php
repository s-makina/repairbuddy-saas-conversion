<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ImpersonationSession;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $branches = Branch::query()->orderBy('name')->get();

        if (! $user->is_admin) {
            $allowed = BranchAccess::accessibleBranchIdsForUser($user);
            $branches = $branches->whereIn('id', $allowed)->values();
        }

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function current(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $activeBranchId = null;

        $session = $request->attributes->get('impersonation_session');
        if ($session instanceof ImpersonationSession) {
            $activeBranchId = $session->active_branch_id;
        } else {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $activeBranchId = $token->active_branch_id ?? null;
            }
        }

        $activeBranchId = is_numeric($activeBranchId) ? (int) $activeBranchId : null;

        $branch = $activeBranchId ? Branch::query()->whereKey($activeBranchId)->first() : null;

        return response()->json([
            'active_branch_id' => $activeBranchId,
            'branch' => $branch,
        ]);
    }

    public function setActive(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $branch = Branch::query()->whereKey((int) $validated['branch_id'])->first();

        if (! $branch || ! $branch->is_active) {
            return response()->json([
                'message' => 'Branch is invalid.',
            ], 422);
        }

        if (! BranchAccess::userCanAccessBranch($user, $branch)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $session = $request->attributes->get('impersonation_session');
        if ($session instanceof ImpersonationSession) {
            $session->forceFill([
                'active_branch_id' => $branch->id,
            ])->save();
        } else {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->forceFill([
                    'active_branch_id' => $branch->id,
                ])->save();
            }
        }

        return response()->json([
            'status' => 'ok',
            'active_branch_id' => $branch->id,
            'branch' => $branch,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:16'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:64'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = TenantContext::tenantId();

        $exists = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper((string) $validated['code']))
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Branch code already exists.',
                'errors' => [
                    'code' => ['Branch code already exists.'],
                ],
            ], 422);
        }

        $branch = Branch::query()->create($validated);

        return response()->json([
            'branch' => $branch,
        ], 201);
    }

    public function update(Request $request, Branch $branch)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $branch->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:16'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:64'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nextCode = strtoupper((string) $validated['code']);

        $exists = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $nextCode)
            ->where('id', '!=', $branch->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Branch code already exists.',
                'errors' => [
                    'code' => ['Branch code already exists.'],
                ],
            ], 422);
        }

        $branch->forceFill($validated)->save();

        return response()->json([
            'branch' => $branch->fresh(),
        ]);
    }

    public function assignUsers(Request $request, Branch $branch)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $branch->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer'],
        ]);

        $userIds = array_values(array_unique(array_map('intval', $validated['user_ids'])));

        $validUserIds = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->all();

        $branch->users()->sync($validUserIds);

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function users(Request $request, Branch $branch)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $branch->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        $assignedUserIds = $branch->users()->pluck('users.id')->all();

        return response()->json([
            'users' => $users,
            'assigned_user_ids' => array_values(array_map('intval', $assignedUserIds)),
        ]);
    }

    public function setDefault(Request $request, Branch $branch)
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $branch->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant not found.'], 422);
        }

        $tenant->forceFill([
            'default_branch_id' => $branch->id,
        ])->save();

        return response()->json([
            'status' => 'ok',
            'tenant' => $tenant->fresh(),
        ]);
    }
}

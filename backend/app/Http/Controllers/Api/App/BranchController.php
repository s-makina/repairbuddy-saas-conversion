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

    public function show(Request $request, string $business, $branch)
    {
        if (! is_numeric($branch)) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchId = (int) $branch;

        $tenantId = TenantContext::tenantId();

        if (! $tenantId) {
            return response()->json(['message' => 'Tenant context is missing.'], 422);
        }

        $branchModel = Branch::query()->withoutGlobalScopes()->whereKey($branchId)->first();

        if (! $branchModel || (int) $branchModel->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        return response()->json([
            'branch' => $branchModel,
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

    public function update(Request $request, string $business, $branch)
    {
        if (! is_numeric($branch)) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchId = (int) $branch;

        $branchModel = Branch::query()->whereKey($branchId)->first();

        if (! $branchModel) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $tenantId = TenantContext::tenantId();

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
            ->where('id', '!=', $branchModel->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Branch code already exists.',
                'errors' => [
                    'code' => ['Branch code already exists.'],
                ],
            ], 422);
        }

        $branchModel->forceFill($validated)->save();

        return response()->json([
            'branch' => $branchModel->fresh(),
        ]);
    }

    public function assignUsers(Request $request, string $business, $branch)
    {
        if (! is_numeric($branch)) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchId = (int) $branch;

        $branchModel = Branch::query()->whereKey($branchId)->first();

        if (! $branchModel) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $tenantId = TenantContext::tenantId();

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

        $branchModel->users()->sync($validUserIds);

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function users(Request $request, string $business, $branch)
    {
        if (! is_numeric($branch)) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchId = (int) $branch;

        $branchModel = Branch::query()->whereKey($branchId)->first();

        if (! $branchModel) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $tenantId = TenantContext::tenantId();

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        $assignedUserIds = $branchModel->users()->pluck('users.id')->all();

        return response()->json([
            'users' => $users,
            'assigned_user_ids' => array_values(array_map('intval', $assignedUserIds)),
        ]);
    }

    public function setDefault(Request $request, string $business, $branch)
    {
        \Log::info('branches.set_default.request', [
            'path' => $request->path(),
            'business_param' => $business,
            'branch_param' => $branch,
            'tenant_id' => TenantContext::tenantId(),
            'tenant_slug' => TenantContext::tenant()?->slug,
        ]);

        if (! is_numeric($branch)) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchId = (int) $branch;

        $tenantId = TenantContext::tenantId();

        if (! $tenantId) {
            return response()->json(['message' => 'Tenant context is missing.'], 422);
        }

        $branchModel = Branch::query()->withoutGlobalScopes()->whereKey($branchId)->first();

        \Log::info('branches.set_default.lookup', [
            'branch_id' => $branchId,
            'found' => (bool) $branchModel,
            'found_tenant_id' => $branchModel?->tenant_id,
            'tenant_id' => $tenantId,
        ]);

        if (! $branchModel) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        if ((int) $branchModel->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant not found.'], 422);
        }

        $tenant->forceFill([
            'default_branch_id' => $branchModel->id,
        ])->save();

        return response()->json([
            'status' => 'ok',
            'tenant' => $tenant->fresh(),
        ]);
    }
}

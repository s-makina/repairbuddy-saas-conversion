<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantBranchController extends Controller
{
    public function setActive(Request $request, string $business): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404, 'Business is invalid.');
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = is_numeric($validated['branch_id'] ?? null) ? (int) $validated['branch_id'] : null;

        if (! $branchId) {
            $request->session()->forget('active_branch_id');

            return back()->with('status', __('Shop switched.'));
        }

        $branch = Branch::query()->whereKey($branchId)->first();

        if (! $branch || ! $branch->is_active || (int) $branch->tenant_id !== (int) $tenant->id) {
            return back()->withErrors([
                'branch_id' => __('Selected shop is invalid.'),
            ]);
        }

        if (! $user->is_admin && ! BranchAccess::userCanAccessBranch($user, $branch)) {
            abort(403);
        }

        $request->session()->put('active_branch_id', $branch->id);

        return back()->with('status', __('Shop switched.'));
    }
}

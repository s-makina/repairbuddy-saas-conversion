<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $branches = Branch::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('tenant.settings.shops.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Shops'),
            'branches' => $branches,
        ]);
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentBranches = Branch::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('tenant.settings.shops.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Add Shop'),
            'recentBranches' => $recentBranches,
        ]);
    }

    public function edit(Request $request, string $business, int $branch)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = Branch::query()
            ->whereKey($branch)
            ->firstOrFail();

        return view('tenant.settings.shops.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Edit Shop'),
            'branch' => $model,
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
            'code' => [
                'required',
                'string',
                'max:16',
                Rule::unique('branches', 'code')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));

        Branch::query()->create($validated);

        return redirect()
            ->route('tenant.settings.shops.index', ['business' => $tenant->slug])
            ->with('status', __('Shop added.'));
    }

    public function update(Request $request, string $business, int $branch): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = Branch::query()
            ->whereKey($branch)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:16',
                Rule::unique('branches', 'code')->where(fn ($q) => $q->where('tenant_id', $tenant->id))->ignore($model->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));

        $model->forceFill($validated)->save();

        return redirect()
            ->route('tenant.settings.shops.index', ['business' => $tenant->slug])
            ->with('status', __('Shop updated.'));
    }

    public function setActive(Request $request, string $business, int $branch): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = Branch::query()
            ->whereKey($branch)
            ->firstOrFail();

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.settings.shops.index', ['business' => $tenant->slug])
            ->with('status', __('Shop updated.'));
    }

    public function setDefault(Request $request, string $business, int $branch): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = Branch::query()
            ->whereKey($branch)
            ->firstOrFail();

        $tenant->forceFill([
            'default_branch_id' => $model->id,
        ])->save();

        return redirect()
            ->route('tenant.settings.shops.index', ['business' => $tenant->slug])
            ->with('status', __('Default shop updated.'));
    }
}

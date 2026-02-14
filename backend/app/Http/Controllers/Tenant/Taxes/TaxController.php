<?php

namespace App\Http\Controllers\Tenant\Taxes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Taxes\SetTaxActiveRequest;
use App\Http\Requests\Tenant\Taxes\StoreTaxRequest;
use App\Http\Requests\Tenant\Taxes\UpdateTaxRequest;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function store(StoreTaxRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }
        $validated = $request->validated();

        $isActive = ($validated['tax_status'] ?? 'active') === 'active';

        DB::transaction(function () use ($validated, $isActive) {
            RepairBuddyTax::query()->create([
                'name' => $validated['tax_name'],
                'description' => $validated['tax_description'] ?? null,
                'rate' => $validated['tax_rate'],
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax added.')
            ->withInput();
    }

    public function update(UpdateTaxRequest $request, string $tax): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $taxId = (int) $tax;
        if ($taxId <= 0) {
            $taxId = 0;
        }

        $taxModel = $taxId > 0 ? RepairBuddyTax::query()->whereKey($taxId)->first() : null;

        if (! $taxModel instanceof RepairBuddyTax) {
            $fallbackId = (int) ($validated['edit_tax_id'] ?? 0);
            if ($fallbackId > 0) {
                $taxModel = RepairBuddyTax::query()->whereKey($fallbackId)->first();
            }
        }

        if (! $taxModel instanceof RepairBuddyTax) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
                ->withFragment('wc_rb_manage_taxes')
                ->withErrors(['edit_tax_name' => 'Tax not found. Please try again.'])
                ->withInput();
        }

        $isActive = ($validated['edit_tax_status'] ?? 'active') === 'active';

        $taxModel->forceFill([
            'name' => $validated['edit_tax_name'],
            'description' => $validated['edit_tax_description'] ?? null,
            'rate' => $validated['edit_tax_rate'],
            'is_active' => $isActive,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax updated.')
            ->withInput();
    }

    public function setActive(SetTaxActiveRequest $request, int $tax): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $taxModel = RepairBuddyTax::query()->whereKey($tax)->firstOrFail();
        $taxModel->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax updated.')
            ->withInput();
    }

    public function setDefault(int $tax): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $taxModel = RepairBuddyTax::query()->whereKey($tax)->firstOrFail();

        DB::transaction(function () use ($taxModel) {
            RepairBuddyTax::query()->where('is_default', true)->update(['is_default' => false]);
            $taxModel->forceFill(['is_default' => true])->save();
        });

        $tenant->refresh();
        $store = new TenantSettingsStore($tenant);

        $taxSettings = $store->get('taxes', []);
        if (! is_array($taxSettings)) {
            $taxSettings = [];
        }
        $taxSettings['defaultTaxId'] = (string) $taxModel->id;
        $store->set('taxes', $taxSettings);

        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Default tax updated.')
            ->withInput();
    }
}

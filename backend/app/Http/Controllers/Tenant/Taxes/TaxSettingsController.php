<?php

namespace App\Http\Controllers\Tenant\Taxes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Taxes\UpdateTaxSettingsRequest;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class TaxSettingsController extends Controller
{
    public function update(UpdateTaxSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $defaultTaxId = array_key_exists('wc_primary_tax', $validated) ? $validated['wc_primary_tax'] : null;
        if (is_int($defaultTaxId)) {
            $exists = RepairBuddyTax::query()->whereKey($defaultTaxId)->exists();
            if (! $exists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
                    ->withFragment('wc_rb_manage_taxes')
                    ->withErrors(['wc_primary_tax' => 'Selected tax is invalid.'])
                    ->withInput();
            }
        }

        $store = new TenantSettingsStore($tenant);

        $taxSettings = $store->get('taxes', []);
        if (! is_array($taxSettings)) {
            $taxSettings = [];
        }

        $taxSettings['enableTaxes'] = array_key_exists('wc_use_taxes', $validated);
        $taxSettings['defaultTaxId'] = is_int($defaultTaxId) ? (string) $defaultTaxId : null;
        $taxSettings['invoiceAmounts'] = $validated['wc_prices_inclu_exclu'];

        // Sync is_default on the model so both sources stay consistent
        if (is_int($defaultTaxId)) {
            RepairBuddyTax::query()->where('is_default', true)->update(['is_default' => false]);
            RepairBuddyTax::query()->whereKey($defaultTaxId)->update(['is_default' => true]);
        } elseif ($defaultTaxId === null) {
            RepairBuddyTax::query()->where('is_default', true)->update(['is_default' => false]);
        }

        $store->set('taxes', $taxSettings);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax settings updated.')
            ->withInput();
    }
}

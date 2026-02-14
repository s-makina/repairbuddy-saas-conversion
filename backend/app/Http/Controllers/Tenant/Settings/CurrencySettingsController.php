<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateCurrencySettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class CurrencySettingsController extends Controller
{
    public function update(UpdateCurrencySettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $currency = (array) $store->get('currency', []);
        foreach (array_keys($validated) as $k) {
            $currency[$k] = $validated[$k];
        }
        $store->set('currency', $currency);

        if (array_key_exists('wc_cr_selected_currency', $validated)
            && is_string($validated['wc_cr_selected_currency'])
            && $validated['wc_cr_selected_currency'] !== '') {
            $tenant->forceFill([
                'currency' => $validated['wc_cr_selected_currency'],
            ]);
        }

        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('currencyFormatting')
            ->with('status', 'Currency settings updated.')
            ->withInput();
    }
}

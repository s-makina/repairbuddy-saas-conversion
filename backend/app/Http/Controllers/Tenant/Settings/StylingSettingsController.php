<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateStylingSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class StylingSettingsController extends Controller
{
    public function update(UpdateStylingSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $styling = $store->get('styling', []);
        if (! is_array($styling)) {
            $styling = [];
        }

        foreach (['delivery_date_label', 'pickup_date_label', 'nextservice_date_label', 'casenumber_label'] as $k) {
            if (array_key_exists($k, $validated)) {
                $styling[$k] = $validated[$k];
            }
        }

        if (array_key_exists('primary_color', $validated)) {
            $styling['primary_color'] = $validated['primary_color'];
        }
        if (array_key_exists('secondary_color', $validated)) {
            $styling['secondary_color'] = $validated['secondary_color'];
        }

        $store->set('styling', $styling);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_styling')
            ->with('status', 'Styling updated.')
            ->withInput();
    }
}

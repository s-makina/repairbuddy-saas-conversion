<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateServiceSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class ServiceSettingsController extends Controller
{
    public function update(UpdateServiceSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $services = $store->get('services', []);
        if (! is_array($services)) {
            $services = [];
        }

        if (array_key_exists('wc_service_sidebar_description', $validated)) {
            $services['wc_service_sidebar_description'] = $validated['wc_service_sidebar_description'];
        }
        if (array_key_exists('wc_service_booking_heading', $validated)) {
            $services['wc_service_booking_heading'] = $validated['wc_service_booking_heading'];
        }
        if (array_key_exists('wc_service_booking_form', $validated)) {
            $services['wc_service_booking_form'] = $validated['wc_service_booking_form'];
        }

        $services['disableBookingOnServicePage'] = (string) ($validated['wc_booking_on_service_page_status'] ?? 'off') === 'on';

        $store->set('services', $services);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_service')
            ->with('status', 'Service settings updated.')
            ->withInput();
    }
}

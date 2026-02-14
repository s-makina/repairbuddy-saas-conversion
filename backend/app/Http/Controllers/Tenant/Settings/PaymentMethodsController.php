<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdatePaymentMethodsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class PaymentMethodsController extends Controller
{
    public function update(UpdatePaymentMethodsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $methods = [];
        if (array_key_exists('wc_rb_payment_method', $validated) && is_array($validated['wc_rb_payment_method'])) {
            foreach ($validated['wc_rb_payment_method'] as $m) {
                if (! is_string($m)) {
                    continue;
                }
                $m = trim($m);
                if ($m === '') {
                    continue;
                }
                $methods[] = $m;
            }
        }

        $methods = array_values(array_unique($methods));

        $store = new TenantSettingsStore($tenant);

        $settings = $store->get('', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $store->merge('', [
            'payment_methods_active' => $methods,
        ]);

        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment methods updated.')
            ->withInput();
    }
}

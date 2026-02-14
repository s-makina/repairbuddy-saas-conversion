<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdatePagesSetupRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class PagesSetupController extends Controller
{
    public function update(UpdatePagesSetupRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $pages = $store->get('pages', []);
        if (! is_array($pages)) {
            $pages = [];
        }

        foreach ([
            'wc_rb_my_account_page_id',
            'wc_rb_status_check_page_id',
            'wc_rb_get_feedback_page_id',
            'wc_rb_device_booking_page_id',
            'wc_rb_list_services_page_id',
            'wc_rb_list_parts_page_id',
            'wc_rb_customer_login_page',
        ] as $key) {
            $value = array_key_exists($key, $validated) ? $validated[$key] : null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $pages[$key] = ($value === '') ? null : $value;
        }

        $pages['wc_rb_turn_registration_on'] = array_key_exists('wc_rb_turn_registration_on', $validated)
            ? 'on'
            : 'off';

        $store->set('pages', $pages);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('panel1')
            ->with('status', 'Settings updated.')
            ->withInput();
    }
}

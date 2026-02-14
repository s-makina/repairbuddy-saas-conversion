<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateGeneralSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class GeneralSettingsController extends Controller
{
    public function update(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $general = (array) $store->get('general', []);

        foreach ([
            'menu_name',
            'wc_rb_business_phone',
            'wc_rb_business_address',
            'computer_repair_logo',
            'computer_repair_email',
            'case_number_prefix',
            'case_number_length',
            'wc_rb_gdpr_acceptance',
            'wc_rb_gdpr_acceptance_link_label',
            'wc_rb_gdpr_acceptance_link',
            'wc_primary_country',
        ] as $k) {
            if (array_key_exists($k, $validated)) {
                $general[$k] = $validated[$k];
            }
        }

        foreach ([
            'wc_job_status_cr_notice',
            'wcrb_attach_pdf_in_customer_emails',
            'wcrb_next_service_date',
            'wc_enable_woo_products',
            'wcrb_disable_statuscheck_serial',
        ] as $k) {
            $general[$k] = (bool) ($validated[$k] ?? false);
        }

        $store->set('general', $general);

        if (array_key_exists('wc_rb_business_name', $validated) && is_string($validated['wc_rb_business_name'])) {
            $tenant->forceFill([
                'name' => $validated['wc_rb_business_name'],
            ]);
        }

        $tenant->forceFill([
            'contact_phone' => array_key_exists('wc_rb_business_phone', $validated) ? ($validated['wc_rb_business_phone'] ?? null) : $tenant->contact_phone,
            'contact_email' => array_key_exists('computer_repair_email', $validated) ? ($validated['computer_repair_email'] ?? null) : $tenant->contact_email,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('panel1')
            ->with('status', 'Settings updated.')
            ->withInput();
    }
}

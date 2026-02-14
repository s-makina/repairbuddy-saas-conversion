<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateInvoicesSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class InvoicesSettingsController extends Controller
{
    public function update(UpdateInvoicesSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $invoices = (array) $store->get('invoices', []);

        foreach (['wc_rb_io_thanks_msg', 'wb_rb_invoice_type', 'repair_order_type', 'business_terms', 'wc_repair_order_print_size', 'wc_rb_ro_thanks_msg'] as $k) {
            if (array_key_exists($k, $validated)) {
                $invoices[$k] = $validated[$k];
            }
        }

        $invoices['wcrb_add_invoice_qr_code'] = (bool) ($validated['wcrb_add_invoice_qr_code'] ?? false);
        $invoices['wc_rb_cr_display_add_on_ro'] = (bool) ($validated['wc_rb_cr_display_add_on_ro'] ?? false);
        $invoices['wc_rb_cr_display_add_on_ro_cu'] = (bool) ($validated['wc_rb_cr_display_add_on_ro_cu'] ?? false);

        $invoices['pickupdate'] = (string) ($validated['pickupdate'] ?? '') === 'show';
        $invoices['deliverydate'] = (string) ($validated['deliverydate'] ?? '') === 'show';
        $invoices['nextservicedate'] = (string) ($validated['nextservicedate'] ?? '') === 'show';

        $store->set('invoices', $invoices);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('reportsAInvoices')
            ->with('status', 'Invoice settings updated.')
            ->withInput();
    }
}

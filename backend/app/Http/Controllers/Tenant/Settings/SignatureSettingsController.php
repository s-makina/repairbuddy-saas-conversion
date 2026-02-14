<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateSignatureSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class SignatureSettingsController extends Controller
{
    public function update(UpdateSignatureSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $signature = $store->get('signature', []);
        if (! is_array($signature)) {
            $signature = [];
        }

        $signature['required'] = array_key_exists('signature_required', $validated);
        $signature['type'] = $validated['signature_type'] ?? 'draw';
        $signature['terms'] = $validated['signature_terms'] ?? null;

		$signature['pickup_enabled'] = (bool) ($validated['pickup_enabled'] ?? false);
		$signature['pickup_trigger_status'] = (string) ($validated['pickup_trigger_status'] ?? '');
		$signature['pickup_email_subject'] = (string) ($validated['pickup_email_subject'] ?? '');
		$signature['pickup_email_template'] = (string) ($validated['pickup_email_template'] ?? '');
		$signature['pickup_sms_text'] = (string) ($validated['pickup_sms_text'] ?? '');
		$signature['pickup_after_status'] = (string) ($validated['pickup_after_status'] ?? '');

		$signature['delivery_enabled'] = (bool) ($validated['delivery_enabled'] ?? false);
		$signature['delivery_trigger_status'] = (string) ($validated['delivery_trigger_status'] ?? '');
		$signature['delivery_email_subject'] = (string) ($validated['delivery_email_subject'] ?? '');
		$signature['delivery_email_template'] = (string) ($validated['delivery_email_template'] ?? '');
		$signature['delivery_sms_text'] = (string) ($validated['delivery_sms_text'] ?? '');
		$signature['delivery_after_status'] = (string) ($validated['delivery_after_status'] ?? '');

        $store->set('signature', $signature);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_signature_workflow')
            ->with('status', 'Signature settings updated.')
            ->withInput();
    }
}

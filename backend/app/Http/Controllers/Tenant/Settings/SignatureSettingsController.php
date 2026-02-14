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

        $store->set('signature', $signature);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_signature_workflow')
            ->with('status', 'Signature settings updated.')
            ->withInput();
    }
}

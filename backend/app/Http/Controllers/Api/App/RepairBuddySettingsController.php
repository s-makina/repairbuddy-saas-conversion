<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddySettingsController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        return response()->json([
            'settings' => data_get($tenant->setup_state ?? [], 'repairbuddy_settings'),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],

            'settings.general' => ['sometimes', 'array'],
            'settings.general.businessName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.general.businessPhone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.general.businessAddress' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'settings.general.logoUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'settings.general.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'settings.general.caseNumberPrefix' => ['sometimes', 'nullable', 'string', 'max:32'],
            'settings.general.caseNumberLength' => ['sometimes', 'integer', 'min:1', 'max:32'],
            'settings.general.emailCustomer' => ['sometimes', 'boolean'],
            'settings.general.attachPdf' => ['sometimes', 'boolean'],
            'settings.general.nextServiceDateEnabled' => ['sometimes', 'boolean'],
            'settings.general.gdprAcceptanceText' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.general.gdprLinkLabel' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.general.gdprLinkUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'settings.general.defaultCountry' => ['sometimes', 'nullable', 'string', 'size:2'],
            'settings.general.disablePartsUseWooProducts' => ['sometimes', 'boolean'],
            'settings.general.disableStatusCheckBySerial' => ['sometimes', 'boolean'],
        ]);

        $before = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $next = $validated['settings'];

        $state = $tenant->setup_state ?? [];
        if (! is_array($state)) {
            $state = [];
        }

        $state['repairbuddy_settings'] = $next;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        PlatformAudit::log($request, 'tenant.repairbuddy_settings.updated', $tenant, null, [
            'before' => $before,
            'after' => $next,
        ]);

        return response()->json([
            'settings' => $next,
        ]);
    }
}

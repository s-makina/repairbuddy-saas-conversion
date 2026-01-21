<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'tenant' => TenantContext::tenant(),
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
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_country' => ['nullable', 'string', 'size:2'],
            'billing_vat_number' => ['nullable', 'string', 'max:255'],
            'billing_address_json' => ['nullable', 'array'],
        ]);

        $before = [
            'name' => $tenant->name,
            'contact_email' => $tenant->contact_email,
            'currency' => $tenant->currency,
            'billing_country' => $tenant->billing_country,
            'billing_vat_number' => $tenant->billing_vat_number,
            'billing_address_json' => $tenant->billing_address_json,
        ];

        $tenant->forceFill([
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'] ?? null,
            'currency' => isset($validated['currency']) ? strtoupper((string) $validated['currency']) : $tenant->currency,
            'billing_country' => isset($validated['billing_country']) ? strtoupper((string) $validated['billing_country']) : null,
            'billing_vat_number' => $validated['billing_vat_number'] ?? null,
            'billing_address_json' => $validated['billing_address_json'] ?? null,
        ])->save();

        PlatformAudit::log($request, 'tenant.settings.updated', $tenant, null, [
            'before' => $before,
            'after' => [
                'name' => $tenant->name,
                'contact_email' => $tenant->contact_email,
                'currency' => $tenant->currency,
                'billing_country' => $tenant->billing_country,
                'billing_vat_number' => $tenant->billing_vat_number,
                'billing_address_json' => $tenant->billing_address_json,
            ],
        ]);

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }
}

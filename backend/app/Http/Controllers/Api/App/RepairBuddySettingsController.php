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

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = $this->applyTenantIdentityToSettings($settings, $tenant);

        return response()->json([
            'settings' => $settings,
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
            'settings.general.caseNumberPrefix' => ['sometimes', 'nullable', 'string', 'max:32'],
            'settings.general.caseNumberLength' => ['sometimes', 'integer', 'min:1', 'max:32'],
            'settings.general.emailCustomer' => ['sometimes', 'boolean'],
            'settings.general.attachPdf' => ['sometimes', 'boolean'],
            'settings.general.nextServiceDateEnabled' => ['sometimes', 'boolean'],
            'settings.general.gdprAcceptanceText' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.general.gdprLinkLabel' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.general.gdprLinkUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'settings.general.disableStatusCheckBySerial' => ['sometimes', 'boolean'],

            'settings.payments' => ['sometimes', 'array'],
            'settings.payments.paymentMethods' => ['sometimes', 'array'],
            'settings.payments.paymentMethods.cash' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.card' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.bankTransfer' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.paypal' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.other' => ['sometimes', 'boolean'],
        ]);

        $before = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $next = $request->input('settings');
        if (! is_array($next)) {
            $next = [];
        }

        $next = $this->applyTenantIdentityToSettings($next, $tenant);

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

    private function formatTenantAddress(Tenant $tenant): string
    {
        $addr = $tenant->billing_address_json;
        if (! is_array($addr)) {
            $addr = [];
        }

        $parts = [];
        foreach (['line1', 'line2', 'city', 'state', 'postal_code'] as $key) {
            $value = $addr[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $parts[] = trim($value);
            }
        }

        $country = is_string($tenant->billing_country) ? strtoupper(trim($tenant->billing_country)) : '';
        if ($country !== '') {
            $parts[] = $country;
        }

        return implode(', ', $parts);
    }

    private function applyTenantIdentityToSettings(array $settings, Tenant $tenant): array
    {
        $general = [];
        if (array_key_exists('general', $settings) && is_array($settings['general'])) {
            $general = $settings['general'];
        }

        $general['businessName'] = $tenant->name ?? '';
        $general['businessPhone'] = $tenant->contact_phone ?? '';
        $general['email'] = $tenant->contact_email ?? '';
        $general['businessAddress'] = $this->formatTenantAddress($tenant);
        $general['logoUrl'] = is_string($tenant->logo_url) ? $tenant->logo_url : '';

        $country = is_string($tenant->billing_country) ? strtoupper(trim($tenant->billing_country)) : '';
        if ($country !== '') {
            $general['defaultCountry'] = $country;
        } elseif (! array_key_exists('defaultCountry', $general)) {
            $general['defaultCountry'] = 'US';
        }

        $settings['general'] = $general;

        return $settings;
    }
}

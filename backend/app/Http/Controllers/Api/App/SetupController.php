<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SetupController extends Controller
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
            'tenant' => $tenant,
            'setup' => [
                'completed_at' => $tenant->setup_completed_at,
                'step' => $tenant->setup_step,
                'state' => $tenant->setup_state,
            ],
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:64'],

            'billing_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'billing_vat_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address_json' => ['sometimes', 'nullable', 'array'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],

            'timezone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'language' => ['sometimes', 'nullable', 'string', 'max:16'],

            'brand_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'logo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'setup_step' => ['sometimes', 'nullable', 'string', 'max:64'],
            'setup_state' => ['sometimes', 'nullable', 'array'],
        ]);

        $before = [
            'name' => $tenant->name,
            'contact_email' => $tenant->contact_email,
            'contact_phone' => $tenant->contact_phone,
            'billing_country' => $tenant->billing_country,
            'billing_vat_number' => $tenant->billing_vat_number,
            'billing_address_json' => $tenant->billing_address_json,
            'currency' => $tenant->currency,
            'timezone' => $tenant->timezone,
            'language' => $tenant->language,
            'brand_color' => $tenant->brand_color,
            'logo_path' => $tenant->logo_path,
            'setup_step' => $tenant->setup_step,
            'setup_state' => $tenant->setup_state,
        ];

        if (array_key_exists('logo', $validated)) {
            if (is_string($tenant->logo_path) && $tenant->logo_path !== '') {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            if (array_key_exists('logo', $validated) && $validated['logo']) {
                $file = $validated['logo'];
                $path = $file->storePublicly('tenant-logos/'.$tenant->id, ['disk' => 'public']);
                $tenant->logo_path = $path;
            } else {
                $tenant->logo_path = null;
            }
        }

        $tenant->forceFill([
            'name' => array_key_exists('name', $validated) ? $validated['name'] : $tenant->name,
            'contact_email' => array_key_exists('contact_email', $validated) ? ($validated['contact_email'] ?? null) : $tenant->contact_email,
            'contact_phone' => array_key_exists('contact_phone', $validated) ? ($validated['contact_phone'] ?? null) : $tenant->contact_phone,

            'currency' => array_key_exists('currency', $validated)
                ? ($validated['currency'] ? strtoupper((string) $validated['currency']) : null)
                : $tenant->currency,
            'billing_country' => array_key_exists('billing_country', $validated)
                ? ($validated['billing_country'] ? strtoupper((string) $validated['billing_country']) : null)
                : $tenant->billing_country,
            'billing_vat_number' => array_key_exists('billing_vat_number', $validated) ? ($validated['billing_vat_number'] ?? null) : $tenant->billing_vat_number,
            'billing_address_json' => array_key_exists('billing_address_json', $validated) ? ($validated['billing_address_json'] ?? null) : $tenant->billing_address_json,

            'timezone' => array_key_exists('timezone', $validated) ? ($validated['timezone'] ?? null) : $tenant->timezone,
            'language' => array_key_exists('language', $validated) ? ($validated['language'] ?? null) : $tenant->language,

            'brand_color' => array_key_exists('brand_color', $validated) ? ($validated['brand_color'] ?? null) : $tenant->brand_color,

            'setup_step' => array_key_exists('setup_step', $validated) ? ($validated['setup_step'] ?? null) : $tenant->setup_step,
            'setup_state' => array_key_exists('setup_state', $validated) ? ($validated['setup_state'] ?? null) : $tenant->setup_state,
        ])->save();

        PlatformAudit::log($request, 'tenant.setup.updated', $tenant, null, [
            'before' => $before,
            'after' => [
                'name' => $tenant->name,
                'contact_email' => $tenant->contact_email,
                'contact_phone' => $tenant->contact_phone,
                'billing_country' => $tenant->billing_country,
                'billing_vat_number' => $tenant->billing_vat_number,
                'billing_address_json' => $tenant->billing_address_json,
                'currency' => $tenant->currency,
                'timezone' => $tenant->timezone,
                'language' => $tenant->language,
                'brand_color' => $tenant->brand_color,
                'logo_path' => $tenant->logo_path,
                'setup_step' => $tenant->setup_step,
                'setup_state' => $tenant->setup_state,
            ],
        ]);

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function complete(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:64'],
            'billing_country' => ['required', 'string', 'size:2'],
            'billing_address_json' => ['required', 'array'],
            'billing_address_json.line1' => ['required', 'string', 'max:255'],
            'billing_address_json.city' => ['required', 'string', 'max:255'],
            'billing_address_json.postal_code' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'language' => ['required', 'string', 'max:16'],
        ]);

        $primaryContactName = data_get($tenant->setup_state ?? [], 'identity.primary_contact_name');
        if (! is_string($primaryContactName) || trim($primaryContactName) === '') {
            return response()->json([
                'message' => 'Primary contact person name is required.',
            ], 422);
        }

        $taxRegistered = data_get($tenant->setup_state ?? [], 'tax.tax_registered');
        if ($taxRegistered === true && (! $tenant->billing_vat_number || trim((string) $tenant->billing_vat_number) === '')) {
            return response()->json([
                'message' => 'VAT number is required when tax/VAT registered is enabled.',
            ], 422);
        }

        $before = [
            'setup_completed_at' => $tenant->setup_completed_at,
            'setup_step' => $tenant->setup_step,
        ];

        $tenant->forceFill([
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'billing_country' => strtoupper((string) $validated['billing_country']),
            'billing_address_json' => $validated['billing_address_json'],
            'currency' => strtoupper((string) $validated['currency']),
            'timezone' => $validated['timezone'],
            'language' => $validated['language'],
            'setup_completed_at' => now(),
            'setup_step' => null,
        ])->save();

        PlatformAudit::log($request, 'tenant.setup.completed', $tenant, null, [
            'before' => $before,
            'after' => [
                'setup_completed_at' => $tenant->setup_completed_at,
                'setup_step' => $tenant->setup_step,
            ],
        ]);

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }
}

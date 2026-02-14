<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateDevicesBrandsSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DevicesBrandsSettingsController extends Controller
{
    public function update(UpdateDevicesBrandsSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $devicesBrands = $store->get('devicesBrands', []);
        if (! is_array($devicesBrands)) {
            $devicesBrands = [];
        }

        $devicesBrands['enablePinCodeField'] = array_key_exists('enablePinCodeField', $validated) && ((string) $validated['enablePinCodeField'] === '1');
        $devicesBrands['showPinCodeInDocuments'] = array_key_exists('showPinCodeInDocuments', $validated) && ((string) $validated['showPinCodeInDocuments'] === '1');
        $devicesBrands['useWooProductsAsDevices'] = array_key_exists('useWooProductsAsDevices', $validated) && ((string) $validated['useWooProductsAsDevices'] === '1');

        $labels = $devicesBrands['labels'] ?? [];
        if (! is_array($labels)) {
            $labels = [];
        }

        if (array_key_exists('labels', $validated) && is_array($validated['labels'])) {
            foreach (['note', 'pin', 'device', 'deviceBrand', 'deviceType', 'imei'] as $k) {
                if (array_key_exists($k, $validated['labels'])) {
                    $val = $validated['labels'][$k];
                    if (is_string($val)) {
                        $val = trim($val);
                    }
                    $labels[$k] = ($val === '') ? null : $val;
                }
            }
        }

        $devicesBrands['labels'] = $labels;

        $additionalDeviceFields = [];
        if (array_key_exists('additionalDeviceFields', $validated) && is_array($validated['additionalDeviceFields'])) {
            foreach ($validated['additionalDeviceFields'] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $label = $row['label'] ?? null;
                if (! is_string($label) || trim($label) === '') {
                    continue;
                }

                $id = $row['id'] ?? null;
                if (is_string($id)) {
                    $id = trim($id);
                }
                if (! is_string($id) || $id === '') {
                    $id = (string) Str::uuid();
                }

                $additionalDeviceFields[] = [
                    'id' => $id,
                    'label' => trim($label),
                    'type' => 'text',
                    'displayInBookingForm' => array_key_exists('displayInBookingForm', $row) && ((string) ($row['displayInBookingForm'] ?? '') === '1'),
                    'displayInInvoice' => array_key_exists('displayInInvoice', $row) && ((string) ($row['displayInInvoice'] ?? '') === '1'),
                    'displayForCustomer' => array_key_exists('displayForCustomer', $row) && ((string) ($row['displayForCustomer'] ?? '') === '1'),
                ];
            }
        }
        $devicesBrands['additionalDeviceFields'] = $additionalDeviceFields;

        $devicesBrands['pickupDeliveryEnabled'] = array_key_exists('pickupDeliveryEnabled', $validated) && ((string) $validated['pickupDeliveryEnabled'] === '1');
        $devicesBrands['pickupCharge'] = array_key_exists('pickupCharge', $validated) ? $validated['pickupCharge'] : null;
        $devicesBrands['deliveryCharge'] = array_key_exists('deliveryCharge', $validated) ? $validated['deliveryCharge'] : null;

        $devicesBrands['rentalEnabled'] = array_key_exists('rentalEnabled', $validated) && ((string) $validated['rentalEnabled'] === '1');
        $devicesBrands['rentalPerDay'] = array_key_exists('rentalPerDay', $validated) ? $validated['rentalPerDay'] : null;
        $devicesBrands['rentalPerWeek'] = array_key_exists('rentalPerWeek', $validated) ? $validated['rentalPerWeek'] : null;

        $store->set('devicesBrands', $devicesBrands);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Settings updated.')
            ->withInput();
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePriceOverride;
use Illuminate\Http\Request;

class RepairBuddyServicePricingController extends Controller
{
    public function resolvePrice(Request $request, string $business)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
            'device_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $serviceId = (int) $validated['service_id'];
        $service = RepairBuddyService::query()->whereKey($serviceId)->first();
        if (! $service) {
            return response()->json([
                'message' => 'Service not found.',
            ], 404);
        }

        $deviceId = array_key_exists('device_id', $validated) && is_numeric($validated['device_id']) ? (int) $validated['device_id'] : null;
        $brandId = array_key_exists('device_brand_id', $validated) && is_numeric($validated['device_brand_id']) ? (int) $validated['device_brand_id'] : null;
        $typeId = array_key_exists('device_type_id', $validated) && is_numeric($validated['device_type_id']) ? (int) $validated['device_type_id'] : null;

        $override = null;
        $source = 'base';

        if ($deviceId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'device')
                ->where('scope_ref_id', $deviceId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            if ($override) {
                $source = 'device';
            }
        }

        if (! $override && $brandId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'brand')
                ->where('scope_ref_id', $brandId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            if ($override) {
                $source = 'brand';
            }
        }

        if (! $override && $typeId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            if ($override) {
                $source = 'type';
            }
        }

        $resolvedPriceCents = $override && is_numeric($override->price_amount_cents)
            ? (int) $override->price_amount_cents
            : (is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : null);

        $resolvedCurrency = $override && is_string($override->price_currency) && $override->price_currency !== ''
            ? (string) $override->price_currency
            : (is_string($service->base_price_currency) && $service->base_price_currency !== '' ? (string) $service->base_price_currency : null);

        $resolvedTaxId = $override && is_numeric($override->tax_id)
            ? (int) $override->tax_id
            : null;

        $resolvedPrice = null;
        if ($resolvedPriceCents !== null && $resolvedCurrency !== null && $resolvedCurrency !== '') {
            $resolvedPrice = [
                'currency' => $resolvedCurrency,
                'amount_cents' => $resolvedPriceCents,
            ];
        }

        return response()->json([
            'service_id' => $service->id,
            'source' => $source,
            'resolved' => [
                'price' => $resolvedPrice,
                'tax_id' => $resolvedTaxId,
            ],
        ]);
    }
}

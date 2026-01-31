<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyTax;
use Illuminate\Http\Request;

class RepairBuddyServicePriceOverrideController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'service_id' => ['sometimes', 'nullable', 'integer'],
            'scope_type' => ['sometimes', 'nullable', 'string', 'max:32'],
            'scope_ref_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyServicePriceOverride::query()->orderByDesc('id');

        if (array_key_exists('service_id', $validated) && is_numeric($validated['service_id'])) {
            $query->where('service_id', (int) $validated['service_id']);
        }

        if (array_key_exists('scope_type', $validated) && is_string($validated['scope_type']) && $validated['scope_type'] !== '') {
            $query->where('scope_type', (string) $validated['scope_type']);
        }

        if (array_key_exists('scope_ref_id', $validated) && is_numeric($validated['scope_ref_id'])) {
            $query->where('scope_ref_id', (int) $validated['scope_ref_id']);
        }

        $items = $query->limit($limit)->get();

        return response()->json([
            'service_price_overrides' => $items->map(fn (RepairBuddyServicePriceOverride $o) => $this->serialize($o)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
            'scope_type' => ['required', 'string', 'max:32'],
            'scope_ref_id' => ['required', 'integer'],
            'price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $serviceId = (int) $validated['service_id'];
        $service = RepairBuddyService::query()->whereKey($serviceId)->first();
        if (! $service) {
            return response()->json([
                'message' => 'Service is invalid.',
            ], 422);
        }

        $scopeType = (string) $validated['scope_type'];
        if (! in_array($scopeType, ['device', 'brand', 'type'], true)) {
            return response()->json([
                'message' => 'Scope type is invalid.',
            ], 422);
        }

        $scopeRefId = (int) $validated['scope_ref_id'];

        if ($scopeType === 'device' && ! RepairBuddyDevice::query()->whereKey($scopeRefId)->exists()) {
            return response()->json(['message' => 'Device is invalid.'], 422);
        }

        if ($scopeType === 'brand' && ! RepairBuddyDeviceBrand::query()->whereKey($scopeRefId)->exists()) {
            return response()->json(['message' => 'Device brand is invalid.'], 422);
        }

        if ($scopeType === 'type' && ! RepairBuddyDeviceType::query()->whereKey($scopeRefId)->exists()) {
            return response()->json(['message' => 'Device type is invalid.'], 422);
        }

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if ($taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $override = RepairBuddyServicePriceOverride::query()->create([
            'service_id' => $service->id,
            'scope_type' => $scopeType,
            'scope_ref_id' => $scopeRefId,
            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : null,
            'price_currency' => array_key_exists('price_currency', $validated) && is_string($validated['price_currency']) && $validated['price_currency'] !== ''
                ? strtoupper((string) $validated['price_currency'])
                : null,
            'tax_id' => $taxId,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'service_price_override' => $this->serialize($override),
        ], 201);
    }

    public function update(Request $request, string $business, $overrideId)
    {
        if (! is_numeric($overrideId)) {
            return response()->json([
                'message' => 'Service price override not found.',
            ], 404);
        }

        $override = RepairBuddyServicePriceOverride::query()->whereKey((int) $overrideId)->first();

        if (! $override) {
            return response()->json([
                'message' => 'Service price override not found.',
            ], 404);
        }

        $validated = $request->validate([
            'price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if (array_key_exists('tax_id', $validated) && $taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $override->forceFill([
            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : $override->price_amount_cents,
            'price_currency' => array_key_exists('price_currency', $validated)
                ? (is_string($validated['price_currency']) && $validated['price_currency'] !== '' ? strtoupper((string) $validated['price_currency']) : null)
                : $override->price_currency,
            'tax_id' => array_key_exists('tax_id', $validated) ? $taxId : $override->tax_id,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $override->is_active,
        ])->save();

        return response()->json([
            'service_price_override' => $this->serialize($override->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $overrideId)
    {
        if (! is_numeric($overrideId)) {
            return response()->json([
                'message' => 'Service price override not found.',
            ], 404);
        }

        $override = RepairBuddyServicePriceOverride::query()->whereKey((int) $overrideId)->first();

        if (! $override) {
            return response()->json([
                'message' => 'Service price override not found.',
            ], 404);
        }

        $override->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    private function serialize(RepairBuddyServicePriceOverride $o): array
    {
        $price = null;
        if (is_numeric($o->price_amount_cents) && is_string($o->price_currency) && $o->price_currency !== '') {
            $price = [
                'currency' => $o->price_currency,
                'amount_cents' => (int) $o->price_amount_cents,
            ];
        }

        return [
            'id' => $o->id,
            'service_id' => $o->service_id,
            'scope_type' => $o->scope_type,
            'scope_ref_id' => $o->scope_ref_id,
            'price' => $price,
            'tax_id' => $o->tax_id,
            'is_active' => (bool) $o->is_active,
        ];
    }
}

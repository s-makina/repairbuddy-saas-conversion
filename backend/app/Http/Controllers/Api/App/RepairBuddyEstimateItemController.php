<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartPriceOverride;
use App\Models\RepairBuddyPartVariant;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyTax;
use Illuminate\Http\Request;

class RepairBuddyEstimateItemController extends Controller
{
    public function store(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $validated = $request->validate([
            'item_type' => ['required', 'string', 'max:32'],
            'ref_id' => ['sometimes', 'nullable', 'integer'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'qty' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'unit_price_amount_cents' => ['sometimes', 'nullable', 'integer', 'min:-1000000000', 'max:1000000000'],
            'unit_price_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);

        $itemType = (string) $validated['item_type'];
        if (! in_array($itemType, ['service', 'part', 'fee', 'discount'], true)) {
            return response()->json([
                'message' => 'Item type is invalid.',
            ], 422);
        }

        $refId = array_key_exists('ref_id', $validated) && is_numeric($validated['ref_id']) ? (int) $validated['ref_id'] : null;
        $name = is_string($validated['name'] ?? null) ? trim((string) $validated['name']) : '';
        $qty = is_numeric($validated['qty'] ?? null) ? (int) $validated['qty'] : 1;

        $unitPriceCents = array_key_exists('unit_price_amount_cents', $validated) && is_numeric($validated['unit_price_amount_cents'])
            ? (int) $validated['unit_price_amount_cents']
            : null;

        $currencyFromRequest = is_string($validated['unit_price_currency'] ?? null) && $validated['unit_price_currency'] !== ''
            ? strtoupper((string) $validated['unit_price_currency'])
            : null;

        $currency = $currencyFromRequest ?: (string) ($this->tenant()->currency ?? 'USD');

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if ($taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $resolvedName = $name;
        $resolvedUnitPriceCents = $unitPriceCents;

        $meta = is_array($validated['meta'] ?? null) ? $validated['meta'] : null;
        $metaDeviceId = is_array($meta) && array_key_exists('device_id', $meta) && is_numeric($meta['device_id']) ? (int) $meta['device_id'] : null;

        if ($itemType === 'service') {
            if (! $refId) {
                return response()->json([
                    'message' => 'Service ref_id is required.',
                ], 422);
            }

            $service = RepairBuddyService::query()->whereKey($refId)->first();
            if (! $service) {
                return response()->json([
                    'message' => 'Service is invalid.',
                ], 422);
            }

            $resolvedName = $resolvedName !== '' ? $resolvedName : (string) $service->name;
            if ($resolvedUnitPriceCents === null) {
                $contextDeviceId = null;
                $contextBrandId = null;
                $contextTypeId = null;

                if ($metaDeviceId) {
                    $device = RepairBuddyDevice::query()->whereKey($metaDeviceId)->first();
                    if ($device) {
                        $contextDeviceId = (int) $device->id;
                        $contextBrandId = is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                        $contextTypeId = is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;
                    }
                }

                if (! $contextDeviceId) {
                    $latestEstimateDevice = RepairBuddyEstimateDevice::query()
                        ->where('estimate_id', $estimate->id)
                        ->orderByDesc('id')
                        ->first();

                    if ($latestEstimateDevice) {
                        $customerDevice = RepairBuddyCustomerDevice::query()->whereKey((int) $latestEstimateDevice->customer_device_id)->first();
                        if ($customerDevice && is_numeric($customerDevice->device_id)) {
                            $deviceId = (int) $customerDevice->device_id;
                            $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
                            if ($device) {
                                $contextDeviceId = (int) $device->id;
                                $contextBrandId = is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                                $contextTypeId = is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;
                            }
                        }
                    }
                }

                $override = null;

                if ($contextDeviceId) {
                    $override = RepairBuddyServicePriceOverride::query()
                        ->where('service_id', $service->id)
                        ->where('scope_type', 'device')
                        ->where('scope_ref_id', $contextDeviceId)
                        ->where('is_active', true)
                        ->orderByDesc('id')
                        ->first();
                }

                if (! $override && $contextBrandId) {
                    $override = RepairBuddyServicePriceOverride::query()
                        ->where('service_id', $service->id)
                        ->where('scope_type', 'brand')
                        ->where('scope_ref_id', $contextBrandId)
                        ->where('is_active', true)
                        ->orderByDesc('id')
                        ->first();
                }

                if (! $override && $contextTypeId) {
                    $override = RepairBuddyServicePriceOverride::query()
                        ->where('service_id', $service->id)
                        ->where('scope_type', 'type')
                        ->where('scope_ref_id', $contextTypeId)
                        ->where('is_active', true)
                        ->orderByDesc('id')
                        ->first();
                }

                $resolvedUnitPriceCents = $override && is_numeric($override->price_amount_cents)
                    ? (int) $override->price_amount_cents
                    : (is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : null);

                if (! $taxId && $override && is_numeric($override->tax_id)) {
                    $taxId = (int) $override->tax_id;
                }

                if (! $taxId && is_numeric($service->tax_id)) {
                    $taxId = (int) $service->tax_id;
                }

                if (! $currencyFromRequest) {
                    $resolvedCurrency = $override && is_string($override->price_currency) && $override->price_currency !== ''
                        ? (string) $override->price_currency
                        : (is_string($service->base_price_currency) && $service->base_price_currency !== '' ? (string) $service->base_price_currency : null);

                    if ($resolvedCurrency && strlen($resolvedCurrency) === 3) {
                        $currency = strtoupper($resolvedCurrency);
                    }
                }
            }
        }

        if ($itemType === 'part') {
            if (! $refId) {
                return response()->json([
                    'message' => 'Part ref_id is required.',
                ], 422);
            }

            $part = RepairBuddyPart::query()->whereKey($refId)->first();
            if (! $part) {
                return response()->json([
                    'message' => 'Part is invalid.',
                ], 422);
            }

            $resolvedName = $resolvedName !== '' ? $resolvedName : (string) $part->name;

            $variantId = is_array($meta) && array_key_exists('part_variant_id', $meta) && is_numeric($meta['part_variant_id'])
                ? (int) $meta['part_variant_id']
                : null;

            $variant = null;
            if ($variantId) {
                $variant = RepairBuddyPartVariant::query()->whereKey($variantId)->first();
                if (! $variant || (int) $variant->part_id !== (int) $part->id) {
                    return response()->json([
                        'message' => 'Part variant is invalid.',
                    ], 422);
                }
            }

            $contextDeviceId = null;
            $contextBrandId = null;
            $contextTypeId = null;

            if ($metaDeviceId) {
                $device = RepairBuddyDevice::query()->whereKey($metaDeviceId)->first();
                if ($device) {
                    $contextDeviceId = (int) $device->id;
                    $contextBrandId = is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                    $contextTypeId = is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;
                }
            }

            $override = null;
            if ($contextDeviceId) {
                $override = RepairBuddyPartPriceOverride::query()
                    ->where('part_id', $part->id)
                    ->where('part_variant_id', $variantId)
                    ->where('scope_type', 'device')
                    ->where('scope_ref_id', $contextDeviceId)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $override && $contextBrandId) {
                $override = RepairBuddyPartPriceOverride::query()
                    ->where('part_id', $part->id)
                    ->where('part_variant_id', $variantId)
                    ->where('scope_type', 'brand')
                    ->where('scope_ref_id', $contextBrandId)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $override && $contextTypeId) {
                $override = RepairBuddyPartPriceOverride::query()
                    ->where('part_id', $part->id)
                    ->where('part_variant_id', $variantId)
                    ->where('scope_type', 'type')
                    ->where('scope_ref_id', $contextTypeId)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();
            }

            $base = $variant ?: $part;

            if ($resolvedUnitPriceCents === null) {
                $resolvedUnitPriceCents = $override && is_numeric($override->price_amount_cents)
                    ? (int) $override->price_amount_cents
                    : (is_numeric($base->price_amount_cents) ? (int) $base->price_amount_cents : null);
            }

            if (! $taxId) {
                if ($override && is_numeric($override->tax_id)) {
                    $taxId = (int) $override->tax_id;
                } elseif (is_numeric($base->tax_id)) {
                    $taxId = (int) $base->tax_id;
                }
            }

            if (! $currencyFromRequest) {
                $resolvedCurrency = $override && is_string($override->price_currency) && $override->price_currency !== ''
                    ? (string) $override->price_currency
                    : (is_string($base->price_currency) && $base->price_currency !== '' ? (string) $base->price_currency : null);

                if ($resolvedCurrency && strlen($resolvedCurrency) === 3) {
                    $currency = strtoupper($resolvedCurrency);
                }
            }
        }

        if ($resolvedName === '') {
            return response()->json([
                'message' => 'Name is required.',
            ], 422);
        }

        if ($resolvedUnitPriceCents === null) {
            return response()->json([
                'message' => 'Unit price is required.',
            ], 422);
        }

        $item = RepairBuddyEstimateItem::query()->create([
            'estimate_id' => $estimate->id,
            'item_type' => $itemType,
            'ref_id' => $refId,
            'name_snapshot' => $resolvedName,
            'qty' => $qty,
            'unit_price_amount_cents' => $resolvedUnitPriceCents,
            'unit_price_currency' => $currency,
            'tax_id' => $taxId,
            'meta_json' => $validated['meta'] ?? null,
        ]);

        $totals = $this->computeTotals($estimate);

        return response()->json([
            'item' => $this->serializeItem($item->fresh(['tax'])),
            'totals' => $totals,
        ], 201);
    }

    public function destroy(Request $request, string $business, $estimateId, $itemId)
    {
        if (! is_numeric($estimateId) || ! is_numeric($itemId)) {
            return response()->json([
                'message' => 'Estimate item not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $item = RepairBuddyEstimateItem::query()
            ->whereKey((int) $itemId)
            ->where('estimate_id', $estimate->id)
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Estimate item not found.',
            ], 404);
        }

        $item->delete();

        $totals = $this->computeTotals($estimate);

        return response()->json([
            'message' => 'Deleted.',
            'totals' => $totals,
        ]);
    }

    private function serializeItem(RepairBuddyEstimateItem $i): array
    {
        $tax = null;
        if ($i->tax) {
            $tax = [
                'id' => $i->tax->id,
                'name' => $i->tax->name,
                'rate' => $i->tax->rate,
                'is_default' => (bool) $i->tax->is_default,
            ];
        }

        return [
            'id' => $i->id,
            'estimate_id' => $i->estimate_id,
            'item_type' => $i->item_type,
            'ref_id' => $i->ref_id,
            'name' => $i->name_snapshot,
            'qty' => $i->qty,
            'unit_price' => [
                'currency' => $i->unit_price_currency,
                'amount_cents' => (int) $i->unit_price_amount_cents,
            ],
            'tax' => $tax,
            'meta' => is_array($i->meta_json) ? $i->meta_json : null,
            'created_at' => $i->created_at,
        ];
    }

    private function computeTotals(RepairBuddyEstimate $estimate): array
    {
        $items = RepairBuddyEstimateItem::query()
            ->where('estimate_id', $estimate->id)
            ->with('tax')
            ->limit(5000)
            ->get();

        $currency = (string) ($this->tenant()->currency ?? 'USD');

        $subtotalCents = 0;
        $taxCents = 0;

        foreach ($items as $item) {
            $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
            $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
            $lineSubtotal = $qty * $unit;

            $rate = $item->tax ? (float) $item->tax->rate : 0.0;
            $lineTax = (int) round($lineSubtotal * ($rate / 100.0));

            $subtotalCents += $lineSubtotal;
            $taxCents += $lineTax;

            if (is_string($item->unit_price_currency) && $item->unit_price_currency !== '') {
                $currency = (string) $item->unit_price_currency;
            }
        }

        return [
            'currency' => $currency,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $subtotalCents + $taxCents,
        ];
    }
}

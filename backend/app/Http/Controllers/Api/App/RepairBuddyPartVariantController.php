<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartVariant;
use App\Models\RepairBuddyTax;
use Illuminate\Http\Request;

class RepairBuddyPartVariantController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'part_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;
        $partId = array_key_exists('part_id', $validated) && is_numeric($validated['part_id']) ? (int) $validated['part_id'] : null;

        $query = RepairBuddyPartVariant::query()->orderBy('name');

        if ($partId) {
            $query->where('part_id', $partId);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $variants = $query->limit($limit)->get();

        return response()->json([
            'part_variants' => $variants->map(fn (RepairBuddyPartVariant $v) => $this->serialize($v)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],

            'manufacturing_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stock_code' => ['sometimes', 'nullable', 'string', 'max:255'],

            'price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],

            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'core_features' => ['sometimes', 'nullable', 'string'],
            'capacity' => ['sometimes', 'nullable', 'string', 'max:255'],

            'installation_charges_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'installation_charges_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'installation_message' => ['sometimes', 'nullable', 'string', 'max:255'],

            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $partId = (int) $validated['part_id'];
        $part = RepairBuddyPart::query()->whereKey($partId)->first();
        if (! $part) {
            return response()->json([
                'message' => 'Part is invalid.',
            ], 422);
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

        $variant = RepairBuddyPartVariant::query()->create([
            'part_id' => $part->id,
            'name' => trim((string) $validated['name']),
            'sku' => $validated['sku'] ?? null,

            'manufacturing_code' => $validated['manufacturing_code'] ?? null,
            'stock_code' => $validated['stock_code'] ?? null,

            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : null,
            'price_currency' => array_key_exists('price_currency', $validated) && is_string($validated['price_currency']) && $validated['price_currency'] !== ''
                ? strtoupper((string) $validated['price_currency'])
                : null,
            'tax_id' => $taxId,

            'warranty' => $validated['warranty'] ?? null,
            'core_features' => $validated['core_features'] ?? null,
            'capacity' => $validated['capacity'] ?? null,

            'installation_charges_amount_cents' => array_key_exists('installation_charges_amount_cents', $validated) ? $validated['installation_charges_amount_cents'] : null,
            'installation_charges_currency' => array_key_exists('installation_charges_currency', $validated) && is_string($validated['installation_charges_currency']) && $validated['installation_charges_currency'] !== ''
                ? strtoupper((string) $validated['installation_charges_currency'])
                : null,
            'installation_message' => $validated['installation_message'] ?? null,

            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part_variant' => $this->serialize($variant),
        ], 201);
    }

    public function update(Request $request, string $business, $variantId)
    {
        if (! is_numeric($variantId)) {
            return response()->json([
                'message' => 'Part variant not found.',
            ], 404);
        }

        $variant = RepairBuddyPartVariant::query()->whereKey((int) $variantId)->first();

        if (! $variant) {
            return response()->json([
                'message' => 'Part variant not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],

            'manufacturing_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stock_code' => ['sometimes', 'nullable', 'string', 'max:255'],

            'price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],

            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'core_features' => ['sometimes', 'nullable', 'string'],
            'capacity' => ['sometimes', 'nullable', 'string', 'max:255'],

            'installation_charges_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'installation_charges_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'installation_message' => ['sometimes', 'nullable', 'string', 'max:255'],

            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if ($taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $variant->forceFill([
            'name' => trim((string) $validated['name']),
            'sku' => array_key_exists('sku', $validated) ? $validated['sku'] : $variant->sku,

            'manufacturing_code' => array_key_exists('manufacturing_code', $validated) ? $validated['manufacturing_code'] : $variant->manufacturing_code,
            'stock_code' => array_key_exists('stock_code', $validated) ? $validated['stock_code'] : $variant->stock_code,

            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : $variant->price_amount_cents,
            'price_currency' => array_key_exists('price_currency', $validated)
                ? (is_string($validated['price_currency']) && $validated['price_currency'] !== '' ? strtoupper((string) $validated['price_currency']) : null)
                : $variant->price_currency,
            'tax_id' => array_key_exists('tax_id', $validated) ? $taxId : $variant->tax_id,

            'warranty' => array_key_exists('warranty', $validated) ? $validated['warranty'] : $variant->warranty,
            'core_features' => array_key_exists('core_features', $validated) ? $validated['core_features'] : $variant->core_features,
            'capacity' => array_key_exists('capacity', $validated) ? $validated['capacity'] : $variant->capacity,

            'installation_charges_amount_cents' => array_key_exists('installation_charges_amount_cents', $validated) ? $validated['installation_charges_amount_cents'] : $variant->installation_charges_amount_cents,
            'installation_charges_currency' => array_key_exists('installation_charges_currency', $validated)
                ? (is_string($validated['installation_charges_currency']) && $validated['installation_charges_currency'] !== '' ? strtoupper((string) $validated['installation_charges_currency']) : null)
                : $variant->installation_charges_currency,
            'installation_message' => array_key_exists('installation_message', $validated) ? $validated['installation_message'] : $variant->installation_message,

            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $variant->is_active,
        ])->save();

        return response()->json([
            'part_variant' => $this->serialize($variant->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $variantId)
    {
        if (! is_numeric($variantId)) {
            return response()->json([
                'message' => 'Part variant not found.',
            ], 404);
        }

        $variant = RepairBuddyPartVariant::query()->whereKey((int) $variantId)->first();

        if (! $variant) {
            return response()->json([
                'message' => 'Part variant not found.',
            ], 404);
        }

        $variant->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    private function serialize(RepairBuddyPartVariant $v): array
    {
        $price = null;
        if (is_numeric($v->price_amount_cents) && is_string($v->price_currency) && $v->price_currency !== '') {
            $price = [
                'currency' => $v->price_currency,
                'amount_cents' => (int) $v->price_amount_cents,
            ];
        }

        $installationCharges = null;
        if (is_numeric($v->installation_charges_amount_cents) && is_string($v->installation_charges_currency) && $v->installation_charges_currency !== '') {
            $installationCharges = [
                'currency' => $v->installation_charges_currency,
                'amount_cents' => (int) $v->installation_charges_amount_cents,
            ];
        }

        return [
            'id' => $v->id,
            'part_id' => $v->part_id,
            'name' => $v->name,
            'sku' => $v->sku,
            'manufacturing_code' => $v->manufacturing_code,
            'stock_code' => $v->stock_code,
            'price' => $price,
            'tax_id' => $v->tax_id,
            'warranty' => $v->warranty,
            'core_features' => $v->core_features,
            'capacity' => $v->capacity,
            'installation_charges' => $installationCharges,
            'installation_message' => $v->installation_message,
            'is_active' => (bool) $v->is_active,
        ];
    }
}

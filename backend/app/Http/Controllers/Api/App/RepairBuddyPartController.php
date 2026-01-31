<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use App\Models\RepairBuddyPartPriceOverride;
use App\Models\RepairBuddyPartType;
use App\Models\RepairBuddyPartVariant;
use App\Models\RepairBuddyTax;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddyPartController extends Controller
{
    private function serialize(RepairBuddyPart $p): array
    {
        $price = null;
        if (is_numeric($p->price_amount_cents) && is_string($p->price_currency) && $p->price_currency !== '') {
            $price = [
                'currency' => $p->price_currency,
                'amount_cents' => (int) $p->price_amount_cents,
            ];
        }

        $installationCharges = null;
        if (is_numeric($p->installation_charges_amount_cents) && is_string($p->installation_charges_currency) && $p->installation_charges_currency !== '') {
            $installationCharges = [
                'currency' => $p->installation_charges_currency,
                'amount_cents' => (int) $p->installation_charges_amount_cents,
            ];
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'part_type_id' => $p->part_type_id,
            'part_brand_id' => $p->part_brand_id,
            'manufacturing_code' => $p->manufacturing_code,
            'stock_code' => $p->stock_code,
            'price' => $price,
            'tax_id' => $p->tax_id,
            'warranty' => $p->warranty,
            'core_features' => $p->core_features,
            'capacity' => $p->capacity,
            'installation_charges' => $installationCharges,
            'installation_message' => $p->installation_message,
            'stock' => $p->stock,
            'is_active' => (bool) $p->is_active,
        ];
    }

    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dir' => ['sometimes', 'nullable', 'string', 'max:4'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;
        $page = is_int($validated['page'] ?? null) ? (int) $validated['page'] : null;
        $perPage = is_int($validated['per_page'] ?? null) ? (int) $validated['per_page'] : null;
        $sort = is_string($validated['sort'] ?? null) ? trim((string) $validated['sort']) : '';
        $dir = strtolower(is_string($validated['dir'] ?? null) ? trim((string) $validated['dir']) : '');

        $query = RepairBuddyPart::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $allowedSorts = ['id', 'name', 'sku', 'stock', 'is_active'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $resolvedDir = $dir === 'desc' ? 'desc' : 'asc';

        $query->orderBy($resolvedSort, $resolvedDir);

        if ($resolvedSort !== 'name') {
            $query->orderBy('name', 'asc');
        }

        if ($page !== null || $perPage !== null || $sort !== '' || $dir !== '') {
            $paginator = $query->paginate($perPage ?? 10, ['*'], 'page', $page ?? 1);

            return response()->json([
                'parts' => collect($paginator->items())->map(fn (RepairBuddyPart $p) => $this->serialize($p)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $parts = $query->limit($limit)->get();

        return response()->json([
            'parts' => $parts->map(fn (RepairBuddyPart $p) => $this->serialize($p)),
        ]);
    }

    public function show(Request $request, string $business, $partId)
    {
        if (! is_numeric($partId)) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $part = RepairBuddyPart::query()->whereKey((int) $partId)->first();

        if (! $part) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        return response()->json([
            'part' => $this->serialize($part),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],
            'part_type_id' => ['sometimes', 'nullable', 'integer'],
            'part_brand_id' => ['sometimes', 'nullable', 'integer'],
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
            'stock' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $priceCurrency = array_key_exists('price_currency', $validated) && is_string($validated['price_currency']) && $validated['price_currency'] !== ''
            ? strtoupper((string) $validated['price_currency'])
            : null;

        $hasPriceAmount = array_key_exists('price_amount_cents', $validated) && is_numeric($validated['price_amount_cents']);
        if ($hasPriceAmount && ($priceCurrency === null || $priceCurrency === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $priceCurrency = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        $installationCurrency = array_key_exists('installation_charges_currency', $validated)
            && is_string($validated['installation_charges_currency'])
            && $validated['installation_charges_currency'] !== ''
            ? strtoupper((string) $validated['installation_charges_currency'])
            : null;

        $hasInstallationAmount = array_key_exists('installation_charges_amount_cents', $validated)
            && is_numeric($validated['installation_charges_amount_cents']);
        if ($hasInstallationAmount && ($installationCurrency === null || $installationCurrency === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $installationCurrency = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        if (is_numeric($validated['part_type_id'] ?? null)) {
            $typeId = (int) $validated['part_type_id'];
            if (! RepairBuddyPartType::query()->whereKey($typeId)->exists()) {
                return response()->json([
                    'message' => 'Part type is invalid.',
                ], 422);
            }
        }

        if (is_numeric($validated['part_brand_id'] ?? null)) {
            $brandId = (int) $validated['part_brand_id'];
            if (! RepairBuddyPartBrand::query()->whereKey($brandId)->exists()) {
                return response()->json([
                    'message' => 'Part brand is invalid.',
                ], 422);
            }
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

        $part = RepairBuddyPart::query()->create([
            'name' => trim((string) $validated['name']),
            'sku' => $validated['sku'] ?? null,
            'part_type_id' => array_key_exists('part_type_id', $validated) ? $validated['part_type_id'] : null,
            'part_brand_id' => array_key_exists('part_brand_id', $validated) ? $validated['part_brand_id'] : null,
            'manufacturing_code' => $validated['manufacturing_code'] ?? null,
            'stock_code' => $validated['stock_code'] ?? null,
            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : null,
            'price_currency' => $priceCurrency,
            'tax_id' => $taxId,
            'warranty' => $validated['warranty'] ?? null,
            'core_features' => $validated['core_features'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'installation_charges_amount_cents' => array_key_exists('installation_charges_amount_cents', $validated) ? $validated['installation_charges_amount_cents'] : null,
            'installation_charges_currency' => $installationCurrency,
            'installation_message' => $validated['installation_message'] ?? null,
            'stock' => array_key_exists('stock', $validated) ? $validated['stock'] : null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part' => $this->serialize($part),
        ], 201);
    }

    public function update(Request $request, string $business, $partId)
    {
        if (! is_numeric($partId)) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $part = RepairBuddyPart::query()->whereKey((int) $partId)->first();

        if (! $part) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],
            'part_type_id' => ['sometimes', 'nullable', 'integer'],
            'part_brand_id' => ['sometimes', 'nullable', 'integer'],
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
            'stock' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        if (array_key_exists('part_type_id', $validated) && is_numeric($validated['part_type_id'])) {
            $typeId = (int) $validated['part_type_id'];
            if (! RepairBuddyPartType::query()->whereKey($typeId)->exists()) {
                return response()->json([
                    'message' => 'Part type is invalid.',
                ], 422);
            }
        }

        if (array_key_exists('part_brand_id', $validated) && is_numeric($validated['part_brand_id'])) {
            $brandId = (int) $validated['part_brand_id'];
            if (! RepairBuddyPartBrand::query()->whereKey($brandId)->exists()) {
                return response()->json([
                    'message' => 'Part brand is invalid.',
                ], 422);
            }
        }

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if (array_key_exists('tax_id', $validated) && $taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $priceAmountCentsNext = array_key_exists('price_amount_cents', $validated) ? ($validated['price_amount_cents'] ?? null) : $part->price_amount_cents;
        $priceCurrencyNext = $part->price_currency;

        if (array_key_exists('price_currency', $validated)) {
            $priceCurrencyNext = is_string($validated['price_currency']) && $validated['price_currency'] !== ''
                ? strtoupper((string) $validated['price_currency'])
                : null;
        } elseif ($priceAmountCentsNext !== null && (! is_string($priceCurrencyNext) || $priceCurrencyNext === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $priceCurrencyNext = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        $installationAmountCentsNext = array_key_exists('installation_charges_amount_cents', $validated)
            ? ($validated['installation_charges_amount_cents'] ?? null)
            : $part->installation_charges_amount_cents;
        $installationCurrencyNext = $part->installation_charges_currency;

        if (array_key_exists('installation_charges_currency', $validated)) {
            $installationCurrencyNext = is_string($validated['installation_charges_currency']) && $validated['installation_charges_currency'] !== ''
                ? strtoupper((string) $validated['installation_charges_currency'])
                : null;
        } elseif ($installationAmountCentsNext !== null && (! is_string($installationCurrencyNext) || $installationCurrencyNext === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $installationCurrencyNext = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        $part->forceFill([
            'name' => trim((string) $validated['name']),
            'sku' => array_key_exists('sku', $validated) ? ($validated['sku'] ?? null) : $part->sku,
            'part_type_id' => array_key_exists('part_type_id', $validated) ? ($validated['part_type_id'] ?? null) : $part->part_type_id,
            'part_brand_id' => array_key_exists('part_brand_id', $validated) ? ($validated['part_brand_id'] ?? null) : $part->part_brand_id,
            'manufacturing_code' => array_key_exists('manufacturing_code', $validated) ? ($validated['manufacturing_code'] ?? null) : $part->manufacturing_code,
            'stock_code' => array_key_exists('stock_code', $validated) ? ($validated['stock_code'] ?? null) : $part->stock_code,
            'price_amount_cents' => $priceAmountCentsNext,
            'price_currency' => $priceCurrencyNext,
            'tax_id' => array_key_exists('tax_id', $validated) ? $taxId : $part->tax_id,
            'warranty' => array_key_exists('warranty', $validated) ? ($validated['warranty'] ?? null) : $part->warranty,
            'core_features' => array_key_exists('core_features', $validated) ? ($validated['core_features'] ?? null) : $part->core_features,
            'capacity' => array_key_exists('capacity', $validated) ? ($validated['capacity'] ?? null) : $part->capacity,
            'installation_charges_amount_cents' => $installationAmountCentsNext,
            'installation_charges_currency' => $installationCurrencyNext,
            'installation_message' => array_key_exists('installation_message', $validated) ? ($validated['installation_message'] ?? null) : $part->installation_message,
            'stock' => array_key_exists('stock', $validated) ? ($validated['stock'] ?? null) : $part->stock,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $part->is_active,
        ])->save();

        return response()->json([
            'part' => $this->serialize($part->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $partId)
    {
        if (! is_numeric($partId)) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $part = RepairBuddyPart::query()->whereKey((int) $partId)->first();

        if (! $part) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $inUseByVariants = RepairBuddyPartVariant::query()->where('part_id', $part->id)->exists();
        $inUseByOverrides = RepairBuddyPartPriceOverride::query()->where('part_id', $part->id)->exists();
        $inUseByJobItems = RepairBuddyJobItem::query()
            ->where('item_type', 'part')
            ->where('ref_id', $part->id)
            ->exists();

        if ($inUseByVariants || $inUseByOverrides || $inUseByJobItems) {
            return response()->json([
                'message' => 'Part is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $part->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    public function resolvePrice(Request $request, string $business)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'integer'],
            'part_variant_id' => ['sometimes', 'nullable', 'integer'],
            'device_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $partId = (int) $validated['part_id'];
        $part = RepairBuddyPart::query()->whereKey($partId)->first();
        if (! $part) {
            return response()->json([
                'message' => 'Part not found.',
            ], 404);
        }

        $variantId = array_key_exists('part_variant_id', $validated) && is_numeric($validated['part_variant_id'])
            ? (int) $validated['part_variant_id']
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

        $deviceId = array_key_exists('device_id', $validated) && is_numeric($validated['device_id']) ? (int) $validated['device_id'] : null;
        $brandId = array_key_exists('device_brand_id', $validated) && is_numeric($validated['device_brand_id']) ? (int) $validated['device_brand_id'] : null;
        $typeId = array_key_exists('device_type_id', $validated) && is_numeric($validated['device_type_id']) ? (int) $validated['device_type_id'] : null;

        $override = null;
        $source = 'base';

        if ($deviceId) {
            $override = RepairBuddyPartPriceOverride::query()
                ->where('part_id', $part->id)
                ->where('part_variant_id', $variantId)
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
            $override = RepairBuddyPartPriceOverride::query()
                ->where('part_id', $part->id)
                ->where('part_variant_id', $variantId)
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
            $override = RepairBuddyPartPriceOverride::query()
                ->where('part_id', $part->id)
                ->where('part_variant_id', $variantId)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            if ($override) {
                $source = 'type';
            }
        }

        $base = $variant ?: $part;

        $resolvedPriceCents = $override && is_numeric($override->price_amount_cents)
            ? (int) $override->price_amount_cents
            : (is_numeric($base->price_amount_cents) ? (int) $base->price_amount_cents : null);

        $resolvedCurrency = $override && is_string($override->price_currency) && $override->price_currency !== ''
            ? (string) $override->price_currency
            : (is_string($base->price_currency) && $base->price_currency !== '' ? (string) $base->price_currency : null);

        $resolvedTaxId = $override && is_numeric($override->tax_id)
            ? (int) $override->tax_id
            : (is_numeric($base->tax_id) ? (int) $base->tax_id : null);

        $resolvedManufacturingCode = $override && is_string($override->manufacturing_code) && $override->manufacturing_code !== ''
            ? (string) $override->manufacturing_code
            : (is_string($base->manufacturing_code) ? (string) $base->manufacturing_code : null);

        $resolvedStockCode = $override && is_string($override->stock_code) && $override->stock_code !== ''
            ? (string) $override->stock_code
            : (is_string($base->stock_code) ? (string) $base->stock_code : null);

        $resolvedPrice = null;
        if ($resolvedPriceCents !== null && $resolvedCurrency !== null && $resolvedCurrency !== '') {
            $resolvedPrice = [
                'currency' => $resolvedCurrency,
                'amount_cents' => $resolvedPriceCents,
            ];
        }

        return response()->json([
            'part_id' => $part->id,
            'part_variant_id' => $variantId,
            'source' => $source,
            'resolved' => [
                'price' => $resolvedPrice,
                'tax_id' => $resolvedTaxId,
                'manufacturing_code' => $resolvedManufacturingCode,
                'stock_code' => $resolvedStockCode,
            ],
        ]);
    }

    
}

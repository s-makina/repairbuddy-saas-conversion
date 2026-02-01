<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyServiceType;
use App\Models\RepairBuddyTax;
use App\Models\RepairBuddyJobItem;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddyServiceController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyService::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $services = $query->limit($limit)->get();

        return response()->json([
            'services' => $services->map(fn (RepairBuddyService $s) => $this->serialize($s)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'service_type_id' => ['sometimes', 'nullable', 'integer'],
            'service_code' => ['sometimes', 'nullable', 'string', 'max:128'],
            'time_required' => ['sometimes', 'nullable', 'string', 'max:128'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pick_up_delivery_available' => ['sometimes', 'nullable', 'boolean'],
            'laptop_rental_available' => ['sometimes', 'nullable', 'boolean'],
            'base_price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'base_price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        if (is_numeric($validated['service_type_id'] ?? null)) {
            $typeId = (int) $validated['service_type_id'];
            if (! RepairBuddyServiceType::query()->whereKey($typeId)->exists()) {
                return response()->json([
                    'message' => 'Service type is invalid.',
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

        $baseCurrency = array_key_exists('base_price_currency', $validated) && is_string($validated['base_price_currency']) && $validated['base_price_currency'] !== ''
            ? strtoupper((string) $validated['base_price_currency'])
            : null;

        $hasBaseAmount = array_key_exists('base_price_amount_cents', $validated) && is_numeric($validated['base_price_amount_cents']);
        if ($hasBaseAmount && ($baseCurrency === null || $baseCurrency === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $baseCurrency = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        $service = RepairBuddyService::query()->create([
            'name' => trim((string) $validated['name']),
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : null,
            'service_type_id' => array_key_exists('service_type_id', $validated) ? ($validated['service_type_id'] ?? null) : null,
            'service_code' => array_key_exists('service_code', $validated) ? ($validated['service_code'] ?? null) : null,
            'time_required' => array_key_exists('time_required', $validated) ? ($validated['time_required'] ?? null) : null,
            'warranty' => array_key_exists('warranty', $validated) ? ($validated['warranty'] ?? null) : null,
            'pick_up_delivery_available' => array_key_exists('pick_up_delivery_available', $validated) ? (bool) $validated['pick_up_delivery_available'] : false,
            'laptop_rental_available' => array_key_exists('laptop_rental_available', $validated) ? (bool) $validated['laptop_rental_available'] : false,
            'base_price_amount_cents' => array_key_exists('base_price_amount_cents', $validated) ? ($validated['base_price_amount_cents'] ?? null) : null,
            'base_price_currency' => $baseCurrency,
            'tax_id' => $taxId,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'service' => $this->serialize($service),
        ], 201);
    }

    public function update(Request $request, string $business, $serviceId)
    {
        if (! is_numeric($serviceId)) {
            return response()->json([
                'message' => 'Service not found.',
            ], 404);
        }

        $service = RepairBuddyService::query()->whereKey((int) $serviceId)->first();

        if (! $service) {
            return response()->json([
                'message' => 'Service not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'service_type_id' => ['sometimes', 'nullable', 'integer'],
            'service_code' => ['sometimes', 'nullable', 'string', 'max:128'],
            'time_required' => ['sometimes', 'nullable', 'string', 'max:128'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pick_up_delivery_available' => ['sometimes', 'nullable', 'boolean'],
            'laptop_rental_available' => ['sometimes', 'nullable', 'boolean'],
            'base_price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'base_price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        if (array_key_exists('service_type_id', $validated) && is_numeric($validated['service_type_id'])) {
            $typeId = (int) $validated['service_type_id'];
            if (! RepairBuddyServiceType::query()->whereKey($typeId)->exists()) {
                return response()->json([
                    'message' => 'Service type is invalid.',
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

        $baseAmountNext = array_key_exists('base_price_amount_cents', $validated) ? ($validated['base_price_amount_cents'] ?? null) : $service->base_price_amount_cents;
        $baseCurrencyNext = $service->base_price_currency;

        if (array_key_exists('base_price_currency', $validated)) {
            $baseCurrencyNext = is_string($validated['base_price_currency']) && $validated['base_price_currency'] !== ''
                ? strtoupper((string) $validated['base_price_currency'])
                : null;
        } elseif ($baseAmountNext !== null && (! is_string($baseCurrencyNext) || $baseCurrencyNext === '')) {
            $tenantCurrency = TenantContext::tenant()?->currency;
            if (is_string($tenantCurrency) && $tenantCurrency !== '') {
                $baseCurrencyNext = strtoupper($tenantCurrency);
            } else {
                return response()->json([
                    'message' => 'Tenant currency is not configured.',
                ], 422);
            }
        }

        $service->forceFill([
            'name' => trim((string) $validated['name']),
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : $service->description,
            'service_type_id' => array_key_exists('service_type_id', $validated) ? ($validated['service_type_id'] ?? null) : $service->service_type_id,
            'service_code' => array_key_exists('service_code', $validated) ? ($validated['service_code'] ?? null) : $service->service_code,
            'time_required' => array_key_exists('time_required', $validated) ? ($validated['time_required'] ?? null) : $service->time_required,
            'warranty' => array_key_exists('warranty', $validated) ? ($validated['warranty'] ?? null) : $service->warranty,
            'pick_up_delivery_available' => array_key_exists('pick_up_delivery_available', $validated) ? (bool) $validated['pick_up_delivery_available'] : $service->pick_up_delivery_available,
            'laptop_rental_available' => array_key_exists('laptop_rental_available', $validated) ? (bool) $validated['laptop_rental_available'] : $service->laptop_rental_available,
            'base_price_amount_cents' => $baseAmountNext,
            'base_price_currency' => $baseCurrencyNext,
            'tax_id' => array_key_exists('tax_id', $validated) ? $taxId : $service->tax_id,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $service->is_active,
        ])->save();

        return response()->json([
            'service' => $this->serialize($service->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $serviceId)
    {
        if (! is_numeric($serviceId)) {
            return response()->json([
                'message' => 'Service not found.',
            ], 404);
        }

        $service = RepairBuddyService::query()->whereKey((int) $serviceId)->first();

        if (! $service) {
            return response()->json([
                'message' => 'Service not found.',
            ], 404);
        }

        $inUseByOverrides = RepairBuddyServicePriceOverride::query()->where('service_id', $service->id)->exists();
        $inUseByJobItems = RepairBuddyJobItem::query()
            ->where('item_type', 'service')
            ->where('ref_id', $service->id)
            ->exists();

        if ($inUseByOverrides || $inUseByJobItems) {
            return response()->json([
                'message' => 'Service is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $service->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    private function serialize(RepairBuddyService $s): array
    {
        $basePrice = null;
        if (is_numeric($s->base_price_amount_cents) && is_string($s->base_price_currency) && $s->base_price_currency !== '') {
            $basePrice = [
                'currency' => $s->base_price_currency,
                'amount_cents' => (int) $s->base_price_amount_cents,
            ];
        }

        return [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'service_type_id' => $s->service_type_id,
            'service_code' => $s->service_code,
            'time_required' => $s->time_required,
            'warranty' => $s->warranty,
            'pick_up_delivery_available' => (bool) $s->pick_up_delivery_available,
            'laptop_rental_available' => (bool) $s->laptop_rental_available,
            'base_price' => $basePrice,
            'tax_id' => $s->tax_id,
            'is_active' => (bool) $s->is_active,
        ];
    }
}

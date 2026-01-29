<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use App\Models\RepairBuddyPartType;
use Illuminate\Http\Request;

class RepairBuddyPartController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyPart::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $parts = $query->limit($limit)->get();

        return response()->json([
            'parts' => $parts->map(fn (RepairBuddyPart $p) => $this->serialize($p)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],
            'part_type_id' => ['sometimes', 'nullable', 'integer'],
            'part_brand_id' => ['sometimes', 'nullable', 'integer'],
            'price_amount_cents' => ['sometimes', 'nullable', 'integer'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'stock' => ['sometimes', 'nullable', 'integer'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

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

        $part = RepairBuddyPart::query()->create([
            'name' => trim((string) $validated['name']),
            'sku' => $validated['sku'] ?? null,
            'part_type_id' => array_key_exists('part_type_id', $validated) ? $validated['part_type_id'] : null,
            'part_brand_id' => array_key_exists('part_brand_id', $validated) ? $validated['part_brand_id'] : null,
            'price_amount_cents' => array_key_exists('price_amount_cents', $validated) ? $validated['price_amount_cents'] : null,
            'price_currency' => array_key_exists('price_currency', $validated) ? $validated['price_currency'] : null,
            'stock' => array_key_exists('stock', $validated) ? $validated['stock'] : null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part' => $this->serialize($part),
        ], 201);
    }

    private function serialize(RepairBuddyPart $p): array
    {
        $price = null;
        if (is_numeric($p->price_amount_cents) && is_string($p->price_currency) && $p->price_currency !== '') {
            $price = [
                'currency' => $p->price_currency,
                'amount_cents' => (int) $p->price_amount_cents,
            ];
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => $price,
            'stock' => $p->stock,
            'is_active' => (bool) $p->is_active,
        ];
    }
}

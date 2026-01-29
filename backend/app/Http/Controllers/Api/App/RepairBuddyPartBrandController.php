<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPartBrand;
use Illuminate\Http\Request;

class RepairBuddyPartBrandController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyPartBrand::query()->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $brands = $query->limit($limit)->get();

        return response()->json([
            'part_brands' => $brands->map(fn (RepairBuddyPartBrand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'image_path' => $b->image_path,
                'is_active' => (bool) $b->is_active,
            ]),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $brand = RepairBuddyPartBrand::query()->create([
            'name' => trim((string) $validated['name']),
            'image_path' => $validated['image_path'] ?? null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part_brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'image_path' => $brand->image_path,
                'is_active' => (bool) $brand->is_active,
            ],
        ], 201);
    }
}

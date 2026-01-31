<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairBuddyPartBrandController extends Controller
{
    protected function serializeBrand(RepairBuddyPartBrand $b): array
    {
        return [
            'id' => $b->id,
            'name' => $b->name,
            'description' => $b->description,
            'image_url' => $b->image_url,
            'is_active' => (bool) $b->is_active,
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

        $query = RepairBuddyPartBrand::query();

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $allowedSorts = ['id', 'name', 'is_active'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $resolvedDir = $dir === 'desc' ? 'desc' : 'asc';

        $query->orderBy($resolvedSort, $resolvedDir);

        if ($resolvedSort !== 'name') {
            $query->orderBy('name', 'asc');
        }

        if ($page !== null || $perPage !== null || $sort !== '' || $dir !== '') {
            $paginator = $query->paginate($perPage ?? 10, ['*'], 'page', $page ?? 1);

            return response()->json([
                'part_brands' => collect($paginator->items())->map(fn (RepairBuddyPartBrand $b) => $this->serializeBrand($b)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $brands = $query->limit($limit)->get();

        return response()->json([
            'part_brands' => $brands->map(fn (RepairBuddyPartBrand $b) => $this->serializeBrand($b)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyPartBrand::query()->where('name', $name)->exists()) {
            return response()->json([
                'message' => 'Part brand already exists.',
            ], 422);
        }

        $brand = RepairBuddyPartBrand::query()->create([
            'name' => $name,
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part_brand' => $this->serializeBrand($brand),
        ], 201);
    }

    public function update(Request $request, string $business, $brandId)
    {
        if (! is_numeric($brandId)) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $brand = RepairBuddyPartBrand::query()->whereKey((int) $brandId)->first();

        if (! $brand) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyPartBrand::query()
            ->where('name', $name)
            ->where('id', '!=', $brand->id)
            ->exists()) {
            return response()->json([
                'message' => 'Part brand already exists.',
            ], 422);
        }

        $brand->forceFill([
            'name' => $name,
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : $brand->description,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $brand->is_active,
        ])->save();

        return response()->json([
            'part_brand' => $this->serializeBrand($brand),
        ]);
    }

    public function uploadImage(Request $request, string $business, $brandId)
    {
        if (! is_numeric($brandId)) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $brand = RepairBuddyPartBrand::query()->whereKey((int) $brandId)->first();

        if (! $brand) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (is_string($brand->image_path) && $brand->image_path !== '') {
            Storage::disk('public')->delete($brand->image_path);
        }

        $file = $validated['image'];
        $path = $file->storePublicly('rb-part-brands/'.$brand->id, ['disk' => 'public']);

        $brand->forceFill([
            'image_path' => $path,
        ])->save();

        return response()->json([
            'part_brand' => $this->serializeBrand($brand->fresh()),
        ]);
    }

    public function deleteImage(Request $request, string $business, $brandId)
    {
        if (! is_numeric($brandId)) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $brand = RepairBuddyPartBrand::query()->whereKey((int) $brandId)->first();

        if (! $brand) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        if (is_string($brand->image_path) && $brand->image_path !== '') {
            Storage::disk('public')->delete($brand->image_path);
        }

        $brand->forceFill([
            'image_path' => null,
        ])->save();

        return response()->json([
            'part_brand' => $this->serializeBrand($brand->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $brandId)
    {
        if (! is_numeric($brandId)) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $brand = RepairBuddyPartBrand::query()->whereKey((int) $brandId)->first();

        if (! $brand) {
            return response()->json([
                'message' => 'Part brand not found.',
            ], 404);
        }

        $inUseByParts = RepairBuddyPart::query()->where('part_brand_id', $brand->id)->exists();

        if ($inUseByParts) {
            return response()->json([
                'message' => 'Part brand is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        if (is_string($brand->image_path) && $brand->image_path !== '') {
            Storage::disk('public')->delete($brand->image_path);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

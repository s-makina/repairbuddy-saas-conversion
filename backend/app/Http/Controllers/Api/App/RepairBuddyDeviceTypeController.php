<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyPartPriceOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairBuddyDeviceTypeController extends Controller
{
    protected function serializeType(RepairBuddyDeviceType $t): array
    {
        return [
            'id' => $t->id,
            'parent_id' => $t->parent_id,
            'name' => $t->name,
            'description' => $t->description,
            'image_url' => $t->image_url,
            'is_active' => (bool) $t->is_active,
        ];
    }

    protected function ensureValidParent(?int $parentId, ?RepairBuddyDeviceType $current = null): ?int
    {
        if (! $parentId || $parentId <= 0) {
            return null;
        }

        if ($current && (int) $current->id === $parentId) {
            abort(response()->json([
                'message' => 'Device type parent cannot be itself.',
            ], 422));
        }

        $parent = RepairBuddyDeviceType::query()->whereKey($parentId)->first();
        if (! $parent) {
            abort(response()->json([
                'message' => 'Parent device type not found.',
            ], 422));
        }

        if ($current) {
            $cursor = $parent;
            $guard = 0;
            while ($cursor && $guard < 50) {
                $guard++;
                if ((int) $cursor->id === (int) $current->id) {
                    abort(response()->json([
                        'message' => 'Device type parent cannot create a cycle.',
                    ], 422));
                }
                $nextParentId = is_numeric($cursor->parent_id ?? null) ? (int) $cursor->parent_id : null;
                if (! $nextParentId) {
                    break;
                }
                $cursor = RepairBuddyDeviceType::query()->whereKey($nextParentId)->first();
            }
        }

        return $parentId;
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

        $query = RepairBuddyDeviceType::query();

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
                'device_types' => collect($paginator->items())->map(fn (RepairBuddyDeviceType $t) => $this->serializeType($t)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $types = $query->limit($limit)->get();

        return response()->json([
            'device_types' => $types->map(fn (RepairBuddyDeviceType $t) => $this->serializeType($t)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);
        $parentId = $this->ensureValidParent(array_key_exists('parent_id', $validated) ? (is_numeric($validated['parent_id']) ? (int) $validated['parent_id'] : null) : null);

        if (RepairBuddyDeviceType::query()->where('name', $name)->exists()) {
            return response()->json([
                'message' => 'Device type already exists.',
            ], 422);
        }

        $type = RepairBuddyDeviceType::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'device_type' => $this->serializeType($type),
        ], 201);
    }

    public function update(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $type = RepairBuddyDeviceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        $parentId = $this->ensureValidParent(
            array_key_exists('parent_id', $validated)
                ? (is_numeric($validated['parent_id']) ? (int) $validated['parent_id'] : null)
                : $type->parent_id,
            $type
        );

        if (RepairBuddyDeviceType::query()
            ->where('name', $name)
            ->where('id', '!=', $type->id)
            ->exists()) {
            return response()->json([
                'message' => 'Device type already exists.',
            ], 422);
        }

        $type->forceFill([
            'parent_id' => $parentId,
            'name' => $name,
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : $type->description,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $type->is_active,
        ])->save();

        return response()->json([
            'device_type' => $this->serializeType($type),
        ]);
    }

    public function uploadImage(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $type = RepairBuddyDeviceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (is_string($type->image_path) && $type->image_path !== '') {
            Storage::disk('public')->delete($type->image_path);
        }

        $file = $validated['image'];
        $path = $file->storePublicly('rb-device-types/'.$type->id, ['disk' => 'public']);

        $type->forceFill([
            'image_path' => $path,
        ])->save();

        return response()->json([
            'device_type' => $this->serializeType($type->fresh()),
        ]);
    }

    public function deleteImage(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $type = RepairBuddyDeviceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        if (is_string($type->image_path) && $type->image_path !== '') {
            Storage::disk('public')->delete($type->image_path);
        }

        $type->forceFill([
            'image_path' => null,
        ])->save();

        return response()->json([
            'device_type' => $this->serializeType($type->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $type = RepairBuddyDeviceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Device type not found.',
            ], 404);
        }

        $inUseByDevices = RepairBuddyDevice::query()->where('device_type_id', $type->id)->exists();
        $inUseByOverrides = RepairBuddyPartPriceOverride::query()
            ->where('scope_type', 'type')
            ->where('scope_ref_id', $type->id)
            ->exists();

        $inUseAsParent = RepairBuddyDeviceType::query()->where('parent_id', $type->id)->exists();

        if ($inUseByDevices || $inUseByOverrides || $inUseAsParent) {
            return response()->json([
                'message' => 'Device type is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        if (is_string($type->image_path) && $type->image_path !== '') {
            Storage::disk('public')->delete($type->image_path);
        }

        $type->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

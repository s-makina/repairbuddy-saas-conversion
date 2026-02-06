<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyPartPriceOverride;
use App\Models\RepairBuddyServicePriceOverride;
use Illuminate\Http\Request;

class RepairBuddyDeviceController extends Controller
{
    protected function serializeDevice(RepairBuddyDevice $d): array
    {
        return [
            'id' => $d->id,
            'model' => $d->model,
            'device_type_id' => $d->device_type_id,
            'device_brand_id' => $d->device_brand_id,
            'parent_device_id' => $d->parent_device_id,
            'disable_in_booking_form' => (bool) $d->disable_in_booking_form,
            'is_other' => (bool) $d->is_other,
            'is_active' => (bool) $d->is_active,
        ];
    }

    protected function ensureValidParent(?int $parentId, ?RepairBuddyDevice $current = null): ?int
    {
        if (! $parentId || $parentId <= 0) {
            return null;
        }

        if ($current && (int) $current->id === $parentId) {
            abort(response()->json([
                'message' => 'Device parent cannot be itself.',
            ], 422));
        }

        $parent = RepairBuddyDevice::query()->whereKey($parentId)->first();
        if (! $parent) {
            abort(response()->json([
                'message' => 'Parent device not found.',
            ], 422));
        }

        if ($current) {
            $cursor = $parent;
            $guard = 0;
            while ($cursor && $guard < 50) {
                $guard++;
                if ((int) $cursor->id === (int) $current->id) {
                    abort(response()->json([
                        'message' => 'Device parent cannot create a cycle.',
                    ], 422));
                }
                $nextParentId = is_numeric($cursor->parent_device_id ?? null) ? (int) $cursor->parent_device_id : null;
                if (! $nextParentId) {
                    break;
                }
                $cursor = RepairBuddyDevice::query()->whereKey($nextParentId)->first();
            }
        }

        return $parentId;
    }

    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dir' => ['sometimes', 'nullable', 'string', 'max:4'],
            'for_booking' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $isActive = array_key_exists('is_active', $validated) ? ($validated['is_active'] ?? null) : null;
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;
        $page = is_int($validated['page'] ?? null) ? (int) $validated['page'] : null;
        $perPage = is_int($validated['per_page'] ?? null) ? (int) $validated['per_page'] : null;
        $sort = is_string($validated['sort'] ?? null) ? trim((string) $validated['sort']) : '';
        $dir = strtolower(is_string($validated['dir'] ?? null) ? trim((string) $validated['dir']) : '');
        $forBooking = array_key_exists('for_booking', $validated) ? (bool) $validated['for_booking'] : false;

        $query = RepairBuddyDevice::query();

        if ($forBooking) {
            $query->where('is_active', true)->where('disable_in_booking_form', false);
        } elseif ($isActive !== null) {
            $query->where('is_active', (bool) $isActive);
        }

        if ($q !== '') {
            $query->where('model', 'like', "%{$q}%");
        }

        $allowedSorts = ['id', 'model', 'is_active'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : 'model';
        $resolvedDir = $dir === 'desc' ? 'desc' : 'asc';

        $query->orderBy($resolvedSort, $resolvedDir);

        if ($resolvedSort !== 'model') {
            $query->orderBy('model', 'asc');
        }

        if ($page !== null || $perPage !== null || $sort !== '' || $dir !== '') {
            $paginator = $query->paginate($perPage ?? 10, ['*'], 'page', $page ?? 1);

            return response()->json([
                'devices' => collect($paginator->items())->map(fn (RepairBuddyDevice $d) => $this->serializeDevice($d)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $devices = $query->limit($limit)->get();

        return response()->json([
            'devices' => $devices->map(fn (RepairBuddyDevice $d) => $this->serializeDevice($d)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'device_type_id' => ['required', 'integer'],
            'device_brand_id' => ['required', 'integer'],
            'parent_device_id' => ['sometimes', 'nullable', 'integer'],
            'disable_in_booking_form' => ['sometimes', 'nullable', 'boolean'],
            'is_other' => ['sometimes', 'nullable', 'boolean'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $typeId = (int) $validated['device_type_id'];
        $brandId = (int) $validated['device_brand_id'];

        $parentId = $this->ensureValidParent(
            array_key_exists('parent_device_id', $validated) && is_numeric($validated['parent_device_id'])
                ? (int) $validated['parent_device_id']
                : null
        );

        if (! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return response()->json([
                'message' => 'Device type is invalid.',
            ], 422);
        }

        if (! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return response()->json([
                'message' => 'Device brand is invalid.',
            ], 422);
        }

        $device = RepairBuddyDevice::query()->create([
            'model' => trim((string) $validated['model']),
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'parent_device_id' => $parentId,
            'disable_in_booking_form' => array_key_exists('disable_in_booking_form', $validated) ? (bool) $validated['disable_in_booking_form'] : false,
            'is_other' => array_key_exists('is_other', $validated) ? (bool) $validated['is_other'] : false,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'device' => [
                'id' => $device->id,
                'model' => $device->model,
                'device_type_id' => $device->device_type_id,
                'device_brand_id' => $device->device_brand_id,
                'parent_device_id' => $device->parent_device_id,
                'disable_in_booking_form' => (bool) $device->disable_in_booking_form,
                'is_other' => (bool) $device->is_other,
                'is_active' => (bool) $device->is_active,
            ],
        ], 201);
    }

    public function update(Request $request, string $business, $deviceId)
    {
        if (! is_numeric($deviceId)) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $device = RepairBuddyDevice::query()->whereKey((int) $deviceId)->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'device_type_id' => ['required', 'integer'],
            'device_brand_id' => ['required', 'integer'],
            'parent_device_id' => ['sometimes', 'nullable', 'integer'],
            'disable_in_booking_form' => ['sometimes', 'nullable', 'boolean'],
            'is_other' => ['sometimes', 'nullable', 'boolean'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $typeId = (int) $validated['device_type_id'];
        $brandId = (int) $validated['device_brand_id'];

        if (! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return response()->json([
                'message' => 'Device type is invalid.',
            ], 422);
        }

        if (! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return response()->json([
                'message' => 'Device brand is invalid.',
            ], 422);
        }

        $parentId = $this->ensureValidParent(
            array_key_exists('parent_device_id', $validated)
                ? (is_numeric($validated['parent_device_id']) ? (int) $validated['parent_device_id'] : null)
                : $device->parent_device_id,
            $device
        );

        $device->forceFill([
            'model' => trim((string) $validated['model']),
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'parent_device_id' => $parentId,
            'disable_in_booking_form' => array_key_exists('disable_in_booking_form', $validated) ? (bool) $validated['disable_in_booking_form'] : $device->disable_in_booking_form,
            'is_other' => array_key_exists('is_other', $validated) ? (bool) $validated['is_other'] : $device->is_other,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $device->is_active,
        ])->save();

        $fresh = $device->fresh();

        return response()->json([
            'device' => [
                'id' => $fresh->id,
                'model' => $fresh->model,
                'device_type_id' => $fresh->device_type_id,
                'device_brand_id' => $fresh->device_brand_id,
                'parent_device_id' => $fresh->parent_device_id,
                'disable_in_booking_form' => (bool) $fresh->disable_in_booking_form,
                'is_other' => (bool) $fresh->is_other,
                'is_active' => (bool) $fresh->is_active,
            ],
        ]);
    }

    public function destroy(Request $request, string $business, $deviceId)
    {
        if (! is_numeric($deviceId)) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $device = RepairBuddyDevice::query()->whereKey((int) $deviceId)->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $inUseByCustomerDevices = RepairBuddyCustomerDevice::query()->where('device_id', $device->id)->exists();
        $inUseByPriceOverrides = RepairBuddyPartPriceOverride::query()
            ->where('scope_type', 'device')
            ->where('scope_ref_id', $device->id)
            ->exists();
        $inUseByServiceOverrides = RepairBuddyServicePriceOverride::query()
            ->where('scope_type', 'device')
            ->where('scope_ref_id', $device->id)
            ->exists();
        $hasChildren = RepairBuddyDevice::query()->where('parent_device_id', $device->id)->exists();

        if ($inUseByCustomerDevices || $inUseByPriceOverrides || $inUseByServiceOverrides || $hasChildren) {
            return response()->json([
                'message' => 'Device is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $device->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    public function createVariations(Request $request, string $business, $deviceId)
    {
        if (! is_numeric($deviceId)) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $parent = RepairBuddyDevice::query()->whereKey((int) $deviceId)->first();

        if (! $parent) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $validated = $request->validate([
            'names' => ['required', 'array', 'min:1', 'max:50'],
            'names.*' => ['required', 'string', 'max:100'],
            'joiner' => ['sometimes', 'nullable', 'string', 'max:10'],
            'prefix_parent_model' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $joiner = array_key_exists('joiner', $validated) && is_string($validated['joiner']) ? (string) $validated['joiner'] : ' - ';
        $prefixParent = array_key_exists('prefix_parent_model', $validated) ? (bool) $validated['prefix_parent_model'] : true;

        $created = [];
        $skipped = [];

        foreach ($validated['names'] as $raw) {
            $name = trim((string) $raw);
            if ($name === '') {
                continue;
            }

            $model = $prefixParent ? trim($parent->model.$joiner.$name) : $name;

            if (strlen($model) > 255) {
                $skipped[] = $model;
                continue;
            }

            $exists = RepairBuddyDevice::query()
                ->where('device_type_id', $parent->device_type_id)
                ->where('device_brand_id', $parent->device_brand_id)
                ->where('model', $model)
                ->exists();

            if ($exists) {
                $skipped[] = $model;
                continue;
            }

            $d = RepairBuddyDevice::query()->create([
                'model' => $model,
                'device_type_id' => $parent->device_type_id,
                'device_brand_id' => $parent->device_brand_id,
                'parent_device_id' => $parent->id,
                'disable_in_booking_form' => (bool) $parent->disable_in_booking_form,
                'is_other' => (bool) $parent->is_other,
                'is_active' => (bool) $parent->is_active,
            ]);

            $created[] = [
                'id' => $d->id,
                'model' => $d->model,
                'device_type_id' => $d->device_type_id,
                'device_brand_id' => $d->device_brand_id,
                'parent_device_id' => $d->parent_device_id,
                'disable_in_booking_form' => (bool) $d->disable_in_booking_form,
                'is_other' => (bool) $d->is_other,
                'is_active' => (bool) $d->is_active,
            ];
        }

        return response()->json([
            'created' => $created,
            'skipped' => $skipped,
        ], 201);
    }
}

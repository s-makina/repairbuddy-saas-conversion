<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use Illuminate\Http\Request;

class RepairBuddyDeviceController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'for_booking' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;
        $forBooking = array_key_exists('for_booking', $validated) ? (bool) $validated['for_booking'] : false;

        $query = RepairBuddyDevice::query()->orderBy('model');

        if ($forBooking) {
            $query->where('is_active', true)->where('disable_in_booking_form', false);
        }

        if ($q !== '') {
            $query->where('model', 'like', "%{$q}%");
        }

        $devices = $query->limit($limit)->get();

        return response()->json([
            'devices' => $devices->map(fn (RepairBuddyDevice $d) => [
                'id' => $d->id,
                'model' => $d->model,
                'device_type_id' => $d->device_type_id,
                'device_brand_id' => $d->device_brand_id,
                'parent_device_id' => $d->parent_device_id,
                'disable_in_booking_form' => (bool) $d->disable_in_booking_form,
                'is_other' => (bool) $d->is_other,
                'is_active' => (bool) $d->is_active,
            ]),
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

        $parentId = array_key_exists('parent_device_id', $validated) && is_numeric($validated['parent_device_id'])
            ? (int) $validated['parent_device_id']
            : null;

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

        if ($parentId && ! RepairBuddyDevice::query()->whereKey($parentId)->exists()) {
            return response()->json([
                'message' => 'Parent device is invalid.',
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
}

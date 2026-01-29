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
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyDevice::query()->orderBy('model');

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

        $device = RepairBuddyDevice::query()->create([
            'model' => trim((string) $validated['model']),
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'device' => [
                'id' => $device->id,
                'model' => $device->model,
                'device_type_id' => $device->device_type_id,
                'device_brand_id' => $device->device_brand_id,
                'is_active' => (bool) $device->is_active,
            ],
        ], 201);
    }
}

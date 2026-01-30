<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyPartPriceOverride;
use Illuminate\Http\Request;

class RepairBuddyDeviceTypeController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyDeviceType::query()->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $types = $query->limit($limit)->get();

        return response()->json([
            'device_types' => $types->map(fn (RepairBuddyDeviceType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'is_active' => (bool) $t->is_active,
            ]),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyDeviceType::query()->where('name', $name)->exists()) {
            return response()->json([
                'message' => 'Device type already exists.',
            ], 422);
        }

        $type = RepairBuddyDeviceType::query()->create([
            'name' => $name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'device_type' => [
                'id' => $type->id,
                'name' => $type->name,
                'is_active' => (bool) $type->is_active,
            ],
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
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyDeviceType::query()
            ->where('name', $name)
            ->where('id', '!=', $type->id)
            ->exists()) {
            return response()->json([
                'message' => 'Device type already exists.',
            ], 422);
        }

        $type->forceFill([
            'name' => $name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $type->is_active,
        ])->save();

        return response()->json([
            'device_type' => [
                'id' => $type->id,
                'name' => $type->name,
                'is_active' => (bool) $type->is_active,
            ],
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

        if ($inUseByDevices || $inUseByOverrides) {
            return response()->json([
                'message' => 'Device type is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $type->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\User;
use Illuminate\Http\Request;

class RepairBuddyCustomerDeviceController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyCustomerDevice::query()->orderBy('id', 'desc');

        if (is_numeric($validated['customer_id'] ?? null)) {
            $query->where('customer_id', (int) $validated['customer_id']);
        }

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('label', 'like', $like)
                    ->orWhere('serial', 'like', $like)
                    ->orWhere('id', $q);
            });
        }

        $devices = $query->limit($limit)->get();

        return response()->json([
            'customer_devices' => $devices->map(function (RepairBuddyCustomerDevice $d) {
                return $this->serialize($d);
            }),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'device_id' => ['sometimes', 'nullable', 'integer'],
            'label' => ['required', 'string', 'max:255'],
            'serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $customerId = (int) $validated['customer_id'];

        $customer = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_admin', false)
            ->whereKey($customerId)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer is invalid.',
            ], 422);
        }

        $deviceId = array_key_exists('device_id', $validated) && is_numeric($validated['device_id']) ? (int) $validated['device_id'] : null;

        if ($deviceId && ! RepairBuddyDevice::query()->whereKey($deviceId)->exists()) {
            return response()->json([
                'message' => 'Device is invalid.',
            ], 422);
        }

        $cd = RepairBuddyCustomerDevice::query()->create([
            'customer_id' => $customerId,
            'device_id' => $deviceId,
            'label' => trim((string) $validated['label']),
            'serial' => array_key_exists('serial', $validated) ? (is_string($validated['serial']) ? trim((string) $validated['serial']) : null) : null,
            'pin' => array_key_exists('pin', $validated) ? (is_string($validated['pin']) ? trim((string) $validated['pin']) : null) : null,
            'notes' => array_key_exists('notes', $validated) ? (is_string($validated['notes']) ? trim((string) $validated['notes']) : null) : null,
        ]);

        return response()->json([
            'customer_device' => $this->serialize($cd),
        ], 201);
    }

    private function serialize(RepairBuddyCustomerDevice $d): array
    {
        return [
            'id' => $d->id,
            'customer_id' => $d->customer_id,
            'device_id' => $d->device_id,
            'label' => $d->label,
            'serial' => $d->serial,
            'pin' => $d->pin,
            'notes' => $d->notes,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use Illuminate\Http\Request;

class RepairBuddyEstimateDeviceController extends Controller
{
    public function index(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $items = RepairBuddyEstimateDevice::query()
            ->where('estimate_id', $estimate->id)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'estimate_devices' => $items->map(function (RepairBuddyEstimateDevice $ed) {
                return [
                    'id' => $ed->id,
                    'estimate_id' => $ed->estimate_id,
                    'customer_device_id' => $ed->customer_device_id,
                    'label' => $ed->label_snapshot,
                    'serial' => $ed->serial_snapshot,
                    'pin' => $ed->pin_snapshot,
                    'notes' => $ed->notes_snapshot,
                    'extra_fields' => is_array($ed->extra_fields_snapshot_json) ? $ed->extra_fields_snapshot_json : null,
                    'created_at' => $ed->created_at,
                ];
            }),
        ]);
    }

    public function store(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $validated = $request->validate([
            'customer_device_id' => ['required', 'integer'],
        ]);

        $customerDeviceId = (int) $validated['customer_device_id'];

        $cd = RepairBuddyCustomerDevice::query()
            ->whereKey($customerDeviceId)
            ->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device is invalid.',
            ], 422);
        }

        if (! $estimate->customer_id) {
            return response()->json([
                'message' => 'Estimate customer is required to attach devices.',
            ], 422);
        }

        if ((int) $cd->customer_id !== (int) $estimate->customer_id) {
            return response()->json([
                'message' => 'Customer device does not belong to the estimate customer.',
            ], 422);
        }

        $existing = RepairBuddyEstimateDevice::query()
            ->where('estimate_id', $estimate->id)
            ->where('customer_device_id', $cd->id)
            ->first();

        if ($existing) {
            return response()->json([
                'estimate_device' => [
                    'id' => $existing->id,
                    'estimate_id' => $existing->estimate_id,
                    'customer_device_id' => $existing->customer_device_id,
                    'label' => $existing->label_snapshot,
                    'serial' => $existing->serial_snapshot,
                    'pin' => $existing->pin_snapshot,
                    'notes' => $existing->notes_snapshot,
                    'extra_fields' => is_array($existing->extra_fields_snapshot_json) ? $existing->extra_fields_snapshot_json : null,
                    'created_at' => $existing->created_at,
                ],
            ], 200);
        }

        $definitions = RepairBuddyDeviceFieldDefinition::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        $values = RepairBuddyCustomerDeviceFieldValue::query()
            ->where('customer_device_id', $cd->id)
            ->get()
            ->keyBy('field_definition_id');

        $extraFieldsSnapshot = [];
        foreach ($definitions as $def) {
            $value = $values->get($def->id);
            if (! $value) {
                continue;
            }
            $rawText = is_string($value->value_text) ? trim((string) $value->value_text) : '';
            if ($rawText === '') {
                continue;
            }
            $extraFieldsSnapshot[] = [
                'key' => $def->key,
                'label' => $def->label,
                'type' => $def->type,
                'show_in_booking' => (bool) $def->show_in_booking,
                'show_in_invoice' => (bool) $def->show_in_invoice,
                'show_in_portal' => (bool) $def->show_in_portal,
                'value_text' => $rawText,
            ];
        }

        $ed = RepairBuddyEstimateDevice::query()->create([
            'estimate_id' => $estimate->id,
            'customer_device_id' => $cd->id,
            'label_snapshot' => $cd->label,
            'serial_snapshot' => $cd->serial,
            'pin_snapshot' => $cd->pin,
            'notes_snapshot' => $cd->notes,
            'extra_fields_snapshot_json' => $extraFieldsSnapshot,
        ]);

        return response()->json([
            'estimate_device' => [
                'id' => $ed->id,
                'estimate_id' => $ed->estimate_id,
                'customer_device_id' => $ed->customer_device_id,
                'label' => $ed->label_snapshot,
                'serial' => $ed->serial_snapshot,
                'pin' => $ed->pin_snapshot,
                'notes' => $ed->notes_snapshot,
                'extra_fields' => is_array($ed->extra_fields_snapshot_json) ? $ed->extra_fields_snapshot_json : null,
                'created_at' => $ed->created_at,
            ],
        ], 201);
    }

    public function destroy(Request $request, string $business, $estimateId, $estimateDeviceId)
    {
        if (! is_numeric($estimateId) || ! is_numeric($estimateDeviceId)) {
            return response()->json([
                'message' => 'Estimate device not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $ed = RepairBuddyEstimateDevice::query()
            ->whereKey((int) $estimateDeviceId)
            ->where('estimate_id', $estimate->id)
            ->first();

        if (! $ed) {
            return response()->json([
                'message' => 'Estimate device not found.',
            ], 404);
        }

        $ed->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

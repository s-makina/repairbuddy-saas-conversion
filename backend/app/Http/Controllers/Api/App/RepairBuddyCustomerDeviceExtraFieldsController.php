<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use Illuminate\Http\Request;

class RepairBuddyCustomerDeviceExtraFieldsController extends Controller
{
    private function serializeRow(RepairBuddyDeviceFieldDefinition $d, ?RepairBuddyCustomerDeviceFieldValue $v): array
    {
        return [
            'field_definition_id' => $d->id,
            'key' => $d->key,
            'label' => $d->label,
            'type' => $d->type,
            'show_in_booking' => (bool) $d->show_in_booking,
            'show_in_invoice' => (bool) $d->show_in_invoice,
            'show_in_portal' => (bool) $d->show_in_portal,
            'is_active' => (bool) $d->is_active,
            'value_text' => $v?->value_text,
        ];
    }

    private function payloadForCustomerDevice(RepairBuddyCustomerDevice $cd): array
    {
        $defs = RepairBuddyDeviceFieldDefinition::query()->orderBy('id', 'asc')->get();
        $values = RepairBuddyCustomerDeviceFieldValue::query()
            ->where('customer_device_id', $cd->id)
            ->get()
            ->keyBy('field_definition_id');

        return [
            'customer_device_id' => $cd->id,
            'extra_fields' => $defs->map(function (RepairBuddyDeviceFieldDefinition $d) use ($values) {
                $v = $values->get($d->id);
                return $this->serializeRow($d, $v instanceof RepairBuddyCustomerDeviceFieldValue ? $v : null);
            }),
        ];
    }

    public function index(Request $request, string $business, $customerDeviceId)
    {
        if (! is_numeric($customerDeviceId)) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        $cd = RepairBuddyCustomerDevice::query()->whereKey((int) $customerDeviceId)->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        return response()->json($this->payloadForCustomerDevice($cd));
    }

    public function update(Request $request, string $business, $customerDeviceId)
    {
        if (! is_numeric($customerDeviceId)) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        $cd = RepairBuddyCustomerDevice::query()->whereKey((int) $customerDeviceId)->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        $validated = $request->validate([
            'values' => ['required', 'array', 'max:200'],
            'values.*.field_definition_id' => ['required', 'integer'],
            'values.*.value_text' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        foreach ($validated['values'] as $row) {
            $definitionId = (int) $row['field_definition_id'];

            $definition = RepairBuddyDeviceFieldDefinition::query()->whereKey($definitionId)->first();
            if (! $definition) {
                return response()->json([
                    'message' => 'Field definition is invalid.',
                ], 422);
            }

            $value = array_key_exists('value_text', $row) && is_string($row['value_text']) ? trim((string) $row['value_text']) : null;

            if ($value === null || $value === '') {
                RepairBuddyCustomerDeviceFieldValue::query()
                    ->where('customer_device_id', $cd->id)
                    ->where('field_definition_id', $definition->id)
                    ->delete();
                continue;
            }

            RepairBuddyCustomerDeviceFieldValue::query()->updateOrCreate([
                'customer_device_id' => $cd->id,
                'field_definition_id' => $definition->id,
            ], [
                'value_text' => $value,
            ]);
        }

        return response()->json($this->payloadForCustomerDevice($cd));
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use Illuminate\Http\Request;

class RepairBuddyDeviceFieldDefinitionController extends Controller
{
    private function serialize(RepairBuddyDeviceFieldDefinition $d): array
    {
        return [
            'id' => $d->id,
            'key' => $d->key,
            'label' => $d->label,
            'type' => $d->type,
            'show_in_booking' => (bool) $d->show_in_booking,
            'show_in_invoice' => (bool) $d->show_in_invoice,
            'show_in_portal' => (bool) $d->show_in_portal,
            'is_active' => (bool) $d->is_active,
        ];
    }

    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyDeviceFieldDefinition::query()->orderBy('id', 'asc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('key', 'like', "%{$q}%")
                    ->orWhere('label', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $items = $query->limit($limit)->get();

        return response()->json([
            'device_field_definitions' => $items->map(fn (RepairBuddyDeviceFieldDefinition $d) => $this->serialize($d)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'max:32'],
            'show_in_booking' => ['sometimes', 'nullable', 'boolean'],
            'show_in_invoice' => ['sometimes', 'nullable', 'boolean'],
            'show_in_portal' => ['sometimes', 'nullable', 'boolean'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $key = trim((string) $validated['key']);
        $label = trim((string) $validated['label']);
        $type = array_key_exists('type', $validated) && is_string($validated['type']) && $validated['type'] !== '' ? trim((string) $validated['type']) : 'text';

        if (! in_array($type, ['text'], true)) {
            return response()->json([
                'message' => 'Field type is invalid.',
            ], 422);
        }

        if (RepairBuddyDeviceFieldDefinition::query()->where('key', $key)->exists()) {
            return response()->json([
                'message' => 'Field key already exists.',
            ], 422);
        }

        $d = RepairBuddyDeviceFieldDefinition::query()->create([
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'show_in_booking' => array_key_exists('show_in_booking', $validated) ? (bool) $validated['show_in_booking'] : false,
            'show_in_invoice' => array_key_exists('show_in_invoice', $validated) ? (bool) $validated['show_in_invoice'] : false,
            'show_in_portal' => array_key_exists('show_in_portal', $validated) ? (bool) $validated['show_in_portal'] : false,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'device_field_definition' => $this->serialize($d),
        ], 201);
    }

    public function update(Request $request, string $business, $definitionId)
    {
        if (! is_numeric($definitionId)) {
            return response()->json([
                'message' => 'Device field definition not found.',
            ], 404);
        }

        $d = RepairBuddyDeviceFieldDefinition::query()->whereKey((int) $definitionId)->first();

        if (! $d) {
            return response()->json([
                'message' => 'Device field definition not found.',
            ], 404);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'max:32'],
            'show_in_booking' => ['sometimes', 'nullable', 'boolean'],
            'show_in_invoice' => ['sometimes', 'nullable', 'boolean'],
            'show_in_portal' => ['sometimes', 'nullable', 'boolean'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $label = trim((string) $validated['label']);
        $type = array_key_exists('type', $validated)
            ? (is_string($validated['type']) && $validated['type'] !== '' ? trim((string) $validated['type']) : null)
            : $d->type;

        if (! in_array($type, ['text'], true)) {
            return response()->json([
                'message' => 'Field type is invalid.',
            ], 422);
        }

        $d->forceFill([
            'label' => $label,
            'type' => $type,
            'show_in_booking' => array_key_exists('show_in_booking', $validated) ? (bool) $validated['show_in_booking'] : $d->show_in_booking,
            'show_in_invoice' => array_key_exists('show_in_invoice', $validated) ? (bool) $validated['show_in_invoice'] : $d->show_in_invoice,
            'show_in_portal' => array_key_exists('show_in_portal', $validated) ? (bool) $validated['show_in_portal'] : $d->show_in_portal,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $d->is_active,
        ])->save();

        return response()->json([
            'device_field_definition' => $this->serialize($d->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $definitionId)
    {
        if (! is_numeric($definitionId)) {
            return response()->json([
                'message' => 'Device field definition not found.',
            ], 404);
        }

        $d = RepairBuddyDeviceFieldDefinition::query()->whereKey((int) $definitionId)->first();

        if (! $d) {
            return response()->json([
                'message' => 'Device field definition not found.',
            ], 404);
        }

        $inUse = RepairBuddyCustomerDeviceFieldValue::query()->where('field_definition_id', $d->id)->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Device field definition is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $d->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

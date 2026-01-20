<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntitlementDefinition;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingEntitlementDefinitionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:64', 'unique:entitlement_definitions,code'],
            'name' => ['required', 'string', 'max:255'],
            'value_type' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'is_premium' => ['nullable', 'boolean'],
        ]);

        $code = $validated['code'] ?? Str::slug($validated['name']);
        $code = $code ?: 'entitlement';

        $baseCode = $code;
        $i = 1;
        while (EntitlementDefinition::query()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$i;
            $i++;
        }

        $def = EntitlementDefinition::query()->create([
            'code' => $code,
            'name' => $validated['name'],
            'value_type' => $validated['value_type'],
            'description' => $validated['description'] ?? null,
            'is_premium' => (bool) ($validated['is_premium'] ?? false),
        ]);

        PlatformAudit::log($request, 'billing.entitlement_definition.created', null, null, [
            'entitlement_definition_id' => $def->id,
            'code' => $def->code,
        ]);

        return response()->json([
            'definition' => $def,
        ], 201);
    }

    public function update(Request $request, EntitlementDefinition $definition)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:entitlement_definitions,code,'.$definition->id],
            'name' => ['required', 'string', 'max:255'],
            'value_type' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'is_premium' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $definition->toArray();

        $definition->forceFill([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'value_type' => $validated['value_type'],
            'description' => $validated['description'] ?? null,
            'is_premium' => (bool) ($validated['is_premium'] ?? $definition->is_premium ?? false),
        ])->save();

        PlatformAudit::log($request, 'billing.entitlement_definition.updated', null, $validated['reason'] ?? null, [
            'entitlement_definition_id' => $definition->id,
            'before' => $before,
            'after' => $definition->toArray(),
        ]);

        return response()->json([
            'definition' => $definition,
        ]);
    }

    public function destroy(Request $request, EntitlementDefinition $definition)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $snapshot = $definition->toArray();
        $id = $definition->id;

        $definition->delete();

        PlatformAudit::log($request, 'billing.entitlement_definition.deleted', null, $validated['reason'] ?? null, [
            'entitlement_definition_id' => $id,
            'definition' => $snapshot,
        ]);

        return response()->json([
            'status' => 'ok',
        ]);
    }
}

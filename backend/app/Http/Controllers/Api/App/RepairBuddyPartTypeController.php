<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPartType;
use Illuminate\Http\Request;

class RepairBuddyPartTypeController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyPartType::query()->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $types = $query->limit($limit)->get();

        return response()->json([
            'part_types' => $types->map(fn (RepairBuddyPartType $t) => [
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

        $type = RepairBuddyPartType::query()->create([
            'name' => trim((string) $validated['name']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'part_type' => [
                'id' => $type->id,
                'name' => $type->name,
                'is_active' => (bool) $type->is_active,
            ],
        ], 201);
    }
}

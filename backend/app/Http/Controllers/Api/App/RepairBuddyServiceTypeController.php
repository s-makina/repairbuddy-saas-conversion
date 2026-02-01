<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServiceType;
use Illuminate\Http\Request;

class RepairBuddyServiceTypeController extends Controller
{
    protected function serializeType(RepairBuddyServiceType $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'is_active' => (bool) $t->is_active,
        ];
    }

    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dir' => ['sometimes', 'nullable', 'string', 'max:4'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;
        $page = is_int($validated['page'] ?? null) ? (int) $validated['page'] : null;
        $perPage = is_int($validated['per_page'] ?? null) ? (int) $validated['per_page'] : null;
        $sort = is_string($validated['sort'] ?? null) ? trim((string) $validated['sort']) : '';
        $dir = strtolower(is_string($validated['dir'] ?? null) ? trim((string) $validated['dir']) : '');

        $query = RepairBuddyServiceType::query();

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $allowedSorts = ['id', 'name', 'is_active'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $resolvedDir = $dir === 'desc' ? 'desc' : 'asc';

        $query->orderBy($resolvedSort, $resolvedDir);

        if ($resolvedSort !== 'name') {
            $query->orderBy('name', 'asc');
        }

        if ($page !== null || $perPage !== null || $sort !== '' || $dir !== '') {
            $paginator = $query->paginate($perPage ?? 10, ['*'], 'page', $page ?? 1);

            return response()->json([
                'service_types' => collect($paginator->items())->map(fn (RepairBuddyServiceType $t) => $this->serializeType($t)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $types = $query->limit($limit)->get();

        return response()->json([
            'service_types' => $types->map(fn (RepairBuddyServiceType $t) => $this->serializeType($t)),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyServiceType::query()->where('name', $name)->exists()) {
            return response()->json([
                'message' => 'Service type already exists.',
            ], 422);
        }

        $type = RepairBuddyServiceType::query()->create([
            'name' => $name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'service_type' => $this->serializeType($type),
        ], 201);
    }

    public function update(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Service type not found.',
            ], 404);
        }

        $type = RepairBuddyServiceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Service type not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);

        if (RepairBuddyServiceType::query()
            ->where('name', $name)
            ->where('id', '!=', $type->id)
            ->exists()) {
            return response()->json([
                'message' => 'Service type already exists.',
            ], 422);
        }

        $type->forceFill([
            'name' => $name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $type->is_active,
        ])->save();

        return response()->json([
            'service_type' => $this->serializeType($type->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, $typeId)
    {
        if (! is_numeric($typeId)) {
            return response()->json([
                'message' => 'Service type not found.',
            ], 404);
        }

        $type = RepairBuddyServiceType::query()->whereKey((int) $typeId)->first();

        if (! $type) {
            return response()->json([
                'message' => 'Service type not found.',
            ], 404);
        }

        $inUseByServices = RepairBuddyService::query()->where('service_type_id', $type->id)->exists();
        if ($inUseByServices) {
            return response()->json([
                'message' => 'Service type is in use and cannot be deleted.',
                'code' => 'in_use',
            ], 409);
        }

        $type->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}

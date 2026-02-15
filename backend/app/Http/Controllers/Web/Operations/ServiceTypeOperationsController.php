<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyServiceType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceTypeOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.service-types.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Service Types'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyServiceType::query()
            ->with(['parent'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('parent_display', function (RepairBuddyServiceType $type) {
                return (string) ($type->parent?->name ?? '');
            })
            ->addColumn('status_display', function (RepairBuddyServiceType $type) {
                if ($type->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyServiceType $type) use ($tenant) {
                $editUrl = route('tenant.operations.service_types.edit', ['business' => $tenant->slug, 'type' => $type->id]);
                $activeUrl = route('tenant.operations.service_types.active', ['business' => $tenant->slug, 'type' => $type->id]);
                $deleteUrl = route('tenant.operations.service_types.delete', ['business' => $tenant->slug, 'type' => $type->id]);
                $csrf = csrf_field();
                $activeValue = $type->is_active ? '0' : '1';
                $activeLabel = $type->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary" title="' . e($activeLabel) . '" aria-label="' . e($activeLabel) . '">'
                    . ($type->is_active ? '<i class="bi bi-toggle-off"></i>' : '<i class="bi bi-toggle-on"></i>')
                    . '</button>'
                    . '</form>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '"><i class="bi bi-trash"></i></button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['parent_display', 'status_display', 'actions_display'])
            ->toJson();
    }

    public function search(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dir' => ['sometimes', 'nullable', 'string', 'max:4'],
            'exclude_id' => ['sometimes', 'nullable'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 10;
        $sort = is_string($validated['sort'] ?? null) ? trim((string) $validated['sort']) : '';
        $dir = strtolower(is_string($validated['dir'] ?? null) ? trim((string) $validated['dir']) : '');
        $excludeIdRaw = $validated['exclude_id'] ?? null;
        $excludeId = is_numeric($excludeIdRaw) ? (int) $excludeIdRaw : null;

        $query = RepairBuddyServiceType::query();

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $allowedSorts = ['id', 'name'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : ($q === '' ? 'id' : 'name');
        $resolvedDir = in_array($dir, ['asc', 'desc'], true) ? $dir : ($q === '' ? 'desc' : 'asc');

        $types = $query
            ->orderBy($resolvedSort, $resolvedDir)
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn (RepairBuddyServiceType $t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
            ])
            ->values();

        return response()->json([
            'service_types' => $types,
        ]);
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentTypes = RepairBuddyServiceType::query()
            ->with(['parent'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $parentOptions = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyServiceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.service-types.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Service Type'),
            'parentOptions' => $parentOptions,
            'recentTypes' => $recentTypes,
        ]);
    }

    public function edit(Request $request, string $business, int $type)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyServiceType::query()->with(['parent'])->whereKey($type)->firstOrFail();

        $parentOptions = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->filter(fn (RepairBuddyServiceType $t) => (int) $t->id !== (int) $model->id)
            ->mapWithKeys(fn (RepairBuddyServiceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.service-types.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Service Type'),
            'type' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable'],
        ]);

        $name = trim((string) $validated['name']);
        $parentIdRaw = $validated['parent_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (RepairBuddyServiceType::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Type already exists.')])
                ->withInput();
        }

        if ($parentId !== null && ! RepairBuddyServiceType::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                ->withErrors(['parent_id' => __('Parent type is invalid.')])
                ->withInput();
        }

        RepairBuddyServiceType::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'is_active' => true,
        ]);

        return redirect()
            ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type added.'));
    }

    public function update(Request $request, string $business, int $type): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable'],
        ]);

        $model = RepairBuddyServiceType::query()->whereKey($type)->firstOrFail();

        $name = trim((string) $validated['name']);
        $parentIdRaw = $validated['parent_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (RepairBuddyServiceType::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Type already exists.')])
                ->withInput();
        }

        if ($parentId !== null) {
            if ($parentId === (int) $model->id) {
                return redirect()
                    ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_id' => __('Parent type is invalid.')])
                    ->withInput();
            }

            if (! RepairBuddyServiceType::query()->whereKey($parentId)->exists()) {
                return redirect()
                    ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_id' => __('Parent type is invalid.')])
                    ->withInput();
            }
        }

        $model->forceFill([
            'name' => $name,
            'parent_id' => $parentId,
        ])->save();

        return redirect()
            ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type updated.'));
    }

    public function setActive(Request $request, string $business, int $type): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model = RepairBuddyServiceType::query()->whereKey($type)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type updated.'));
    }

    public function delete(Request $request, string $business, int $type): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyServiceType::query()->whereKey($type)->firstOrFail();

        if ($model->children()->exists() || $model->services()->exists()) {
            return redirect()
                ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
                ->withErrors(['type' => __('Type is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.service_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type deleted.'));
    }
}

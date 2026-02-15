<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class DeviceTypeOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.brand-types.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Device Types'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyDeviceType::query()
            ->with(['parent'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('image_display', function (RepairBuddyDeviceType $type) {
                if (! is_string($type->image_url) || $type->image_url === '') {
                    return '';
                }

                $alt = (string) ($type->name ?? '');
                return '<img src="' . e($type->image_url) . '" alt="' . e($alt) . '" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;" />';
            })
            ->addColumn('parent_display', function (RepairBuddyDeviceType $type) {
                return (string) ($type->parent?->name ?? '');
            })
            ->addColumn('status_display', function (RepairBuddyDeviceType $type) {
                if ($type->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyDeviceType $type) use ($tenant) {
                $editUrl = route('tenant.operations.brand_types.edit', ['business' => $tenant->slug, 'type' => $type->id]);
                $activeUrl = route('tenant.operations.brand_types.active', ['business' => $tenant->slug, 'type' => $type->id]);
                $deleteUrl = route('tenant.operations.brand_types.delete', ['business' => $tenant->slug, 'type' => $type->id]);
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
            ->rawColumns(['image_display', 'status_display', 'actions_display'])
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

        $query = RepairBuddyDeviceType::query();

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
            ->map(fn (RepairBuddyDeviceType $t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
            ])
            ->values();

        return response()->json([
            'device_types' => $types,
        ]);
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentTypes = RepairBuddyDeviceType::query()
            ->with(['parent'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $parentOptions = RepairBuddyDeviceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brand-types.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Device Type'),
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

        $model = RepairBuddyDeviceType::query()->whereKey($type)->firstOrFail();

        $parentOptions = RepairBuddyDeviceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->filter(fn (RepairBuddyDeviceType $t) => (int) $t->id !== (int) $model->id)
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brand-types.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Device Type'),
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
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $name = trim((string) $validated['name']);
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : null;
        $parentIdRaw = $validated['parent_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (RepairBuddyDeviceType::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Type already exists.')])
                ->withInput();
        }

        if ($parentId !== null && ! RepairBuddyDeviceType::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                ->withErrors(['parent_id' => __('Parent type is invalid.')])
                ->withInput();
        }

        $typeModel = RepairBuddyDeviceType::query()->create([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);

        if ($request->hasFile('image') && $request->file('image') !== null) {
            $file = $request->file('image');
            $path = $file->storePublicly('rb-device-types/'.$typeModel->id, ['disk' => 'public']);
            $typeModel->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
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
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable'],
        ]);

        $model = RepairBuddyDeviceType::query()->whereKey($type)->firstOrFail();

        $name = trim((string) $validated['name']);
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : null;
        $parentIdRaw = $validated['parent_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (RepairBuddyDeviceType::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Type already exists.')])
                ->withInput();
        }

        if ($parentId !== null) {
            if ($parentId === (int) $model->id) {
                return redirect()
                    ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_id' => __('Parent type is invalid.')])
                    ->withInput();
            }

            if (! RepairBuddyDeviceType::query()->whereKey($parentId)->exists()) {
                return redirect()
                    ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_id' => __('Parent type is invalid.')])
                    ->withInput();
            }
        }

        $model->forceFill([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId,
        ])->save();

        if ($request->hasFile('image') && $request->file('image') !== null) {
            if (is_string($model->image_path) && $model->image_path !== '') {
                Storage::disk('public')->delete($model->image_path);
            }

            $file = $request->file('image');
            $path = $file->storePublicly('rb-device-types/'.$model->id, ['disk' => 'public']);
            $model->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
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

        $model = RepairBuddyDeviceType::query()->whereKey($type)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type updated.'));
    }

    public function delete(Request $request, string $business, int $type): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDeviceType::query()->whereKey($type)->firstOrFail();

        if ($model->children()->exists() || $model->devices()->exists()) {
            return redirect()
                ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
                ->withErrors(['type' => __('Type is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.brand_types.index', ['business' => $tenant->slug])
            ->with('status', __('Type deleted.'));
    }
}

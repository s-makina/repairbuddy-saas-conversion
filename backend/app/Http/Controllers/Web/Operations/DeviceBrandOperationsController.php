<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class DeviceBrandOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.brands.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Brands'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyDeviceBrand::query()->orderByDesc('is_active')->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('image_display', function (RepairBuddyDeviceBrand $brand) {
                if (! is_string($brand->image_url) || $brand->image_url === '') {
                    return '';
                }

                $alt = (string) ($brand->name ?? '');
                return '<img src="' . e($brand->image_url) . '" alt="' . e($alt) . '" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;" />';
            })
            ->addColumn('status_display', function (RepairBuddyDeviceBrand $brand) {
                if ($brand->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyDeviceBrand $brand) use ($tenant) {
                $editUrl = route('tenant.operations.brands.edit', ['business' => $tenant->slug, 'brand' => $brand->id]);
                $activeUrl = route('tenant.operations.brands.active', ['business' => $tenant->slug, 'brand' => $brand->id]);
                $deleteUrl = route('tenant.operations.brands.delete', ['business' => $tenant->slug, 'brand' => $brand->id]);
                $csrf = csrf_field();
                $activeValue = $brand->is_active ? '0' : '1';
                $activeLabel = $brand->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary" title="' . e($activeLabel) . '" aria-label="' . e($activeLabel) . '">'
                    . ($brand->is_active ? '<i class="bi bi-toggle-off"></i>' : '<i class="bi bi-toggle-on"></i>')
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

        $query = RepairBuddyDeviceBrand::query();

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $allowedSorts = ['id', 'name'];
        $resolvedSort = in_array($sort, $allowedSorts, true) ? $sort : ($q === '' ? 'id' : 'name');
        $resolvedDir = in_array($dir, ['asc', 'desc'], true) ? $dir : ($q === '' ? 'desc' : 'asc');

        $brands = $query
            ->orderBy($resolvedSort, $resolvedDir)
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json([
            'device_brands' => $brands->map(fn (RepairBuddyDeviceBrand $b) => [
                'id' => $b->id,
                'name' => (string) $b->name,
            ]),
        ]);
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentBrands = RepairBuddyDeviceBrand::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $parentOptions = RepairBuddyDeviceBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyDeviceBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brands.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Brand'),
            'recentBrands' => $recentBrands,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function edit(Request $request, string $business, int $brand)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        $parentOptions = RepairBuddyDeviceBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->filter(fn (RepairBuddyDeviceBrand $b) => (int) $b->id !== (int) $model->id)
            ->mapWithKeys(fn (RepairBuddyDeviceBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brands.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Brand'),
            'brand' => $model,
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

        if ($parentId !== null && ! RepairBuddyDeviceBrand::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.brands.create', ['business' => $tenant->slug])
                ->withErrors(['parent_id' => __('Parent brand is invalid.')])
                ->withInput();
        }

        if (RepairBuddyDeviceBrand::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Brand already exists.')])
                ->withInput();
        }

        $brand = RepairBuddyDeviceBrand::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'description' => $description,
            'is_active' => true,
        ]);

        if ($request->hasFile('image') && $request->file('image') !== null) {
            $file = $request->file('image');
            $path = $file->storePublicly('rb-device-brands/'.$brand->id, ['disk' => 'public']);
            $brand->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
            ->with('status', __('Brand added.'));
    }

    public function update(Request $request, string $business, int $brand): RedirectResponse
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

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        if ($parentId !== null) {
            if ($parentId === (int) $model->id) {
                return redirect()
                    ->route('tenant.operations.brands.edit', ['business' => $tenant->slug, 'brand' => $model->id])
                    ->withErrors(['parent_id' => __('Parent brand is invalid.')])
                    ->withInput();
            }

            if (! RepairBuddyDeviceBrand::query()->whereKey($parentId)->exists()) {
                return redirect()
                    ->route('tenant.operations.brands.edit', ['business' => $tenant->slug, 'brand' => $model->id])
                    ->withErrors(['parent_id' => __('Parent brand is invalid.')])
                    ->withInput();
            }
        }

        if (RepairBuddyDeviceBrand::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Brand already exists.')])
                ->withInput();
        }

        $model->forceFill([
            'parent_id' => $parentId,
            'name' => $name,
            'description' => $description,
        ])->save();

        if ($request->hasFile('image') && $request->file('image') !== null) {
            if (is_string($model->image_path) && $model->image_path !== '') {
                Storage::disk('public')->delete($model->image_path);
            }

            $file = $request->file('image');
            $path = $file->storePublicly('rb-device-brands/'.$model->id, ['disk' => 'public']);
            $model->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
            ->with('status', __('Brand updated.'));
    }

    public function setActive(Request $request, string $business, int $brand): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
            ->with('status', __('Brand updated.'));
    }

    public function delete(Request $request, string $business, int $brand): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        if ($model->devices()->exists()) {
            return redirect()
                ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
                ->withErrors(['brand' => __('Brand is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
            ->with('status', __('Brand deleted.'));
    }
}

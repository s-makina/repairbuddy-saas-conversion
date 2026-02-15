<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPartBrand;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class PartBrandOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.part-brands.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Part Brands'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyPartBrand::query()->orderByDesc('is_active')->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('image_display', function (RepairBuddyPartBrand $brand) {
                if (! is_string($brand->image_url) || $brand->image_url === '') {
                    return '';
                }

                $alt = (string) ($brand->name ?? '');

                return '<img src="' . e($brand->image_url) . '" alt="' . e($alt) . '" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;" />';
            })
            ->addColumn('status_display', function (RepairBuddyPartBrand $brand) {
                if ($brand->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyPartBrand $brand) use ($tenant) {
                $editUrl = route('tenant.operations.part_brands.edit', ['business' => $tenant->slug, 'brand' => $brand->id]);
                $activeUrl = route('tenant.operations.part_brands.active', ['business' => $tenant->slug, 'brand' => $brand->id]);
                $deleteUrl = route('tenant.operations.part_brands.delete', ['business' => $tenant->slug, 'brand' => $brand->id]);
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

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentBrands = RepairBuddyPartBrand::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $parentOptions = RepairBuddyPartBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartBrand $b) => [(string) $b->id => (string) $b->name])
            ->all();

        return view('tenant.operations.part-brands.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Part Brand'),
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

        $model = RepairBuddyPartBrand::query()->whereKey($brand)->firstOrFail();

        $parentOptions = RepairBuddyPartBrand::query()
            ->where('id', '!=', $model->id)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartBrand $b) => [(string) $b->id => (string) $b->name])
            ->all();

        return view('tenant.operations.part-brands.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Part Brand'),
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

        if ($parentId !== null && ! RepairBuddyPartBrand::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.part_brands.create', ['business' => $tenant->slug])
                ->withErrors(['parent_id' => __('Parent part brand is invalid.')])
                ->withInput();
        }

        if (RepairBuddyPartBrand::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Part brand already exists.')])
                ->withInput();
        }

        $brand = RepairBuddyPartBrand::query()->create([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);

        if ($request->hasFile('image') && $request->file('image') !== null) {
            $file = $request->file('image');
            $path = $file->storePublicly('rb-part-brands/'.$brand->id, ['disk' => 'public']);
            $brand->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
            ->with('status', __('Part brand added.'));
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

        $model = RepairBuddyPartBrand::query()->whereKey($brand)->firstOrFail();

        $name = trim((string) $validated['name']);
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : null;
        $parentIdRaw = $validated['parent_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if ($parentId !== null && $parentId === (int) $model->id) {
            return redirect()
                ->route('tenant.operations.part_brands.edit', ['business' => $tenant->slug, 'brand' => $model->id])
                ->withErrors(['parent_id' => __('A part brand cannot be its own parent.')])
                ->withInput();
        }

        if ($parentId !== null && ! RepairBuddyPartBrand::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.part_brands.edit', ['business' => $tenant->slug, 'brand' => $model->id])
                ->withErrors(['parent_id' => __('Parent part brand is invalid.')])
                ->withInput();
        }

        if (RepairBuddyPartBrand::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Part brand already exists.')])
                ->withInput();
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
            $path = $file->storePublicly('rb-part-brands/'.$model->id, ['disk' => 'public']);
            $model->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
            ->with('status', __('Part brand updated.'));
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

        $model = RepairBuddyPartBrand::query()->whereKey($brand)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
            ->with('status', __('Part brand updated.'));
    }

    public function delete(Request $request, string $business, int $brand): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyPartBrand::query()->whereKey($brand)->firstOrFail();

        if ($model->parts()->exists()) {
            return redirect()
                ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
                ->withErrors(['brand' => __('Part brand is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.part_brands.index', ['business' => $tenant->slug])
            ->with('status', __('Part brand deleted.'));
    }
}

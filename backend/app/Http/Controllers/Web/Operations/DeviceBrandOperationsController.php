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
            ->rawColumns(['status_display', 'actions_display'])
            ->toJson();
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

        return view('tenant.operations.brands.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Brand'),
            'recentBrands' => $recentBrands,
        ]);
    }

    public function edit(Request $request, string $business, int $brand)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        return view('tenant.operations.brands.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Brand'),
            'brand' => $model,
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
            'image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $name = trim((string) $validated['name']);
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : null;

        if (RepairBuddyDeviceBrand::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.brands.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Brand already exists.')])
                ->withInput();
        }

        $brand = RepairBuddyDeviceBrand::query()->create([
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
            'image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $name = trim((string) $validated['name']);
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : null;

        $model = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

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

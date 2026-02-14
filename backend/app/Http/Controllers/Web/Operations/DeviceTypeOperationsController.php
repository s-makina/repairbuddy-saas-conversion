<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'pageTitle' => __('Brand Types'),
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
            ->addColumn('parent_display', function (RepairBuddyDeviceType $type) {
                return (string) ($type->parent?->name ?? '');
            })
            ->addColumn('status_display', function (RepairBuddyDeviceType $type) {
                return $type->is_active ? __('Active') : __('Inactive');
            })
            ->addColumn('actions_display', function (RepairBuddyDeviceType $type) use ($tenant) {
                $editUrl = route('tenant.operations.brand_types.edit', ['business' => $tenant->slug, 'type' => $type->id]);
                $activeUrl = route('tenant.operations.brand_types.active', ['business' => $tenant->slug, 'type' => $type->id]);
                $deleteUrl = route('tenant.operations.brand_types.delete', ['business' => $tenant->slug, 'type' => $type->id]);
                $csrf = csrf_field();
                $activeValue = $type->is_active ? '0' : '1';
                $activeLabel = $type->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '">' . e(__('Edit')) . '</a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary">' . e($activeLabel) . '</button>'
                    . '</form>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger">' . e(__('Delete')) . '</button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['actions_display'])
            ->toJson();
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $parentOptions = RepairBuddyDeviceType::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brand-types.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Type'),
            'parentOptions' => $parentOptions,
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
            ->whereNull('parent_id')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->filter(fn (RepairBuddyDeviceType $t) => (int) $t->id !== (int) $model->id)
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.brand-types.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Type'),
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

        RepairBuddyDeviceType::query()->create([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);

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

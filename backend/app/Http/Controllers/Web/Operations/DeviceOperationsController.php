<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class DeviceOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.devices.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Devices'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyDevice::query()
            ->with(['type', 'brand', 'parent'])
            ->orderByDesc('is_active')
            ->orderBy('model');

        return DataTables::eloquent($query)
            ->addColumn('image_display', function (RepairBuddyDevice $device) {
                if (! is_string($device->image_url) || $device->image_url === '') {
                    return '';
                }

                $alt = (string) ($device->model ?? '');
                return '<img src="' . e($device->image_url) . '" alt="' . e($alt) . '" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;" />';
            })
            ->addColumn('type_display', function (RepairBuddyDevice $device) {
                return (string) ($device->type?->name ?? '');
            })
            ->addColumn('brand_display', function (RepairBuddyDevice $device) {
                return (string) ($device->brand?->name ?? '');
            })
            ->addColumn('status_display', function (RepairBuddyDevice $device) {
                if ($device->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyDevice $device) use ($tenant) {
                $editUrl = route('tenant.operations.devices.edit', ['business' => $tenant->slug, 'device' => $device->id]);
                $activeUrl = route('tenant.operations.devices.active', ['business' => $tenant->slug, 'device' => $device->id]);
                $deleteUrl = route('tenant.operations.devices.delete', ['business' => $tenant->slug, 'device' => $device->id]);
                $csrf = csrf_field();
                $activeValue = $device->is_active ? '0' : '1';
                $activeLabel = $device->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary" title="' . e($activeLabel) . '" aria-label="' . e($activeLabel) . '">'
                    . ($device->is_active ? '<i class="bi bi-toggle-off"></i>' : '<i class="bi bi-toggle-on"></i>')
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

        $recentDevices = RepairBuddyDevice::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $typeOptions = RepairBuddyDeviceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('Select type'), '')
            ->all();

        $brandOptions = RepairBuddyDeviceBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDeviceBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('Select brand'), '')
            ->all();

        $parentOptions = RepairBuddyDevice::query()
            ->orderBy('model')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDevice $d) => [(string) $d->id => (string) $d->model])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.devices.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Device'),
            'recentDevices' => $recentDevices,
            'typeOptions' => $typeOptions,
            'brandOptions' => $brandOptions,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function edit(Request $request, string $business, int $device)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDevice::query()->with(['type', 'brand', 'parent'])->whereKey($device)->firstOrFail();

        $variations = RepairBuddyDevice::query()
            ->with(['type', 'brand'])
            ->where('parent_device_id', $model->id)
            ->orderBy('model')
            ->get();

        $typeOptions = RepairBuddyDeviceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDeviceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('Select type'), '')
            ->all();

        $brandOptions = RepairBuddyDeviceBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (RepairBuddyDeviceBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('Select brand'), '')
            ->all();

        $parentOptions = RepairBuddyDevice::query()
            ->orderBy('model')
            ->limit(500)
            ->get()
            ->filter(fn (RepairBuddyDevice $d) => (int) $d->id !== (int) $model->id)
            ->mapWithKeys(fn (RepairBuddyDevice $d) => [(string) $d->id => (string) $d->model])
            ->prepend((string) __('None'), '')
            ->all();

        return view('tenant.operations.devices.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Device'),
            'device' => $model,
            'variations' => $variations,
            'typeOptions' => $typeOptions,
            'brandOptions' => $brandOptions,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function storeVariations(Request $request, string $business, int $device): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'variations_list' => ['required', 'string', 'max:2000'],
        ]);

        $parent = RepairBuddyDevice::query()->whereKey($device)->firstOrFail();

        $raw = trim((string) $validated['variations_list']);
        $names = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), explode(',', $raw)), static fn ($v) => $v !== ''));

        if (count($names) === 0) {
            return redirect()
                ->route('tenant.operations.devices.edit', ['business' => $tenant->slug, 'device' => $parent->id])
                ->withErrors(['variations_list' => __('Please enter at least one variation.')]);
        }

        $createdCount = 0;

        foreach ($names as $name) {
            $modelName = trim((string) $parent->model.' - '.$name);

            if ($modelName === '' || strlen($modelName) > 255) {
                continue;
            }

            $exists = RepairBuddyDevice::query()
                ->where('device_type_id', $parent->device_type_id)
                ->where('device_brand_id', $parent->device_brand_id)
                ->where('model', $modelName)
                ->exists();

            if ($exists) {
                continue;
            }

            RepairBuddyDevice::query()->create([
                'model' => $modelName,
                'device_type_id' => $parent->device_type_id,
                'device_brand_id' => $parent->device_brand_id,
                'parent_device_id' => $parent->id,
                'disable_in_booking_form' => (bool) $parent->disable_in_booking_form,
                'is_other' => (bool) $parent->is_other,
                'is_active' => (bool) $parent->is_active,
            ]);

            $createdCount++;
        }

        return redirect()
            ->route('tenant.operations.devices.edit', ['business' => $tenant->slug, 'device' => $parent->id])
            ->with('status', $createdCount > 0 ? __('Variations created.') : __('No variations were created.'));
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'device_type_id' => ['required', 'integer'],
            'device_brand_id' => ['required', 'integer'],
            'parent_device_id' => ['sometimes', 'nullable'],
            'disable_in_booking_form' => ['sometimes', 'nullable', 'boolean'],
            'is_other' => ['sometimes', 'nullable', 'boolean'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'variations_list' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $model = trim((string) $validated['model']);
        $typeId = (int) $validated['device_type_id'];
        $brandId = (int) $validated['device_brand_id'];
        $parentIdRaw = $validated['parent_device_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['device_type_id' => __('Type is invalid.')])
                ->withInput();
        }

        if (! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['device_brand_id' => __('Brand is invalid.')])
                ->withInput();
        }

        if ($parentId !== null && ! RepairBuddyDevice::query()->whereKey($parentId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['parent_device_id' => __('Parent device is invalid.')])
                ->withInput();
        }

        if (RepairBuddyDevice::query()->where('model', $model)->where('device_brand_id', $brandId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['model' => __('Device already exists for this brand.')])
                ->withInput();
        }

        $deviceModel = RepairBuddyDevice::query()->create([
            'model' => $model,
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'parent_device_id' => $parentId,
            'disable_in_booking_form' => (bool) ($validated['disable_in_booking_form'] ?? false),
            'is_other' => (bool) ($validated['is_other'] ?? false),
            'is_active' => true,
        ]);

        if ($request->hasFile('image') && $request->file('image') !== null) {
            $file = $request->file('image');
            $path = $file->storePublicly('rb-devices/'.$deviceModel->id, ['disk' => 'public']);
            $deviceModel->forceFill([
                'image_path' => $path,
            ])->save();
        }

        if (is_string($validated['variations_list'] ?? null) && trim((string) $validated['variations_list']) !== '') {
            $raw = trim((string) $validated['variations_list']);
            $names = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), explode(',', $raw)), static fn ($v) => $v !== ''));

            foreach ($names as $name) {
                $modelName = trim((string) $deviceModel->model.' - '.$name);
                if ($modelName === '' || strlen($modelName) > 255) {
                    continue;
                }

                $exists = RepairBuddyDevice::query()
                    ->where('device_type_id', $deviceModel->device_type_id)
                    ->where('device_brand_id', $deviceModel->device_brand_id)
                    ->where('model', $modelName)
                    ->exists();

                if ($exists) {
                    continue;
                }

                RepairBuddyDevice::query()->create([
                    'model' => $modelName,
                    'device_type_id' => $deviceModel->device_type_id,
                    'device_brand_id' => $deviceModel->device_brand_id,
                    'parent_device_id' => $deviceModel->id,
                    'disable_in_booking_form' => (bool) $deviceModel->disable_in_booking_form,
                    'is_other' => (bool) $deviceModel->is_other,
                    'is_active' => (bool) $deviceModel->is_active,
                ]);
            }
        }

        return redirect()
            ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
            ->with('status', __('Device added.'));
    }

    public function update(Request $request, string $business, int $device): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'device_type_id' => ['required', 'integer'],
            'device_brand_id' => ['required', 'integer'],
            'parent_device_id' => ['sometimes', 'nullable'],
            'disable_in_booking_form' => ['sometimes', 'nullable', 'boolean'],
            'is_other' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $existing = RepairBuddyDevice::query()->whereKey($device)->firstOrFail();

        $model = trim((string) $validated['model']);
        $typeId = (int) $validated['device_type_id'];
        $brandId = (int) $validated['device_brand_id'];
        $parentIdRaw = $validated['parent_device_id'] ?? null;
        $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

        if (! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['device_type_id' => __('Type is invalid.')])
                ->withInput();
        }

        if (! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['device_brand_id' => __('Brand is invalid.')])
                ->withInput();
        }

        if ($parentId !== null) {
            if ($parentId === (int) $existing->id) {
                return redirect()
                    ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_device_id' => __('Parent device is invalid.')])
                    ->withInput();
            }

            if (! RepairBuddyDevice::query()->whereKey($parentId)->exists()) {
                return redirect()
                    ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                    ->withErrors(['parent_device_id' => __('Parent device is invalid.')])
                    ->withInput();
            }
        }

        if (RepairBuddyDevice::query()
            ->where('model', $model)
            ->where('device_brand_id', $brandId)
            ->where('id', '!=', $existing->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['model' => __('Device already exists for this brand.')])
                ->withInput();
        }

        $existing->forceFill([
            'model' => $model,
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'parent_device_id' => $parentId,
            'disable_in_booking_form' => (bool) ($validated['disable_in_booking_form'] ?? false),
            'is_other' => (bool) ($validated['is_other'] ?? false),
        ])->save();

        if ($request->hasFile('image') && $request->file('image') !== null) {
            if (is_string($existing->image_path) && $existing->image_path !== '') {
                Storage::disk('public')->delete($existing->image_path);
            }

            $file = $request->file('image');
            $path = $file->storePublicly('rb-devices/'.$existing->id, ['disk' => 'public']);
            $existing->forceFill([
                'image_path' => $path,
            ])->save();
        }

        return redirect()
            ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
            ->with('status', __('Device updated.'));
    }

    public function setActive(Request $request, string $business, int $device): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model = RepairBuddyDevice::query()->whereKey($device)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
            ->with('status', __('Device updated.'));
    }

    public function delete(Request $request, string $business, int $device): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyDevice::query()->whereKey($device)->firstOrFail();

        if (RepairBuddyDevice::query()->where('parent_device_id', $model->id)->exists()) {
            return redirect()
                ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
                ->withErrors(['device' => __('Device is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.devices.index', ['business' => $tenant->slug])
            ->with('status', __('Device deleted.'));
    }
}

<?php

namespace App\Http\Controllers\Tenant\DeviceBrands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DeviceBrands\SetDeviceBrandActiveRequest;
use App\Http\Requests\Tenant\DeviceBrands\StoreDeviceBrandRequest;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class DeviceBrandController extends Controller
{
    public function store(StoreDeviceBrandRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $name = trim((string) $validated['name']);
        if ($name === '') {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['name' => 'Brand name is required.'])
                ->withInput();
        }

        if (RepairBuddyDeviceBrand::query()->where('name', $name)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['name' => 'Device brand already exists.'])
                ->withInput();
        }

        RepairBuddyDeviceBrand::query()->create([
            'name' => $name,
            'is_active' => true,
        ]);

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand added.')
            ->withInput();
    }

    public function setActive(SetDeviceBrandActiveRequest $request, int $brand): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $brandModel = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();
        $brandModel->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand updated.')
            ->withInput();
    }

    public function delete(int $brand): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $brandModel = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        if ($brandModel->devices()->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['brand' => 'Device brand is in use and cannot be deleted.'])
                ->withInput();
        }

        $brandModel->delete();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand deleted.')
            ->withInput();
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyServiceType;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantPublicPageController extends Controller
{
    public function myaccount(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        return view('tenant.my-account', [
            'tenant' => $tenant,
            'tenantSlug' => $business,
            'business' => $business,
        ]);
    }

    public function services(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        $branchId = $tenant->default_branch_id;

        // Fetch service types with their active services for the default branch
        $serviceTypes = RepairBuddyServiceType::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['services' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        // Also get services without a type
        $untypedServices = \App\Models\RepairBuddyService::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('service_type_id')
            ->orderBy('name')
            ->get();

        return view('tenant.services', [
            'tenant'          => $tenant,
            'tenantSlug'      => $business,
            'business'        => $business,
            'serviceTypes'    => $serviceTypes,
            'untypedServices' => $untypedServices,
        ]);
    }

    public function parts(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.parts', $business);
    }

    public function review(Request $request, string $business)
    {
        return $this->placeholder('tenant.placeholders.review', $business);
    }

    private function placeholder(string $view, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        return view($view, [
            'tenant'   => $tenant,
            'business' => $business,
        ]);
    }
}

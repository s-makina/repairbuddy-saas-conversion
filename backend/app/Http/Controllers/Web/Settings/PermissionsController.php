<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PermissionsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.settings.permissions.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'settings',
            'pageTitle' => __('Permissions'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        return DataTables::eloquent($query)->toJson();
    }
}

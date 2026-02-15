<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ManagersController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.managers.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'managers',
            'pageTitle' => __('Managers'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Manager'))
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('shops_display', function (User $u) {
                $names = $u->branches()
                    ->orderBy('name')
                    ->get(['branches.code', 'branches.name'])
                    ->map(function ($b) {
                        $code = is_string($b->code) && $b->code !== '' ? (string) $b->code : '';
                        $name = is_string($b->name) ? (string) $b->name : '';
                        return $code !== '' ? ($code.' - '.$name) : $name;
                    })
                    ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                    ->values()
                    ->all();

                return e(implode(', ', $names));
            })
            ->toJson();
    }
}

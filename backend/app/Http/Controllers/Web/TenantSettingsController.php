<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantBootstrap\EnsureDefaultRepairBuddyStatuses;
use App\Support\TenantContext;
use App\ViewModels\Tenant\SettingsScreenViewModel;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        if (! $request->user()) {
            return redirect()->route('web.login');
        }

        if (is_int($tenantId) && $tenantId > 0) {
            app(EnsureDefaultRepairBuddyStatuses::class)->ensure($tenantId);
        }

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $vm = new SettingsScreenViewModel($request, $tenant);

        return view('tenant.settings.index', $vm->toArray());
    }
}

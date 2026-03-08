<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class BusinessSetupController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(404, 'Business not found.');
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // If setup is already done, redirect to dashboard
        if ($tenant->setup_completed_at) {
            return redirect()->route('tenant.dashboard', ['business' => $tenant->slug]);
        }

        $tenant->load('plan');

        return view('tenant.setup', [
            'tenant' => $tenant,
            'user' => $user,
        ]);
    }
}

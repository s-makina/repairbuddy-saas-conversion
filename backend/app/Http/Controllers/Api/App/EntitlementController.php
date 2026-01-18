<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Support\Entitlements;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function index(Request $request)
    {
        $tenant = TenantContext::tenant();

        return response()->json([
            'tenant' => $tenant,
            'plan' => $tenant?->plan,
            'entitlements' => $tenant ? Entitlements::forTenant($tenant) : [],
        ]);
    }
}

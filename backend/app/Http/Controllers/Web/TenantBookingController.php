<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantBookingController extends Controller
{
    public function show(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        return view('tenant.booking', [
            'tenant' => $tenant,
            'business' => $business,
        ]);
    }
}

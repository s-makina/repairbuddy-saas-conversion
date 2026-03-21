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

        // Get logged-in user (if any) for prepopulating contact info
        $user = $request->user();

        return view('tenant.book', [
            'tenant' => $tenant,
            'tenantSlug' => $business,
            'user' => $user,
        ]);
    }
}

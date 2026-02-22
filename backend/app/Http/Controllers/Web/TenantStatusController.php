<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantStatusController extends Controller
{
    public function show(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404);
        }

        // Get case number from query string if provided
        $caseNumber = $request->query('caseNumber', '');

        return view('tenant.status', [
            'tenant' => $tenant,
            'business' => $business,
            'initialCaseNumber' => $caseNumber,
        ]);
    }
}

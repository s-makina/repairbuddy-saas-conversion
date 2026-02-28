<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantAppointmentController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $request->user()) {
            return redirect()->route('web.login');
        }

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.appointments.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'appointments',
            'pageTitle' => 'Appointments',
        ]);
    }
}

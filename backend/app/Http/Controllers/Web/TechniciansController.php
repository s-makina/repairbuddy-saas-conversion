<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TechniciansController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.technicians.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'technicians',
            'pageTitle' => __('Technicians'),
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
            ->whereHas('roles', fn ($q) => $q->where('name', 'Technician'))
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('tech_rate_display', function (User $u) {
                $cents = is_numeric($u->tech_hourly_rate_cents) ? (int) $u->tech_hourly_rate_cents : null;
                if ($cents === null) {
                    return '';
                }

                return number_format($cents / 100, 2, '.', '');
            })
            ->addColumn('client_rate_display', function (User $u) {
                $cents = is_numeric($u->client_hourly_rate_cents) ? (int) $u->client_hourly_rate_cents : null;
                if ($cents === null) {
                    return '';
                }

                return number_format($cents / 100, 2, '.', '');
            })
            ->addColumn('jobs_count', function (User $u) use ($tenant) {
                return (int) RepairBuddyJob::query()
                    ->where('tenant_id', $tenant->id)
                    ->where(function ($q) use ($u) {
                        $q->where('assigned_technician_id', $u->id)
                            ->orWhereHas('technicians', fn ($tq) => $tq->whereKey($u->id));
                    })
                    ->distinct('id')
                    ->count('id');
            })
            ->addColumn('hourly_rates_display', function (User $u) {
                $techRate = is_numeric($u->tech_hourly_rate_cents) ? number_format(((int) $u->tech_hourly_rate_cents) / 100, 2, '.', '') : '';
                $clientRate = is_numeric($u->client_hourly_rate_cents) ? number_format(((int) $u->client_hourly_rate_cents) / 100, 2, '.', '') : '';

                return '<div class="d-flex align-items-center gap-2 justify-content-end">'
                    . '<span class="badge text-bg-light border">' . e(__('Tech')) . ': ' . e($techRate !== '' ? $techRate : '--') . '</span>'
                    . '<span class="badge text-bg-light border">' . e(__('Client')) . ': ' . e($clientRate !== '' ? $clientRate : '--') . '</span>'
                    . '</div>';
            })
            ->rawColumns(['hourly_rates_display'])
            ->toJson();
    }

    public function updateHourlyRates(Request $request, string $business, int $user): RedirectResponse
    {
        abort(404);
    }
}

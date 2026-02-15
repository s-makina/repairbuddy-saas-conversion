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
            ->addColumn('hourly_rates_action', function (User $u) use ($tenant) {
                $postUrl = route('tenant.technicians.hourly_rates.update', ['business' => $tenant->slug, 'user' => $u->id]);
                $csrf = csrf_field();

                $techRate = is_numeric($u->tech_hourly_rate_cents) ? number_format(((int) $u->tech_hourly_rate_cents) / 100, 2, '.', '') : '';
                $clientRate = is_numeric($u->client_hourly_rate_cents) ? number_format(((int) $u->client_hourly_rate_cents) / 100, 2, '.', '') : '';

                return '<form method="post" action="' . e($postUrl) . '">' . $csrf
                    . '<div class="d-flex align-items-center gap-2 justify-content-end">'
                    . '<input type="number" step="0.01" min="0" inputmode="decimal" name="tech_rate" value="' . e($techRate) . '" class="form-control form-control-sm" style="width: 120px;" placeholder="' . e(__('Tech')) . '" />'
                    . '<input type="number" step="0.01" min="0" inputmode="decimal" name="client_rate" value="' . e($clientRate) . '" class="form-control form-control-sm" style="width: 120px;" placeholder="' . e(__('Client')) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-primary" title="' . e(__('Update')) . '" aria-label="' . e(__('Update')) . '"><i class="bi bi-save"></i></button>'
                    . '</div>'
                    . '</form>';
            })
            ->rawColumns(['hourly_rates_action'])
            ->toJson();
    }

    public function updateHourlyRates(Request $request, string $business, int $user): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $tech = $request->input('tech_rate');
        $client = $request->input('client_rate');

        $validated = $request->validate([
            'tech_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'client_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $model = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereKey($user)
            ->firstOrFail();

        if (! $model->hasRole('Technician')) {
            return redirect()
                ->route('tenant.technicians.index', ['business' => $tenant->slug])
                ->with('status', __('User is not a technician.'));
        }

        $techCents = array_key_exists('tech_rate', $validated) && $validated['tech_rate'] !== null
            ? (int) round(((float) $validated['tech_rate']) * 100)
            : null;

        $clientCents = array_key_exists('client_rate', $validated) && $validated['client_rate'] !== null
            ? (int) round(((float) $validated['client_rate']) * 100)
            : null;

        $model->forceFill([
            'tech_hourly_rate_cents' => $techCents,
            'client_hourly_rate_cents' => $clientCents,
        ])->save();

        return redirect()
            ->route('tenant.technicians.index', ['business' => $tenant->slug])
            ->with('status', __('Hourly rates updated.'));
    }
}

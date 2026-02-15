<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HourlyRatesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $staff = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->orderBy('name')
            ->get();

        return view('tenant.settings.hourly-rates.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'pageTitle' => __('Manage Hourly Rates'),
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, string $business, int $user): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'tech_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'client_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $model = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereKey($user)
            ->firstOrFail();

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
            ->route('tenant.settings.hourly_rates.index', ['business' => $tenant->slug])
            ->with('status', __('Hourly rates updated.'));
    }
}

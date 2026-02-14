<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateTimeLogSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class TimeLogSettingsController extends Controller
{
    public function update(UpdateTimeLogSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $timeLog = $store->get('timeLog', []);
        if (! is_array($timeLog)) {
            $timeLog = [];
        }

        $timeLog['disabled'] = array_key_exists('disable_timelog', $validated);
        if (array_key_exists('default_tax_id', $validated)) {
            $timeLog['defaultTaxId'] = is_int($validated['default_tax_id']) ? (string) $validated['default_tax_id'] : null;
        }
        if (array_key_exists('job_status_include', $validated)) {
            $timeLog['jobStatusInclude'] = array_values(array_filter(
                $validated['job_status_include'] ?? [],
                fn ($v) => is_string($v) && $v !== ''
            ));
        }
        if (array_key_exists('activities', $validated)) {
            $timeLog['activities'] = is_string($validated['activities']) ? $validated['activities'] : '';
        }

        $store->set('timeLog', $timeLog);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_timelog_tab')
            ->with('status', 'Time log settings updated.')
            ->withInput();
    }
}

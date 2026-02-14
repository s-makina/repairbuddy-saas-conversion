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

        if (array_key_exists('disable_timelog', $validated)) {
            $timeLog['disabled'] = (bool) $validated['disable_timelog'];
        }

        if (array_key_exists('default_tax_id', $validated)) {
            $defaultTaxId = $validated['default_tax_id'];
            if (is_int($defaultTaxId) && $defaultTaxId > 0) {
                $timeLog['defaultTaxId'] = (string) $defaultTaxId;
            } elseif (is_string($defaultTaxId) && $defaultTaxId !== '' && ctype_digit($defaultTaxId) && (int) $defaultTaxId > 0) {
                $timeLog['defaultTaxId'] = (string) (int) $defaultTaxId;
            } else {
                $timeLog['defaultTaxId'] = null;
            }
        }
        if (array_key_exists('job_status_include', $validated)) {
            $timeLog['jobStatusInclude'] = array_values(array_filter(
                $validated['job_status_include'] ?? [],
                fn ($v) => is_string($v) && trim($v) !== ''
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

<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateJobStatusSettingsRequest;
use App\Models\Status;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class JobStatusSettingsController extends Controller
{
    public function update(UpdateJobStatusSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $validated = $request->validated();

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $jobStatus = $repairBuddySettings['jobStatus'] ?? [];
        if (! is_array($jobStatus)) {
            $jobStatus = [];
        }

        $allowedSlugs = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->select('code')
            ->orderBy('id')
            ->pluck('code')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values()
            ->all();

        $allowedSlugSet = array_fill_keys($allowedSlugs, true);

        foreach (['wcrb_job_status_delivered', 'wcrb_job_status_cancelled'] as $k) {
            if (! array_key_exists($k, $validated)) {
                continue;
            }

            $value = $validated[$k];
            if (! is_string($value)) {
                $jobStatus[$k] = null;
                continue;
            }

            $value = trim($value);
            if ($value === '' || ! isset($allowedSlugSet[$value])) {
                $jobStatus[$k] = null;
                continue;
            }

            $jobStatus[$k] = $value;
        }

        $repairBuddySettings['jobStatus'] = $jobStatus;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Job status settings updated.',
                'data' => [
                    'wcrb_job_status_delivered' => $jobStatus['wcrb_job_status_delivered'] ?? null,
                    'wcrb_job_status_cancelled' => $jobStatus['wcrb_job_status_cancelled'] ?? null,
                ],
            ]);
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status settings updated.')
            ->withInput();
    }
}

<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateEstimatesSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class EstimatesSettingsController extends Controller
{
    public function update(UpdateEstimatesSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $estimates = $store->get('estimates', []);
        if (! is_array($estimates)) {
            $estimates = [];
        }

        $estimates['enabled'] = $request->boolean('estimates_enabled');
        $estimates['validDays'] = array_key_exists('estimate_valid_days', $validated)
            ? (int) $validated['estimate_valid_days']
            : 30;

        foreach (
            [
                'estimate_email_subject_to_customer',
                'estimate_email_body_to_customer',
                'estimate_approve_email_subject_to_admin',
                'estimate_approve_email_body_to_admin',
                'estimate_reject_email_subject_to_admin',
                'estimate_reject_email_body_to_admin',
            ] as $k
        ) {
            if (array_key_exists($k, $validated)) {
                $estimates[$k] = $validated[$k];
            }
        }

        if (array_key_exists('wcrb_turn_booking_forms_to_jobs', $validated)) {
            $bookings = $store->get('bookings', []);
            if (! is_array($bookings)) {
                $bookings = [];
            }
            $bookings['turnBookingFormsToJobs'] = $request->boolean('wcrb_turn_booking_forms_to_jobs');
            $store->set('bookings', $bookings);
        }

        $store->set('estimates', $estimates);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_estimates_tab')
            ->with('status', 'Estimates settings updated.')
            ->withInput();
    }
}

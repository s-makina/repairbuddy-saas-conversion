<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateReviewsSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class ReviewsSettingsController extends Controller
{
    public function update(UpdateReviewsSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $reviews = $store->get('reviews', []);
        if (! is_array($reviews)) {
            $reviews = [];
        }

        $reviews['requestBySms'] = array_key_exists('request_by_sms', $validated);
        $reviews['requestByEmail'] = array_key_exists('request_by_email', $validated);

        foreach (['get_feedback_page_url', 'send_request_job_status', 'auto_request_interval', 'email_subject', 'email_message', 'sms_message'] as $k) {
            if (array_key_exists($k, $validated)) {
                $reviews[$k] = $validated[$k];
            }
        }

        $store->set('reviews', $reviews);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wcrb_reviews_tab')
            ->with('status', 'Review settings updated.')
            ->withInput();
    }
}

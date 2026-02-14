<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateBookingSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class BookingSettingsController extends Controller
{
    public function update(UpdateBookingSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $bookings = $store->get('bookings', []);
        if (! is_array($bookings)) {
            $bookings = [];
        }

        foreach (['booking_email_subject_to_customer', 'booking_email_body_to_customer', 'booking_email_subject_to_admin', 'booking_email_body_to_admin'] as $k) {
            if (array_key_exists($k, $validated)) {
                $bookings[$k] = $validated[$k];
            }
        }

        foreach (['wc_booking_default_type', 'wc_booking_default_brand', 'wc_booking_default_device'] as $k) {
            if (array_key_exists($k, $validated)) {
                $val = $validated[$k];
                $bookings[$k] = is_int($val) ? (string) $val : null;
            }
        }

        $bookings['turnBookingFormsToJobs'] = array_key_exists('wcrb_turn_booking_forms_to_jobs', $validated);
        $bookings['turnOffOtherDeviceBrands'] = array_key_exists('wcrb_turn_off_other_device_brands', $validated);
        $bookings['turnOffOtherService'] = array_key_exists('wcrb_turn_off_other_service', $validated);
        $bookings['turnOffServicePrice'] = array_key_exists('wcrb_turn_off_service_price', $validated);
        $bookings['turnOffIdImeiBooking'] = array_key_exists('wcrb_turn_off_idimei_booking', $validated);

        $store->set('bookings', $bookings);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_bookings')
            ->with('status', 'Booking settings updated.')
            ->withInput();
    }
}

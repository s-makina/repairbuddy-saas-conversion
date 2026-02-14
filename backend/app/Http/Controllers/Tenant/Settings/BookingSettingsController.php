<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateBookingSettingsRequest;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingSettingsController extends Controller
{
    public function brands(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'typeId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $typeId = array_key_exists('typeId', $validated) ? ($validated['typeId'] ?? null) : null;
        $typeId = is_int($typeId) ? $typeId : null;

        $query = RepairBuddyDeviceBrand::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($typeId !== null) {
            $brandIds = RepairBuddyDevice::query()
                ->where('is_active', true)
                ->where('disable_in_booking_form', false)
                ->where('device_type_id', $typeId)
                ->distinct()
                ->pluck('device_brand_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (count($brandIds) === 0) {
                return response()->json([
                    'brands' => [],
                ]);
            }

            $query->whereIn('id', $brandIds);
        }

        $brands = $query->limit(500)->get();

        return response()->json([
            'brands' => $brands->map(fn (RepairBuddyDeviceBrand $b) => [
                'id' => $b->id,
                'name' => $b->name,
            ]),
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'typeId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'brandId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $typeId = array_key_exists('typeId', $validated) ? ($validated['typeId'] ?? null) : null;
        $brandId = array_key_exists('brandId', $validated) ? ($validated['brandId'] ?? null) : null;

        $typeId = is_int($typeId) ? $typeId : null;
        $brandId = is_int($brandId) ? $brandId : null;

        $query = RepairBuddyDevice::query()
            ->where('is_active', true)
            ->where('disable_in_booking_form', false)
            ->orderBy('model');

        if ($typeId !== null) {
            $query->where('device_type_id', $typeId);
        }

        if ($brandId !== null) {
            $query->where('device_brand_id', $brandId);
        }

        $devices = $query->limit(1000)->get();

        return response()->json([
            'devices' => $devices->map(fn (RepairBuddyDevice $d) => [
                'id' => $d->id,
                'model' => $d->model,
            ]),
        ]);
    }

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
                $bookings[$k] = is_numeric($val) && (int) $val > 0 ? (string) ((int) $val) : null;
            }
        }

        $bookings['turnBookingFormsToJobs'] = $request->boolean('wcrb_turn_booking_forms_to_jobs');
        $bookings['turnOffOtherDeviceBrands'] = $request->boolean('wcrb_turn_off_other_device_brands');
        $bookings['turnOffOtherService'] = $request->boolean('wcrb_turn_off_other_service');
        $bookings['turnOffServicePrice'] = $request->boolean('wcrb_turn_off_service_price');
        $bookings['turnOffIdImeiBooking'] = $request->boolean('wcrb_turn_off_idimei_booking');

        $store->set('bookings', $bookings);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_bookings')
            ->with('status', 'Booking settings updated.')
            ->withInput();
    }
}

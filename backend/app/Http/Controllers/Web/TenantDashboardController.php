<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDashboardController extends Controller
{
    public function updateDevicesBrandsSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'enablePinCodeField' => ['nullable', 'in:on'],
            'showPinCodeInDocuments' => ['nullable', 'in:on'],
            'useWooProductsAsDevices' => ['nullable', 'in:on'],

            'labels' => ['sometimes', 'array'],
            'labels.note' => ['nullable', 'string', 'max:255'],
            'labels.pin' => ['nullable', 'string', 'max:255'],
            'labels.device' => ['nullable', 'string', 'max:255'],
            'labels.deviceBrand' => ['nullable', 'string', 'max:255'],
            'labels.deviceType' => ['nullable', 'string', 'max:255'],
            'labels.imei' => ['nullable', 'string', 'max:255'],

            'additionalDeviceFields' => ['sometimes', 'array'],
            'additionalDeviceFields.*.id' => ['nullable', 'string', 'max:255'],
            'additionalDeviceFields.*.label' => ['nullable', 'string', 'max:255'],
            'additionalDeviceFields.*.type' => ['nullable', 'in:text'],
            'additionalDeviceFields.*.displayInBookingForm' => ['nullable', 'in:1'],
            'additionalDeviceFields.*.displayInInvoice' => ['nullable', 'in:1'],
            'additionalDeviceFields.*.displayForCustomer' => ['nullable', 'in:1'],

            'pickupDeliveryEnabled' => ['nullable', 'in:on'],
            'pickupCharge' => ['nullable', 'string', 'max:64'],
            'deliveryCharge' => ['nullable', 'string', 'max:64'],
            'rentalEnabled' => ['nullable', 'in:on'],
            'rentalPerDay' => ['nullable', 'string', 'max:64'],
            'rentalPerWeek' => ['nullable', 'string', 'max:64'],
        ]);

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $devicesBrands = $repairBuddySettings['devicesBrands'] ?? [];
        if (! is_array($devicesBrands)) {
            $devicesBrands = [];
        }

        $devicesBrands['enablePinCodeField'] = array_key_exists('enablePinCodeField', $validated);
        $devicesBrands['showPinCodeInDocuments'] = array_key_exists('showPinCodeInDocuments', $validated);
        $devicesBrands['useWooProductsAsDevices'] = array_key_exists('useWooProductsAsDevices', $validated);

        $labels = $devicesBrands['labels'] ?? [];
        if (! is_array($labels)) {
            $labels = [];
        }

        if (array_key_exists('labels', $validated) && is_array($validated['labels'])) {
            foreach (['note', 'pin', 'device', 'deviceBrand', 'deviceType', 'imei'] as $k) {
                if (array_key_exists($k, $validated['labels'])) {
                    $val = $validated['labels'][$k];
                    if (is_string($val)) {
                        $val = trim($val);
                    }
                    $labels[$k] = ($val === '') ? null : $val;
                }
            }
        }

        $devicesBrands['labels'] = $labels;

        $additionalDeviceFields = [];
        if (array_key_exists('additionalDeviceFields', $validated) && is_array($validated['additionalDeviceFields'])) {
            foreach ($validated['additionalDeviceFields'] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $label = $row['label'] ?? null;
                if (! is_string($label) || trim($label) === '') {
                    continue;
                }

                $additionalDeviceFields[] = [
                    'id' => (isset($row['id']) && is_string($row['id']) && trim($row['id']) !== '') ? trim($row['id']) : null,
                    'label' => trim($label),
                    'type' => 'text',
                    'displayInBookingForm' => array_key_exists('displayInBookingForm', $row),
                    'displayInInvoice' => array_key_exists('displayInInvoice', $row),
                    'displayForCustomer' => array_key_exists('displayForCustomer', $row),
                ];
            }
        }
        $devicesBrands['additionalDeviceFields'] = $additionalDeviceFields;

        $devicesBrands['pickupDeliveryEnabled'] = array_key_exists('pickupDeliveryEnabled', $validated);
        $devicesBrands['pickupCharge'] = array_key_exists('pickupCharge', $validated) ? $validated['pickupCharge'] : null;
        $devicesBrands['deliveryCharge'] = array_key_exists('deliveryCharge', $validated) ? $validated['deliveryCharge'] : null;

        $devicesBrands['rentalEnabled'] = array_key_exists('rentalEnabled', $validated);
        $devicesBrands['rentalPerDay'] = array_key_exists('rentalPerDay', $validated) ? $validated['rentalPerDay'] : null;
        $devicesBrands['rentalPerWeek'] = array_key_exists('rentalPerWeek', $validated) ? $validated['rentalPerWeek'] : null;

        $repairBuddySettings['devicesBrands'] = $devicesBrands;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Settings updated.')
            ->withInput();
    }

    public function storeDeviceBrand(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim((string) $validated['name']);
        if ($name === '') {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['name' => 'Brand name is required.'])
                ->withInput();
        }

        if (RepairBuddyDeviceBrand::query()->where('name', $name)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['name' => 'Device brand already exists.'])
                ->withInput();
        }

        RepairBuddyDeviceBrand::query()->create([
            'name' => $name,
            'is_active' => true,
        ]);

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand added.')
            ->withInput();
    }

    public function setDeviceBrandActive(Request $request, int $brand)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $brandModel = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();
        $brandModel->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand updated.')
            ->withInput();
    }

    public function deleteDeviceBrand(Request $request, int $brand)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $brandModel = RepairBuddyDeviceBrand::query()->whereKey($brand)->firstOrFail();

        if ($brandModel->devices()->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_manage_devices')
                ->withErrors(['brand' => 'Device brand is in use and cannot be deleted.'])
                ->withInput();
        }

        $brandModel->delete();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_devices')
            ->with('status', 'Brand deleted.')
            ->withInput();
    }

    public function updateBookingSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'booking_email_subject_to_customer' => ['nullable', 'string', 'max:255'],
            'booking_email_body_to_customer' => ['nullable', 'string', 'max:5000'],
            'booking_email_subject_to_admin' => ['nullable', 'string', 'max:255'],
            'booking_email_body_to_admin' => ['nullable', 'string', 'max:5000'],

            'wcrb_turn_booking_forms_to_jobs' => ['nullable', 'in:on'],
            'wcrb_turn_off_other_device_brands' => ['nullable', 'in:on'],
            'wcrb_turn_off_other_service' => ['nullable', 'in:on'],
            'wcrb_turn_off_service_price' => ['nullable', 'in:on'],
            'wcrb_turn_off_idimei_booking' => ['nullable', 'in:on'],

            'wc_booking_default_type' => ['nullable', 'integer', 'min:1'],
            'wc_booking_default_brand' => ['nullable', 'integer', 'min:1'],
            'wc_booking_default_device' => ['nullable', 'integer', 'min:1'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $bookings = $repairBuddySettings['bookings'] ?? [];
        if (! is_array($bookings)) {
            $bookings = [];
        }

        foreach (['booking_email_subject_to_customer', 'booking_email_body_to_customer', 'booking_email_subject_to_admin', 'booking_email_body_to_admin'] as $k) {
            if (array_key_exists($k, $validated)) {
                $val = $validated[$k];
                $bookings[$k] = is_string($val) ? $val : null;
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

        $repairBuddySettings['bookings'] = $bookings;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $setupState,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_bookings')
            ->with('status', 'Booking settings updated.')
            ->withInput();
    }

    public function updateServiceSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wc_service_sidebar_description' => ['nullable', 'string', 'max:1000'],
            'wc_booking_on_service_page_status' => ['nullable', 'in:on'],
            'wc_service_booking_heading' => ['nullable', 'string', 'max:255'],
            'wc_service_booking_form' => ['nullable', 'in:with_type,without_type,warranty_booking'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $services = $repairBuddySettings['services'] ?? [];
        if (! is_array($services)) {
            $services = [];
        }

        if (array_key_exists('wc_service_sidebar_description', $validated)) {
            $services['wc_service_sidebar_description'] = $validated['wc_service_sidebar_description'];
        }
        if (array_key_exists('wc_service_booking_heading', $validated)) {
            $services['wc_service_booking_heading'] = $validated['wc_service_booking_heading'];
        }
        if (array_key_exists('wc_service_booking_form', $validated)) {
            $services['wc_service_booking_form'] = $validated['wc_service_booking_form'];
        }
        $services['disableBookingOnServicePage'] = array_key_exists('wc_booking_on_service_page_status', $validated);

        $repairBuddySettings['services'] = $services;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $setupState,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_service')
            ->with('status', 'Service settings updated.')
            ->withInput();
    }

    public function updatePaymentStatusDisplay(Request $request, string $slug)
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $branchId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $status = \App\Models\RepairBuddyPaymentStatus::query()->where('slug', $slug)->first();
        if (! $status) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_payment_status')
                ->with('status', 'Payment status not found.')
                ->withInput();
        }

        $override = \App\Models\TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('domain', 'payment')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            \App\Models\TenantStatusOverride::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'domain' => 'payment',
                'code' => $slug,
                'label' => $validated['label'] ?? null,
                'color' => $validated['color'] ?? null,
                'sort_order' => $validated['sort_order'] ?? null,
            ]);
        } else {
            $override->forceFill([
                'label' => array_key_exists('label', $validated) ? $validated['label'] : $override->label,
                'color' => array_key_exists('color', $validated) ? $validated['color'] : $override->color,
                'sort_order' => array_key_exists('sort_order', $validated) ? $validated['sort_order'] : $override->sort_order,
            ])->save();
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.')
            ->withInput();
    }

    public function storeMaintenanceReminder(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'email_enabled' => ['sometimes', 'nullable', 'in:on'],
            'sms_enabled' => ['sometimes', 'nullable', 'in:on'],
            'reminder_enabled' => ['sometimes', 'nullable', 'in:on'],
            'email_body' => ['sometimes', 'nullable', 'string'],
            'sms_body' => ['sometimes', 'nullable', 'string'],
        ]);

        $emailEnabled = array_key_exists('email_enabled', $validated);
        $smsEnabled = array_key_exists('sms_enabled', $validated);
        $reminderEnabled = array_key_exists('reminder_enabled', $validated);

        if ($emailEnabled && (! is_string($validated['email_body'] ?? null) || trim((string) $validated['email_body']) === '')) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['email_body' => 'Email body is required when email is enabled.'])
                ->withInput();
        }

        if ($smsEnabled && (! is_string($validated['sms_body'] ?? null) || trim((string) $validated['sms_body']) === '')) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['sms_body' => 'SMS body is required when SMS is enabled.'])
                ->withInput();
        }

        $typeId = array_key_exists('device_type_id', $validated) ? (int) ($validated['device_type_id'] ?? 0) : 0;
        $brandId = array_key_exists('device_brand_id', $validated) ? (int) ($validated['device_brand_id'] ?? 0) : 0;
        $typeId = $typeId > 0 ? $typeId : null;
        $brandId = $brandId > 0 ? $brandId : null;

        if ($typeId !== null && ! \App\Models\RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['device_type_id' => 'Device type is invalid.'])
                ->withInput();
        }

        if ($brandId !== null && ! \App\Models\RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['device_brand_id' => 'Device brand is invalid.'])
                ->withInput();
        }

        \App\Models\RepairBuddyMaintenanceReminder::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'interval_days' => (int) $validated['interval_days'],
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'email_enabled' => $emailEnabled,
            'sms_enabled' => $smsEnabled,
            'reminder_enabled' => $reminderEnabled,
            'email_body' => is_string($validated['email_body'] ?? null) ? (string) $validated['email_body'] : null,
            'sms_body' => is_string($validated['sms_body'] ?? null) ? (string) $validated['sms_body'] : null,
            'created_by_user_id' => $request->user()?->id,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_maintenance_reminder')
            ->with('status', 'Maintenance reminder added.')
            ->withInput();
    }

    public function updateMaintenanceReminder(Request $request, int $reminder)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = \App\Models\RepairBuddyMaintenanceReminder::query()->whereKey($reminder)->first();
        if (! $model) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->with('status', 'Reminder not found.')
                ->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'email_enabled' => ['sometimes', 'nullable', 'in:on'],
            'sms_enabled' => ['sometimes', 'nullable', 'in:on'],
            'reminder_enabled' => ['sometimes', 'nullable', 'in:on'],
            'email_body' => ['sometimes', 'nullable', 'string'],
            'sms_body' => ['sometimes', 'nullable', 'string'],
        ]);

        $emailEnabled = array_key_exists('email_enabled', $validated);
        $smsEnabled = array_key_exists('sms_enabled', $validated);
        $reminderEnabled = array_key_exists('reminder_enabled', $validated);

        if ($emailEnabled && (! is_string($validated['email_body'] ?? null) || trim((string) $validated['email_body']) === '')) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['email_body' => 'Email body is required when email is enabled.'])
                ->withInput();
        }

        if ($smsEnabled && (! is_string($validated['sms_body'] ?? null) || trim((string) $validated['sms_body']) === '')) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['sms_body' => 'SMS body is required when SMS is enabled.'])
                ->withInput();
        }

        $typeId = array_key_exists('device_type_id', $validated) ? (int) ($validated['device_type_id'] ?? 0) : 0;
        $brandId = array_key_exists('device_brand_id', $validated) ? (int) ($validated['device_brand_id'] ?? 0) : 0;
        $typeId = $typeId > 0 ? $typeId : null;
        $brandId = $brandId > 0 ? $brandId : null;

        if ($typeId !== null && ! \App\Models\RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['device_type_id' => 'Device type is invalid.'])
                ->withInput();
        }

        if ($brandId !== null && ! \App\Models\RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_maintenance_reminder')
                ->withErrors(['device_brand_id' => 'Device brand is invalid.'])
                ->withInput();
        }

        $model->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'interval_days' => (int) $validated['interval_days'],
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'email_enabled' => $emailEnabled,
            'sms_enabled' => $smsEnabled,
            'reminder_enabled' => $reminderEnabled,
            'email_body' => is_string($validated['email_body'] ?? null) ? (string) $validated['email_body'] : null,
            'sms_body' => is_string($validated['sms_body'] ?? null) ? (string) $validated['sms_body'] : null,
            'updated_by_user_id' => $request->user()?->id,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_maintenance_reminder')
            ->with('status', 'Maintenance reminder updated.')
            ->withInput();
    }

    public function deleteMaintenanceReminder(Request $request, int $reminder)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = \App\Models\RepairBuddyMaintenanceReminder::query()->whereKey($reminder)->first();
        if ($model) {
            $model->delete();
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_maintenance_reminder')
            ->with('status', 'Maintenance reminder deleted.')
            ->withInput();
    }

    public function updatePagesSetup(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wc_rb_my_account_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_status_check_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_get_feedback_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_device_booking_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_list_services_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_list_parts_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_customer_login_page' => ['nullable', 'string', 'max:2048'],
            'wc_rb_turn_registration_on' => ['nullable', 'in:on'],
        ]);

        $state = $tenant->setup_state;
        if (! is_array($state)) {
            $state = [];
        }

        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $pages = $repairBuddySettings['pages'] ?? [];
        if (! is_array($pages)) {
            $pages = [];
        }

        foreach ([
            'wc_rb_my_account_page_id',
            'wc_rb_status_check_page_id',
            'wc_rb_get_feedback_page_id',
            'wc_rb_device_booking_page_id',
            'wc_rb_list_services_page_id',
            'wc_rb_list_parts_page_id',
            'wc_rb_customer_login_page',
        ] as $key) {
            $value = array_key_exists($key, $validated) ? $validated[$key] : null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $pages[$key] = ($value === '') ? null : $value;
        }

        $pages['wc_rb_turn_registration_on'] = array_key_exists('wc_rb_turn_registration_on', $validated)
            ? 'on'
            : 'off';

        $repairBuddySettings['pages'] = $pages;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_page_settings')
            ->with('status', 'Settings updated.')
            ->withInput();
    }

    public function storeTax(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'tax_name' => ['required', 'string', 'max:255'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_status' => ['nullable', 'in:active,inactive'],
            'tax_is_default' => ['nullable', 'in:on'],
        ]);

        $isActive = ($validated['tax_status'] ?? 'active') === 'active';
        $isDefault = array_key_exists('tax_is_default', $validated);

        DB::transaction(function () use ($validated, $isDefault, $isActive) {
            if ($isDefault) {
                RepairBuddyTax::query()
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            RepairBuddyTax::query()->create([
                'name' => $validated['tax_name'],
                'rate' => $validated['tax_rate'],
                'is_default' => $isDefault,
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax added.')
            ->withInput();
    }

    public function setTaxActive(Request $request, int $tax)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $taxModel = RepairBuddyTax::query()->whereKey($tax)->firstOrFail();
        $taxModel->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax updated.')
            ->withInput();
    }

    public function setTaxDefault(Request $request, int $tax)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $taxModel = RepairBuddyTax::query()->whereKey($tax)->firstOrFail();

        DB::transaction(function () use ($taxModel) {
            RepairBuddyTax::query()->where('is_default', true)->update(['is_default' => false]);
            $taxModel->forceFill(['is_default' => true])->save();
        });

        $tenant->refresh();
        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }
        $taxSettings = $repairBuddySettings['taxes'] ?? [];
        if (! is_array($taxSettings)) {
            $taxSettings = [];
        }
        $taxSettings['defaultTaxId'] = (string) $taxModel->id;
        $repairBuddySettings['taxes'] = $taxSettings;
        $state['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $state])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Default tax updated.')
            ->withInput();
    }

    public function updateTaxSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wc_use_taxes' => ['nullable', 'in:on'],
            'wc_primary_tax' => ['nullable', 'integer', 'min:1'],
            'wc_prices_inclu_exclu' => ['required', 'in:exclusive,inclusive'],
        ]);

        $defaultTaxId = array_key_exists('wc_primary_tax', $validated) ? $validated['wc_primary_tax'] : null;
        if (is_int($defaultTaxId)) {
            $exists = RepairBuddyTax::query()->whereKey($defaultTaxId)->exists();
            if (! $exists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_manage_taxes')
                    ->withErrors(['wc_primary_tax' => 'Selected tax is invalid.'])
                    ->withInput();
            }
        }

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $taxSettings = $repairBuddySettings['taxes'] ?? [];
        if (! is_array($taxSettings)) {
            $taxSettings = [];
        }

        $taxSettings['enableTaxes'] = array_key_exists('wc_use_taxes', $validated);
        $taxSettings['defaultTaxId'] = is_int($defaultTaxId) ? (string) $defaultTaxId : null;
        $taxSettings['invoiceAmounts'] = $validated['wc_prices_inclu_exclu'];

        $repairBuddySettings['taxes'] = $taxSettings;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_taxes')
            ->with('status', 'Tax settings updated.')
            ->withInput();
    }

    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $screen = is_string($request->query('screen')) && $request->query('screen') !== ''
            ? (string) $request->query('screen')
            : 'dashboard';

        if ($screen === 'settings') {
            $updateStatus = $request->query('update_status');

            if (isset($updateStatus) && (string) $updateStatus !== '') {
                $class_settings = '';
                $class_activation = '';
                $class_status = ' is-active';
            } else {
                $class_settings = ' is-active';
                $class_status = '';
                $class_activation = '';
            }

            $class_general_settings = ($request->input('wc_rep_settings') === '1') ? ' is-active' : '';
            $class_settings = empty($class_general_settings) ? $class_settings : '';

            $class_invoices_settings = ($request->input('wc_rep_labels_submit') === '1') ? ' is-active' : '';
            $class_settings = empty($class_invoices_settings) ? $class_settings : '';

            $class_currency_settings = ($request->input('wc_rep_currency_submit') === '1') ? ' is-active' : '';
            $class_settings = empty($class_currency_settings) ? $class_settings : '';

            $updatePaymentStatus = $request->query('update_payment_status');
            if (isset($updatePaymentStatus) && (string) $updatePaymentStatus !== '') {
                $class_settings = '';
                $class_status = '';
            }

            $unselect = $request->query('unselect');
            if (isset($unselect) && (string) $unselect !== '') {
                $class_settings = '';
                $class_status = '';
            }

            $settingsTabMenuItemsHtml = '';
            $settingsTabBodyHtml = '';

            $setupState = $tenant?->setup_state;
            if (! is_array($setupState)) {
                $setupState = [];
            }

            $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
            if (! is_array($repairBuddySettings)) {
                $repairBuddySettings = [];
            }

            $pagesSettings = $repairBuddySettings['pages'] ?? [];
            if (! is_array($pagesSettings)) {
                $pagesSettings = [];
            }

            $pagesSetupValues = [
                'wc_rb_my_account_page_id' => $pagesSettings['wc_rb_my_account_page_id'] ?? null,
                'wc_rb_status_check_page_id' => $pagesSettings['wc_rb_status_check_page_id'] ?? null,
                'wc_rb_get_feedback_page_id' => $pagesSettings['wc_rb_get_feedback_page_id'] ?? null,
                'wc_rb_device_booking_page_id' => $pagesSettings['wc_rb_device_booking_page_id'] ?? null,
                'wc_rb_list_services_page_id' => $pagesSettings['wc_rb_list_services_page_id'] ?? null,
                'wc_rb_list_parts_page_id' => $pagesSettings['wc_rb_list_parts_page_id'] ?? null,
                'wc_rb_customer_login_page' => $pagesSettings['wc_rb_customer_login_page'] ?? null,
                'wc_rb_turn_registration_on' => ($pagesSettings['wc_rb_turn_registration_on'] ?? 'off') === 'on',
            ];

            $taxes = RepairBuddyTax::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->limit(200)
                ->get();

            $deviceBrands = RepairBuddyDeviceBrand::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->limit(200)
                ->get();

            $devicesBrandsSettings = $repairBuddySettings['devicesBrands'] ?? [];
            if (! is_array($devicesBrandsSettings)) {
                $devicesBrandsSettings = [];
            }

            $deviceLabels = $devicesBrandsSettings['labels'] ?? [];
            if (! is_array($deviceLabels)) {
                $deviceLabels = [];
            }

            $additionalDeviceFields = $devicesBrandsSettings['additionalDeviceFields'] ?? [];
            if (! is_array($additionalDeviceFields)) {
                $additionalDeviceFields = [];
            }

            $pickupDeliveryEnabled = (bool) ($devicesBrandsSettings['pickupDeliveryEnabled'] ?? false);
            $pickupCharge = is_string($devicesBrandsSettings['pickupCharge'] ?? null) ? (string) $devicesBrandsSettings['pickupCharge'] : '';
            $deliveryCharge = is_string($devicesBrandsSettings['deliveryCharge'] ?? null) ? (string) $devicesBrandsSettings['deliveryCharge'] : '';

            $rentalEnabled = (bool) ($devicesBrandsSettings['rentalEnabled'] ?? false);
            $rentalPerDay = is_string($devicesBrandsSettings['rentalPerDay'] ?? null) ? (string) $devicesBrandsSettings['rentalPerDay'] : '';
            $rentalPerWeek = is_string($devicesBrandsSettings['rentalPerWeek'] ?? null) ? (string) $devicesBrandsSettings['rentalPerWeek'] : '';

            $devicesBrandsUi = [
                'enablePinCodeField' => (bool) ($devicesBrandsSettings['enablePinCodeField'] ?? false),
                'showPinCodeInDocuments' => (bool) ($devicesBrandsSettings['showPinCodeInDocuments'] ?? false),
                'useWooProductsAsDevices' => (bool) ($devicesBrandsSettings['useWooProductsAsDevices'] ?? false),
                'labels' => [
                    'note' => $deviceLabels['note'] ?? null,
                    'pin' => $deviceLabels['pin'] ?? null,
                    'device' => $deviceLabels['device'] ?? null,
                    'deviceBrand' => $deviceLabels['deviceBrand'] ?? null,
                    'deviceType' => $deviceLabels['deviceType'] ?? null,
                    'imei' => $deviceLabels['imei'] ?? null,
                ],
            ];

            if (old('enablePinCodeField') !== null) {
                $devicesBrandsUi['enablePinCodeField'] = (string) old('enablePinCodeField') === 'on';
            }
            if (old('showPinCodeInDocuments') !== null) {
                $devicesBrandsUi['showPinCodeInDocuments'] = (string) old('showPinCodeInDocuments') === 'on';
            }
            if (old('useWooProductsAsDevices') !== null) {
                $devicesBrandsUi['useWooProductsAsDevices'] = (string) old('useWooProductsAsDevices') === 'on';
            }
            foreach (['note', 'pin', 'device', 'deviceBrand', 'deviceType', 'imei'] as $k) {
                $oldLabel = old('labels.' . $k);
                if ($oldLabel !== null) {
                    $devicesBrandsUi['labels'][$k] = is_string($oldLabel) ? $oldLabel : null;
                }
            }

            $taxSettings = $repairBuddySettings['taxes'] ?? [];
            if (! is_array($taxSettings)) {
                $taxSettings = [];
            }

            $bookingSettings = $repairBuddySettings['bookings'] ?? [];
            if (! is_array($bookingSettings)) {
                $bookingSettings = [];
            }

            $serviceSettings = $repairBuddySettings['services'] ?? [];
            if (! is_array($serviceSettings)) {
                $serviceSettings = [];
            }

            $paymentStatuses = \App\Models\RepairBuddyPaymentStatus::query()->orderBy('id')->get();
            $paymentStatusOverrides = \App\Models\TenantStatusOverride::query()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('domain', 'payment')
                ->get()
                ->keyBy('code');

            $maintenanceReminders = \App\Models\RepairBuddyMaintenanceReminder::query()
                ->with(['deviceType', 'deviceBrand'])
                ->orderByDesc('id')
                ->limit(200)
                ->get();

            $taxEnable = (bool) ($taxSettings['enableTaxes'] ?? false);
            $taxInvoiceAmounts = is_string($taxSettings['invoiceAmounts'] ?? null) ? (string) $taxSettings['invoiceAmounts'] : 'exclusive';
            $taxDefaultId = $taxSettings['defaultTaxId'] ?? null;
            if (is_string($taxDefaultId) && $taxDefaultId !== '' && ctype_digit($taxDefaultId)) {
                $taxDefaultId = (int) $taxDefaultId;
            } else {
                $taxDefaultId = null;
            }

            $oldUseTaxes = old('wc_use_taxes');
            if ($oldUseTaxes !== null) {
                $taxEnable = (string) $oldUseTaxes === 'on';
            }
            $oldAmounts = old('wc_prices_inclu_exclu');
            if (is_string($oldAmounts) && in_array($oldAmounts, ['exclusive', 'inclusive'], true)) {
                $taxInvoiceAmounts = $oldAmounts;
            }
            $oldDefault = old('wc_primary_tax');
            if ($oldDefault !== null && ctype_digit((string) $oldDefault)) {
                $taxDefaultId = (int) $oldDefault;
            }

            foreach (array_keys($pagesSetupValues) as $key) {
                $old = old($key);
                if ($old !== null) {
                    if ($key === 'wc_rb_turn_registration_on') {
                        $pagesSetupValues[$key] = (string) $old === 'on';
                    } else {
                        $pagesSetupValues[$key] = is_string($old) ? $old : null;
                    }
                }
            }

            $pagesSetupOptions = [
                'dashboard' => [
                    'label' => __('Tenant Dashboard (Settings screen)'),
                    'value' => $tenant?->slug ? route('tenant.dashboard', ['business' => $tenant->slug]) : '#',
                ],
                'public_status' => [
                    'label' => __('Public Status Check (API endpoint)'),
                    'value' => $tenant?->slug ? url('/api/t/'.$tenant->slug.'/status/lookup') : '#',
                ],
                'public_booking' => [
                    'label' => __('Public Booking (API endpoint)'),
                    'value' => $tenant?->slug ? url('/api/t/'.$tenant->slug.'/booking/config') : '#',
                ],
                'public_services' => [
                    'label' => __('Public Services (API endpoint)'),
                    'value' => $tenant?->slug ? url('/api/t/'.$tenant->slug.'/services') : '#',
                ],
                'public_parts' => [
                    'label' => __('Public Parts (API endpoint)'),
                    'value' => $tenant?->slug ? url('/api/t/'.$tenant->slug.'/parts') : '#',
                ],
                'public_portal_tickets' => [
                    'label' => __('Public Portal Tickets (API endpoint)'),
                    'value' => $tenant?->slug ? url('/api/t/'.$tenant->slug.'/portal/tickets') : '#',
                ],
            ];

            $settingsTabs = [
                ['id' => 'wc_rb_page_settings', 'label' => __('Pages Setup'), 'heading' => __('Pages Setup')],
                ['id' => 'wc_rb_manage_devices', 'label' => __('Devices & Brands'), 'heading' => __('Brands & Devices')],
                ['id' => 'wc_rb_manage_bookings', 'label' => __('Booking Settings'), 'heading' => __('Booking Settings')],
                ['id' => 'wc_rb_manage_service', 'label' => __('Service Settings'), 'heading' => __('Service Settings')],
                ['id' => 'wcrb_estimates_tab', 'label' => __('Estimates'), 'heading' => __('Estimates')],
                ['id' => 'wc_rb_payment_status', 'label' => __('Payment Status'), 'heading' => __('Payment Status')],
                ['id' => 'wc_rb_manage_taxes', 'label' => __('Manage Taxes'), 'heading' => __('Tax Settings')],
                ['id' => 'wc_rb_maintenance_reminder', 'label' => __('Maintenance Reminders'), 'heading' => __('Maintenance Reminders')],
                ['id' => 'wcrb_timelog_tab', 'label' => __('Time Log Settings'), 'heading' => __('Time Log Settings')],
                ['id' => 'wcrb_styling', 'label' => __('Styling & Labels'), 'heading' => __('Styling & Labels')],
                ['id' => 'wcrb_reviews_tab', 'label' => __('Job Reviews'), 'heading' => __('Job Reviews')],
                ['id' => 'wc_rb_page_sms_IDENTIFIER', 'label' => __('SMS'), 'heading' => __('SMS')],
                ['id' => 'wc_rb_manage_account', 'label' => __('My Account Settings'), 'heading' => __('My Account Settings')],
                ['id' => 'wcrb_signature_workflow', 'label' => __('Signature Workflow'), 'heading' => __('Digital Signature Workflow')],
            ];

            foreach ($settingsTabs as $tab) {
                $tabId = $tab['id'];
                $tabLabel = $tab['label'];
                $heading = $tab['heading'];

                $settingsTabMenuItemsHtml .= '<li class="tabs-title" role="presentation">';
                $settingsTabMenuItemsHtml .= '<a href="#' . e($tabId) . '" role="tab" aria-controls="' . e($tabId) . '" aria-selected="true" id="' . e($tabId) . '-label">';
                $settingsTabMenuItemsHtml .= '<h2>' . e($tabLabel) . '</h2>';
                $settingsTabMenuItemsHtml .= '</a>';
                $settingsTabMenuItemsHtml .= '</li>';

                if ($tabId === 'wc_rb_page_settings') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="' . e($tabId) . '" role="tabpanel" aria-hidden="true" aria-labelledby="' . e($tabId) . '-label">';
                    $settingsTabBodyHtml .= '<div class="wrap"><div class="form-message"></div>';
                    $settingsTabBodyHtml .= '<h3>' . e(__('You may change pages which have related shortcodes.')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.pages_setup.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table cellpadding="5" cellspacing="5" class="form-table border"><tbody>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_my_account_page_id"><strong>' . e(__('Select Dashboard Page')) . '</strong></label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_my_account_page_id" id="wc_rb_my_account_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select my account page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_my_account_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page for customers, technicians, store managers and administrators to perform various tasks page with shortcode ')) . '<strong>[wc_cr_my_account]</strong></label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_status_check_page_id">' . e(__('Select Status Check Page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_status_check_page_id" id="wc_rb_status_check_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select status page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_status_check_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page that have shortcode ')) . '<strong>[wc_order_status_form]</strong> ' . e(__('If set this would be used to send link to customers for status check in email and other notification mediums.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_get_feedback_page_id">' . e(__('Get feedback on job page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_get_feedback_page_id" id="wc_rb_get_feedback_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select job review page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_get_feedback_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page that have shortcode ')) . '<strong>[wc_get_order_feedback]</strong> ' . e(__('If set this would be used to send link to customers so they can leave feedback on jobs.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_device_booking_page_id">' . e(__('Select Device Booking Page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_device_booking_page_id" id="wc_rb_device_booking_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select booking page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_device_booking_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page for booking process with shortcode ')) . '<strong>[wc_book_my_service]</strong></label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_list_services_page_id">' . e(__('Select Services Page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_list_services_page_id" id="wc_rb_list_services_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select services page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_list_services_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page lists services should have shortcode ')) . '<strong>[wc_list_services]</strong></label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_list_parts_page_id">' . e(__('Select Parts Page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_list_parts_page_id" id="wc_rb_list_parts_page_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select parts page')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_list_parts_page_id'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page lists parts should have shortcode ')) . '<strong>[wc_list_products]</strong> ' . e(__('If you are using WooCommerce products as parts then its not needed.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<h3>' . e(__('Redirect user after login.')) . '</h3>';
                    $settingsTabBodyHtml .= '<table cellpadding="5" cellspacing="5" class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_customer_login_page">' . e(__('Select Page for customer to redirect after login')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_rb_customer_login_page" id="wc_rb_customer_login_page" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select customer page after login')) . '</option>';
                    foreach ($pagesSetupOptions as $opt) {
                        $selected = ((string) ($pagesSetupValues['wc_rb_customer_login_page'] ?? '') === (string) $opt['value']) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e($opt['value']) . '"' . $selected . '>' . e($opt['label']) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page that have shortcode ')) . '<strong>[wc_cr_my_account]</strong> ' . e(__('If you want to use WooCommerce My Account page please select that.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $checked = ($pagesSetupValues['wc_rb_turn_registration_on'] ?? false) ? ' checked="checked"' : '';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_rb_turn_registration_on">' . e(__('Turn on Customer Registration on My Account Page')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="checkbox"' . $checked . ' name="wc_rb_turn_registration_on" id="wc_rb_turn_registration_on" />';
                    $settingsTabBodyHtml .= '<label for="wc_rb_turn_registration_on">' . e(__('If checked customer registration form will appear in my account page which have shortcode ')) . '<strong>[wc_cr_my_account]</strong></label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><td colspan="2"><button class="button button-primary" type="submit">' . e(__('Submit')) . '</button></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_manage_devices') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_manage_devices" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_devices-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';

                    $settingsTabBodyHtml .= '<h2>' . e(__('Brands & Devices')) . '</h2>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Device Settings')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.devices_brands.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="enablePinCodeField">' . e(__('Enable Pin Code Field in Jobs page')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($devicesBrandsUi['enablePinCodeField'] ? 'checked="checked"' : '') . ' name="enablePinCodeField" id="enablePinCodeField" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="showPinCodeInDocuments">' . e(__('Show Pin Code in Invoices/Emails/Status Check')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($devicesBrandsUi['showPinCodeInDocuments'] ? 'checked="checked"' : '') . ' name="showPinCodeInDocuments" id="showPinCodeInDocuments" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="useWooProductsAsDevices">' . e(__('Replace devices & brands with WooCommerce products')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($devicesBrandsUi['useWooProductsAsDevices'] ? 'checked="checked"' : '') . ' name="useWooProductsAsDevices" id="useWooProductsAsDevices" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Other Labels')) . '</th>';
                    $settingsTabBodyHtml .= '<td><table class="form-table no-padding-table"><tr>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Note label like Device Note'));
                    $settingsTabBodyHtml .= '<input name="labels[note]" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['note'] ?? '')) . '" type="text" placeholder="' . e(__('Note')) . '" /></label></td>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Pin Code/Password Label'));
                    $settingsTabBodyHtml .= '<input name="labels[pin]" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['pin'] ?? '')) . '" type="text" placeholder="' . e(__('Pin Code/Password')) . '" /></label></td>';
                    $settingsTabBodyHtml .= '</tr></table></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Device Label')) . '</th>';
                    $settingsTabBodyHtml .= '<td><table class="form-table no-padding-table"><tr>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Singular device label'));
                    $settingsTabBodyHtml .= '<input name="labels[device]" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['device'] ?? '')) . '" type="text" placeholder="' . e(__('Device')) . '" /></label></td>';
                    $settingsTabBodyHtml .= '</tr></table></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Device Brand Label')) . '</th>';
                    $settingsTabBodyHtml .= '<td><table class="form-table no-padding-table"><tr>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Singular device brand label'));
                    $settingsTabBodyHtml .= '<input name="labels[deviceBrand]" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['deviceBrand'] ?? '')) . '" type="text" placeholder="' . e(__('Device Brand')) . '" /></label></td>';
                    $settingsTabBodyHtml .= '</tr></table></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Device Type Label')) . '</th>';
                    $settingsTabBodyHtml .= '<td><table class="form-table no-padding-table"><tr>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Singular device type label'));
                    $settingsTabBodyHtml .= '<input name="labels[deviceType]" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['deviceType'] ?? '')) . '" type="text" placeholder="' . e(__('Device Type')) . '" /></label></td>';
                    $settingsTabBodyHtml .= '</tr></table></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="labels_imei">' . e(__('ID/IMEI Label')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input name="labels[imei]" id="labels_imei" class="regular-text" value="' . e((string) ($devicesBrandsUi['labels']['imei'] ?? '')) . '" type="text" placeholder="' . e(__('ID/IMEI')) . '" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="pickupDeliveryEnabled">' . e(__('Offer pickup and delivery?')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($pickupDeliveryEnabled ? 'checked="checked"' : '') . ' name="pickupDeliveryEnabled" id="pickupDeliveryEnabled" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="pickupCharge">' . e(__('Pick up charge')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input name="pickupCharge" id="pickupCharge" class="regular-text" value="' . e($pickupCharge) . '" type="text" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="deliveryCharge">' . e(__('Delivery charge')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input name="deliveryCharge" id="deliveryCharge" class="regular-text" value="' . e($deliveryCharge) . '" type="text" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="rentalEnabled">' . e(__('Offer device rental?')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($rentalEnabled ? 'checked="checked"' : '') . ' name="rentalEnabled" id="rentalEnabled" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Device rent')) . '</th><td>';
                    $settingsTabBodyHtml .= '<table class="form-table no-padding-table"><tr>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Device rent per day'));
                    $settingsTabBodyHtml .= '<input name="rentalPerDay" class="regular-text" value="' . e($rentalPerDay) . '" type="text" /></label></td>';
                    $settingsTabBodyHtml .= '<td><label>' . e(__('Device rent per week'));
                    $settingsTabBodyHtml .= '<input name="rentalPerWeek" class="regular-text" value="' . e($rentalPerWeek) . '" type="text" /></label></td>';
                    $settingsTabBodyHtml .= '</tr></table>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $maxRows = max(1, min(10, count($additionalDeviceFields) + 1));
                    for ($i = 0; $i < $maxRows; $i++) {
                        $row = $additionalDeviceFields[$i] ?? [];
                        $rowId = is_array($row) && is_string($row['id'] ?? null) ? (string) $row['id'] : '';
                        $rowLabel = is_array($row) && is_string($row['label'] ?? null) ? (string) $row['label'] : '';
                        $dBooking = is_array($row) && ($row['displayInBookingForm'] ?? false) ? ' checked="checked"' : '';
                        $dInvoice = is_array($row) && ($row['displayInInvoice'] ?? false) ? ' checked="checked"' : '';
                        $dCustomer = is_array($row) && ($row['displayForCustomer'] ?? false) ? ' checked="checked"' : '';

                        $settingsTabBodyHtml .= '<tr>';
                        $settingsTabBodyHtml .= '<td><label>' . e(__('Field label'));
                        $settingsTabBodyHtml .= '<input class="regular-text" name="additionalDeviceFields[' . e((string) $i) . '][label]" value="' . e($rowLabel) . '" type="text" /></label>';
                        $settingsTabBodyHtml .= '<input type="hidden" name="additionalDeviceFields[' . e((string) $i) . '][id]" value="' . e($rowId) . '" />';
                        $settingsTabBodyHtml .= '<input type="hidden" name="additionalDeviceFields[' . e((string) $i) . '][type]" value="text" />';
                        $settingsTabBodyHtml .= '</td>';
                        $settingsTabBodyHtml .= '<td><label>' . e(__('In booking form?')) . '<input type="checkbox" name="additionalDeviceFields[' . e((string) $i) . '][displayInBookingForm]" value="1"' . $dBooking . ' /></label></td>';
                        $settingsTabBodyHtml .= '<td><label>' . e(__('In invoice?')) . '<input type="checkbox" name="additionalDeviceFields[' . e((string) $i) . '][displayInInvoice]" value="1"' . $dInvoice . ' /></label></td>';
                        $settingsTabBodyHtml .= '<td><label>' . e(__('In customer output?')) . '<input type="checkbox" name="additionalDeviceFields[' . e((string) $i) . '][displayForCustomer]" value="1"' . $dCustomer . ' /></label></td>';
                        $settingsTabBodyHtml .= '</tr>';
                    }

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Manage Brands')) . '</h3>';

                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.device_brands.store', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="rb_brand_name">' . e(__('Add brand')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input name="name" id="rb_brand_name" class="regular-text" value="" type="text" placeholder="' . e(__('Brand name')) . '" required /> ';
                    $settingsTabBodyHtml .= '<button class="button button-primary" type="submit">' . e(__('Add')) . '</button></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</form>';

                    $settingsTabBodyHtml .= '<table class="wp-list-table widefat fixed striped posts"><thead><tr>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('ID')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Name')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Status')) . '</th>';
                    $settingsTabBodyHtml .= '<th class="column-action">' . e(__('Actions')) . '</th>';
                    $settingsTabBodyHtml .= '</tr></thead><tbody>';

                    if ($deviceBrands->count() > 0) {
                        foreach ($deviceBrands as $b) {
                            $settingsTabBodyHtml .= '<tr>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $b->id) . '</td>';
                            $settingsTabBodyHtml .= '<td><strong>' . e((string) $b->name) . '</strong></td>';
                            $settingsTabBodyHtml .= '<td>' . e($b->is_active ? 'active' : 'inactive') . '</td>';
                            $settingsTabBodyHtml .= '<td>';

                            $settingsTabBodyHtml .= '<form method="post" style="display:inline;" action="' . e($tenant?->slug ? route('tenant.settings.device_brands.active', ['business' => $tenant->slug, 'brand' => $b->id]) : '#') . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="is_active" value="' . e($b->is_active ? '0' : '1') . '">';
                            $settingsTabBodyHtml .= '<button type="submit" class="button button-small">' . e(__('Change Status')) . '</button>';
                            $settingsTabBodyHtml .= '</form> ';

                            $settingsTabBodyHtml .= '<form method="post" style="display:inline;" action="' . e($tenant?->slug ? route('tenant.settings.device_brands.delete', ['business' => $tenant->slug, 'brand' => $b->id]) : '#') . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<button type="submit" class="button button-small">' . e(__('Delete')) . '</button>';
                            $settingsTabBodyHtml .= '</form>';

                            $settingsTabBodyHtml .= '</td>';
                            $settingsTabBodyHtml .= '</tr>';
                        }
                    } else {
                        $settingsTabBodyHtml .= '<tr><td colspan="4">' . e(__('No brands yet.')) . '</td></tr>';
                    }

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_manage_bookings') {
                    $menuName = $tenant?->name ?: 'RepairBuddy';

                    $emailSubjectCustomer = (string) ($bookingSettings['booking_email_subject_to_customer'] ?? ('We have received your booking order! | ' . $menuName));
                    $emailBodyCustomer = (string) ($bookingSettings['booking_email_body_to_customer'] ?? '');
                    $emailSubjectAdmin = (string) ($bookingSettings['booking_email_subject_to_admin'] ?? ('You have new booking order | ' . $menuName));
                    $emailBodyAdmin = (string) ($bookingSettings['booking_email_body_to_admin'] ?? '');

                    $turnBookingFormsToJobs = (bool) ($bookingSettings['turnBookingFormsToJobs'] ?? false);
                    $turnOffOtherDeviceBrands = (bool) ($bookingSettings['turnOffOtherDeviceBrands'] ?? false);
                    $turnOffOtherService = (bool) ($bookingSettings['turnOffOtherService'] ?? false);
                    $turnOffServicePrice = (bool) ($bookingSettings['turnOffServicePrice'] ?? false);
                    $turnOffIdImeiBooking = (bool) ($bookingSettings['turnOffIdImeiBooking'] ?? false);

                    $defaultTypeId = $bookingSettings['wc_booking_default_type'] ?? null;
                    $defaultBrandId = $bookingSettings['wc_booking_default_brand'] ?? null;
                    $defaultDeviceId = $bookingSettings['wc_booking_default_device'] ?? null;

                    $deviceTypes = \App\Models\RepairBuddyDeviceType::query()->orderBy('name')->limit(200)->get();
                    $deviceBrands = \App\Models\RepairBuddyDeviceBrand::query()->orderBy('name')->limit(200)->get();
                    $devices = \App\Models\RepairBuddyDevice::query()->orderBy('model')->limit(200)->get();

                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_manage_bookings" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_bookings-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h2>' . e(__('Booking Email To Customer')) . '</h2>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.bookings.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="booking_email_subject_to_customer">' . e(__('Email subject')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="text" id="booking_email_subject_to_customer" name="booking_email_subject_to_customer" class="regular-text" value="' . e(old('booking_email_subject_to_customer', $emailSubjectCustomer)) . '" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="booking_email_body_to_customer">' . e(__('Email body')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><textarea id="booking_email_body_to_customer" name="booking_email_body_to_customer" rows="6" class="large-text">' . e(old('booking_email_body_to_customer', $emailBodyCustomer)) . '</textarea>';
                    $settingsTabBodyHtml .= '<p class="description">' . e(__('Available Keywords')) . ' {{customer_full_name}} {{customer_device_label}} {{status_check_link}} {{start_anch_status_check_link}} {{end_anch_status_check_link}} {{order_invoice_details}} {{job_id}} {{case_number}}</p>';
                    $settingsTabBodyHtml .= '</td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<h2>' . e(__('Booking email to administrator')) . '</h2>';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="booking_email_subject_to_admin">' . e(__('Email subject')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="text" id="booking_email_subject_to_admin" name="booking_email_subject_to_admin" class="regular-text" value="' . e(old('booking_email_subject_to_admin', $emailSubjectAdmin)) . '" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="booking_email_body_to_admin">' . e(__('Email body')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><textarea id="booking_email_body_to_admin" name="booking_email_body_to_admin" rows="6" class="large-text">' . e(old('booking_email_body_to_admin', $emailBodyAdmin)) . '</textarea>';
                    $settingsTabBodyHtml .= '<p class="description">' . e(__('Available Keywords')) . ' {{customer_full_name}} {{customer_device_label}} {{status_check_link}} {{start_anch_status_check_link}} {{end_anch_status_check_link}} {{order_invoice_details}} {{job_id}} {{case_number}}</p>';
                    $settingsTabBodyHtml .= '</td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wcrb_turn_booking_forms_to_jobs">' . e(__('Booking & Quote Forms')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ((old('wcrb_turn_booking_forms_to_jobs') !== null ? old('wcrb_turn_booking_forms_to_jobs') === 'on' : $turnBookingFormsToJobs) ? 'checked="checked"' : '') . ' name="wcrb_turn_booking_forms_to_jobs" id="wcrb_turn_booking_forms_to_jobs" /> ';
                    $settingsTabBodyHtml .= '<label for="wcrb_turn_booking_forms_to_jobs">' . e(__('Send booking forms & quote forms to jobs instead of estimates')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wcrb_turn_off_other_device_brands">' . e(__('Other Devices & Brands')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ((old('wcrb_turn_off_other_device_brands') !== null ? old('wcrb_turn_off_other_device_brands') === 'on' : $turnOffOtherDeviceBrands) ? 'checked="checked"' : '') . ' name="wcrb_turn_off_other_device_brands" id="wcrb_turn_off_other_device_brands" /> ';
                    $settingsTabBodyHtml .= '<label for="wcrb_turn_off_other_device_brands">' . e(__('Turn off other option for devices and brands')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wcrb_turn_off_other_service">' . e(__('Other Service')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ((old('wcrb_turn_off_other_service') !== null ? old('wcrb_turn_off_other_service') === 'on' : $turnOffOtherService) ? 'checked="checked"' : '') . ' name="wcrb_turn_off_other_service" id="wcrb_turn_off_other_service" /> ';
                    $settingsTabBodyHtml .= '<label for="wcrb_turn_off_other_service">' . e(__('Turn off other service option')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wcrb_turn_off_service_price">' . e(__('Disable Service Prices')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ((old('wcrb_turn_off_service_price') !== null ? old('wcrb_turn_off_service_price') === 'on' : $turnOffServicePrice) ? 'checked="checked"' : '') . ' name="wcrb_turn_off_service_price" id="wcrb_turn_off_service_price" /> ';
                    $settingsTabBodyHtml .= '<label for="wcrb_turn_off_service_price">' . e(__('Turn off prices from services')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wcrb_turn_off_idimei_booking">' . e(__('Disable ID/IMEI Field')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ((old('wcrb_turn_off_idimei_booking') !== null ? old('wcrb_turn_off_idimei_booking') === 'on' : $turnOffIdImeiBooking) ? 'checked="checked"' : '') . ' name="wcrb_turn_off_idimei_booking" id="wcrb_turn_off_idimei_booking" /> ';
                    $settingsTabBodyHtml .= '<label for="wcrb_turn_off_idimei_booking">' . e(__('Turn off id/imei field from booking form')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_booking_default_type">' . e(__('Default Device Type')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_booking_default_type" id="wc_booking_default_type" class="regular-text">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select')) . '</option>';
                    foreach ($deviceTypes as $dt) {
                        $selected = (string) old('wc_booking_default_type', $defaultTypeId) === (string) $dt->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $dt->id) . '"' . $selected . '>' . e((string) $dt->name) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_booking_default_brand">' . e(__('Default Device Brand')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_booking_default_brand" id="wc_booking_default_brand" class="regular-text">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select')) . '</option>';
                    foreach ($deviceBrands as $db) {
                        $selected = (string) old('wc_booking_default_brand', $defaultBrandId) === (string) $db->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $db->id) . '"' . $selected . '>' . e((string) $db->name) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_booking_default_device">' . e(__('Default Device')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="wc_booking_default_device" id="wc_booking_default_device" class="regular-text">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select')) . '</option>';
                    foreach ($devices as $d) {
                        $label = trim((string) ($d->model ?? ''));
                        if ($label === '') {
                            $label = 'Device #' . $d->id;
                        }
                        $selected = (string) old('wc_booking_default_device', $defaultDeviceId) === (string) $d->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $d->id) . '"' . $selected . '>' . e($label) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_manage_service') {
                    $sidebarDescription = (string) ($serviceSettings['wc_service_sidebar_description'] ?? __('Below you can check price by type or brand and to get accurate value check devices.'));
                    $bookingHeading = (string) ($serviceSettings['wc_service_booking_heading'] ?? __('Book Service'));
                    $disableBookingOnServicePage = (bool) ($serviceSettings['disableBookingOnServicePage'] ?? false);
                    $bookingForm = (string) ($serviceSettings['wc_service_booking_form'] ?? 'without_type');
                    if (! in_array($bookingForm, ['with_type', 'without_type', 'warranty_booking'], true)) {
                        $bookingForm = 'without_type';
                    }

                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_manage_service" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_service-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.services.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_service_sidebar_description">' . e(__('Single Service Price Sidebar')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<label>' . e(__('Add some description for prices on single service page sidebar')) . '</label>';
                    $settingsTabBodyHtml .= '<textarea class="form-control" name="wc_service_sidebar_description" id="wc_service_sidebar_description">' . e(old('wc_service_sidebar_description', $sidebarDescription)) . '</textarea>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $checked = (old('wc_booking_on_service_page_status') !== null)
                        ? (old('wc_booking_on_service_page_status') === 'on')
                        : $disableBookingOnServicePage;
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_booking_on_service_page_status">' . e(__('Disable Booking on Service Page?')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($checked ? 'checked="checked"' : '') . ' name="wc_booking_on_service_page_status" id="wc_booking_on_service_page_status" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_service_booking_heading">' . e(__('Single Service Price Sidebar')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="text" class="form-control" name="wc_service_booking_heading" id="wc_service_booking_heading" value="' . e(old('wc_service_booking_heading', $bookingHeading)) . '" />';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $selected = (string) old('wc_service_booking_form', $bookingForm);
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_service_booking_form">' . e(__('Booking Form')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select class="form-control" name="wc_service_booking_form" id="wc_service_booking_form">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select booking form')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="with_type"' . ($selected === 'with_type' ? ' selected' : '') . '>' . e(__('Booking with type, manufacture, device and grouped services')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="without_type"' . ($selected === 'without_type' ? ' selected' : '') . '>' . e(__('Booking with manufacture, device and services no types')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="warranty_booking"' . ($selected === 'warranty_booking' ? ' selected' : '') . '>' . e(__('Booking without service selection')) . '</option>';
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_payment_status') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_payment_status" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_payment_status-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<table class="wp-list-table widefat fixed striped posts">';
                    $settingsTabBodyHtml .= '<thead><tr>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Slug')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Default Label')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Display Label')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Color')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Sort Order')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Actions')) . '</th>';
                    $settingsTabBodyHtml .= '</tr></thead><tbody>';

                    if ($paymentStatuses->count() === 0) {
                        $settingsTabBodyHtml .= '<tr><td colspan="6">' . e(__('No payment statuses found.')) . '</td></tr>';
                    } else {
                        foreach ($paymentStatuses as $ps) {
                            $override = $paymentStatusOverrides[$ps->slug] ?? null;
                            $displayLabel = (is_string($override?->label) && $override->label !== '') ? $override->label : $ps->label;
                            $displayColor = is_string($override?->color) ? $override->color : '';
                            $displaySort = is_numeric($override?->sort_order) ? (string) (int) $override->sort_order : '';

                            $settingsTabBodyHtml .= '<tr>';
                            $settingsTabBodyHtml .= '<td><code>' . e((string) $ps->slug) . '</code></td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $ps->label) . '</td>';

                            $settingsTabBodyHtml .= '<td>';
                            $settingsTabBodyHtml .= '<form method="post" action="' . e(route('tenant.settings.payment_status.update', ['business' => $tenant->slug, 'slug' => $ps->slug])) . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<input type="text" class="regular-text" name="label" value="' . e(old('label', $displayLabel)) . '">';
                            $settingsTabBodyHtml .= '</td>';

                            $settingsTabBodyHtml .= '<td><input type="text" style="width:110px" name="color" value="' . e(old('color', $displayColor)) . '" placeholder="#000000"></td>';
                            $settingsTabBodyHtml .= '<td><input type="number" style="width:110px" name="sort_order" value="' . e(old('sort_order', $displaySort)) . '" min="0"></td>';
                            $settingsTabBodyHtml .= '<td><button class="button button-primary button-small" type="submit">' . e(__('Save')) . '</button></form></td>';
                            $settingsTabBodyHtml .= '</tr>';
                        }
                    }

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_maintenance_reminder') {
                    $deviceTypes = \App\Models\RepairBuddyDeviceType::query()->orderBy('name')->limit(200)->get();
                    $deviceBrands = \App\Models\RepairBuddyDeviceBrand::query()->orderBy('name')->limit(200)->get();

                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_maintenance_reminder" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_maintenance_reminder-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Jobs should have delivery date set for reminders to work')) . '</p>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Add New Maintenance Reminder')) . '</h3>';
                    $settingsTabBodyHtml .= '<form method="post" action="' . e(route('tenant.settings.maintenance_reminders.store', ['business' => $tenant->slug])) . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_name">' . e(__('Reminder Name')) . '</label></th><td><input id="mr_name" type="text" name="name" class="regular-text" value="' . e(old('name', '')) . '"></td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_interval_days">' . e(__('Run After (days)')) . '</label></th><td><input id="mr_interval_days" type="number" name="interval_days" min="1" max="3650" value="' . e(old('interval_days', '30')) . '"></td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_description">' . e(__('Description')) . '</label></th><td><input id="mr_description" type="text" name="description" class="regular-text" value="' . e(old('description', '')) . '"></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_device_type_id">' . e(__('Device Type')) . '</label></th><td><select id="mr_device_type_id" name="device_type_id" class="regular-text">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('All')) . '</option>';
                    foreach ($deviceTypes as $dt) {
                        $selected = (string) old('device_type_id', '') === (string) $dt->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $dt->id) . '"' . $selected . '>' . e((string) $dt->name) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_device_brand_id">' . e(__('Brand')) . '</label></th><td><select id="mr_device_brand_id" name="device_brand_id" class="regular-text">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('All')) . '</option>';
                    foreach ($deviceBrands as $db) {
                        $selected = (string) old('device_brand_id', '') === (string) $db->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $db->id) . '"' . $selected . '>' . e((string) $db->name) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Email')) . '</th><td><label><input type="checkbox" name="email_enabled" ' . (old('email_enabled') ? 'checked="checked"' : '') . '> ' . e(__('Enable')) . '</label></td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_email_body">' . e(__('Email Message')) . '</label></th><td><textarea id="mr_email_body" name="email_body" rows="5" class="large-text">' . e(old('email_body', '')) . '</textarea><p class="description">' . e(__('Keywords')) . ': {{device_name}} {{customer_name}} {{unsubscribe_device}}</p></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('SMS')) . '</th><td><label><input type="checkbox" name="sms_enabled" ' . (old('sms_enabled') ? 'checked="checked"' : '') . '> ' . e(__('Enable')) . '</label></td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="mr_sms_body">' . e(__('SMS Message')) . '</label></th><td><textarea id="mr_sms_body" name="sms_body" rows="3" class="large-text">' . e(old('sms_body', '')) . '</textarea><p class="description">' . e(__('Keywords')) . ': {{device_name}} {{customer_name}} {{unsubscribe_device}}</p></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Reminder')) . '</th><td><label><input type="checkbox" name="reminder_enabled" ' . (old('reminder_enabled', 'on') ? 'checked="checked"' : '') . '> ' . e(__('Active')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Add Reminder')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<h3>' . e(__('Existing Reminders')) . '</h3>';
                    $settingsTabBodyHtml .= '<table class="wp-list-table widefat fixed striped posts">';
                    $settingsTabBodyHtml .= '<thead><tr>';
                    $settingsTabBodyHtml .= '<th>' . e(__('ID')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Name')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Interval')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Device Type')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Brand')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Email')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('SMS')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Reminder')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Last Run')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Actions')) . '</th>';
                    $settingsTabBodyHtml .= '</tr></thead><tbody>';

                    if ($maintenanceReminders->count() === 0) {
                        $settingsTabBodyHtml .= '<tr><td colspan="10">' . e(__('No reminders yet.')) . '</td></tr>';
                    } else {
                        foreach ($maintenanceReminders as $r) {
                            $settingsTabBodyHtml .= '<tr>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $r->id) . '</td>';
                            $settingsTabBodyHtml .= '<td><strong>' . e((string) $r->name) . '</strong></td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $r->interval_days) . ' ' . e(__('days')) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) ($r->deviceType?->name ?? __('All'))) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) ($r->deviceBrand?->name ?? __('All'))) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e($r->email_enabled ? __('Active') : __('Inactive')) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e($r->sms_enabled ? __('Active') : __('Inactive')) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e($r->reminder_enabled ? __('Active') : __('Inactive')) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e($r->last_executed_at ? (string) $r->last_executed_at : '-') . '</td>';
                            $settingsTabBodyHtml .= '<td>';

                            $settingsTabBodyHtml .= '<details><summary>' . e(__('Edit')) . '</summary>';
                            $settingsTabBodyHtml .= '<form method="post" action="' . e(route('tenant.settings.maintenance_reminders.update', ['business' => $tenant->slug, 'reminder' => $r->id])) . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<p><label>' . e(__('Name')) . '<br><input type="text" name="name" value="' . e((string) $r->name) . '" class="regular-text"></label></p>';
                            $settingsTabBodyHtml .= '<p><label>' . e(__('Interval days')) . '<br><input type="number" name="interval_days" min="1" max="3650" value="' . e((string) $r->interval_days) . '"></label></p>';
                            $settingsTabBodyHtml .= '<p><label>' . e(__('Description')) . '<br><input type="text" name="description" value="' . e((string) ($r->description ?? '')) . '" class="regular-text"></label></p>';

                            $settingsTabBodyHtml .= '<p><label>' . e(__('Device Type')) . '<br><select name="device_type_id" class="regular-text">';
                            $settingsTabBodyHtml .= '<option value="">' . e(__('All')) . '</option>';
                            foreach ($deviceTypes as $dt) {
                                $selected = (string) ($r->device_type_id ?? '') === (string) $dt->id ? ' selected' : '';
                                $settingsTabBodyHtml .= '<option value="' . e((string) $dt->id) . '"' . $selected . '>' . e((string) $dt->name) . '</option>';
                            }
                            $settingsTabBodyHtml .= '</select></label></p>';

                            $settingsTabBodyHtml .= '<p><label>' . e(__('Brand')) . '<br><select name="device_brand_id" class="regular-text">';
                            $settingsTabBodyHtml .= '<option value="">' . e(__('All')) . '</option>';
                            foreach ($deviceBrands as $db) {
                                $selected = (string) ($r->device_brand_id ?? '') === (string) $db->id ? ' selected' : '';
                                $settingsTabBodyHtml .= '<option value="' . e((string) $db->id) . '"' . $selected . '>' . e((string) $db->name) . '</option>';
                            }
                            $settingsTabBodyHtml .= '</select></label></p>';

                            $settingsTabBodyHtml .= '<p><label><input type="checkbox" name="email_enabled" ' . ($r->email_enabled ? 'checked="checked"' : '') . '> ' . e(__('Email enabled')) . '</label></p>';
                            $settingsTabBodyHtml .= '<p><label>' . e(__('Email body')) . '<br><textarea name="email_body" rows="4" class="large-text">' . e((string) ($r->email_body ?? '')) . '</textarea></label></p>';
                            $settingsTabBodyHtml .= '<p><label><input type="checkbox" name="sms_enabled" ' . ($r->sms_enabled ? 'checked="checked"' : '') . '> ' . e(__('SMS enabled')) . '</label></p>';
                            $settingsTabBodyHtml .= '<p><label>' . e(__('SMS body')) . '<br><textarea name="sms_body" rows="3" class="large-text">' . e((string) ($r->sms_body ?? '')) . '</textarea></label></p>';
                            $settingsTabBodyHtml .= '<p><label><input type="checkbox" name="reminder_enabled" ' . ($r->reminder_enabled ? 'checked="checked"' : '') . '> ' . e(__('Reminder enabled')) . '</label></p>';

                            $settingsTabBodyHtml .= '<p><button type="submit" class="button button-primary button-small">' . e(__('Save')) . '</button></p>';
                            $settingsTabBodyHtml .= '</form>';

                            $settingsTabBodyHtml .= '<form method="post" action="' . e(route('tenant.settings.maintenance_reminders.delete', ['business' => $tenant->slug, 'reminder' => $r->id])) . '" onsubmit="return confirm(\'' . e(__('Delete this reminder?')) . '\');">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<button type="submit" class="button button-secondary button-small">' . e(__('Delete')) . '</button>';
                            $settingsTabBodyHtml .= '</form>';
                            $settingsTabBodyHtml .= '</details>';

                            $settingsTabBodyHtml .= '</td>';
                            $settingsTabBodyHtml .= '</tr>';
                        }
                    }

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_manage_taxes') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="' . e($tabId) . '" role="tabpanel" aria-hidden="true" aria-labelledby="' . e($tabId) . '-label">';
                    $settingsTabBodyHtml .= '<p class="help-text"><a class="button button-primary button-small" data-open="taxFormReveal">' . e(__('Add New Tax')) . '</a></p>';

                    $settingsTabBodyHtml .= '<div class="small reveal" id="taxFormReveal" data-reveal>';
                    $settingsTabBodyHtml .= '<h2>' . e(__('Add new tax')) . '</h2>';
                    $settingsTabBodyHtml .= '<div class="form-message"></div>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.taxes.store', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Tax Name')) . '*';
                    $settingsTabBodyHtml .= '<input name="tax_name" type="text" class="form-control login-field" value="" required id="tax_name" />';
                    $settingsTabBodyHtml .= '</label></div>';

                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Tax Rate')) . '*';
                    $settingsTabBodyHtml .= '<input name="tax_rate" type="number" step="any" class="form-control login-field" value="" id="tax_rate" required />';
                    $settingsTabBodyHtml .= '</label></div>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Tax Status'));
                    $settingsTabBodyHtml .= '<select class="form-control" name="tax_status">';
                    $settingsTabBodyHtml .= '<option value="active">' . e(__('Active')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="inactive">' . e(__('Inactive')) . '</option>';
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '</label></div>';

                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Default Tax'));
                    $settingsTabBodyHtml .= '<input type="checkbox" name="tax_is_default" id="tax_is_default" />';
                    $settingsTabBodyHtml .= '</label></div>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x"><fieldset class="cell medium-6">';
                    $settingsTabBodyHtml .= '<button class="button" type="submit">' . e(__('Add Tax')) . '</button>';
                    $settingsTabBodyHtml .= '</fieldset></div>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '<button class="close-button" data-close aria-label="Close modal" type="button"><span aria-hidden="true">&times;</span></button>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div id="poststuff_wrapper">';
                    $settingsTabBodyHtml .= '<table id="poststuff" class="wp-list-table widefat fixed striped posts">';
                    $settingsTabBodyHtml .= '<thead><tr>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('ID')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Name')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Rate (%)')) . '</th>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('Status')) . '</th>';
                    $settingsTabBodyHtml .= '<th class="column-action">' . e(__('Actions')) . '</th>';
                    $settingsTabBodyHtml .= '</tr></thead><tbody>';

                    if ($taxes->count() > 0) {
                        foreach ($taxes as $t) {
                            $settingsTabBodyHtml .= '<tr>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $t->id) . '</td>';
                            $settingsTabBodyHtml .= '<td><strong>' . e((string) $t->name) . '</strong>' . ($t->is_default ? ' <span class="dashicons dashicons-yes"></span>' : '') . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $t->rate) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e($t->is_active ? 'active' : 'inactive') . '</td>';
                            $settingsTabBodyHtml .= '<td>';

                            $settingsTabBodyHtml .= '<form method="post" style="display:inline;" action="' . e($tenant?->slug ? route('tenant.settings.taxes.active', ['business' => $tenant->slug, 'tax' => $t->id]) : '#') . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                            $settingsTabBodyHtml .= '<input type="hidden" name="is_active" value="' . e($t->is_active ? '0' : '1') . '">';
                            $settingsTabBodyHtml .= '<button type="submit" class="button button-small">' . e(__('Change Status')) . '</button>';
                            $settingsTabBodyHtml .= '</form> ';

                            if (! $t->is_default) {
                                $settingsTabBodyHtml .= '<form method="post" style="display:inline;" action="' . e($tenant?->slug ? route('tenant.settings.taxes.default', ['business' => $tenant->slug, 'tax' => $t->id]) : '#') . '">';
                                $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                                $settingsTabBodyHtml .= '<button type="submit" class="button button-small">' . e(__('Set Default')) . '</button>';
                                $settingsTabBodyHtml .= '</form>';
                            }

                            $settingsTabBodyHtml .= '</td>';
                            $settingsTabBodyHtml .= '</tr>';
                        }
                    } else {
                        $settingsTabBodyHtml .= '<tr><td colspan="5">' . e(__('Please add a tax rate by clicking add new tax button above')) . '</td></tr>';
                    }

                    $settingsTabBodyHtml .= '</tbody></table></div>';

                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h2>' . e(__('Tax Settings')) . '</h2>';
                    $settingsTabBodyHtml .= '<div class="wc_rb_manage_taxes"></div>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.taxes.settings', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_add_taxes">' . e(__('Enable Taxes')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($taxEnable ? 'checked="checked"' : '') . ' name="wc_use_taxes" id="wc_add_taxes" /></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_primary_tax">' . e(__('Default Tax')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><select name="wc_primary_tax" id="wc_primary_tax" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select tax')) . '</option>';
                    foreach ($taxes as $t) {
                        $selected = ($taxDefaultId !== null && (int) $taxDefaultId === (int) $t->id) ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $t->id) . '"' . $selected . '>' . e((string) $t->name) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $inclusive = $taxInvoiceAmounts === 'inclusive' ? ' selected' : '';
                    $exclusive = $taxInvoiceAmounts === 'exclusive' ? ' selected' : '';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="wc_prices_inclu_exclu">' . e(__('Invoice Amounts Are')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><select name="wc_prices_inclu_exclu" id="wc_prices_inclu_exclu" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="exclusive"' . $exclusive . '>' . e(__('Exclusive of Tax')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="inclusive"' . $inclusive . '>' . e(__('Inclusive of Tax')) . '</option>';
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } else {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="' . e($tabId) . '" role="tabpanel" aria-hidden="true" aria-labelledby="' . e($tabId) . '-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                }
            }

            return view('tenant.settings', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'settings',
                'pageTitle' => 'Settings',
                'class_settings' => $class_settings,
                'class_general_settings' => $class_general_settings,
                'class_currency_settings' => $class_currency_settings,
                'class_invoices_settings' => $class_invoices_settings,
                'class_status' => $class_status,
                'class_activation' => $class_activation,
                'logoURL' => 'https://www.webfulcreations.com/products/crm-wordpress-plugin-repairbuddy/',
                'logolink' => '/brand/repair-buddy-logo.png',
                'contactURL' => 'https://www.webfulcreations.com/contact-us/',
                'repairbuddy_whitelabel' => false,
                'menu_name_p' => '',
                'wc_rb_business_name' => $tenant?->name ?? '',
                'wc_rb_business_phone' => $tenant?->contact_phone ?? '',
                'wc_rb_business_address' => is_string($tenant?->billing_address_json) ? $tenant?->billing_address_json : '',
                'computer_repair_logo' => is_string($tenant?->logo_url) ? $tenant?->logo_url : '',
                'computer_repair_email' => $tenant?->contact_email ?? '',
                'wc_rb_gdpr_acceptance_link' => '',
                'wc_rb_gdpr_acceptance_link_label' => 'Privacy policy',
                'wc_rb_gdpr_acceptance' => 'I understand that I will be contacted by a representative regarding this request and I agree to the privacy policy.',
                'case_number_length' => 6,
                'case_number_prefix' => 'WC_',
                'wc_primary_country' => '',
                'useWooProducts' => '',
                'disableStatusCheckSerial' => '',
                'disableNextServiceDate' => '',
                'send_notice' => '',
                'attach_pdf' => '',
                'wc_cr_selected_currency' => '',
                'wc_cr_currency_position' => '',
                'wc_cr_thousand_separator' => ',',
                'wc_cr_decimal_separator' => '.',
                'wc_cr_number_of_decimals' => '0',
                'wc_cr_currency_options_html' => '',
                'wc_cr_currency_position_options_html' => '',
                'wc_repair_order_print_size' => '',
                'repair_order_type' => '',
                'wb_rb_invoice_type' => '',
                'business_terms' => '',
                'wc_rb_ro_thanks_msg' => '',
                'wc_rb_io_thanks_msg' => '',
                'wc_rb_cr_display_add_on_ro' => '',
                'wc_rb_cr_display_add_on_ro_cu' => '',
                'wcrb_add_invoice_qr_code' => '',
                'pickupdate_checked' => '',
                'deliverydate_checked' => '',
                'nextservicedate_checked' => '',
                'wcrb_invoice_disclaimer_html' => '',
                'job_status_rows_html' => '',
                'wc_inventory_management_status' => false,
                'job_status_delivered' => 'delivered',
                'job_status_cancelled' => 'cancelled',
                'status_options_delivered_html' => '',
                'status_options_cancelled_html' => '',
                'settings_tab_menu_items_html' => $settingsTabMenuItemsHtml,
                'settings_tab_body_html' => $settingsTabBodyHtml,
                'add_status_form_footer_html' => '',
                'activation_form_html' => '',
                'nonce_main_setting_html' => '',
                'nonce_currency_setting_html' => '',
                'nonce_report_setting_html' => '',
                'nonce_delivered_status_html' => '',
                'dashoutput_html' => '',
                'countries_options_html' => '',
                'rb_ms_version_defined' => false,
                'rb_qb_version_defined' => false,
                'casenumber_label_first' => 'Case',
                'casenumber_label_none' => 'Case',
                'woocommerce_activated' => true,
                'pickup_date_label_none' => 'pickup_date',
                'delivery_date_label_none' => 'delivery_date',
                'nextservice_date_label_none' => 'nextservice_date',
            ]);
        }

        if ($screen === 'jobs' || $screen === 'jobs_card') {
            $current_view = $screen === 'jobs_card' ? 'card' : 'table';
            $_page = $screen === 'jobs_card' ? 'jobs_card' : 'jobs';

            $view_label = $current_view === 'card' ? 'Table View' : 'Card View';

            $view_url = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=' . ($current_view === 'card' ? 'jobs' : 'jobs_card')
                : '#';

            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            $jobShowUrl101 = $tenant?->slug ? route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => 101]) : '#';
            $jobShowUrl102 = $tenant?->slug ? route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => 102]) : '#';

            $mockJobRowsTable = <<<'HTML'
<tr class="job_id_101 job_status_in_process">
    <td  class="ps-4" data-label="ID"><a href="__JOB_SHOW_URL_101__" target="_blank"><strong>00101</a></strong></th>
    <td data-label="Case Number/Tech"><a href="__JOB_SHOW_URL_101__" target="_blank">WC_ABC123</a><br><strong class="text-primary">Tech: Alex</strong></td>
    <td data-label="Customer">John Smith<br><strong>P</strong>: (555) 111-2222<br><strong>E</strong>: john@example.com</td>
    <td data-label="Devices">Dell Inspiron 15 (D-1001)</td>
    <td data-bs-toggle="tooltip" data-bs-title="P: = Pickup date D: = Delivery date N: = Next service date " data-label="Dates"><strong>P</strong>:02/10/2026<br><strong>D</strong>:02/12/2026<br><strong>N</strong>:02/15/2026</td>
    <td data-label="Total">
        <strong>$120.00</strong>
    </td>
    <td class="gap-3 p-3" data-label="Balance">
        <span class="p-2 text-success-emphasis bg-success-subtle border border-success-subtle rounded-3">$40.00</span>
    </td>
    <td data-label="Payment">
        Partial
    </td>
    <td data-label="Status">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-info" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-gear me-2"></i>In Process</button><ul class="dropdown-menu"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="101"
                            data-type="job_status_update"
                            data-value="new"
                            data-security=""
                            href="#" data-status="new"><div class="d-flex align-items-center"><i class="bi-plus-circle text-primary me-2"></i><span>New</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                            recordid="101"
                            data-type="job_status_update"
                            data-value="inprocess"
                            data-security=""
                            href="#" data-status="inprocess"><div class="d-flex align-items-center"><i class="bi-gear text-info me-2"></i><span>In Process</span></div><i class="bi bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="101"
                            data-type="job_status_update"
                            data-value="delivered"
                            data-security=""
                            href="#" data-status="delivered"><div class="d-flex align-items-center"><i class="bi-check-square text-success me-2"></i><span>Delivered</span></div></a></li></ul></div>
    </td>
    <td data-label="Priority">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-warning" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-arrow-up-circle me-2"></i><span>High</span></button><ul class="dropdown-menu shadow-sm"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="normal"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-circle text-secondary me-2"></i><span>Normal</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="high"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-arrow-up-circle text-warning me-2"></i><span>High</span></div><i class="bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="urgent"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-exclamation-triangle text-danger me-2"></i><span>Urgent</span></div></a></li></ul></div>
    </td>
    
    <td data-label="Actions" class="text-end pe-4">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Actions
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#openTakePaymentModal" recordid="101" data-security="">
                        <i class="bi bi-credit-card text-success me-2"></i>Take Payment
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank">
                    <i class="bi bi-printer text-secondary me-2"></i>Print Job Invoice</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Download PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-envelope text-info me-2"></i>Email Customer
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="101" data-security="">
                        <i class="bi bi-files text-warning me-2"></i>Duplicate job
                    </a>
                </li>
                <li><a class="dropdown-item" href="__JOB_SHOW_URL_101__" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
<tr class="job_id_102 job_status_completed">
    <td  class="ps-4" data-label="ID"><a href="__JOB_SHOW_URL_102__" target="_blank"><strong>00102</a></strong></th>
    <td data-label="Case Number/Tech"><a href="__JOB_SHOW_URL_102__" target="_blank">WC_DEF456</a><br><strong class="text-primary">Tech: Sam</strong></td>
    <td data-label="Customer">Sarah Johnson<br><strong>P</strong>: (555) 333-4444<br><strong>E</strong>: sarah@example.com</td>
    <td data-label="Devices">iPhone 13 (IP-2233)</td>
    <td data-bs-toggle="tooltip" data-bs-title="P: = Pickup date D: = Delivery date N: = Next service date " data-label="Dates"><strong>P</strong>:02/09/2026<br><strong>D</strong>:02/11/2026</td>
    <td data-label="Total">
        <strong>$80.00</strong>
    </td>
    <td class="gap-3 p-3" data-label="Balance">
        <span class="p-2 text-primary-emphasis bg-primary-subtle border border-primary-subtle rounded-3">$0.00</span>
    </td>
    <td data-label="Payment">
        Paid
    </td>
    <td data-label="Status">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-success" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-check-square me-2"></i>Delivered</button><ul class="dropdown-menu"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="102"
                            data-type="job_status_update"
                            data-value="new"
                            data-security=""
                            href="#" data-status="new"><div class="d-flex align-items-center"><i class="bi-plus-circle text-primary me-2"></i><span>New</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="102"
                            data-type="job_status_update"
                            data-value="inprocess"
                            data-security=""
                            href="#" data-status="inprocess"><div class="d-flex align-items-center"><i class="bi-gear text-info me-2"></i><span>In Process</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                            recordid="102"
                            data-type="job_status_update"
                            data-value="delivered"
                            data-security=""
                            href="#" data-status="delivered"><div class="d-flex align-items-center"><i class="bi-check-square text-success me-2"></i><span>Delivered</span></div><i class="bi bi-check2 text-primary ms-2"></i></a></li></ul></div>
    </td>
    <td data-label="Priority">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-circle me-2"></i><span>Normal</span></button><ul class="dropdown-menu shadow-sm"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="normal"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-circle text-secondary me-2"></i><span>Normal</span></div><i class="bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="high"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-arrow-up-circle text-warning me-2"></i><span>High</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="urgent"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-exclamation-triangle text-danger me-2"></i><span>Urgent</span></div></a></li></ul></div>
    </td>
    
    <td data-label="Actions" class="text-end pe-4">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Actions
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#openTakePaymentModal" recordid="102" data-security="">
                        <i class="bi bi-credit-card text-success me-2"></i>Take Payment
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank">
                    <i class="bi bi-printer text-secondary me-2"></i>Print Job Invoice</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Download PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-envelope text-info me-2"></i>Email Customer
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="102" data-security="">
                        <i class="bi bi-files text-warning me-2"></i>Duplicate job
                    </a>
                </li>
                <li><a class="dropdown-item" href="__JOB_SHOW_URL_102__" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
HTML;

            $mockJobRowsTable = str_replace(
                ['__JOB_SHOW_URL_101__', '__JOB_SHOW_URL_102__'],
                [$jobShowUrl101, $jobShowUrl102],
                $mockJobRowsTable
            );

            $mockJobRowsCard = <<<'HTML'
<div class="row g-3 p-3">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100 job-card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong class="text-primary">00101</strong>
                <span class="badge bg-warning">In Process</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <span class="device-icon me-3">
                        <i class="bi bi-laptop display-6 text-primary"></i>
                    </span>
                    <div>
                        <h6 class="card-title mb-1">Dell Inspiron 15</h6>
                        <p class="text-muted small mb-0">WC_ABC123</p>
                    </div>
                </div>
                <div class="job-meta">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer:</span>
                        <span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">John Smith</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Priority:</span>
                        <span class="badge bg-danger">High</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <span class="fw-semibold">$120.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Due:</span>
                        <span class="fw-semibold">02/12/2026</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <div class="btn-group w-100">
                    <a href="__JOB_SHOW_URL_101__" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="__JOB_SHOW_URL_101__" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="#" target="_blank" class="btn btn-outline-info btn-sm"><i class="bi bi-printer me-1"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100 job-card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong class="text-primary">00102</strong>
                <span class="badge bg-success">Completed</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <span class="device-icon me-3">
                        <i class="bi bi-phone display-6 text-primary"></i>
                    </span>
                    <div>
                        <h6 class="card-title mb-1">iPhone 13</h6>
                        <p class="text-muted small mb-0">WC_DEF456</p>
                    </div>
                </div>
                <div class="job-meta">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer:</span>
                        <span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">Sarah Johnson</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Priority:</span>
                        <span class="badge bg-secondary">Normal</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <span class="fw-semibold">$80.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Due:</span>
                        <span class="fw-semibold">02/11/2026</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <div class="btn-group w-100">
                    <a href="__JOB_SHOW_URL_102__" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="__JOB_SHOW_URL_102__" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="#" target="_blank" class="btn btn-outline-info btn-sm"><i class="bi bi-printer me-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

            $mockJobRowsCard = str_replace(
                ['__JOB_SHOW_URL_101__', '__JOB_SHOW_URL_102__'],
                [$jobShowUrl101, $jobShowUrl102],
                $mockJobRowsCard
            );

            $mockPagination = <<<'HTML'
<div class="card-footer">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted">Showing 1 to 2 of 2 jobs</div>
        <nav><ul class="pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
        </ul></nav>
    </div>
</div>
HTML;

            $mockExportButtons = <<<'HTML'
<ul class="dropdown-menu">
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-csv me-2"></i>CSV
    </a></li>
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-pdf me-2"></i>PDF
    </a></li>
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-xlsx me-2"></i>Excel
    </a></li>
</ul>
HTML;

            $mockJobStatusOptions = <<<'HTML'
<option value="new">New</option>
<option value="in_process">In Process</option>
<option value="completed">Completed</option>
HTML;

            $mockDeviceOptions = <<<'HTML'
<option value="">Devices ...</option>
<option value="1">Dell Inspiron 15</option>
<option value="2">iPhone 13</option>
HTML;

            $mockPaymentStatusOptions = <<<'HTML'
<option value="unpaid">Unpaid</option>
<option value="partial">Partial</option>
<option value="paid">Paid</option>
HTML;

            $mockPriorityOptions = <<<'HTML'
<select class="form-select" name="wc_job_priority" id="wc_job_priority">
    <option value="all">Priority (All)</option>
    <option value="normal">Normal</option>
    <option value="urgent">Urgent</option>
</select>
HTML;

            $mockDuplicateFrontBox = <<<'HTML'
<div id="wcrb-duplicate-job-front-box" class="d-none"></div>
HTML;

            $jobStatusTiles = [
                [
                    'status_slug' => 'in_process',
                    'status_name' => 'In Process',
                    'jobs_count' => 3,
                    'color' => 'bg-primary',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=in_process')
                        : '#',
                ],
                [
                    'status_slug' => 'completed',
                    'status_name' => 'Completed',
                    'jobs_count' => 5,
                    'color' => 'bg-success',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=completed')
                        : '#',
                ],
                [
                    'status_slug' => 'new',
                    'status_name' => 'New',
                    'jobs_count' => 2,
                    'color' => 'bg-warning',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=new')
                        : '#',
                ],
            ];

            return view('tenant.jobs', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'jobs',
                'pageTitle' => 'Jobs',
                'role' => is_string($user?->role) ? (string) $user->role : null,
                'current_view' => $current_view,
                '_page' => $_page,
                'view_label' => $view_label,
                'view_url' => $view_url,
                '_job_status' => $jobStatusTiles,
                'job_status_options_html' => $mockJobStatusOptions,
                'job_priority_options_html' => $mockPriorityOptions,
                'device_options_html' => $mockDeviceOptions,
                'payment_status_options_html' => $mockPaymentStatusOptions,
                'export_buttons_html' => $mockExportButtons,
                'license_state' => true,
                'use_store_select' => false,
                'clear_filters_url' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=' . $_page)
                    : '#',
                'duplicate_job_front_box_html' => $mockDuplicateFrontBox,
                'jobs_list' => [
                    'rows' => $current_view === 'card' ? $mockJobRowsCard : $mockJobRowsTable,
                    'pagination' => $mockPagination,
                ],
            ]);
        }

        if ($screen === 'estimates' || $screen === 'estimates_card') {
            $current_view = $screen === 'estimates_card' ? 'card' : 'table';
            $_page = $screen === 'estimates_card' ? 'estimates_card' : 'estimates';

            $view_label = $current_view === 'card' ? 'Table View' : 'Card View';

            $view_url = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=' . ($current_view === 'card' ? 'estimates' : 'estimates_card')
                : '#';

            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            return view('tenant.estimates', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'estimates',
                'pageTitle' => 'Estimates',
                'role' => is_string($user?->role) ? (string) $user->role : null,
                'current_view' => $current_view,
                '_page' => $_page,
                'view_label' => $view_label,
                'view_url' => $view_url,
                'license_state' => true,
                'use_store_select' => false,
                'clear_filters_url' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=' . $_page)
                    : '#',
                'export_buttons_html' => '',
                'duplicate_job_front_box_html' => '',
                'jobs_list' => [
                    'rows' => '',
                    'pagination' => '',
                ],
            ]);
        }

        if ($screen === 'calendar') {
            return view('tenant.calendar', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'dashboard',
                'pickup_date_label' => 'Pickup date',
                'delivery_date_label' => 'Delivery date',
                'nextservice_date_label' => 'Next service date',
                'enable_next_service' => true,
                'calendar_events_url' => $tenant?->slug
                    ? route('tenant.calendar.events', ['business' => $tenant->slug])
                    : '#',
            ]);
        }

        if ($screen === 'timelog') {
            $mockEligibleJobsWithDevicesDropdown = <<<'HTML'
<select class="form-select" id="wcrb_timelog_jobs_devices">
    <option value="">Select a job/device</option>
</select>
HTML;

            $mockNonceFieldHtml = <<<'HTML'
<input type="hidden" id="wcrb_timelog_nonce_field" name="wcrb_timelog_nonce_field" value="">
<input type="hidden" name="_wp_http_referer" value="">
HTML;

            $mockActivityTypesDropdownHtml = <<<'HTML'
<select class="form-select" id="activityType">
    <option value="">Select activity</option>
</select>
HTML;

            $mockActivityTypesDropdownManualHtml = <<<'HTML'
<select class="form-select" id="activityType_manual" name="activityType_manual">
    <option value="">Select activity</option>
</select>
HTML;

            return view('tenant.timelog', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'timelog',
                'pageTitle' => 'Time Log',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'technician_id' => $user?->id,
                'device_label' => 'device',
                'stats' => [
                    'today_hours' => 0,
                    'week_hours' => 0,
                    'billable_rate' => 0,
                    'month_earnings' => 0,
                    'month_earnings_formatted' => '$0.00',
                    'completed_jobs' => 0,
                    'avg_time_per_job' => 0,
                ],
                'eligible_jobs_with_devices_dropdown_html' => $mockEligibleJobsWithDevicesDropdown,
                'timelog_nonce_field_html' => $mockNonceFieldHtml,
                'timelog_activity_types_dropdown_html' => $mockActivityTypesDropdownHtml,
                'timelog_activity_types_dropdown_manual_html' => $mockActivityTypesDropdownManualHtml,
                'productivity_stats' => [
                    'avg_daily_hours' => 0,
                    'total_jobs_completed' => 0,
                    'efficiency_score' => 0,
                ],
                'activity_distribution' => [],
                'recent_time_logs_html' => '',
            ]);
        }

        if ($screen === 'customer-devices') {
            $mockStatsHtml = <<<'HTML'
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card stats-card bg-primary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Devices</h6>
                <h4 class="mb-0">12</h4>
                <small class="text-white-50">Total</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Active</h6>
                <h4 class="mb-0">9</h4>
                <small class="text-white-50">In use</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-warning text-dark">
            <div class="card-body text-center p-3">
                <h6 class="card-title mb-1">Needs Attention</h6>
                <h4 class="mb-0">2</h4>
                <small class="text-muted">Flagged</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-secondary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Archived</h6>
                <h4 class="mb-0">1</h4>
                <small class="text-white-50">Old</small>
            </div>
        </div>
    </div>
</div>
HTML;

            $mockFiltersHtml = <<<'HTML'
<div class="card mb-4"><div class="card-body"></div></div>
HTML;

            $mockRowsHtml = <<<'HTML'
HTML;

            $mockPaginationHtml = <<<'HTML'
HTML;

            $mockAddDeviceFormHtml = <<<'HTML'
HTML;

            $isAdminUser = (bool) ($user?->is_admin ?? false);
            $role = is_string($user?->role) ? (string) $user->role : '';
            if ($role !== '' && $role !== 'customer' && $role !== 'guest') {
                $isAdminUser = true;
            }

            return view('tenant.customer_devices', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'customer-devices',
                'pageTitle' => 'Devices',
                'is_admin_user' => $isAdminUser,
                'wc_device_label' => 'Devices',
                'sing_device_label' => 'Device',
                'wc_device_id_imei_label' => 'ID/IMEI',
                'wc_pin_code_label' => 'Pin Code/Password',
                'devices_data' => [
                    'stats' => $mockStatsHtml,
                    'filters' => $mockFiltersHtml,
                    'rows' => $mockRowsHtml,
                    'pagination' => $mockPaginationHtml,
                ],
                'add_device_form_html' => $mockAddDeviceFormHtml,
            ]);
        }

        if ($screen === 'expenses') {
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            return view('tenant.expenses', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'expenses',
                'pageTitle' => 'Expenses',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'search' => is_string($request->query('search')) ? (string) $request->query('search') : '',
                'category_id' => $request->query('category_id') ?? '',
                'payment_status' => is_string($request->query('payment_status')) ? (string) $request->query('payment_status') : '',
                'start_date' => is_string($request->query('start_date')) ? (string) $request->query('start_date') : '',
                'end_date' => is_string($request->query('end_date')) ? (string) $request->query('end_date') : '',
                'page' => (int) ($request->query('expenses_page') ?? 1),
                'limit' => 20,
                'offset' => 0,
                'expenses' => [],
                'total_expenses' => 0,
                'total_pages' => 0,
                'stats' => [
                    'totals' => (object) [
                        'grand_total' => 0,
                        'total_count' => 0,
                        'total_amount' => 0,
                        'total_tax' => 0,
                    ],
                ],
                'categories' => [],
                'payment_methods' => [],
                'payment_statuses' => [],
                'expense_types' => [],
                'reset_url' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_url_prev' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_url_next' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_urls' => [],
            ]);
        }

        if ($screen === 'expense_categories') {
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            return view('tenant.expense_categories', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'expense_categories',
                'pageTitle' => 'Expense Categories',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'categories' => [
                    (object) [
                        'category_id' => 1,
                        'category_name' => 'Parts & Supplies',
                        'category_description' => 'Consumables, screws, adhesives, cables, and small parts.',
                        'color_code' => '#3498db',
                        'is_active' => 1,
                        'taxable' => 1,
                        'tax_rate' => 15,
                    ],
                    (object) [
                        'category_id' => 2,
                        'category_name' => 'Tools',
                        'category_description' => 'Equipment and tools purchased for repairs.',
                        'color_code' => '#2ecc71',
                        'is_active' => 1,
                        'taxable' => 0,
                        'tax_rate' => 0,
                    ],
                    (object) [
                        'category_id' => 3,
                        'category_name' => 'Utilities',
                        'category_description' => 'Electricity, water, internet, and phone.',
                        'color_code' => '#f39c12',
                        'is_active' => 1,
                        'taxable' => 1,
                        'tax_rate' => 5,
                    ],
                    (object) [
                        'category_id' => 4,
                        'category_name' => 'Marketing',
                        'category_description' => '',
                        'color_code' => '#9b59b6',
                        'is_active' => 0,
                        'taxable' => 0,
                        'tax_rate' => 0,
                    ],
                ],
                'nonce' => csrf_token(),
            ]);
        }

        if ($screen === 'reviews') {
            $role = is_string($user?->role) ? (string) $user->role : 'guest';
            $userRoles = [$role];
            if (($user?->is_admin ?? false) === true) {
                $userRoles[] = 'administrator';
            }
            if ($role !== 'customer' && $role !== 'guest') {
                $userRoles[] = 'store_manager';
                $userRoles[] = 'technician';
            }
            $userRoles = array_values(array_unique(array_filter($userRoles)));

            $isAdminUser = ! empty(array_intersect(['administrator', 'store_manager', 'technician'], $userRoles));
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            $mockStatsHtml = <<<'HTML'
<div class="row g-3 mb-4"><div class="col"><div class="card stats-card bg-primary text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">Total Reviews</h6><h3 class="mb-0 text-white">0</h3></div></div></div><div class="col"><div class="card stats-card bg-success text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-graph-up fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">Avg. Rating</h6><h3 class="mb-0 text-white">0/5</h3></div></div></div><div class="col"><div class="card stats-card bg-warning text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">5 Stars</h6><h3 class="mb-0 text-white">0</h3></div></div></div><div class="col"><div class="card stats-card bg-danger text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">1 Star</h6><h3 class="mb-0 text-white">0</h3></div></div></div></div>
HTML;

            $mockFiltersHtml = '';
            if ($isAdminUser) {
                $resetUrl = $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=reviews')
                    : '#';

                $mockFiltersHtml = '<div class="card mb-4"><div class="card-body"><form method="get" action="" class="row g-3">'
                    . '<input type="hidden" name="screen" value="reviews" />'
                    . '<div class="col-md-4"><div class="input-group">'
                    . '<span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>'
                    . '<input type="text" class="form-control border-start-0" name="review_search" id="reviewSearch" value="" placeholder="Search...">'
                    . '</div></div>'
                    . '<div class="col-md-3"><select name="rating_filter" class="form-select">'
                    . '<option value="all">All Ratings</option>'
                    . '<option value="5" >★★★★★ (5 stars)</option>'
                    . '<option value="4" >★★★★ (4 stars)</option>'
                    . '<option value="3" >★★★ (3 stars)</option>'
                    . '<option value="2" >★★ (2 stars)</option>'
                    . '<option value="1" >★ (1 stars)</option>'
                    . '</select></div>'
                    . '<div class="col-md-2"><div class="d-flex gap-2">'
                    . '<a href="' . $resetUrl . '" class="btn btn-outline-secondary" id="clearReviewFilters"><i class="bi bi-arrow-clockwise"></i></a>'
                    . '<button type="submit" class="btn btn-primary" id="applyReviewFilters"><i class="bi bi-funnel"></i> Filter</button>'
                    . '</div></div>'
                    . '</form></div></div>';
            }

            $mockRowsHtml = '';
            if ($isAdminUser) {
                $mockRowsHtml .= '<tr><td colspan="9" class="text-center py-5">'
                    . '<i class="bi bi-star display-1 text-muted"></i>'
                    . '<h4 class="text-muted mt-3">No reviews found!</h4>'
                    . '</td></tr>';
            } else {
                $mockRowsHtml .= '<tr><td colspan="8" class="text-center py-5">'
                    . '<i class="bi bi-star display-1 text-muted"></i>'
                    . '<h4 class="text-muted mt-3">No reviews found!</h4>'
                    . '</td></tr>';
            }

            return view('tenant.reviews', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'reviews',
                'pageTitle' => 'Reviews',
                'userRole' => $role,
                'is_admin_user' => $isAdminUser,
                'sing_device_label' => 'Device',
                'reviews_data' => [
                    'stats' => $mockStatsHtml,
                    'filters' => $mockFiltersHtml,
                    'rows' => $mockRowsHtml,
                    'pagination' => '',
                ],
            ]);
        }

        if ($screen === 'profile') {
            $dateTime = '';
            if ($user?->created_at) {
                $dateTime = $user->created_at->format('F j, Y');
            }

            $userRoleLabel = 'Customer';
            if (is_string($user?->role) && $user->role !== '') {
                $userRoleLabel = ucfirst($user->role);
            }

            $countryCode = is_string($user?->country) ? (string) $user->country : '';
            $countries = [];
            if (class_exists(\Symfony\Component\Intl\Countries::class)) {
                $countries = \Symfony\Component\Intl\Countries::getNames('en');
            }

            if (empty($countries)) {
                $countries = [
                    'AF' => 'Afghanistan',
                    'AX' => 'Aland Islands',
                    'AL' => 'Albania',
                    'DZ' => 'Algeria',
                    'AS' => 'American Samoa',
                    'AD' => 'Andorra',
                    'AO' => 'Angola',
                    'AI' => 'Anguilla',
                    'AQ' => 'Antarctica',
                    'AG' => 'Antigua and Barbuda',
                    'AR' => 'Argentina',
                    'AM' => 'Armenia',
                    'AW' => 'Aruba',
                    'AU' => 'Australia',
                    'AT' => 'Austria',
                    'AZ' => 'Azerbaijan',
                    'BS' => 'Bahamas',
                    'BH' => 'Bahrain',
                    'BD' => 'Bangladesh',
                    'BB' => 'Barbados',
                    'BY' => 'Belarus',
                    'BE' => 'Belgium',
                    'BZ' => 'Belize',
                    'BJ' => 'Benin',
                    'BM' => 'Bermuda',
                    'BT' => 'Bhutan',
                    'BO' => 'Bolivia',
                    'BQ' => 'Bonaire, Sint Eustatius and Saba',
                    'BA' => 'Bosnia and Herzegovina',
                    'BW' => 'Botswana',
                    'BV' => 'Bouvet Island',
                    'BR' => 'Brazil',
                    'IO' => 'British Indian Ocean Territory',
                    'BN' => 'Brunei Darussalam',
                    'BG' => 'Bulgaria',
                    'BF' => 'Burkina Faso',
                    'BI' => 'Burundi',
                    'KH' => 'Cambodia',
                    'CM' => 'Cameroon',
                    'CA' => 'Canada',
                    'CV' => 'Cape Verde',
                    'KY' => 'Cayman Islands',
                    'CF' => 'Central African Republic',
                    'TD' => 'Chad',
                    'CL' => 'Chile',
                    'CN' => 'China',
                    'CX' => 'Christmas Island',
                    'CC' => 'Cocos (Keeling) Islands',
                    'CO' => 'Colombia',
                    'KM' => 'Comoros',
                    'CG' => 'Congo',
                    'CD' => 'Congo, Democratic Republic of the Congo',
                    'CK' => 'Cook Islands',
                    'CR' => 'Costa Rica',
                    'CI' => "Cote D'Ivoire",
                    'HR' => 'Croatia',
                    'CU' => 'Cuba',
                    'CW' => 'Curacao',
                    'CY' => 'Cyprus',
                    'CZ' => 'Czech Republic',
                    'DK' => 'Denmark',
                    'DJ' => 'Djibouti',
                    'DM' => 'Dominica',
                    'DO' => 'Dominican Republic',
                    'EC' => 'Ecuador',
                    'EG' => 'Egypt',
                    'SV' => 'El Salvador',
                    'GQ' => 'Equatorial Guinea',
                    'ER' => 'Eritrea',
                    'EE' => 'Estonia',
                    'ET' => 'Ethiopia',
                    'FK' => 'Falkland Islands (Malvinas)',
                    'FO' => 'Faroe Islands',
                    'FJ' => 'Fiji',
                    'FI' => 'Finland',
                    'FR' => 'France',
                    'GF' => 'French Guiana',
                    'PF' => 'French Polynesia',
                    'TF' => 'French Southern Territories',
                    'GA' => 'Gabon',
                    'GM' => 'Gambia',
                    'GE' => 'Georgia',
                    'DE' => 'Germany',
                    'GH' => 'Ghana',
                    'GI' => 'Gibraltar',
                    'GR' => 'Greece',
                    'GL' => 'Greenland',
                    'GD' => 'Grenada',
                    'GP' => 'Guadeloupe',
                    'GU' => 'Guam',
                    'GT' => 'Guatemala',
                    'GG' => 'Guernsey',
                    'GN' => 'Guinea',
                    'GW' => 'Guinea-Bissau',
                    'GY' => 'Guyana',
                    'HT' => 'Haiti',
                    'HM' => 'Heard Island and Mcdonald Islands',
                    'VA' => 'Holy See (Vatican City State)',
                    'HN' => 'Honduras',
                    'HK' => 'Hong Kong',
                    'HU' => 'Hungary',
                    'IS' => 'Iceland',
                    'IN' => 'India',
                    'ID' => 'Indonesia',
                    'IR' => 'Iran, Islamic Republic of',
                    'IQ' => 'Iraq',
                    'IE' => 'Ireland',
                    'IM' => 'Isle of Man',
                    'IL' => 'Israel',
                    'IT' => 'Italy',
                    'JM' => 'Jamaica',
                    'JP' => 'Japan',
                    'JE' => 'Jersey',
                    'JO' => 'Jordan',
                    'KZ' => 'Kazakhstan',
                    'KE' => 'Kenya',
                    'KI' => 'Kiribati',
                    'KP' => "Korea, Democratic People's Republic of",
                    'KR' => 'Korea, Republic of',
                    'XK' => 'Kosovo',
                    'KW' => 'Kuwait',
                    'KG' => 'Kyrgyzstan',
                    'LA' => "Lao People's Democratic Republic",
                    'LV' => 'Latvia',
                    'LB' => 'Lebanon',
                    'LS' => 'Lesotho',
                    'LR' => 'Liberia',
                    'LY' => 'Libyan Arab Jamahiriya',
                    'LI' => 'Liechtenstein',
                    'LT' => 'Lithuania',
                    'LU' => 'Luxembourg',
                    'MO' => 'Macao',
                    'MK' => 'Macedonia, the Former Yugoslav Republic of',
                    'MG' => 'Madagascar',
                    'MW' => 'Malawi',
                    'MY' => 'Malaysia',
                    'MV' => 'Maldives',
                    'ML' => 'Mali',
                    'MT' => 'Malta',
                    'MH' => 'Marshall Islands',
                    'MQ' => 'Martinique',
                    'MR' => 'Mauritania',
                    'MU' => 'Mauritius',
                    'YT' => 'Mayotte',
                    'MX' => 'Mexico',
                    'FM' => 'Micronesia, Federated States of',
                    'MD' => 'Moldova, Republic of',
                    'MC' => 'Monaco',
                    'MN' => 'Mongolia',
                    'ME' => 'Montenegro',
                    'MS' => 'Montserrat',
                    'MA' => 'Morocco',
                    'MZ' => 'Mozambique',
                    'MM' => 'Myanmar',
                    'NA' => 'Namibia',
                    'NR' => 'Nauru',
                    'NP' => 'Nepal',
                    'NL' => 'Netherlands',
                    'AN' => 'Netherlands Antilles',
                    'NC' => 'New Caledonia',
                    'NZ' => 'New Zealand',
                    'NI' => 'Nicaragua',
                    'NE' => 'Niger',
                    'NG' => 'Nigeria',
                    'NU' => 'Niue',
                    'NF' => 'Norfolk Island',
                    'MP' => 'Northern Mariana Islands',
                    'NO' => 'Norway',
                    'OM' => 'Oman',
                    'PK' => 'Pakistan',
                    'PW' => 'Palau',
                    'PS' => 'Palestinian Territory, Occupied',
                    'PA' => 'Panama',
                    'PG' => 'Papua New Guinea',
                    'PY' => 'Paraguay',
                    'PE' => 'Peru',
                    'PH' => 'Philippines',
                    'PN' => 'Pitcairn',
                    'PL' => 'Poland',
                    'PT' => 'Portugal',
                    'PR' => 'Puerto Rico',
                    'QA' => 'Qatar',
                    'RE' => 'Reunion',
                    'RO' => 'Romania',
                    'RU' => 'Russian Federation',
                    'RW' => 'Rwanda',
                    'BL' => 'Saint Barthelemy',
                    'SH' => 'Saint Helena',
                    'KN' => 'Saint Kitts and Nevis',
                    'LC' => 'Saint Lucia',
                    'MF' => 'Saint Martin',
                    'PM' => 'Saint Pierre and Miquelon',
                    'VC' => 'Saint Vincent and the Grenadines',
                    'WS' => 'Samoa',
                    'SM' => 'San Marino',
                    'ST' => 'Sao Tome and Principe',
                    'SA' => 'Saudi Arabia',
                    'SN' => 'Senegal',
                    'RS' => 'Serbia',
                    'SC' => 'Seychelles',
                    'SL' => 'Sierra Leone',
                    'SG' => 'Singapore',
                    'SX' => 'Sint Maarten',
                    'SK' => 'Slovakia',
                    'SI' => 'Slovenia',
                    'SB' => 'Solomon Islands',
                    'SO' => 'Somalia',
                    'ZA' => 'South Africa',
                    'GS' => 'South Georgia and the South Sandwich Islands',
                    'SS' => 'South Sudan',
                    'ES' => 'Spain',
                    'LK' => 'Sri Lanka',
                    'SD' => 'Sudan',
                    'SR' => 'Suriname',
                    'SJ' => 'Svalbard and Jan Mayen',
                    'SZ' => 'Swaziland',
                    'SE' => 'Sweden',
                    'CH' => 'Switzerland',
                    'SY' => 'Syrian Arab Republic',
                    'TW' => 'Taiwan, Province of China',
                    'TJ' => 'Tajikistan',
                    'TZ' => 'Tanzania, United Republic of',
                    'TH' => 'Thailand',
                    'TL' => 'Timor-Leste',
                    'TG' => 'Togo',
                    'TK' => 'Tokelau',
                    'TO' => 'Tonga',
                    'TT' => 'Trinidad and Tobago',
                    'TN' => 'Tunisia',
                    'TR' => 'Turkey',
                    'TM' => 'Turkmenistan',
                    'TC' => 'Turks and Caicos Islands',
                    'TV' => 'Tuvalu',
                    'UG' => 'Uganda',
                    'UA' => 'Ukraine',
                    'AE' => 'United Arab Emirates',
                    'GB' => 'United Kingdom',
                    'US' => 'United States',
                    'UM' => 'United States Minor Outlying Islands',
                    'UY' => 'Uruguay',
                    'UZ' => 'Uzbekistan',
                    'VU' => 'Vanuatu',
                    'VE' => 'Venezuela',
                    'VN' => 'Viet Nam',
                    'VG' => 'Virgin Islands, British',
                    'VI' => 'Virgin Islands, U.S.',
                    'WF' => 'Wallis and Futuna',
                    'EH' => 'Western Sahara',
                    'YE' => 'Yemen',
                    'ZM' => 'Zambia',
                    'ZW' => 'Zimbabwe',
                ];
            }
            ksort($countries);
            $optionsGenerated = '';
            foreach ($countries as $code => $name) {
                $selected = ($countryCode !== '' && strtoupper($code) === strtoupper($countryCode)) ? ' selected' : '';
                $optionsGenerated .= '<option value="' . e($code) . '"' . $selected . '>' . e($name) . '</option>';
            }

            return view('tenant.profile', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'profile',
                'pageTitle' => 'Profile',
                'first_name' => is_string($user?->first_name) ? (string) $user->first_name : '',
                'last_name' => is_string($user?->last_name) ? (string) $user->last_name : '',
                'user_email' => is_string($user?->email) ? (string) $user->email : '',
                'phone_number' => is_string($user?->phone_number) ? (string) $user->phone_number : '',
                'company' => is_string($user?->company) ? (string) $user->company : '',
                'billing_tax' => is_string($user?->billing_tax) ? (string) $user->billing_tax : '',
                'address' => is_string($user?->address) ? (string) $user->address : '',
                'city' => is_string($user?->city) ? (string) $user->city : '',
                'zip_code' => is_string($user?->zip_code) ? (string) $user->zip_code : '',
                'state' => is_string($user?->state) ? (string) $user->state : '',
                'country' => $countryCode,
                'optionsGenerated' => $optionsGenerated,
                'current_avatar' => '',
                '_jobs_count' => 0,
                '_estimates_count' => 0,
                'lifetime_value_formatted' => '$0.00',
                'dateTime' => $dateTime,
                'userRole' => $userRoleLabel,
                'wcrb_updateuser_nonce_post' => '',
                'wcrb_updatepassword_nonce_post' => '',
                'wcrb_profile_photo_nonce' => '',
                'wp_http_referer' => '',
            ]);
        }

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'dashboard',
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $events = [
            [
                'title' => 'Job #72 - John Smith',
                'start' => now()->addDay()->setTime(10, 0)->toIso8601String(),
                'end' => now()->addDay()->setTime(11, 30)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-primary', 'job-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: John Smith | Status: New | Date Field: pickup date',
                    'status' => 'New',
                    'type' => 'job',
                ],
            ],
            [
                'title' => 'Estimate #73 - smakina',
                'start' => now()->setTime(14, 0)->toIso8601String(),
                'end' => now()->setTime(15, 30)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-warning', 'estimate-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: smakina | Status: Quote | Date Field: pickup date',
                    'status' => 'Quote',
                    'type' => 'estimate',
                ],
            ],
            [
                'title' => 'Job #74 - Michael Chen',
                'start' => now()->addDays(2)->setTime(11, 0)->toIso8601String(),
                'end' => now()->addDays(2)->setTime(12, 0)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-info', 'job-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: Michael Chen | Status: In Process | Date Field: pickup date',
                    'status' => 'In Process',
                    'type' => 'job',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}

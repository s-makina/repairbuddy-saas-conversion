<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\RepairBuddyAppointment;
use App\Models\RepairBuddyAppointmentSetting;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateAttachment;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyService;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AppointmentConfirmationNotification;
use App\Support\Audit\PlatformAudit;
use App\Support\RepairBuddyBookingTemplateService;
use App\Support\RepairBuddyCaseNumberService;
use App\Models\RepairBuddyServiceAvailabilityOverride;
use App\Models\RepairBuddyServicePriceOverride;
use App\Notifications\BookingSubmissionAdminNotification;
use App\Notifications\BookingSubmissionCustomerNotification;
use App\Notifications\OneTimePasswordNotification;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepairBuddyPublicBookingService
{
    public function __construct(
        private readonly RepairBuddyCaseNumberService $caseNumbers,
        private readonly RepairBuddyBookingTemplateService $templates,
    ) {
    }

    public function submit(Request $request, string $business, array $validated): array
    {
        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();

        if (! $tenant instanceof Tenant) {
            throw new \RuntimeException('Tenant context is missing.');
        }

        if (! $branch) {
            throw new \RuntimeException('Branch context is missing.');
        }

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $settings = is_array($settings) ? $settings : [];

        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];
        $myAccount = is_array($settings['myAccount'] ?? null) ? $settings['myAccount'] : [];
        $devicesBrands = is_array($settings['devicesBrands'] ?? null) ? $settings['devicesBrands'] : [];
        $estimates = is_array($settings['estimates'] ?? null) ? $settings['estimates'] : [];

        if ((bool) ($myAccount['disableBooking'] ?? false)) {
            throw ValidationException::withMessages([
                'mode' => ['Booking is disabled.'],
            ]);
        }

        $mode = is_string($validated['mode'] ?? null) && (string) $validated['mode'] !== ''
            ? (string) $validated['mode']
            : (is_string($booking['publicBookingMode'] ?? null) ? (string) $booking['publicBookingMode'] : 'ungrouped');

        if ($mode === 'warranty') {
            $dop = data_get($validated, 'warranty.dateOfPurchase');
            if (! $dop) {
                throw ValidationException::withMessages([
                    'warranty.dateOfPurchase' => ['Date of purchase is required for warranty bookings.'],
                ]);
            }
        }

        $gdprText = is_string($general['wc_rb_gdpr_acceptance'] ?? null) ? trim((string) $general['wc_rb_gdpr_acceptance']) : '';
        if ($gdprText !== '') {
            $accepted = array_key_exists('gdprAccepted', $validated) ? (bool) $validated['gdprAccepted'] : false;
            if (! $accepted) {
                throw ValidationException::withMessages([
                    'gdprAccepted' => ['GDPR acceptance is required.'],
                ]);
            }
        }

        $sendToJobs = (bool) ($booking['sendBookingQuoteToJobs'] ?? ($estimates['bookingQuoteSendToJobs'] ?? false));
        $disableEstimates = (bool) ($estimates['disableEstimates'] ?? false);

        if ($disableEstimates) {
            $sendToJobs = true;
        }

        $turnOffOtherDeviceBrand = (bool) ($booking['turnOffOtherDeviceBrand'] ?? false);
        $turnOffOtherService = (bool) ($booking['turnOffOtherService'] ?? false);
        $turnOffIdImeiInBooking = (bool) ($booking['turnOffIdImeiInBooking'] ?? false);
        $enablePinCodeField = (bool) ($devicesBrands['enablePinCodeField'] ?? false);

        $customer = is_array($validated['customer'] ?? null) ? $validated['customer'] : [];

        $email = strtolower(trim((string) ($customer['userEmail'] ?? '')));
        $fullName = trim((string) ($customer['firstName'] ?? '')).' '.trim((string) ($customer['lastName'] ?? ''));
        $fullName = trim($fullName);

        $existingAny = User::query()->where('email', $email)->first();
        if ($existingAny instanceof User && (int) ($existingAny->tenant_id ?? 0) !== (int) $tenant->id) {
            throw ValidationException::withMessages([
                'customer.userEmail' => ['Email is already in use.'],
            ]);
        }

        $existing = User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('email', $email)
            ->first();

        $creationBehavior = is_string($booking['customerCreationEmailBehavior'] ?? null)
            ? trim((string) $booking['customerCreationEmailBehavior'])
            : '';
        if (! in_array($creationBehavior, ['send_login_credentials', 'send_invite_link', 'do_not_email'], true)) {
            $creationBehavior = 'send_login_credentials';
        }

        $createdCustomer = null;
        $createdCustomerOneTimePassword = null;

        $result = DB::transaction(function () use (
            $branch,
            $customer,
            $disableEstimates,
            $email,
            $existing,
            $fullName,
            $general,
            $mode,
            $request,
            $sendToJobs,
            $tenant,
            $turnOffIdImeiInBooking,
            $enablePinCodeField,
            $turnOffOtherDeviceBrand,
            $turnOffOtherService,
            $validated,
            &$createdCustomer,
            &$createdCustomerOneTimePassword,
        ) {
            $tenantId = (int) $tenant->id;

            $customerId = null;

            if ($existing instanceof User) {
                $customerId = (int) $existing->id;
            } else {
                $oneTimePassword = Str::password(16);
                $oneTimePasswordExpiresAt = now()->addMinutes(60 * 24);

                try {
                    $newUser = User::query()->create([
                        'tenant_id' => $tenantId,
                        'is_admin' => false,
                        'role' => 'customer',
                        'role_id' => null,
                        'status' => 'active',

                        'name' => $fullName !== '' ? $fullName : $email,
                        'email' => $email,
                        'phone' => array_key_exists('phone', $customer) && is_string($customer['phone']) ? trim((string) $customer['phone']) : null,
                        'company' => array_key_exists('company', $customer) && is_string($customer['company']) ? trim((string) $customer['company']) : null,
                        'tax_id' => array_key_exists('taxId', $customer) && is_string($customer['taxId']) ? trim((string) $customer['taxId']) : null,
                        'address_line1' => array_key_exists('addressLine1', $customer) && is_string($customer['addressLine1']) ? trim((string) $customer['addressLine1']) : null,
                        'address_line2' => array_key_exists('addressLine2', $customer) && is_string($customer['addressLine2']) ? trim((string) $customer['addressLine2']) : null,
                        'address_city' => array_key_exists('city', $customer) && is_string($customer['city']) ? trim((string) $customer['city']) : null,
                        'address_state' => array_key_exists('state', $customer) && is_string($customer['state']) ? trim((string) $customer['state']) : null,
                        'address_postal_code' => array_key_exists('postalCode', $customer) && is_string($customer['postalCode']) ? trim((string) $customer['postalCode']) : null,
                        'address_country' => array_key_exists('country', $customer) && is_string($customer['country']) ? strtoupper(trim((string) $customer['country'])) : null,

                        'password' => Hash::make(Str::random(72)),
                        'must_change_password' => true,
                        'one_time_password_hash' => Hash::make($oneTimePassword),
                        'one_time_password_expires_at' => $oneTimePasswordExpiresAt,
                        'one_time_password_used_at' => null,
                        'email_verified_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    throw ValidationException::withMessages([
                        'customer.userEmail' => ['Email is already in use.'],
                    ]);
                }

                $customerId = (int) $newUser->id;
                $createdCustomer = $newUser;
                $createdCustomerOneTimePassword = $oneTimePassword;
            }

            $caseNumber = $this->caseNumbers->nextCaseNumber($tenant, $branch, $general);

            $jobDetails = trim((string) $validated['jobDetails']);
            if ($mode === 'warranty') {
                $dop = data_get($validated, 'warranty.dateOfPurchase');
                if ($dop) {
                    $jobDetails = trim($jobDetails."\n\nDate of purchase: ".(string) $dop);
                }
            }

            if ($sendToJobs) {
                $statusSlug = $this->resolveInitialJobStatusSlug();

                $job = RepairBuddyJob::query()->create([
                    'case_number' => $caseNumber,
                    'title' => $caseNumber,
                    'status_slug' => $statusSlug,
                    'payment_status_slug' => null,
                    'priority' => null,
                    'customer_id' => $customerId,
                    'created_by' => null,
                    'opened_at' => now(),
                    'pickup_date' => now()->toDateString(),
                    'delivery_date' => null,
                    'next_service_date' => null,
                    'case_detail' => $jobDetails,
                    'assigned_technician_id' => null,
                    'plugin_device_post_id' => null,
                    'plugin_device_id_text' => null,
                ]);

                $this->attachDevicesAndItemsToJob(
                    job: $job,
                    devicesPayload: is_array($validated['devices'] ?? null) ? $validated['devices'] : [],
                    turnOffOtherDeviceBrand: $turnOffOtherDeviceBrand,
                    turnOffOtherService: $turnOffOtherService,
                    turnOffIdImeiInBooking: $turnOffIdImeiInBooking,
                    enablePinCodeField: $enablePinCodeField,
                );

                $uploaded = $request->file('attachments');
                $this->attachUploadsToJob($job, $uploaded);

                $appointment = $this->createAppointmentIfProvided(
                    appointmentData: is_array($validated['appointment'] ?? null) ? $validated['appointment'] : [],
                    customerId: $customerId,
                    jobId: $job->id,
                    estimateId: null,
                    tenant: $tenant,
                );

                return [
                    'entity' => 'job',
                    'id' => $job->id,
                    'case_number' => $job->case_number,
                    'customer_id' => $customerId,
                    'appointment_id' => $appointment?->id,
                ];
            }

            if ($disableEstimates) {
                throw ValidationException::withMessages([
                    'mode' => ['Estimates are disabled.'],
                ]);
            }

            $estimate = RepairBuddyEstimate::query()->create([
                'case_number' => $caseNumber,
                'title' => $caseNumber,
                'status' => 'pending',
                'customer_id' => $customerId,
                'created_by' => null,
                'pickup_date' => now()->toDateString(),
                'delivery_date' => null,
                'case_detail' => $jobDetails,
                'assigned_technician_id' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'converted_job_id' => null,
            ]);

            $this->attachDevicesAndItemsToEstimate(
                estimate: $estimate,
                devicesPayload: is_array($validated['devices'] ?? null) ? $validated['devices'] : [],
                turnOffOtherDeviceBrand: $turnOffOtherDeviceBrand,
                turnOffOtherService: $turnOffOtherService,
                turnOffIdImeiInBooking: $turnOffIdImeiInBooking,
                enablePinCodeField: $enablePinCodeField,
            );

            $uploaded = $request->file('attachments');
            $this->attachUploadsToEstimate($estimate, $uploaded);

            $appointment = $this->createAppointmentIfProvided(
                appointmentData: is_array($validated['appointment'] ?? null) ? $validated['appointment'] : [],
                customerId: $customerId,
                jobId: null,
                estimateId: $estimate->id,
                tenant: $tenant,
            );

            return [
                'entity' => 'estimate',
                'id' => $estimate->id,
                'case_number' => $estimate->case_number,
                'customer_id' => $customerId,
                'appointment_id' => $appointment?->id,
            ];
        });

        if ($createdCustomer instanceof User && is_string($createdCustomerOneTimePassword) && $createdCustomerOneTimePassword !== '') {
            if ($creationBehavior === 'send_login_credentials') {
                try {
                    $createdCustomer->notify(new OneTimePasswordNotification($createdCustomerOneTimePassword, 60 * 24));
                } catch (\Throwable $e) {
                    Log::error('customer.onetime_password_notification_failed', [
                        'user_id' => $createdCustomer->id,
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $frontendBase = rtrim((string) env('FRONTEND_URL', (string) env('APP_URL', '')), '/');
        $statusCheckUrl = $frontendBase.'/t/'.$business.'/status?caseNumber='.urlencode((string) $result['case_number']);

        $customerDeviceLabel = $this->renderCustomerDeviceLabel(is_array($validated['devices'] ?? null) ? $validated['devices'] : []);
        $invoiceDetails = $this->renderInvoiceDetails((string) $result['entity'], (int) $result['id']);

        $pairs = [
            'customer_full_name' => $fullName,
            'customer_device_label' => $customerDeviceLabel,
            'status_check_link' => $statusCheckUrl,
            'status_check_url' => $statusCheckUrl,
            'job_id' => (string) $result['id'],
            'case_number' => (string) $result['case_number'],
            'business_name' => is_string($tenant->name) ? (string) $tenant->name : '',
            'order_invoice_details' => $invoiceDetails,
            'entity' => (string) $result['entity'],
            'entity_id' => (string) $result['id'],
            'customer_email' => $email,
        ];

        $customerSubject = is_string($booking['customerEmailSubject'] ?? null) ? (string) $booking['customerEmailSubject'] : '';
        $customerBody = is_string($booking['customerEmailBody'] ?? null) ? (string) $booking['customerEmailBody'] : '';
        $adminSubject = is_string($booking['adminEmailSubject'] ?? null) ? (string) $booking['adminEmailSubject'] : '';
        $adminBody = is_string($booking['adminEmailBody'] ?? null) ? (string) $booking['adminEmailBody'] : '';

        if (trim($customerSubject) === '') {
            $customerSubject = 'We received your booking';
        }
        if (trim($customerBody) === '') {
            $customerBody = "Hello,\n\nWe have received your booking request.\n\nCase: {{case_number}}\nStatus check: {{status_check_link}}\n\n{{order_invoice_details}}\n";
        }
        if (trim($adminSubject) === '') {
            $adminSubject = 'New booking received';
        }
        if (trim($adminBody) === '') {
            $adminBody = "A new booking was submitted.\n\nCase: {{case_number}}\nCustomer: {{customer_full_name}} ({{customer_email}})\nStatus check: {{status_check_link}}\n\n{{order_invoice_details}}\n";
        }

        $customerSubject = $this->templates->render($customerSubject, $pairs);
        $customerBody = $this->templates->render($customerBody, $pairs);
        $adminSubject = $this->templates->render($adminSubject, $pairs);
        $adminBody = $this->templates->render($adminBody, $pairs);

        $customerUser = $createdCustomer instanceof User ? $createdCustomer : $existing;
        if ($customerUser instanceof User && is_string($customerUser->email) && trim((string) $customerUser->email) !== '') {
            try {
                $customerUser->notify(new BookingSubmissionCustomerNotification(
                    subject: $customerSubject,
                    body: $customerBody,
                ));
            } catch (\Throwable $e) {
                Log::error('booking.customer_email_failed', [
                    'tenant_id' => $tenant->id,
                    'case_number' => $result['case_number'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $adminTo = is_string($branch->email) && trim((string) $branch->email) !== ''
            ? trim((string) $branch->email)
            : (is_string($tenant->contact_email) && trim((string) $tenant->contact_email) !== '' ? trim((string) $tenant->contact_email) : null);

        if ($adminTo) {
            try {
                Notification::route('mail', $adminTo)->notify(new BookingSubmissionAdminNotification(
                    subject: $adminSubject,
                    body: $adminBody,
                ));
            } catch (\Throwable $e) {
                Log::error('booking.admin_email_failed', [
                    'tenant_id' => $tenant->id,
                    'case_number' => $result['case_number'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            PlatformAudit::logAs($request, null, 'public.booking.submitted', $tenant, null, [
                'entity' => (string) $result['entity'],
                'entity_id' => (int) $result['id'],
                'case_number' => (string) $result['case_number'],
                'customer_email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::error('audit.public_booking_failed', [
                'tenant_id' => $tenant->id,
                'case_number' => $result['case_number'],
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'message' => 'Received.',
            'case_number' => $result['case_number'],
            'entity' => $result['entity'],
            'entity_id' => $result['id'],
            'status_check_url' => $statusCheckUrl,
            'created_user' => $createdCustomer instanceof User,
        ];
    }

    protected function resolveInitialJobStatusSlug(): string
    {
        $preferred = ['neworder', 'new'];

        foreach ($preferred as $slug) {
            $exists = Status::query()
                ->where('status_type', 'Job')
                ->where('code', $slug)
                ->exists();
            if ($exists) {
                return $slug;
            }
        }

        $first = Status::query()
            ->where('status_type', 'Job')
            ->orderBy('id')
            ->value('code');
        if (is_string($first) && trim((string) $first) !== '') {
            return trim((string) $first);
        }

        return 'neworder';
    }

    protected function attachDevicesAndItemsToJob(
        RepairBuddyJob $job,
        array $devicesPayload,
        bool $turnOffOtherDeviceBrand,
        bool $turnOffOtherService,
        bool $turnOffIdImeiInBooking,
        bool $enablePinCodeField,
    ): void {
        foreach ($devicesPayload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $deviceId = array_key_exists('device_id', $entry) && is_numeric($entry['device_id']) ? (int) $entry['device_id'] : null;
            $label = is_string($entry['device_label'] ?? null) ? trim((string) $entry['device_label']) : '';

            $device = null;
            if ($deviceId) {
                $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
                if (! $device) {
                    throw ValidationException::withMessages([
                        'devices' => ['Device is invalid.'],
                    ]);
                }
                $label = (string) $device->model;
            } else {
                if ($turnOffOtherDeviceBrand) {
                    throw ValidationException::withMessages([
                        'devices' => ['Other devices are disabled.'],
                    ]);
                }
            }

            if ($label === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Device label is required.'],
                ]);
            }

            $serial = $turnOffIdImeiInBooking ? null : (is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : null);
            $pin = $enablePinCodeField ? (is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : null) : null;
            $notes = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : null;

            $extraFieldsPayload = is_array($entry['extra_fields'] ?? null) ? $entry['extra_fields'] : [];
            $extraFieldsSnapshot = $this->normalizeExtraFieldsSnapshot($extraFieldsPayload);

            $cd = RepairBuddyCustomerDevice::query()->create([
                'customer_id' => (int) $job->customer_id,
                'device_id' => $device?->id,
                'label' => $label,
                'serial' => $serial !== '' ? $serial : null,
                'pin' => $pin !== '' ? $pin : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            RepairBuddyJobDevice::query()->create([
                'job_id' => $job->id,
                'customer_device_id' => $cd->id,
                'label_snapshot' => $cd->label,
                'serial_snapshot' => $cd->serial,
                'pin_snapshot' => $cd->pin,
                'notes_snapshot' => $cd->notes,
                'extra_fields_snapshot_json' => $extraFieldsSnapshot,
            ]);

            $serviceEntries = is_array($entry['services'] ?? null) ? $entry['services'] : [];
            $otherService = is_string($entry['other_service'] ?? null) ? trim((string) $entry['other_service']) : '';

            if ($turnOffOtherService && $otherService !== '') {
                throw ValidationException::withMessages([
                    'devices' => ['Other service is disabled.'],
                ]);
            }

            $serviceEntries = array_values(array_filter($serviceEntries, fn ($x) => is_array($x)));

            if ($otherService !== '' && count($serviceEntries) > 0) {
                throw ValidationException::withMessages([
                    'devices' => ['Choose either a service or an other service description, not both.'],
                ]);
            }

            if ($otherService === '' && count($serviceEntries) < 1) {
                throw ValidationException::withMessages([
                    'devices' => ['Select at least one service for each device.'],
                ]);
            }

            // Process all selected services (multi-select allowed)
            if ($otherService === '' && count($serviceEntries) >= 1) {
                foreach ($serviceEntries as $svcEntry) {
                    if (! array_key_exists('service_id', $svcEntry) || ! is_numeric($svcEntry['service_id'])) {
                        throw ValidationException::withMessages([
                            'devices' => ['Service is invalid.'],
                        ]);
                    }

                    $serviceId = (int) $svcEntry['service_id'];
                    $qty = array_key_exists('qty', $svcEntry) && is_numeric($svcEntry['qty']) ? (int) $svcEntry['qty'] : 1;
                $qty = max(1, min(9999, $qty));

                $service = RepairBuddyService::query()->whereKey($serviceId)->first();
                if (! $service || ! $service->is_active) {
                    throw ValidationException::withMessages([
                        'devices' => ['Service is invalid.'],
                    ]);
                }

                $contextBrandId = $device && is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                $contextTypeId = $device && is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;

                if (! $this->isServiceAvailableForContext($service->id, $device?->id, $contextBrandId, $contextTypeId)) {
                    throw ValidationException::withMessages([
                        'devices' => ['A selected service is not available for the selected device.'],
                    ]);
                }

                [$unitCents, $currency, $taxId] = $this->resolveServiceUnitPriceAndTax($service, $device?->id, $contextBrandId, $contextTypeId);

                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => 'service',
                    'ref_id' => $service->id,
                    'name_snapshot' => (string) $service->name,
                    'qty' => $qty,
                    'unit_price_amount_cents' => $unitCents,
                    'unit_price_currency' => $currency,
                    'tax_id' => $taxId,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'customer_device_id' => $cd->id,
                        'device_label' => $cd->label,
                        'device_serial' => $cd->serial,
                    ],
                ]);
                }
            }

            if ($otherService !== '') {
                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => 'fee',
                    'ref_id' => null,
                    'name_snapshot' => $otherService,
                    'qty' => 1,
                    'unit_price_amount_cents' => 0,
                    'unit_price_currency' => (string) (TenantContext::tenant()?->currency ?? 'USD'),
                    'tax_id' => null,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'customer_device_id' => $cd->id,
                        'device_label' => $cd->label,
                        'device_serial' => $cd->serial,
                        'other_service' => true,
                    ],
                ]);
            }
        }
    }

    protected function attachDevicesAndItemsToEstimate(
        RepairBuddyEstimate $estimate,
        array $devicesPayload,
        bool $turnOffOtherDeviceBrand,
        bool $turnOffOtherService,
        bool $turnOffIdImeiInBooking,
        bool $enablePinCodeField,
    ): void {
        foreach ($devicesPayload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $deviceId = array_key_exists('device_id', $entry) && is_numeric($entry['device_id']) ? (int) $entry['device_id'] : null;
            $label = is_string($entry['device_label'] ?? null) ? trim((string) $entry['device_label']) : '';

            $device = null;
            if ($deviceId) {
                $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
                if (! $device) {
                    throw ValidationException::withMessages([
                        'devices' => ['Device is invalid.'],
                    ]);
                }
                $label = (string) $device->model;
            } else {
                if ($turnOffOtherDeviceBrand) {
                    throw ValidationException::withMessages([
                        'devices' => ['Other devices are disabled.'],
                    ]);
                }
            }

            if ($label === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Device label is required.'],
                ]);
            }

            $serial = $turnOffIdImeiInBooking ? null : (is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : null);
            $pin = $enablePinCodeField ? (is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : null) : null;
            $notes = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : null;

            $extraFieldsPayload = is_array($entry['extra_fields'] ?? null) ? $entry['extra_fields'] : [];
            $extraFieldsSnapshot = $this->normalizeExtraFieldsSnapshot($extraFieldsPayload);

            $cd = RepairBuddyCustomerDevice::query()->create([
                'customer_id' => (int) $estimate->customer_id,
                'device_id' => $device?->id,
                'label' => $label,
                'serial' => $serial !== '' ? $serial : null,
                'pin' => $pin !== '' ? $pin : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            RepairBuddyEstimateDevice::query()->create([
                'estimate_id' => $estimate->id,
                'customer_device_id' => $cd->id,
                'label_snapshot' => $cd->label,
                'serial_snapshot' => $cd->serial,
                'pin_snapshot' => $cd->pin,
                'notes_snapshot' => $cd->notes,
                'extra_fields_snapshot_json' => $extraFieldsSnapshot,
            ]);

            $serviceEntries = is_array($entry['services'] ?? null) ? $entry['services'] : [];
            $otherService = is_string($entry['other_service'] ?? null) ? trim((string) $entry['other_service']) : '';

            if ($turnOffOtherService && $otherService !== '') {
                throw ValidationException::withMessages([
                    'devices' => ['Other service is disabled.'],
                ]);
            }

            $serviceEntries = array_values(array_filter($serviceEntries, fn ($x) => is_array($x)));

            if ($otherService !== '' && count($serviceEntries) > 0) {
                throw ValidationException::withMessages([
                    'devices' => ['Choose either a service or an other service description, not both.'],
                ]);
            }

            if ($otherService === '' && count($serviceEntries) < 1) {
                throw ValidationException::withMessages([
                    'devices' => ['Select at least one service for each device.'],
                ]);
            }

            // Process all selected services (multi-select allowed)
            if ($otherService === '' && count($serviceEntries) >= 1) {
                foreach ($serviceEntries as $svcEntry) {
                    if (! array_key_exists('service_id', $svcEntry) || ! is_numeric($svcEntry['service_id'])) {
                        throw ValidationException::withMessages([
                            'devices' => ['Service is invalid.'],
                        ]);
                    }

                    $serviceId = (int) $svcEntry['service_id'];
                    $qty = array_key_exists('qty', $svcEntry) && is_numeric($svcEntry['qty']) ? (int) $svcEntry['qty'] : 1;
                $qty = max(1, min(9999, $qty));

                $service = RepairBuddyService::query()->whereKey($serviceId)->first();
                if (! $service || ! $service->is_active) {
                    throw ValidationException::withMessages([
                        'devices' => ['Service is invalid.'],
                    ]);
                }

                $contextBrandId = $device && is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                $contextTypeId = $device && is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;

                if (! $this->isServiceAvailableForContext($service->id, $device?->id, $contextBrandId, $contextTypeId)) {
                    throw ValidationException::withMessages([
                        'devices' => ['A selected service is not available for the selected device.'],
                    ]);
                }

                [$unitCents, $currency, $taxId] = $this->resolveServiceUnitPriceAndTax($service, $device?->id, $contextBrandId, $contextTypeId);

                RepairBuddyEstimateItem::query()->create([
                    'estimate_id' => $estimate->id,
                    'item_type' => 'service',
                    'ref_id' => $service->id,
                    'name_snapshot' => (string) $service->name,
                    'qty' => $qty,
                    'unit_price_amount_cents' => $unitCents,
                    'unit_price_currency' => $currency,
                    'tax_id' => $taxId,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'customer_device_id' => $cd->id,
                        'device_label' => $cd->label,
                        'device_serial' => $cd->serial,
                    ],
                ]);
                }
            }

            if ($otherService !== '') {
                RepairBuddyEstimateItem::query()->create([
                    'estimate_id' => $estimate->id,
                    'item_type' => 'fee',
                    'ref_id' => null,
                    'name_snapshot' => $otherService,
                    'qty' => 1,
                    'unit_price_amount_cents' => 0,
                    'unit_price_currency' => (string) (TenantContext::tenant()?->currency ?? 'USD'),
                    'tax_id' => null,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'customer_device_id' => $cd->id,
                        'device_label' => $cd->label,
                        'device_serial' => $cd->serial,
                        'other_service' => true,
                    ],
                ]);
            }
        }
    }

    protected function normalizeExtraFieldsSnapshot(array $payload): array
    {
        $out = [];

        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = is_string($row['key'] ?? null) ? trim((string) $row['key']) : '';
            $label = is_string($row['label'] ?? null) ? trim((string) $row['label']) : '';
            $value = is_string($row['value_text'] ?? null) ? trim((string) $row['value_text']) : '';

            if ($key === '' || $label === '' || $value === '') {
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'type' => 'text',
                'show_in_booking' => true,
                'show_in_invoice' => true,
                'show_in_portal' => true,
                'value_text' => $value,
            ];
        }

        return array_slice($out, 0, 50);
    }

    protected function isServiceAvailableForContext(int $serviceId, ?int $deviceId, ?int $brandId, ?int $typeId): bool
    {
        if ($deviceId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'device')
                ->where('scope_ref_id', $deviceId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        if ($brandId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'brand')
                ->where('scope_ref_id', $brandId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        if ($typeId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        return true;
    }

    protected function resolveServiceUnitPriceAndTax(RepairBuddyService $service, ?int $deviceId, ?int $brandId, ?int $typeId): array
    {
        $override = null;

        if ($deviceId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'device')
                ->where('scope_ref_id', $deviceId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        if (! $override && $brandId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'brand')
                ->where('scope_ref_id', $brandId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        if (! $override && $typeId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        $unitCents = $override && is_numeric($override->price_amount_cents)
            ? (int) $override->price_amount_cents
            : (is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : 0);

        $currency = $override && is_string($override->price_currency) && $override->price_currency !== ''
            ? strtoupper((string) $override->price_currency)
            : (is_string($service->base_price_currency) && $service->base_price_currency !== '' ? strtoupper((string) $service->base_price_currency) : (string) (TenantContext::tenant()?->currency ?? 'USD'));

        $taxId = $override && is_numeric($override->tax_id)
            ? (int) $override->tax_id
            : (is_numeric($service->tax_id) ? (int) $service->tax_id : null);

        return [$unitCents, $currency, $taxId];
    }

    protected function attachUploadsToJob(RepairBuddyJob $job, mixed $uploaded): void
    {
        $files = [];

        if ($uploaded instanceof UploadedFile) {
            $files = [$uploaded];
        } elseif (is_array($uploaded)) {
            $files = array_values(array_filter($uploaded, fn ($f) => $f instanceof UploadedFile));
        }

        $files = array_slice($files, 0, 5);

        foreach ($files as $file) {
            $disk = 'public';
            $path = $file->store('rb/jobs/'.$job->id.'/attachments', $disk);
            $url = Storage::disk($disk)->url($path);

            RepairBuddyJobAttachment::query()->create([
                'job_id' => $job->id,
                'uploader_user_id' => null,
                'visibility' => 'public',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'storage_disk' => $disk,
                'storage_path' => $path,
                'url' => $url,
            ]);
        }
    }

    protected function attachUploadsToEstimate(RepairBuddyEstimate $estimate, mixed $uploaded): void
    {
        $files = [];

        if ($uploaded instanceof UploadedFile) {
            $files = [$uploaded];
        } elseif (is_array($uploaded)) {
            $files = array_values(array_filter($uploaded, fn ($f) => $f instanceof UploadedFile));
        }

        $files = array_slice($files, 0, 5);

        foreach ($files as $file) {
            $disk = 'public';
            $path = $file->store('rb/estimates/'.$estimate->id.'/attachments', $disk);
            $url = Storage::disk($disk)->url($path);

            RepairBuddyEstimateAttachment::query()->create([
                'estimate_id' => $estimate->id,
                'uploader_user_id' => null,
                'visibility' => 'public',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'storage_disk' => $disk,
                'storage_path' => $path,
                'url' => $url,
            ]);
        }
    }

    private function renderCustomerDeviceLabel(array $devicesPayload): string
    {
        $labels = [];

        foreach ($devicesPayload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $label = is_string($entry['device_label'] ?? null) ? trim((string) $entry['device_label']) : '';
            if ($label === '' && is_numeric($entry['device_id'] ?? null)) {
                $device = RepairBuddyDevice::query()->whereKey((int) $entry['device_id'])->first();
                $label = $device ? (string) $device->model : '';
            }

            if ($label !== '') {
                $labels[] = $label;
            }
        }

        $labels = array_values(array_unique(array_slice($labels, 0, 5)));

        return implode(', ', $labels);
    }

    private function renderInvoiceDetails(string $entity, int $entityId): string
    {
        if ($entity === 'job') {
            $job = RepairBuddyJob::query()->whereKey($entityId)->first();
            if (! $job) {
                return '';
            }

            $items = RepairBuddyJobItem::query()
                ->where('job_id', $job->id)
                ->orderBy('id', 'asc')
                ->limit(5000)
                ->get();

            if ($items->count() === 0) {
                return '';
            }

            $lines = [];
            foreach ($items as $item) {
                $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
                $name = is_string($item->name_snapshot) ? (string) $item->name_snapshot : '';
                $currency = is_string($item->unit_price_currency) && $item->unit_price_currency !== '' ? (string) $item->unit_price_currency : (string) (TenantContext::tenant()?->currency ?? 'USD');
                $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;

                $meta = is_array($item->meta_json) ? $item->meta_json : [];
                $deviceLabel = is_string($meta['device_label'] ?? null) ? trim((string) $meta['device_label']) : '';

                $label = $deviceLabel !== '' ? ($deviceLabel.' - '.$name) : $name;

                $amount = ($qty * $unit) / 100;
                $lines[] = $label.' x'.$qty.' = '.$currency.' '.number_format($amount, 2, '.', '');
            }

            return implode("\n", $lines);
        }

        if ($entity === 'estimate') {
            $estimate = RepairBuddyEstimate::query()->whereKey($entityId)->first();
            if (! $estimate) {
                return '';
            }

            $items = RepairBuddyEstimateItem::query()
                ->where('estimate_id', $estimate->id)
                ->orderBy('id', 'asc')
                ->limit(5000)
                ->get();

            if ($items->count() === 0) {
                return '';
            }

            $lines = [];
            foreach ($items as $item) {
                $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
                $name = is_string($item->name_snapshot) ? (string) $item->name_snapshot : '';
                $currency = is_string($item->unit_price_currency) && $item->unit_price_currency !== '' ? (string) $item->unit_price_currency : (string) (TenantContext::tenant()?->currency ?? 'USD');
                $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;

                $meta = is_array($item->meta_json) ? $item->meta_json : [];
                $deviceLabel = is_string($meta['device_label'] ?? null) ? trim((string) $meta['device_label']) : '';

                $label = $deviceLabel !== '' ? ($deviceLabel.' - '.$name) : $name;

                $amount = ($qty * $unit) / 100;
                $lines[] = $label.' x'.$qty.' = '.$currency.' '.number_format($amount, 2, '.', '');
            }

            return implode("\n", $lines);
        }

        return '';
    }

    private function createAppointmentIfProvided(
        array $appointmentData,
        int $customerId,
        ?int $jobId,
        ?int $estimateId,
        Tenant $tenant,
    ): ?RepairBuddyAppointment {
        $settingId = is_numeric($appointmentData['appointment_setting_id'] ?? null)
            ? (int) $appointmentData['appointment_setting_id']
            : null;

        $date = is_string($appointmentData['date'] ?? null) && $appointmentData['date'] !== ''
            ? trim($appointmentData['date'])
            : null;

        $timeSlot = is_string($appointmentData['time_slot'] ?? null) && $appointmentData['time_slot'] !== ''
            ? trim($appointmentData['time_slot'])
            : null;

        if (! $settingId || ! $date || ! $timeSlot) {
            return null;
        }

        $setting = RepairBuddyAppointmentSetting::query()
            ->where('id', $settingId)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            Log::warning('booking.appointment_setting_not_found', [
                'setting_id' => $settingId,
            ]);

            return null;
        }

        $branch = BranchContext::branch();
        if (! $branch) {
            return null;
        }

        $appointmentDate = \Carbon\Carbon::parse($date)->toDateString();

        if ($appointmentDate < now()->toDateString()) {
            Log::warning('booking.appointment_date_in_past', [
                'date' => $appointmentDate,
            ]);

            return null;
        }

        $typeCount = RepairBuddyAppointment::query()
            ->where('appointment_setting_id', $settingId)
            ->where('appointment_date', $appointmentDate)
            ->whereNotIn('status', [RepairBuddyAppointment::STATUS_CANCELLED])
            ->count();

        if ($setting->max_appointments_per_day && $typeCount >= $setting->max_appointments_per_day) {
            Log::warning('booking.appointment_type_capacity_exceeded', [
                'setting_id' => $settingId,
                'date' => $appointmentDate,
                'count' => $typeCount,
                'max' => $setting->max_appointments_per_day,
            ]);

            return null;
        }

        if ($branch->max_appointments_per_day) {
            $branchCount = RepairBuddyAppointment::query()
                ->where('branch_id', $branch->id)
                ->where('appointment_date', $appointmentDate)
                ->whereNotIn('status', [RepairBuddyAppointment::STATUS_CANCELLED])
                ->count();

            if ($branchCount >= $branch->max_appointments_per_day) {
                Log::warning('booking.appointment_branch_capacity_exceeded', [
                    'branch_id' => $branch->id,
                    'date' => $appointmentDate,
                    'count' => $branchCount,
                    'max' => $branch->max_appointments_per_day,
                ]);

                return null;
            }
        }

        $duration = is_numeric($setting->slot_duration_minutes) ? (int) $setting->slot_duration_minutes : 30;
        $timeSlotStart = \Carbon\Carbon::parse($timeSlot);
        $timeSlotEnd = $timeSlotStart->copy()->addMinutes($duration);

        $appointment = RepairBuddyAppointment::query()->create([
            'appointment_setting_id' => $settingId,
            'job_id' => $jobId,
            'estimate_id' => $estimateId,
            'customer_id' => $customerId,
            'title' => $setting->title,
            'appointment_date' => $appointmentDate,
            'time_slot_start' => $timeSlotStart->format('H:i:s'),
            'time_slot_end' => $timeSlotEnd->format('H:i:s'),
            'status' => RepairBuddyAppointment::STATUS_SCHEDULED,
            'created_by' => null,
        ]);

        Log::info('booking.appointment_created', [
            'appointment_id' => $appointment->id,
            'setting_id' => $settingId,
            'date' => $appointmentDate,
            'time_slot' => $timeSlot,
            'customer_id' => $customerId,
            'job_id' => $jobId,
            'estimate_id' => $estimateId,
        ]);

        $customer = User::query()->where('id', $customerId)->first();
        if ($customer && $tenant) {
            try {
                $customer->notify(new AppointmentConfirmationNotification(
                    appointment: $appointment,
                    tenant: $tenant,
                ));
            } catch (\Throwable $e) {
                Log::error('booking.appointment_confirmation_notification_failed', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $appointment;
    }
}

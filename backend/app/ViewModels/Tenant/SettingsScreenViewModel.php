<?php

namespace App\ViewModels\Tenant;

use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyTax;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class SettingsScreenViewModel
{
    public function __construct(
        private Request $request,
        private Tenant $tenant,
    ) {
    }

    public function toArray(): array
    {
        $request = $this->request;
        $tenant = $this->tenant;

        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();

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

        $store = new TenantSettingsStore($tenant);

        $generalSettings = $store->get('general', []);
        $currencySettings = $store->get('currency', []);
        $invoiceSettings = $store->get('invoices', []);
        $jobStatusSettings = $store->get('jobStatus', []);
        $devicesBrandsSettings = $store->get('devicesBrands', []);
        $taxSettings = $store->get('taxes', []);
        $estimatesSettings = $store->get('estimates', []);
        $smsSettings = $store->get('sms', []);
        $accountSettings = $store->get('account', []);
        $signatureSettings = $store->get('signature', []);
        $bookingSettings = $store->get('bookings', []);
        $serviceSettings = $store->get('services', []);
        $timeLogSettings = $store->get('timeLog', []);
        $stylingSettings = $store->get('styling', []);
        $reviewsSettings = $store->get('reviews', []);
        $pagesSettings = $store->get('pages', []);

        if (! is_array($generalSettings)) {
            $generalSettings = [];
        }
        if (! is_array($currencySettings)) {
            $currencySettings = [];
        }
        if (! is_array($invoiceSettings)) {
            $invoiceSettings = [];
        }
        if (! is_array($jobStatusSettings)) {
            $jobStatusSettings = [];
        }
        if (! is_array($devicesBrandsSettings)) {
            $devicesBrandsSettings = [];
        }
        if (! is_array($taxSettings)) {
            $taxSettings = [];
        }
        if (! is_array($estimatesSettings)) {
            $estimatesSettings = [];
        }
        if (! is_array($smsSettings)) {
            $smsSettings = [];
        }
        if (! is_array($accountSettings)) {
            $accountSettings = [];
        }
        if (! is_array($signatureSettings)) {
            $signatureSettings = [];
        }
        if (! is_array($bookingSettings)) {
            $bookingSettings = [];
        }
        if (! is_array($serviceSettings)) {
            $serviceSettings = [];
        }
        if (! is_array($timeLogSettings)) {
            $timeLogSettings = [];
        }
        if (! is_array($stylingSettings)) {
            $stylingSettings = [];
        }
        if (! is_array($reviewsSettings)) {
            $reviewsSettings = [];
        }
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

        $taxEnable = (bool) ($taxSettings['enableTaxes'] ?? false);
        $taxInvoiceAmounts = is_string($taxSettings['invoiceAmounts'] ?? null) ? (string) $taxSettings['invoiceAmounts'] : 'exclusive';
        $taxDefaultId = $taxSettings['defaultTaxId'] ?? null;
        if (is_string($taxDefaultId) && $taxDefaultId !== '' && ctype_digit($taxDefaultId)) {
            $taxDefaultId = (int) $taxDefaultId;
        } else {
            $taxDefaultId = null;
        }

        $estimatesEnabledUi = (bool) ($estimatesSettings['enabled'] ?? false);
        $estimatesValidDaysUi = is_int($estimatesSettings['validDays'] ?? null) ? (int) $estimatesSettings['validDays'] : 30;

        $estimateEmailSubjectCustomerUi = (string) ($estimatesSettings['estimate_email_subject_to_customer'] ?? '');
        $estimateEmailBodyCustomerUi = (string) ($estimatesSettings['estimate_email_body_to_customer'] ?? '');
        $estimateApproveEmailSubjectAdminUi = (string) ($estimatesSettings['estimate_approve_email_subject_to_admin'] ?? '');
        $estimateApproveEmailBodyAdminUi = (string) ($estimatesSettings['estimate_approve_email_body_to_admin'] ?? '');
        $estimateRejectEmailSubjectAdminUi = (string) ($estimatesSettings['estimate_reject_email_subject_to_admin'] ?? '');
        $estimateRejectEmailBodyAdminUi = (string) ($estimatesSettings['estimate_reject_email_body_to_admin'] ?? '');

        $customerRegistrationUi = (bool) ($accountSettings['customerRegistration'] ?? false);
        $accountApprovalRequiredUi = (bool) ($accountSettings['accountApprovalRequired'] ?? false);
        $defaultCustomerRoleUi = (string) ($accountSettings['defaultCustomerRole'] ?? 'customer');
        if (! in_array($defaultCustomerRoleUi, ['customer', 'vip_customer'], true)) {
            $defaultCustomerRoleUi = 'customer';
        }

        $signatureRequiredUi = (bool) ($signatureSettings['required'] ?? false);
        $signatureTypeUi = (string) ($signatureSettings['type'] ?? 'draw');
        if (! in_array($signatureTypeUi, ['draw', 'type', 'upload'], true)) {
            $signatureTypeUi = 'draw';
        }
        $signatureTermsUi = is_string($signatureSettings['terms'] ?? null) ? (string) $signatureSettings['terms'] : '';

        $smsActiveUi = (bool) ($smsSettings['activateSmsForSelectiveStatuses'] ?? false);
        if (! $smsActiveUi) {
            $smsActiveUi = (bool) ($smsSettings['enabled'] ?? false);
        }
        $smsGatewayUi = is_string($smsSettings['gateway'] ?? null) ? (string) $smsSettings['gateway'] : '';
        $smsGatewayAccountSidUi = is_string($smsSettings['gatewayAccountSid'] ?? null) ? (string) $smsSettings['gatewayAccountSid'] : '';
        $smsGatewayAuthTokenUi = is_string($smsSettings['gatewayAuthToken'] ?? null) ? (string) $smsSettings['gatewayAuthToken'] : '';
        $smsGatewayFromNumberUi = is_string($smsSettings['gatewayFromNumber'] ?? null) ? (string) $smsSettings['gatewayFromNumber'] : '';
        $smsSendWhenStatusChangedToIdsUi = $smsSettings['sendWhenStatusChangedToIds'] ?? [];
        if (! is_array($smsSendWhenStatusChangedToIdsUi)) {
            $smsSendWhenStatusChangedToIdsUi = [];
        }
        $smsSendWhenStatusChangedToIdsUi = collect($smsSendWhenStatusChangedToIdsUi)
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->values()
            ->all();

        $smsTestNumberUi = is_string($smsSettings['testNumber'] ?? null) ? (string) $smsSettings['testNumber'] : '';
        $smsTestMessageUi = is_string($smsSettings['testMessage'] ?? null) ? (string) $smsSettings['testMessage'] : '';

        $timeLogDisabledUi = (bool) ($timeLogSettings['disabled'] ?? false);
        $timeLogDefaultTaxIdUi = $timeLogSettings['defaultTaxId'] ?? null;
        $timeLogIncludedStatusesUi = $timeLogSettings['jobStatusInclude'] ?? [];
        if (! is_array($timeLogIncludedStatusesUi)) {
            $timeLogIncludedStatusesUi = [];
        }
        $timeLogActivitiesUi = is_string($timeLogSettings['activities'] ?? null)
            ? (string) $timeLogSettings['activities']
            : "Repair\nDiagnostic\nTesting\nCleaning\nConsultation\nOther";

        $taxesForTimeLog = RepairBuddyTax::query()->orderBy('id')->limit(500)->get();
        $jobStatusesForTimeLog = Status::query()->where('status_type', 'Job')->orderBy('label')->limit(500)->get();

        $deliveryLabelUi = (string) ($stylingSettings['delivery_date_label'] ?? __('Delivery Date'));
        $pickupLabelUi = (string) ($stylingSettings['pickup_date_label'] ?? __('Pickup Date'));
        $nextServiceLabelUi = (string) ($stylingSettings['nextservice_date_label'] ?? __('Next Service Date'));
        $caseNumberLabelUi = (string) ($stylingSettings['casenumber_label'] ?? __('Case Number'));
        $primaryColorUi = (string) ($stylingSettings['primary_color'] ?? '#063e70');
        $secondaryColorUi = (string) ($stylingSettings['secondary_color'] ?? '#fd6742');

        $reviewsRequestBySmsUi = (bool) ($reviewsSettings['requestBySms'] ?? false);
        $reviewsRequestByEmailUi = (bool) ($reviewsSettings['requestByEmail'] ?? false);
        $reviewsFeedbackPageUrlUi = (string) ($reviewsSettings['get_feedback_page_url'] ?? '');
        $reviewsSendOnStatusUi = (string) ($reviewsSettings['send_request_job_status'] ?? '');
        $reviewsIntervalUi = (string) ($reviewsSettings['auto_request_interval'] ?? 'disabled');
        if (! in_array($reviewsIntervalUi, ['disabled', 'one-notification', 'two-notifications'], true)) {
            $reviewsIntervalUi = 'disabled';
        }
        $reviewsEmailSubjectUi = (string) ($reviewsSettings['email_subject'] ?? __('How would you rate the service you received?'));
        $reviewsEmailMessageUi = (string) ($reviewsSettings['email_message'] ?? '');
        $reviewsSmsMessageUi = (string) ($reviewsSettings['sms_message'] ?? '');

        $jobStatusesForReviews = Status::query()->where('status_type', 'Job')->orderBy('label')->limit(500)->get();

        $paymentStatuses = Status::query()
            ->where('status_type', 'Payment')
            ->orderBy('id')
            ->get();

        $paymentStatusOverrides = $tenantId
            ? \App\Models\TenantStatusOverride::query()
                ->where('tenant_id', $tenantId)
                ->where('domain', 'payment')
                ->get()
                ->keyBy('code')
            : collect();

        $maintenanceReminders = \App\Models\RepairBuddyMaintenanceReminder::query()
            ->with(['deviceType', 'deviceBrand'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $deviceTypesForMaintenance = RepairBuddyDeviceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get();

        $deviceBrandsForMaintenance = RepairBuddyDeviceBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get();

        $jobStatusOptions = Status::query()
            ->where('status_type', 'Job')
            ->orderBy('label')
            ->limit(200)
            ->get()
            ->filter(fn (Status $s) => is_string($s->code) && trim((string) $s->code) !== '')
            ->mapWithKeys(fn (Status $s) => [trim((string) $s->code) => (string) $s->label])
            ->all();

        $jobStatuses = Status::query()
            ->where('status_type', 'Job')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(500)
            ->get();

        $jobStatusCounts = [];
        if ($tenantId && $branchId) {
            $jobStatusCounts = RepairBuddyJob::query()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->selectRaw('status_slug, COUNT(*) as aggregate')
                ->groupBy('status_slug')
                ->pluck('aggregate', 'status_slug')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $estimateCounts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];
        if ($tenantId && $branchId) {
            $rawEstimateCounts = RepairBuddyEstimate::query()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($v) => (int) $v)
                ->all();

            foreach ($estimateCounts as $k => $_) {
                $estimateCounts[$k] = (int) ($rawEstimateCounts[$k] ?? 0);
            }
        }

        $dashoutputHtml = '';
        try {
            $dashoutputHtml = view('tenant.settings.sections.dashboard-content', [
                'tenant' => $tenant,
                'user' => $request->user(),
                'jobStatusOptions' => $jobStatusOptions,
                'jobStatusCounts' => $jobStatusCounts,
                'estimateCounts' => $estimateCounts,
                'dashboardBaseUrl' => $tenant->slug ? route('tenant.dashboard', ['business' => $tenant->slug]) : '#',
            ])->render();
        } catch (\Throwable $e) {
            $dashoutputHtml = '';
        }

        $countries = [];
        if (class_exists(\Symfony\Component\Intl\Countries::class)) {
            $countries = \Symfony\Component\Intl\Countries::getNames('en');
        }
        if (empty($countries) && class_exists(\ResourceBundle::class)) {
            $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
            if ($bundle) {
                foreach ($bundle as $code => $name) {
                    if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code) && is_string($name) && $name !== '') {
                        $countries[$code] = $name;
                    }
                }
            }
        }
        if (empty($countries)) {
            $countries = [
                'US' => 'United States',
                'GB' => 'United Kingdom',
                'CA' => 'Canada',
                'AU' => 'Australia',
            ];
        }
        ksort($countries);

        $currencyOptions = [];
        if (class_exists(\Symfony\Component\Intl\Currencies::class)) {
            $currencyOptions = \Symfony\Component\Intl\Currencies::getNames('en');
            if (is_array($currencyOptions)) {
                $currencyOptions = collect($currencyOptions)
                    ->mapWithKeys(fn ($label, $code) => [(string) $code => sprintf('%s — %s', (string) $code, (string) $label)])
                    ->all();
            } else {
                $currencyOptions = [];
            }
        }
        if (empty($currencyOptions)) {
            $currencyOptions = [
                'USD' => 'USD',
                'EUR' => 'EUR',
                'GBP' => 'GBP',
                'ZAR' => 'ZAR',
            ];
        }

        $tenantCurrency = is_string($tenant->currency) ? (string) $tenant->currency : '';
        if ($tenantCurrency !== '' && ! array_key_exists($tenantCurrency, $currencyOptions)) {
            $currencyOptions[$tenantCurrency] = $tenantCurrency;
        }
        ksort($currencyOptions);

        $currencyPositionOptions = [
            'left' => __('Left'),
            'right' => __('Right'),
            'left_space' => __('Left with space'),
            'right_space' => __('Right with space'),
        ];

        $tenantNameForSubject = (string) ($tenant->name ?? '');
        if (trim($tenantNameForSubject) === '') {
            $tenantNameForSubject = (string) config('app.name');
        }

        if (trim($estimateEmailSubjectCustomerUi) === '') {
            $estimateEmailSubjectCustomerUi = 'You have received an estimate! | '.$tenantNameForSubject;
        }
        if (trim($estimateEmailBodyCustomerUi) === '') {
            $estimateEmailBodyCustomerUi = "Hello {{customer_full_name}},\n\nWe have prepared an estimate for you. If you have further questions please contact us.\n\nYour estimate details are listed below. You can approve or reject estimate as per your choice. If you have questions please get in touch.\n\nApprove/Reject the Estimate\n\n{{start_approve_estimate_link}}Approve Estimate{{end_approve_estimate_link}}\n\n{{start_reject_estimate_link}}Reject Estimate {{end_reject_estimate_link}}\n\n\n{{order_invoice_details}}\n\nThank you again for your business!";
        }
        if (trim($estimateApproveEmailSubjectAdminUi) === '') {
            $estimateApproveEmailSubjectAdminUi = 'Congratulations! Customer have approved your estimate! | '.$tenantNameForSubject;
        }
        if (trim($estimateApproveEmailBodyAdminUi) === '') {
            $estimateApproveEmailBodyAdminUi = "Hello,\n\nEstimate you sent to {{customer_full_name}} have been approved by customer and converted to job.\n\nJob ID : {{job_id}} created from Estimate ID : {{estimate_id}}\n\nThank you!";
        }
        if (trim($estimateRejectEmailSubjectAdminUi) === '') {
            $estimateRejectEmailSubjectAdminUi = 'Estimate have been rejected! | '.$tenantNameForSubject;
        }
        if (trim($estimateRejectEmailBodyAdminUi) === '') {
            $estimateRejectEmailBodyAdminUi = "Hello,\n\nEstimate you sent to {{customer_full_name}} have been rejected by customer.\n\nEstimate ID : {{estimate_id}}\n\nThank you!";
        }

        $bookingEmailSubjectCustomerUi = (string) ($bookingSettings['booking_email_subject_to_customer'] ?? '');
        if (trim($bookingEmailSubjectCustomerUi) === '') {
            $bookingEmailSubjectCustomerUi = 'We have received your booking order! | '.$tenantNameForSubject;
        }

        $bookingEmailBodyCustomerUi = (string) ($bookingSettings['booking_email_body_to_customer'] ?? '');
        if (trim($bookingEmailBodyCustomerUi) === '') {
            $bookingEmailBodyCustomerUi = "Hello {{customer_full_name}},\n\nThank you for booking. We have received your job id : {{job_id}} and assigned you Case number : {{case_number}}\n\nFor your device : {{customer_device_label}} \n\nNote: Job status page will not able to show your job details unless its approved from our side. During our working hours its done quickly.\n\nWe will get in touch whenever its needed. You can always check your job status by clicking {{start_anch_status_check_link}} Check Status {{end_anch_status_check_link}}.\n\nDirect status check link : {{status_check_link}}\n\nDetails which we have received from you are below. \n\n{{order_invoice_details}}\n\nThank you again for your business!";
        }

        $bookingEmailSubjectAdminUi = (string) ($bookingSettings['booking_email_subject_to_admin'] ?? '');
        if (trim($bookingEmailSubjectAdminUi) === '') {
            $bookingEmailSubjectAdminUi = 'You have new booking order | '.$tenantNameForSubject;
        }

        $bookingEmailBodyAdminUi = (string) ($bookingSettings['booking_email_body_to_admin'] ?? '');
        if (trim($bookingEmailBodyAdminUi) === '') {
            $bookingEmailBodyAdminUi = "Hello,\n\nYou have received a new booking job ID: {{job_id}} Case number: {{case_number}}.\n\nFrom Customer : {{customer_full_name}}\n\nJob Details are listed below.\n\n{{order_invoice_details}}\n\n";
        }

        $turnBookingFormsToJobsUi = (bool) ($bookingSettings['turnBookingFormsToJobs'] ?? false);
        $turnOffOtherDeviceBrandsUi = (bool) ($bookingSettings['turnOffOtherDeviceBrands'] ?? false);
        $turnOffOtherServiceUi = (bool) ($bookingSettings['turnOffOtherService'] ?? false);
        $turnOffServicePriceUi = (bool) ($bookingSettings['turnOffServicePrice'] ?? false);
        $turnOffIdImeiBookingUi = (bool) ($bookingSettings['turnOffIdImeiBooking'] ?? false);

        $bookingDefaultTypeIdUi = $bookingSettings['wc_booking_default_type'] ?? null;
        $bookingDefaultBrandIdUi = $bookingSettings['wc_booking_default_brand'] ?? null;
        $bookingDefaultDeviceIdUi = $bookingSettings['wc_booking_default_device'] ?? null;

        $deviceTypesForBookings = \App\Models\RepairBuddyDeviceType::query()->orderBy('name')->limit(200)->get();
        $deviceBrandsForBookings = \App\Models\RepairBuddyDeviceBrand::query()->orderBy('name')->limit(200)->get();
        $devicesForBookings = \App\Models\RepairBuddyDevice::query()->orderBy('model')->limit(200)->get();

        $serviceSidebarDescriptionUi = (string) ($serviceSettings['wc_service_sidebar_description'] ?? __('Below you can check price by type or brand and to get accurate value check devices.'));
        $serviceBookingHeadingUi = (string) ($serviceSettings['wc_service_booking_heading'] ?? __('Book Service'));
        $serviceDisableBookingOnServicePageUi = (bool) ($serviceSettings['disableBookingOnServicePage'] ?? false);
        $serviceBookingFormUi = (string) ($serviceSettings['wc_service_booking_form'] ?? 'without_type');
        if (! in_array($serviceBookingFormUi, ['with_type', 'without_type', 'warranty_booking'], true)) {
            $serviceBookingFormUi = 'without_type';
        }

        return [
            'tenant' => $tenant,
            'user' => $request->user(),
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
            'menu_name_p' => (string) ($generalSettings['menu_name'] ?? ''),
            'wc_rb_business_name' => $tenant->name ?? '',
            'wc_rb_business_phone' => (string) ($generalSettings['wc_rb_business_phone'] ?? ($tenant->contact_phone ?? '')),
            'wc_rb_business_address' => (string) ($generalSettings['wc_rb_business_address'] ?? ''),
            'computer_repair_logo' => (string) ($generalSettings['computer_repair_logo'] ?? (is_string($tenant->logo_url) ? $tenant->logo_url : '')),
            'computer_repair_email' => (string) ($generalSettings['computer_repair_email'] ?? ($tenant->contact_email ?? '')),
            'wc_rb_gdpr_acceptance_link' => (string) ($generalSettings['wc_rb_gdpr_acceptance_link'] ?? ''),
            'wc_rb_gdpr_acceptance_link_label' => (string) ($generalSettings['wc_rb_gdpr_acceptance_link_label'] ?? 'Privacy policy'),
            'wc_rb_gdpr_acceptance' => (string) ($generalSettings['wc_rb_gdpr_acceptance'] ?? 'I understand that I will be contacted by a representative regarding this request and I agree to the privacy policy.'),
            'case_number_length' => (int) ($generalSettings['case_number_length'] ?? 6),
            'case_number_prefix' => (string) ($generalSettings['case_number_prefix'] ?? 'WC_'),
            'wc_primary_country' => (string) ($generalSettings['wc_primary_country'] ?? ''),
            'useWooProducts' => ($generalSettings['wc_enable_woo_products'] ?? false) ? 'checked' : '',
            'disableStatusCheckSerial' => ($generalSettings['wcrb_disable_statuscheck_serial'] ?? false) ? 'checked' : '',
            'disableNextServiceDate' => ($generalSettings['wcrb_next_service_date'] ?? false) ? 'checked' : '',
            'send_notice' => ($generalSettings['wc_job_status_cr_notice'] ?? false) ? 'checked' : '',
            'attach_pdf' => ($generalSettings['wcrb_attach_pdf_in_customer_emails'] ?? false) ? 'checked' : '',
            'wc_cr_selected_currency' => (string) ($currencySettings['wc_cr_selected_currency'] ?? ($tenant->currency ?? '')),
            'wc_cr_currency_position' => (string) ($currencySettings['wc_cr_currency_position'] ?? 'left'),
            'wc_cr_thousand_separator' => (string) ($currencySettings['wc_cr_thousand_separator'] ?? ','),
            'wc_cr_decimal_separator' => (string) ($currencySettings['wc_cr_decimal_separator'] ?? '.'),
            'wc_cr_number_of_decimals' => (string) ($currencySettings['wc_cr_number_of_decimals'] ?? '0'),
            'wc_cr_currency_options_html' => '',
            'wc_cr_currency_position_options_html' => '',
            'wc_repair_order_print_size' => (string) ($invoiceSettings['wc_repair_order_print_size'] ?? 'default'),
            'repair_order_type' => (string) ($invoiceSettings['repair_order_type'] ?? 'pos_type'),
            'wb_rb_invoice_type' => (string) ($invoiceSettings['wb_rb_invoice_type'] ?? 'default'),
            'business_terms' => (string) ($invoiceSettings['business_terms'] ?? ''),
            'wc_rb_ro_thanks_msg' => (string) ($invoiceSettings['wc_rb_ro_thanks_msg'] ?? ''),
            'wc_rb_io_thanks_msg' => (string) ($invoiceSettings['wc_rb_io_thanks_msg'] ?? ''),
            'wc_rb_cr_display_add_on_ro' => (bool) ($invoiceSettings['wc_rb_cr_display_add_on_ro'] ?? false),
            'wc_rb_cr_display_add_on_ro_cu' => (bool) ($invoiceSettings['wc_rb_cr_display_add_on_ro_cu'] ?? false),
            'wcrb_add_invoice_qr_code' => (bool) ($invoiceSettings['wcrb_add_invoice_qr_code'] ?? false),
            'pickupdate_checked' => ($invoiceSettings['pickupdate'] ?? false) ? 'checked' : '',
            'deliverydate_checked' => ($invoiceSettings['deliverydate'] ?? false) ? 'checked' : '',
            'nextservicedate_checked' => ($invoiceSettings['nextservicedate'] ?? false) ? 'checked' : '',
            'wcrb_invoice_disclaimer_html' => '',
            'job_status_rows_html' => '',
            'jobStatuses' => $jobStatuses,
            'wc_inventory_management_status' => false,
            'job_status_delivered' => (string) ($jobStatusSettings['wcrb_job_status_delivered'] ?? 'delivered'),
            'job_status_cancelled' => (string) ($jobStatusSettings['wcrb_job_status_cancelled'] ?? 'cancelled'),
            'status_options_delivered_html' => '',
            'status_options_cancelled_html' => '',
            'extraTabs' => [
                // ['id' => 'wc_rb_page_settings', 'label' => __('Pages Setup'), 'view' => 'tenant.settings.sections.pages-setup'],
                ['id' => 'wc_rb_manage_devices', 'label' => __('Devices & Brands'), 'view' => 'tenant.settings.sections.devices-brands'],
                ['id' => 'wc_rb_manage_bookings', 'label' => __('Booking Settings'), 'view' => 'tenant.settings.sections.bookings'],
                ['id' => 'wc_rb_maintenance_reminder', 'label' => __('Maintenance Reminders'), 'view' => 'tenant.settings.sections.maintenance-reminders'],
                ['id' => 'wc_rb_manage_taxes', 'label' => __('Manage Taxes'), 'view' => 'tenant.settings.sections.taxes'],
                ['id' => 'wc_rb_manage_service', 'label' => __('Service Settings'), 'view' => 'tenant.settings.sections.services'],
                ['id' => 'wcrb_styling', 'label' => __('Styling & Labels'), 'view' => 'tenant.settings.sections.styling'],
                ['id' => 'wcrb_estimates_tab', 'label' => __('Estimates'), 'view' => 'tenant.settings.sections.estimates'],
                ['id' => 'wcrb_timelog_tab', 'label' => __('Time Log Settings'), 'view' => 'tenant.settings.sections.timelog'],
                ['id' => 'wcrb_reviews_tab', 'label' => __('Job Reviews'), 'view' => 'tenant.settings.sections.reviews'],
                ['id' => 'wc_rb_manage_account', 'label' => __('My Account Settings'), 'view' => 'tenant.settings.sections.account'],
                ['id' => 'wcrb_signature_workflow', 'label' => __('Signature Workflow'), 'view' => 'tenant.settings.sections.signature'],
            ],
            'deviceBrands' => $deviceBrands,
            'devicesBrandsUi' => $devicesBrandsUi,
            'additionalDeviceFields' => $additionalDeviceFields,
            'pickupDeliveryEnabled' => $pickupDeliveryEnabled,
            'pickupCharge' => $pickupCharge,
            'deliveryCharge' => $deliveryCharge,
            'rentalEnabled' => $rentalEnabled,
            'rentalPerDay' => $rentalPerDay,
            'rentalPerWeek' => $rentalPerWeek,
            'bookingEmailSubjectCustomerUi' => $bookingEmailSubjectCustomerUi,
            'bookingEmailBodyCustomerUi' => $bookingEmailBodyCustomerUi,
            'bookingEmailSubjectAdminUi' => $bookingEmailSubjectAdminUi,
            'bookingEmailBodyAdminUi' => $bookingEmailBodyAdminUi,
            'turnBookingFormsToJobsUi' => $turnBookingFormsToJobsUi,
            'turnOffOtherDeviceBrandsUi' => $turnOffOtherDeviceBrandsUi,
            'turnOffOtherServiceUi' => $turnOffOtherServiceUi,
            'turnOffServicePriceUi' => $turnOffServicePriceUi,
            'turnOffIdImeiBookingUi' => $turnOffIdImeiBookingUi,
            'bookingDefaultTypeIdUi' => $bookingDefaultTypeIdUi,
            'bookingDefaultBrandIdUi' => $bookingDefaultBrandIdUi,
            'bookingDefaultDeviceIdUi' => $bookingDefaultDeviceIdUi,
            'deviceTypesForBookings' => $deviceTypesForBookings,
            'deviceBrandsForBookings' => $deviceBrandsForBookings,
            'devicesForBookings' => $devicesForBookings,
            'serviceSidebarDescriptionUi' => $serviceSidebarDescriptionUi,
            'serviceBookingHeadingUi' => $serviceBookingHeadingUi,
            'serviceDisableBookingOnServicePageUi' => $serviceDisableBookingOnServicePageUi,
            'serviceBookingFormUi' => $serviceBookingFormUi,
            'estimatesEnabledUi' => $estimatesEnabledUi,
            'estimatesValidDaysUi' => $estimatesValidDaysUi,
            'estimateEmailSubjectCustomerUi' => $estimateEmailSubjectCustomerUi,
            'estimateEmailBodyCustomerUi' => $estimateEmailBodyCustomerUi,
            'estimateApproveEmailSubjectAdminUi' => $estimateApproveEmailSubjectAdminUi,
            'estimateApproveEmailBodyAdminUi' => $estimateApproveEmailBodyAdminUi,
            'estimateRejectEmailSubjectAdminUi' => $estimateRejectEmailSubjectAdminUi,
            'estimateRejectEmailBodyAdminUi' => $estimateRejectEmailBodyAdminUi,
            'taxes' => $taxes,
            'taxEnable' => $taxEnable,
            'taxInvoiceAmounts' => $taxInvoiceAmounts,
            'taxDefaultId' => $taxDefaultId,
            'maintenanceReminders' => $maintenanceReminders,
            'deviceTypesForMaintenance' => $deviceTypesForMaintenance,
            'deviceBrandsForMaintenance' => $deviceBrandsForMaintenance,
            'timeLogDisabledUi' => $timeLogDisabledUi,
            'timeLogDefaultTaxIdUi' => $timeLogDefaultTaxIdUi,
            'timeLogIncludedStatusesUi' => $timeLogIncludedStatusesUi,
            'timeLogActivitiesUi' => $timeLogActivitiesUi,
            'taxesForTimeLog' => $taxesForTimeLog,
            'jobStatusesForTimeLog' => $jobStatusesForTimeLog,
            'deliveryLabelUi' => $deliveryLabelUi,
            'pickupLabelUi' => $pickupLabelUi,
            'nextServiceLabelUi' => $nextServiceLabelUi,
            'caseNumberLabelUi' => $caseNumberLabelUi,
            'primaryColorUi' => $primaryColorUi,
            'secondaryColorUi' => $secondaryColorUi,
            'reviewsRequestBySmsUi' => $reviewsRequestBySmsUi,
            'reviewsRequestByEmailUi' => $reviewsRequestByEmailUi,
            'reviewsFeedbackPageUrlUi' => $reviewsFeedbackPageUrlUi,
            'reviewsSendOnStatusUi' => $reviewsSendOnStatusUi,
            'reviewsIntervalUi' => $reviewsIntervalUi,
            'reviewsEmailSubjectUi' => $reviewsEmailSubjectUi,
            'reviewsEmailMessageUi' => $reviewsEmailMessageUi,
            'reviewsSmsMessageUi' => $reviewsSmsMessageUi,
            'jobStatusesForReviews' => $jobStatusesForReviews,
            'customerRegistrationUi' => $customerRegistrationUi,
            'accountApprovalRequiredUi' => $accountApprovalRequiredUi,
            'defaultCustomerRoleUi' => $defaultCustomerRoleUi,
            'signatureRequiredUi' => $signatureRequiredUi,
            'signatureTypeUi' => $signatureTypeUi,
            'signatureTermsUi' => $signatureTermsUi,
            'settings_tab_menu_items_html' => '',
            'settings_tab_body_html' => '',
            'paymentStatuses' => $paymentStatuses,
            'paymentStatusOverrides' => $paymentStatusOverrides,
            'paymentMethodsActive' => (function () use ($store) {
                $methods = $store->get('payment_methods_active', []);
                return is_array($methods) ? $methods : [];
            })(),
            'add_status_form_footer_html' => '',
            'activation_form_html' => '',
            'nonce_main_setting_html' => '',
            'nonce_currency_setting_html' => '',
            'nonce_report_setting_html' => '',
            'nonce_delivered_status_html' => '',
            'dashoutput_html' => $dashoutputHtml,
            'countries_options_html' => '',
            'countries' => $countries,
            'currencyOptions' => $currencyOptions,
            'currencyPositionOptions' => $currencyPositionOptions,
            'jobStatusOptions' => $jobStatusOptions,
            'smsActiveUi' => $smsActiveUi,
            'smsGatewayUi' => $smsGatewayUi,
            'smsGatewayAccountSidUi' => $smsGatewayAccountSidUi,
            'smsGatewayAuthTokenUi' => $smsGatewayAuthTokenUi,
            'smsGatewayFromNumberUi' => $smsGatewayFromNumberUi,
            'smsSendWhenStatusChangedToIdsUi' => $smsSendWhenStatusChangedToIdsUi,
            'smsTestNumberUi' => $smsTestNumberUi,
            'smsTestMessageUi' => $smsTestMessageUi,
            'smsGatewayOptions' => [
                '' => __('Select SMS Gateway'),
                'twilio' => 'Twilio',
                'releans' => 'Releans',
                'bulkgate' => 'BulkGate',
                'smschef' => 'SMSChef',
                'smshosting' => 'SMSHosting.it',
                'capitolemobile' => 'Capitole Mobile',
                'bitelietuva' => 'Bite Lietuva',
                'textmecoil' => 'TextMe.co.il',
                'custom' => __('Custom'),
            ],
            'allJobStatuses' => $jobStatuses,
            'rb_ms_version_defined' => false,
            'rb_qb_version_defined' => false,
            'casenumber_label_first' => 'Case',
            'casenumber_label_none' => 'Case',
            'woocommerce_activated' => true,
            'pickup_date_label_none' => 'pickup_date',
            'delivery_date_label_none' => 'delivery_date',
            'nextservice_date_label_none' => 'nextservice_date',
            'pagesSetupValues' => $pagesSetupValues,
        ];
    }
}

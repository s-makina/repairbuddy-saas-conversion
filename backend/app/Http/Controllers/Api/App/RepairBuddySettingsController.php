<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddySettingsController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = $this->applyTenantIdentityToSettings($settings, $tenant);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],

            'settings.general' => ['sometimes', 'array'],
            'settings.general.caseNumberPrefix' => ['sometimes', 'nullable', 'string', 'max:32'],
            'settings.general.caseNumberLength' => ['sometimes', 'integer', 'min:1', 'max:32'],
            'settings.general.emailCustomer' => ['sometimes', 'boolean'],
            'settings.general.attachPdf' => ['sometimes', 'boolean'],
            'settings.general.nextServiceDateEnabled' => ['sometimes', 'boolean'],
            'settings.general.gdprAcceptanceText' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.general.gdprLinkLabel' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.general.gdprLinkUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'settings.general.disableStatusCheckBySerial' => ['sometimes', 'boolean'],

            'settings.invoicesReports' => ['sometimes', 'array'],
            'settings.invoicesReports.addQrCodeToInvoice' => ['sometimes', 'boolean'],
            'settings.invoicesReports.invoiceFooterMessage' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'settings.invoicesReports.invoicePrintType' => ['sometimes', 'string', 'in:standard,thermal'],
            'settings.invoicesReports.displayPickupDate' => ['sometimes', 'boolean'],
            'settings.invoicesReports.displayDeliveryDate' => ['sometimes', 'boolean'],
            'settings.invoicesReports.displayNextServiceDate' => ['sometimes', 'boolean'],
            'settings.invoicesReports.invoiceDisclaimerTerms' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.invoicesReports.repairOrderType' => ['sometimes', 'string', 'in:standard,detailed'],
            'settings.invoicesReports.termsUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'settings.invoicesReports.repairOrderPrintSize' => ['sometimes', 'string', 'in:a4,letter'],
            'settings.invoicesReports.displayBusinessAddressDetails' => ['sometimes', 'boolean'],
            'settings.invoicesReports.displayCustomerEmailAddressDetails' => ['sometimes', 'boolean'],
            'settings.invoicesReports.repairOrderFooterMessage' => ['sometimes', 'nullable', 'string', 'max:1024'],

            'settings.payments' => ['sometimes', 'array'],
            'settings.payments.paymentMethods' => ['sometimes', 'array'],
            'settings.payments.paymentMethods.cash' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.card' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.bankTransfer' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.paypal' => ['sometimes', 'boolean'],
            'settings.payments.paymentMethods.other' => ['sometimes', 'boolean'],

            'settings.reviews' => ['sometimes', 'array'],
            'settings.reviews.requestFeedbackBySms' => ['sometimes', 'boolean'],
            'settings.reviews.requestFeedbackByEmail' => ['sometimes', 'boolean'],
            'settings.reviews.feedbackPage' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.reviews.sendReviewRequestIfJobStatusId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.reviews.autoFeedbackRequestIntervalDays' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'settings.reviews.emailSubject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.reviews.emailMessageTemplate' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.reviews.smsMessageTemplate' => ['sometimes', 'nullable', 'string', 'max:4096'],

            'settings.estimates' => ['sometimes', 'array'],
            'settings.estimates.customerEmailSubject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.estimates.customerEmailBody' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.estimates.disableEstimates' => ['sometimes', 'boolean'],
            'settings.estimates.bookingQuoteSendToJobs' => ['sometimes', 'boolean'],
            'settings.estimates.adminApproveRejectEmailSubject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.estimates.adminApproveRejectEmailBody' => ['sometimes', 'nullable', 'string', 'max:4096'],

            'settings.booking' => ['sometimes', 'array'],
            'settings.booking.customerEmailSubject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.booking.customerEmailBody' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.booking.adminEmailSubject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.booking.adminEmailBody' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'settings.booking.sendBookingQuoteToJobs' => ['sometimes', 'boolean'],
            'settings.booking.turnOffOtherDeviceBrand' => ['sometimes', 'boolean'],
            'settings.booking.turnOffOtherService' => ['sometimes', 'boolean'],
            'settings.booking.turnOffServicePrice' => ['sometimes', 'boolean'],
            'settings.booking.turnOffIdImeiInBooking' => ['sometimes', 'boolean'],
            'settings.booking.defaultType' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.booking.defaultBrand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.booking.defaultDevice' => ['sometimes', 'nullable', 'string', 'max:255'],

            'settings.myAccount' => ['sometimes', 'array'],
            'settings.myAccount.disableBooking' => ['sometimes', 'boolean'],
            'settings.myAccount.disableEstimates' => ['sometimes', 'boolean'],
            'settings.myAccount.disableReviews' => ['sometimes', 'boolean'],
            'settings.myAccount.bookingFormType' => ['sometimes', 'string', 'in:simple,detailed'],

            'settings.devicesBrands' => ['sometimes', 'array'],
            'settings.devicesBrands.enablePinCodeField' => ['sometimes', 'boolean'],
            'settings.devicesBrands.showPinCodeInDocuments' => ['sometimes', 'boolean'],
            'settings.devicesBrands.useWooProductsAsDevices' => ['sometimes', 'boolean'],
            'settings.devicesBrands.labels' => ['sometimes', 'array'],
            'settings.devicesBrands.labels.note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.labels.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.labels.device' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.labels.deviceBrand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.labels.deviceType' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.labels.imei' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.additionalDeviceFields' => ['sometimes', 'array'],
            'settings.devicesBrands.additionalDeviceFields.*.id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.devicesBrands.additionalDeviceFields.*.label' => ['sometimes', 'required', 'string', 'max:255'],
            'settings.devicesBrands.additionalDeviceFields.*.type' => ['sometimes', 'string', 'in:text'],
            'settings.devicesBrands.additionalDeviceFields.*.displayInBookingForm' => ['sometimes', 'boolean'],
            'settings.devicesBrands.additionalDeviceFields.*.displayInInvoice' => ['sometimes', 'boolean'],
            'settings.devicesBrands.additionalDeviceFields.*.displayForCustomer' => ['sometimes', 'boolean'],
            'settings.devicesBrands.pickupDeliveryEnabled' => ['sometimes', 'boolean'],
            'settings.devicesBrands.pickupCharge' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.devicesBrands.deliveryCharge' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.devicesBrands.rentalEnabled' => ['sometimes', 'boolean'],
            'settings.devicesBrands.rentalPerDay' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.devicesBrands.rentalPerWeek' => ['sometimes', 'nullable', 'string', 'max:64'],

            'settings.sms' => ['sometimes', 'array'],
            'settings.sms.activateSmsForSelectiveStatuses' => ['sometimes', 'boolean'],
            'settings.sms.gateway' => ['sometimes', 'string', 'in:twilio,nexmo,custom'],
            'settings.sms.gatewayAccountSid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.sms.gatewayAuthToken' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.sms.gatewayFromNumber' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.sms.sendWhenStatusChangedToIds' => ['sometimes', 'array'],
            'settings.sms.sendWhenStatusChangedToIds.*' => ['sometimes', 'string', 'max:255'],
            'settings.sms.testNumber' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.sms.testMessage' => ['sometimes', 'nullable', 'string', 'max:1024'],

            'settings.taxes' => ['sometimes', 'array'],
            'settings.taxes.enableTaxes' => ['sometimes', 'boolean'],
            'settings.taxes.defaultTaxId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.taxes.invoiceAmounts' => ['sometimes', 'string', 'in:exclusive,inclusive'],

            'settings.timeLogs' => ['sometimes', 'array'],
            'settings.timeLogs.disableTimeLog' => ['sometimes', 'boolean'],
            'settings.timeLogs.defaultTaxIdForHours' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.timeLogs.enableTimeLogForStatusIds' => ['sometimes', 'array'],
            'settings.timeLogs.enableTimeLogForStatusIds.*' => ['sometimes', 'string', 'max:255'],
            'settings.timeLogs.activities' => ['sometimes', 'nullable', 'string', 'max:4096'],
        ]);

        $before = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $next = $request->input('settings');
        if (! is_array($next)) {
            $next = [];
        }

        $next = $this->applyTenantIdentityToSettings($next, $tenant);

        $booking = [];
        if (array_key_exists('booking', $next) && is_array($next['booking'])) {
            $booking = $next['booking'];
        }

        $estimates = [];
        if (array_key_exists('estimates', $next) && is_array($next['estimates'])) {
            $estimates = $next['estimates'];
        }

        $bookingSendToJobs = array_key_exists('sendBookingQuoteToJobs', $booking) ? $booking['sendBookingQuoteToJobs'] : null;
        $estimatesSendToJobs = array_key_exists('bookingQuoteSendToJobs', $estimates) ? $estimates['bookingQuoteSendToJobs'] : null;

        if (is_bool($bookingSendToJobs) && ! is_bool($estimatesSendToJobs)) {
            $estimates['bookingQuoteSendToJobs'] = $bookingSendToJobs;
        } elseif (! is_bool($bookingSendToJobs) && is_bool($estimatesSendToJobs)) {
            $booking['sendBookingQuoteToJobs'] = $estimatesSendToJobs;
        } elseif (is_bool($bookingSendToJobs) && is_bool($estimatesSendToJobs) && $bookingSendToJobs !== $estimatesSendToJobs) {
            $estimates['bookingQuoteSendToJobs'] = $bookingSendToJobs;
        }

        if (! empty($booking)) {
            $next['booking'] = $booking;
        }

        if (! empty($estimates)) {
            $next['estimates'] = $estimates;
        }

        $state = $tenant->setup_state ?? [];
        if (! is_array($state)) {
            $state = [];
        }

        $state['repairbuddy_settings'] = $next;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        PlatformAudit::log($request, 'tenant.repairbuddy_settings.updated', $tenant, null, [
            'before' => $before,
            'after' => $next,
        ]);

        return response()->json([
            'settings' => $next,
        ]);
    }

    private function formatTenantAddress(Tenant $tenant): string
    {
        $addr = $tenant->billing_address_json;
        if (! is_array($addr)) {
            $addr = [];
        }

        $parts = [];
        foreach (['line1', 'line2', 'city', 'state', 'postal_code'] as $key) {
            $value = $addr[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $parts[] = trim($value);
            }
        }

        $country = is_string($tenant->billing_country) ? strtoupper(trim($tenant->billing_country)) : '';
        if ($country !== '') {
            $parts[] = $country;
        }

        return implode(', ', $parts);
    }

    private function applyTenantIdentityToSettings(array $settings, Tenant $tenant): array
    {
        $general = [];
        if (array_key_exists('general', $settings) && is_array($settings['general'])) {
            $general = $settings['general'];
        }

        $general['businessName'] = $tenant->name ?? '';
        $general['businessPhone'] = $tenant->contact_phone ?? '';
        $general['email'] = $tenant->contact_email ?? '';
        $general['businessAddress'] = $this->formatTenantAddress($tenant);
        $general['logoUrl'] = is_string($tenant->logo_url) ? $tenant->logo_url : '';

        $country = is_string($tenant->billing_country) ? strtoupper(trim($tenant->billing_country)) : '';
        if ($country !== '') {
            $general['defaultCountry'] = $country;
        } elseif (! array_key_exists('defaultCountry', $general)) {
            $general['defaultCountry'] = 'US';
        }

        $settings['general'] = $general;

        return $settings;
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobStatus;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantDashboardController extends Controller
{
    private function ensureDefaultRepairBuddyStatuses(int $tenantId, ?int $branchId = null): void
    {
        if (! Schema::hasTable('rb_job_statuses') || ! Schema::hasTable('rb_payment_statuses')) {
            return;
        }

        DB::transaction(function () use ($tenantId) {
            $jobDefaults = [
                ['slug' => 'new', 'label' => 'New Order', 'invoice_label' => 'Invoice'],
                ['slug' => 'quote', 'label' => 'Quote', 'invoice_label' => 'Quote'],
                ['slug' => 'cancelled', 'label' => 'Cancelled', 'invoice_label' => 'Cancelled'],
                ['slug' => 'inprocess', 'label' => 'In Process', 'invoice_label' => 'Work Order'],
                ['slug' => 'inservice', 'label' => 'In Service', 'invoice_label' => 'Work Order'],
                ['slug' => 'ready_complete', 'label' => 'Ready/Complete', 'invoice_label' => 'Invoice'],
                ['slug' => 'delivered', 'label' => 'Delivered', 'invoice_label' => 'Invoice'],
            ];

            $paymentDefaults = [
                ['slug' => 'nostatus', 'label' => 'No Status'],
                ['slug' => 'credit', 'label' => 'Credit'],
                ['slug' => 'paid', 'label' => 'Paid'],
                ['slug' => 'partial', 'label' => 'Partially Paid'],
            ];

            foreach ($jobDefaults as $s) {
                DB::table('rb_job_statuses')->updateOrInsert([
                    'tenant_id' => $tenantId,
                    'slug' => $s['slug'],
                ], [
                    'label' => $s['label'],
                    'email_enabled' => false,
                    'email_template' => null,
                    'sms_enabled' => false,
                    'invoice_label' => $s['invoice_label'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            foreach ($paymentDefaults as $s) {
                DB::table('rb_payment_statuses')->updateOrInsert([
                    'tenant_id' => $tenantId,
                    'slug' => $s['slug'],
                ], [
                    'label' => $s['label'],
                    'email_template' => null,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function savePaymentStatus(Request $request)
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $validated = $request->validate([
            'payment_status_name' => ['required', 'string', 'max:255'],
            'payment_status_slug' => ['required', 'string', 'max:64'],
            'payment_status_status' => ['sometimes', 'in:active,inactive'],
            'form_type_status_payment' => ['sometimes', 'in:add,update'],
            'status_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $slug = Str::of((string) $validated['payment_status_slug'])
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        if ($slug === '') {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_payment_status')
                ->withErrors(['payment_status_slug' => 'Status slug is invalid.'])
                ->withInput();
        }

        $statusValue = (string) ($validated['payment_status_status'] ?? 'active');
        $isActive = $statusValue === 'active';

        $mode = (string) ($validated['form_type_status_payment'] ?? 'add');

        if ($mode === 'update') {
            $id = (int) ($validated['status_id'] ?? 0);
            if ($id <= 0) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['status_id' => 'Payment status id is missing.'])
                    ->withInput();
            }

            $existing = \App\Models\RepairBuddyPaymentStatus::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($id)
                ->first();

            if (! $existing) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['status_id' => 'Payment status not found.'])
                    ->withInput();
            }

            $slugExists = \App\Models\RepairBuddyPaymentStatus::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->whereKeyNot($existing->id)
                ->exists();

            if ($slugExists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_slug' => 'This status slug already exists.'])
                    ->withInput();
            }

            $existing->forceFill([
                'label' => (string) $validated['payment_status_name'],
                'slug' => $slug,
                'is_active' => $isActive,
            ])->save();
        } else {
            $slugExists = \App\Models\RepairBuddyPaymentStatus::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->exists();

            if ($slugExists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_slug' => 'This status slug already exists.'])
                    ->withInput();
            }

            \App\Models\RepairBuddyPaymentStatus::query()->create([
                'tenant_id' => $tenantId,
                'label' => (string) $validated['payment_status_name'],
                'slug' => $slug,
                'email_template' => null,
                'is_active' => $isActive,
            ]);
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.')
            ->withInput();
    }

    public function updatePaymentMethods(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wc_rb_payment_method' => ['sometimes', 'array'],
            'wc_rb_payment_method.*' => ['string', 'max:64'],
        ]);

        $methods = [];
        if (array_key_exists('wc_rb_payment_method', $validated) && is_array($validated['wc_rb_payment_method'])) {
            foreach ($validated['wc_rb_payment_method'] as $m) {
                if (! is_string($m)) {
                    continue;
                }
                $m = trim($m);
                if ($m === '') {
                    continue;
                }
                $methods[] = $m;
            }
        }

        $methods = array_values(array_unique($methods));

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $repairBuddySettings['payment_methods_active'] = $methods;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment methods updated.')
            ->withInput();
    }

    public function updateGeneralSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'menu_name' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_name' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_phone' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_address' => ['nullable', 'string', 'max:255'],
            'computer_repair_logo' => ['nullable', 'string', 'max:2048'],
            'computer_repair_email' => ['nullable', 'string', 'max:255'],
            'case_number_prefix' => ['nullable', 'string', 'max:32'],
            'case_number_length' => ['nullable', 'integer', 'min:1', 'max:32'],
            'wc_job_status_cr_notice' => ['nullable', 'boolean'],
            'wcrb_attach_pdf_in_customer_emails' => ['nullable', 'boolean'],
            'wcrb_next_service_date' => ['nullable', 'boolean'],
            'wc_rb_gdpr_acceptance' => ['nullable', 'string', 'max:255'],
            'wc_rb_gdpr_acceptance_link_label' => ['nullable', 'string', 'max:255'],
            'wc_rb_gdpr_acceptance_link' => ['nullable', 'string', 'max:2048'],
            'wc_primary_country' => ['nullable', 'string', 'size:2'],
            'wc_enable_woo_products' => ['nullable', 'boolean'],
            'wcrb_disable_statuscheck_serial' => ['nullable', 'boolean'],
        ]);

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $general = $repairBuddySettings['general'] ?? [];
        if (! is_array($general)) {
            $general = [];
        }

        foreach ([
            'menu_name',
            'wc_rb_business_phone',
            'wc_rb_business_address',
            'computer_repair_logo',
            'computer_repair_email',
            'case_number_prefix',
            'case_number_length',
            'wc_rb_gdpr_acceptance',
            'wc_rb_gdpr_acceptance_link_label',
            'wc_rb_gdpr_acceptance_link',
            'wc_primary_country',
        ] as $k) {
            if (array_key_exists($k, $validated)) {
                $general[$k] = $validated[$k];
            }
        }

        foreach ([
            'wc_job_status_cr_notice',
            'wcrb_attach_pdf_in_customer_emails',
            'wcrb_next_service_date',
            'wc_enable_woo_products',
            'wcrb_disable_statuscheck_serial',
        ] as $k) {
            $general[$k] = (bool) ($validated[$k] ?? false);
        }

        $repairBuddySettings['general'] = $general;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        if (array_key_exists('wc_rb_business_name', $validated) && is_string($validated['wc_rb_business_name'])) {
            $tenant->forceFill([
                'name' => $validated['wc_rb_business_name'],
            ]);
        }
        $tenant->forceFill([
            'contact_phone' => array_key_exists('wc_rb_business_phone', $validated) ? ($validated['wc_rb_business_phone'] ?? null) : $tenant->contact_phone,
            'contact_email' => array_key_exists('computer_repair_email', $validated) ? ($validated['computer_repair_email'] ?? null) : $tenant->contact_email,
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel1')
            ->with('status', 'Settings updated.')
            ->withInput();
    }

    public function storeJobStatus(Request $request)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        if (! $tenant || ! $tenantId) {
            return back()
                ->withErrors(['status_name' => 'Tenant context is missing.'])
                ->withInput();
        }

        $validated = $request->validate([
            'status_name' => ['required', 'string', 'max:255'],
            'status_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'invoice_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_status' => ['sometimes', 'in:active,inactive'],
            'statusEmailMessage' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        $slugBase = Str::of((string) $validated['status_name'])
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        if ($slugBase === '') {
            return back()
                ->withErrors(['status_name' => 'Status name is invalid.'])
                ->withInput();
        }

        $slug = $slugBase;
        $suffix = 2;
        while (RepairBuddyJobStatus::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $slugBase.'_'.$suffix;
            $suffix++;
            if ($suffix > 200) {
                return back()
                    ->withErrors(['status_name' => 'Unable to generate a unique status slug.'])
                    ->withInput();
            }
        }

        $emailTemplate = array_key_exists('statusEmailMessage', $validated) ? $validated['statusEmailMessage'] : null;
        if (is_string($emailTemplate)) {
            $emailTemplate = trim($emailTemplate);
            if ($emailTemplate === '') {
                $emailTemplate = null;
            }
        }

        RepairBuddyJobStatus::query()->create([
            'slug' => $slug,
            'label' => (string) $validated['status_name'],
            'invoice_label' => array_key_exists('invoice_label', $validated) ? $validated['invoice_label'] : null,
            'is_active' => (string) ($validated['status_status'] ?? 'active') === 'active',
            'email_enabled' => $emailTemplate !== null,
            'email_template' => $emailTemplate,
            'sms_enabled' => false,
        ]);

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status created.')
            ->withInput();
    }

    public function updateCurrencySettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wc_cr_selected_currency' => ['nullable', 'string', 'max:8'],
            'wc_cr_currency_position' => ['nullable', 'string', 'max:32'],
            'wc_cr_thousand_separator' => ['nullable', 'string', 'max:8'],
            'wc_cr_decimal_separator' => ['nullable', 'string', 'max:8'],
            'wc_cr_number_of_decimals' => ['nullable', 'integer', 'min:0', 'max:8'],
        ]);

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $currency = $repairBuddySettings['currency'] ?? [];
        if (! is_array($currency)) {
            $currency = [];
        }

        foreach (array_keys($validated) as $k) {
            $currency[$k] = $validated[$k];
        }

        $repairBuddySettings['currency'] = $currency;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        if (array_key_exists('wc_cr_selected_currency', $validated) && is_string($validated['wc_cr_selected_currency']) && $validated['wc_cr_selected_currency'] !== '') {
            $tenant->forceFill([
                'currency' => $validated['wc_cr_selected_currency'],
            ]);
        }

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('currencyFormatting')
            ->with('status', 'Currency settings updated.')
            ->withInput();
    }

    public function updateInvoicesSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'wcrb_add_invoice_qr_code' => ['nullable', 'boolean'],
            'wc_rb_io_thanks_msg' => ['nullable', 'string', 'max:255'],
            'wb_rb_invoice_type' => ['nullable', 'in:default,by_device,by_items'],
            'pickupdate' => ['nullable', 'in:show'],
            'deliverydate' => ['nullable', 'in:show'],
            'nextservicedate' => ['nullable', 'in:show'],
            'repair_order_type' => ['nullable', 'in:pos_type,invoice_type'],
            'business_terms' => ['nullable', 'string', 'max:2048'],
            'wc_repair_order_print_size' => ['nullable', 'in:default,a4,a5'],
            'wc_rb_cr_display_add_on_ro' => ['nullable', 'boolean'],
            'wc_rb_cr_display_add_on_ro_cu' => ['nullable', 'boolean'],
            'wc_rb_ro_thanks_msg' => ['nullable', 'string', 'max:255'],
        ]);

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $invoices = $repairBuddySettings['invoices'] ?? [];
        if (! is_array($invoices)) {
            $invoices = [];
        }

        foreach (['wc_rb_io_thanks_msg', 'wb_rb_invoice_type', 'repair_order_type', 'business_terms', 'wc_repair_order_print_size', 'wc_rb_ro_thanks_msg'] as $k) {
            if (array_key_exists($k, $validated)) {
                $invoices[$k] = $validated[$k];
            }
        }

        $invoices['wcrb_add_invoice_qr_code'] = (bool) ($validated['wcrb_add_invoice_qr_code'] ?? false);
        $invoices['wc_rb_cr_display_add_on_ro'] = (bool) ($validated['wc_rb_cr_display_add_on_ro'] ?? false);
        $invoices['wc_rb_cr_display_add_on_ro_cu'] = (bool) ($validated['wc_rb_cr_display_add_on_ro_cu'] ?? false);

        $invoices['pickupdate'] = (string) ($validated['pickupdate'] ?? '') === 'show';
        $invoices['deliverydate'] = (string) ($validated['deliverydate'] ?? '') === 'show';
        $invoices['nextservicedate'] = (string) ($validated['nextservicedate'] ?? '') === 'show';

        $repairBuddySettings['invoices'] = $invoices;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('reportsAInvoices')
            ->with('status', 'Invoice settings updated.')
            ->withInput();
    }

    public function updateJobStatusSettings(Request $request)
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $branchId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $validated = $request->validate([
            'wcrb_job_status_delivered' => ['nullable', 'string', 'max:64'],
            'wcrb_job_status_cancelled' => ['nullable', 'string', 'max:64'],
        ]);

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $jobStatus = $repairBuddySettings['jobStatus'] ?? [];
        if (! is_array($jobStatus)) {
            $jobStatus = [];
        }

        foreach (['wcrb_job_status_delivered', 'wcrb_job_status_cancelled'] as $k) {
            if (array_key_exists($k, $validated)) {
                $jobStatus[$k] = $validated[$k];
            }
        }

        $repairBuddySettings['jobStatus'] = $jobStatus;
        $state['repairbuddy_settings'] = $repairBuddySettings;

        $tenant->forceFill([
            'setup_state' => $state,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status settings updated.')
            ->withInput();
    }

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
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
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
            ->where('domain', 'payment')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            \App\Models\TenantStatusOverride::query()->create([
                'tenant_id' => $tenantId,
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

    public function updateTimeLogSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'disable_timelog' => ['sometimes', 'nullable', 'in:on'],
            'default_tax_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'job_status_include' => ['sometimes', 'array'],
            'job_status_include.*' => ['string', 'max:64'],
            'activities' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $timeLog = $repairBuddySettings['timeLog'] ?? [];
        if (! is_array($timeLog)) {
            $timeLog = [];
        }

        $timeLog['disabled'] = array_key_exists('disable_timelog', $validated);
        if (array_key_exists('default_tax_id', $validated)) {
            $timeLog['defaultTaxId'] = is_int($validated['default_tax_id']) ? (string) $validated['default_tax_id'] : null;
        }
        if (array_key_exists('job_status_include', $validated)) {
            $timeLog['jobStatusInclude'] = array_values(array_filter($validated['job_status_include'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        }
        if (array_key_exists('activities', $validated)) {
            $timeLog['activities'] = is_string($validated['activities']) ? $validated['activities'] : '';
        }

        $repairBuddySettings['timeLog'] = $timeLog;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wcrb_timelog_tab')
            ->with('status', 'Time log settings updated.')
            ->withInput();
    }

    public function updateStylingSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'delivery_date_label' => ['nullable', 'string', 'max:255'],
            'pickup_date_label' => ['nullable', 'string', 'max:255'],
            'nextservice_date_label' => ['nullable', 'string', 'max:255'],
            'casenumber_label' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $styling = $repairBuddySettings['styling'] ?? [];
        if (! is_array($styling)) {
            $styling = [];
        }

        foreach (['delivery_date_label', 'pickup_date_label', 'nextservice_date_label', 'casenumber_label'] as $k) {
            if (array_key_exists($k, $validated)) {
                $styling[$k] = $validated[$k];
            }
        }

        if (array_key_exists('primary_color', $validated)) {
            $styling['primary_color'] = $validated['primary_color'];
        }
        if (array_key_exists('secondary_color', $validated)) {
            $styling['secondary_color'] = $validated['secondary_color'];
        }

        $repairBuddySettings['styling'] = $styling;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wcrb_styling')
            ->with('status', 'Styling updated.')
            ->withInput();
    }

    public function updateReviewSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'request_by_sms' => ['sometimes', 'nullable', 'in:on'],
            'request_by_email' => ['sometimes', 'nullable', 'in:on'],
            'get_feedback_page_url' => ['nullable', 'string', 'max:2048'],
            'send_request_job_status' => ['nullable', 'string', 'max:64'],
            'auto_request_interval' => ['nullable', 'in:disabled,one-notification,two-notifications'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_message' => ['nullable', 'string', 'max:5000'],
            'sms_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $reviews = $repairBuddySettings['reviews'] ?? [];
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

        $repairBuddySettings['reviews'] = $reviews;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wcrb_reviews_tab')
            ->with('status', 'Review settings updated.')
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
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $screen = is_string($request->query('screen')) && $request->query('screen') !== ''
            ? (string) $request->query('screen')
            : 'dashboard';

        if ($screen === 'settings') {
            if (is_int($tenantId) && $tenantId > 0) {
                $this->ensureDefaultRepairBuddyStatuses($tenantId);
            }

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

            $estimatesSettings = $repairBuddySettings['estimates'] ?? [];
            if (! is_array($estimatesSettings)) {
                $estimatesSettings = [];
            }

            $smsSettings = $repairBuddySettings['sms'] ?? [];
            if (! is_array($smsSettings)) {
                $smsSettings = [];
            }

            $accountSettings = $repairBuddySettings['account'] ?? [];
            if (! is_array($accountSettings)) {
                $accountSettings = [];
            }

            $signatureSettings = $repairBuddySettings['signature'] ?? [];
            if (! is_array($signatureSettings)) {
                $signatureSettings = [];
            }

            $bookingSettings = $repairBuddySettings['bookings'] ?? [];
            if (! is_array($bookingSettings)) {
                $bookingSettings = [];
            }

            $serviceSettings = $repairBuddySettings['services'] ?? [];
            if (! is_array($serviceSettings)) {
                $serviceSettings = [];
            }

            $timeLogSettings = $repairBuddySettings['timeLog'] ?? [];
            if (! is_array($timeLogSettings)) {
                $timeLogSettings = [];
            }

            $stylingSettings = $repairBuddySettings['styling'] ?? [];
            if (! is_array($stylingSettings)) {
                $stylingSettings = [];
            }

            $reviewsSettings = $repairBuddySettings['reviews'] ?? [];
            if (! is_array($reviewsSettings)) {
                $reviewsSettings = [];
            }

            $paymentStatuses = \App\Models\RepairBuddyPaymentStatus::query()->orderBy('id')->get();
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

            $estimatesEnabledUi = (bool) ($estimatesSettings['enabled'] ?? false);
            $estimatesValidDaysUi = is_int($estimatesSettings['validDays'] ?? null) ? (int) $estimatesSettings['validDays'] : 30;
            $oldEstimatesEnabled = old('estimates_enabled');
            if ($oldEstimatesEnabled !== null) {
                $estimatesEnabledUi = (string) $oldEstimatesEnabled === 'on';
            }
            $oldValidDays = old('estimate_valid_days');
            if ($oldValidDays !== null && is_numeric($oldValidDays)) {
                $estimatesValidDaysUi = (int) $oldValidDays;
            }

            $smsEnabledUi = (bool) ($smsSettings['enabled'] ?? false);
            $smsApiKeyUi = is_string($smsSettings['apiKey'] ?? null) ? (string) $smsSettings['apiKey'] : '';
            $smsSenderIdUi = is_string($smsSettings['senderId'] ?? null) ? (string) $smsSettings['senderId'] : '';
            $oldSmsEnabled = old('sms_enabled');
            if ($oldSmsEnabled !== null) {
                $smsEnabledUi = (string) $oldSmsEnabled === 'on';
            }
            $oldSmsApiKey = old('sms_api_key');
            if ($oldSmsApiKey !== null) {
                $smsApiKeyUi = is_string($oldSmsApiKey) ? $oldSmsApiKey : '';
            }
            $oldSmsSenderId = old('sms_sender_id');
            if ($oldSmsSenderId !== null) {
                $smsSenderIdUi = is_string($oldSmsSenderId) ? $oldSmsSenderId : '';
            }

            $customerRegistrationUi = (bool) ($accountSettings['customerRegistration'] ?? false);
            $accountApprovalRequiredUi = (bool) ($accountSettings['accountApprovalRequired'] ?? false);
            $defaultCustomerRoleUi = (string) ($accountSettings['defaultCustomerRole'] ?? 'customer');
            if (! in_array($defaultCustomerRoleUi, ['customer', 'vip_customer'], true)) {
                $defaultCustomerRoleUi = 'customer';
            }
            $oldCustomerRegistration = old('customer_registration');
            if ($oldCustomerRegistration !== null) {
                $customerRegistrationUi = (string) $oldCustomerRegistration === 'on';
            }
            $oldAccountApprovalRequired = old('account_approval_required');
            if ($oldAccountApprovalRequired !== null) {
                $accountApprovalRequiredUi = (string) $oldAccountApprovalRequired === 'on';
            }
            $oldDefaultCustomerRole = old('default_customer_role');
            if (is_string($oldDefaultCustomerRole) && in_array($oldDefaultCustomerRole, ['customer', 'vip_customer'], true)) {
                $defaultCustomerRoleUi = $oldDefaultCustomerRole;
            }

            $signatureRequiredUi = (bool) ($signatureSettings['required'] ?? false);
            $signatureTypeUi = (string) ($signatureSettings['type'] ?? 'draw');
            if (! in_array($signatureTypeUi, ['draw', 'type', 'upload'], true)) {
                $signatureTypeUi = 'draw';
            }
            $signatureTermsUi = is_string($signatureSettings['terms'] ?? null) ? (string) $signatureSettings['terms'] : '';
            $oldSignatureRequired = old('signature_required');
            if ($oldSignatureRequired !== null) {
                $signatureRequiredUi = (string) $oldSignatureRequired === 'on';
            }
            $oldSignatureType = old('signature_type');
            if (is_string($oldSignatureType) && in_array($oldSignatureType, ['draw', 'type', 'upload'], true)) {
                $signatureTypeUi = $oldSignatureType;
            }
            $oldSignatureTerms = old('signature_terms');
            if ($oldSignatureTerms !== null) {
                $signatureTermsUi = is_string($oldSignatureTerms) ? $oldSignatureTerms : '';
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
                ['id' => 'wc_rb_payment_status', 'label' => __('Payment Status'), 'heading' => __('Payment Status')],
                ['id' => 'wc_rb_page_sms_IDENTIFIER', 'label' => __('SMS'), 'heading' => __('SMS')],
                ['id' => 'wc_rb_manage_devices', 'label' => __('Devices & Brands'), 'heading' => __('Brands & Devices')],
                ['id' => 'wc_rb_manage_bookings', 'label' => __('Booking Settings'), 'heading' => __('Booking Settings')],
                ['id' => 'wc_rb_manage_service', 'label' => __('Service Settings'), 'heading' => __('Service Settings')],
                ['id' => 'wcrb_estimates_tab', 'label' => __('Estimates'), 'heading' => __('Estimates')],
                ['id' => 'wc_rb_manage_taxes', 'label' => __('Manage Taxes'), 'heading' => __('Tax Settings')],
                ['id' => 'wc_rb_maintenance_reminder', 'label' => __('Maintenance Reminders'), 'heading' => __('Maintenance Reminders')],
                ['id' => 'wcrb_timelog_tab', 'label' => __('Time Log Settings'), 'heading' => __('Time Log Settings')],
                ['id' => 'wcrb_styling', 'label' => __('Styling & Labels'), 'heading' => __('Styling & Labels')],
                ['id' => 'wcrb_reviews_tab', 'label' => __('Job Reviews'), 'heading' => __('Job Reviews')],
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

                    $settingsTabBodyHtml .= '<p class="help-text">';
                    $settingsTabBodyHtml .= '<a class="button button-primary button-small" data-open="paymentStatusFormReveal">' . e(__('Add New Payment Status')) . '</a>';
                    $settingsTabBodyHtml .= '</p>';

                    $settingsTabBodyHtml .= '<div id="payment_status_wrapper">';
                    $settingsTabBodyHtml .= '<table id="paymentStatus_poststuff" class="wp-list-table widefat fixed striped posts">';
                    $settingsTabBodyHtml .= '<thead><tr>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('ID')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Name')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Slug')) . '</th>';
                    $settingsTabBodyHtml .= '<th>' . e(__('Description')) . '</th>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('Status')) . '</th>';
                    $settingsTabBodyHtml .= '<th class="column-id">' . e(__('Actions')) . '</th>';
                    $settingsTabBodyHtml .= '</tr></thead><tbody>';

                    if ($paymentStatuses->count() === 0) {
                        $settingsTabBodyHtml .= '<tr><td colspan="6">' . e(__('No payment statuses found.')) . '</td></tr>';
                    } else {
                        foreach ($paymentStatuses as $ps) {
                            $editLink = $tenant?->slug
                                ? (route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings&update_payment_status=' . urlencode((string) $ps->id) . '#wc_rb_payment_status')
                                : '#';

                            $settingsTabBodyHtml .= '<tr>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $ps->id) . '</td>';
                            $settingsTabBodyHtml .= '<td><strong>' . e((string) $ps->label) . '</strong></td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) $ps->slug) . '</td>';
                            $settingsTabBodyHtml .= '<td>' . e((string) ($ps->description ?? '')) . '</td>';

                            $settingsTabBodyHtml .= '<td>';
                            $settingsTabBodyHtml .= '<a href="#" title="' . e(__('Change Status')) . '" class="change_tax_status" data-type="paymentStatus" data-value="' . e((string) $ps->id) . '">' . e($ps->is_active ? 'active' : 'inactive') . '</a>';
                            $settingsTabBodyHtml .= '</td>';

                            $settingsTabBodyHtml .= '<td>';
                            $settingsTabBodyHtml .= '<a href="' . e($editLink) . '" class="update_tax_status" data-type="status" data-value="' . e((string) $ps->id) . '">' . e(__('Edit')) . '</a>';
                            $settingsTabBodyHtml .= '</td>';
                            $settingsTabBodyHtml .= '</tr>';
                        }
                    }

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '</div>';

                    $updatePaymentStatusId = $request->query('update_payment_status');
                    $selectedPaymentStatus = null;
                    if (is_string($updatePaymentStatusId) && $updatePaymentStatusId !== '') {
                        $selectedPaymentStatus = $paymentStatuses->firstWhere('id', (int) $updatePaymentStatusId);
                    }

                    $modalLabel = $selectedPaymentStatus ? __('Update') : __('Add new');
                    $buttonLabel = $modalLabel;
                    $statusName = $selectedPaymentStatus?->label ?? '';
                    $statusSlug = $selectedPaymentStatus?->slug ?? '';
                    $statusDescription = $selectedPaymentStatus?->description ?? '';
                    $statusStatus = ($selectedPaymentStatus?->is_active ?? true) ? 'active' : 'inactive';

                    $settingsTabBodyHtml .= '<div class="small reveal" id="paymentStatusFormReveal" data-reveal>';
                    $settingsTabBodyHtml .= '<h2>' . e($modalLabel) . ' ' . e(__('Payment status')) . '</h2>';
                    $settingsTabBodyHtml .= '<div class="form-message"></div>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.payment_status.save', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<div class="cell"><div data-abide-error class="alert callout" style="display:none;"><p>' . e(__('There are some errors in your form.')) . '</p></div></div>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Status Name')) . '*';
                    $settingsTabBodyHtml .= '<input name="payment_status_name" type="text" class="form-control login-field" value="' . e(old('payment_status_name', $statusName)) . '" required id="payment_status_name" />';
                    $settingsTabBodyHtml .= '<span class="form-error">' . e(__('Name the status to recognize.')) . '</span>';
                    $settingsTabBodyHtml .= '</label></div>';

                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Status Slug')) . '*';
                    $settingsTabBodyHtml .= '<input name="payment_status_slug" type="text" class="form-control login-field" value="' . e(old('payment_status_slug', $statusSlug)) . '" required id="payment_status_slug" />';
                    $settingsTabBodyHtml .= '<span class="form-error">' . e(__('Slug is required to recognize the status make sure to not change it.')) . '</span>';
                    $settingsTabBodyHtml .= '</label></div>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Description'));
                    $settingsTabBodyHtml .= '<input name="payment_status_description" type="text" class="form-control login-field" value="' . e(old('payment_status_description', $statusDescription)) . '" id="payment_status_description" />';
                    $settingsTabBodyHtml .= '</label></div>';

                    $settingsTabBodyHtml .= '<div class="cell medium-6"><label>' . e(__('Status'));
                    $settingsTabBodyHtml .= '<select class="form-control form-select" name="payment_status_status">';
                    $settingsTabBodyHtml .= '<option value="active"' . ((string) old('payment_status_status', $statusStatus) === 'active' ? ' selected' : '') . '>' . e(__('Active')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="inactive"' . ((string) old('payment_status_status', $statusStatus) === 'inactive' ? ' selected' : '') . '>' . e(__('Inactive')) . '</option>';
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '</label></div>';
                    $settingsTabBodyHtml .= '</div>';

                    $settingsTabBodyHtml .= '<input name="form_type" type="hidden" value="payment_status_form" />';
                    if ($selectedPaymentStatus) {
                        $settingsTabBodyHtml .= '<input name="form_type_status_payment" type="hidden" value="update" />';
                        $settingsTabBodyHtml .= '<input name="status_id" type="hidden" value="' . e((string) $selectedPaymentStatus->id) . '" />';
                    } else {
                        $settingsTabBodyHtml .= '<input name="form_type_status_payment" type="hidden" value="add" />';
                    }

                    $settingsTabBodyHtml .= '<div class="grid-x grid-margin-x">';
                    $settingsTabBodyHtml .= '<fieldset class="cell medium-6">';
                    $settingsTabBodyHtml .= '<button class="button" type="submit">' . e($buttonLabel) . '</button>';
                    $settingsTabBodyHtml .= '</fieldset>';
                    $settingsTabBodyHtml .= '<small>(*) ' . e(__('fields are required')) . '</small>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '<button class="close-button" data-close aria-label="Close modal" type="button"><span aria-hidden="true">&times;</span></button>';
                    $settingsTabBodyHtml .= '</div>';

                    if ($selectedPaymentStatus) {
                        $settingsTabBodyHtml .= '<div id="updatePaymentStatus"></div>';
                    }

                    $settingsTabBodyHtml .= '<div class="wc-rb-payment-methods">';
                    $settingsTabBodyHtml .= '<h2>' . e(__('Payment Methods')) . '</h2>';
                    $settingsTabBodyHtml .= '<div class="methods_success_msg"></div>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.payment_methods.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<fieldset class="fieldset">';
                    $settingsTabBodyHtml .= '<legend>' . e(__('Select Payment Methods')) . '</legend>';

                    $defaultMethods = $repairBuddySettings['payment_methods_active'] ?? [];
                    if (! is_array($defaultMethods)) {
                        $defaultMethods = [];
                    }

                    $receiveArray = [
                        ['name' => 'cash', 'label' => __('Cash'), 'description' => ''],
                        ['name' => 'card', 'label' => __('Card'), 'description' => ''],
                        ['name' => 'bank', 'label' => __('Bank Transfer'), 'description' => ''],
                        ['name' => 'woocommerce', 'label' => __('WooCommerce'), 'description' => ''],
                    ];

                    foreach ($receiveArray as $m) {
                        $theName = (string) ($m['name'] ?? '');
                        $theLabel = (string) ($m['label'] ?? '');
                        $theDescription = (string) ($m['description'] ?? '');
                        if ($theName === '' || $theLabel === '') {
                            continue;
                        }
                        $checked = in_array($theName, $defaultMethods, true) ? ' checked' : '';
                        $settingsTabBodyHtml .= ($theDescription !== '') ? '<br>' : '';
                        $settingsTabBodyHtml .= '<label for="' . e($theName) . '"><input' . $checked . ' id="' . e($theName) . '" name="wc_rb_payment_method[]" value="' . e($theName) . '" type="checkbox">' . e($theLabel);
                        $settingsTabBodyHtml .= ($theDescription !== '') ? ' <small>' . e($theDescription) . '</small>' : '';
                        $settingsTabBodyHtml .= '</label>';
                    }

                    $settingsTabBodyHtml .= '</fieldset>';
                    $settingsTabBodyHtml .= '<input type="hidden" name="form_type" value="wc_rb_update_methods_ac" />';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary" data-type="rbsubmitmethods">' . e(__('Update Methods')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';

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
                } elseif ($tabId === 'wcrb_timelog_tab') {
                    $disabled = (bool) ($timeLogSettings['disabled'] ?? false);
                    $defaultTaxId = $timeLogSettings['defaultTaxId'] ?? null;
                    $includedStatuses = $timeLogSettings['jobStatusInclude'] ?? [];
                    if (! is_array($includedStatuses)) {
                        $includedStatuses = [];
                    }
                    $activities = is_string($timeLogSettings['activities'] ?? null)
                        ? (string) $timeLogSettings['activities']
                        : "Repair\nDiagnostic\nTesting\nCleaning\nConsultation\nOther";

                    $taxes = RepairBuddyTax::query()->orderBy('id')->limit(500)->get();
                    $jobStatuses = \App\Models\RepairBuddyJobStatus::query()->orderBy('id')->limit(500)->get();

                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wcrb_timelog_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_timelog_tab-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e(route('tenant.settings.time_log.update', ['business' => $tenant->slug])) . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $checkedDisabled = (old('disable_timelog') !== null)
                        ? (old('disable_timelog') === 'on')
                        : $disabled;
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="disable_timelog">' . e(__('Disable Time Log Completely')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($checkedDisabled ? 'checked="checked"' : '') . ' name="disable_timelog" id="disable_timelog" />';
                    $settingsTabBodyHtml .= ' <label for="disable_timelog">' . e(__('Disable Time Log Completely')) . '</label></td></tr>';

                    $selectedTax = (string) old('default_tax_id', $defaultTaxId);
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="default_tax_id">' . e(__('Default tax for hours')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="default_tax_id" id="default_tax_id" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select tax')) . '</option>';
                    foreach ($taxes as $tax) {
                        $sel = $selectedTax !== '' && $selectedTax === (string) $tax->id ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $tax->id) . '"' . $sel . '>' . e((string) $tax->name) . ' (' . e((string) $tax->rate) . '%)</option>';
                    }
                    $settingsTabBodyHtml .= '</select></td></tr>';

                    $included = old('job_status_include', $includedStatuses);
                    if (! is_array($included)) {
                        $included = [];
                    }
                    $settingsTabBodyHtml .= '<tr><th scope="row">' . e(__('Enable time log')) . '</th><td>';
                    $settingsTabBodyHtml .= '<fieldset class="fieldset"><legend>' . e(__('Select job status to include')) . '</legend>';
                    foreach ($jobStatuses as $st) {
                        $isChecked = in_array((string) $st->slug, array_map('strval', $included), true);
                        $settingsTabBodyHtml .= '<label style="display:block" for="job_status_' . e((string) $st->slug) . '">';
                        $settingsTabBodyHtml .= '<input type="checkbox" id="job_status_' . e((string) $st->slug) . '" name="job_status_include[]" value="' . e((string) $st->slug) . '" ' . ($isChecked ? 'checked="checked"' : '') . '> ' . e((string) $st->label);
                        $settingsTabBodyHtml .= '</label>';
                    }
                    $settingsTabBodyHtml .= '<p>' . e(__('To make time log work make sure to create correct my account page in page settings.')) . '</p>';
                    $settingsTabBodyHtml .= '</fieldset>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="activities">' . e(__('Time Log Activities')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<fieldset class="fieldset"><legend>' . e(__('Define activities for time log')) . '</legend>';
                    $settingsTabBodyHtml .= '<textarea name="activities" id="activities" rows="5" cols="50" class="large-text code">' . e(old('activities', $activities)) . '</textarea>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Define activities for time log, one per line.')) . '</p>';
                    $settingsTabBodyHtml .= '</fieldset>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wcrb_styling') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wcrb_styling" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_styling-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e(route('tenant.settings.styling.update', ['business' => $tenant->slug])) . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $deliveryLabel = (string) ($stylingSettings['delivery_date_label'] ?? __('Delivery Date'));
                    $pickupLabel = (string) ($stylingSettings['pickup_date_label'] ?? __('Pickup Date'));
                    $nextServiceLabel = (string) ($stylingSettings['nextservice_date_label'] ?? __('Next Service Date'));
                    $caseNumberLabel = (string) ($stylingSettings['casenumber_label'] ?? __('Case Number'));
                    $primaryColor = (string) ($stylingSettings['primary_color'] ?? '#063e70');
                    $secondaryColor = (string) ($stylingSettings['secondary_color'] ?? '#fd6742');

                    $settingsTabBodyHtml .= '<h2>' . e(__('Labels')) . '</h2>';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><td><label for="delivery_date_label">' . e(__('Delivery Date label')) . '<input type="text" id="delivery_date_label" class="form-control" name="delivery_date_label" value="' . e(old('delivery_date_label', $deliveryLabel)) . '" /></label></td>';
                    $settingsTabBodyHtml .= '<td><label for="pickup_date_label">' . e(__('Pickup Date label')) . '<input type="text" id="pickup_date_label" class="form-control" name="pickup_date_label" value="' . e(old('pickup_date_label', $pickupLabel)) . '" /></label></td></tr>';
                    $settingsTabBodyHtml .= '<tr><td><label for="nextservice_date_label">' . e(__('Next Service Date label')) . '<input type="text" id="nextservice_date_label" class="form-control" name="nextservice_date_label" value="' . e(old('nextservice_date_label', $nextServiceLabel)) . '" /></label></td>';
                    $settingsTabBodyHtml .= '<td><label for="casenumber_label">' . e(__('Case Number label')) . '<input type="text" id="casenumber_label" class="form-control" name="casenumber_label" value="' . e(old('casenumber_label', $caseNumberLabel)) . '" /></label></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<h2>' . e(__('Styling')) . '</h2>';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="primary_color">' . e(__('Primary Color')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="color" id="primary_color" class="form-control" name="primary_color" value="' . e(old('primary_color', $primaryColor)) . '" />';
                    $settingsTabBodyHtml .= '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="secondary_color">' . e(__('Secondary Color')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="color" id="secondary_color" class="form-control" name="secondary_color" value="' . e(old('secondary_color', $secondaryColor)) . '" />';
                    $settingsTabBodyHtml .= '</td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';

                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';

                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wcrb_reviews_tab') {
                    $requestBySms = (bool) ($reviewsSettings['requestBySms'] ?? false);
                    $requestByEmail = (bool) ($reviewsSettings['requestByEmail'] ?? false);
                    $feedbackPageUrl = (string) ($reviewsSettings['get_feedback_page_url'] ?? '');
                    $sendOnStatus = (string) ($reviewsSettings['send_request_job_status'] ?? '');
                    $interval = (string) ($reviewsSettings['auto_request_interval'] ?? 'disabled');
                    if (! in_array($interval, ['disabled', 'one-notification', 'two-notifications'], true)) {
                        $interval = 'disabled';
                    }
                    $emailSubject = (string) ($reviewsSettings['email_subject'] ?? __('How would you rate the service you received?'));
                    $emailMessage = (string) ($reviewsSettings['email_message'] ?? '');
                    $smsMessage = (string) ($reviewsSettings['sms_message'] ?? '');

                    $jobStatuses = \App\Models\RepairBuddyJobStatus::query()->orderBy('id')->limit(500)->get();

                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wcrb_reviews_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_reviews_tab-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';

                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e(route('tenant.settings.reviews.update', ['business' => $tenant->slug])) . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';

                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';

                    $checkedSms = (old('request_by_sms') !== null) ? (old('request_by_sms') === 'on') : $requestBySms;
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="request_by_sms">' . e(__('Request Feedback by SMS')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="checkbox" ' . ($checkedSms ? 'checked="checked"' : '') . ' name="request_by_sms" id="request_by_sms" /> ';
                    $settingsTabBodyHtml .= '<label for="request_by_sms">' . e(__('Enable SMS notification for feedback request')) . '</label></td></tr>';

                    $checkedEmail = (old('request_by_email') !== null) ? (old('request_by_email') === 'on') : $requestByEmail;
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="request_by_email">' . e(__('Request Feedback by Email')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="checkbox" ' . ($checkedEmail ? 'checked="checked"' : '') . ' name="request_by_email" id="request_by_email" /> ';
                    $settingsTabBodyHtml .= '<label for="request_by_email">' . e(__('Enable Email notification for feedback request')) . '</label></td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="get_feedback_page_url">' . e(__('Get feedback on job page URL')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="text" id="get_feedback_page_url" name="get_feedback_page_url" class="regular-text" value="' . e(old('get_feedback_page_url', $feedbackPageUrl)) . '" />';
                    $settingsTabBodyHtml .= '<label>' . e(__('A page that contains the review form. This will be used to send link to customers so they can leave feedback on jobs.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $selectedStatus = (string) old('send_request_job_status', $sendOnStatus);
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="send_request_job_status">' . e(__('Send review request if job status is')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="send_request_job_status" class="form-control" id="send_request_job_status">';
                    $settingsTabBodyHtml .= '<option value="">' . e(__('Select job status to send review request')) . '</option>';
                    foreach ($jobStatuses as $st) {
                        $sel = $selectedStatus !== '' && $selectedStatus === (string) $st->slug ? ' selected' : '';
                        $settingsTabBodyHtml .= '<option value="' . e((string) $st->slug) . '"' . $sel . '>' . e((string) $st->label) . '</option>';
                    }
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('When job has the status you selected above only then you can auto or manually request feedback.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $selectedInterval = (string) old('auto_request_interval', $interval);
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="auto_request_interval">' . e(__('Auto feedback request')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<select name="auto_request_interval" class="form-control" id="auto_request_interval">';
                    $settingsTabBodyHtml .= '<option value="disabled"' . ($selectedInterval === 'disabled' ? ' selected' : '') . '>' . e(__('Disabled')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="one-notification"' . ($selectedInterval === 'one-notification' ? ' selected' : '') . '>' . e(__('1 Notification - After 24 Hours')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="two-notifications"' . ($selectedInterval === 'two-notifications' ? ' selected' : '') . '>' . e(__('2 Notifications - After 24 Hrs and 48 Hrs')) . '</option>';
                    $settingsTabBodyHtml .= '</select>';
                    $settingsTabBodyHtml .= '<label>' . e(__('A request for customer feedback will be sent automatically.')) . '</label>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="email_message">' . e(__('Email message to request feedback')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<p class="description">' . e(__('Available keywords')) . ': {{st_feedback_anch}} {{end_feedback_anch}} {{feedback_link}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}}</p>';
                    $settingsTabBodyHtml .= '<textarea rows="6" name="email_message" id="email_message" class="large-text">' . e(old('email_message', $emailMessage)) . '</textarea>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="email_subject">' . e(__('Email subject to request feedback')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<input type="text" class="regular-text" name="email_subject" value="' . e(old('email_subject', $emailSubject)) . '" id="email_subject" />';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="sms_message">' . e(__('SMS message to request feedback')) . '</label></th><td>';
                    $settingsTabBodyHtml .= '<p class="description">' . e(__('Available keywords')) . ': {{feedback_link}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}}</p>';
                    $settingsTabBodyHtml .= '<textarea rows="3" name="sms_message" id="sms_message" class="large-text">' . e(old('sms_message', $smsMessage)) . '</textarea>';
                    $settingsTabBodyHtml .= '</td></tr>';

                    $settingsTabBodyHtml .= '<tr><td colspan="2">' . e(__('You should have set a review page with correct shortcode.')) . '</td></tr>';

                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
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
                } elseif ($tabId === 'wcrb_estimates_tab') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wcrb_estimates_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_estimates_tab-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Estimates settings allow you to configure how estimates and quotes are managed in your repair shop.')) . '</p>';
                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Estimate Settings')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.estimates.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="estimates_enabled">' . e(__('Enable Estimates')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($estimatesEnabledUi ? 'checked="checked"' : '') . ' name="estimates_enabled" id="estimates_enabled" /> ' . e(__('Allow customers to request estimates')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="estimate_valid_days">' . e(__('Estimate Validity (Days)')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="number" name="estimate_valid_days" id="estimate_valid_days" class="regular-text" value="' . e((string) $estimatesValidDaysUi) . '" min="1" /> ' . e(__('Days estimate is valid')) . '</td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_page_sms_IDENTIFIER') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_page_sms_IDENTIFIER" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_page_sms_IDENTIFIER-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Configure SMS notifications for your repair shop. You will need to configure an SMS gateway service.')) . '</p>';
                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('SMS Settings')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.sms.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="sms_enabled">' . e(__('Enable SMS')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($smsEnabledUi ? 'checked="checked"' : '') . ' name="sms_enabled" id="sms_enabled" /> ' . e(__('Enable SMS notifications')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="sms_api_key">' . e(__('SMS API Key')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="text" name="sms_api_key" id="sms_api_key" class="regular-text" value="' . e($smsApiKeyUi) . '" /> ' . e(__('Your SMS provider API key')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="sms_sender_id">' . e(__('Sender ID')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="text" name="sms_sender_id" id="sms_sender_id" class="regular-text" value="' . e($smsSenderIdUi) . '" /> ' . e(__('Sender name or number')) . '</td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wc_rb_manage_account') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wc_rb_manage_account" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_account-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Configure customer account settings and portal access.')) . '</p>';
                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Account Settings')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.account.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="customer_registration">' . e(__('Customer Registration')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($customerRegistrationUi ? 'checked="checked"' : '') . ' name="customer_registration" id="customer_registration" /> ' . e(__('Allow customers to register accounts')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="account_approval_required">' . e(__('Account Approval Required')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($accountApprovalRequiredUi ? 'checked="checked"' : '') . ' name="account_approval_required" id="account_approval_required" /> ' . e(__('Require admin approval for new accounts')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="default_customer_role">' . e(__('Default Customer Role')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><select name="default_customer_role" id="default_customer_role" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="customer"' . ($defaultCustomerRoleUi === 'customer' ? ' selected' : '') . '>' . e(__('Customer')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="vip_customer"' . ($defaultCustomerRoleUi === 'vip_customer' ? ' selected' : '') . '>' . e(__('VIP Customer')) . '</option>';
                    $settingsTabBodyHtml .= '</select></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                    $settingsTabBodyHtml .= '</div>';
                } elseif ($tabId === 'wcrb_signature_workflow') {
                    $settingsTabBodyHtml .= '<div class="tabs-panel team-wrap" id="wcrb_signature_workflow" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_signature_workflow-label">';
                    $settingsTabBodyHtml .= '<div class="wrap">';
                    $settingsTabBodyHtml .= '<h2>' . e($heading) . '</h2>';
                    $settingsTabBodyHtml .= '<p>' . e(__('Configure digital signature workflow for repair orders and customer approvals.')) . '</p>';
                    $settingsTabBodyHtml .= '<div class="wc-rb-grey-bg-box">';
                    $settingsTabBodyHtml .= '<h3>' . e(__('Signature Settings')) . '</h3>';
                    $settingsTabBodyHtml .= '<form data-abide class="needs-validation" novalidate method="post" action="' . e($tenant?->slug ? route('tenant.settings.signature.update', ['business' => $tenant->slug]) : '#') . '">';
                    $settingsTabBodyHtml .= '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
                    $settingsTabBodyHtml .= '<table class="form-table border"><tbody>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="signature_required">' . e(__('Require Signature')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><input type="checkbox" ' . ($signatureRequiredUi ? 'checked="checked"' : '') . ' name="signature_required" id="signature_required" /> ' . e(__('Require customer signature on repair orders')) . '</td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="signature_type">' . e(__('Signature Type')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><select name="signature_type" id="signature_type" class="form-control">';
                    $settingsTabBodyHtml .= '<option value="draw"' . ($signatureTypeUi === 'draw' ? ' selected' : '') . '>' . e(__('Draw Signature')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="type"' . ($signatureTypeUi === 'type' ? ' selected' : '') . '>' . e(__('Type Signature')) . '</option>';
                    $settingsTabBodyHtml .= '<option value="upload"' . ($signatureTypeUi === 'upload' ? ' selected' : '') . '>' . e(__('Upload Signature')) . '</option>';
                    $settingsTabBodyHtml .= '</select></td></tr>';
                    $settingsTabBodyHtml .= '<tr><th scope="row"><label for="signature_terms">' . e(__('Signature Terms')) . '</label></th>';
                    $settingsTabBodyHtml .= '<td><textarea name="signature_terms" id="signature_terms" rows="4" class="large-text" placeholder="' . e(__('I agree to the terms and conditions of the repair service.')) . '">' . e($signatureTermsUi) . '</textarea></td></tr>';
                    $settingsTabBodyHtml .= '</tbody></table>';
                    $settingsTabBodyHtml .= '<button type="submit" class="button button-primary">' . e(__('Update Options')) . '</button>';
                    $settingsTabBodyHtml .= '</form>';
                    $settingsTabBodyHtml .= '</div>';
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

            $generalSettings = $repairBuddySettings['general'] ?? [];
            if (! is_array($generalSettings)) {
                $generalSettings = [];
            }

            $currencySettings = $repairBuddySettings['currency'] ?? [];
            if (! is_array($currencySettings)) {
                $currencySettings = [];
            }

            $invoiceSettings = $repairBuddySettings['invoices'] ?? [];
            if (! is_array($invoiceSettings)) {
                $invoiceSettings = [];
            }

            $jobStatusSettings = $repairBuddySettings['jobStatus'] ?? [];
            if (! is_array($jobStatusSettings)) {
                $jobStatusSettings = [];
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

            $tenantCurrency = is_string($tenant?->currency) ? (string) $tenant?->currency : '';
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

            $jobStatusOptions = RepairBuddyJobStatus::query()
                ->orderBy('label')
                ->limit(200)
                ->get()
                ->mapWithKeys(fn ($s) => [(string) $s->slug => (string) $s->label])
                ->all();

            $jobStatuses = RepairBuddyJobStatus::query()
                ->orderBy('id')
                ->limit(500)
                ->get();

            $tenantIdForDashboard = TenantContext::tenantId();
            $branchIdForDashboard = BranchContext::branchId();

            $jobStatusCounts = [];
            if ($tenantIdForDashboard && $branchIdForDashboard) {
                $jobStatusCounts = RepairBuddyJob::query()
                    ->where('tenant_id', $tenantIdForDashboard)
                    ->where('branch_id', $branchIdForDashboard)
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
            if ($tenantIdForDashboard && $branchIdForDashboard) {
                $rawEstimateCounts = RepairBuddyEstimate::query()
                    ->where('tenant_id', $tenantIdForDashboard)
                    ->where('branch_id', $branchIdForDashboard)
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
                    'user' => $user,
                    'jobStatusOptions' => $jobStatusOptions,
                    'jobStatusCounts' => $jobStatusCounts,
                    'estimateCounts' => $estimateCounts,
                    'dashboardBaseUrl' => $tenant?->slug ? route('tenant.dashboard', ['business' => $tenant->slug]) : '#',
                ])->render();
            } catch (\Throwable $e) {
                $dashoutputHtml = '';
            }

            return view('tenant.settings.index', [
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
                'menu_name_p' => (string) ($generalSettings['menu_name'] ?? ''),
                'wc_rb_business_name' => $tenant?->name ?? '',
                'wc_rb_business_phone' => (string) ($generalSettings['wc_rb_business_phone'] ?? ($tenant?->contact_phone ?? '')),
                'wc_rb_business_address' => (string) ($generalSettings['wc_rb_business_address'] ?? ''),
                'computer_repair_logo' => (string) ($generalSettings['computer_repair_logo'] ?? (is_string($tenant?->logo_url) ? $tenant?->logo_url : '')),
                'computer_repair_email' => (string) ($generalSettings['computer_repair_email'] ?? ($tenant?->contact_email ?? '')),
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
                'wc_cr_selected_currency' => (string) ($currencySettings['wc_cr_selected_currency'] ?? ($tenant?->currency ?? '')),
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
                'settings_tab_menu_items_html' => $settingsTabMenuItemsHtml,
                'settings_tab_body_html' => $settingsTabBodyHtml,
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

    public function updateEstimatesSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'estimates_enabled' => ['nullable', 'in:on'],
            'estimate_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $estimates = $repairBuddySettings['estimates'] ?? [];
        if (! is_array($estimates)) {
            $estimates = [];
        }

        $estimates['enabled'] = array_key_exists('estimates_enabled', $validated);
        $estimates['validDays'] = array_key_exists('estimate_valid_days', $validated) ? (int) $validated['estimate_valid_days'] : 30;

        $repairBuddySettings['estimates'] = $estimates;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wcrb_estimates_tab')
            ->with('status', 'Estimates settings updated.')
            ->withInput();
    }

    public function updateSmsSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'sms_enabled' => ['nullable', 'in:on'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_sender_id' => ['nullable', 'string', 'max:255'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $sms = $repairBuddySettings['sms'] ?? [];
        if (! is_array($sms)) {
            $sms = [];
        }

        $sms['enabled'] = array_key_exists('sms_enabled', $validated);
        $sms['apiKey'] = $validated['sms_api_key'] ?? null;
        $sms['senderId'] = $validated['sms_sender_id'] ?? null;

        $repairBuddySettings['sms'] = $sms;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_page_sms_IDENTIFIER')
            ->with('status', 'SMS settings updated.')
            ->withInput();
    }

    public function updateAccountSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'customer_registration' => ['nullable', 'in:on'],
            'account_approval_required' => ['nullable', 'in:on'],
            'default_customer_role' => ['nullable', 'in:customer,vip_customer'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $account = $repairBuddySettings['account'] ?? [];
        if (! is_array($account)) {
            $account = [];
        }

        $account['customerRegistration'] = array_key_exists('customer_registration', $validated);
        $account['accountApprovalRequired'] = array_key_exists('account_approval_required', $validated);
        $account['defaultCustomerRole'] = $validated['default_customer_role'] ?? 'customer';

        $repairBuddySettings['account'] = $account;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_manage_account')
            ->with('status', 'Account settings updated.')
            ->withInput();
    }

    public function updateSignatureSettings(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'signature_required' => ['nullable', 'in:on'],
            'signature_type' => ['nullable', 'in:draw,type,upload'],
            'signature_terms' => ['nullable', 'string', 'max:1000'],
        ]);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $signature = $repairBuddySettings['signature'] ?? [];
        if (! is_array($signature)) {
            $signature = [];
        }

        $signature['required'] = array_key_exists('signature_required', $validated);
        $signature['type'] = $validated['signature_type'] ?? 'draw';
        $signature['terms'] = $validated['signature_terms'] ?? null;

        $repairBuddySettings['signature'] = $signature;
        $setupState['repairbuddy_settings'] = $repairBuddySettings;
        $tenant->forceFill(['setup_state' => $setupState])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wcrb_signature_workflow')
            ->with('status', 'Signature settings updated.')
            ->withInput();
    }
}

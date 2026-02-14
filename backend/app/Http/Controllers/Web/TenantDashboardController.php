<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\Status;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Models\TenantPlan;
use App\Models\TenantSubscription;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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

            if (Schema::hasTable('statuses')) {
                $hasStatusCode = Schema::hasColumn('statuses', 'code');
                $hasStatusDescription = Schema::hasColumn('statuses', 'description');
                $hasStatusInvoiceLabel = Schema::hasColumn('statuses', 'invoice_label');

                foreach ($paymentDefaults as $s) {
                    DB::table('statuses')->updateOrInsert([
                        'tenant_id' => $tenantId,
                        'status_type' => 'Payment',
                        'label' => $s['label'],
                    ], [
                        'email_enabled' => false,
                        'email_template' => null,
                        'sms_enabled' => false,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }

                if ($hasStatusCode) {
                    foreach ($jobDefaults as $s) {
                        $update = [
                            'label' => $s['label'],
                            'email_enabled' => false,
                            'email_template' => null,
                            'sms_enabled' => false,
                            'is_active' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ];

                        if ($hasStatusDescription) {
                            $update['description'] = null;
                        }
                        if ($hasStatusInvoiceLabel) {
                            $update['invoice_label'] = $s['invoice_label'];
                        }

                        DB::table('statuses')->updateOrInsert([
                            'tenant_id' => $tenantId,
                            'status_type' => 'Job',
                            'code' => $s['slug'],
                        ], $update);
                    }
                }
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
            'payment_status_status' => ['sometimes', 'in:active,inactive'],
            'form_type_status_payment' => ['sometimes', 'in:add,update'],
            'status_id' => ['sometimes', 'integer', 'min:1'],
        ]);

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

            $existing = \App\Models\Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->whereKey($id)
                ->first();

            if (! $existing) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['status_id' => 'Payment status not found.'])
                    ->withInput();
            }

            $labelExists = \App\Models\Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->where('label', (string) $validated['payment_status_name'])
                ->whereKeyNot($existing->id)
                ->exists();

            if ($labelExists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_name' => 'This status already exists.'])
                    ->withInput();
            }

            $existing->forceFill([
                'label' => (string) $validated['payment_status_name'],
                'is_active' => $isActive,
            ])->save();
        } else {
            $labelExists = \App\Models\Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->where('label', (string) $validated['payment_status_name'])
                ->exists();

            if ($labelExists) {
                return redirect()
                    ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_name' => 'This status already exists.'])
                    ->withInput();
            }

            \App\Models\Status::query()->create([
                'tenant_id' => $tenantId,
                'status_type' => 'Payment',
                'label' => (string) $validated['payment_status_name'],
                'email_enabled' => false,
                'email_template' => null,
                'sms_enabled' => false,
                'is_active' => $isActive,
            ]);
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.')
            ->withInput();
    }

    public function togglePaymentStatusActive(Request $request, string $status)
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $statusId = ctype_digit($status) ? (int) $status : 0;
        if ($statusId <= 0) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_payment_status')
                ->with('status', 'Payment status not found.');
        }

        $existing = \App\Models\Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Payment')
            ->whereKey($statusId)
            ->first();

        if (! $existing) {
            return redirect()
                ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
                ->withFragment('wc_rb_payment_status')
                ->with('status', 'Payment status not found.');
        }

        $existing->forceFill([
            'is_active' => ! (bool) $existing->is_active,
        ])->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.');
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

        $code = $slugBase;
        $suffix = 2;
        while (Status::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('code', $code)
            ->exists()) {
            $code = $slugBase.'_'.$suffix;
            $suffix++;
            if ($suffix > 200) {
                return back()
                    ->withErrors(['status_name' => 'Unable to generate a unique status code.'])
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

        $label = trim((string) $validated['status_name']);

        $labelExists = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('label', $label)
            ->exists();

        if ($labelExists) {
            return back()
                ->withErrors(['status_name' => 'This status already exists.'])
                ->withInput();
        }

        $isActive = (string) ($validated['status_status'] ?? 'active') === 'active';
        $invoiceLabel = array_key_exists('invoice_label', $validated) ? $validated['invoice_label'] : null;
        $description = array_key_exists('status_description', $validated) ? $validated['status_description'] : null;

        DB::transaction(function () use ($tenantId, $code, $label, $description, $invoiceLabel, $isActive, $emailTemplate) {
            Status::query()->create([
                'tenant_id' => $tenantId,
                'status_type' => 'Job',
                'code' => $code,
                'label' => $label,
                'description' => $description,
                'invoice_label' => $invoiceLabel,
                'is_active' => $isActive,
                'email_enabled' => $emailTemplate !== null,
                'email_template' => $emailTemplate,
                'sms_enabled' => false,
            ]);

            if (Schema::hasTable('rb_job_statuses')) {
                DB::table('rb_job_statuses')->updateOrInsert([
                    'tenant_id' => $tenantId,
                    'slug' => $code,
                ], [
                    'label' => $label,
                    'email_enabled' => $emailTemplate !== null,
                    'email_template' => $emailTemplate,
                    'sms_enabled' => false,
                    'invoice_label' => $invoiceLabel,
                    'is_active' => $isActive,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status created.');
    }

    public function updateJobStatus(Request $request, $status)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        $statusId = (int) $status;
        if ($statusId <= 0) {
            return back()
                ->withErrors(['status_name' => 'Job status id is missing.'])
                ->withInput();
        }

        if (! $tenant || ! $tenantId) {
            return back()
                ->withErrors(['status_name' => 'Tenant context is missing.'])
                ->withInput();
        }

        $jobStatus = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->whereKey($statusId)
            ->first();

        if (! $jobStatus) {
            return back()
                ->withErrors(['status_name' => 'Job status not found.'])
                ->withInput();
        }

        $validated = $request->validate([
            'status_name' => ['required', 'string', 'max:255'],
            'status_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'invoice_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_status' => ['sometimes', 'in:active,inactive'],
            'statusEmailMessage' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        $emailTemplate = array_key_exists('statusEmailMessage', $validated) ? $validated['statusEmailMessage'] : null;
        if (is_string($emailTemplate)) {
            $emailTemplate = trim($emailTemplate);
            if ($emailTemplate === '') {
                $emailTemplate = null;
            }
        }

        $label = trim((string) $validated['status_name']);
        $description = array_key_exists('status_description', $validated) ? $validated['status_description'] : null;
        $invoiceLabel = array_key_exists('invoice_label', $validated) ? $validated['invoice_label'] : null;
        $isActive = (string) ($validated['status_status'] ?? ($jobStatus->is_active ? 'active' : 'inactive')) === 'active';

        $code = is_string($jobStatus->code) ? trim((string) $jobStatus->code) : '';
        if ($code === '') {
            return back()
                ->withErrors(['status_name' => 'Status code is missing.'])
                ->withInput();
        }

        $labelExists = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('label', $label)
            ->whereKeyNot($jobStatus->id)
            ->exists();

        if ($labelExists) {
            return back()
                ->withErrors(['status_name' => 'This status already exists.'])
                ->withInput();
        }

        DB::transaction(function () use ($jobStatus, $tenantId, $code, $label, $description, $invoiceLabel, $isActive, $emailTemplate) {
            $jobStatus->forceFill([
                'label' => $label,
                'description' => $description,
                'invoice_label' => $invoiceLabel,
                'is_active' => $isActive,
                'email_enabled' => $emailTemplate !== null,
                'email_template' => $emailTemplate,
            ])->save();

            if (Schema::hasTable('rb_job_statuses')) {
                DB::table('rb_job_statuses')
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $code)
                    ->update([
                        'label' => $label,
                        'email_enabled' => $emailTemplate !== null,
                        'email_template' => $emailTemplate,
                        'invoice_label' => $invoiceLabel,
                        'is_active' => $isActive,
                        'updated_at' => now(),
                    ]);
            }
        });

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status updated.');
    }

    public function deleteJobStatus(Request $request, $status)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        $statusId = (int) $status;
        if ($statusId <= 0) {
            return back()->withErrors(['status_name' => 'Job status id is missing.']);
        }

        if (! $tenant || ! $tenantId) {
            return back()
                ->withErrors(['status_name' => 'Tenant context is missing.']);
        }

        $jobStatus = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->whereKey($statusId)
            ->first();

        if (! $jobStatus) {
            return back()
                ->withErrors(['status_name' => 'Job status not found.']);
        }

        $code = is_string($jobStatus->code) ? trim((string) $jobStatus->code) : '';
        if ($code === '') {
            return back()
                ->withErrors(['status_name' => 'Status code is missing.']);
        }

        $inUseByJobs = RepairBuddyJob::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_slug', $code)
            ->exists();

        if ($inUseByJobs) {
            return back()
                ->withErrors(['status_name' => 'Cannot delete a status that is used by existing jobs.']);
        }

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }
        $jobStatusSettings = $repairBuddySettings['jobStatus'] ?? [];
        if (! is_array($jobStatusSettings)) {
            $jobStatusSettings = [];
        }

        $blockedBySettings = in_array($code, [
            (string) ($jobStatusSettings['wcrb_job_status_delivered'] ?? ''),
            (string) ($jobStatusSettings['wcrb_job_status_cancelled'] ?? ''),
        ], true);

        if ($blockedBySettings) {
            return back()
                ->withErrors(['status_name' => 'Cannot delete a status that is selected in status settings.']);
        }

        DB::transaction(function () use ($jobStatus, $tenantId, $code) {
            $jobStatus->delete();

            if (Schema::hasTable('rb_job_statuses')) {
                DB::table('rb_job_statuses')
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $code)
                    ->delete();
            }
        });

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=settings')
            ->withFragment('panel3')
            ->with('status', 'Job status deleted.');
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
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $wantsJson = $request->expectsJson() || $request->ajax();
        $rules = [
            'wcrb_job_status_delivered' => ['nullable', 'string', 'max:64'],
            'wcrb_job_status_cancelled' => ['nullable', 'string', 'max:64'],
        ];

        if ($wantsJson) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules);
        }

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

        if ($wantsJson) {
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
            ->withFragment('panel1')
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

        if ($screen === 'profile') {
            if ($tenant instanceof Tenant) {
                return redirect()->route('tenant.profile.edit', ['business' => $tenant->slug]);
            }

            abort(400, 'Tenant is missing.');
        }

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

            $extraTabs = [];

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

            $paymentStatuses = \App\Models\Status::query()
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

            $menuNameForBookings = $tenant?->name ?: 'RepairBuddy';

            $bookingEmailSubjectCustomerUi = (string) ($bookingSettings['booking_email_subject_to_customer'] ?? ('We have received your booking order! | ' . $menuNameForBookings));
            $bookingEmailBodyCustomerUi = (string) ($bookingSettings['booking_email_body_to_customer'] ?? '');
            $bookingEmailSubjectAdminUi = (string) ($bookingSettings['booking_email_subject_to_admin'] ?? ('You have new booking order | ' . $menuNameForBookings));
            $bookingEmailBodyAdminUi = (string) ($bookingSettings['booking_email_body_to_admin'] ?? '');

            $turnBookingFormsToJobsUi = (bool) ($bookingSettings['turnBookingFormsToJobs'] ?? false);
            $turnOffOtherDeviceBrandsUi = (bool) ($bookingSettings['turnOffOtherDeviceBrands'] ?? false);
            $turnOffOtherServiceUi = (bool) ($bookingSettings['turnOffOtherService'] ?? false);
            $turnOffServicePriceUi = (bool) ($bookingSettings['turnOffServicePrice'] ?? false);
            $turnOffIdImeiBookingUi = (bool) ($bookingSettings['turnOffIdImeiBooking'] ?? false);

            if (old('wcrb_turn_booking_forms_to_jobs') !== null) {
                $turnBookingFormsToJobsUi = (string) old('wcrb_turn_booking_forms_to_jobs') === 'on';
            }
            if (old('wcrb_turn_off_other_device_brands') !== null) {
                $turnOffOtherDeviceBrandsUi = (string) old('wcrb_turn_off_other_device_brands') === 'on';
            }
            if (old('wcrb_turn_off_other_service') !== null) {
                $turnOffOtherServiceUi = (string) old('wcrb_turn_off_other_service') === 'on';
            }
            if (old('wcrb_turn_off_service_price') !== null) {
                $turnOffServicePriceUi = (string) old('wcrb_turn_off_service_price') === 'on';
            }
            if (old('wcrb_turn_off_idimei_booking') !== null) {
                $turnOffIdImeiBookingUi = (string) old('wcrb_turn_off_idimei_booking') === 'on';
            }

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

            $oldSmsActive = old('wc_rb_sms_active');
            if ($oldSmsActive !== null) {
                $smsActiveUi = (string) $oldSmsActive === 'on' || (string) $oldSmsActive === 'YES';
            }
            $oldSmsGateway = old('wc_rb_sms_gateway');
            if ($oldSmsGateway !== null) {
                $smsGatewayUi = is_string($oldSmsGateway) ? $oldSmsGateway : '';
            }
            foreach (['sms_gateway_account_sid', 'sms_gateway_auth_token', 'sms_gateway_from_number'] as $field) {
                $old = old($field);
                if ($old === null) {
                    continue;
                }
                if ($field === 'sms_gateway_account_sid') {
                    $smsGatewayAccountSidUi = is_string($old) ? $old : '';
                } elseif ($field === 'sms_gateway_auth_token') {
                    $smsGatewayAuthTokenUi = is_string($old) ? $old : '';
                } elseif ($field === 'sms_gateway_from_number') {
                    $smsGatewayFromNumberUi = is_string($old) ? $old : '';
                }
            }
            $oldStatusIncludes = old('wc_rb_job_status_include');
            if (is_array($oldStatusIncludes)) {
                $smsSendWhenStatusChangedToIdsUi = collect($oldStatusIncludes)
                    ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                    ->map(fn ($v) => trim($v))
                    ->unique()
                    ->values()
                    ->all();
            }
            $oldTestNumber = old('sms_test_number');
            if ($oldTestNumber !== null) {
                $smsTestNumberUi = is_string($oldTestNumber) ? $oldTestNumber : '';
            }
            $oldTestMessage = old('sms_test_message');
            if ($oldTestMessage !== null) {
                $smsTestMessageUi = is_string($oldTestMessage) ? $oldTestMessage : '';
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

            if (old('request_by_sms') !== null) {
                $reviewsRequestBySmsUi = (string) old('request_by_sms') === 'on';
            }
            if (old('request_by_email') !== null) {
                $reviewsRequestByEmailUi = (string) old('request_by_email') === 'on';
            }
            $oldReviewsInterval = old('auto_request_interval');
            if (is_string($oldReviewsInterval) && in_array($oldReviewsInterval, ['disabled', 'one-notification', 'two-notifications'], true)) {
                $reviewsIntervalUi = $oldReviewsInterval;
            }

            $jobStatusesForReviews = Status::query()->where('status_type', 'Job')->orderBy('label')->limit(500)->get();

            $deviceTypesForMaintenance = \App\Models\RepairBuddyDeviceType::query()->orderBy('name')->limit(200)->get();
            $deviceBrandsForMaintenance = \App\Models\RepairBuddyDeviceBrand::query()->orderBy('name')->limit(200)->get();

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
                ['id' => 'wc_rb_payment_status', 'label' => __('Payment Status'), 'heading' => __('Payment Status')],
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

            $extraTabs = [
                ['id' => 'wc_rb_page_settings', 'label' => __('Pages Setup'), 'view' => 'tenant.settings.sections.pages-setup'],
            ];

            foreach ($settingsTabs as $tab) {
                $tabId = $tab['id'];
                $tabLabel = $tab['label'];
                $heading = $tab['heading'];

                $tabView = match ($tabId) {
                    'wc_rb_manage_devices' => 'tenant.settings.sections.devices-brands',
                    'wc_rb_manage_bookings' => 'tenant.settings.sections.bookings',
                    'wc_rb_manage_service' => 'tenant.settings.sections.services',
                    'wcrb_estimates_tab' => 'tenant.settings.sections.estimates',
                    'wc_rb_manage_taxes' => 'tenant.settings.sections.taxes',
                    'wc_rb_maintenance_reminder' => 'tenant.settings.sections.maintenance-reminders',
                    'wcrb_timelog_tab' => 'tenant.settings.sections.timelog',
                    'wcrb_styling' => 'tenant.settings.sections.styling',
                    'wcrb_reviews_tab' => 'tenant.settings.sections.reviews',
                    'wc_rb_manage_account' => 'tenant.settings.sections.account',
                    'wcrb_signature_workflow' => 'tenant.settings.sections.signature',
                    default => '',
                };

                if ($tabId !== 'wc_rb_payment_status' && $tabView !== '') {
                    $extraTabs[] = [
                        'id' => $tabId,
                        'label' => $tabLabel,
                        'view' => $tabView,
                    ];
                }

                continue;
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
                'extraTabs' => $extraTabs,
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
                'paymentMethodsActive' => (function () use ($repairBuddySettings) {
                    $defaultMethods = $repairBuddySettings['payment_methods_active'] ?? [];
                    return is_array($defaultMethods) ? $defaultMethods : [];
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
            'sms_settings_form' => ['nullable', 'in:1'],
            'wc_rb_job_status_include_present' => ['nullable', 'in:1'],

            'wc_rb_sms_active' => ['nullable', 'in:YES,on'],
            'wc_rb_sms_gateway' => ['nullable', 'string', 'max:64'],
            'sms_gateway_account_sid' => ['nullable', 'string', 'max:255'],
            'sms_gateway_auth_token' => ['nullable', 'string', 'max:255'],
            'sms_gateway_from_number' => ['nullable', 'string', 'max:64'],
            'wc_rb_job_status_include' => ['nullable', 'array'],
            'wc_rb_job_status_include.*' => ['string', 'max:64'],

            'sms_test' => ['nullable', 'in:1'],
            'sms_test_number' => ['nullable', 'string', 'max:64', 'required_if:sms_test,1'],
            'sms_test_message' => ['nullable', 'string', 'max:1024', 'required_if:sms_test,1'],
        ]);

        $isSettingsForm = array_key_exists('sms_settings_form', $validated);
        $isTestForm = array_key_exists('sms_test', $validated);

        $setupState = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $setupState['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }

        $sms = $repairBuddySettings['sms'] ?? [];
        if (! is_array($sms)) {
            $sms = [];
        }

        if ($isSettingsForm) {
            $isActive = $request->has('wc_rb_sms_active');
            $sms['activateSmsForSelectiveStatuses'] = $isActive;
            $sms['enabled'] = $isActive;

            $sms['gateway'] = (string) ($validated['wc_rb_sms_gateway'] ?? '');
            $sms['gatewayAccountSid'] = $validated['sms_gateway_account_sid'] ?? null;
            $sms['gatewayAuthToken'] = $validated['sms_gateway_auth_token'] ?? null;
            $sms['gatewayFromNumber'] = $validated['sms_gateway_from_number'] ?? null;

            $includes = $validated['wc_rb_job_status_include'] ?? null;
            if (is_array($includes)) {
                $sms['sendWhenStatusChangedToIds'] = collect($includes)
                    ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                    ->map(fn ($v) => trim((string) $v))
                    ->unique()
                    ->values()
                    ->all();
            } elseif (array_key_exists('wc_rb_job_status_include_present', $validated)) {
                $sms['sendWhenStatusChangedToIds'] = [];
            }
        }

        if ($isTestForm) {
            $sms['testNumber'] = $validated['sms_test_number'] ?? null;
            $sms['testMessage'] = $validated['sms_test_message'] ?? null;
        }

        if (($sms['gateway'] ?? null) === 'custom') {
            $sms['apiKey'] = $sms['gatewayAccountSid'] ?? null;
            $sms['senderId'] = $sms['gatewayFromNumber'] ?? null;
        }

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

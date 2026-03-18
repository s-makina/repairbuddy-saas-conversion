<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class BookingSettings extends Component
{
    public $tenant;

    /* ─── Email to Customer ──────────────────────── */
    public string $email_subject_customer = '';
    public string $email_body_customer = '';

    /* ─── Email to Admin ─────────────────────────── */
    public string $email_subject_admin = '';
    public string $email_body_admin = '';

    /* ─── Booking & Quote Form settings ──────────── */
    public bool $send_to_jobs = false;
    public bool $turn_off_other_device_brands = false;
    public bool $turn_off_other_service = false;
    public bool $turn_off_service_price = false;
    public bool $turn_off_id_imei_booking = false;

    /* ─── Default selections ─────────────────────── */
    public string $default_type = '';
    public string $default_brand = '';
    public string $default_device = '';

    /* ─── Options ────────────────────────────────── */
    public array $typeOptions = [];
    public array $brandOptions = [];
    public array $deviceOptions = [];

    protected function rules(): array
    {
        return [
            'email_subject_customer'        => 'nullable|string|max:255',
            'email_body_customer'           => 'nullable|string|max:5000',
            'email_subject_admin'           => 'nullable|string|max:255',
            'email_body_admin'              => 'nullable|string|max:5000',
            'send_to_jobs'                  => 'boolean',
            'turn_off_other_device_brands'  => 'boolean',
            'turn_off_other_service'        => 'boolean',
            'turn_off_service_price'        => 'boolean',
            'turn_off_id_imei_booking'      => 'boolean',
            'default_type'                  => 'nullable|string',
            'default_brand'                 => 'nullable|string',
            'default_device'                => 'nullable|string',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        $this->loadSettings();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->defaultBranch;
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadOptions(): void
    {
        $this->typeOptions = ['' => '— Select Type —'] +
            RepairBuddyDeviceType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();

        $this->brandOptions = ['' => '— Select Brand —'] +
            RepairBuddyDeviceBrand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();

        // Devices are typically filtered by type/brand, start with empty
        $this->deviceOptions = ['' => '— Select Device —'];
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('booking', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        // Default email templates (matching WordPress plugin)
        $defaultCustomerSubject = 'We have received your booking order!';
        $defaultCustomerBody = <<<'TEXT'
Hello {{customer_full_name}},

Thank you for booking. We have received your job id : {{job_id}} and assigned you case number : {{case_number}}

For your device : {{customer_device_label}}

Note: Job status page will not able to show your job details unless its approved from our side. During our working hours its done quickly.

We will get in touch whenever its needed. You can always check your job status by clicking {{start_anch_status_check_link}} Check Status {{end_anch_status_check_link}}.

Direct status check link : {{status_check_link}}

Details which we have received from you are below.

{{order_invoice_details}}

Thank you again for your business!
TEXT;

        $defaultAdminSubject = 'You have new booking order';
        $defaultAdminBody = <<<'TEXT'
Hello,

You have received a new booking job ID: {{job_id}} case number: {{case_number}}.

From Customer : {{customer_full_name}}

Job Details are listed below.

{{order_invoice_details}}
TEXT;

        $this->email_subject_customer       = (string) ($settings['customerEmailSubject'] ?? $defaultCustomerSubject);
        $this->email_body_customer          = (string) ($settings['customerEmailBody'] ?? $defaultCustomerBody);
        $this->email_subject_admin          = (string) ($settings['adminEmailSubject'] ?? $defaultAdminSubject);
        $this->email_body_admin             = (string) ($settings['adminEmailBody'] ?? $defaultAdminBody);
        $this->send_to_jobs                 = (bool) ($settings['sendBookingQuoteToJobs'] ?? false);
        $this->turn_off_other_device_brands = (bool) ($settings['turnOffOtherDeviceBrand'] ?? false);
        $this->turn_off_other_service       = (bool) ($settings['turnOffOtherService'] ?? false);
        $this->turn_off_service_price       = (bool) ($settings['turnOffServicePrice'] ?? false);
        $this->turn_off_id_imei_booking     = (bool) ($settings['turnOffIdImeiInBooking'] ?? false);
        $this->default_type                 = (string) ($settings['defaultType'] ?? '');
        $this->default_brand                = (string) ($settings['defaultBrand'] ?? '');
        $this->default_device               = (string) ($settings['defaultDevice'] ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('booking', [
            'customerEmailSubject'       => $this->email_subject_customer,
            'customerEmailBody'          => $this->email_body_customer,
            'adminEmailSubject'          => $this->email_subject_admin,
            'adminEmailBody'             => $this->email_body_admin,
            'sendBookingQuoteToJobs'     => $this->send_to_jobs,
            'turnOffOtherDeviceBrand'    => $this->turn_off_other_device_brands,
            'turnOffOtherService'        => $this->turn_off_other_service,
            'turnOffServicePrice'        => $this->turn_off_service_price,
            'turnOffIdImeiInBooking'     => $this->turn_off_id_imei_booking,
            'defaultType'                => $this->default_type,
            'defaultBrand'               => $this->default_brand,
            'defaultDevice'              => $this->default_device,
        ]);

        $store->merge('estimates', [
            'bookingQuoteSendToJobs' => $this->send_to_jobs,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Booking settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.booking-settings');
    }
}

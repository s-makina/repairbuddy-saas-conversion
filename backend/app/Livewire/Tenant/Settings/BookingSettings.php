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
        $settings = $store->get('bookings', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->email_subject_customer       = (string) ($settings['email_subject_customer'] ?? '');
        $this->email_body_customer          = (string) ($settings['email_body_customer'] ?? '');
        $this->email_subject_admin          = (string) ($settings['email_subject_admin'] ?? '');
        $this->email_body_admin             = (string) ($settings['email_body_admin'] ?? '');
        $this->send_to_jobs                 = (bool) ($settings['send_to_jobs'] ?? false);
        $this->turn_off_other_device_brands = (bool) ($settings['turn_off_other_device_brands'] ?? false);
        $this->turn_off_other_service       = (bool) ($settings['turn_off_other_service'] ?? false);
        $this->turn_off_service_price       = (bool) ($settings['turn_off_service_price'] ?? false);
        $this->turn_off_id_imei_booking     = (bool) ($settings['turn_off_id_imei_booking'] ?? false);
        $this->default_type                 = (string) ($settings['default_type'] ?? '');
        $this->default_brand                = (string) ($settings['default_brand'] ?? '');
        $this->default_device               = (string) ($settings['default_device'] ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('bookings', [
            'email_subject_customer'       => $this->email_subject_customer,
            'email_body_customer'          => $this->email_body_customer,
            'email_subject_admin'          => $this->email_subject_admin,
            'email_body_admin'             => $this->email_body_admin,
            'send_to_jobs'                 => $this->send_to_jobs,
            'turn_off_other_device_brands' => $this->turn_off_other_device_brands,
            'turn_off_other_service'       => $this->turn_off_other_service,
            'turn_off_service_price'       => $this->turn_off_service_price,
            'turn_off_id_imei_booking'     => $this->turn_off_id_imei_booking,
            'default_type'                 => $this->default_type,
            'default_brand'                => $this->default_brand,
            'default_device'               => $this->default_device,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Booking settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.booking-settings');
    }
}

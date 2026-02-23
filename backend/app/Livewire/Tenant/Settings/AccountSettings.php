<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class AccountSettings extends Component
{
    public $tenant;

    /* ─── My Account Page Options ────────────────── */
    public bool $customer_registration = false;
    public bool $account_approval_required = false;
    public string $default_customer_role = 'customer';

    /* ─── Feature Toggles (From Plugin Parity) ──── */
    public bool $disable_booking = false;
    public bool $disable_estimates = false;
    public bool $disable_reviews = false;
    public string $booking_form_type = 'with_type';

    /* ─── Options ────────────────────────────────── */
    public array $roleOptions = [];
    public array $bookingFormOptions = [];

    protected function rules(): array
    {
        return [
            'customer_registration'     => 'boolean',
            'account_approval_required' => 'boolean',
            'default_customer_role'     => 'required|string|in:customer,vip_customer',
            'disable_booking'           => 'boolean',
            'disable_estimates'         => 'boolean',
            'disable_reviews'           => 'boolean',
            'booking_form_type'         => 'required|string|in:with_type,without_type,warranty_booking',
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
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadOptions(): void
    {
        $this->roleOptions = [
            'customer'     => 'Customer',
            'vip_customer' => 'VIP Customer',
        ];

        $this->bookingFormOptions = [
            'with_type'        => 'With Device Type',
            'without_type'     => 'Without Device Type',
            'warranty_booking' => 'Warranty Booking',
        ];
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('account', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->customer_registration     = (bool) ($settings['customer_registration'] ?? false);
        $this->account_approval_required = (bool) ($settings['account_approval_required'] ?? false);
        $this->default_customer_role     = (string) ($settings['default_customer_role'] ?? 'customer');
        $this->disable_booking           = (bool) ($settings['disable_booking'] ?? false);
        $this->disable_estimates         = (bool) ($settings['disable_estimates'] ?? false);
        $this->disable_reviews           = (bool) ($settings['disable_reviews'] ?? false);
        $this->booking_form_type         = (string) ($settings['booking_form_type'] ?? 'with_type');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('account', [
            'customer_registration'     => $this->customer_registration,
            'account_approval_required' => $this->account_approval_required,
            'default_customer_role'     => $this->default_customer_role,
            'disable_booking'           => $this->disable_booking,
            'disable_estimates'         => $this->disable_estimates,
            'disable_reviews'           => $this->disable_reviews,
            'booking_form_type'         => $this->booking_form_type,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Account settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.account-settings');
    }
}

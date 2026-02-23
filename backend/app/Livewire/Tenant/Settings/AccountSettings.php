<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
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

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        // TODO: Load settings from TenantSettingsStore
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

    public function save(): void
    {
        // TODO: Wire functionality — persist to TenantSettingsStore
        $this->dispatch('settings-saved', message: 'Account settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.account-settings');
    }
}

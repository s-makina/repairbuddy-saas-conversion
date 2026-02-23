<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class DevicesBrandsSettings extends Component
{
    public $tenant;

    /* ─── Pin Code ───────────────────────────────── */
    public bool $enable_pin_code = false;
    public bool $show_pin_in_documents = false;

    /* ─── Labels ─────────────────────────────────── */
    public string $label_note = 'Note';
    public string $label_pin = 'Pin Code / Password';
    public string $label_device = 'Device';
    public string $label_brand = 'Brand';
    public string $label_type = 'Type';
    public string $label_imei = 'ID / IMEI';

    /* ─── Pickup & Delivery ──────────────────────── */
    public bool $pickup_delivery_enabled = false;
    public string $pickup_charge = '0';
    public string $delivery_charge = '0';

    /* ─── Rental ─────────────────────────────────── */
    public bool $rental_enabled = false;
    public string $rental_per_day = '0';
    public string $rental_per_week = '0';

    /* ─── Additional Device Fields ───────────────── */
    public array $additional_fields = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load from TenantSettingsStore
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) { BranchContext::set($branch); }
        }
    }

    public function addField(): void
    {
        if (count($this->additional_fields) < 10) {
            $this->additional_fields[] = [
                'label' => '',
                'type' => 'text',
                'show_in_booking' => true,
                'show_in_invoice' => true,
                'show_for_customer' => true,
            ];
        }
    }

    public function removeField(int $index): void
    {
        unset($this->additional_fields[$index]);
        $this->additional_fields = array_values($this->additional_fields);
    }

    public function save(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Devices & Brands settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.devices-brands-settings');
    }
}

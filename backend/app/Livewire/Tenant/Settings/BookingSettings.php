<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
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

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load settings and populate options from DB
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) { BranchContext::set($branch); }
        }
    }

    public function save(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Booking settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.booking-settings');
    }
}

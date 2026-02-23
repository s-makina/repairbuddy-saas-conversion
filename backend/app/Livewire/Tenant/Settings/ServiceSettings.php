<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class ServiceSettings extends Component
{
    public $tenant;

    /* ─── Service Settings ───────────────────────── */
    public string $sidebar_description = '';
    public bool $disable_booking_on_service_page = false;
    public string $booking_heading = '';
    public string $booking_form_type = 'with_type';

    /* ─── Options ────────────────────────────────── */
    public array $formTypeOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->formTypeOptions = [
            'with_type' => 'With Type',
            'without_type' => 'Without Type',
            'warranty' => 'Warranty Booking',
        ];
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

    public function save(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Service settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.service-settings');
    }
}

<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class StylingSettings extends Component
{
    public $tenant;

    /* ─── Labels ─────────────────────────────────── */
    public string $delivery_date_label = '';
    public string $pickup_date_label = '';
    public string $nextservice_date_label = '';
    public string $casenumber_label = '';

    /* ─── Colors ─────────────────────────────────── */
    public string $primary_color = '#063e70';
    public string $secondary_color = '#fd6742';

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
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

    public function save(): void
    {
        // TODO: Wire functionality — persist to TenantSettingsStore
        $this->dispatch('settings-saved', message: 'Styling settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.styling-settings');
    }
}

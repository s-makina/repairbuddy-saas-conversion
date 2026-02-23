<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class TimeLogSettings extends Component
{
    public $tenant;

    /* ─── General ────────────────────────────────── */
    public bool $disable_timelog = false;
    public string $default_tax_id = '';
    public string $activities = '';

    /* ─── Status Inclusion ───────────────────────── */
    public array $available_statuses = [];
    public array $included_statuses = [];

    /* ─── Options ────────────────────────────────── */
    public array $taxOptions = [];

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
        // TODO: Populate from database
        $this->taxOptions = ['' => 'Select tax'];
        $this->available_statuses = [
            ['key' => 'received',    'label' => 'Received'],
            ['key' => 'in_progress', 'label' => 'In Progress'],
            ['key' => 'completed',   'label' => 'Completed'],
            ['key' => 'delivered',   'label' => 'Delivered'],
        ];
    }

    public function save(): void
    {
        // TODO: Wire functionality — persist to TenantSettingsStore
        $this->dispatch('settings-saved', message: 'Time log settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.time-log-settings');
    }
}

<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'delivery_date_label'    => 'nullable|string|max:100',
            'pickup_date_label'      => 'nullable|string|max:100',
            'nextservice_date_label' => 'nullable|string|max:100',
            'casenumber_label'       => 'nullable|string|max:100',
            'primary_color'          => 'nullable|string|max:20',
            'secondary_color'        => 'nullable|string|max:20',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
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

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('styling', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->delivery_date_label    = (string) ($settings['delivery_date_label'] ?? '');
        $this->pickup_date_label      = (string) ($settings['pickup_date_label'] ?? '');
        $this->nextservice_date_label = (string) ($settings['nextservice_date_label'] ?? '');
        $this->casenumber_label       = (string) ($settings['casenumber_label'] ?? '');
        $this->primary_color          = (string) ($settings['primary_color'] ?? '#063e70');
        $this->secondary_color        = (string) ($settings['secondary_color'] ?? '#fd6742');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('styling', [
            'delivery_date_label'    => $this->delivery_date_label,
            'pickup_date_label'      => $this->pickup_date_label,
            'nextservice_date_label' => $this->nextservice_date_label,
            'casenumber_label'       => $this->casenumber_label,
            'primary_color'          => $this->primary_color,
            'secondary_color'        => $this->secondary_color,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Styling settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.styling-settings');
    }
}

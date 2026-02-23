<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyJobStatus;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'disable_timelog'    => 'boolean',
            'default_tax_id'    => 'nullable|string',
            'activities'         => 'nullable|string|max:5000',
            'included_statuses'  => 'array',
            'included_statuses.*' => 'string',
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
        $this->taxOptions = ['' => 'Select tax'] +
            RepairBuddyTax::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn ($t) => [(string) $t->id => $t->name . ' (' . $t->rate . '%)'])
                ->toArray();

        $this->available_statuses = RepairBuddyJobStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'key'   => $s->slug,
                'label' => $s->label,
            ])
            ->toArray();
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('time_log', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->disable_timelog    = (bool) ($settings['disable_timelog'] ?? false);
        $this->default_tax_id    = (string) ($settings['default_tax_id'] ?? '');
        $this->activities         = (string) ($settings['activities'] ?? '');
        $this->included_statuses  = (array) ($settings['included_statuses'] ?? []);
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('time_log', [
            'disable_timelog'   => $this->disable_timelog,
            'default_tax_id'   => $this->default_tax_id,
            'activities'        => $this->activities,
            'included_statuses' => $this->included_statuses,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Time log settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.time-log-settings');
    }
}

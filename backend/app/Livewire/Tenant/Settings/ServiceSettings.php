<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'sidebar_description'            => 'nullable|string|max:2000',
            'disable_booking_on_service_page' => 'boolean',
            'booking_heading'                => 'nullable|string|max:255',
            'booking_form_type'              => 'required|string|in:with_type,without_type,warranty',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->formTypeOptions = [
            'with_type'    => 'With Type',
            'without_type' => 'Without Type',
            'warranty'     => 'Warranty Booking',
        ];
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

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('services', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->sidebar_description            = (string) ($settings['sidebar_description'] ?? '');
        $this->disable_booking_on_service_page = (bool) ($settings['disable_booking_on_service_page'] ?? false);
        $this->booking_heading                = (string) ($settings['booking_heading'] ?? '');
        $this->booking_form_type              = (string) ($settings['booking_form_type'] ?? 'with_type');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('services', [
            'sidebar_description'            => $this->sidebar_description,
            'disable_booking_on_service_page' => $this->disable_booking_on_service_page,
            'booking_heading'                => $this->booking_heading,
            'booking_form_type'              => $this->booking_form_type,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Service settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.service-settings');
    }
}

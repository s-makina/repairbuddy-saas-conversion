<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class TaxSettings extends Component
{
    public $tenant;

    /* ─── Tax Table ──────────────────────────────── */
    public array $taxes = [];

    /* ─── Tax Settings ───────────────────────────── */
    public bool $enable_taxes = false;
    public string $default_tax = '';
    public string $prices_inclusive_exclusive = 'exclusive';

    /* ─── Modal ──────────────────────────────────── */
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $modal_tax_name = '';
    public string $modal_tax_description = '';
    public string $modal_tax_rate = '';
    public string $modal_tax_status = 'active';

    /* ─── Options ────────────────────────────────── */
    public array $taxInclusiveOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->taxInclusiveOptions = [
            'exclusive' => 'Exclusive (tax added to price)',
            'inclusive' => 'Inclusive (tax included in price)',
        ];
        // TODO: Load taxes and settings from DB
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) { BranchContext::set($branch); }
        }
    }

    public function openAddModal(): void
    {
        $this->editingId = null;
        $this->modal_tax_name = '';
        $this->modal_tax_description = '';
        $this->modal_tax_rate = '';
        $this->modal_tax_status = 'active';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->editingId = $id;
        // TODO: Populate from record
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    public function saveTax(): void
    {
        // TODO: Wire functionality
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Tax saved.');
    }

    public function setDefault(int $id): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Default tax updated.');
    }

    public function saveSettings(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Tax settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.tax-settings');
    }
}

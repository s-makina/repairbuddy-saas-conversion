<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'modal_tax_name'        => 'required|string|max:100',
            'modal_tax_description' => 'nullable|string|max:255',
            'modal_tax_rate'        => 'required|numeric|min:0|max:100',
            'modal_tax_status'      => 'required|in:active,inactive',
        ];
    }

    protected array $validationAttributes = [
        'modal_tax_name'   => 'tax name',
        'modal_tax_rate'   => 'tax rate',
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->taxInclusiveOptions = [
            'exclusive' => 'Exclusive (tax added to price)',
            'inclusive' => 'Inclusive (tax included in price)',
        ];
        $this->loadTaxes();
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

    private function loadTaxes(): void
    {
        $this->taxes = RepairBuddyTax::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'description' => $t->description ?? '',
                'rate'        => $t->rate,
                'is_default'  => $t->is_default,
                'is_active'   => $t->is_active,
            ])
            ->toArray();
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('taxes', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->enable_taxes              = (bool) ($settings['enable_taxes'] ?? false);
        $this->prices_inclusive_exclusive = (string) ($settings['prices_inclusive_exclusive'] ?? 'exclusive');

        // Default tax from DB
        $defaultTax = collect($this->taxes)->firstWhere('is_default', true);
        $this->default_tax = $defaultTax ? (string) $defaultTax['id'] : '';
    }

    public function openAddModal(): void
    {
        $this->editingId = null;
        $this->modal_tax_name = '';
        $this->modal_tax_description = '';
        $this->modal_tax_rate = '';
        $this->modal_tax_status = 'active';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $tax = RepairBuddyTax::find($id);
        if (! $tax) {
            return;
        }

        $this->editingId            = $tax->id;
        $this->modal_tax_name       = $tax->name;
        $this->modal_tax_description = $tax->description ?? '';
        $this->modal_tax_rate       = (string) $tax->rate;
        $this->modal_tax_status     = $tax->is_active ? 'active' : 'inactive';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function saveTax(): void
    {
        $this->validate();

        if ($this->editingId) {
            $tax = RepairBuddyTax::find($this->editingId);
            if (! $tax) {
                $this->dispatch('settings-saved', message: 'Tax not found.');
                return;
            }

            $tax->update([
                'name'        => $this->modal_tax_name,
                'description' => $this->modal_tax_description,
                'rate'        => (float) $this->modal_tax_rate,
                'is_active'   => $this->modal_tax_status === 'active',
            ]);
        } else {
            RepairBuddyTax::create([
                'name'        => $this->modal_tax_name,
                'description' => $this->modal_tax_description,
                'rate'        => (float) $this->modal_tax_rate,
                'is_active'   => $this->modal_tax_status === 'active',
                'is_default'  => false,
            ]);
        }

        $this->loadTaxes();
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Tax saved successfully.');
    }

    public function deleteTax(int $id): void
    {
        $tax = RepairBuddyTax::find($id);
        if ($tax) {
            $tax->delete();
            $this->loadTaxes();
            $this->dispatch('settings-saved', message: 'Tax deleted.');
        }
    }

    public function setDefault(int $id): void
    {
        // Unset all defaults first
        RepairBuddyTax::query()->update(['is_default' => false]);

        // Set the new default
        $tax = RepairBuddyTax::find($id);
        if ($tax) {
            $tax->update(['is_default' => true]);
        }

        $this->loadTaxes();
        $this->default_tax = (string) $id;
        $this->dispatch('settings-saved', message: 'Default tax updated.');
    }

    public function saveSettings(): void
    {
        $this->validate([
            'enable_taxes'              => 'boolean',
            'prices_inclusive_exclusive' => 'required|in:exclusive,inclusive',
        ]);

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('taxes', [
            'enable_taxes'              => $this->enable_taxes,
            'prices_inclusive_exclusive' => $this->prices_inclusive_exclusive,
        ]);

        $store->save();

        // If default_tax changed, update DB
        if ($this->default_tax) {
            RepairBuddyTax::query()->update(['is_default' => false]);
            RepairBuddyTax::where('id', (int) $this->default_tax)->update(['is_default' => true]);
        }

        $this->loadTaxes();
        $this->dispatch('settings-saved', message: 'Tax settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.tax-settings');
    }
}

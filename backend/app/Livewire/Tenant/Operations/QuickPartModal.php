<?php

namespace App\Livewire\Tenant\Operations;

use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use App\Models\RepairBuddyPartType;
use App\Support\TenantContext;
use Livewire\Component;

class QuickPartModal extends Component
{
    public $showModal = false;
    public $tenant;

    // Core fields (mirroring main create form)
    public $name;
    public $part_brand_id;
    public $part_type_id;
    public $manufacturing_code;
    public $stock_code;
    public $sku;
    public $price_amount;
    public $warranty;
    public $core_features;
    public $capacity;
    public $installation_charges;
    public $installation_message;
    public $stock;

    protected $rules = [
        'name' => 'required|string|max:255',
        'part_brand_id' => 'nullable|integer',
        'part_type_id' => 'nullable|integer',
        'manufacturing_code' => 'required|string|max:255',
        'stock_code' => 'nullable|string|max:255',
        'sku' => 'nullable|string|max:128',
        'price_amount' => 'required|numeric|min:0',
        'warranty' => 'nullable|string|max:255',
        'core_features' => 'nullable|string|max:10000',
        'capacity' => 'nullable|string|max:255',
        'installation_charges' => 'nullable|numeric|min:0',
        'installation_message' => 'nullable|string|max:255',
        'stock' => 'nullable|integer|min:0',
    ];

    protected $listeners = ['openQuickPartModal' => 'open'];

    public function mount($tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function boot(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            \App\Support\TenantContext::set($this->tenant);
        }

        $branchId = session('active_branch_id');
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                \App\Support\BranchContext::set($branch);
            }
        }
    }

    public function hydrate(): void
    {
        $this->boot();
    }

    public function open()
    {
        $this->reset([
            'name', 'part_brand_id', 'part_type_id', 'manufacturing_code',
            'stock_code', 'sku', 'price_amount', 'warranty', 'core_features',
            'capacity', 'installation_charges', 'installation_message', 'stock',
        ]);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function save()
    {
        $this->validate();

        $tenant_id = $this->tenant ? $this->tenant->id : TenantContext::tenant()?->id;
        $tenantCurrency = TenantContext::tenant()?->currency ?? 'USD';

        $priceCents = (int) round(((float) $this->price_amount) * 100);

        $installationCents = null;
        if ($this->installation_charges !== null && $this->installation_charges !== '') {
            $installationCents = (int) round(((float) $this->installation_charges) * 100);
        }

        $part = RepairBuddyPart::create([
            'tenant_id' => $tenant_id,
            'branch_id' => session('active_branch_id'),
            'name' => $this->name,
            'part_type_id' => $this->part_type_id ?: null,
            'part_brand_id' => $this->part_brand_id ?: null,
            'sku' => $this->sku ?: null,
            'manufacturing_code' => $this->manufacturing_code,
            'stock_code' => $this->stock_code ?: null,
            'price_amount_cents' => $priceCents,
            'price_currency' => strtoupper($tenantCurrency),
            'warranty' => $this->warranty ?: null,
            'core_features' => $this->core_features ?: null,
            'capacity' => $this->capacity ?: null,
            'installation_charges_amount_cents' => $installationCents,
            'installation_charges_currency' => $installationCents !== null ? strtoupper($tenantCurrency) : null,
            'installation_message' => $this->installation_message ?: null,
            'stock' => $this->stock !== null && $this->stock !== '' ? (int) $this->stock : null,
            'is_active' => true,
        ]);

        $this->dispatch('partCreated', partId: $part->id);
        $this->dispatch('close-part-modal');
        $this->close();
    }

    public function render()
    {
        $types = RepairBuddyPartType::query()
            ->orderBy('name')
            ->limit(500)
            ->get();

        $brands = RepairBuddyPartBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get();

        $tenantCurrency = is_string(TenantContext::tenant()?->currency) && TenantContext::tenant()?->currency !== ''
            ? strtoupper(TenantContext::tenant()->currency)
            : '';

        return view('livewire.tenant.operations.quick-part-modal', [
            'types' => $types,
            'brands' => $brands,
            'tenantCurrency' => $tenantCurrency,
        ]);
    }
}

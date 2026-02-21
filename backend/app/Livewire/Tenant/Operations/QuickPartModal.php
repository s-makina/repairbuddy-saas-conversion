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

    public $name;
    public $sku;
    public $price_amount;
    public $part_type_id;
    public $part_brand_id;
    public $stock;
    public $email;
    public $phone;
    public $company;
    public $tax_id;
    public $address_line1;
    public $address_line2;
    public $address_city;
    public $address_state;
    public $address_postal_code;
    public $address_country;
    public $address_country_code;
    public $currency;

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:255',
        'price_amount' => 'nullable|numeric|min:0',
        'part_type_id' => 'nullable|integer',
        'part_brand_id' => 'nullable|integer',
        'stock' => 'nullable|integer|min:0',
    ];

    protected $listeners = ['openQuickPartModal' => 'open'];

    public function mount($tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            \App\Support\TenantContext::set($this->tenant);
        }
    }

    public function open()
    {
        $this->reset([
            'name', 'sku', 'price_amount', 'part_type_id', 'part_brand_id', 'stock'
        ]);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
        // The browser event is now dispatched directly in save() if needed,
        // but let's keep this clean for general closing.
    }

    public function save()
    {
        $this->validate();

        $tenant_id = $this->tenant ? $this->tenant->id : TenantContext::tenant()?->id;
        $branch_id = session('active_branch_id'); // We need a branch context, defaulting if not found usually

        $price_cents = (int) round(((float) $this->price_amount) * 100);

        $part = RepairBuddyPart::create([
            'tenant_id' => $tenant_id,
            'branch_id' => $branch_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price_amount_cents' => $price_cents,
            'price_currency' => TenantContext::tenant()?->currency ?? 'USD',
            'part_type_id' => $this->part_type_id ?: null,
            'part_brand_id' => $this->part_brand_id ?: null,
            'stock' => $this->stock ?: 0,
            'is_active' => true,
        ]);

        $this->dispatch('partCreated', partId: $part->id);
        $this->dispatch('close-part-modal'); 
        $this->close();
    }

    public function render()
    {
        $tenant_id = $this->tenant ? $this->tenant->id : TenantContext::tenant()?->id;

        $types = RepairBuddyPartType::where('tenant_id', $tenant_id)->where('is_active', true)->get();
        $brands = RepairBuddyPartBrand::where('tenant_id', $tenant_id)->where('is_active', true)->get();

        return view('livewire.tenant.operations.quick-part-modal', [
            'types' => $types,
            'brands' => $brands,
        ]);
    }
}

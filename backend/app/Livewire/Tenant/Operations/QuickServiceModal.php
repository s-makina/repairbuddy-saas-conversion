<?php

namespace App\Livewire\Tenant\Operations;

use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServiceType;
use App\Models\RepairBuddyTax;
use App\Support\TenantContext;
use Livewire\Component;

class QuickServiceModal extends Component
{
    public $showModal = false;
    public $tenant;

    // Fields
    public $name;
    public $description;
    public $service_type_id;
    public $base_price;
    public $tax_id;
    public $warranty;
    public $service_code;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:10000',
        'service_type_id' => 'nullable|integer',
        'base_price' => 'required|numeric|min:0',
        'tax_id' => 'nullable|integer',
        'warranty' => 'nullable|string|max:255',
        'service_code' => 'nullable|string|max:128',
    ];

    protected $listeners = ['openQuickServiceModal' => 'open'];

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
        $this->reset(['name', 'description', 'service_type_id', 'base_price', 'tax_id', 'warranty', 'service_code']);
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

        $priceCents = (int) round(((float) $this->base_price) * 100);

        $service = RepairBuddyService::create([
            'tenant_id' => $tenant_id,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'service_type_id' => $this->service_type_id ?: null,
            'service_code' => $this->service_code ?: null,
            'base_price_amount_cents' => $priceCents,
            'base_price_currency' => strtoupper($tenantCurrency),
            'tax_id' => $this->tax_id ?: null,
            'warranty' => $this->warranty ?: null,
            'is_active' => true,
        ]);

        $this->dispatch('serviceCreated', serviceId: $service->id);
        $this->dispatch('close-service-modal');
        $this->close();
    }

    public function render()
    {
        $types = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get();

        $taxes = RepairBuddyTax::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $tenantCurrency = is_string(TenantContext::tenant()?->currency) && TenantContext::tenant()?->currency !== ''
            ? strtoupper(TenantContext::tenant()->currency)
            : '';

        return view('livewire.tenant.operations.quick-service-modal', [
            'types' => $types,
            'taxes' => $taxes,
            'tenantCurrency' => $tenantCurrency,
        ]);
    }
}

<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServiceType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Livewire\Component;

class ServiceList extends Component
{
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Data ───────── */
    public array $serviceTypes = [];
    public array $ungroupedServices = [];
    public string $search = '';
    public string $filterTypeId = '';

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '')
    {
        $this->business = $business;

        if (! $tenant) {
            $tenant = TenantContext::tenant();
        }

        if ($tenant instanceof Tenant) {
            $this->tenant = $tenant;
            $this->tenantId = $tenant->id;
            $this->tenantName = (string) ($tenant->name ?? '');
        }

        $this->loadServices();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant) {
            TenantContext::set($this->tenant);

            $branchId = is_numeric($this->tenant->default_branch_id) ? (int) $this->tenant->default_branch_id : null;
            if ($branchId) {
                $branch = \App\Models\Branch::find($branchId);
                if ($branch) {
                    \App\Support\BranchContext::set($branch);
                }
            }
        }
    }

    /* ─────────── Load ─────────── */

    public function loadServices(): void
    {
        $types = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
            ])
            ->toArray();

        $this->serviceTypes = $types;

        $query = RepairBuddyService::query()
            ->with('type')
            ->where('is_active', true)
            ->orderBy('name');

        $services = $query->get();

        // Group by service type
        $grouped = [];
        $ungrouped = [];

        foreach ($services as $service) {
            $item = [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'service_code' => $service->service_code,
                'time_required' => $service->time_required,
                'warranty' => $service->warranty,
                'base_price_amount_cents' => $service->base_price_amount_cents,
                'base_price_currency' => $service->base_price_currency ?? 'USD',
                'type_id' => $service->service_type_id,
                'type_name' => $service->type?->name ?? null,
                'pick_up_delivery_available' => $service->pick_up_delivery_available,
                'laptop_rental_available' => $service->laptop_rental_available,
            ];

            if ($service->service_type_id) {
                $grouped[$service->service_type_id][] = $item;
            } else {
                $ungrouped[] = $item;
            }
        }

        $this->ungroupedServices = array_merge(
            ...array_values($grouped),
            ...[$ungrouped]
        );
    }

    /* ─────────── Computed ─────────── */

    public function getFilteredServicesProperty(): array
    {
        $services = $this->ungroupedServices;

        if ($this->filterTypeId !== '') {
            $typeId = (int) $this->filterTypeId;
            $services = array_filter($services, fn ($s) => ($s['type_id'] ?? null) === $typeId);
        }

        if ($this->search !== '') {
            $needle = mb_strtolower(trim($this->search));
            $services = array_filter($services, function ($s) use ($needle) {
                return str_contains(mb_strtolower($s['name']), $needle)
                    || str_contains(mb_strtolower($s['description'] ?? ''), $needle);
            });
        }

        return array_values($services);
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.public-pages.service-list', [
            'filteredServices' => $this->filteredServices,
        ]);
    }
}

<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartType;
use App\Models\RepairBuddyPartBrand;
use App\Models\Tenant;
use App\Support\TenantContext;
use Livewire\Component;

class PartList extends Component
{
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Data ───────── */
    public array $parts = [];
    public array $partTypes = [];
    public array $partBrands = [];
    public string $search = '';
    public string $filterTypeId = '';
    public string $filterBrandId = '';

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

        $this->loadParts();
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

    public function loadParts(): void
    {
        $this->partTypes = RepairBuddyPartType::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();

        $this->partBrands = RepairBuddyPartBrand::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->toArray();

        $this->parts = RepairBuddyPart::query()
            ->with(['type', 'brand'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock_code' => $p->stock_code,
                'price_amount_cents' => $p->price_amount_cents,
                'price_currency' => $p->price_currency ?? 'USD',
                'stock' => $p->stock,
                'warranty' => $p->warranty,
                'core_features' => $p->core_features,
                'capacity' => $p->capacity,
                'type_id' => $p->part_type_id,
                'type_name' => $p->type?->name ?? null,
                'brand_id' => $p->part_brand_id,
                'brand_name' => $p->brand?->name ?? null,
            ])
            ->toArray();
    }

    /* ─────────── Computed ─────────── */

    public function getFilteredPartsProperty(): array
    {
        $parts = $this->parts;

        if ($this->filterTypeId !== '') {
            $typeId = (int) $this->filterTypeId;
            $parts = array_filter($parts, fn ($p) => ($p['type_id'] ?? null) === $typeId);
        }

        if ($this->filterBrandId !== '') {
            $brandId = (int) $this->filterBrandId;
            $parts = array_filter($parts, fn ($p) => ($p['brand_id'] ?? null) === $brandId);
        }

        if ($this->search !== '') {
            $needle = mb_strtolower(trim($this->search));
            $parts = array_filter($parts, function ($p) use ($needle) {
                return str_contains(mb_strtolower($p['name']), $needle)
                    || str_contains(mb_strtolower($p['sku'] ?? ''), $needle)
                    || str_contains(mb_strtolower($p['core_features'] ?? ''), $needle);
            });
        }

        return array_values($parts);
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.public-pages.part-list', [
            'filteredParts' => $this->filteredParts,
        ]);
    }
}

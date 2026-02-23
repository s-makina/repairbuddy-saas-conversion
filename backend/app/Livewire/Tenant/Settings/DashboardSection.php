<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\Status;
use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class DashboardSection extends Component
{
    public $tenant;

    /** Lazy-loaded stats */
    public bool $statsLoaded = false;
    public array $jobStatusList = [];
    public array $estimateCountList = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
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

    /**
     * Called via wire:init — runs after the page is rendered so the UI is interactive immediately.
     */
    public function loadStats(): void
    {
        if ($this->statsLoaded) {
            return;
        }
        $this->jobStatusList = $this->buildJobStatuses();
        $this->estimateCountList = $this->buildEstimateCounts();
        $this->statsLoaded = true;
    }

    /**
     * Navigation card items — mirrors the original wcrb_dashboard_nav tiles.
     * Each item links to the matching tenant module route.
     */
    public function getNavItemsProperty(): array
    {
        $slug = $this->tenant->slug;

        /**
         * Helper: generate route URL if it exists, otherwise fall back to dashboard.
         * @param string $name
         * @param array  $params
         * @return string
         */
        $url = function (string $name, array $params = []) use ($slug): string {
            try {
                return route($name, array_merge(['business' => $slug], $params));
            } catch (\Exception $e) {
                return route('tenant.dashboard', ['business' => $slug]);
            }
        };

        return [
            ['label' => 'Tickets',       'image' => 'jobs.png',       'route' => $url('tenant.dashboard')],
            ['label' => 'Estimates',      'image' => 'estimate.png',   'route' => $url('tenant.estimates.index')],
            ['label' => 'Reviews',        'image' => 'reviews.png',    'route' => $url('tenant.dashboard')],
            ['label' => 'Payments',       'image' => 'payments.png',   'route' => $url('tenant.dashboard')],
            ['label' => 'Services',       'image' => 'services.png',   'route' => $url('tenant.operations.services.index')],
            ['label' => 'Parts',          'image' => 'parts.png',      'route' => $url('tenant.operations.parts.index')],
            ['label' => 'Devices',        'image' => 'devices.png',    'route' => $url('tenant.operations.devices.index')],
            ['label' => 'Device Brands',  'image' => 'manufacture.png','route' => $url('tenant.operations.brands.index')],
            ['label' => 'Device Type',    'image' => 'types.png',      'route' => $url('tenant.operations.brand_types.index')],
            ['label' => 'Customers',      'image' => 'clients.png',    'route' => $url('tenant.operations.clients.index')],
            ['label' => 'Technicians',    'image' => 'technicians.png','route' => $url('tenant.technicians.index')],
            ['label' => 'Managers',       'image' => 'manager.png',    'route' => $url('tenant.managers.index')],
            ['label' => 'Reports',        'image' => 'report.png',     'route' => $url('tenant.dashboard')],
        ];
    }

    /**
     * Job status list with counts for the current branch.
     */
    private function buildJobStatuses(): array
    {
        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();
        $tenantId = $tenant?->id;
        $branchId = $branch?->id;

        $statuses = Status::query()
            ->where('status_type', 'Job')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(200)
            ->get();

        $counts = [];
        if ($tenantId && $branchId) {
            $counts = RepairBuddyJob::query()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->selectRaw('status_slug, COUNT(*) as aggregate')
                ->groupBy('status_slug')
                ->pluck('aggregate', 'status_slug')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $slug = $this->tenant->slug;
        return $statuses->map(function (Status $s) use ($counts, $slug) {
            $code = trim((string) $s->code);
            return [
                'label' => (string) $s->label,
                'slug'  => $code,
                'count' => (int) ($counts[$code] ?? 0),
                'color' => (string) ($s->color ?? '#063e70'),
                'link'  => $code
                    ? route('tenant.jobs.create', ['business' => $slug]) . '?job_status=' . urlencode($code)
                    : route('tenant.dashboard', ['business' => $slug]),
            ];
        })->values()->all();
    }

    /**
     * Estimate status counts for the current branch.
     */
    private function buildEstimateCounts(): array
    {
        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();
        $tenantId = $tenant?->id;
        $branchId = $branch?->id;

        $defaults = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        if (! $tenantId || ! $branchId) {
            return $defaults;
        }

        $raw = RepairBuddyEstimate::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        foreach ($defaults as $k => $_) {
            $defaults[$k] = (int) ($raw[$k] ?? 0);
        }
        return $defaults;
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.dashboard-section');
    }
}

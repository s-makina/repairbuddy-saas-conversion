<?php

namespace App\Livewire\Tenant\Operations;

use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\Scopes\BranchScope;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class DocumentPreviewModal extends Component
{
    public $showModal = false;
    public $tenant;

    /* Persisted IDs — only these survive Livewire's serialize/deserialize cycle */
    public ?int    $loadedDocId   = null;
    public string  $loadedDocType = '';
    public bool    $isLoading     = false;

    /* Resolved scalar values (public = serialized by Livewire) */
    public $docType       = '';
    public $docNumber     = '';
    public $statusLabel   = '';
    public $paymentLabel  = '';
    public $shopName      = '';
    public $shopAddress   = '';
    public $shopPhone     = '';
    public $shopEmail     = '';
    public $currencyCode  = 'USD';
    public $warrantyLines = [];
    public $pdfUrl        = '#';
    public $printUrl      = '#';

    public function mount($tenant = null)
    {
        $this->tenant = $tenant;
        $this->bootContext();
    }

    public function boot(): void
    {
        $this->bootContext();
    }

    public function hydrate(): void
    {
        $this->bootContext();
    }

    private function bootContext(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            TenantContext::set($this->tenant);
        }

        $branchId = session('active_branch_id');
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                BranchContext::set($branch);
            }
        }

        Log::debug('[DocumentPreviewModal] bootContext', [
            'tenantId' => TenantContext::tenantId(),
            'branchId' => BranchContext::branchId(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Open the modal                                                     */
    /*  Usage:                                                             */
    /*    $dispatch('openDocumentPreview', { type: 'job', id: 42 })        */
    /*    $dispatch('openDocumentPreview', { type: 'estimate', id: 7 })    */
    /*    $dispatch('openDocumentPreview', { caseNumber: 'JOB-00042' })    */
    /* ------------------------------------------------------------------ */
    #[On('openDocumentPreview')]
    public function open($type = null, $id = null, $caseNumber = null): void
    {
        Log::debug('[DocumentPreviewModal] open() fired', [
            'type'       => $type,
            'id'         => $id,
            'caseNumber' => $caseNumber,
        ]);

        $this->resetState();

        // Resolve by case number if no explicit type/id
        if ($caseNumber && !$id) {
            $resolved = $this->resolveFromCaseNumber($caseNumber);
            if (!$resolved) {
                Log::warning('[DocumentPreviewModal] caseNumber not found', ['caseNumber' => $caseNumber]);
                return;
            }
            $type = $resolved['type'];
            $id   = $resolved['id'];
            Log::debug('[DocumentPreviewModal] resolved from caseNumber', $resolved);
        }

        if (!$type || !$id) {
            Log::warning('[DocumentPreviewModal] open() aborted — missing type or id', compact('type', 'id'));
            return;
        }

        // Fast path: show modal with spinner immediately — no DB work here
        $this->docType       = $type;
        $this->loadedDocType = $type;
        $this->loadedDocId   = (int) $id;
        $this->isLoading     = true;
        $this->showModal     = true;

        // Schedule the actual data load after this render completes
        $this->js('setTimeout(() => $wire.loadDocument(), 0)');
    }

    /* ------------------------------------------------------------------ */
    /*  Load document data (called after the modal is visible)            */
    /* ------------------------------------------------------------------ */
    public function loadDocument(): void
    {
        Log::debug('[DocumentPreviewModal] loadDocument() called', [
            'type' => $this->loadedDocType,
            'id'   => $this->loadedDocId,
        ]);

        if (!$this->loadedDocId || !$this->loadedDocType) {
            $this->isLoading = false;
            return;
        }

        if ($this->loadedDocType === 'job') {
            $this->loadJob($this->loadedDocId);
        } elseif ($this->loadedDocType === 'estimate') {
            $this->loadEstimate($this->loadedDocId);
        }

        Log::debug('[DocumentPreviewModal] loadDocument() complete', [
            'docNumber' => $this->docNumber,
            'status'    => $this->statusLabel,
            'pdfUrl'    => $this->pdfUrl,
        ]);

        $this->isLoading = false;
    }

    public function close(): void
    {
        $this->showModal = false;
    }

    /* ------------------------------------------------------------------ */
    /*  Render — rehydrate Eloquent data only when not in loading state   */
    /* ------------------------------------------------------------------ */

    public function render()
    {
        $doc        = null;
        $customer   = null;
        $technician = null;
        $items      = collect();
        $devices    = collect();

        Log::debug('[DocumentPreviewModal] render()', [
            'loadedDocType' => $this->loadedDocType,
            'loadedDocId'   => $this->loadedDocId,
            'isLoading'     => $this->isLoading,
            'showModal'     => $this->showModal,
        ]);

        // Only query DB when loadDocument() has finished
        if (!$this->isLoading && $this->loadedDocId && $this->loadedDocType) {
            if ($this->loadedDocType === 'job') {
                $job = RepairBuddyJob::query()->withoutGlobalScope(BranchScope::class)->whereKey($this->loadedDocId)->first();
                if ($job) {
                    $doc        = $job;
                    $items      = RepairBuddyJobItem::query()->withoutGlobalScope(BranchScope::class)->with(['tax'])->where('job_id', $job->id)->orderBy('id')->get();
                    $devices    = RepairBuddyJobDevice::query()->withoutGlobalScope(BranchScope::class)->with(['customerDevice.device'])->where('job_id', $job->id)->orderBy('id')->get();
                    $customer   = $job->customer;
                    $technician = $job->technicians?->first() ?? $job->assignedTechnician;
                    Log::debug('[DocumentPreviewModal] job loaded', [
                        'job_id'     => $job->id,
                        'items'      => $items->count(),
                        'devices'    => $devices->count(),
                        'customer'   => $customer?->name,
                        'technician' => $technician?->name,
                    ]);
                } else {
                    Log::warning('[DocumentPreviewModal] job not found', ['id' => $this->loadedDocId]);
                }
            } elseif ($this->loadedDocType === 'estimate') {
                $estimate = RepairBuddyEstimate::query()->withoutGlobalScope(BranchScope::class)->whereKey($this->loadedDocId)->first();
                if ($estimate) {
                    $doc        = $estimate;
                    $items      = RepairBuddyEstimateItem::query()->withoutGlobalScope(BranchScope::class)->with(['tax'])->where('estimate_id', $estimate->id)->orderBy('id')->get();
                    $devices    = RepairBuddyEstimateDevice::query()->withoutGlobalScope(BranchScope::class)->with(['customerDevice.device'])->where('estimate_id', $estimate->id)->orderBy('id')->get();
                    $customer   = $estimate->customer;
                    $technician = $estimate->assignedTechnician;
                    Log::debug('[DocumentPreviewModal] estimate loaded', [
                        'estimate_id' => $estimate->id,
                        'items'       => $items->count(),
                        'devices'     => $devices->count(),
                        'customer'    => $customer?->name,
                        'technician'  => $technician?->name,
                    ]);
                } else {
                    Log::warning('[DocumentPreviewModal] estimate not found', ['id' => $this->loadedDocId]);
                }
            }
        }

        return view('livewire.tenant.operations.document-preview-modal', [
            'doc'        => $doc,
            'customer'   => $customer,
            'technician' => $technician,
            'items'      => $items,
            'devices'    => $devices,
            'isLoading'  => $this->isLoading,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Private — load job data                                            */
    /* ------------------------------------------------------------------ */

    private function loadJob(int $jobId): void
    {
        $job = RepairBuddyJob::query()->withoutGlobalScope(BranchScope::class)->whereKey($jobId)->first();
        if (!$job) {
            Log::warning('[DocumentPreviewModal] loadJob: job not found', ['jobId' => $jobId]);
            return;
        }

        $this->statusLabel  = $job->status_slug ?? 'Open';
        $this->paymentLabel = $job->payment_status_slug ?? '';
        $this->docNumber    = $job->case_number ?? str_pad((string) $job->job_number, 5, '0', STR_PAD_LEFT);

        $this->resolveShopInfo();
        $this->warrantyLines = $this->resolveWarranty($job);
        $this->resolveUrls($job);

        Log::debug('[DocumentPreviewModal] loadJob scalars resolved', [
            'docNumber'   => $this->docNumber,
            'status'      => $this->statusLabel,
            'payment'     => $this->paymentLabel,
            'shopName'    => $this->shopName,
            'currency'    => $this->currencyCode,
            'warrantyCount' => count($this->warrantyLines),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Private — load estimate data                                       */
    /* ------------------------------------------------------------------ */

    private function loadEstimate(int $estimateId): void
    {
        $estimate = RepairBuddyEstimate::query()->withoutGlobalScope(BranchScope::class)->whereKey($estimateId)->first();
        if (!$estimate) {
            Log::warning('[DocumentPreviewModal] loadEstimate: estimate not found', ['estimateId' => $estimateId]);
            return;
        }

        $this->statusLabel  = ucfirst($estimate->status ?? 'Draft');
        $this->paymentLabel = '';
        $this->docNumber    = $estimate->case_number ?? ('EST-' . str_pad((string) $estimate->id, 5, '0', STR_PAD_LEFT));

        $this->resolveShopInfo();
        $this->warrantyLines = [];
        $this->resolveUrls($estimate);

        Log::debug('[DocumentPreviewModal] loadEstimate scalars resolved', [
            'docNumber' => $this->docNumber,
            'status'    => $this->statusLabel,
            'shopName'  => $this->shopName,
            'currency'  => $this->currencyCode,
            'printUrl'  => $this->printUrl,
            'pdfUrl'    => $this->pdfUrl,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Private — resolve from case number                                 */
    /* ------------------------------------------------------------------ */

    private function resolveFromCaseNumber(string $caseNumber): ?array
    {
        // Try job first
        $job = RepairBuddyJob::withoutGlobalScope(BranchScope::class)->where('case_number', $caseNumber)->first();
        if ($job) {
            return ['type' => 'job', 'id' => $job->id];
        }

        // Try estimate
        $estimate = RepairBuddyEstimate::withoutGlobalScope(BranchScope::class)->where('case_number', $caseNumber)->first();
        if ($estimate) {
            return ['type' => 'estimate', 'id' => $estimate->id];
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Private — shop info                                                */
    /* ------------------------------------------------------------------ */

    private function resolveShopInfo(): void
    {
        $tenant = TenantContext::tenant();
        $branch = BranchContext::branch();

        $this->shopName = ($branch && is_string($branch->name) && $branch->name !== '')
            ? $branch->name
            : (is_string($tenant?->name) ? (string) $tenant->name : 'Repair Shop');

        $parts = $branch ? array_filter([
            $branch->address_line1      ?? null,
            $branch->address_city       ?? null,
            $branch->address_state      ?? null,
            $branch->address_postal_code ?? null,
        ]) : [];
        $this->shopAddress = implode(', ', $parts);

        $this->shopPhone = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $this->shopEmail = $branch?->email ?? $tenant?->contact_email ?? '';
        $this->currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';
    }

    /* ------------------------------------------------------------------ */
    /*  Private — warranty                                                 */
    /* ------------------------------------------------------------------ */

    private function resolveWarranty(RepairBuddyJob $job): array
    {
        $lines = [];
        if ($job->items) {
            foreach ($job->items as $item) {
                $meta = is_string($item->meta_json)
                    ? json_decode($item->meta_json, true)
                    : (is_array($item->meta_json) ? $item->meta_json : []);
                $warranty = $meta['warranty'] ?? null;
                if ($warranty) {
                    $lines[] = $item->name_snapshot . ': ' . $warranty;
                }
            }
        }
        if (empty($lines)) {
            $lines = ['Parts: 90 days', 'Labour: 30 days'];
        }
        return $lines;
    }

    /* ------------------------------------------------------------------ */
    /*  Private — URLs                                                     */
    /* ------------------------------------------------------------------ */

    private function resolveUrls($doc): void
    {
        try {
            $tenant = TenantContext::tenant();
            $business = $tenant?->slug ?? 'default';

            if ($this->docType === 'job') {
                $this->printUrl = route('tenant.jobs.print', ['business' => $business, 'jobId' => $doc->id]);
                $this->pdfUrl   = route('tenant.jobs.pdf',   ['business' => $business, 'jobId' => $doc->id]);
            } else {
                $this->printUrl = route('tenant.estimates.print', ['business' => $business, 'estimateId' => $doc->id]);
                $this->pdfUrl   = route('tenant.estimates.pdf',   ['business' => $business, 'estimateId' => $doc->id]);
            }
        } catch (\Throwable) {
            $this->printUrl = '#';
            $this->pdfUrl   = '#';
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Private — reset                                                    */
    /* ------------------------------------------------------------------ */

    private function resetState(): void
    {
        $this->showModal     = false;
        $this->isLoading     = false;
        $this->loadedDocId   = null;
        $this->loadedDocType = '';
        $this->docType       = '';
        $this->docNumber     = '';
        $this->statusLabel   = '';
        $this->paymentLabel  = '';
        $this->warrantyLines = [];
        $this->pdfUrl        = '#';
        $this->printUrl      = '#';
    }
}

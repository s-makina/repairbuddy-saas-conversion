<?php

namespace App\Livewire\Tenant\JobStatus;

use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\TenantStatusOverride;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class StatusForm extends Component
{
    use WithFileUploads;

    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Search state ───────── */
    public string $caseNumber = '';
    public string $errorMessage = '';
    public bool $loading = false;

    /* ───────── Result state ───────── */
    public ?string $entityType = null; // 'job' or 'estimate'
    public ?array $job = null;
    public ?array $estimate = null;

    /* ───────── Message form ───────── */
    public string $messageBody = '';
    public $attachment = null;
    public string $messageSuccess = '';
    public string $messageError = '';
    public bool $messageSending = false;

    /* ───────── Active tab ───────── */
    public string $activeTab = 'timeline';

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '', string $initialCaseNumber = '')
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

        // If initial case number provided, search immediately
        if ($initialCaseNumber !== '') {
            $this->caseNumber = $initialCaseNumber;
            $this->searchCase();
        }
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

    /* ─────────── Search ─────────── */

    public function searchCase(): void
    {
        $this->errorMessage = '';
        $this->job = null;
        $this->estimate = null;
        $this->entityType = null;
        $this->messageSuccess = '';
        $this->messageError = '';

        $caseNumber = trim($this->caseNumber);
        if ($caseNumber === '') {
            $this->errorMessage = 'Please enter a case number.';
            return;
        }

        $this->loading = true;

        try {
            // First try to find a job
            $jobModel = RepairBuddyJob::query()
                ->with(['jobDevices.customerDevice.device.type', 'jobDevices.customerDevice.device.brand'])
                ->where('tenant_id', $this->tenantId)
                ->where('case_number', $caseNumber)
                ->first();

            if ($jobModel) {
                $this->entityType = 'job';
                $this->job = $this->serializeJobDetail($jobModel);
                $this->loading = false;
                return;
            }

            // Then try to find an estimate
            $estimateModel = RepairBuddyEstimate::query()
                ->where('tenant_id', $this->tenantId)
                ->where('case_number', $caseNumber)
                ->first();

            if ($estimateModel) {
                $this->entityType = 'estimate';
                $this->estimate = $this->serializeEstimateDetail($estimateModel);
                $this->loading = false;
                return;
            }

            $this->errorMessage = 'No case found with that number. Please check and try again.';
        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage());
            $this->errorMessage = 'An error occurred while searching. Please try again.';
        }

        $this->loading = false;
    }

    /* ─────────── Send Message ─────────── */

    public function sendMessage(): void
    {
        $this->messageSuccess = '';
        $this->messageError = '';

        if (! $this->job) {
            $this->messageError = 'Please search for a case first.';
            return;
        }

        $body = trim($this->messageBody);
        if ($body === '' && ! $this->attachment) {
            $this->messageError = 'Please enter a message or attach a file.';
            return;
        }

        $this->messageSending = true;

        try {
            $jobId = $this->job['id'] ?? null;
            if (! $jobId) {
                $this->messageError = 'Invalid job reference.';
                $this->messageSending = false;
                return;
            }

            // Handle file upload if present
            if ($this->attachment) {
                $originalName = $this->attachment->getClientOriginalName();
                $mimeType = $this->attachment->getMimeType() ?? 'application/octet-stream';
                $sizeBytes = $this->attachment->getSize();

                // Store the file
                $storagePath = $this->attachment->store("tenant_{$this->tenantId}/job_attachments", 'public');
                $url = Storage::disk('public')->url($storagePath);

                // Create event with attachment info
                RepairBuddyEvent::query()->create([
                    'actor_user_id' => null,
                    'entity_type' => 'job',
                    'entity_id' => $jobId,
                    'visibility' => 'public',
                    'event_type' => 'customer.attachment',
                    'payload_json' => [
                        'title' => 'Customer attachment',
                        'message' => "Uploaded file: {$originalName}",
                        'attachment' => [
                            'filename' => $originalName,
                            'mime_type' => $mimeType,
                            'size_bytes' => $sizeBytes,
                            'url' => $url,
                            'storage_path' => $storagePath,
                        ],
                    ],
                ]);
            }

            // Create message event if message provided
            if ($body !== '') {
                RepairBuddyEvent::query()->create([
                    'actor_user_id' => null,
                    'entity_type' => 'job',
                    'entity_id' => $jobId,
                    'visibility' => 'public',
                    'event_type' => 'customer.message',
                    'payload_json' => [
                        'title' => 'Customer message',
                        'message' => $body,
                    ],
                ]);
            }

            $this->messageBody = '';
            $this->attachment = null;
            $this->messageSuccess = 'Your message has been sent successfully!';

            // Refresh the job data to show the new message in timeline
            $this->searchCase();
        } catch (\Exception $e) {
            Log::error('Status message send error: ' . $e->getMessage());
            $this->messageError = 'Failed to send message. Please try again.';
        }

        $this->messageSending = false;
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function refresh(): void
    {
        if ($this->caseNumber !== '') {
            $this->searchCase();
        }
    }

    /* ─────────── Serializers ─────────── */

    protected function serializeJobDetail(RepairBuddyJob $job): array
    {
        // Get job status label
        $status = Status::query()
            ->where('status_type', 'Job')
            ->where('code', $job->status_slug)
            ->first();
        $statusOverride = TenantStatusOverride::query()
            ->where('tenant_id', $this->tenantId)
            ->where('domain', 'job')
            ->where('code', $job->status_slug)
            ->first();
        $statusLabel = $status?->label;
        if (is_string($statusOverride?->label) && $statusOverride->label !== '') {
            $statusLabel = $statusOverride->label;
        }

        // Get payment status label
        $paymentStatus = Status::query()
            ->where('status_type', 'Payment')
            ->where('code', $job->payment_status_slug)
            ->first();
        $paymentStatusOverride = TenantStatusOverride::query()
            ->where('tenant_id', $this->tenantId)
            ->where('domain', 'payment')
            ->where('code', $job->payment_status_slug)
            ->first();
        $paymentStatusLabel = $paymentStatus?->label;
        if (is_string($paymentStatusOverride?->label) && $paymentStatusOverride->label !== '') {
            $paymentStatusLabel = $paymentStatusOverride->label;
        }

        // Get timeline (public events only)
        $events = RepairBuddyEvent::query()
            ->where('entity_type', 'job')
            ->where('entity_id', $job->id)
            ->where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $timeline = $events->map(function (RepairBuddyEvent $e) {
            $payload = is_array($e->payload_json) ? $e->payload_json : [];
            $title = is_string($payload['title'] ?? null) ? $payload['title'] : null;
            if (! $title) {
                $title = match ((string) $e->event_type) {
                    'job.created' => 'Job created',
                    'job.status_changed' => 'Status updated',
                    'customer.message' => 'Customer message',
                    'customer.attachment' => 'File attached',
                    'note' => 'Note added',
                    default => ucfirst(str_replace(['.', '_'], ' ', (string) $e->event_type)),
                };
            }

            $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;
            $attachment = is_array($payload['attachment'] ?? null) ? $payload['attachment'] : null;

            return [
                'id' => (string) $e->id,
                'title' => $title,
                'type' => (string) $e->event_type,
                'message' => $message,
                'attachment' => $attachment ? [
                    'filename' => $attachment['filename'] ?? null,
                    'url' => $attachment['url'] ?? null,
                ] : null,
                'created_at' => $e->created_at?->format('M j, Y g:i A'),
                'created_at_raw' => $e->created_at?->toIso8601String(),
            ];
        })->values()->all();

        // Get job devices
        $devices = [];
        if ($job->relationLoaded('jobDevices')) {
            $devices = $job->jobDevices->map(function (RepairBuddyJobDevice $d) {
                $customerDevice = $d->customerDevice;
                return [
                    'id' => $d->id,
                    'label' => $d->label_snapshot,
                    'serial' => $d->serial_snapshot,
                    'type_name' => $customerDevice?->device?->type?->name ?? null,
                    'brand_name' => $customerDevice?->device?->brand?->name ?? null,
                    'device_name' => $customerDevice?->device?->model ?? null,
                ];
            })->values()->all();
        }

        // Get job items (services/parts)
        $items = RepairBuddyJobItem::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'asc')
            ->limit(500)
            ->get();

        $serializedItems = $items->map(function (RepairBuddyJobItem $i) {
            return [
                'id' => $i->id,
                'item_type' => $i->item_type,
                'name' => $i->name_snapshot,
                'qty' => $i->qty,
            ];
        })->values()->all();

        return [
            'id' => $job->id,
            'case_number' => $job->case_number,
            'title' => $job->title,
            'status' => $job->status_slug,
            'status_label' => $statusLabel ?? ucfirst(str_replace('_', ' ', $job->status_slug ?? '')),
            'payment_status' => $job->payment_status_slug,
            'payment_status_label' => $paymentStatusLabel ?? ucfirst(str_replace('_', ' ', $job->payment_status_slug ?? '')),
            'priority' => $job->priority,
            'case_detail' => $job->case_detail,
            'pickup_date' => $job->pickup_date?->format('M j, Y'),
            'delivery_date' => $job->delivery_date?->format('M j, Y'),
            'created_at' => $job->created_at?->format('M j, Y g:i A'),
            'updated_at' => $job->updated_at?->format('M j, Y g:i A'),
            'devices' => $devices,
            'items' => $serializedItems,
            'timeline' => $timeline,
        ];
    }

    protected function serializeEstimateDetail(RepairBuddyEstimate $estimate): array
    {
        $statusKey = is_string($estimate->status) ? (string) $estimate->status : 'pending';
        $statusLabel = ucwords(str_replace('_', ' ', $statusKey));

        return [
            'id' => $estimate->id,
            'case_number' => $estimate->case_number,
            'title' => $estimate->title,
            'status' => $statusKey,
            'status_label' => $statusLabel,
            'created_at' => $estimate->created_at?->format('M j, Y g:i A'),
            'updated_at' => $estimate->updated_at?->format('M j, Y g:i A'),
        ];
    }

    /* ─────────── Status Class Helpers ─────────── */

    public function getStatusClass(?string $status): string
    {
        $s = strtolower($status ?? '');
        if (in_array($s, ['delivered', 'completed', 'ready_for_pickup'])) {
            return 'success';
        }
        if (in_array($s, ['ready', 'waiting_for_parts'])) {
            return 'warning';
        }
        if (in_array($s, ['cancelled', 'rejected'])) {
            return 'danger';
        }
        if (in_array($s, ['in_process', 'diagnosing', 'repairing'])) {
            return 'info';
        }

        return 'secondary';
    }

    public function getPaymentStatusClass(?string $status): string
    {
        $s = strtolower($status ?? '');
        if (in_array($s, ['paid', 'completed'])) {
            return 'success';
        }
        if (in_array($s, ['partial', 'pending'])) {
            return 'warning';
        }
        if (in_array($s, ['unpaid', 'due'])) {
            return 'danger';
        }

        return 'secondary';
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.job-status.status-form');
    }
}

<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\Status;
use App\Models\TenantStatusOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairBuddyStatusController extends Controller
{
    public function lookup(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $job = RepairBuddyJob::query()->where('case_number', $caseNumber)->first();

        if ($job) {
            $status = Status::query()
                ->where('status_type', 'Job')
                ->where('code', $job->status_slug)
                ->first();
            $override = TenantStatusOverride::query()
                ->where('domain', 'job')
                ->where('code', $job->status_slug)
                ->first();

            $statusLabel = $status?->label;
            if (is_string($override?->label) && $override->label !== '') {
                $statusLabel = $override->label;
            }

            return response()->json([
                'entity_type' => 'job',
                'job' => [
                    'id' => $job->id,
                    'case_number' => $job->case_number,
                    'title' => $job->title,
                    'status' => $job->status_slug,
                    'status_label' => $statusLabel,
                    'updated_at' => $job->updated_at,
                ],
            ]);
        }

        $estimate = RepairBuddyEstimate::query()
            ->where('case_number', $caseNumber)
            ->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Case not found.',
            ], 404);
        }

        $statusKey = is_string($estimate->status) ? (string) $estimate->status : 'pending';
        $statusLabel = ucwords(str_replace('_', ' ', $statusKey));

        return response()->json([
            'entity_type' => 'estimate',
            'estimate' => [
                'id' => $estimate->id,
                'case_number' => $estimate->case_number,
                'title' => $estimate->title,
                'status' => $statusKey,
                'status_label' => $statusLabel,
                'updated_at' => $estimate->updated_at,
            ],
        ]);
    }

    public function message(Request $request, string $business, string $caseNumber)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $caseNumber = trim($caseNumber);
        if ($caseNumber === '') {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->where('case_number', $caseNumber)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $body = trim((string) $validated['message']);
        if ($body === '') {
            return response()->json([
                'message' => 'Message is required.',
            ], 422);
        }

        $event = RepairBuddyEvent::query()->create([
            'actor_user_id' => null,
            'entity_type' => 'job',
            'entity_id' => $job->id,
            'visibility' => 'public',
            'event_type' => 'customer.message',
            'payload_json' => [
                'title' => 'Customer message',
                'message' => $body,
            ],
        ]);

        return response()->json([
            'message' => 'Message received.',
            'event_id' => $event->id,
        ], 201);
    }

    /**
     * Get detailed job information by case number (public endpoint).
     * Returns job details including timeline, devices, items, and messages.
     */
    public function detail(Request $request, string $business, string $caseNumber)
    {
        $caseNumber = trim($caseNumber);
        if ($caseNumber === '') {
            return response()->json([
                'message' => 'Case number is required.',
            ], 422);
        }

        // First try to find a job
        $job = RepairBuddyJob::query()
            ->with(['jobDevices.customerDevice'])
            ->where('case_number', $caseNumber)
            ->first();

        if ($job) {
            return response()->json([
                'entity_type' => 'job',
                'job' => $this->serializeJobDetail($job),
            ]);
        }

        // Then try to find an estimate
        $estimate = RepairBuddyEstimate::query()
            ->where('case_number', $caseNumber)
            ->first();

        if ($estimate) {
            return response()->json([
                'entity_type' => 'estimate',
                'estimate' => $this->serializeEstimateDetail($estimate),
            ]);
        }

        return response()->json([
            'message' => 'Case not found.',
        ], 404);
    }

    /**
     * Upload attachment for a job by case number (public endpoint).
     */
    public function uploadAttachment(Request $request, string $business, string $caseNumber)
    {
        $caseNumber = trim($caseNumber);
        if ($caseNumber === '') {
            return response()->json([
                'message' => 'Case number is required.',
            ], 422);
        }

        $job = RepairBuddyJob::query()->where('case_number', $caseNumber)->first();
        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $sizeBytes = $file->getSize();

        // Store the file
        $storagePath = $file->store("tenant_{$job->tenant_id}/job_attachments", 'public');
        $url = Storage::disk('public')->url($storagePath);

        // Create event with attachment info
        $event = RepairBuddyEvent::query()->create([
            'actor_user_id' => null,
            'entity_type' => 'job',
            'entity_id' => $job->id,
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

        return response()->json([
            'message' => 'File uploaded successfully.',
            'event_id' => $event->id,
            'attachment' => [
                'filename' => $originalName,
                'url' => $url,
            ],
        ], 201);
    }

    private function serializeJobDetail(RepairBuddyJob $job): array
    {
        // Get job status label
        $status = Status::query()
            ->where('status_type', 'Job')
            ->where('code', $job->status_slug)
            ->first();
        $statusOverride = TenantStatusOverride::query()
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
                'created_at' => $e->created_at,
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
                    'type_name' => $customerDevice?->deviceType?->name ?? null,
                    'brand_name' => $customerDevice?->deviceBrand?->name ?? null,
                    'device_name' => $customerDevice?->device?->model ?? null,
                ];
            })->values()->all();
        }

        // Get job items (services/parts) - only show name and qty, not prices for public
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
            'status_label' => $statusLabel,
            'payment_status' => $job->payment_status_slug,
            'payment_status_label' => $paymentStatusLabel,
            'priority' => $job->priority,
            'case_detail' => $job->case_detail,
            'pickup_date' => $job->pickup_date,
            'delivery_date' => $job->delivery_date,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
            'devices' => $devices,
            'items' => $serializedItems,
            'timeline' => $timeline,
        ];
    }

    private function serializeEstimateDetail(RepairBuddyEstimate $estimate): array
    {
        $statusKey = is_string($estimate->status) ? (string) $estimate->status : 'pending';
        $statusLabel = ucwords(str_replace('_', ' ', $statusKey));

        return [
            'id' => $estimate->id,
            'case_number' => $estimate->case_number,
            'title' => $estimate->title,
            'status' => $statusKey,
            'status_label' => $statusLabel,
            'created_at' => $estimate->created_at,
            'updated_at' => $estimate->updated_at,
        ];
    }
}

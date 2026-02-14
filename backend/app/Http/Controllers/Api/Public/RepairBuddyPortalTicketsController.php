<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\Status;
use App\Models\TenantStatusOverride;
use Illuminate\Http\Request;

class RepairBuddyPortalTicketsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $job = RepairBuddyJob::query()
            ->where('case_number', $caseNumber)
            ->first();

        if (! $job) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        return response()->json([
            'tickets' => [
                $this->serializeTicketSummary($job),
            ],
        ]);
    }

    public function show(Request $request, string $business, string $jobId)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()
            ->whereKey((int) $jobId)
            ->where('case_number', $caseNumber)
            ->first();

        if (! $job) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        $events = RepairBuddyEvent::query()
            ->where('entity_type', 'job')
            ->where('entity_id', $job->id)
            ->where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

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
            'ticket' => [
                'id' => $job->id,
                'case_number' => $job->case_number,
                'title' => $job->title,
                'status' => $job->status_slug,
                'status_label' => $statusLabel,
                'updated_at' => $job->updated_at,
                'timeline' => $events->map(function (RepairBuddyEvent $e) {
                    $payload = is_array($e->payload_json) ? $e->payload_json : [];
                    $title = is_string($payload['title'] ?? null) ? $payload['title'] : (string) $e->event_type;
                    $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;

                    return [
                        'id' => (string) $e->id,
                        'title' => $title,
                        'type' => (string) $e->event_type,
                        'message' => $message,
                        'created_at' => $e->created_at,
                        'visibility' => (string) $e->visibility,
                    ];
                })->values()->all(),
            ],
        ]);
    }

    private function serializeTicketSummary(RepairBuddyJob $job): array
    {
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

        return [
            'id' => $job->id,
            'case_number' => $job->case_number,
            'title' => $job->title,
            'status' => $job->status_slug,
            'status_label' => $statusLabel,
            'updated_at' => $job->updated_at,
        ];
    }
}

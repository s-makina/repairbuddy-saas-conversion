<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobStatus;
use App\Models\TenantStatusOverride;
use Illuminate\Http\Request;

class RepairBuddyStatusController extends Controller
{
    public function lookup(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $job = RepairBuddyJob::query()->where('case_number', $caseNumber)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $status = RepairBuddyJobStatus::query()->where('slug', $job->status_slug)->first();
        $override = TenantStatusOverride::query()
            ->where('domain', 'job')
            ->where('code', $job->status_slug)
            ->first();

        $statusLabel = $status?->label;
        if (is_string($override?->label) && $override->label !== '') {
            $statusLabel = $override->label;
        }

        return response()->json([
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
}

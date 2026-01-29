<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use Illuminate\Http\Request;

class RepairBuddyJobEventController extends Controller
{
    public function index(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $events = RepairBuddyEvent::query()
            ->where('entity_type', 'job')
            ->where('entity_id', $job->id)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'events' => $events->map(fn (RepairBuddyEvent $e) => $this->serializeEvent($e)),
        ]);
    }

    public function store(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $event = RepairBuddyEvent::query()->create([
            'actor_user_id' => $request->user()?->id,
            'entity_type' => 'job',
            'entity_id' => $job->id,
            'visibility' => 'private',
            'event_type' => 'note',
            'payload_json' => [
                'title' => 'Internal note',
                'message' => trim((string) $validated['message']),
            ],
        ]);

        return response()->json([
            'event' => $this->serializeEvent($event),
        ], 201);
    }

    private function serializeEvent(RepairBuddyEvent $event): array
    {
        $payload = is_array($event->payload_json) ? $event->payload_json : [];
        $title = is_string($payload['title'] ?? null) ? $payload['title'] : null;

        if (! $title) {
            $title = match ((string) $event->event_type) {
                'job.created' => 'Job created',
                'note' => 'Internal note',
                default => (string) $event->event_type,
            };
        }

        return [
            'id' => $event->id,
            'title' => $title,
            'type' => $event->event_type,
            'visibility' => $event->visibility,
            'payload' => $payload,
            'created_at' => $event->created_at,
            'actor_user_id' => $event->actor_user_id,
        ];
    }
}

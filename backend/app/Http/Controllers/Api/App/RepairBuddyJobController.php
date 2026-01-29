<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCaseCounter;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairBuddyJobController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 100;

        $query = RepairBuddyJob::query()->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('case_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $jobs = $query->limit($limit)->get();

        return response()->json([
            'jobs' => $jobs->map(fn (RepairBuddyJob $j) => $this->serializeJob($j, includeTimeline: false)),
        ]);
    }

    public function show(Request $request, string $business, $jobId)
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

        return response()->json([
            'job' => $this->serializeJob($job, includeTimeline: true),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $statusSlug = is_string($validated['status_slug'] ?? null) && $validated['status_slug'] !== ''
            ? $validated['status_slug']
            : 'new_quote';

        $statusExists = RepairBuddyJobStatus::query()->where('slug', $statusSlug)->exists();
        if (! $statusExists) {
            return response()->json([
                'message' => 'Job status is invalid.',
            ], 422);
        }

        $caseNumber = $this->generateCaseNumber();

        $job = DB::transaction(function () use ($caseNumber, $request, $statusSlug, $validated) {
            $job = RepairBuddyJob::query()->create([
                'case_number' => $caseNumber,
                'title' => $validated['title'],
                'status_slug' => $statusSlug,
                'payment_status_slug' => $validated['payment_status_slug'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'created_by' => $request->user()?->id,
                'opened_at' => now(),
            ]);

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $request->user()?->id,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.created',
                'payload_json' => [
                    'title' => 'Job created',
                    'case_number' => $job->case_number,
                ],
            ]);

            return $job;
        });

        return response()->json([
            'job' => $this->serializeJob($job, includeTimeline: true),
        ], 201);
    }

    public function update(Request $request, string $business, $jobId)
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('status_slug', $validated) && is_string($validated['status_slug']) && $validated['status_slug'] !== '') {
            $statusExists = RepairBuddyJobStatus::query()->where('slug', $validated['status_slug'])->exists();
            if (! $statusExists) {
                return response()->json([
                    'message' => 'Job status is invalid.',
                ], 422);
            }
        }

        $job->forceFill([
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $job->title,
            'status_slug' => array_key_exists('status_slug', $validated) ? ($validated['status_slug'] ?: null) : $job->status_slug,
            'payment_status_slug' => array_key_exists('payment_status_slug', $validated) ? $validated['payment_status_slug'] : $job->payment_status_slug,
            'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $job->priority,
            'customer_id' => array_key_exists('customer_id', $validated) ? $validated['customer_id'] : $job->customer_id,
        ])->save();

        return response()->json([
            'job' => $this->serializeJob($job->fresh()),
        ]);
    }

    private function generateCaseNumber(): string
    {
        $branch = $this->branch();

        $prefix = is_string($branch->rb_case_prefix) ? trim($branch->rb_case_prefix) : '';
        $digits = is_numeric($branch->rb_case_digits) ? (int) $branch->rb_case_digits : 6;
        $digits = max(1, min(12, $digits));

        $issuedNumber = DB::transaction(function () {
            $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();

            if (! $counter) {
                $counter = RepairBuddyCaseCounter::query()->create([
                    'next_number' => 1,
                ]);
                $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();
            }

            $n = (int) ($counter->next_number ?? 1);

            $counter->forceFill([
                'next_number' => $n + 1,
            ])->save();

            return $n;
        });

        $numberPart = str_pad((string) $issuedNumber, $digits, '0', STR_PAD_LEFT);

        if ($prefix === '') {
            return $numberPart;
        }

        return $prefix.'-'.$numberPart;
    }

    private function serializeJob(RepairBuddyJob $job, bool $includeTimeline = false): array
    {
        $timeline = [];
        if ($includeTimeline) {
            $events = RepairBuddyEvent::query()
                ->where('entity_type', 'job')
                ->where('entity_id', $job->id)
                ->orderBy('created_at', 'desc')
                ->limit(200)
                ->get();

            $timeline = $events->map(function (RepairBuddyEvent $e) {
                $payload = is_array($e->payload_json) ? $e->payload_json : [];
                $title = is_string($payload['title'] ?? null) ? $payload['title'] : null;
                if (! $title) {
                    $title = match ((string) $e->event_type) {
                        'job.created' => 'Job created',
                        'note' => 'Internal note',
                        default => (string) $e->event_type,
                    };
                }

                $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;

                return [
                    'id' => (string) $e->id,
                    'title' => $title,
                    'type' => (string) $e->event_type,
                    'message' => $message,
                    'created_at' => $e->created_at,
                ];
            })->all();
        }

        return [
            'id' => $job->id,
            'case_number' => $job->case_number,
            'title' => $job->title,
            'status' => $job->status_slug,
            'payment_status' => $job->payment_status_slug,
            'priority' => $job->priority,
            'customer_id' => $job->customer_id,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
            'timeline' => $timeline,
            'messages' => [],
            'attachments' => [],
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyTax;
use App\Models\RepairBuddyTimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairBuddyTimeLogController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_id' => ['sometimes', 'nullable', 'integer'],
            'technician_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string', 'max:20'],
            'activity' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $jobId = array_key_exists('job_id', $validated) && is_numeric($validated['job_id']) ? (int) $validated['job_id'] : null;
        $technicianId = array_key_exists('technician_id', $validated) && is_numeric($validated['technician_id']) ? (int) $validated['technician_id'] : null;
        $status = is_string($validated['status'] ?? null) ? strtolower(trim((string) $validated['status'])) : '';
        $activity = is_string($validated['activity'] ?? null) ? trim((string) $validated['activity']) : '';
        $dateFrom = array_key_exists('date_from', $validated) ? $validated['date_from'] : null;
        $dateTo = array_key_exists('date_to', $validated) ? $validated['date_to'] : null;

        $perPage = is_numeric($validated['per_page'] ?? null) ? (int) $validated['per_page'] : 10;
        $perPage = max(1, min(100, $perPage));

        $query = RepairBuddyTimeLog::query()
            ->with([
                'job:id,case_number,title,status_slug',
                'technician:id,name,email',
            ]);

        if ($jobId) {
            $query->where('job_id', $jobId);
        }

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        if ($status !== '') {
            if (in_array($status, ['pending', 'approved', 'rejected', 'billed'], true)) {
                $query->where('log_state', $status);
            }
        }

        if ($activity !== '') {
            $query->where('activity', $activity);
        }

        if ($dateFrom) {
            $query->whereDate('start_time', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('start_time', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('activity', 'like', "%{$q}%")
                    ->orWhere('work_description', 'like', "%{$q}%")
                    ->orWhereHas('job', function ($job) use ($q) {
                        $job->where('case_number', 'like', "%{$q}%")
                            ->orWhere('title', 'like', "%{$q}%");
                    })
                    ->orWhereHas('technician', function ($t) use ($q) {
                        $t->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $summaryRow = (clone $query)
            ->selectRaw('COUNT(*) as total_logs')
            ->selectRaw('COALESCE(SUM(total_minutes), 0) as total_minutes')
            ->selectRaw('AVG(hourly_rate_cents) as avg_rate_cents')
            ->selectRaw('AVG(hourly_cost_cents) as avg_cost_cents')
            ->selectRaw('COALESCE(SUM((COALESCE(total_minutes, 0) * COALESCE(hourly_rate_cents, 0)) / 60.0), 0) as total_amount_cents')
            ->selectRaw('COALESCE(SUM((COALESCE(total_minutes, 0) * COALESCE(hourly_cost_cents, 0)) / 60.0), 0) as total_cost_cents')
            ->first();

        $query->orderByDesc('start_time')->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        $logs = collect($paginator->items());

        $currentTotals = [
            'charged_cents' => 0,
            'cost_cents' => 0,
            'profit_cents' => 0,
        ];

        $serialized = $logs->map(function (RepairBuddyTimeLog $tl) use (&$currentTotals) {
            $rate = is_numeric($tl->hourly_rate_cents) ? (int) $tl->hourly_rate_cents : 0;
            $cost = is_numeric($tl->hourly_cost_cents) ? (int) $tl->hourly_cost_cents : 0;
            $minutes = is_numeric($tl->total_minutes) ? (int) $tl->total_minutes : 0;

            $charged = (int) round(($minutes * $rate) / 60);
            $costAmount = (int) round(($minutes * $cost) / 60);

            $currentTotals['charged_cents'] += $charged;
            $currentTotals['cost_cents'] += $costAmount;
            $currentTotals['profit_cents'] += ($charged - $costAmount);

            return $this->serializeTimeLog($tl);
        })->values();

        $totalAmountCents = is_numeric($summaryRow?->total_amount_cents ?? null) ? (int) round((float) $summaryRow->total_amount_cents) : 0;
        $totalCostCents = is_numeric($summaryRow?->total_cost_cents ?? null) ? (int) round((float) $summaryRow->total_cost_cents) : 0;

        return response()->json([
            'time_logs' => $serialized,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'summary' => [
                'total_logs' => is_numeric($summaryRow?->total_logs ?? null) ? (int) $summaryRow->total_logs : 0,
                'total_minutes' => is_numeric($summaryRow?->total_minutes ?? null) ? (int) $summaryRow->total_minutes : 0,
                'total_hours' => is_numeric($summaryRow?->total_minutes ?? null) ? ((int) $summaryRow->total_minutes / 60) : 0,
                'avg_rate_cents' => is_numeric($summaryRow?->avg_rate_cents ?? null) ? (int) round((float) $summaryRow->avg_rate_cents) : 0,
                'avg_cost_cents' => is_numeric($summaryRow?->avg_cost_cents ?? null) ? (int) round((float) $summaryRow->avg_cost_cents) : 0,
                'total_amount_cents' => $totalAmountCents,
                'total_cost_cents' => $totalCostCents,
                'total_profit_cents' => $totalAmountCents - $totalCostCents,
                'current_totals' => $currentTotals,
            ],
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer'],
            'start_time' => ['required', 'date'],
            'end_time' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_time'],
            'time_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'activity' => ['required', 'string', 'max:100'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:20'],
            'work_description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'device_data' => ['sometimes', 'nullable', 'array'],
            'is_billable' => ['sometimes', 'boolean'],
        ]);

        $jobId = (int) $validated['job_id'];
        $job = RepairBuddyJob::query()->whereKey($jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $this->assertTimeLogsEnabledForJob($job);

        $user = $request->user();
        $technicianId = $user?->id;
        if (! is_numeric($technicianId)) {
            abort(403, 'Forbidden.');
        }

        $startTime = $validated['start_time'];
        $endTime = array_key_exists('end_time', $validated) ? $validated['end_time'] : null;

        $totalMinutes = null;
        if ($endTime) {
            $start = \Illuminate\Support\Carbon::parse($startTime);
            $end = \Illuminate\Support\Carbon::parse($endTime);
            $totalMinutes = $start->diffInMinutes($end);

            if ($totalMinutes <= 0) {
                return response()->json([
                    'message' => 'Total time must be greater than zero.',
                ], 422);
            }
        }

        $rateCents = is_numeric($user?->client_hourly_rate_cents) ? (int) $user->client_hourly_rate_cents : null;
        $costCents = is_numeric($user?->tech_hourly_rate_cents) ? (int) $user->tech_hourly_rate_cents : null;

        $currency = is_string($this->tenant()->currency) && $this->tenant()->currency !== '' ? strtoupper((string) $this->tenant()->currency) : 'USD';

        $timeLog = RepairBuddyTimeLog::query()->create([
            'job_id' => $job->id,
            'technician_id' => (int) $technicianId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_type' => is_string($validated['time_type'] ?? null) && $validated['time_type'] !== '' ? $validated['time_type'] : 'time_charge',
            'activity' => trim((string) $validated['activity']),
            'priority' => is_string($validated['priority'] ?? null) && $validated['priority'] !== '' ? $validated['priority'] : 'medium',
            'work_description' => is_string($validated['work_description'] ?? null) ? trim((string) $validated['work_description']) : null,
            'device_data_json' => $validated['device_data'] ?? null,
            'log_state' => 'pending',
            'total_minutes' => $totalMinutes,
            'hourly_rate_cents' => $rateCents,
            'hourly_cost_cents' => $costCents,
            'currency' => $currency,
            'is_billable' => array_key_exists('is_billable', $validated) ? (bool) $validated['is_billable'] : true,
        ]);

        return response()->json([
            'time_log' => $this->serializeTimeLog($timeLog->fresh(['job:id,case_number,title,status_slug', 'technician:id,name,email'])),
        ], 201);
    }

    public function update(Request $request, string $business, int $timeLogId)
    {
        $timeLog = RepairBuddyTimeLog::query()->whereKey($timeLogId)->first();

        if (! $timeLog) {
            return response()->json([
                'message' => 'Time log not found.',
            ], 404);
        }

        if ((string) $timeLog->log_state === 'billed') {
            return response()->json([
                'message' => 'Time log is billed and cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'nullable', 'date'],
            'time_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'activity' => ['sometimes', 'string', 'max:100'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:20'],
            'work_description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'device_data' => ['sometimes', 'nullable', 'array'],
            'is_billable' => ['sometimes', 'boolean'],
            'log_state' => ['sometimes', 'string', 'in:pending,approved,rejected'],
            'rejection_reason' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        $nextStart = array_key_exists('start_time', $validated) ? $validated['start_time'] : $timeLog->start_time;
        $nextEnd = array_key_exists('end_time', $validated) ? $validated['end_time'] : $timeLog->end_time;

        $totalMinutes = $timeLog->total_minutes;

        if ($nextEnd) {
            $start = \Illuminate\Support\Carbon::parse($nextStart);
            $end = \Illuminate\Support\Carbon::parse($nextEnd);

            if ($end->lessThan($start)) {
                return response()->json([
                    'message' => 'End time must be after start time.',
                ], 422);
            }

            $minutes = $start->diffInMinutes($end);

            if ($minutes <= 0) {
                return response()->json([
                    'message' => 'Total time must be greater than zero.',
                ], 422);
            }

            $totalMinutes = $minutes;
        } else {
            $totalMinutes = null;
        }

        $actorUserId = $request->user()?->id;

        $nextState = array_key_exists('log_state', $validated) ? (string) $validated['log_state'] : (string) $timeLog->log_state;
        $approvedBy = $timeLog->approved_by;
        $approvedAt = $timeLog->approved_at;
        $rejectionReason = $timeLog->rejection_reason;

        if ($nextState === 'approved') {
            $approvedBy = is_numeric($actorUserId) ? (int) $actorUserId : $approvedBy;
            $approvedAt = now();
            $rejectionReason = null;
        } elseif ($nextState === 'rejected') {
            $approvedBy = is_numeric($actorUserId) ? (int) $actorUserId : $approvedBy;
            $approvedAt = null;
            if (array_key_exists('rejection_reason', $validated)) {
                $rejectionReason = $validated['rejection_reason'] !== null ? trim((string) $validated['rejection_reason']) : null;
            }
        } elseif ($nextState === 'pending') {
            $approvedBy = null;
            $approvedAt = null;
            $rejectionReason = null;
        }

        $timeLog->forceFill([
            'start_time' => $nextStart,
            'end_time' => $nextEnd,
            'time_type' => array_key_exists('time_type', $validated) ? $validated['time_type'] : $timeLog->time_type,
            'activity' => array_key_exists('activity', $validated) ? trim((string) $validated['activity']) : $timeLog->activity,
            'priority' => array_key_exists('priority', $validated) ? ($validated['priority'] ?: null) : $timeLog->priority,
            'work_description' => array_key_exists('work_description', $validated) ? ($validated['work_description'] !== null ? trim((string) $validated['work_description']) : null) : $timeLog->work_description,
            'device_data_json' => array_key_exists('device_data', $validated) ? ($validated['device_data'] ?? null) : $timeLog->device_data_json,
            'is_billable' => array_key_exists('is_billable', $validated) ? (bool) $validated['is_billable'] : $timeLog->is_billable,
            'log_state' => $nextState,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'rejection_reason' => $rejectionReason,
            'total_minutes' => $totalMinutes,
        ])->save();

        return response()->json([
            'time_log' => $this->serializeTimeLog($timeLog->fresh(['job:id,case_number,title,status_slug', 'technician:id,name,email'])),
        ]);
    }

    public function bill(Request $request, string $business, int $timeLogId)
    {
        $timeLog = RepairBuddyTimeLog::query()->with('job')->whereKey($timeLogId)->first();

        if (! $timeLog) {
            return response()->json([
                'message' => 'Time log not found.',
            ], 404);
        }

        if (! $timeLog->job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $this->assertTimeLogsEnabledForJob($timeLog->job);

        if (! $timeLog->is_billable) {
            return response()->json([
                'message' => 'Time log is not billable.',
            ], 422);
        }

        if ((string) $timeLog->log_state === 'billed') {
            return response()->json([
                'message' => 'Time log is already billed.',
            ], 422);
        }

        $minutes = is_numeric($timeLog->total_minutes) ? (int) $timeLog->total_minutes : 0;
        if ($minutes <= 0) {
            return response()->json([
                'message' => 'Total time must be greater than zero.',
            ], 422);
        }

        $rate = is_numeric($timeLog->hourly_rate_cents) ? (int) $timeLog->hourly_rate_cents : 0;
        if ($rate <= 0) {
            return response()->json([
                'message' => 'Hourly rate is required to bill.',
            ], 422);
        }

        $currency = is_string($timeLog->currency) && $timeLog->currency !== '' ? strtoupper((string) $timeLog->currency) : (string) ($this->tenant()->currency ?? 'USD');

        $totalCents = (int) round(($minutes * $rate) / 60);

        $settings = data_get($this->tenant()->setup_state ?? [], 'repairbuddy_settings', []);
        $defaultTax = data_get($settings, 'timeLogs.defaultTaxIdForHours');
        $taxId = is_numeric($defaultTax) ? (int) $defaultTax : null;
        if (! $taxId) {
            $taxId = RepairBuddyTax::query()->where('is_default', true)->value('id');
            $taxId = is_numeric($taxId) ? (int) $taxId : null;
        }

        $result = DB::transaction(function () use ($timeLog, $currency, $totalCents, $taxId) {
            $item = RepairBuddyJobItem::query()->create([
                'job_id' => $timeLog->job_id,
                'item_type' => 'fee',
                'ref_id' => null,
                'name_snapshot' => ucfirst((string) $timeLog->activity) . ' - Time Log',
                'qty' => 1,
                'unit_price_amount_cents' => $totalCents,
                'unit_price_currency' => $currency,
                'tax_id' => $taxId,
                'meta_json' => [
                    'source' => 'time_log',
                    'time_log_id' => $timeLog->id,
                    'total_minutes' => $timeLog->total_minutes,
                    'hourly_rate_cents' => $timeLog->hourly_rate_cents,
                    'hourly_cost_cents' => $timeLog->hourly_cost_cents,
                    'technician_id' => $timeLog->technician_id,
                    'device_data' => $timeLog->device_data_json,
                ],
            ]);

            $timeLog->forceFill([
                'log_state' => 'billed',
                'billed_job_item_id' => $item->id,
            ])->save();

            RepairBuddyEvent::query()->create([
                'actor_user_id' => request()->user()?->id,
                'entity_type' => 'job',
                'entity_id' => $timeLog->job_id,
                'visibility' => 'public',
                'event_type' => 'time_log.billed',
                'payload_json' => [
                    'time_log_id' => $timeLog->id,
                    'job_item_id' => $item->id,
                    'total_minutes' => $timeLog->total_minutes,
                    'total_amount_cents' => $totalCents,
                    'currency' => $currency,
                ],
            ]);

            return $item;
        });

        return response()->json([
            'message' => 'Billed.',
            'time_log' => $this->serializeTimeLog($timeLog->fresh(['job:id,case_number,title,status_slug', 'technician:id,name,email'])),
            'job_item' => $result,
        ]);
    }

    private function assertTimeLogsEnabledForJob(RepairBuddyJob $job): void
    {
        $settings = data_get($this->tenant()->setup_state ?? [], 'repairbuddy_settings', []);
        $timeLogs = is_array($settings) ? (data_get($settings, 'timeLogs') ?? []) : [];

        if (is_array($timeLogs) && (bool) ($timeLogs['disableTimeLog'] ?? false)) {
            abort(403, 'Time logs are disabled.');
        }

        $enabledStatuses = is_array($timeLogs) && isset($timeLogs['enableTimeLogForStatusIds']) && is_array($timeLogs['enableTimeLogForStatusIds'])
            ? $timeLogs['enableTimeLogForStatusIds']
            : [];

        if (count($enabledStatuses) > 0) {
            $jobStatusId = 'status_' . (string) ($job->status_slug ?? '');
            if ($jobStatusId === 'status_') {
                abort(422, 'Job status is missing.');
            }

            if (! in_array($jobStatusId, $enabledStatuses, true)) {
                abort(422, 'Time logs are not enabled for this job status.');
            }
        }
    }

    private function serializeTimeLog(RepairBuddyTimeLog $tl): array
    {
        $minutes = is_numeric($tl->total_minutes) ? (int) $tl->total_minutes : 0;
        $rate = is_numeric($tl->hourly_rate_cents) ? (int) $tl->hourly_rate_cents : 0;
        $cost = is_numeric($tl->hourly_cost_cents) ? (int) $tl->hourly_cost_cents : 0;

        $charged = (int) round(($minutes * $rate) / 60);
        $costAmount = (int) round(($minutes * $cost) / 60);

        $job = $tl->relationLoaded('job') ? $tl->job : null;
        $technician = $tl->relationLoaded('technician') ? $tl->technician : null;

        $currency = is_string($tl->currency) && $tl->currency !== '' ? strtoupper((string) $tl->currency) : (string) ($this->tenant()->currency ?? 'USD');

        return [
            'id' => $tl->id,
            'job_id' => $tl->job_id,
            'job' => $job ? [
                'id' => $job->id,
                'case_number' => $job->case_number,
                'title' => $job->title,
                'status_slug' => $job->status_slug,
            ] : null,
            'technician' => $technician ? [
                'id' => $technician->id,
                'name' => $technician->name,
                'email' => $technician->email,
            ] : null,
            'start_time' => $tl->start_time,
            'end_time' => $tl->end_time,
            'time_type' => $tl->time_type,
            'activity' => $tl->activity,
            'priority' => $tl->priority,
            'work_description' => $tl->work_description,
            'device_data' => is_array($tl->device_data_json) ? $tl->device_data_json : null,
            'log_state' => $tl->log_state,
            'total_minutes' => $tl->total_minutes,
            'is_billable' => (bool) $tl->is_billable,
            'hourly_rate' => [
                'currency' => $currency,
                'amount_cents' => $tl->hourly_rate_cents,
            ],
            'hourly_cost' => [
                'currency' => $currency,
                'amount_cents' => $tl->hourly_cost_cents,
            ],
            'charged_amount' => [
                'currency' => $currency,
                'amount_cents' => $charged,
            ],
            'cost_amount' => [
                'currency' => $currency,
                'amount_cents' => $costAmount,
            ],
            'profit_amount' => [
                'currency' => $currency,
                'amount_cents' => $charged - $costAmount,
            ],
            'billed_job_item_id' => $tl->billed_job_item_id,
            'created_at' => $tl->created_at,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyMaintenanceReminderLog;
use Illuminate\Http\Request;

class RepairBuddyMaintenanceReminderLogController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'reminder_id' => ['sometimes', 'nullable', 'integer'],
            'job_id' => ['sometimes', 'nullable', 'integer'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $reminderId = array_key_exists('reminder_id', $validated) && is_numeric($validated['reminder_id']) ? (int) $validated['reminder_id'] : null;
        $jobId = array_key_exists('job_id', $validated) && is_numeric($validated['job_id']) ? (int) $validated['job_id'] : null;
        $dateFrom = array_key_exists('date_from', $validated) ? $validated['date_from'] : null;
        $dateTo = array_key_exists('date_to', $validated) ? $validated['date_to'] : null;

        $perPage = is_numeric($validated['per_page'] ?? null) ? (int) $validated['per_page'] : 10;
        $perPage = max(1, min(100, $perPage));

        $query = RepairBuddyMaintenanceReminderLog::query()
            ->with([
                'reminder:id,name',
                'job:id,case_number,title',
                'customer:id,name,email,phone',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($reminderId) {
            $query->where('reminder_id', $reminderId);
        }

        if ($jobId) {
            $query->where('job_id', $jobId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('to_address', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%")
                    ->orWhere('channel', 'like', "%{$q}%")
                    ->orWhereHas('reminder', function ($r) use ($q) {
                        $r->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('job', function ($j) use ($q) {
                        $j->where('case_number', 'like', "%{$q}%")
                            ->orWhere('title', 'like', "%{$q}%");
                    })
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        $logs = collect($paginator->items());

        return response()->json([
            'logs' => $logs->map(function (RepairBuddyMaintenanceReminderLog $l) {
                return [
                    'id' => $l->id,
                    'created_at' => $l->created_at,
                    'reminder' => $l->reminder ? ['id' => $l->reminder->id, 'name' => $l->reminder->name] : null,
                    'job' => $l->job ? ['id' => $l->job->id, 'case_number' => $l->job->case_number, 'title' => $l->job->title] : null,
                    'customer' => $l->customer ? ['id' => $l->customer->id, 'name' => $l->customer->name] : null,
                    'channel' => $l->channel,
                    'to_address' => $l->to_address,
                    'status' => $l->status,
                    'error_message' => $l->error_message,
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}

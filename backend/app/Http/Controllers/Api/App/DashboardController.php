<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobItem;
use App\Models\TenantNote;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        $tenantCurrency = is_string($tenant?->currency) && $tenant?->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $closedStatuses = ['delivered', 'completed', 'cancelled'];
        $activeJobsCount = (int) RepairBuddyJob::query()
            ->whereNotIn('status_slug', $closedStatuses)
            ->count();

        $completedJobsCount = (int) RepairBuddyJob::query()
            ->whereIn('status_slug', ['delivered', 'completed'])
            ->count();

        $pendingEstimatesCount = (int) RepairBuddyEstimate::query()
            ->where('status', 'pending')
            ->count();

        $revenueFrom = now()->subDays(30);
        $revenueRow = RepairBuddyJobItem::query()
            ->select([
                DB::raw("COALESCE(NULLIF(unit_price_currency, ''), '{$tenantCurrency}') as currency"),
                DB::raw('SUM(qty * unit_price_amount_cents) as total_cents'),
            ])
            ->whereHas('job', function ($q) use ($revenueFrom) {
                $q->where('status_slug', '!=', 'cancelled')
                    ->where('updated_at', '>=', $revenueFrom);
            })
            ->groupBy('currency')
            ->orderByRaw('SUM(qty * unit_price_amount_cents) desc')
            ->first();

        $revenueLast30dCents = (int) ($revenueRow?->total_cents ?? 0);
        $revenueCurrency = is_string($revenueRow?->currency) && $revenueRow?->currency !== '' ? strtoupper((string) $revenueRow->currency) : $tenantCurrency;

        $recentJobs = RepairBuddyJob::query()
            ->select(['id', 'case_number', 'title', 'status_slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        $jobTotals = RepairBuddyJobItem::query()
            ->select([
                'job_id',
                DB::raw('SUM(qty * unit_price_amount_cents) as total_cents'),
                DB::raw("COALESCE(NULLIF(MAX(unit_price_currency), ''), '{$tenantCurrency}') as currency"),
            ])
            ->whereIn('job_id', $recentJobs->pluck('id')->all())
            ->groupBy('job_id')
            ->get()
            ->keyBy('job_id');

        $recentEstimates = RepairBuddyEstimate::query()
            ->select(['id', 'case_number', 'title', 'status', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        $estimateTotals = RepairBuddyEstimateItem::query()
            ->select([
                'estimate_id',
                DB::raw('SUM(qty * unit_price_amount_cents) as total_cents'),
                DB::raw("COALESCE(NULLIF(MAX(unit_price_currency), ''), '{$tenantCurrency}') as currency"),
            ])
            ->whereIn('estimate_id', $recentEstimates->pluck('id')->all())
            ->groupBy('estimate_id')
            ->get()
            ->keyBy('estimate_id');

        $activity = RepairBuddyEvent::query()
            ->with(['actor'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'tenant' => $tenant,
            'user' => $user,
            'metrics' => [
                'notes_count' => (int) TenantNote::query()->count(),
                'active_jobs_count' => $activeJobsCount,
                'completed_jobs_count' => $completedJobsCount,
                'pending_estimates_count' => $pendingEstimatesCount,
                'revenue_last_30d' => [
                    'currency' => $revenueCurrency,
                    'amount_cents' => $revenueLast30dCents,
                ],
            ],
            'recent' => [
                'jobs' => $recentJobs->map(function (RepairBuddyJob $job) use ($jobTotals, $tenantCurrency) {
                    $t = $jobTotals->get($job->id);
                    $currency = is_string($t?->currency) && $t?->currency !== '' ? strtoupper((string) $t->currency) : $tenantCurrency;

                    return [
                        'id' => (int) $job->id,
                        'case_number' => (string) ($job->case_number ?? ''),
                        'title' => (string) ($job->title ?? ''),
                        'status' => (string) ($job->status_slug ?? ''),
                        'total_cents' => (int) ($t?->total_cents ?? 0),
                        'currency' => $currency,
                        'updated_at' => optional($job->updated_at)->toISOString(),
                    ];
                })->values(),
                'estimates' => $recentEstimates->map(function (RepairBuddyEstimate $estimate) use ($estimateTotals, $tenantCurrency) {
                    $t = $estimateTotals->get($estimate->id);
                    $currency = is_string($t?->currency) && $t?->currency !== '' ? strtoupper((string) $t->currency) : $tenantCurrency;

                    return [
                        'id' => (int) $estimate->id,
                        'case_number' => (string) ($estimate->case_number ?? ''),
                        'title' => (string) ($estimate->title ?? ''),
                        'status' => (string) ($estimate->status ?? ''),
                        'total_cents' => (int) ($t?->total_cents ?? 0),
                        'currency' => $currency,
                        'updated_at' => optional($estimate->updated_at)->toISOString(),
                    ];
                })->values(),
            ],
            'activity' => $activity->map(function (RepairBuddyEvent $event) {
                $payload = is_array($event->payload_json) ? $event->payload_json : [];
                $summary = is_string($payload['summary'] ?? null) ? (string) $payload['summary'] : null;
                $description = is_string($payload['description'] ?? null) ? (string) $payload['description'] : null;

                return [
                    'id' => (int) $event->id,
                    'visibility' => (string) ($event->visibility ?? 'public'),
                    'event_type' => (string) ($event->event_type ?? ''),
                    'entity_type' => (string) ($event->entity_type ?? ''),
                    'entity_id' => (int) ($event->entity_id ?? 0),
                    'summary' => $summary,
                    'description' => $description,
                    'actor_email' => is_string($event->actor?->email) ? (string) $event->actor->email : null,
                    'created_at' => optional($event->created_at)->toISOString(),
                ];
            })->values(),
        ]);
    }
}
